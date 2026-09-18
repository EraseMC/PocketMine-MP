# Legacy protocol session log

## 2026-09-18 — research baseline and branch isolation

- Created `feature/legacy-1.19` from the current EraseMC branches in `PocketMine-MP`, `BedrockProtocol`, and `BedrockData`. `stable` was not modified.
- Confirmed the stable 1.19 profile matrix: 527, 534, 544, 545, 554, 557, 560, 567, 568, 575, and 582.
- Identified the transport split: pre-1.19.30 has RakNet acceptor version 10 and starts with compressed login; 1.19.30+ uses RakNet 11 and starts with network-settings negotiation.
- Identified the undocumented protocol-bump exception: 1.19.62 logs in as 567 but needs the 568 skin/wire profile, selected from `GameVersion`.
- Confirmed that the exact 1.19 data blobs are preserved in `BedrockData` history and recorded their Git object IDs for later byte-for-byte validation.
- Reviewed Nukkit-MOT at `f63ff4d8831fadcc3d0b0c8f8ee832c674feb17e` and Mojang protocol docs at `6b4469359544936f0b2f124d08b3f3f1da9ea7e3`.

## 2026-09-18 — protocol codec foundation, first boundary batch

- Added named constants for every stable 1.19 protocol profile in `BedrockProtocol`; preview protocol IDs remain excluded.
- Restored and byte-fixture-tested the 1.19 layout boundaries for maps, modal-form replies, chunk publishing, network settings, request chunk radius, and player input analog movement.
- Restored the corresponding packet gates for actor data/properties, actor body yaw, command requests, structure waterlogging, map origins, `StartGamePacket`, and `LevelSettings`.
- Legacy decoders now create explicit safe defaults for fields unavailable in an older wire format (for example, actor properties and network permissions), rather than leaving typed properties uninitialised.
- Added a generator-owned registry of released protocol profiles. It preserves 1.19 constants across future BedrockData updates and explicitly keeps every 1.19 profile out of the accepted-protocol list until validation succeeds.
- The new PHP test cases could not be executed in this workspace because PHP, Composer, and Docker are not installed. `git diff --check` passed; the tests must run in CI or a PHP 8.1+ development environment before any profile is enabled.

### Next concrete work

1. Restore the remaining packet layout gates, including 1.19.0 abilities and inventory/recipe descriptors.
2. Implement/test the dual legacy/current bootstrap in `PocketMine-MP`.
3. Restore and hash-verify historical `BedrockData` assets, then connect the profile-specific translators.

### Not yet verified

- No real 1.19 client join has been performed in this branch.
- No binary BedrockData asset has been restored to the working tree.
- No 1.19 protocol is advertised as supported yet.
