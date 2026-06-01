# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.5.4] - 2026-06-01

### Added

- PHP 8.5 support (`~8.5` added to the `php` requirement).

### Fixed

- PHP 8.4/8.5 compatibility: replaced implicit-nullable parameter declarations
  (`Type $param = null`) with explicit nullable types (`?Type $param = null`)
  across the failure providers, area config, and abstract config classes, removing
  the "Implicitly marking parameter as nullable is deprecated" notices.

### Security

- Widened the `symfony/dom-crawler` requirement from `>=2.7 <5.0` to
  `^5.4.52 || ^6.4.40 || ^7.4.12 || ^8.0.12`. The previous constraint could only
  resolve to versions affected by **CVE-2026-45071** (XXE / local file disclosure
  in `DomCrawler::addXmlContent()` via `validateOnParse = true`), because no fix
  exists for the 2.x–4.x branches. The new constraint pins to the patched releases
  and aligns with the Symfony components shipped by Magento 2.4.x.

## [2.5.3] - 2025-10-06

### Changed

- Updated dependencies and removed leftover `console.log` debug output.

## [2.5.2] - 2025-08-12

### Changed

- `composer.json` maintenance.

## [2.5.1] - 2025-06-24

### Changed

- `composer.json` maintenance.

## [2.5.0] - 2025-06-17

### Changed

- `composer.json` maintenance.

[2.5.4]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.5.3...2.5.4
[2.5.3]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.5.2...2.5.3
[2.5.2]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.5.1...2.5.2
[2.5.1]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.5.0...2.5.1
[2.5.0]: https://github.com/hryvinskyi/magento2-invisible-captcha/compare/2.4.10...2.5.0
