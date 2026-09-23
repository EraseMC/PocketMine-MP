<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\serializer;

use PHPUnit\Framework\TestCase;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\convert\BlockTranslator;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;

final class Legacy1_17ChunkSerializerTest extends TestCase{
	public function test1_17OverworldUsesOldHeight() : void{
		self::assertSame([0, 15], ChunkSerializer::getDimensionChunkBounds(DimensionIds::OVERWORLD, ProtocolInfo::PROTOCOL_1_17_40));
		self::assertSame([-4, 19], ChunkSerializer::getDimensionChunkBounds(DimensionIds::OVERWORLD, ProtocolInfo::PROTOCOL_1_18_0));
	}

	public function test1_17BiomesUseTwoDimensionalByteArray() : void{
		$chunk = new Chunk([], false);
		$chunk->setBiomeId(3, 64, 5, 42);
		$out = new ByteBufferWriter();
		ChunkSerializer::serializeBiomes($chunk, DimensionIds::OVERWORLD, ProtocolInfo::PROTOCOL_1_17_0, $out);
		$biomes = $out->getData();
		self::assertSame(256, strlen($biomes));
		self::assertSame(42, ord($biomes[5 * 16 + 3]));
	}

	public function testZeroBitBlockPaletteBoundary() : void{
		$subChunk = new SubChunk(0, [new PalettedBlockArray(0)], new PalettedBlockArray(0));
		foreach([
			ProtocolInfo::PROTOCOL_1_17_0 => 3,
			ProtocolInfo::PROTOCOL_1_17_10 => 3,
			ProtocolInfo::PROTOCOL_1_17_30 => 1,
			ProtocolInfo::PROTOCOL_1_17_40 => 1,
		] as $protocolId => $header){
			$out = new ByteBufferWriter();
			ChunkSerializer::serializeSubChunk($subChunk, BlockTranslator::loadFromProtocolId($protocolId), $out, false, $protocolId);
			self::assertSame($header, ord($out->getData()[2]));
		}
	}
}
