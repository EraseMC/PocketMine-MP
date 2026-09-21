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
