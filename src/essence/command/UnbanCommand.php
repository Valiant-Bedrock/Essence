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

use essence\ban\BanManager;
use essence\EssenceBase;
use essence\EssencePermissions;
use essence\translation\EssenceTranslationFactory;
use essence\translation\TranslationHandler;
use Generator;
use libcommand\Command;
use libcommand\Overload;
use libcommand\parameter\types\StringParameter;
use pocketmine\command\CommandSender;
use SOFe\AwaitGenerator\Await;

final class UnbanCommand extends Command {

	public function __construct(private readonly EssenceBase $plugin) {
		$plugin->getServer()->getCommandMap()->unregister($plugin->mustGetCommand("unban"));
		parent::__construct(
			name: "unban",
			description: "Unbans a player from the server",
			usageMessage: "/unban <username>",
			overloads: [
				new Overload(name: "default", parameters: [new StringParameter(name: "username")])
			],
			permission: (string) EssencePermissions::COMMAND_BAN()
		);
	}

	public function onExecute(CommandSender $sender, string $overload, array $arguments): bool|string {
		/** @var string $username */
		$username = $arguments["username"];
		Await::g2c($this->handleUnban($sender, $username));
		return true;
	}

	public function handleUnban(CommandSender $unbannedBy, string $username): Generator {
		$handler = TranslationHandler::getInstance();
		$manager = $this->plugin->fetchManager(BanManager::class);
		/** @var bool $isBanned */
		$isBanned = yield from $manager->isBanned($username);
		if (!$isBanned) {
			$unbannedBy->sendMessage($handler->translate(EssenceTranslationFactory::command_unban_not_banned(
				player: $username
			)));
			return;
		}
		/** @var bool $status */
		$status = yield from $manager->unban($unbannedBy, $username);
		if (!$status) {
			$unbannedBy->sendMessage($handler->translate(EssenceTranslationFactory::command_unban_failure(player: $username)));
			return;
		}
		$unbannedBy->sendMessage($handler->translate(EssenceTranslationFactory::command_unban_success(
			player: $username,
		)));
		$this->plugin->broadcastMessageByPermission(
			message: $handler->translate(EssenceTranslationFactory::command_unban_broadcast(
				unbannedBy: $unbannedBy->getName(),
				player: $username
			)),
			permission: (string) EssencePermissions::COMMAND_BAN()
		);
	}
}