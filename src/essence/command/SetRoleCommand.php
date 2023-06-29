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

namespace essence\command;

use essence\EssenceBase;
use essence\EssencePermissions;
use essence\managers\RoleManager;
use essence\player\EssenceDataException;
use essence\player\EssencePlayerData;
use essence\role\EssenceRole;
use essence\session\PlayerSessionManager;
use essence\translation\EssenceTranslationFactory;
use essence\translation\TranslationHandler;
use Generator;
use libcommand\ConsoleCommand;
use libcommand\Overload;
use libcommand\parameter\types\enums\EnumParameter;
use libcommand\parameter\types\StringParameter;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\player\Player;
use RuntimeException;
use SOFe\AwaitGenerator\Await;
use function array_map;

final class SetRoleCommand extends ConsoleCommand {

	public function __construct(public readonly EssenceBase $plugin) {
		parent::__construct(
			name: "setrole",
			description: "Sets the role of a player",
			usageMessage: "/setrole <player> <role>",
			overloads: [
				new Overload(name: "default", parameters: [
					new StringParameter(name: "player", optional: false),
					new EnumParameter(
						name: "role",
						enumName: "role",
						enumValues: array_map(fn (EssenceRole $role) => $role->name, EssenceRole::getAll()),
						optional: false
					)
				])
			],
			permission: (string) EssencePermissions::COMMAND_SETROLE()
		);
	}

	public function onConsoleExecute(ConsoleCommandSender $sender, string $overload, array $arguments): string|bool {
		$handler = TranslationHandler::getInstance();
		/**
		 * @var string $playerName
		 * @var string $rawRole
		 */
		["player" => $playerName, "role" => $rawRole] = $arguments;
		$role = EssenceRole::tryFrom($rawRole) ?? throw new InvalidCommandSyntaxException(
			$handler->translate(EssenceTranslationFactory::command_setrole_invalid_role(role: $rawRole))
		);
		$player = $sender->getServer()->getPlayerExact($playerName);
		if ($player instanceof Player) {
			Await::g2c($this->setRoleByPlayer($sender, $player, $role));
			return true;
		}
		Await::g2c($this->setRoleByUserName($sender, $playerName, $role));
		return true;
	}

	private function setRoleByPlayer(ConsoleCommandSender $sender, Player $player, EssenceRole $role): Generator {
		$handler = TranslationHandler::getInstance();
		$session = PlayerSessionManager::getInstance()->getOrNull($player);
		if ($session === null) {
			$handler->sendMessage($sender, EssenceTranslationFactory::command_setrole_failure(
				player: $player->getName(),
				reason: "No session found for player"
			));
			return;
		}

		try {
			yield from $session->setRole($role);
			$handler->sendMessage($sender, EssenceTranslationFactory::command_setrole_success(
				player: $player->getName(),
				role: $role->formattedName
			));
			$handler->sendMessage($player, EssenceTranslationFactory::command_setrole_updated_role(role: $role->name));
		} catch (EssenceDataException $exception) {
			$handler->sendMessage($sender, EssenceTranslationFactory::command_setrole_failure(
				player: $player->getName(),
				reason: $exception->getMessage()
			));
		}

	}

	public function setRoleByUserName(ConsoleCommandSender $sender, string $username, EssenceRole $role): Generator {
		try {
			// fetch player data to ensure they exist
			/** @var EssencePlayerData $data */
			$data = yield from $this->plugin->fetchManager(RoleManager::class)->resolvePlayerDataFromUsername(username: $username);
			$data->role = $role;
			// save to database
			yield from $data->saveToDatabase();
			TranslationHandler::getInstance()->sendMessage($sender, EssenceTranslationFactory::command_setrole_success(
				player: $username,
				role: $data->role->formattedName
			));
		} catch (EssenceDataException $exception) {
			TranslationHandler::getInstance()->sendMessage($sender, EssenceTranslationFactory::command_setrole_failure(
				player: $username,
				reason: $exception->getMessage()
			));
		} catch (RuntimeException) {
			TranslationHandler::getInstance()->sendMessage($sender, EssenceTranslationFactory::command_setrole_player_must_join(player: $username));
		}
	}
}