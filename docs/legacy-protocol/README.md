# Legacy protocol engineering notes

This directory is the durable technical memory for EraseMC Core legacy-protocol work. It is intentionally committed with the implementation so that a new development session can recover the exact scope, decisions, evidence, and outstanding verification work without relying on chat history.

## Reading order for a new session

1. Read [`1.19.md`](1.19.md) for the supported-client matrix and implementation status.
2. Read [`DECISIONS.md`](DECISIONS.md) before changing the login, transport, serialization, data-translation, or chunk paths.
3. Read [`SOURCES.md`](SOURCES.md) before adding a protocol boundary; it records the authoritative and corroborating evidence used here.
4. Read the newest entry in [`SESSION_LOG.md`](SESSION_LOG.md), then update it in the same change set as substantive work.

## Rules for every legacy protocol change

- Do not add a protocol number to `ACCEPTED_PROTOCOL` until its transport bootstrap, packet layouts, block state palette, item dictionary, recipes, and spawn packets are available for that protocol.
- A Minecraft patch release is supported only when it is covered by a verified network-protocol profile. Patch labels are not a substitute for a protocol version.
- Preserve the current-protocol path. Legacy handling must be selected by the negotiated profile; it must not alter bytes sent to current clients.
- Document every exceptional client fingerprint, especially when Mojang changes wire data without a network protocol bump.
- Add deterministic codec/data tests for every profile boundary. A real-client join and play-through test is required before a profile is marked release-ready.
- Keep source attribution and licence notices intact. Historical PocketMine commits and Mojang documentation are evidence, not a reason to copy code blindly across architectural generations.

## Scope policy

The first delivery target is the stable Bedrock Edition 1.19 release line, from 1.19.0 through 1.19.83. Preview/beta builds are tracked separately and are not implied by stable-release support.

## Ownership

- Work branch: `feature/legacy-1.19` in `PocketMine-MP`, `BedrockProtocol`, and `BedrockData`.
- Integration target: this branch only. `stable` must remain untouched until the verification gates in `1.19.md` have passed.
- Persistent owner: EraseMC.
