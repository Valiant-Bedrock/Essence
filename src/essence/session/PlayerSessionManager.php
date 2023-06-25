<?php
/**
 *  _    _ _    _  _____
 * | |  | | |  | |/ ____|
 * | |  | | |__| | |
 * | |  | |  __  | |
 * | |__| | |  | | |____
 * \____/|_|  |_|\_____|
 *
 * Copyright (C) 2020 - 2023 | Valiant Network / Matthew Jordan
 *
 * This program is private software. You may not redistribute this software, or
 * any derivative works of this software, in source or binary form, without
 * the express permission of the owner.
 *
 * @author sylvrs
 * @noinspection PhpIllegalArrayKeyTypeInspection - PHPStorm does not recognize array operator overloading from WeakMap
 */
declare(strict_types=1);

namespace essence\session;

use essence\player\EssenceDataException;
use Generator;
use InvalidArgumentException;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use WeakMap;

final class PlayerSessionManager {
	use SingletonTrait;

	/**
	 * @param WeakMap<Player, PlayerSession> $sessions
	 * @phpstan-ignore-next-line - Promoted creation of a WeakMap causes issues
	 */
	public function __construct(private WeakMap $sessions = new WeakMap()) {
	}

	/**
	 * @throws EssenceDataException
	 */
	public function create(Player $player): Generator {
		/** @var PlayerSession $session */
		$session = yield from PlayerSession::create($player);
		$this->sessions[$player] = $session;
		return $session;
	}

	/**
	 * @return WeakMap<Player, PlayerSession>
	 */
	public function getAll(): WeakMap {
		return $this->sessions;
	}

	/**
	 * Gets a player's session and throws an error if one doesn't exist
	 */
	public function get(Player $player): PlayerSession {
		return $this->sessions[$player] ?? throw new InvalidArgumentException("Player {$player->getDisplayName()} does not have an existing session");
	}

	/**
	 * Gets a player's session and returns null if one doesn't exist
	 */
	public function getOrNull(Player $player): ?PlayerSession {
		return $this->sessions[$player] ?? null;
	}

	/**
	 * Returns true if a player has a session
	 */
	public function exists(Player $player): bool {
		return isset($this->sessions[$player]);
	}
}