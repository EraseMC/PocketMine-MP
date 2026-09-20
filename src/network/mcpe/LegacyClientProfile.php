<?php

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe;

use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class LegacyClientProfile{
	private function __construct(){}

	public static function select(int $loginProtocol, string $gameVersion) : int{
		//1.19.62 logs in as 567 but encodes skin data as 568.
		return $loginProtocol === ProtocolInfo::PROTOCOL_1_19_60 && $gameVersion === '1.19.62' ?
			ProtocolInfo::PROTOCOL_1_19_63 : $loginProtocol;
	}
}
