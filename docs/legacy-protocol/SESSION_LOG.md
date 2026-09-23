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
- No 1.19 protocol is advertised as supported yet.

## 2026-09-20 — packet, inventory, and historical data milestone

- Reintroduced the 1.19.0 `AdventureSettingsPacket` and its `AddPlayerPacket` payload variant. The packet is registered in the generator's legacy packet list so regeneration retains it.
- Added older recipe ingredient, smithing, skin, attribute, item request, and container-ID wire layouts in `BedrockProtocol`, with byte fixtures for the 1.19.30 and 1.19.50 boundaries.
- Restored 27 immutable 1.19 files in `BedrockData` from tree `285cdbb07a8fb188972e9564bf2b9ece11670452^`; each working-tree file was compared against its source Git object ID. These are six canonical block-state palettes, six block-state meta maps, six item lists, six block-to-item maps, and three R12 block maps.
- Reconnected block palette, item list, and item schema selection for the 11 stable 1.19 protocol IDs in `PocketMine-MP` using the exact mappings preserved immediately before the 2023 removal commit.
- The server's Composer manifest and lockfile now point at the two feature branches and pin `BedrockData` to `349adc3f1cdf5b43152862e094164c189bbc6752` and `BedrockProtocol` to `c404b35685f7bf07297c2b8440b6bb7b1960450c`. Composer install has not run locally.
- PHP, Composer, Docker, and a WSL distribution are unavailable in this workspace. Codec tests and code generation therefore still need a PHP environment or CI; only Git whitespace checks and source object-hash checks have run locally.
- Transport bootstrap, gameplay packet completeness, and actual client joins remain open; no 1.19 profile is enabled in `ACCEPTED_PROTOCOL`.

## 2026-09-20 — transport bootstrap implementation

- Added a RakLib `ProtocolAcceptor` adapter accepting transport versions 10 and 11 with 11 primary, matching the dependency's current two-method interface.
- Added a bounded first-batch parser: one raw `RequestNetworkSettingsPacket` or one compressed `LoginPacket`. The selected compression state is retained for the session, and legacy login is forwarded to the regular login handler after a supported profile is selected.
- Added unit tests for the two first-batch forms, invalid raw/multiple packets, and the RakNet acceptor. These tests have not been executed locally because PHP and extensions are unavailable. The transport flow still requires CI and real-client verification before enabling any legacy profile.
- An unrelated working-tree change in `src/VersionInfo.php` appeared while this milestone was being implemented. It was left untouched and excluded from this work.

## 2026-09-20 — 1.19.62 profile refinement

- After parsing client data, a login with protocol 567 and exact `GameVersion` `1.19.62` switches the session to profile 568 before skin conversion. This follows the historical 1.19.62 wire exception corroborated by Nukkit-MOT; a normal 1.19.60 login remains on 567.
- A boundary unit test is present, but it has not been run; a real 1.19.62 client is also required before this exception can be considered verified.

## 2026-09-20 — entity flag conversion

- Added the pre-1.19.50 `CAN_DASH` gap conversion at the shared entity-metadata serializer. The conversion preserves `CAN_POWER_JUMP`, shifts later flags, and carries bit 63 between the two flag longs. A boundary test covers this case.
- Updated the server lockfile to `BedrockProtocol` commit `54d70e593422289fb981da2e2f73eb1f87f36ac1`. PHP tests still need to run before any legacy profile is enabled.
- Restored numeric-ID recipe ingredient conversion in `TypeConverter`, including the legacy ID-0 empty ingredient, so recipes decoded by the 1.19 codec can reach the core crafting model.

## 2026-09-20 — versioned block-item aliases and generated paths

- `TypeConverter` now loads the historical block-item alias table for each 1.19 profile instead of applying the current disk-format singleton to old network item IDs. A regression test checks the old `minecraft:item.acacia_door` alias against the current map.
- Updated generated BedrockData path constants for all 27 restored assets; the data provenance note was moved into `README.md`, which the path generator already excludes.
- PHP code generation and tests remain unexecuted locally; the generated constant list must be checked against the generator in CI or a PHP environment.
- A read-only source-versus-generated check confirms all 158 top-level BedrockData entries have constants in generator order, with no missing or extra paths. The server lockfile now pins `BedrockData` commit `bd3add47ef73114bbd03b89fcea7e34b6dd94696`.

## 2026-09-20 — 1.19.10 chunk tile workaround

- Restored the historical 1.19.10 tile workaround: chunk NBT contains only tile ID and coordinates, then full `BlockActorDataPacket` updates follow the chunk. Both cached and asynchronous chunk-send paths use the same helper.
- This is based on the pre-removal PocketMine implementation (`af8464adeb5ecf3288b97cc3c25889ea09e70ca7^`). It still requires a real 1.19.10 client test, especially item frames and lecterns.
- The subchunk-version question remains open: current serializer emits v8; Nukkit indicates v9 from 1.19.80, but this should not be changed without confirming the exact network-chunk payload and client behaviour.

## 2026-09-21 — local 1.19.10 client trial

- Found a compatible PHP 8.2 runtime with required extensions and installed the exact locked Composer dependencies. This enables actual server boot and PHP lint in this workspace.
- A dedicated data directory outside the Git repos is used for the test server. The server boots on UDP 19132; development builds are enabled only in this local directory.
- `ERASEMC_TEST_1_19_10=1` opt-in admits protocol 534 through the legacy login handler and advertises 1.19.10/534 in RakNet discovery for manual client testing. `ACCEPTED_PROTOCOL` remains unchanged. This is an experimental test gate, not a declaration of full support.
- Real-client login, world join, and gameplay still require observation. The unrelated user edit in `src/VersionInfo.php` remains untouched.

## 2026-09-21 — first real 1.19.10 login crash

- The client reached the session from `127.0.0.1`, proving loopback transport and discovery worked after the user's local Windows loopback exemption.
- First packet caused a PHP `ParseError` in `BedrockProtocol/src/serializer/CommonTypes.php:640`: a method call on an unparenthesized `new` expression. Corrected to `(new IntIdMetaItemDescriptor(0, 0))->write(...)` in protocol commit `0b65303bd202cdbf9f9aa8dd387c3975839c7bb2`.
- PHP 8.2 lint checked all 661 PHP files in `BedrockProtocol/src`, `tests`, and `tools`: zero syntax errors after the fix. This does not yet prove successful login or gameplay; repeat client test needed.

## 2026-09-21 — 1.19.10 client data shape

- A real 1.19.10 client now reaches `LoginPacketHandler`, but JSON mapping rejected missing `CompatibleWithClientSideChunkGen` before authentication. The historical 1.19 model marks that field as introduced in 1.19.80 and `TrustedSkin` in 1.19.20.
- `ClientData` now defaults both absent fields to false instead of requiring them from older clients. Modern required fields remain checked by the mapper. Protocol commit `a6a1cd684838e171d58424b14bfb639bece68332`.
- Retest required; this only addresses this specific client-data parsing failure.

## 2026-09-21 — legacy Xbox root selection

- The client now reaches player identification, but a validly signed legacy certificate chain was not marked Xbox-authenticated. The current verifier only recognized Mojang's newer root key; the historical 1.19 implementation also trusted the original root, removed later in commit `5386e8607977583767a62d8f4eba2011f9a050ea`.
- Reintroduced the original root key for 1.19 profiles only. JWT signature verification and authenticated-XUID checks remain enabled, and modern profiles continue using their prior key policy.
- Repeat 1.19.10 client login to verify whether the client's certificate was signed by that root. Do not disable `xbox-auth` as a substitute for this check.

## 2026-09-21 — Xbox chain diagnosis after repeat failure

- Repeated real-client login still ended in `Not authenticated` with the old root alone. For 1.19 profiles, the verifier now accepts either historically valid Mojang root, while still checking each JWT signature. Modern versions retain their prior trust policy.
- When a required-auth legacy chain contains no trusted root, the disconnect reason now includes only a count and short SHA-256 prefixes of the public signing keys. No JWT, private key, XUID, or other login data is logged. This diagnostic distinguishes an unsupported root from a self-signed-only legacy client chain.
- Do not infer from the client's signed-in UI state that its 1.19.10 network login still carries an Xbox-certified chain. Wait for the diagnostic from a fresh client attempt.

## 2026-09-21 — one-link chain and async task correction

- A fresh 1.19.10 login supplied exactly one certificate in its legacy chain (`array[1]` in the local crash trace). It therefore contains no separately Xbox-signed link; accepting it as Xbox-authenticated would bypass the server's required-auth policy. The signed-in client UI is not sufficient proof of a trusted network chain.
- The first diagnostic implementation used array-valued properties on an `AsyncTask`; `pmmpthread` rejected the non-thread-safe default and the server crashed. Root-key lists are now serialized into a string for the worker; signer fingerprint diagnostics use scalar properties only. PHP lint and a repeat boot/client attempt are still required.
- Keep `xbox-auth=on` by default. For further gameplay testing, using a separate explicitly offline local test profile would require a conscious security trade-off and must not be mistaken for authenticated compatibility.
- After the thread-safety fix and fresh restart, the real client was disconnected cleanly with `No trusted Xbox root in legacy chain (0 signer keys; SHA-256 prefixes: )`. The server remained running. Combined with the one-link chain in the prior trace, this confirms that this client session has no Xbox-signed legacy certificate link; adding trusted roots cannot authenticate it.

## 2026-09-21 — localhost-only offline gameplay trial

- With explicit user approval, the separate test runtime now sets `server-ip=127.0.0.1`, `enable-ipv6=off`, and `xbox-auth=off`. The UDP socket was verified bound only to `127.0.0.1:19132`; no production configuration changed. This is intentionally not Xbox-authenticated support.
- The client reached the `logged in` event, then crashed the server at `ItemTagToIdMap::make(534)` because the 1.19 profiles were absent from the tag-path table.
- Added all 1.19 protocol IDs to that table with the earliest available `item_tags-1.20.0.json` as a provisional recipe-downgrade map. This avoids the missing-key crash but is not a version-accurate recipe compatibility claim. A canonical 1.19 tag snapshot and crafting test remain release gates.
- A follow-up login reached the same crafting packet but crashed encoding a `TagItemDescriptor`: pre-1.19.30 recipes support only integer item descriptors. Restored the historical `TypeConverter` guard so unsupported tag recipes are skipped by the existing cache builder's conversion-error handler instead of crashing the server. Crafting completeness remains unverified.

## 2026-09-21 — Essential comparison and Xbox-auth correction

- Inspected `D:\BakuTeam\Essential` read-only. Its legacy bootstrap retries zlib decompression after a first-packet parse failure, while this fork uses an explicit bounded first-packet selection. Essential's `ItemTagToIdMap` also maps 1.19 to the 1.20 tag file, and its `TypeConverter` rejects tag recipe ingredients below 1.19.30; both align with our recent crash fixes.
- Essential's legacy login verifier checks the `x5u` signing key of **every** verified JWT, including the first. Our `ProcessLegacyLoginTask` incorrectly compared only the previous link's identity key, so the prior `0 signer keys` result omitted the first link. The earlier statement that a one-link chain cannot be Xbox-signed was not justified and is superseded here.
- `AuthJwtHelper` now returns the DER key that actually verified each legacy JWT signature. The task compares that verified key to the two trusted 1.19 Mojang roots and records its safe fingerprint. A single-link chain is authenticated only if its signature verifies against a trusted root. Real client re-test with `xbox-auth=on` is required; the currently running localhost gameplay test still has auth disabled.
- The localhost offline test reached `joined the game` at 21:36:35 after the recipe guard fix, with no immediate crash. This validates only a narrow 1.19.10 join smoke test, not complete gameplay or authenticated support.
- Essential contains an unconditional `$this->authenticated = true` immediately after signature verification (`ProcessLoginTask.php:170`, commit `2dc1f29efa` by blame), making any valid self-signed legacy chain appear Xbox-authenticated. This is not adopted: it would bypass the server's trust-root policy. Our rejection diagnostic now also reports the final client-key fingerprint; equality with the sole signer fingerprint proves a self-signed chain without exposing key material.
- Authenticated retest produced signer fingerprint `148d744560723b8b` and the identical final client-key fingerprint. The trusted original and replacement Mojang roots fingerprint as `fa52209a3b4f317f` and `43eef8791c71d29d`. This proves the observed 1.19.10 login is self-signed, not Xbox-certified. Keep rejecting it with `xbox-auth=on`; Essential only accepts the same shape because of its unconditional authentication bypass.

## 2026-09-21 — Essential-compatible legacy authentication

- At the user's explicit request, the previous decision was superseded and Essential's login behaviour was adopted exactly: after the complete legacy certificate chain and client-data JWT signatures validate, `ProcessLegacyLoginTask` unconditionally marks the session authenticated.
- Consequently, `xbox-auth=on` still rejects malformed, expired, or cryptographically invalid legacy login data, but it no longer requires a chain signer to match a trusted Mojang/Xbox root. A valid self-signed legacy chain is accepted as authenticated, matching Essential's `ProcessLoginTask` implementation.
- This compatibility behaviour weakens the meaning of Xbox authentication and must be reviewed before merging `feature/legacy-1.19` into `stable`.

## 2026-09-21 — local 1.19.22 client trial

- Added the opt-in `ERASEMC_TEST_1_19_22=1` gate for stable 1.19.21/1.19.22 protocol 545 and matching RakNet discovery advertisement. It remains an experimental manual-test gate and does not alter `ACCEPTED_PROTOCOL`.

## 2026-09-21 — local 1.19.31 client trial

- Added the opt-in `ERASEMC_TEST_1_19_31=1` gate for stable 1.19.30/1.19.31 protocol 554 and matching RakNet discovery advertisement. This profile exercises the `RequestNetworkSettingsPacket` startup path and remains outside `ACCEPTED_PROTOCOL` until validated.

## 2026-09-21 — Minecraft 1.18 implementation started

- Replaced the merged/deleted `feature/legacy-1.19` branches with `feature/legacy-1.18` in PocketMine-MP, BedrockProtocol and BedrockData. User-owned local edits to `src/VersionInfo.php` and `start.cmd` remain untouched.
- Stable wire profiles are 475 (1.18.0-1.18.2), 486 (1.18.10-1.18.12) and 503 (1.18.30-1.18.33). Preview-only protocols remain out of scope.
- Restored all three historical immutable block/item data snapshots from the original PMMP history in BedrockData commit `7f7e86c`.

## 2026-09-21 — 1.18 codec gate and sub-chunk correction

- Removed protocols 475, 486 and 503 from `ACCEPTED_PROTOCOL` pending real-client gameplay verification. The opt-in `ERASEMC_TEST_1_18_PROTOCOL` gate remains available for local trials.
- Corrected the 1.18.0 `SubChunkPacket` layout: it contains an absolute sub-chunk position before the response data and an optional blob-hash flag even when caching is disabled. Added a fixed-byte regression test.
- BedrockProtocol PHPUnit: 515 tests, 993 assertions pass. User is obtaining clients for each of the three protocol profiles; real join and gameplay gates are still open.

## 2026-09-21 — 1.18.2 live login trial

- A 1.18.2 client (protocol 475) completed login and joined the world, then disconnected on the first `PlayerAuthInputPacket` with a short-read error.
- Historical BedrockProtocol commit `0c00504` shows that `interactionMode` was added in 1.19.0. The decoder had read this field from 1.18 traffic, shifting all following fields. Gate both decoding and encoding at protocol 527.
- Added a fixed-layout regression test for both sides of that boundary; BedrockProtocol PHPUnit: 516 tests, 996 assertions. Client movement and gameplay still need a live retest after restart.
- The next live 1.18.2 attempt joined and moved, but breaking a block produced a `PlayerActionPacket` short-read. Historical commit `0c00504` also added `resultPosition` to this packet in 1.19.0. Gate it at 527 and use `blockPosition` as the legacy result position; added a byte-layout test. BedrockProtocol PHPUnit: 517 tests, 999 assertions. Block-breaking live retest pending.
- User confirmed the restarted 1.18.2 client works after the `PlayerActionPacket` fix. Confirmed scope so far: login, world join, movement and a block-breaking attempt without the prior disconnect. Inventory, crafting, entity interaction, reconnect and broader gameplay remain unverified; protocols 486 and 503 still need their own live trials.
- User confirmed 1.18.12 (protocol 486) works after joining the localhost test server. The server log confirms login and world join without a disconnect at the time of this note. The requested gameplay checklist is user-reported as working; protocol 503 and release-gate breadth remain unverified, so a full 1.18 stable promotion is not yet justified.
- The owner subsequently requested stable promotion despite the untested protocol 503 and incomplete gameplay checklist, noting that no players currently use that profile. Release work therefore admits 475/486/503 while retaining an explicit verification caveat in `1.18.md`.

## 2026-09-23 — 1.17 implementation in progress

- Created `feature/legacy-1.17` in all three repositories from current release branches. Preserved user-owned local edits to `src/VersionInfo.php` and `start.cmd` without staging them.
- Reviewed Opus 5.5 commits: core `24321b9cf` keeps rootless legacy chains unauthenticated; core `1cf5dffc5` updates protocol dependency; BedrockProtocol `f939fcd` clamps 1.18 sound IDs. Do not regress them.
- Stable profiles: 440 (1.17.0–1.17.2), 448 (1.17.10–1.17.11), 465 (1.17.30–1.17.34), 471 (1.17.40–1.17.41). User plans to install 1.17.2, 1.17.11, 1.17.34, 1.17.41 for live trials.
- BedrockData commit `3b9d004` restores historical 1.17 block/item snapshots; protocol commits `c5da675` and `ebc65f8` restore IDs, wire-layout boundaries, optional early skin field and 1.17 sound limit. Core pins these feature branches, restores chunk and data paths, and adds a single-profile opt-in `ERASEMC_TEST_1_17_PROTOCOL` gate.
- Automated checks to date: BedrockProtocol 526 PHPUnit tests, 1022 assertions; core initial suite 197 tests, 96232 assertions and targeted 1.17 data/chunk tests pass. Core PHPStan is green. BedrockProtocol PHPStan reports 16 pre-existing errors in unrelated source/tests; no new 1.17 errors.
- Not release-ready: real-client tests pending; historical block-item, block-meta and tag maps are unavailable and currently fall back to newer data. Validate rendering, recipes and interactions before admitting 1.17 into `ACCEPTED_PROTOCOL` or merging to stable.

## 2026-09-23 — 1.17.2 localhost client diagnosis

- A dedicated `test-runtime-1.17` binds only to `127.0.0.1:19132` with `xbox-auth=true`. RakNet discovery advertises protocol 440 and 1.17.2. The server is launched through `start.cmd`; this runtime is outside all three repositories.
- The first client attempts completed login, requested chunk radius 8, received 224 chunks and spawn status but did not acknowledge spawn; `Locating Server` hung until timeout. Added `ERASEMC_TRACE_1_17=1` stage-only diagnostics (no credentials or raw packet bytes). The 1.17 client's wire movement value 2 means rewind mode, while the modern server called it V3; selecting value 1 allowed a later 1.17.2 attempt to send `SetLocalPlayerAsInitializedPacket` and reach `joined the game`. More repetitions are needed to isolate whether this change was causative.
- On breaking a block the client was kicked by `Invalid action type for PlayerBlockAction` while decoding `PlayerAuthInputPacket`. The first fix (`87f11d5`) skipped the action extension, but this was incomplete: with `debug.level: 2`, real 1.17.0 packets showed 9-55 unread bytes after the movement delta, and the client had no block-breaking animation/drops. BedrockProtocol `ecd044a` restores flag-gated interaction, stack-request and block-action decoding for 1.17. This mode differs from historical servers that configured legacy movement. The corrected decoder needs a fresh live trial.
- Live 1.17.0 inventory transfer repeatedly failed as `LoomStackRequestAction`. The 1.17 wire IDs 14/15 denote deprecated crafting actions; two more action IDs were inserted before them in 1.18. BedrockProtocol `ecd044a` fixes this mapping and adds fixed-byte regression tests for all four 1.17 profiles. Live inventory retest pending.
- Client build clarification: the owner reports the active client is 1.17.0, not 1.17.2 as previously assumed; both use protocol 440. Multiple attempts reached `joined the game`, but some stalled at `Locating Server` and timed out after chunks/spawn status. This intermittent spawn issue remains open.
- At the owner's request, `debug.level: 2` is enabled in this **test runtime** only. Debug output can include encoded network packet fragments and must not be shared in full; return it to level 1 after diagnosis.

## 2026-09-23 - protocol 440 gameplay and client-crash follow-up

- Windows Application Error events identified the tested installation as `MinecraftUWP_1.17.2.0` with executable version `1.17.0.2`. The old client's crashes at `Loading Resources`/`Locating Server` show `ucrtbase.dll` fail-fast code `0xc0000409`, identical fault offset across attempts. These are client-process crashes, not server-side kicks. Reducing only the test server's view distance from 16 to 4 did not prevent them; the setting was restored.
- A recoverable fresh-spawn test moved `players/khaoslxng.dat` to a backup; the same client crash occurred at the default spawn, so the saved player position is not a sufficient explanation. The original file was restored. The disposable test-created player file remains backed up; nothing was deleted.
- The subsequent 1.17.2 package joined with Xbox authentication, then breaking a block kicked with `PlayerAuthInputPacket: Invalid action type 280`. This is a field-offset error, not a genuine action 280. Historical 1.17 `ItemInteractionData` includes the complete `UseItemTransactionData`; the modern `decodeAuthInput()` read only its action list, leaving action type, block position, item and vectors to be misinterpreted as block actions. BedrockProtocol `a18da1b` restores `decodeData()`/`encodeData()` for <=1.17.40, with a combined interaction-and-block-action regression test. Live retest is required; do not promote to stable.
- A new executable build `1.17.2.1` (Windows package `MinecraftUWP_1.17.201.0`) crashed at `Locating Server` during the pre-spawn/chunk stage; the same build joined an Essential-based server according to the owner. Our StartGame sent the `locatorbar` rule to all versions; Essential gates it at 1.21.80. Gate the rule accordingly and retest. This difference is real, but causality for the crash is not established. If it persists, compare chunk/bootstrap delivery, especially the spawn-status threshold and versioned resource data.
- The next trial still crashed after chunk radius request and spawn status, with server timeout. At the owner's request, added `RecentPacketTypes` and an opt-in per-session disconnect summary for 1.17. It holds only 32 coalesced packet names plus counts/timings; no packet payloads, Xbox tokens, JWTs, or raw encoded data are logged by this diagnostic. The summary distinguishes packet queueing from RakLib batch submission and reports current handler/stage. It is diagnostic correlation, not proof that the last listed packet crashed the client.
- First disconnect trace: protocol 440, client requested chunk radius 32, server queued 859 chunks, `PlayStatusPacket` after chunk 51, no spawn acknowledgement. Windows recorded the client's `0xc0000409` crash around 50 seconds after connect; RakLib timed out at 59 seconds. Later `RequestChunkRadiusPacket` and `MovePlayerPacket` were discarded by `SpawnResponsePacketHandler`, consistent with the session still awaiting acknowledgement. The same handler is present in Essential. Next test: cap only the localhost runtime's view distance from 16 to 8; compare results before changing core behaviour.
