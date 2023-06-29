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

use DateTime;
use essence\command\BanCommand;
use essence\command\UnbanCommand;
use essence\EssenceBase;
use essence\managers\Manageable;
use essence\session\PlayerExtradata;
use essence\translation\EssenceTranslationFactory;
use essence\translation\TranslationHandler;
use Generator;
use libMarshal\exception\UnmarshalException;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\player\Player;
use RuntimeException;
use SOFe\AwaitGenerator\Await;

final class BanManager extends Manageable implements Listener {

	/** @var array<Ban> */
	private array $bans = [];

	public function onEnable(EssenceBase $plugin): void {
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		$plugin->getServer()->getCommandMap()->registerAll("essence", [
			new BanCommand($plugin),
			new UnbanCommand($plugin),
		]);
	}

	public function onDisable(EssenceBase $plugin): void {
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

	public function fetchBan(Player $player): ?Ban {
		foreach ($this->bans as $ban) {
			if ($ban->isCurrent() && $ban->isAttachedTo($player)) {
				return $ban;
			}
		}
		return null;
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

	public function handleLogin(PlayerLoginEvent $event): void {
		// fetch in memory before going to DB
		$ban = $this->fetchBan($event->getPlayer());
		if ($ban !== null) {
			$event->cancel();
			$event->setKickMessage(TranslationHandler::getInstance()->translate(EssenceTranslationFactory::kick_ban(
				reason: $ban->reason,
				expires: $ban->getExpiryAsString()
			)));
			return;
		}
		Await::g2c($this->loadBansOnLogin($event->getPlayer()));
	}

	private function loadBansOnLogin(Player $player): Generator {
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