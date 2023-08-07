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

use libDiscord\DiscordChannel;
use libDiscord\embed\Field;
use libDiscord\embed\RichEmbed;
use LogLevel;
use pocketmine\utils\TextFormat;
use ThreadedLoggerAttachment;
use function count;
use function explode;
use function preg_match;
use function preg_replace;
use function strlen;
use function strtoupper;

final class EssenceLogForwarder extends ThreadedLoggerAttachment {
	protected const MAX_EMBED_FIELD_LENGTH = 1024;
	protected const STACK_TRACE_REGEX = "/(?<=--- Stack trace ---\n)(.*)(?=--- End of exception information ---)/s";
	protected const DISCORD_WEBHOOK_USERNAME = "Error Forwarder";

	/** @var array<LogLevel::*, true> */
	protected const FORWARDED_LOG_LEVELS = [
		LogLevel::ERROR => true,
		LogLevel::ALERT => true,
		LogLevel::CRITICAL => true,
	];

	private static DiscordChannel $logChannel;

	public function __construct(EssenceBase $plugin) {
		self::$logChannel = new DiscordChannel(
			webhookId: $plugin->getEnvironment()->forwardingId,
			username: self::DISCORD_WEBHOOK_USERNAME,
		);
	}

	/**
	 * @param LogLevel::* $level
	 * @param string $message
	 */
	public function log($level, $message): void {
		if (!isset(self::FORWARDED_LOG_LEVELS[$level])) {
			return;
		}
		$this->handle($level, $message);
	}

	/**
	 * @param LogLevel::* $level
	 */
	public function handle(string $level, string $message): void {
		// TODO: how can we pass our forwarding IDs between threads w/o hardcoding it as a constant?
		if (!isset(self::$logChannel)) {
			return;
		}
		self::$logChannel->sendEmbed(new RichEmbed(
			title: "Notification - " . strtoupper($level),
			description: "",
			color: self::resolveColorFromLogLevel($level),
			fields: [
				self::resolveMessage($message),
				...self::resolveStacktrace($message)
			]
		));
	}

	private static function resolveMessage(string $message): Field {
		$extracted = [];
		preg_match("/]:\s(.*)/", $message, $extracted);
		return new Field(name: "Message", value: TextFormat::clean($extracted[1] ?? $message), inline: false);
	}

	/**
	 * @return array<Field>
	 */
	private static function resolveStacktrace(string $message): array {
		$lines = [];
		// check if there's a stack trace
		preg_match(self::STACK_TRACE_REGEX, $message, $lines);
		if (empty($lines)) {
			return [];
		}
		$fields = [];
		$current = new Field(name: "Stack Trace", value: "", inline: false);
		foreach (explode(separator: "\n", string: $lines[1]) as $line) {
			// search for line numbers and format them as bold
			// search for trace messages and format them as code
			/** @var string $formatted */
			$formatted = preg_replace("/(#\d+)\s+(.*)/m", "**\$1** `\$2`", $line);
			if (strlen($current->value) + strlen($formatted) > self::MAX_EMBED_FIELD_LENGTH) {
				$fields[] = $current;
				$current = new Field(name: "Stack Trace - " . count($fields), value: "", inline: false);
			}
			$current->value .= $formatted . "\n";
		}
		return $fields;
	}

	/**
	 * @param LogLevel::* $level
	 */
	private static function resolveColorFromLogLevel(string $level): int {
		return match ($level) {
			LogLevel::DEBUG, LogLevel::NOTICE => 0x93DBEA,
			LogLevel::INFO => 0x6B72BD,
			LogLevel::WARNING => 0xFCC436,
			LogLevel::ALERT => 0xFCF036,
			LogLevel::ERROR => 0xF99338,
			LogLevel::EMERGENCY, LogLevel::CRITICAL => 0xFF4545,
		};
	}
}