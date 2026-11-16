# ramsey/identifier Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Add `getSortKey()` to every identifier. It returns a string of bytes that places the identifier in order among other identifiers. A Microsoft GUID's sort key is in RFC 9562 layout, so it differs from the bytes `toBytes()` returns.
- Add `Ramsey\Identifier\Exception\NotConvertible`, which `getDateTime()` throws when an identifier cannot return a date-time.

### Changed

- Require PHP 8.3 or later.
- **Breaking:** Require identifier/identifier `^0.6.0`.
- **Breaking:** `Ramsey\Identifier\Exception\NotComparable` extends `Ramsey\Identifier\Exception\InvalidArgument` in place of `RuntimeException`.
- `Uuid`, `Ulid`, and `Snowflake` now extend `Identifier\StringIdentifier`, `JsonSerializable`, and `Stringable`.
- `getDateTime()` on `MicrosoftGuid` and `UntypedUuid` throws `NotConvertible` for a UUID that has no date-time. It extends `BadMethodCall`, so code that catches `BadMethodCall` is unaffected.
- **Breaking:** The snowflake classes and factories refuse a string with a leading zero, such as `0123`.

### Fixed

- `getDateTime()` on a snowflake whose epoch offset puts the date-time out of range throws `NotConvertible` in place of a PHP error.
- `getDateTime()` on a snowflake returned the wrong date-time when the date-time was less than one second before the Unix Epoch.
- A snowflake value above the largest unsigned 64-bit integer was accepted when written with a leading zero.

## [0.1.0] - 2025-06-19

### Added

- Implement the [Common Interfaces and Factories for Identifiers](https://github.com/php-identifier/identifier) specification.
- Support universally unique identifiers (UUIDs) as defined in [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562).
- Support universally unique lexicographically sortable identifiers (ULIDs) as defined in [ulid/spec](https://github.com/ulid/spec).
- Support [snowflake identifiers](https://en.wikipedia.org/wiki/Snowflake_ID), including popular variants for Twitter, Instagram, Discord, and Mastodon.

[unreleased]: https://github.com/ramsey/identifier/compare/0.1.0...HEAD
[0.1.0]: https://github.com/ramsey/identifier/commits/0.1.0/
