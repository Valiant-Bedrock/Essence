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

namespace essence;

use essence\session\PlayerSessionManager;
use essence\translation\EssenceTranslationFactory;
use essence\translation\TranslationHandler;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use function strlen;

final readonly class EssenceListener implements Listener {

	public function __construct(public EssenceBase $plugin) {
	}

	public function handlePlayerChat(PlayerChatEvent $event): void {
		$session = PlayerSessionManager::getInstance()->getOrNull($event->getPlayer());
		if ($session === null) {
			$event->cancel();
			$this->plugin->getLogger()->debug("Cancelled chat event for player {$event->getPlayer()->getName()} due to null session");
			return;
		}
		$event->setFormat(TranslationHandler::getInstance()->translate(EssenceTranslationFactory::chat_format(
			role: strlen($session->data->role->prefix) > 0 ? "{$session->data->role->prefix} " : "",
			player: $event->getPlayer()->getName(),
			message: $event->getMessage(),
		)));
	}
}