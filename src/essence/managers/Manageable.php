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
use PrefixedLogger;

abstract class Manageable {
	protected readonly PrefixedLogger $logger;

	public function __construct(protected readonly EssenceBase $plugin) {
		$this->logger = new PrefixedLogger($plugin->getLogger(), static::class);
	}

	public function getLogger(): PrefixedLogger {
		return $this->logger;
	}

	public function getPlugin(): EssenceBase {
		return $this->plugin;
	}

	public abstract function onEnable(EssenceBase $plugin): void;

	public abstract function onDisable(EssenceBase $plugin): void;
}