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

use essence\EssencePermissions;
use InvalidArgumentException;
use pocketmine\permission\DefaultPermissionNames;
use pocketmine\utils\RegistryTrait;
use pocketmine\utils\TextFormat;
use function strtolower;

/**
 * TODO: Originally, this was conceptualized and partially implemented as a database feature but I think it's better to just implement it directly for now.
 * We can always change it later but our use case doesn't call for being able to modify permissions on the fly.
 *
 * @method static self USER()
 * @method static self HOST()
 * @method static self DESIGNER()
 * @method static self MANAGER()
 * @method static self EXECUTIVE()
 */
final class EssenceRole {
	use RegistryTrait;

	protected static function setup(): void {
		self::register(name: "user", formattedName: "User", prefix: "", permissions: []);
		self::register(
			name: "host",
			formattedName: "Game Host",
			prefix: "&e&lGAME HOST",
			permissions: [
				"uhc.command.bypass",
				"uhc.command.create",
				(string) EssencePermissions::COMMAND_BAN(),
				(string) EssencePermissions::COMMAND_FREEZE(),
				(string) EssencePermissions::COMMAND_KILL(),
				(string) EssencePermissions::COMMAND_CLEAR(),
				DefaultPermissionNames::COMMAND_BAN_IP,
				DefaultPermissionNames::COMMAND_BAN_PLAYER,
				DefaultPermissionNames::COMMAND_EFFECT,
				DefaultPermissionNames::COMMAND_ENCHANT,
				DefaultPermissionNames::COMMAND_GIVE,
				DefaultPermissionNames::COMMAND_GAMEMODE,
				DefaultPermissionNames::COMMAND_GAMEMODE_SELF,
				DefaultPermissionNames::COMMAND_GAMEMODE_OTHER,
				DefaultPermissionNames::COMMAND_KICK,
				DefaultPermissionNames::COMMAND_WHITELIST_ENABLE,
				DefaultPermissionNames::COMMAND_WHITELIST_DISABLE,
				DefaultPermissionNames::COMMAND_WHITELIST_ADD,
				DefaultPermissionNames::COMMAND_WHITELIST_REMOVE,
				DefaultPermissionNames::COMMAND_TELEPORT,
			],
		);
		self::register(
			name: "designer",
			formattedName: "Designer",
			prefix: "&c&lDESIGNER",
			permissions: []
		);
		self::register(
			name: "manager",
			formattedName: "Manager",
			prefix: "&a&lMANAGER",
			permissions: [
				"uhc.command.bypass",
				"uhc.command.create",
				(string) EssencePermissions::COMMAND_BAN(),
				(string) EssencePermissions::COMMAND_FREEZE(),
				(string) EssencePermissions::COMMAND_KILL(),
				(string) EssencePermissions::COMMAND_CLEAR(),
				DefaultPermissionNames::COMMAND_BAN_IP,
				DefaultPermissionNames::COMMAND_BAN_PLAYER,
				DefaultPermissionNames::COMMAND_EFFECT,
				DefaultPermissionNames::COMMAND_ENCHANT,
				DefaultPermissionNames::COMMAND_GIVE,
				DefaultPermissionNames::COMMAND_GAMEMODE,
				DefaultPermissionNames::COMMAND_GAMEMODE_SELF,
				DefaultPermissionNames::COMMAND_GAMEMODE_OTHER,
				DefaultPermissionNames::COMMAND_KICK,
				DefaultPermissionNames::COMMAND_WHITELIST_ENABLE,
				DefaultPermissionNames::COMMAND_WHITELIST_DISABLE,
				DefaultPermissionNames::COMMAND_WHITELIST_ADD,
				DefaultPermissionNames::COMMAND_WHITELIST_REMOVE,
				DefaultPermissionNames::COMMAND_TELEPORT,
			],
		);
		self::register(
			name: "executive",
			formattedName: "Executive",
			prefix: "&c&lEXECUTIVE",
			permissions: [],
			operator: true
		);
	}

	/**
	 * @param list<string> $permissions
	 */
	protected static function register(string $name, string $formattedName, string $prefix, array $permissions, bool $operator = false): void {
		self::_registryRegister($name, new self($name, $formattedName, TextFormat::colorize($prefix), $permissions, $operator));
	}

	public static function tryFrom(string $name): ?self {
		try {
			return self::from(strtolower($name));
		} catch (InvalidArgumentException) {
			return null;
		}
	}

	public static function from(string $name): self {
		/** @var EssenceRole $role */
		$role = self::_registryFromString($name);
		return $role;
	}

	/**
	 * @return EssenceRole[]
	 */
	public static function getAll(): array {
		/** @var EssenceRole[] $all */
		$all = self::_registryGetAll();
		return $all;
	}

	/**
	 * @param list<string> $permissions
	 */
	public function __construct(
		public readonly string $name,
		public readonly string $formattedName,
		public readonly string $prefix,
		public readonly array $permissions,
		public readonly bool $operator,
	) {
	}
}