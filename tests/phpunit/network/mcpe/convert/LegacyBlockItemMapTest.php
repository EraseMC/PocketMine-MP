<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\convert;

use PHPUnit\Framework\TestCase;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class LegacyBlockItemMapTest extends TestCase{
	public function testMinecraft1_18ProfilesLoadTheirDataSnapshots() : void{
		foreach([
			ProtocolInfo::PROTOCOL_1_18_0,
			ProtocolInfo::PROTOCOL_1_18_10,
			ProtocolInfo::PROTOCOL_1_18_30,
		] as $protocolId){
			self::assertNotEmpty(BlockTranslator::loadFromProtocolId($protocolId)->getBlockStateDictionary()->getStates());
			self::assertNotEmpty(ItemTypeDictionaryFromDataHelper::loadFromProtocolId($protocolId)->getEntries());
			self::assertSame('minecraft:item.acacia_door', BlockItemIdMapFromDataHelper::loadFromProtocolId($protocolId)->lookupItemId('minecraft:acacia_door'));
		}
	}

	public function testLegacyBlockItemAliasIsLoadedFromItsOwnProfile() : void{
		$legacy = BlockItemIdMapFromDataHelper::loadFromProtocolId(ProtocolInfo::PROTOCOL_1_19_0);
		self::assertSame('minecraft:item.acacia_door', $legacy->lookupItemId('minecraft:acacia_door'));
		self::assertSame('minecraft:acacia_door', $legacy->lookupBlockId('minecraft:item.acacia_door'));

		$current = BlockItemIdMapFromDataHelper::loadFromProtocolId(ProtocolInfo::CURRENT_PROTOCOL);
		self::assertNull($current->lookupItemId('minecraft:acacia_door'));
	}
}
