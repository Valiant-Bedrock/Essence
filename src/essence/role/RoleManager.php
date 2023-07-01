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

namespace essence\role;

use Closure;
use essence\command\SetRoleCommand;
use essence\EssenceBase;
use essence\EssenceDatabaseKeys;
use essence\Manageable;
use essence\player\EssenceDataException;
use essence\player\EssencePlayerData;
use essence\session\PlayerSessionManager;
use Generator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function count;

final class RoleManager extends Manageable implements Listener {

	public function onEnable(EssenceBase $plugin): void {
		$plugin->getServer()->getCommandMap()->registerAll("essence", [new SetRoleCommand(plugin: $plugin)]);
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}

	public function onDisable(EssenceBase $plugin): void {
	}

	public function handleLoadOnLogin(PlayerLoginEvent $event): void {
		Await::g2c($this->loadPlayerData($event->getPlayer()));
	}

	public function handleSaveOnQuit(PlayerQuitEvent $event): void {
		Await::g2c($this->savePlayerData($event->getPlayer()));
	}

	/**
	 * @throws EssenceDataException
	 */
	public function resolvePlayerDataFromUsername(string $username): Generator {
		return yield from Await::promise(fn (Closure $resolve, Closure $reject) => $this->getPlugin()->getConnector()->executeSelect(
			queryName: EssenceDatabaseKeys::PLAYER_LOAD_BY_USERNAME,
			args: ["username" => $username],
			onSelect: function (array $rows) use($resolve, $reject): void {
				if (count($rows) !== 1) {
					$reject(new EssenceDataException("Expected 1 row, got " . count($rows)));
					return;
				}
				$resolve(EssencePlayerData::unmarshal($rows[0]));
			},
			onError: fn (Throwable $throwable) => $reject(new EssenceDataException($throwable->getMessage()))
		));
	}

	public function loadPlayerData(Player $player): Generator {
		try {
			$session = yield from PlayerSessionManager::getInstance()->create($player);
			$this->getLogger()->debug("Loaded player data for {$player->getName()}");
			$session->recalculatePermissions();
		} catch (EssenceDataException) {
			$this->getLogger()->error("Player failed to join due to an error loading their data.");
			$player->kick(TextFormat::RED . "An error occurred while loading your data. Please try again later.");
		}
	}

	public function savePlayerData(Player $player): Generator {
		$session = PlayerSessionManager::getInstance()->getOrNull($player);
		if ($session === null) {
			$this->getLogger()->debug("Player {$player->getName()} has no session to save. Ignoring.");
			return;
		}
		try {
			yield from $session->saveToDatabase();
			$this->getLogger()->debug("Saved player data for {$player->getName()}");
		} catch (EssenceDataException $e) {
			$this->getLogger()->error("Failed to save player data for {$player->getName()}");
		}
	}

}