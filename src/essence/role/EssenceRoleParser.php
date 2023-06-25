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

namespace essence\role;

use libMarshal\parser\Parseable;

/**
 * @phpstan-implements Parseable<string, EssenceRole>
 */
final class EssenceRoleParser implements Parseable {

	/**
	 * @param string $value
	 */
	public function parse(mixed $value): EssenceRole {
		return EssenceRole::tryFrom($value) ?? EssenceRole::USER();
	}

	/**
	 * @param EssenceRole $value
	 */
	public function serialize(mixed $value): string {
		return $value->name;
	}
}