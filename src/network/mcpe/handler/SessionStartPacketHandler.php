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

namespace pocketmine\network\mcpe\handler;

use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use function in_array;
use function getenv;

final class SessionStartPacketHandler extends PacketHandler{

	/**
	 * @phpstan-param \Closure() : void $onSuccess
	 * @phpstan-param \Closure(LoginPacket) : void $onLegacySuccess
	 */
	public function __construct(
		private NetworkSession $session,
		private \Closure $onSuccess,
		private \Closure $onLegacySuccess
	){}

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool{
		$protocolVersion = $packet->getProtocolVersion();
		if($protocolVersion < ProtocolInfo::PROTOCOL_1_19_30 || !$this->isCompatibleProtocol($protocolVersion)){
			$this->session->disconnectIncompatibleProtocol($protocolVersion);

			return true;
		}
		$this->session->setProtocolId($protocolVersion);

		//TODO: we're filling in the defaults to get pre-1.19.30 behaviour back for now, but we should explore the new options in the future
		$this->session->sendDataPacket(NetworkSettingsPacket::create(
			NetworkSettingsPacket::COMPRESS_EVERYTHING,
			$this->session->getCompressor()->getNetworkId(),
			false,
			0,
			0
		));
		($this->onSuccess)();

		return true;
	}

	public function handleLogin(LoginPacket $packet) : bool{
		$protocolVersion = $packet->protocol;
		if($protocolVersion >= ProtocolInfo::PROTOCOL_1_19_30 || !$this->isCompatibleProtocol($protocolVersion)){
			//The first batch has already selected legacy compression. There is no
			//negotiated serializer for an unsupported profile, so close without
			//attempting a version-dependent PlayStatus packet.
			$this->session->disconnect(KnownTranslationFactory::pocketmine_disconnect_incompatibleProtocol((string) $protocolVersion), notify: false);
			return true;
		}
		$this->session->setProtocolId($protocolVersion);
		($this->onLegacySuccess)($packet);
		return true;
	}

	protected function isCompatibleProtocol(int $protocolVersion) : bool{
		return in_array($protocolVersion, ProtocolInfo::ACCEPTED_PROTOCOL, true)
			|| ($protocolVersion === ProtocolInfo::PROTOCOL_1_19_10 && getenv('ERASEMC_TEST_1_19_10') === '1');
	}
}
