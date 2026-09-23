<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe;

use pocketmine\inventory\transaction\action\CreateItemAction;
use pocketmine\inventory\transaction\action\DestroyItemAction;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\inventory\transaction\action\InventoryAction;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;

/**
 * Converts the inventory actions of clients before 1.16.100, which have no ItemStackRequest and report every inventory
 * change as a legacy transaction, into core inventory actions.
 */
final class LegacyInventoryActionConverter{

	public function __construct(
		private InventoryManager $inventoryManager,
		private TypeConverter $typeConverter
	){}

	/**
	 * Returns the core action for the given network action, or null if it carries no state change to act on (an
	 * unknown window, or a marker slot such as the crafting result).
	 *
	 * @throws TypeConversionException
	 */
	public function convert(NetworkInventoryAction $action) : ?InventoryAction{
		$sourceItem = $this->typeConverter->netItemStackToCore($action->oldItem->getItemStack());
		$targetItem = $this->typeConverter->netItemStackToCore($action->newItem->getItemStack());

		return match($action->sourceType){
			NetworkInventoryAction::SOURCE_CONTAINER => $this->convertContainerAction($action, $sourceItem, $targetItem),
			NetworkInventoryAction::SOURCE_WORLD => $action->inventorySlot === NetworkInventoryAction::ACTION_MAGIC_SLOT_DROP_ITEM ?
				new DropItemAction($targetItem) :
				null,
			NetworkInventoryAction::SOURCE_CREATIVE => match($action->inventorySlot){
				NetworkInventoryAction::ACTION_MAGIC_SLOT_CREATIVE_DELETE_ITEM => new DestroyItemAction($targetItem),
				NetworkInventoryAction::ACTION_MAGIC_SLOT_CREATIVE_CREATE_ITEM => new CreateItemAction($sourceItem),
				default => null
			},
			default => null
		};
	}

	private function convertContainerAction(NetworkInventoryAction $action, Item $sourceItem, Item $targetItem) : ?SlotChangeAction{
		if($action->windowId === null){
			return null;
		}
		$info = $this->inventoryManager->locateWindowAndSlot($action->windowId, $action->inventorySlot);
		if($info === null){
			return null;
		}

		[$inventory, $slot] = $info;
		return new SlotChangeAction($inventory, $slot, $sourceItem, $targetItem);
	}
}
