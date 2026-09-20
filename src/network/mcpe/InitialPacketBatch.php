<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\DataDecodeException;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\PacketHandlingException;
use function count;
use function iterator_to_array;
use function strlen;

/**
 * Resolves only the first MCPE batch. The two accepted forms are an uncompressed
 * network-settings request and a compressed legacy login, each as one packet.
 */
final class InitialPacketBatch{
	private function __construct(){}

	/**
	 * @return array{string, bool} Raw batch and whether legacy compression was used.
	 * @throws PacketHandlingException
	 */
	public static function decode(string $payload, Compressor $compressor, PacketPool $pool) : array{
		//A network-settings request is a tiny fixed-size packet. Keep the raw
		//probe bounded so an arbitrary login blob is never treated as raw data.
		if(strlen($payload) <= 64){
			try{
				$packets = iterator_to_array(PacketBatch::decodeRaw(new ByteBufferReader($payload)), false);
				if(count($packets) === 1 && $pool->getPacket($packets[0]) instanceof RequestNetworkSettingsPacket){
					return [$payload, false];
				}
			}catch(PacketDecodeException | DataDecodeException){
				//The sole alternative initial form is compressed legacy login.
			}
		}

		try{
			$decompressed = $compressor->decompress($payload);
		}catch(DecompressionException $e){
			throw PacketHandlingException::wrap($e, 'Invalid initial packet batch');
		}
		try{
			$packets = iterator_to_array(PacketBatch::decodeRaw(new ByteBufferReader($decompressed)), false);
			if(count($packets) !== 1 || !($pool->getPacket($packets[0]) instanceof LoginPacket)){
				throw new PacketHandlingException('Expected one legacy LoginPacket in initial compressed batch');
			}
		}catch(PacketDecodeException | DataDecodeException $e){
			throw PacketHandlingException::wrap($e, 'Invalid legacy login batch');
		}
		return [$decompressed, true];
	}
}
