<?php
/**
 * Copyright (C) 2020 - 2023 | Valiant Network
 *
 * This program is private software. You may not redistribute this software, or
 * any derivative works of this software, in source or binary form, without
 * the express permission of the owner.
 *
 * @author sylvrs
 */
declare(strict_types=1);

namespace essence;

use Closure;
use essence\command\SetRoleCommand;
use essence\player\EssenceDataException;
use essence\player\EssencePlayerData;
use essence\session\PlayerSessionManager;
use Generator;
use libcommand\LibCommandBase;
use libcommand\VanillaCommandPatcher;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function array_rand;
use function assert;
use function count;

final class EssenceBase extends PluginBase {
	use SingletonTrait;

	public const MOTD_PREFIX = TextFormat::RED . TextFormat::BOLD . "Valiant" . TextFormat::RESET . TextFormat::WHITE . " - ";
	public const MOTD_MESSAGES = ["&eNOW IN BETA!"];

	private static string $environmentMode = "development";

	private DataConnector $connector;

	protected function onEnable(): void {
		// setup instance and environment mode
		self::setInstance($this);
		$this->setupEnvironmentMode();
		// setup database
		$this->saveResource("config.yml");
		$this->connector = libasynql::create(
			plugin: $this,
			configData: $this->getConfig()->get("database", []),
			sqlMap: ["mysql" => "mysql.sql"]
		);

		// register commands
		LibCommandBase::register($this);
		VanillaCommandPatcher::register($this);
		$this->getServer()->getCommandMap()->registerAll("essence", [
			new SetRoleCommand(plugin: $this)
		]);

		// finish setup
		$this->getServer()->getPluginManager()->registerEvents(listener: new EssenceListener($this), plugin: $this);
		$this->getServer()->getNetwork()->setName(self::MOTD_PREFIX . TextFormat::colorize(self::MOTD_MESSAGES[array_rand(self::MOTD_MESSAGES)]));
	}

	protected function onDisable(): void {
		$this->getConnector()->close();
	}

	public function getConnector(): DataConnector {
		return $this->connector;
	}

	/**
	 * @throws EssenceDataException
	 */
	public function resolvePlayerDataFromUsername(string $username): Generator {
		yield from Await::promise(fn (Closure $resolve, Closure $reject) => $this->getConnector()->executeSelect(
			queryName: EssenceDatabaseKeys::PLAYER_LOAD_BY_USERNAME,
			args: ["username" => $username],
			onSelect: function (array $rows) use($resolve, $reject): void {
				if (count($rows) !== 1) {
					$reject(new EssenceDataException("Expected 1 row, got " . count($rows)));
					return;
				}
				$resolve(EssencePlayerData::unmarshal($rows[0], false));
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

	private function setupEnvironmentMode(): void {
		$mode = $this->getConfig()->getNested("environment.mode", "development");
		assert($mode === "production" || $mode === "development");
		self::$environmentMode = $mode;
	}

	public static function isProduction(): bool {
		return self::$environmentMode === "production";
	}

	public static function isDevelopment(): bool {
		return self::$environmentMode === "development";
	}
}