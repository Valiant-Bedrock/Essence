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

final class EssenceDatabaseKeys {
	public const PLAYER_LOAD = "player.load";
	public const PLAYER_LOAD_BY_USERNAME = "player.load_by_username";
	public const PLAYER_SAVE = "player.save";
	public const BANS_SAVE = "bans.save";
	public const BANS_LOAD = "bans.load";
	public const BANS_UPDATE = "bans.update";

	private function __construct() {
	}
}