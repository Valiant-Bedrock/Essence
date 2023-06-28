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

use essence\managers\FreezeManager;
use essence\managers\Manageable;
use essence\managers\RoleManager;
use libcommand\LibCommandBase;
use libcommand\VanillaCommandPatcher;
use pocketmine\command\Command;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use RuntimeException;
use function array_rand;
use function assert;
use function basename;

final class EssenceBase extends PluginBase {
	use SingletonTrait;

	/** @var array<class-string<Manageable>> */
	private const MANAGERS = [FreezeManager::class, RoleManager::class];
	public const MOTD_PREFIX = TextFormat::RED . TextFormat::BOLD . "Valiant" . TextFormat::RESET . TextFormat::WHITE . " - ";
	public const MOTD_MESSAGES = ["&eNOW IN BETA!"];

	/** @var array<class-string<Manageable>, Manageable> */
	private array $managerInstances = [];

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
		$this->setupManagers();
		$this->setupCommands();
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

	private function setupManagers(): void {
		foreach (self::MANAGERS as $managerClass) {
			/** @var Manageable $manager */
			$manager = new $managerClass($this);
			$this->getLogger()->info("Enabling manager: " . basename($managerClass));
			$manager->onEnable($this);
			$this->managerInstances[$managerClass] = $manager;
		}
	}

	/**
	 * @template TManager of Manageable
	 * @param class-string<TManager> $name
	 * @return TManager
	 */
	public function fetchManager(string $name): Manageable {
		/** @var TManager $manager */
		$manager = $this->managerInstances[$name] ?? throw new RuntimeException("Manager $name not found");
		return $manager;
	}

	private function setupCommands(): void {
		LibCommandBase::register($this);
		VanillaCommandPatcher::register($this);
		$killCommand = $this->mustGetCommand("kill");
		$killCommand->setPermission((string) EssencePermissions::COMMAND_KILL());
	}

	private function mustGetCommand(string $name): Command {
		return $this->getServer()->getCommandMap()->getCommand($name) ?? throw new RuntimeException("Command $name not found");
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