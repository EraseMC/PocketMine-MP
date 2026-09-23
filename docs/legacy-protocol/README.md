# Legacy protocol engineering notes

This directory is the durable technical memory for EraseMC Core legacy-protocol work. It is intentionally committed with the implementation so that a new development session can recover the exact scope, decisions, evidence, and outstanding verification work without relying on chat history.

## Reading order for a new session

1. Read [`1.17.md`](1.17.md), [`1.18.md`](1.18.md) and [`1.19.md`](1.19.md) for client matrices and implementation status.
2. Read [`DECISIONS.md`](DECISIONS.md) before changing the login, transport, serialization, data-translation, or chunk paths.
3. Read [`SOURCES.md`](SOURCES.md) before adding a protocol boundary; it records the authoritative and corroborating evidence used here.
4. Read the newest entry in [`SESSION_LOG.md`](SESSION_LOG.md), then update it in the same change set as substantive work.

## Rules for every legacy protocol change

- Do not add a protocol number to `ACCEPTED_PROTOCOL` until its transport bootstrap, packet layouts, block state palette, item dictionary, recipes, and spawn packets are available for that protocol.
- A Minecraft patch release is fully verified only when its network-protocol profile has passed real-client tests. Patch labels and admission in `ACCEPTED_PROTOCOL` are not substitutes for verification.
- Preserve the current-protocol path. Legacy handling must be selected by the negotiated profile; it must not alter bytes sent to current clients.
- Document every exceptional client fingerprint, especially when Mojang changes wire data without a network protocol bump.
- Add deterministic codec/data tests for every profile boundary. A real-client join and play-through test is required before a profile is described as fully verified; explicitly document any owner-approved release exception.
- Keep source attribution and licence notices intact. Historical PocketMine commits and Mojang documentation are evidence, not a reason to copy code blindly across architectural generations.

## Scope policy

The 1.19 and 1.18 lines remain documented in their respective files; the current development target is 1.17.x. Preview/beta builds are tracked separately and are not implied by stable-release support.

## Ownership

- Work branch: `feature/legacy-1.17` in `PocketMine-MP`, `BedrockProtocol`, and `BedrockData`.
- Integration target: PocketMine-MP `stable` and dependency `master` branches only after client validation and explicit owner release request. See `1.18.md` for the earlier owner-approved unverified protocol 503 exception.
- Persistent owner: EraseMC.
