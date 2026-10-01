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

use function count;
use function explode;
use function inet_pton;
use function intdiv;
use function is_array;
use function is_numeric;
use function is_string;
use function ord;
use function strlen;
use function substr;

/**
 * Addresses and CIDR ranges of proxies that may report the real address of the players they carry.
 */
final class TrustedProxies{

	/**
	 * @var string[][] packed network address and prefix length in bits
	 * @phpstan-var list<array{string, int}>
	 */
	private array $ranges = [];

	/**
	 * @param string[] $entries
	 * @phpstan-param list<string> $entries
	 *
	 * @throws \InvalidArgumentException if an entry is neither an address nor a CIDR range
	 */
	public function __construct(array $entries){
		foreach($entries as $entry){
			$parts = explode("/", $entry, 2);
			$address = @inet_pton($parts[0]);
			if($address === false){
				throw new \InvalidArgumentException("Invalid trusted proxy \"$entry\"");
			}
			$bits = strlen($address) * 8;
			if(count($parts) === 2){
				if(!is_numeric($parts[1]) || (int) $parts[1] < 0 || (int) $parts[1] > $bits){
					throw new \InvalidArgumentException("Invalid prefix length in trusted proxy \"$entry\"");
				}
				$bits = (int) $parts[1];
			}
			$this->ranges[] = [$address, $bits];
		}
	}

	/**
	 * @throws \InvalidArgumentException if the value is not a list of addresses and CIDR ranges
	 */
	public static function fromConfig(mixed $value) : self{
		if(!is_array($value)){
			throw new \InvalidArgumentException("Trusted proxies must be a list");
		}
		$entries = [];
		foreach($value as $entry){
			if(!is_string($entry)){
				throw new \InvalidArgumentException("Trusted proxies must be strings");
			}
			$entries[] = $entry;
		}
		return new self($entries);
	}

	public function contains(string $ip) : bool{
		$address = @inet_pton($ip);
		if($address === false){
			return false;
		}
		foreach($this->ranges as [$network, $bits]){
			if(strlen($network) === strlen($address) && self::samePrefix($network, $address, $bits)){
				return true;
			}
		}
		return false;
	}

	private static function samePrefix(string $a, string $b, int $bits) : bool{
		$bytes = intdiv($bits, 8);
		if(substr($a, 0, $bytes) !== substr($b, 0, $bytes)){
			return false;
		}
		$rest = $bits % 8;
		if($rest === 0){
			return true;
		}
		$mask = (0xff << (8 - $rest)) & 0xff;
		return (ord($a[$bytes]) & $mask) === (ord($b[$bytes]) & $mask);
	}
}
