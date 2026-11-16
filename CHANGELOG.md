# ramsey/identifier Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Require PHP 8.3 or later.

## [0.1.0] - 2025-06-19

### Added

- Implement the [Common Interfaces and Factories for Identifiers](https://github.com/php-identifier/identifier) specification.
- Support universally unique identifiers (UUIDs) as defined in [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562).
- Support universally unique lexicographically sortable identifiers (ULIDs) as defined in [ulid/spec](https://github.com/ulid/spec).
- Support [snowflake identifiers](https://en.wikipedia.org/wiki/Snowflake_ID), including popular variants for Twitter, Instagram, Discord, and Mastodon.

[unreleased]: https://github.com/ramsey/identifier/compare/0.1.0...HEAD
[0.1.0]: https://github.com/ramsey/identifier/commits/0.1.0/
