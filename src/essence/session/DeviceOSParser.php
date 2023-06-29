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

namespace essence\session;

use libMarshal\parser\Parseable;

/**
 * @phpstan-implements Parseable<int, DeviceOS>
 */
final class DeviceOSParser implements Parseable {

	/**
	 * @param int $value
	 */
	public function parse(mixed $value): DeviceOS {
		return DeviceOS::from($value);
	}

	/**
	 * @param DeviceOS $value
	 */
	public function serialize(mixed $value): int {
		return $value->value;
	}
}