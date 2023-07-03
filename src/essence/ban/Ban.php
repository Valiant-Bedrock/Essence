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

namespace essence\ban;

use Closure;
use DateTime;
use essence\EssenceBase;
use essence\EssenceDatabaseKeys;
use essence\player\EssenceDataException;
use essence\session\PlayerExtradata;
use essence\utils\DateTimeParser;
use Generator;
use libMarshal\attributes\Field;
use libMarshal\exception\UnmarshalException;
use libMarshal\MarshalTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function array_map;

final class Ban {
	use MarshalTrait;

	public function __construct(
		#[Field] public string $username,
		#[Field] public string $xuid,
		#[Field(name: "ip_address")] public string $address,
		#[Field(name: "device_id")] public string $deviceId,
		#[Field] public string $reason,
		#[Field(name: "banned_by")] public string $bannedBy,
		#[Field(name: "creation_time", parser: DateTimeParser::class)] public DateTime $creationTime,
		#[Field(name: "expiry_time", parser: DateTimeParser::class)] public ?DateTime $expiryTime = null,
		#[Field(name: "unbanned_by")] public string $unbannedBy = "",
		#[Field(name: "ban_id")] public ?int $id = null,
	) {
	}

	public function isCurrent(): bool {
		return $this->unbannedBy === "" && ($this->expiryTime === null || $this->expiryTime > new DateTime());
	}

	public function getExpiryAsString(): string {
		return $this->expiryTime === null ? "never" : $this->expiryTime->format("Y-m-d H:i:s e");
	}

	public function isMissingData(): bool {
		return $this->xuid === "" || $this->address === "" || $this->deviceId === "";
	}

	public function replaceMissingData(string $xuid, string $address, string $deviceId): void {
		if ($this->xuid === "") {
			$this->xuid = $xuid;
		}
		if ($this->address === "") {
			$this->address = $address;
		}
		if ($this->deviceId === "") {
			$this->deviceId = $deviceId;
		}
	}

	public function isAttachedTo(string $username, string $xuid, string $address, string $deviceId): bool {
		return $this->username === $username || $this->xuid === $xuid || $this->address === $address || $this->deviceId === $deviceId;
	}

	public function isAttachedToPlayer(Player $player): bool {
		try {
			$extraData = PlayerExtradata::unmarshal($player->getPlayerInfo()->getExtraData());
			return $this->xuid === $player->getXuid() || $this->address === $player->getNetworkSession()->getIp() || $this->deviceId === $extraData->deviceId;
		} catch (UnmarshalException) {
			return false;
		}
	}

	public function equals(Ban $other): bool {
		return $this->username === $other->username && $this->xuid === $other->xuid && $this->address === $other->address && $this->deviceId === $other->deviceId && $this->reason === $other->reason && $this->bannedBy === $other->bannedBy;
	}

	public function __toString(): string {
		return "$this->username (XUID: $this->xuid) banned by $this->bannedBy for $this->reason until {$this->getExpiryAsString()}";
	}

	public static function fromDatabase(string $username, string $xuid, string $ipAddress, string $deviceId): Generator {
		return yield from Await::promise(fn (Closure $resolve, Closure $reject) => EssenceBase::getInstance()->getConnector()->executeSelect(
			queryName: EssenceDatabaseKeys::BANS_LOAD,
			args: [
				"username" => $username,
				"xuid" => $xuid,
				"ip_address" => $ipAddress,
				"device_id" => $deviceId
			],
			onSelect: fn (array $rows) => $resolve(array_map(fn (array $row) => self::unmarshal($row, false), $rows)),
			onError: fn (Throwable $throwable) => $reject(new EssenceDataException($throwable->getMessage()))
		));
	}

	public static function fromUsername(string $username): Generator {
		return yield from Await::promise(fn (Closure $resolve, Closure $reject) => EssenceBase::getInstance()->getConnector()->executeSelect(
			queryName: EssenceDatabaseKeys::BANS_LOAD_BY_USERNAME,
			args: ["username" => $username],
			onSelect: fn (array $rows) => $resolve(array_map(fn (array $row) => self::unmarshal($row, false), $rows)),
			onError: fn (Throwable $throwable) => $reject(new EssenceDataException($throwable->getMessage()))
		));
	}

	public function updateInDatabase(): Generator {
		return yield from Await::promise(fn (Closure $resolve, Closure $reject) => EssenceBase::getInstance()->getConnector()->executeGeneric(
			queryName: EssenceDatabaseKeys::BANS_UPDATE,
			args: $this->marshal(),
			onSuccess: fn () => $resolve(true),
			onError: fn (Throwable $error) => $reject(new EssenceDataException($error->getMessage()))
		));
	}

	/**
	 * @throws UnmarshalException
	 */
	public function saveToDatabase(): Generator {
		return yield from Await::promise(fn (Closure $resolve, Closure $reject) => EssenceBase::getInstance()->getConnector()->executeGeneric(
			queryName: EssenceDatabaseKeys::BANS_SAVE,
			args: $this->marshal(),
			onSuccess: fn () => $resolve(true),
			onError: fn (Throwable $error) => $reject(new EssenceDataException($error->getMessage()))
		));
	}

}