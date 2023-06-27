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

interface Manageable {
	public function onEnable(EssenceBase $plugin): void;

	public function onDisable(EssenceBase $plugin): void;
}