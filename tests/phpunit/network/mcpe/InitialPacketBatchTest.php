<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe;

use PHPUnit\Framework\TestCase;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\raklib\LegacyProtocolAcceptor;
use pocketmine\network\PacketHandlingException;

final class InitialPacketBatchTest extends TestCase{
	private static function batch(DataPacket ...$packets) : string{
		$encoded = [];
		foreach($packets as $packet){
			$writer = new ByteBufferWriter();
			$packet->encode($writer, ProtocolInfo::CURRENT_PROTOCOL);
			$encoded[] = $writer->getData();
		}
		$out = new ByteBufferWriter();
		PacketBatch::encodeRaw($out, $encoded);
		return $out->getData();
	}

	public function testModernBootstrapStaysUncompressed() : void{
		$batch = self::batch(RequestNetworkSettingsPacket::create(ProtocolInfo::CURRENT_PROTOCOL));
		self::assertSame([$batch, false], InitialPacketBatch::decode($batch, new ZlibCompressor(7, 256, 8192), PacketPool::getInstance()));
	}

	public function testLegacyBootstrapRequiresCompressedLogin() : void{
		$batch = self::batch(LoginPacket::create(ProtocolInfo::PROTOCOL_1_19_0, '{}', 'jwt'));
		$compressor = new ZlibCompressor(7, 0, 8192);
		self::assertSame([$batch, true], InitialPacketBatch::decode($compressor->compress($batch), $compressor, PacketPool::getInstance()));

		$this->expectException(PacketHandlingException::class);
		InitialPacketBatch::decode($batch, $compressor, PacketPool::getInstance());
	}

	public function testFirstBatchRejectsMultiplePackets() : void{
		$batch = self::batch(RequestNetworkSettingsPacket::create(ProtocolInfo::CURRENT_PROTOCOL), RequestNetworkSettingsPacket::create(ProtocolInfo::CURRENT_PROTOCOL));
		$this->expectException(PacketHandlingException::class);
		InitialPacketBatch::decode($batch, new ZlibCompressor(7, 0, 8192), PacketPool::getInstance());
	}

	public function testRakNet10And11AcceptedWith11Primary() : void{
		$acceptor = new LegacyProtocolAcceptor(11);
		self::assertSame(11, $acceptor->getPrimaryVersion());
		self::assertTrue($acceptor->accepts(10));
		self::assertTrue($acceptor->accepts(11));
		self::assertFalse($acceptor->accepts(9));
	}
}
