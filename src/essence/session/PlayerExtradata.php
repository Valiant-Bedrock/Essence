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

use libMarshal\attributes\Field;
use libMarshal\MarshalTrait;

final class PlayerExtradata {
	use MarshalTrait;

	public function __construct(
		#[Field(name: "DeviceId")] public readonly string $deviceId,
		#[Field(name: "UIProfile")] public readonly int $uiProfile,
		#[Field(name: "GuiScale")] public readonly int $guiScale,
		#[Field(name: "DeviceOS")] public readonly DeviceOS $deviceOS,
		#[Field(name: "CurrentInputMode")] public readonly InputMode $currentInputMode,
	) {
	}

}