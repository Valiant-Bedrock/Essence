<?php
/**
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

use essence\utils\ResolveEnumByNameTrait;

enum DeviceOS: int {
	use ResolveEnumByNameTrait;

	case UNKNOWN = -1;
	case ANDROID = 1;
	case IOS = 2;
	case OSX = 3;
	case AMAZON = 4;
	case GEAR_VR = 5;
	case HOLOLENS = 6;
	case WINDOWS_10 = 7;
	case WIN32 = 8;
	case DEDICATED = 9;
	case TVOS = 10;
	case PLAYSTATION = 11;
	case NINTENDO = 12;
	case XBOX = 13;
	case WINDOWS_PHONE = 14;
}
