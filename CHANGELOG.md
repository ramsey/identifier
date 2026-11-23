# ramsey/identifier Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Breaking:** Add `escapeShellArg()` to `Ramsey\Identifier\Service\Os\Os`. It returns an argument quoted and escaped for a shell command, and an implementation of `Os` must have it. `SystemDce` uses it on Windows.
- **Breaking:** Add `microtime()` and `getMyPid()` to `Ramsey\Identifier\Service\Os\Os`. `microtime()` returns the current time as a float of seconds, and `getMyPid()` returns the identifier of the current process, or `false` when it cannot be read. An implementation of `Os` must have both.
- Add `getSortKey()` to every identifier. It returns a string of bytes that places the identifier in order among other identifiers. A Microsoft GUID's sort key is in RFC 9562 layout, so it differs from the bytes `toBytes()` returns.
- Add `Ramsey\Identifier\Exception\NotConvertible`, which `getDateTime()` throws when an identifier cannot return a date-time.
- Add `Ramsey\Identifier\Service\BytesGenerator\TimeSourceFailure`. `MonotonicBytesGenerator::bytes()` throws it when the current time is after 10889-08-02 05:31:50.655 UTC, when the current time is before 1970-01-01 and no earlier call gave the generator a time to continue from, when the operating system service returns a value that is not a time, and when the timestamp would have to move past the last millisecond.
- Add `Ramsey\Identifier\Service\BytesGenerator\RandomSourceFailure`. `MonotonicBytesGenerator::bytes()` throws it when the bytes generator it was given returns a different number of bytes than it was asked for.
- Add a third constructor parameter to `MonotonicBytesGenerator`, the `Os` service it reads the system time and the process identifier from. It defaults to `PhpOs`.

### Changed

- **Breaking:** Require identifier/identifier `^0.6.1`.
- **Breaking:** Four exceptions are in the namespace of the code that throws them: `Ramsey\Identifier\Service\Nic\MacAddressNotFound`, `Ramsey\Identifier\Service\Os\MissingFunction`, `Ramsey\Identifier\Service\Dce\DceIdentifierNotFound`, and `Ramsey\Identifier\Uuid\CannotDetermineVersion`. Each was in `Ramsey\Identifier\Exception`.
- **Breaking:** `PhpOs` and `Md5Hash` are final, as the other service classes are.
- **Breaking:** `Ramsey\Identifier\Exception\NotComparable` extends `Ramsey\Identifier\Exception\InvalidArgument` in place of `RuntimeException`.
- **Breaking:** `compareTo()` given any `Identifier\Identifier` compares the two sort keys and does not throw.
- **Breaking:** `compareTo()` throws `NotComparable` for `null`, numbers, booleans, objects that are neither identifiers nor `Stringable`, and strings (or `Stringable` objects) not in the identifier's own string form, including raw bytes and bare hexadecimal strings.
- **Breaking:** `equals()` returns `true` only for an identifier of the same kind (UUID, ULID, or snowflake) with the same sort key, or a string in the identifier's own string form with the same value; a UUID and a ULID with the same bytes are not equal.
- **Breaking:** The snowflake classes and factories refuse a string with a leading zero, such as `0123`.
- **Breaking:** `createFromInteger()` on every UUID factory and on the ULID factory refuses a string that is not decimal digits with no sign and no leading zero, such as an empty string, `+1`, `-0`, `0123`, `1.0`, or `1e3`.
- **Breaking:** `toUuid()` on a ULID returns an `UntypedUuid` with the same bytes as the ULID. Call `toTypedUuid()` on it to get a typed UUID, such as `UuidV7` or `NonstandardUuid`.
- **Breaking:** `UntypedUuid` and `NonstandardUuid` read a byte string in RFC 9562 layout, whatever its variant, so the bytes have the version and the value their string form shows. Use `MicrosoftGuid` or `MicrosoftGuidFactory` for bytes in Microsoft GUID byte order; when `toTypedUuid()` returns a `MicrosoftGuid` for bytes of the Microsoft variant, its `toBytes()` is in GUID byte order.
- **Breaking:** A version 3 or 5 UUID created from a `MicrosoftGuid` namespace hashes the namespace by its value in RFC 9562 layout, so it is the same UUID as one created from the namespace's string form; a version 3 or 5 UUID stored from a `MicrosoftGuid` namespace by an earlier release differs.
- **Breaking:** `UlidFactory::createFromUuid()` given a `MicrosoftGuid` returns a ULID with the GUID's value, the same value as its string form; a ULID created from a `MicrosoftGuid` by an earlier release differs.
- **Breaking:** The bytes `MonotonicBytesGenerator` returns have a new layout: 48 bits of milliseconds, 4 bits at zero, a 12-bit fraction of the millisecond, 2 bits at zero, and a 62-bit counter that starts at a random value. A version 7 UUID or a ULID created for a given time has different bytes after its first 48 bits, and version 7 UUIDs and ULIDs sort by creation time to about a microsecond. `getDateTime()` returns the time to the millisecond.
- Require PHP 8.3 or later.
- `Uuid`, `Ulid`, and `Snowflake` now extend `Identifier\StringIdentifier`, `JsonSerializable`, and `Stringable`.
- `getDateTime()` on `MicrosoftGuid` and `UntypedUuid` throws `NotConvertible` for a UUID that has no date-time. It extends `BadMethodCall`, so code that catches `BadMethodCall` is unaffected.
- `getDateTime()` on a version 1, 2, or 6 UUID whose timestamp is not a whole number of microseconds rounds to the nearest microsecond, with a half rounded up, on both sides of the Unix Epoch.
- `PhpOs` checks for `shell_exec()`, `escapeshellcmd()`, `escapeshellarg()`, `file_get_contents()`, `glob()`, and `is_readable()` when the method that needs each one is called, and that method throws `MissingFunction`. Its constructor throws nothing, so `SystemNic`, `SystemDce`, and `UuidV2Factory` can be created on a system that disables one of these functions.
- When a PHP function it needs is not available, `SystemNic` goes on to its next way of finding the address, ending with a random address, and `SystemDce` throws `DceIdentifierNotFound`.
- `toUuidV7()` on a ULID created by `UlidFactory` with its default bytes generator returns the version 7 UUID with the same timestamp, fraction, and counter.
- A `MonotonicBytesGenerator` created with a clock other than `SystemClock` keeps its own timestamp and counter. Generators created with `SystemClock` share one.
- `MonotonicBytesGenerator` asks the bytes generator it was given for 8 bytes for each new counter, for 64 bytes for every 21 times a counter increases, and, when more than 16 bytes are wanted, for the bytes beyond 16.
- `MonotonicBytesGenerator::bytes()` throws `TimeSourceFailure` when the current time is after 10889-08-02 05:31:50.655 UTC, or is before 1970-01-01 and no earlier call gave the generator a time to continue from. `UuidV7Factory::create()` and `UlidFactory::create()` can throw it.

### Fixed

- `createFromInteger()` and `toInteger()` on UUIDs and ULIDs keep the exact value of an integer just above `PHP_INT_MAX`, such as `9223372036854775808`.
- `UuidFactory::createFromInteger()` given an integer greater than 128 bits throws `InvalidArgument` with a message that names the integer.
- Unserializing an identifier whose serialized data has a value of the wrong type, or no value, throws `InvalidArgument` in place of a PHP `TypeError`.
- Identifiers whose bytes look like numbers to PHP compare by their bytes, not as numbers.
- GUIDs sort in the order of their string form.
- An `UntypedUuid` created from bytes of the Microsoft variant is equal to the `MicrosoftGuid` that `toTypedUuid()` returns, and the two have the same string form.
- `UlidFactory::createFromUuid()` given a `MicrosoftGuid` returns a ULID with the GUID's value, so the two compare as equal.
- `UuidV3Factory` and `UuidV5Factory` given a `MicrosoftGuid` namespace create the same UUID as for any other UUID with the same string form.
- A version 1, 2, or 6 UUID, or a snowflake, created from a date-time before the Unix Epoch that has a fraction of a second has that date-time.
- `getDateTime()` on a snowflake whose epoch offset puts the date-time out of range throws `NotConvertible` in place of a PHP error.
- `getDateTime()` on a snowflake returns the right date-time when the date-time is less than one second before the Unix Epoch.
- `GenericSnowflakeFactory::createFromDateTime()` with a negative epoch offset names the factory's epoch and its latest date-time correctly in the exceptions it throws, in place of a PHP error.
- `GenericSnowflakeFactory::createFromDateTime()` throws `InvalidArgument` for a date-time too far from the Unix Epoch to count in milliseconds, in place of a snowflake with a wrong timestamp.
- A snowflake value above the largest unsigned 64-bit integer was accepted when written with a leading zero.
- `MastodonSnowflakeFactory` throws `InvalidArgument` when its sequence returns anything other than an integer or a decimal string that fits in an integer.
- A date-time passed to `UuidV7Factory::create()`, `UuidV7Factory::createFromDateTime()`, or `UlidFactory::createFromDateTime()` does not change the identifiers created after it without one; they have the current time.
- A parent process and a child process create different version 7 UUIDs and ULIDs after a fork.
- A version 7 UUID created without a date-time is always greater than the one created before it in the same process.
- `MonotonicBytesGenerator::bytes()` given a date-time before 1970-01-01 or after 10889-08-02 05:31:50.655 UTC throws `InvalidArgument`, in place of bytes with a wrong timestamp.
- `MonotonicBytesGenerator` refuses a bytes generator that returns the wrong number of bytes, with `RandomSourceFailure`, in place of PHP warnings and wrong bytes.
- `MonotonicBytesGenerator` throws `TimeSourceFailure` when its counter would move the timestamp past 10889-08-02 05:31:50.655 UTC, in place of bytes with a wrong timestamp.
- `MonotonicBytesGenerator` returns the same bytes on big-endian and little-endian machines for the same time and the same random bytes.
- The amounts by which `MonotonicBytesGenerator` increases a counter cannot be worked out from identifiers it created earlier.
- `bytes()` on `FixedBytesGenerator`, `MonotonicBytesGenerator`, and `RandomBytesGenerator` throws `InvalidArgument` for a length less than 1, in place of an empty string, the wrong number of bytes, or a PHP `ValueError`.
- `MonotonicSequence` does not raise a deprecation on PHP 8.6. A call without a state keeps its value on the instance and does not use the cache, so a new sequence does not continue from an earlier sequence's value, and a sequence does not return to its start value when a cache entry expires.
- `MonotonicSequence::next()` throws `SequenceOverflow` only when the step would move the sequence past the limit in the step's own direction, so a rising sequence can start at or near `PHP_INT_MIN`. A step of `PHP_INT_MIN` that would pass the minimum throws `SequenceOverflow`.
- `Rfc4122ClockSequence` compares date-times before the Unix Epoch that have a fraction of a second in the right order, so its sequence rises only when the date-time moves backward.
- `InMemoryCache` evicts the least recently used item when its clock is before the Unix Epoch.

## [0.1.0] - 2025-06-19

### Added

- Implement the [Common Interfaces and Factories for Identifiers](https://github.com/php-identifier/identifier) specification.
- Support universally unique identifiers (UUIDs) as defined in [RFC 9562](https://www.rfc-editor.org/rfc/rfc9562).
- Support universally unique lexicographically sortable identifiers (ULIDs) as defined in [ulid/spec](https://github.com/ulid/spec).
- Support [snowflake identifiers](https://en.wikipedia.org/wiki/Snowflake_ID), including popular variants for Twitter, Instagram, Discord, and Mastodon.

[unreleased]: https://github.com/ramsey/identifier/compare/0.1.0...HEAD
[0.1.0]: https://github.com/ramsey/identifier/commits/0.1.0/
