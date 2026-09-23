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

/**
 * Generates a block_state_meta_map for a historical block palette which was captured without one. The map is indexed by
 * palette position and gives the legacy data value of each state, which older clients use as the damage of block items
 * and recipe ingredients.
 *
 * Values come from BDS-generated source maps (a canonical palette plus its meta map), matching identical states first
 * and otherwise states which are equal after upgrading both sides to the current format (the upgrade can merge states); the oldest suitable source should be listed first, because blocks flattened by
 * later versions lose their data values. States missing from every source fall back to the 1.12 ID/meta mapping of
 * BedrockBlockUpgradeSchema, then to 0.
 *
 * Usage: generate-block-state-meta-map.php <palette.nbt> <canonical|required> <output.json> [<source-palette.nbt> <source-meta-map.json>]...
 * "canonical" palettes are concatenated state compounds; "required" palettes (before 1.16.100) are a list of
 * {block, id} compounds.
 */

namespace pocketmine\tools\generate_block_state_meta_map;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\VarInt;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\network\mcpe\convert\BlockStateDictionary;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use Symfony\Component\Filesystem\Path;
use function array_slice;
use function count;
use function dirname;
use function file_put_contents;
use function fwrite;
use function is_array;
use function json_decode;
use function json_encode;
use function ksort;
use function min;
use const JSON_THROW_ON_ERROR;
use const STDERR;

require dirname(__DIR__) . '/vendor/autoload.php';

function stateKey(BlockStateData $state) : string{
	$states = [];
	foreach(Utils::stringifyKeys($state->getStates()) as $name => $tag){
		$states[$name] = (string) $tag;
	}
	ksort($states);
	return $state->getName() . json_encode($states, JSON_THROW_ON_ERROR);
}

if(count($argv) < 4 || count($argv) % 2 !== 0){
	fwrite(STDERR, "Usage: {$argv[0]} <palette.nbt> <canonical|required> <output.json> [<source-palette.nbt> <source-meta-map.json>]..." . PHP_EOL);
	exit(1);
}
[, $palettePath, $format, $outputPath] = $argv;

$paletteContents = Filesystem::fileGetContents($palettePath);
$palette = match($format){
	"canonical" => BlockStateDictionary::loadPaletteFromString($paletteContents),
	"required" => BlockStateDictionary::loadLegacyPaletteFromString($paletteContents),
	default => throw new \InvalidArgumentException("Unknown palette format $format")
};

$upgrader = GlobalBlockStateHandlers::getUpgrader()->getBlockStateUpgrader();

/** @var int[] $sourceMetaByRawState */
$sourceMetaByRawState = [];
/** @var int[] $sourceMetaByState */
$sourceMetaByState = [];
$sources = array_slice($argv, 4);
for($i = 0; $i < count($sources); $i += 2){
	$sourcePalette = BlockStateDictionary::loadPaletteFromString(Filesystem::fileGetContents($sources[$i]));
	$sourceMetaMap = json_decode(Filesystem::fileGetContents($sources[$i + 1]), true, flags: JSON_THROW_ON_ERROR);
	if(!is_array($sourceMetaMap) || count($sourceMetaMap) !== count($sourcePalette)){
		throw new \InvalidArgumentException("Source meta map {$sources[$i + 1]} does not match its palette");
	}
	foreach($sourcePalette as $index => $state){
		$sourceMetaByRawState[stateKey($state)] ??= $sourceMetaMap[$index];
		$sourceMetaByState[stateKey($upgrader->upgrade($state))] ??= $sourceMetaMap[$index];
	}
}

/** @var int[] $legacyMetaByState */
$legacyMetaByState = [];
$reader = new ByteBufferReader(Filesystem::fileGetContents(Path::join(\pocketmine\BEDROCK_BLOCK_UPGRADE_SCHEMA_PATH, "id_meta_to_nbt/1.12.0.bin")));
$nbtReader = new LittleEndianNbtSerializer();
for($idIndex = 0, $idCount = VarInt::readUnsignedInt($reader); $idIndex < $idCount; $idIndex++){
	$reader->readByteArray(VarInt::readUnsignedInt($reader)); //legacy string ID
	for($metaIndex = 0, $metaCount = VarInt::readUnsignedInt($reader); $metaIndex < $metaCount; $metaIndex++){
		$meta = VarInt::readUnsignedInt($reader);
		$offset = $reader->getOffset();
		$state = $upgrader->upgrade(BlockStateData::fromNbt($nbtReader->read($reader->getData(), $offset)->mustGetCompoundTag()));
		$reader->setOffset($offset);
		$key = stateKey($state);
		$legacyMetaByState[$key] = min($legacyMetaByState[$key] ?? $meta, $meta);
	}
}

$metaMap = [];
$fromSource = 0;
$fromLegacyMap = 0;
foreach($palette as $state){
	$key = stateKey($upgrader->upgrade($state));
	if(isset($sourceMetaByRawState[stateKey($state)])){
		$metaMap[] = $sourceMetaByRawState[stateKey($state)];
		++$fromSource;
	}elseif(isset($sourceMetaByState[$key])){
		$metaMap[] = $sourceMetaByState[$key];
		++$fromSource;
	}elseif(isset($legacyMetaByState[$key])){
		$metaMap[] = $legacyMetaByState[$key];
		++$fromLegacyMap;
	}else{
		$metaMap[] = 0;
	}
}

echo "States: " . count($metaMap) . ", from source maps: $fromSource, from the 1.12 ID/meta map: $fromLegacyMap, defaulted: " . (count($metaMap) - $fromSource - $fromLegacyMap) . PHP_EOL;

file_put_contents($outputPath, json_encode($metaMap, JSON_THROW_ON_ERROR) . "\n");
