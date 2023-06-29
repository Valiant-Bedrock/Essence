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

namespace essence;

use pocketmine\permission\DefaultPermissionNames;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\RegistryTrait;
use function array_fill_keys;

/**
 * @method static self COMMAND_BAN()
 * @method static self COMMAND_FREEZE()
 * @method static self COMMAND_KILL()
 * @method static self COMMAND_SETROLE()
 */
final class EssencePermissions {
	use RegistryTrait;

	protected static function setup(): void {
		self::register("command_ban", "essence.command.ban", "Allows the player to use the `ban` command", true);
		self::register("command_freeze", "essence.command.freeze", "Allows the player to use the `freeze` command", true);
		self::register("command_kill", "essence.command.kill", "Allows the player to use the `kill` command", true);
		self::register("command_setrole", "essence.command.setrole", "Allows the player to use the `setrole` command", true);
	}

	protected static function register(string $enumName, string $permissionName, string $description, bool $addToOperatorGroup = false): void {
		$member = new self($enumName, $permissionName, $description);
		self::_registryRegister($enumName, $member);
		PermissionManager::getInstance()->addPermission(new Permission(
			name: $member->permissionName(),
			description: $member->description(),
			children: array_fill_keys($member->children(), true)
		));

		if ($addToOperatorGroup) {
			$group = PermissionManager::getInstance()->getPermission(DefaultPermissionNames::GROUP_OPERATOR) ?? throw new AssumptionFailedError("Operator group does not exist");
			$group->addChild($member->permissionName(), true);
		}
	}

	/**
	 * @param list<string> $children
	 */
	public function __construct(protected string $enumName, protected string $permissionName, protected ?string $description = null, protected array $children = []) {
	}

	public function name(): string {
		return $this->enumName;
	}

	public function permissionName(): string {
		return $this->permissionName;
	}

	public function description(): ?string {
		return $this->description;
	}

	/**
	 * @return array<string>
	 */
	public function children(): array {
		return $this->children;
	}

	public function __toString(): string {
		return $this->permissionName;
	}
}