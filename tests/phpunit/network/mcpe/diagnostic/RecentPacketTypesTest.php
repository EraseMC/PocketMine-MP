<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\diagnostic;

use PHPUnit\Framework\TestCase;

final class RecentPacketTypesTest extends TestCase{
	public function testBoundedAndCoalesced() : void{
		$trace = new RecentPacketTypes(2);
		$trace->recordInbound('RequestChunkRadiusPacket');
		$trace->recordInbound('RequestChunkRadiusPacket');
		$trace->recordOutbound('StartGamePacket');
		$trace->recordOutbound('LevelChunkPacket');

		self::assertSame(2, $trace->getInboundCount());
		self::assertSame(2, $trace->getOutboundCount());
		self::assertStringNotContainsString('RequestChunkRadiusPacket', $trace->formatRecent());
		self::assertStringContainsString('OUT StartGamePacket', $trace->formatRecent());
		self::assertStringContainsString('OUT LevelChunkPacket', $trace->formatRecent());
	}
}
