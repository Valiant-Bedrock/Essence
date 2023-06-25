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
 */
declare(strict_types=1);

namespace essence\session;

use essence\utils\Icons;
use essence\utils\ResolveEnumByNameTrait;

enum InputMode: int {
	use ResolveEnumByNameTrait;

	case MOUSE_KEYBOARD = 1;
	case TOUCHSCREEN = 2;
	case GAME_PAD = 3;
	case MOTION_CONTROLLER = 4;

	public function icon(): string {
		return match ($this) {
			self::MOUSE_KEYBOARD => Icons::MOUSE,
			self::TOUCHSCREEN => Icons::MOBILE,
			self::GAME_PAD, self::MOTION_CONTROLLER => Icons::CONTROLLER
		};
	}
}