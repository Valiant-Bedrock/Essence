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

use libMarshal\attributes\Field;
use libMarshal\MarshalTrait;

final class EssenceConfiguration {
	use MarshalTrait;

	/**
	 * @param array<string, mixed> $databaseData
	 */
	public function __construct(
		#[Field] public EssenceEnvironment $environment,
		#[Field(name: "database")] public array $databaseData,
	) {
	}

}