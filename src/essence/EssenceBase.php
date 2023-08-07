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

use essence\ban\BanManager;
use essence\role\RoleManager;
use essence\translation\TranslationHandler;
use libcommand\LibCommandBase;
use libcommand\VanillaCommandPatcher;
use pocketmine\command\Command;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use RuntimeException;
use function array_filter;
use function array_rand;
use function basename;

require_once("EssenceAutoloader.php");

final class EssenceBase extends PluginBase {
	use SingletonTrait;

	/** @var array<class-string<Manageable>> */
	private const MANAGERS = [BanManager::class, FreezeManager::class, RoleManager::class];
	public const MOTD_PREFIX = TextFormat::RED . TextFormat::BOLD . "Valiant" . TextFormat::RESET . TextFormat::WHITE . " - ";
	public const MOTD_MESSAGES = ["&eNOW IN BETA!"];

	/** @var array<class-string<Manageable>, Manageable> */
	private array $managerInstances = [];

	private EssenceConfiguration $configuration;
	private DataConnector $connector;

	private EssenceLogForwarder $errorForwarder;

	protected function onEnable(): void {
		// setup instance and environment mode
		self::setInstance($this);
		$this->saveResource("config.yml");
		$this->configuration = EssenceConfiguration::loadFromYaml($this->getConfig()->getPath());
		// setup database
		$this->connector = libasynql::create(
			plugin: $this,
			configData: $this->configuration->databaseData,
			sqlMap: ["mysql" => "mysql.sql"]
		);
		$this->setupManagers();
		$this->setupCommands();
		// finish setup
		$this->getServer()->getPluginManager()->registerEvents(listener: new EssenceListener($this), plugin: $this);
		$this->getServer()->getNetwork()->setName(self::MOTD_PREFIX . TextFormat::colorize(self::MOTD_MESSAGES[array_rand(self::MOTD_MESSAGES)]));
		$this->getServer()->getLogger()->addAttachment($this->errorForwarder = new EssenceLogForwarder($this));
	}

	protected function onDisable(): void {
		$this->getConnector()->close();
		$this->getServer()->getLogger()->removeAttachment($this->errorForwarder);
	}

	public function getConnector(): DataConnector {
		return $this->connector;
	}

	public function getConfiguration(): EssenceConfiguration {
		return $this->configuration;
	}

	public function getEnvironment(): EssenceEnvironment {
		return $this->configuration->environment;
	}

	public function getErrorForwarder(): EssenceLogForwarder {
		return $this->errorForwarder;
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
		$clearCommand = $this->mustGetCommand("clear");
		$clearCommand->setPermission((string) EssencePermissions::COMMAND_CLEAR());
		$killCommand = $this->mustGetCommand("kill");
		$killCommand->setPermission((string) EssencePermissions::COMMAND_KILL());
	}

	public function mustGetCommand(string $name): Command {
		return $this->getServer()->getCommandMap()->getCommand($name) ?? throw new RuntimeException("Command $name not found");
	}

	/**
	 * @return Player[]
	 */
	public function getPlayersByPermission(string $permission): array {
		return array_filter(
			array: $this->getServer()->getOnlinePlayers(),
			callback: fn (Player $player) => $player->hasPermission($permission)
		);
	}

	public function broadcastMessageByPermission(Translatable|string $message, string $permission): void {
		$this->getServer()->broadcastMessage(
			message: $message instanceof Translatable ? TranslationHandler::getInstance()->translate($message) : $message,
			recipients: $this->getPlayersByPermission($permission)
		);
	}
}