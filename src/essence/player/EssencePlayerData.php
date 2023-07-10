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

namespace essence\player;

use Closure;
use essence\EssenceBase;
use essence\EssenceDatabaseKeys;
use essence\role\EssenceRole;
use essence\role\EssenceRoleParser;
use essence\session\PlayerExtradata;
use Generator;
use libMarshal\attributes\Field;
use libMarshal\exception\UnmarshalException;
use libMarshal\MarshalTrait;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;
use Throwable;
use function count;

final class EssencePlayerData {
	use MarshalTrait;

	public function __construct(
		#[Field] public string $uuid,
		#[Field(name: "role_name", parser: EssenceRoleParser::class)] public EssenceRole $role,
		#[Field] public string $username,
		#[Field(name: "xuid")] public string $xuid,
		#[Field(name: "device_id")] public string $deviceId,
		#[Field(name: "last_address")] public string $lastIp,
	) {
	}

	public static function fromDatabase(Player $player, PlayerExtradata $extraData): Generator {
		return yield from Await::promise(fn (Closure $resolve, Closure $reject) => EssenceBase::getInstance()->getConnector()->executeSelect(
			queryName: EssenceDatabaseKeys::PLAYER_LOAD,
			args: ["xuid" => $player->getXuid()],
			onSelect: fn (array $rows) => $resolve(count($rows) === 1 ? self::unmarshal($rows[0], false) : self::default($player, $extraData)),
			onError: fn (Throwable $throwable) => $reject(new EssenceDataException($throwable->getMessage()))
		));
	}

	public function updateIdentity(Player $player, PlayerExtradata $extraData): Generator {
		if ($this->deviceId === $extraData->deviceId && $this->xuid === $player->getXuid() && $this->lastIp === $player->getNetworkSession()->getIp()) {
			return true;
		}
		$this->deviceId = $extraData->deviceId;
		$this->xuid = $player->getXuid();
		$this->lastIp = $player->getNetworkSession()->getIp();
		return yield from $this->updateInDatabase();
	}

	/**
	 * @throws UnmarshalException
	 */
	public function updateInDatabase(): Generator {
		return yield from Await::promise(fn (Closure $resolve, Closure $reject) => EssenceBase::getInstance()->getConnector()->executeGeneric(
			queryName: EssenceDatabaseKeys::PLAYER_UPDATE,
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
			queryName: EssenceDatabaseKeys::PLAYER_SAVE,
			args: $this->marshal(),
			onSuccess: fn () => $resolve(true),
			onError: fn (Throwable $error) => $reject(new EssenceDataException($error->getMessage()))
		));
	}

	public static function default(Player $player, PlayerExtradata $extraData): self {
		return new EssencePlayerData(
			uuid: $player->getUniqueId()->toString(),
			role: EssenceRole::USER(),
			username: $player->getName(),
			xuid: $player->getXuid(),
			deviceId: $extraData->deviceId,
			lastIp: $player->getNetworkSession()->getIp(),
		);
	}
}