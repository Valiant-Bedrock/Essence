<?php
/**
 * Copyright (C) 2020 - 2023 | Valiant Network / Matthew Jordan
 *
 * This program is private software. You may not redistribute this software, or
 * any derivative works of this software, in source or binary form, without
 * the express permission of the owner.
 *
 * @author sylvrs
 */
declare(strict_types=1);

namespace essence\translation;

use pocketmine\lang\Translatable;

/**
 * This class was automatically generated by /scripts/generate_translations.php
 * Do not edit this file manually.
 */
final class EssenceTranslationFactory {
	public static function command_ban_already_banned(string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_BAN_ALREADY_BANNED, params: ["player" => $player]);
	}

	public static function command_ban_success(string $player, string $reason, string $expires): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_BAN_SUCCESS, params: ["player" => $player, "reason" => $reason, "expires" => $expires]);
	}

	public static function command_ban_broadcast(string $bannedBy, string $player, string $reason, string $expires): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_BAN_BROADCAST, params: ["bannedBy" => $bannedBy, "player" => $player, "reason" => $reason, "expires" => $expires]);
	}

	public static function command_freeze_already_frozen(string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_FREEZE_ALREADY_FROZEN, params: ["player" => $player]);
	}

	public static function command_freeze_frozen_player(): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_FREEZE_FROZEN_PLAYER, params: []);
	}

	public static function command_freeze_frozen_sender(string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_FREEZE_FROZEN_SENDER, params: ["player" => $player]);
	}

	public static function command_freeze_unfrozen_player(): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_FREEZE_UNFROZEN_PLAYER, params: []);
	}

	public static function command_freeze_unfrozen_sender(string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_FREEZE_UNFROZEN_SENDER, params: ["player" => $player]);
	}

	public static function command_setrole_failure(string $player, string $reason): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_SETROLE_FAILURE, params: ["player" => $player, "reason" => $reason]);
	}

	public static function command_setrole_player_must_join(string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_SETROLE_PLAYER_MUST_JOIN, params: ["player" => $player]);
	}

	public static function command_setrole_invalid_role(string $role): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_SETROLE_INVALID_ROLE, params: ["role" => $role]);
	}

	public static function command_setrole_player_not_found(): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_SETROLE_PLAYER_NOT_FOUND, params: []);
	}

	public static function command_setrole_success(string $player, string $role): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_SETROLE_SUCCESS, params: ["player" => $player, "role" => $role]);
	}

	public static function command_setrole_updated_role(string $role): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_SETROLE_UPDATED_ROLE, params: ["role" => $role]);
	}

	public static function command_unban_not_banned(string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_UNBAN_NOT_BANNED, params: ["player" => $player]);
	}

	public static function command_unban_success(string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_UNBAN_SUCCESS, params: ["player" => $player]);
	}

	public static function command_unban_broadcast(string $unbannedBy, string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_UNBAN_BROADCAST, params: ["unbannedBy" => $unbannedBy, "player" => $player]);
	}

	public static function command_unban_failure(string $player): Translatable {
		return new Translatable(text: EssenceTranslationKeys::COMMAND_UNBAN_FAILURE, params: ["player" => $player]);
	}

	public static function kick_data_failure(): Translatable {
		return new Translatable(text: EssenceTranslationKeys::KICK_DATA_FAILURE, params: []);
	}

	public static function kick_ban(string $reason, string $expires): Translatable {
		return new Translatable(text: EssenceTranslationKeys::KICK_BAN, params: ["reason" => $reason, "expires" => $expires]);
	}

	public static function kick_xbox_required(): Translatable {
		return new Translatable(text: EssenceTranslationKeys::KICK_XBOX_REQUIRED, params: []);
	}

	public static function chat_format(string $role, string $player, string $message): Translatable {
		return new Translatable(text: EssenceTranslationKeys::CHAT_FORMAT, params: ["role" => $role, "player" => $player, "message" => $message]);
	}

	public static function server_join_xbox_required(): Translatable {
		return new Translatable(text: EssenceTranslationKeys::SERVER_JOIN_XBOX_REQUIRED, params: []);
	}

	private function __construct() {
	}
}
