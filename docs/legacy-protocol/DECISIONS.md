# Legacy protocol decisions

## ADR-001: Support is profile-based, not patch-label-based

**Decision:** Select serializers, data dictionaries, and feature gates by a named network profile. Map stable Minecraft patch releases to that profile only after protocol and behavioural compatibility are established.

**Why:** Many patch releases retain a protocol number; 1.19.62 is the critical counterexample because Mojang changed a skin field without a new login protocol. A plain `protocolId` is insufficient for every decision.

**Consequences:** The session stores its negotiated protocol and may refine it to a compatibility profile once trusted client data has been parsed. The refinement must be narrow, documented, and tested.

## ADR-002: Legacy transport begins with an explicit bootstrap state

**Decision:** Introduce a bootstrap state that accepts only the two documented initial forms: a RakNet-10 compressed legacy `LoginPacket`, or a RakNet-11 uncompressed `RequestNetworkSettingsPacket`.

**Why:** Earlier PocketMine code used decompression failure to infer the new flow. That was practical at the time but accepts an ambiguous input path. The current implementation must inspect, bound, and validate the first batch before switching state.

**Consequences:** Do not enable broad fallback decompression after bootstrap. Login packet parsing must not run until the profile is known. The RakNet acceptor must advertise/accept both 10 and 11 while retaining 11 as primary.

## ADR-003: Historical BedrockData assets are immutable inputs

**Decision:** Restore exact 1.19 data blobs from the existing repository history and verify their Git object hashes in automated tests/documentation.

**Why:** Block palettes and item dictionaries are wire data. Rebuilding them from a newer game version changes runtime IDs and produces silent client corruption.

**Consequences:** Binary files are restored byte-for-byte. Generated transformations require a separate reproducible tool and an explicit review; they are never substituted for the captured historical asset.

## ADR-004: Preserve upstream lineage and licences

**Decision:** Keep existing copyright and licence headers. Cite source commits in code comments only where the semantic reason cannot otherwise be made clear.

**Why:** The project is a fork, and legal attribution is required. EraseMC ownership and product identity do not remove upstream licence obligations.

## ADR-005: No compatibility claims before real-client testing

**Decision:** The public fully verified list remains separate from `ACCEPTED_PROTOCOL`. The preferred gate is real-client verification before admission. The owner explicitly approved a 1.18.x release with protocol 503 untested; document such exceptions without claiming full compatibility.

**Why:** A server can complete a login handshake yet fail at chunks, inventories, or entity metadata. Advertising an untested version harms operators.

## ADR-006: Keep released protocol profiles generator-owned

**Context:** `ProtocolInfo.php` is generated from the newest BedrockData protocol metadata. Ad-hoc legacy constants would be silently deleted by the next regeneration.

**Decision:** Maintain one reviewed profile registry in `BedrockProtocol/tools/protocol-profiles.php`. The generator requires a matching named released profile for its input version and emits both the named constants and the accepted-profile list. An owner-approved release exception may admit a profile before full client verification, with that status recorded explicitly.

**Status:** Accepted.
