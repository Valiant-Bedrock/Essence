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

use function define;
use function defined;
use function dirname;

if (defined("essence\LOADED")) {
	return;
}

$autoloader = dirname(__FILE__, 3) . "/vendor/autoload.php";
require_once($autoloader);

define("essence\LOADED", true);