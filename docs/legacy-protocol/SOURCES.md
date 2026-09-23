# 1.19 source register

All sources below were reviewed on 2026-09-18. Keep this register updated when evidence is added or superseded.

## Authoritative protocol material

| Source | Revision / file | What it establishes |
| --- | --- | --- |
| [Mojang bedrock-protocol-docs](https://github.com/Mojang/bedrock-protocol-docs) | `6b4469359544936f0b2f124d08b3f3f1da9ea7e3` | The project publishes Bedrock packet/type/enum schemas and states that protocol can change between releases. |
| Mojang legacy changelog | `legacy_changelogs/changelog_534_06_28_22.md` | 1.19.10 / protocol 534: abilities, adventure settings, actor/player layout, level settings. |
| Mojang legacy changelog | `legacy_changelogs/changelog_544_07_05_22.md` | 1.19.20 / protocol 544: maps, forms, chunk publisher, attributes, feature registry, start game. |
| Mojang legacy changelog | `legacy_changelogs/changelog_557_10_6_22.md` | 1.19.40 / protocol 557: synced properties, network settings, descriptors. |
| Mojang legacy changelog | `legacy_changelogs/changelog_560_11_10_22.md` | 1.19.50 / protocol 560: input locks, entity/container/packet changes. |
| Mojang legacy changelog | `legacy_changelogs/changelog_567_01_12_23.md` | 1.19.60 / protocol 567: command, crafting, abilities, events. |
| Mojang legacy changelog | `legacy_changelogs/changelog_575_02_21_23.md` | 1.19.70 / protocol 575: movement, recipes, camera, skins. |
| Mojang legacy changelog | `legacy_changelogs/changelog_582_03_22_23.md` | 1.19.80 / protocol 582: hashed block IDs, subchunks, biome, trims, signs. |

The current Mojang repository has no tagged protocol schema release for the 2022 1.19 line. Its legacy changelogs are therefore used for deltas; exact legacy packet implementation is cross-checked against preserved PocketMine history and real-client tests.

## Independent multi-version implementation

| Source | Revision / file | What it establishes |
| --- | --- | --- |
| [MemoriesOfTime/Nukkit-MOT](https://github.com/MemoriesOfTime/Nukkit-MOT) | `f63ff4d8831fadcc3d0b0c8f8ee832c674feb17e` | Active multi-version server implementation; its README advertises compatibility from 1.2 through 1.26.50. |
| Nukkit-MOT | `ProtocolInfo.java` | Stable 1.19 protocol constants: 527, 534, 544, 545, 554, 557, 560, 567, 568, 575, 582; preview constants are distinct. |
| Nukkit-MOT | `ProtocolCodecMapping.java` | Maps the stable 1.19 profile set to separate codecs. |
| Nukkit-MOT | `LoginPacket.java`, `NetworkSettingsPacket.java`, chunk/palette tests | Corroborates the 1.19.62 special case, network-settings boundary, and the subchunk-v9/hash boundary at 1.19.80. |

Nukkit-MOT is a Java implementation under a different architecture. It is used as behavioural corroboration only; do not transplant source code from it.

## Preserved PocketMine history in this fork

For 1.17, the local PocketMine-MP commits `57d274901` (1.17.0), `8b79253d3` (1.17.10), `8e2d06a88` (zero-bit chunk palette boundary) and `c4446ca28` (1.17.40) establish the wire and terrain differences. BedrockProtocol commits `faff7da` and `ea9e225` define the 1.17.30 and 1.17.40 packet changes; `040a883` defines the 1.18.0 boundary to avoid sending newer fields to 1.17. BedrockData tree `657395e^` and commit `f29b7be` contain the immutable 1.17 block/item snapshots. Missing versioned block-item, block-meta and tag maps are explicitly tracked in `1.17.md`.

| Repository | Commit | Relevance |
| --- | --- | --- |
| `PocketMine-MP` | `1579e5b8e39475963fb68deb0df9970d0ffbfe0f` | Initial 1.19.0 compatibility work. |
| `PocketMine-MP` | `06655bee7820b14fa75b3da38635ce462814f155` through `a8dec1adb151d787f7c60a50733decf2674cd6b3` | Incremental 1.19.10–1.19.80 changes and data/profile choices. |
| `BedrockProtocol` | `d214c816f4b3a79630e06a55776fdce56d2128d9` through `1bb0839506cedb9179a5b9de351f0ba6f0f17baf` | Packet and serializer changes for each 1.19 profile. |
| `PocketMine-MP` | `af8464adeb5ecf3288b97cc3c25889ea09e70ca7` | Removed older-protocol support: documents the old/new bootstrap distinction and all removed translation paths. |
| `BedrockProtocol` | `76dbfac167ff6190c31ee758d7c120f2ffd99787` | Removed packet layout gates below 1.20; this is the regression inventory to restore deliberately. |
| `BedrockData` | `285cdbb07a8fb188972e9564bf2b9ece11670452^` | Last tree containing exact 1.19 assets; use it for byte-for-byte restoration. |

## Release-to-protocol corroboration

The [Minecraft Wiki protocol table](https://minecraft.wiki/w/Template:Protocol_version/Table) and release pages are supplementary evidence for stable-client labels. For example, the [1.19.2 page](https://minecraft.wiki/w/Bedrock_Edition_1.19.2) records protocol 527, and the [1.19.10 page](https://minecraft.wiki/w/Bedrock_Edition_1.19.10) records protocol 534. The source of truth for implementation remains packet evidence plus tests.
