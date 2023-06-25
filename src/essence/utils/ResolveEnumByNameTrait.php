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

namespace essence\utils;

use InvalidArgumentException;
use ReflectionEnum;
use ReflectionException;
use function enum_exists;

trait ResolveEnumByNameTrait {
	public static function fromName(string $name): self {
		if (!enum_exists(self::class)) {
			throw new InvalidArgumentException(self::class . " is not a valid enum");
		}
		try {
			/** @var self $value */
			$value = (new ReflectionEnum(self::class))->getCase($name)->getValue();
			return $value;
		} catch (ReflectionException) {
			throw new InvalidArgumentException("'$name' is not a valid case name for enum " . self::class);
		}
	}
}