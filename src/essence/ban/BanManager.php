<?php

declare(strict_types=1);
/**
 * Copyright (C) 2020 - 2023 | Valiant Network
 *
 * This program is private software. You may not redistribute this software, or
 * any derivative works of this software, in source or binary form, without
 * the express permission of the owner.
 *
 * @author sylvrs
 */

namespace essence\ban;

use Closure;
use DateTime;
use essence\command\BanCommand;
use essence\command\UnbanCommand;
use essence\EssenceBase;
use essence\EssenceDatabaseKeys;
use essence\Manageable;
use essence\player\EssenceDataException;
use essence\session\PlayerExtradata;
use essence\translation\EssenceTranslationFactory;
use essence\translation\TranslationHandler;
use Generator;
use libMarshal\exception\UnmarshalException;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\player\Player;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\AssumptionFailedError;
use RuntimeException;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function array_map;
use function count;

final class BanManager extends Manageable implements Listener {
	protected const BAN_SYNC_TIME = 20 * 60;
	/** @var array<Ban> */
	private array $bans = [];

	public function onEnable(EssenceBase $plugin): void {
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		$plugin->getServer()->getCommandMap()->registerAll("essence", [
			new BanCommand($plugin),
			new UnbanCommand($plugin),
		]);
		$plugin->getScheduler()->scheduleRepeatingTask(
			task: new ClosureTask(function(): void { Await::g2c($this->syncBans()); }),
			period: self::BAN_SYNC_TIME
		);
	}

	public function onDisable(EssenceBase $plugin): void {
	}

	private function syncBans(): Generator {
		$beforeCount = count($this->bans);
		$this->bans = yield from $this->loadBans();
		$afterCount = count($this->bans);
		$this->getLogger()->debug("Synced bans. Before: $beforeCount, After: $afterCount");
		if ($beforeCount !== $afterCount) {
			$this->getLogger()->info(match (true) {
				$afterCount > $beforeCount => "Loaded " . ($afterCount - $beforeCount) . " new ban(s)",
				$afterCount < $beforeCount => "Removed " . ($beforeCount - $afterCount) . " ban(s)",
				default => throw new AssumptionFailedError("Ban count did not change"),
			});
		}
	}
	public function loadBans(): Generator {
		return yield from Await::promise(fn (Closure $resolve, Closure $reject) => EssenceBase::getInstance()->getConnector()->executeSelect(
			queryName: EssenceDatabaseKeys::BANS_LOAD_ALL,
			onSelect: fn (array $rows) => $resolve(array_map(fn (array $row) => Ban::unmarshal($row, false), $rows)),
			onError: fn (Throwable $throwable) => $reject(new EssenceDataException($throwable->getMessage()))
		));
	}

	public function ban(CommandSender $bannedBy, string $username, ?DateTime $expires, string $reason): Generator {
		$player = $this->plugin->getServer()->getPlayerExact($username);
		$xuid = $player?->getXuid() ?? "";
		$ipAddress = $player?->getNetworkSession()?->getIp() ?? "";
		if ($player instanceof Player) {
			$extraData = PlayerExtradata::unmarshal($player->getPlayerInfo()->getExtraData());
			$deviceId = $extraData->deviceId;
		} else {
			$deviceId = "";
		}
		$ban = new Ban(
			username: $username,
			xuid: $xuid,
			address: $ipAddress,
			deviceId: $deviceId,
			reason: $reason,
			bannedBy: $bannedBy->getName(),
			creationTime: new DateTime(),
			expiryTime: $expires,
		);
		yield from $ban->saveToDatabase();
		$this->insertBan($ban);
		$player?->kick(
			reason: TranslationHandler::getInstance()->translate(EssenceTranslationFactory::kick_ban(
				reason: $reason,
				expires: $ban->getExpiryAsString()
			)),
		);
		return $ban;
	}

	public function unban(CommandSender $unbannedBy, string $username): Generator {
		try {
			/** @var Ban[] $bans */
			$bans = yield from Ban::fromUsername(username: $username);
			foreach ($bans as $ban) {
				if (!$ban->isCurrent()) {
					continue;
				}
				$ban->unbannedBy = $unbannedBy->getName();
				yield from $ban->updateInDatabase();
				$this->removeBan($ban);
				return true;
			}
			return false;
		} catch (RuntimeException) {
			return false;
		}
	}

	/**
	 * @param string $username
	 * @return array<Ban>
	 */
	public function fetchBans(string $username): array {
		$bans = [];
		foreach ($this->bans as $ban) {
			if ($ban->isCurrent() && $ban->hasUsername($username)) {
				$bans[] = $ban;
			}
		}
		return $bans;
	}

	public function insertBan(Ban $ban): void {
		foreach ($this->bans as $current) {
			if ($ban->equals($current)) {
				return;
			}
		}
		$this->bans[] = $ban;
	}

	public function removeBan(Ban $ban): void {
		foreach($this->bans as $key => $current) {
			if ($ban->equals($current)) {
				unset($this->bans[$key]);
			}
		}
	}

	public function isBanned(string $username): Generator {
		try {
			/** @var Ban[] $bans */
			$bans = yield from Ban::fromUsername(username: $username);
			foreach ($bans as $ban) {
				if ($ban->isCurrent()) {
					return true;
				}
			}
			return false;
		} catch (RuntimeException) {
			return false;
		}
	}

	public function handlePreLogin(PlayerPreLoginEvent $event): void {
		$bans = $this->fetchBans($event->getPlayerInfo()->getUsername());
		if (count($bans) === 0) {
			return;
		}
		/** @var XboxLivePlayerInfo $info */
		$info = $event->getPlayerInfo();
		$extraData = PlayerExtradata::unmarshal($info->getExtraData());
		foreach ($bans as $ban) {
			if (!$ban->isCurrent()) {
				$this->removeBan($ban);
				continue;
			}

			$event->setKickReason(
				flag: PlayerPreLoginEvent::KICK_REASON_PLUGIN,
				message: TranslationHandler::getInstance()->translate(EssenceTranslationFactory::kick_ban(
					reason: $ban->reason,
					expires: $ban->getExpiryAsString()
				))
			);
			if ($ban->isMissingData()) {
				$ban->replaceMissingData($info->getXuid(), $event->getIp(), $extraData->deviceId);
				$this->getLogger()->warning("Missing data for ban entry: $ban");
				$this->getLogger()->warning("Updating ban entry...");
				Await::g2c($ban->updateInDatabase());
				return;
			}
			$comparedBan = $this->fromCurrent(
				username: $info->getUsername(),
				ip: $event->getIp(),
				xuid: $info->getXuid(),
				extraData: $extraData,
				previous: $ban
			);
			if ($comparedBan->equals($ban)) {
				continue;
			}
			$this->getLogger()->warning("Potential ban evasion detected:");
			$this->getLogger()->warning("Original ban: $ban");
			$this->getLogger()->warning("Current ban: $comparedBan");
			$this->getLogger()->warning("Creating new ban entry...");
			Await::g2c($comparedBan->saveToDatabase());
		}
	}

	public function checkBans(Player $player): Generator {
		// drop handler until verification
		$handler = $player->getNetworkSession()->getHandler();
		$player->getNetworkSession()->setHandler(null);
		try {
			if ($player->getXuid() === "") {
				$player->kick(reason: TranslationHandler::getInstance()->translate(EssenceTranslationFactory::kick_xbox_required()));
				return;
			}
			$info = $player->getPlayerInfo();
			$extraData = PlayerExtradata::unmarshal($info->getExtraData());
			/** @var Ban[] $bans */
			$bans = yield from Ban::fromDatabase(
				username: $player->getName(),
				xuid: $player->getXuid(),
				ipAddress: $player->getNetworkSession()->getIp(),
				deviceId: $extraData->deviceId
			);
			foreach ($bans as $ban) {
				if (!$ban->isCurrent()) {
					continue;
				}

				// add ban to list of bans if not already in there
				$this->insertBan($ban);

				$player->disconnect(TranslationHandler::getInstance()->translate(EssenceTranslationFactory::kick_ban(
					reason: $ban->reason,
					expires: $ban->getExpiryAsString()
				)));
				if ($ban->isMissingData()) {
					$ban->replaceMissingData($player->getXuid(), $player->getNetworkSession()->getIp(), $extraData->deviceId);
					$this->getLogger()->warning("Missing data for ban entry: $ban");
					$this->getLogger()->warning("Updating ban entry...");
					yield from $ban->updateInDatabase();
					return;
				}
				$comparedBan = $this->fromCurrent(
					username: $player->getName(),
					ip: $player->getNetworkSession()->getIp(),
					xuid: $player->getXuid(),
					extraData: $extraData,
					previous: $ban
				);
				if ($comparedBan->equals($ban)) {
					continue;
				}
				$this->getLogger()->warning("Potential ban evasion detected:");
				$this->getLogger()->warning("Original ban: $ban");
				$this->getLogger()->warning("Current ban: $comparedBan");
				$this->getLogger()->warning("Creating new ban entry...");
				yield from $comparedBan->saveToDatabase();
				return;
			}
		} catch (UnmarshalException) {
			$player->kick(reason: TranslationHandler::getInstance()->translate(EssenceTranslationFactory::kick_data_failure()));
		} finally {
			$player->getNetworkSession()->setHandler($handler);
		}

	}

	public function fromCurrent(string $username, string $ip, string $xuid, PlayerExtradata $extraData, Ban $previous): Ban {
		return new Ban(
			username: $username,
			xuid: $xuid,
			address: $ip,
			deviceId: $extraData->deviceId,
			reason: $previous->reason,
			bannedBy: $previous->bannedBy,
			creationTime: new DateTime(),
			expiryTime: $previous->expiryTime,
		);
	}
}