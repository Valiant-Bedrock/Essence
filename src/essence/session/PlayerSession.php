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

namespace essence\session;

use essence\EssenceBase;
use essence\player\EssenceDataException;
use essence\player\EssencePlayerData;
use essence\role\EssenceRole;
use Generator;
use libMarshal\exception\UnmarshalException;
use LogicException;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use function array_combine;
use function array_fill;
use function count;

final class PlayerSession {

	public function __construct(
		public readonly Player $player,
		public readonly EssencePlayerData $data,
		public readonly DeviceOS  $deviceOS,
		public InputMode $inputMode,
	) {
		$this->checkForUpdates();
	}

	private function checkForUpdates(): Generator {
		// update data if necessary
		if ($this->player->getName() !== $this->data->username) {
			$this->data->username = $this->player->getName();
			try {
				yield from $this->saveToDatabase();
				EssenceBase::getInstance()->getLogger()->debug("Updated username for {$this->player->getName()}");
			} catch (EssenceDataException|LogicException $exception) {
				EssenceBase::getInstance()->getLogger()->warning("Failed to update username for {$this->player->getName()}: {$exception->getMessage()}");
			}
		}
	}

	/**
	 * @throws EssenceDataException
	 */
	public static function create(Player $player): Generator {
		try {
			$info = $player->getNetworkSession()->getPlayerInfo() ?? throw new EssenceDataException("PlayerInfo not found for {$player->getName()}");
			$extraData = PlayerExtradata::unmarshal($info->getExtraData());
			/** @var EssencePlayerData $data */
			$data = yield from EssencePlayerData::fromDatabase($player, $extraData);
			// ensure fields are up-to-date
			$success = yield from $data->updateIdentity($player, $extraData);
			if (!$success) {
				throw new EssenceDataException("Failed to update identity for {$player->getName()}");
			}
			return new self(
				player: $player,
				data: $data,
				deviceOS: $extraData->deviceOS,
				inputMode: $extraData->currentInputMode,
			);
		} catch (UnmarshalException|LogicException $exception) {
			throw new EssenceDataException($exception->getMessage(), $exception->getCode(), $exception);
		}
	}

	/**
	 * @throws EssenceDataException
	 */
	public function setRole(EssenceRole $role): Generator {
		$previousRole = $this->data->role;
		$this->data->role = $role;
		try {
			yield from $this->saveToDatabase();
			$this->recalculatePermissions();
		} catch (EssenceDataException $exception) {
			// revert role if saving to database fails
			$this->data->role = $previousRole;
			throw $exception;
		}
	}

	public function recalculatePermissions(): void {
		if (!$this->player->isConnected()) {
			return;
		}
		if ($this->data->role->operator) {
			$this->player->setBasePermission(DefaultPermissions::ROOT_OPERATOR, true);
		} else {
			$this->player->unsetBasePermission(DefaultPermissions::ROOT_OPERATOR);
		}
		$attachment = $this->player->addAttachment(EssenceBase::getInstance());
		$permissions = $this->data->role->permissions;
		$attachment->setPermissions(array_combine(
			keys: $permissions,
			values: array_fill(0, count($permissions), true)
		));
		$this->player->recalculatePermissions();
	}

	public function saveToDatabase(): Generator {
		return yield from $this->data->saveToDatabase();
	}
}