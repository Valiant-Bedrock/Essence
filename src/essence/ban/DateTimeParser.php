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

use DateTime;
use libMarshal\exception\UnmarshalException;
use libMarshal\parser\Parseable;

/**
 * @phpstan-implements Parseable<string, DateTime>
 */
final class DateTimeParser implements Parseable {

	/**
	 * @param string $value
	 */
	public function parse(mixed $value): DateTime {
		return DateTime::createFromFormat("Y-m-d H:i:s", $value) ?: throw new UnmarshalException("Failed to parse DateTime");
	}

	/**
	 * @param DateTime $value
	 */
	public function serialize(mixed $value): string {
		return $value->format("Y-m-d H:i:s");
	}
}