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

use PHPUnit\Framework\TestCase;

final class TrustedProxiesTest extends TestCase{

	public function testExactAddress() : void{
		$proxies = new TrustedProxies(["127.0.0.1", "::1"]);
		self::assertTrue($proxies->contains("127.0.0.1"));
		self::assertTrue($proxies->contains("::1"));
		self::assertFalse($proxies->contains("127.0.0.2"));
	}

	public function testRange() : void{
		$proxies = new TrustedProxies(["172.16.0.0/12", "fd00::/8"]);
		self::assertTrue($proxies->contains("172.16.0.1"));
		self::assertTrue($proxies->contains("172.31.255.254"));
		self::assertFalse($proxies->contains("172.32.0.1"));
		self::assertFalse($proxies->contains("172.15.255.255"));
		self::assertTrue($proxies->contains("fd12::1"));
		self::assertFalse($proxies->contains("fe80::1"));
	}

	public function testFamiliesDoNotMix() : void{
		$proxies = new TrustedProxies(["0.0.0.0/0"]);
		self::assertTrue($proxies->contains("8.8.8.8"));
		self::assertFalse($proxies->contains("::ffff:8.8.8.8"));
	}

	public function testEmptyTrustsNothing() : void{
		self::assertFalse((new TrustedProxies([]))->contains("127.0.0.1"));
	}

	public function testInvalidInput() : void{
		self::assertFalse((new TrustedProxies(["127.0.0.1"]))->contains("not an address"));
	}

	public function testInvalidEntry() : void{
		$this->expectException(\InvalidArgumentException::class);
		new TrustedProxies(["10.0.0.0/33"]);
	}
}
