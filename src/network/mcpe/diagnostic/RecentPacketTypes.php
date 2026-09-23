<?php

declare(strict_types=1);

namespace pocketmine\network\mcpe\diagnostic;

use function array_map;
use function array_shift;
use function count;
use function implode;
use function microtime;
use function sprintf;

/**
 * Bounded, payload-free packet history for local protocol diagnostics.
 * Recording a packet means the server processed or queued it, not that a client received it.
 */
final class RecentPacketTypes{
	/** @phpstan-var list<array{time: float, direction: string, name: string, count: int}> */
	private array $recent = [];
	private float $startedAt;
	private int $inbound = 0;
	private int $outbound = 0;

	public function __construct(private int $limit = 32){
		if($limit < 1){
			throw new \InvalidArgumentException('Packet history limit must be positive');
		}
		$this->startedAt = microtime(true);
	}

	public function recordInbound(string $packetName) : void{
		++$this->inbound;
		$this->record('IN', $packetName);
	}

	public function recordOutbound(string $packetName) : void{
		++$this->outbound;
		$this->record('OUT', $packetName);
	}

	private function record(string $direction, string $packetName) : void{
		$elapsed = microtime(true) - $this->startedAt;
		$lastIndex = count($this->recent) - 1;
		if($lastIndex >= 0 && $this->recent[$lastIndex]['direction'] === $direction && $this->recent[$lastIndex]['name'] === $packetName){
			++$this->recent[$lastIndex]['count'];
			$this->recent[$lastIndex]['time'] = $elapsed;
			return;
		}
		$this->recent[] = ['time' => $elapsed, 'direction' => $direction, 'name' => $packetName, 'count' => 1];
		if(count($this->recent) > $this->limit){
			array_shift($this->recent);
		}
	}

	public function getInboundCount() : int{ return $this->inbound; }
	public function getOutboundCount() : int{ return $this->outbound; }

	public function formatRecent() : string{
		return implode(', ', array_map(static fn(array $event) : string => sprintf('+%.3fs %s %s%s', $event['time'], $event['direction'], $event['name'], $event['count'] > 1 ? ' x' . $event['count'] : ''), $this->recent));
	}
}
