# Building EraseMC Core
## Pre-requisites
- A bash shell (git bash is sufficient for Windows)
- [`git`](https://git-scm.com) available in your shell
- PHP 8.2 or newer available in your shell
- [`composer`](https://getcomposer.org) available in your shell

## Custom PHP binaries
Because EraseMC Core requires several non-standard PHP extensions and configuration, custom PHP binaries may be needed for local development.

- [Upstream prebuilt binaries](https://github.com/pmmp/PHP-Binaries/releases)
- [Compile scripts](https://github.com/pmmp/php-build-scripts) are provided as a submodule in the path `build/php`

If you use a custom binary, you'll need to replace `composer` usages in this guide with `path/to/your/php path/to/your/composer.phar`.

## Setting up environment
1. `git clone https://github.com/EraseMC/PocketMine-MP.git`
2. `composer install`

## Checking out a different branch to build
1. `git checkout <branch to checkout>`
2. Re-run `composer install` to synchronize dependencies.

## Optimizing for release builds
1. Add the flags `--no-dev --classmap-authoritative` to your `composer install` command. This will reduce build size and improve autoloading speed.

## Building the server PHAR
Run `composer make-server` using your preferred PHP binary. It'll drop the server PHAR into the current working directory.

You can also use the `--out` option to change the output filename.

## Running EraseMC Core from source code
Run `src/PocketMine.php` using your preferred PHP binary.
