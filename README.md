# Erase-PocketMine-MP

[![CI](https://github.com/EraseMC/PocketMine-MP/actions/workflows/main.yml/badge.svg)](https://github.com/EraseMC/PocketMine-MP/actions/workflows/main.yml)
[![Latest release](https://img.shields.io/github/v/release/EraseMC/PocketMine-MP?sort=semver)](https://github.com/EraseMC/PocketMine-MP/releases/latest)
[![License](https://img.shields.io/badge/license-LGPL--3.0-blue.svg)](LICENSE)

Erase-PocketMine-MP is an open-source, high-performance server software for Minecraft: Bedrock Edition, written in PHP and maintained by the EraseMC organization.

The project is an independent fork of PocketMine-MP and NetherGames work. It keeps the existing plugin ecosystem and compatible namespaces while the EraseMC team builds its own release, protocol and compatibility roadmap.

## Compatibility target

The long-term compatibility target is Minecraft: Bedrock Edition 1.16.0 through the latest release. Support for additional client versions is being added and verified incrementally; check the release notes for the versions supported by a particular build.

| Minecraft: Bedrock Edition | Status |
| --- | --- |
| 1.16.100 – 1.16.101 | Supported |
| 1.16.0 – 1.16.40, 1.16.200 – 1.16.221 | Implemented, not yet tested with real clients; blocked by default |
| 1.17.0 – 1.17.41 | Supported |
| 1.18.0 – 1.18.33 | Supported |
| 1.19.x | Not supported yet |
| 1.20.0 – latest | Supported |

Which versions may join is configured in the `multiversion` section of `pocketmine.yml`: a minimum and maximum version, and a list of blocked versions, release lines or protocol numbers.

## Getting started

- [Building from source](BUILDING.md)
- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)
- [Releases](https://github.com/EraseMC/PocketMine-MP/releases)
- [Issue tracker](https://github.com/EraseMC/PocketMine-MP/issues)

Erase-PocketMine-MP is not a vanilla server implementation. Features such as vanilla world generation, redstone and complete mob AI are outside the current core scope and may require plugins or future engine work.

## Related EraseMC repositories

- [BedrockProtocol](https://github.com/EraseMC/BedrockProtocol) — Minecraft: Bedrock Edition protocol implementation
- [BedrockData](https://github.com/EraseMC/BedrockData) — generated Bedrock protocol and game data

## Attribution and licensing

Erase-PocketMine-MP is distributed under the [GNU LGPL v3.0](LICENSE). This repository contains code and compatible APIs originating from PocketMine-MP and related projects; their copyright and license notices are preserved in the source tree.

EraseMC and this project are not affiliated with Mojang. Minecraft is a trademark of Microsoft Corporation.
