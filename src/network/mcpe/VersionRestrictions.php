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

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\Utils;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_int;
use function is_string;
use function preg_match;
use function str_starts_with;
use function trim;
use function usort;
use function version_compare;

/**
 * Decides which of the supported Minecraft versions may join, as configured in the multiversion section of
 * pocketmine.yml.
 */
final class VersionRestrictions{
	/**
	 * Used when pocketmine.yml has no multiversion.blocked-versions entry (e.g. a file from an older release): these
	 * profiles are supported but have not been verified with a real client.
	 */
	public const DEFAULT_BLOCKED_VERSIONS = ["1.16.0", "1.16.20", "1.16.200", "1.16.210", "1.16.220"];

	/**
	 * @param int[] $allowedProtocols
	 * @phpstan-param list<int> $allowedProtocols
	 */
	private function __construct(
		private array $allowedProtocols,
		private string $kickMessage
	){}

	public static function unrestricted() : self{
		return new self(ProtocolInfo::ACCEPTED_PROTOCOL, "");
	}

	/**
	 * @param mixed[] $blockedVersions
	 * @param string[] $warnings Filled with a message for every setting which could not be understood
	 */
	public static function create(string $minimumVersion, string $maximumVersion, array $blockedVersions, string $kickMessage, array &$warnings = []) : self{
		$minimum = null;
		$maximum = null;
		if(trim($minimumVersion) !== "" && ($minimum = self::resolveVersion($minimumVersion)) === null){
			$warnings[] = "Unknown minimum version \"$minimumVersion\"";
		}
		if(trim($maximumVersion) !== "" && ($maximum = self::resolveVersion($maximumVersion)) === null){
			$warnings[] = "Unknown maximum version \"$maximumVersion\"";
		}

		$blocked = [];
		foreach($blockedVersions as $entry){
			$matched = is_int($entry) || is_string($entry) ? self::matchEntry($entry) : [];
			if(count($matched) === 0){
				$warnings[] = "Unknown blocked version \"" . (is_string($entry) || is_int($entry) ? $entry : "?") . "\"";
			}
			foreach($matched as $protocol){
				$blocked[$protocol] = true;
			}
		}

		$allowed = array_values(array_filter(ProtocolInfo::ACCEPTED_PROTOCOL, fn(int $protocol) =>
			!isset($blocked[$protocol]) &&
			($minimum === null || $protocol >= $minimum) &&
			($maximum === null || $protocol <= $maximum)
		));

		return new self($allowed, $kickMessage);
	}

	public function isAllowed(int $protocolVersion) : bool{
		return in_array($protocolVersion, $this->allowedProtocols, true);
	}

	/**
	 * Returns whether the version is supported by the server but may not join because of the configuration.
	 */
	public function isBlocked(int $protocolVersion) : bool{
		return in_array($protocolVersion, ProtocolInfo::ACCEPTED_PROTOCOL, true) && !$this->isAllowed($protocolVersion);
	}

	/**
	 * @return int[]
	 * @phpstan-return list<int>
	 */
	public function getAllowedProtocols() : array{ return $this->allowedProtocols; }

	/**
	 * Custom message shown to players whose version may not join, or "" for the game's standard outdated screen.
	 */
	public function getKickMessage() : string{ return $this->kickMessage; }

	/**
	 * Returns a readable summary of the allowed versions, e.g. "1.16.100, 1.17.0 - 1.26.50".
	 */
	public function describeAllowedVersions() : string{
		$versions = self::getNamedProfiles();
		$accepted = ProtocolInfo::ACCEPTED_PROTOCOL;
		usort($accepted, fn(int $a, int $b) => $a <=> $b);

		$ranges = [];
		$start = null;
		$previous = null;
		foreach($accepted as $protocol){
			if($this->isAllowed($protocol)){
				$start ??= $protocol;
				$previous = $protocol;
			}elseif($start !== null && $previous !== null){
				$ranges[] = [$start, $previous];
				$start = null;
			}
		}
		if($start !== null && $previous !== null){
			$ranges[] = [$start, $previous];
		}

		return count($ranges) === 0 ? "none" : implode(", ", array_map(fn(array $range) => $range[0] === $range[1] ?
			$versions[$range[0]] :
			$versions[$range[0]] . " - " . $versions[$range[1]],
			$ranges
		));
	}

	/**
	 * Returns the protocol of the given Minecraft version. Patch releases resolve to the profile of their protocol,
	 * e.g. "1.16.101" to 1.16.100. Versions displayed in the new scheme ("26.50") are accepted too.
	 */
	public static function resolveVersion(string $version) : ?int{
		$version = self::normaliseVersion($version);
		if($version === null){
			return null;
		}
		[$major, $minor] = explode(".", $version, 3);
		$best = null;
		foreach(self::getNamedProfiles() as $protocol => $profileVersion){
			if(str_starts_with($profileVersion, "$major.$minor.") && version_compare($profileVersion, $version, "<=") && ($best === null || version_compare($profileVersion, self::getNamedProfiles()[$best], ">"))){
				$best = $protocol;
			}
		}
		return $best;
	}

	/**
	 * @return int[]
	 * @phpstan-return list<int>
	 */
	private static function matchEntry(int|string $entry) : array{
		if(is_int($entry) || preg_match('/^\d+$/', trim($entry)) === 1){
			$protocol = (int) $entry;
			return isset(self::getNamedProfiles()[$protocol]) ? [$protocol] : [];
		}
		$entry = trim($entry);
		if(preg_match('/^(\d+)\.(\d+)$/', $entry, $matches) === 1){
			//a whole release line, e.g. "1.16", or "26.10" in the new display scheme
			$line = $matches[1] === "1" ? $entry . "." : "1.$entry.";
			$protocols = array_keys(array_filter(self::getNamedProfiles(), fn(string $version) => str_starts_with($version, $line)));
			if(count($protocols) > 0 || $matches[1] !== "1"){
				return $protocols;
			}
		}
		$protocol = self::resolveVersion($entry);
		return $protocol !== null ? [$protocol] : [];
	}

	private static function normaliseVersion(string $version) : ?string{
		$version = trim($version);
		if(preg_match('/^(\d+)\.(\d+)(?:\.(\d+))?(?:\.\d+)*$/', $version, $matches) !== 1){
			return null;
		}
		if($matches[1] !== "1"){
			//new display scheme: "26.50" is network version 1.26.50
			return "1.{$matches[1]}." . $matches[2];
		}
		return "1.{$matches[2]}." . ($matches[3] ?? "0");
	}

	/** @phpstan-var array<int, string>|null */
	private static ?array $namedProfiles = null;

	/**
	 * @return string[] protocol => Minecraft version, for every named profile
	 * @phpstan-return array<int, string>
	 */
	private static function getNamedProfiles() : array{
		if(self::$namedProfiles === null){
			self::$namedProfiles = [];
			foreach(Utils::stringifyKeys((new \ReflectionClass(ProtocolInfo::class))->getConstants()) as $name => $value){
				if(is_int($value) && preg_match('/^PROTOCOL_(\d+)_(\d+)_(\d+)$/', $name, $matches) === 1){
					//several patch profiles may share a protocol; keep the lowest version for it
					$version = "{$matches[1]}.{$matches[2]}.{$matches[3]}";
					if(!isset(self::$namedProfiles[$value]) || version_compare($version, self::$namedProfiles[$value], "<")){
						self::$namedProfiles[$value] = $version;
					}
				}
			}
		}
		return self::$namedProfiles;
	}
}
