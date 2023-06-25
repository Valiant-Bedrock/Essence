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

namespace essence\translation;

use essence\EssenceBase;
use pocketmine\command\CommandSender;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function array_combine;
use function array_keys;
use function array_map;
use function is_string;
use function parse_ini_string;
use function str_replace;
use function stream_get_contents;

final class TranslationHandler {
	use SingletonTrait;

	private const FILE_NAME = "messages.ini";

	/**
	 * @param array<string, string> $messages
	 */
	protected function __construct(protected array $messages) {}

	protected static function make(): self {
		$resource = EssenceBase::getInstance()->getResource(self::FILE_NAME) ?? throw new RuntimeException("Could not find " . self::FILE_NAME);
		$raw = stream_get_contents($resource) ?: throw new RuntimeException("Could not read " . self::FILE_NAME);
		/** @var string[] $data */
		$data = parse_ini_string($raw) ?: throw new RuntimeException("Could not parse " . self::FILE_NAME);
		return new self(array_combine(
			keys: array_keys($data),
			values: array_map(
				callback: fn (string $message) => TextFormat::colorize($message),
				array: $data
			)
		));
	}

	/**
	 * Attempts to find a message with the given key.
	 */
	public function getMessage(string $key): string {
		return $this->messages[$key] ?? $key;
	}

	public function translate(Translatable $translatable): string {
		$message = $this->getMessage($translatable->getText());
		foreach ($translatable->getParameters() as $key => $value) {
			$message = str_replace(
				search: "{%$key}",
				replace: !is_string($value) ? $this->translate($value) : $value,
				subject: $message
			);
		}
		return $message;
	}

	/**
	 * This method is used to send a translated message to a player.
	 */
	public function sendMessage(CommandSender $player, Translatable $translatable, string $prefix = ""): void {
		$player->sendMessage($prefix . $this->translate($translatable));
	}

	/**
	 * This method is used to send a translated popup to a player.
	 */
	public function sendPopup(Player $player, Translatable $translatable, string $prefix = ""): void {
		$player->sendPopup($prefix . $this->translate($translatable));
	}

	/**
	 * This method is used to send a translated tip to a player.
	 */
	public function sendTip(Player $player, Translatable $translatable, string $prefix = ""): void {
		$player->sendTip($prefix . $this->translate($translatable));
	}

	/**
	 * This method is used to send a translated action bar message to a player.
	 */
	public function sendActionBarMessage(Player $player, Translatable $translatable, string $prefix = ""): void {
		$player->sendActionBarMessage($prefix . $this->translate($translatable));
	}
}