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

namespace essence\managers;

use essence\EssenceBase;
use essence\EssencePermissions;
use essence\translation\EssenceTranslationFactory;
use essence\translation\TranslationHandler;
use libcommand\ClosureCommand;
use libcommand\Overload;
use libcommand\parameter\types\TargetParameter;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;

final class FreezeManager extends Manageable implements Listener {
	protected const MOVE_THRESHOLD = 0.25;

	/** @var array<string, true> */
	private array $frozen = [];

	public function onEnable(EssenceBase $plugin): void {
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		$plugin->getServer()->getCommandMap()->register("essence", new ClosureCommand(
			name: "freeze",
			onExecute: $this->handleCommand(...),
			description: "Restrict/Unrestrict a player's movement",
			usageMessage: "/freeze <player>",
			overloads: [new Overload(name: "default", parameters: [new TargetParameter(name: "player")])],
			permission: (string) EssencePermissions::COMMAND_FREEZE(),
		));
	}
	public function onDisable(EssenceBase $plugin): void {
	}

	/**
	 * @param array<string, mixed> $arguments
	 */
	public function handleCommand(CommandSender $sender, string $overload, array $arguments): bool|string {
		/** @var Player $player */
		$player = $arguments["player"] ?? throw new InvalidCommandSyntaxException("Unable to find player");
		$translationHandler = TranslationHandler::getInstance();
		// unfreeze the player if they are frozen
		if ($this->isFrozen($player)) {
			$this->unfreeze($player);
			$translationHandler->sendMessage($player, EssenceTranslationFactory::command_freeze_unfrozen_player());
			return $translationHandler->translate(EssenceTranslationFactory::command_freeze_unfrozen_sender(player: $player->getName()));
		}
		// freeze the player if they are not frozen
		$this->freeze($player);
		$translationHandler->sendMessage($player, EssenceTranslationFactory::command_freeze_frozen_player());
		return $translationHandler->translate(EssenceTranslationFactory::command_freeze_frozen_sender(player: $player->getName()));
	}

	public function handleJoin(PlayerJoinEvent $event): void {
		if ($this->isFrozen($event->getPlayer())) {
			$this->freeze($event->getPlayer());
		}
	}

	public function handleMove(PlayerMoveEvent $event): void {
		if ($this->isFrozen($event->getPlayer()) && $event->getTo()->distance($event->getFrom()) > self::MOVE_THRESHOLD) {
			$event->cancel();
		}
	}

	public function isFrozen(Player $player): bool {
		return isset($this->frozen[$player->getUniqueId()->toString()]);
	}

	public function freeze(Player $player): void {
		$this->frozen[$player->getUniqueId()->toString()] = true;
		$player->setImmobile();
	}

	public function unfreeze(Player $player): void {
		unset($this->frozen[$player->getUniqueId()->toString()]);
		$player->setImmobile(false);
	}
}