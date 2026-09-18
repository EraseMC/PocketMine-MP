# EraseMC Core

[![CI](https://github.com/EraseMC/PocketMine-MP/actions/workflows/main.yml/badge.svg)](https://github.com/EraseMC/PocketMine-MP/actions/workflows/main.yml)
[![Latest release](https://img.shields.io/github/v/release/EraseMC/PocketMine-MP?sort=semver)](https://github.com/EraseMC/PocketMine-MP/releases/latest)
[![License](https://img.shields.io/badge/license-LGPL--3.0-blue.svg)](LICENSE)

EraseMC Core is an open-source, high-performance server software for Minecraft: Bedrock Edition, written in PHP and maintained by the EraseMC organization.

The project is an independent fork of PocketMine-MP and NetherGames work. It keeps the existing plugin ecosystem and compatible namespaces while the EraseMC team builds its own release, protocol and compatibility roadmap.

## Compatibility target

The long-term compatibility target is Minecraft: Bedrock Edition 1.16.0 through the latest release. Support for additional client versions is being added and verified incrementally; check the release notes for the versions supported by a particular build.

## Getting started

- [Building from source](BUILDING.md)
- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)
- [Releases](https://github.com/EraseMC/PocketMine-MP/releases)
- [Issue tracker](https://github.com/EraseMC/PocketMine-MP/issues)

EraseMC Core is not a vanilla server implementation. Features such as vanilla world generation, redstone and complete mob AI are outside the current core scope and may require plugins or future engine work.

## Related EraseMC repositories

- [BedrockProtocol](https://github.com/EraseMC/BedrockProtocol) — Minecraft: Bedrock Edition protocol implementation
- [BedrockData](https://github.com/EraseMC/BedrockData) — generated Bedrock protocol and game data

## Attribution and licensing

EraseMC Core is distributed under the [GNU LGPL v3.0](LICENSE). This repository contains code and compatible APIs originating from PocketMine-MP and related projects; their copyright and license notices are preserved in the source tree.

EraseMC and this project are not affiliated with Mojang. Minecraft is a trademark of Microsoft Corporation.
