<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\convert;

use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use function is_array;
use function is_string;
use function json_decode;
use const JSON_THROW_ON_ERROR;

/** @internal */
final class BlockItemIdMapFromDataHelper{
	private function __construct(){}

	public static function loadFromProtocolId(int $protocolId) : BlockItemIdMap{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_0){
			return BlockItemIdMap::getInstance();
		}
		$path = match($protocolId){
			ProtocolInfo::PROTOCOL_1_19_80 => BedrockDataFiles::BLOCK_ID_TO_ITEM_ID_MAP_1_19_80_JSON,
			ProtocolInfo::PROTOCOL_1_19_70 => BedrockDataFiles::BLOCK_ID_TO_ITEM_ID_MAP_1_19_70_JSON,
			ProtocolInfo::PROTOCOL_1_19_63,
			ProtocolInfo::PROTOCOL_1_19_60 => BedrockDataFiles::BLOCK_ID_TO_ITEM_ID_MAP_1_19_63_JSON,
			ProtocolInfo::PROTOCOL_1_19_50 => BedrockDataFiles::BLOCK_ID_TO_ITEM_ID_MAP_1_19_50_JSON,
			ProtocolInfo::PROTOCOL_1_19_40 => BedrockDataFiles::BLOCK_ID_TO_ITEM_ID_MAP_1_19_40_JSON,
			ProtocolInfo::PROTOCOL_1_19_30,
			ProtocolInfo::PROTOCOL_1_19_21,
			ProtocolInfo::PROTOCOL_1_19_20,
			ProtocolInfo::PROTOCOL_1_19_10,
			ProtocolInfo::PROTOCOL_1_19_0 => BedrockDataFiles::BLOCK_ID_TO_ITEM_ID_MAP_1_19_0_JSON,
			ProtocolInfo::PROTOCOL_1_18_30 => BedrockDataFiles::BLOCK_ID_TO_ITEM_ID_MAP_1_18_30_JSON,
			ProtocolInfo::PROTOCOL_1_18_10,
			ProtocolInfo::PROTOCOL_1_18_0 => BedrockDataFiles::BLOCK_ID_TO_ITEM_ID_MAP_1_18_10_JSON,
			default => throw new AssumptionFailedError("Unknown legacy protocol ID $protocolId"),
		};
		$map = json_decode(Filesystem::fileGetContents($path), associative: true, flags: JSON_THROW_ON_ERROR);
		if(!is_array($map)){
			throw new AssumptionFailedError("Invalid legacy block-item map for protocol $protocolId");
		}
		foreach($map as $blockId => $itemId){
			if(!is_string($blockId) || !is_string($itemId)){
				throw new AssumptionFailedError("Invalid legacy block-item map entry for protocol $protocolId");
			}
		}
		return new BlockItemIdMap($map);
	}
}
