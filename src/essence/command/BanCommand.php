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

use DateTime;
use essence\ban\Ban;
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
use pocketmine\command\utils\InvalidCommandSyntaxException;
use SOFe\AwaitGenerator\Await;
use function intval;
use function is_numeric;
use function str_split;

final class BanCommand extends Command {
	public function __construct(private readonly EssenceBase $plugin) {
		$plugin->getServer()->getCommandMap()->unregister($plugin->mustGetCommand("ban"));
		parent::__construct(
			name: "ban",
			description: "Ban a player from the server",
			usageMessage: "/ban <username> <reason> <duration>",
			overloads: [
				new Overload(name: "default", parameters: [
					new StringParameter(name: "username"),
					new StringParameter(name: "reason"),
					new StringParameter(name: "duration", optional: true),
				])
			],
			permission: (string) EssencePermissions::COMMAND_BAN()
		);
	}

	public function onExecute(CommandSender $sender, string $overload, array $arguments): bool|string {
		/**
		 * @var string $username
		 * @var string $reason
		 */
		["username" => $username, "reason" => $reason] = $arguments;
		/** @var ?string $duration */
		$duration = $arguments["duration"] ?? null;
		$time = match (true) {
			$duration !== null => $this->parseDuration($duration) ?? throw new InvalidCommandSyntaxException(),
			default => null
		};
		Await::g2c($this->handleBan($sender, $username, $time, $reason));
		return true;
	}

	public function handleBan(CommandSender $bannedBy, string $username, ?DateTime $expires, string $reason): Generator {
		$handler = TranslationHandler::getInstance();
		$manager = $this->plugin->fetchManager(BanManager::class);
		/** @var bool $isBanned */
		$isBanned = yield from $manager->isBanned($username);
		if ($isBanned) {
			$bannedBy->sendMessage($handler->translate(EssenceTranslationFactory::command_ban_already_banned(player: $username)));
			return;
		}
		/** @var Ban $ban */
		$ban = yield from $manager->ban($bannedBy, $username, $expires, $reason);
		$bannedBy->sendMessage($handler->translate(EssenceTranslationFactory::command_ban_success(
			player: $ban->username,
			reason: $ban->reason,
			expires: $ban->getExpiryAsString()
		)));
		$this->plugin->broadcastMessageByPermission(
			message: $handler->translate(EssenceTranslationFactory::command_ban_broadcast(
				bannedBy: $bannedBy->getName(),
				player: $ban->username,
				reason: $ban->reason,
				expires: $ban->getExpiryAsString()
			)),
			permission: (string) EssencePermissions::COMMAND_BAN()
		);
	}

	/**
	 * Parses a duration like 1d2h3m4s into a DateTime object in the future or null if invalid
	 */
	private function parseDuration(string $duration): ?DateTime {
		$time = new DateTime();
		$raw = "";
		foreach (str_split($duration) as $character) {
			if (is_numeric($character)) {
				$raw .= $character;
				continue;
			}
			$value = intval($raw);
			if ($value <= 0) {
				return null;
			}
			$modifier = $this->resolveCharToModifier($character, $value);
			if ($modifier === null) {
				return null;
			}
			$time->modify($modifier);
			$raw = "";
		}
		return $time;
	}

	private function resolveCharToModifier(string $char, int $value): ?string {
		return match ($char) {
			"y" => "+$value years",
			"M" => "+$value months",
			"w" => "+$value weeks",
			"d" => "+$value days",
			"h" => "+$value hours",
			"m" => "+$value minutes",
			"s" => "+$value seconds",
			default => null,
		};
	}

}