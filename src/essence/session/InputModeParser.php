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
 * @phpstan-implements Parseable<int, InputMode>
 */
final class InputModeParser implements Parseable {
	/**
	 * @param int $value
	 */
	public function parse(mixed $value): InputMode {
		return InputMode::from($value);
	}

	/**
	 * @param InputMode $value
	 */
	public function serialize(mixed $value): int {
		return $value->value;
	}
}