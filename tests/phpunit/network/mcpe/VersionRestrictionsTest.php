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
use pocketmine\network\mcpe\protocol\ProtocolInfo;

final class VersionRestrictionsTest extends TestCase{

	public function testResolvesPatchReleasesToTheirProfile() : void{
		self::assertSame(ProtocolInfo::PROTOCOL_1_16_0, VersionRestrictions::resolveVersion("1.16.1"));
		self::assertSame(ProtocolInfo::PROTOCOL_1_16_20, VersionRestrictions::resolveVersion("1.16.40"));
		self::assertSame(ProtocolInfo::PROTOCOL_1_16_100, VersionRestrictions::resolveVersion("1.16.101"));
		self::assertSame(ProtocolInfo::PROTOCOL_1_17_30, VersionRestrictions::resolveVersion("1.17.34"));
		self::assertSame(ProtocolInfo::PROTOCOL_1_26_50, VersionRestrictions::resolveVersion("26.50"));
		self::assertNull(VersionRestrictions::resolveVersion("1.2.0"));
		self::assertNull(VersionRestrictions::resolveVersion("latest"));
	}

	public function testBlockedEntries() : void{
		$warnings = [];
		$restrictions = VersionRestrictions::create("", "", ["1.18", ProtocolInfo::PROTOCOL_1_17_10, "1.17.2", "nonsense"], "", $warnings);

		foreach([ProtocolInfo::PROTOCOL_1_18_0, ProtocolInfo::PROTOCOL_1_18_10, ProtocolInfo::PROTOCOL_1_18_30, ProtocolInfo::PROTOCOL_1_17_10, ProtocolInfo::PROTOCOL_1_17_0] as $protocol){
			self::assertTrue($restrictions->isBlocked($protocol), "protocol $protocol");
		}
		self::assertFalse($restrictions->isBlocked(ProtocolInfo::PROTOCOL_1_17_30));
		self::assertFalse($restrictions->isBlocked(ProtocolInfo::CURRENT_PROTOCOL));
		self::assertSame(['Unknown blocked version "nonsense"'], $warnings);
	}

	public function testVersionRange() : void{
		$restrictions = VersionRestrictions::create("1.17.30", "1.18.12", [], "Use 1.17.30 to 1.18.12");
		self::assertTrue($restrictions->isBlocked(ProtocolInfo::PROTOCOL_1_17_10));
		self::assertTrue($restrictions->isAllowed(ProtocolInfo::PROTOCOL_1_17_30));
		self::assertTrue($restrictions->isAllowed(ProtocolInfo::PROTOCOL_1_18_10));
		self::assertTrue($restrictions->isBlocked(ProtocolInfo::PROTOCOL_1_18_30));
		self::assertSame("1.17.30 - 1.18.10", $restrictions->describeAllowedVersions());
		self::assertSame("Use 1.17.30 to 1.18.12", $restrictions->getKickMessage());
	}

	public function testOnlyNarrowsAcceptedProtocols() : void{
		//profiles outside ACCEPTED_PROTOCOL (e.g. opt-in development trials) are not decided by the configuration
		$restrictions = VersionRestrictions::create("", "", ["1.19"], "");
		self::assertNotContains(ProtocolInfo::PROTOCOL_1_19_10, ProtocolInfo::ACCEPTED_PROTOCOL);
		self::assertFalse($restrictions->isBlocked(ProtocolInfo::PROTOCOL_1_19_10));
	}
}
