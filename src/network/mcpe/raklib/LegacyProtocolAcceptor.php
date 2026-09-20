<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\raklib;

use raklib\server\ProtocolAcceptor;

/** Accepts the RakNet transport versions used on both sides of the 1.19.30 bootstrap change. */
final class LegacyProtocolAcceptor implements ProtocolAcceptor{
	public const LEGACY_VERSION = 10;

	public function __construct(private int $primaryVersion){}

	public function accepts(int $protocolVersion) : bool{
		return $protocolVersion === self::LEGACY_VERSION || $protocolVersion === $this->primaryVersion;
	}

	public function getPrimaryVersion() : int{
		return $this->primaryVersion;
	}
}
