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

namespace essence\ban;

use libMarshal\parser\Parseable;
use function explode;
use function implode;

/**
 * @phpstan-implements Parseable<string, string[]>
 */
final class StringArrayParser implements Parseable {
	protected const SEPARATOR = ";";

	/**
	 * @param string $value
	 * @return string[]
	 */
	public function parse(mixed $value): array {
		return explode(separator: self::SEPARATOR, string: $value);
	}

	/**
	 * @param string[] $value
	 */
	public function serialize(mixed $value): string {
		return implode(separator: self::SEPARATOR, array: $value);
	}
}