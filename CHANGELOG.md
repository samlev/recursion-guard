# Release Notes

## v1.3.0 - 2026-09-11

### What's Changed

* Replaced mockery with double by [@samlev](https://github.com/samlev) in https://github.com/samlev/recursion-guard/pull/4

### Breaking changes

* Dropped support for PHP8.2

**Full Changelog**: https://github.com/samlev/recursion-guard/compare/v1.2.0...v1.3.0

## v1.2.0 - 2026-09-08

### What's Changed

* Code consolidation by [@samlev](https://github.com/samlev) in https://github.com/samlev/recursion-guard/pull/3

### Breaking Changes

* `Recursable::$signature`,`Recursable::$hash`, and `Recursable::$callback` are no longer public.
* All properties on `Recursable` are now available to read as methods (e.g. `$recursable->signature()`).
* Removed `ArrayWritesForbidden` and `WithRecursable` traits as they were each only used by a single class.

**Full Changelog**: https://github.com/samlev/recursion-guard/compare/v1.1.0...v1.2.0

## v1.1.0 - 2026-09-07

### What's Changed

* Upgrades and refactors by [@samlev](https://github.com/samlev) in https://github.com/samlev/recursion-guard/pull/2

### Breaking Changes

* `RecursionGuard\Data\Frame::only()` has been moved to `RecursionGuard\Helper\Arr::only()`
* `RecursionGuard\Data\BaseData::defaults()` has been moved to `RecursionGuard\Helper\Obj::defaults()`

You are likely not using either of these methods directly, so it should not affect you.

**Full Changelog**: https://github.com/samlev/recursion-guard/compare/v1.0.0...v1.1.0

## v1.0.0 - 2024-11-18

Cleaned up some minor bugs, and increased test coverage; removed helper functions.

**Full Changelog**: https://github.com/samlev/recursion-guard/compare/v0.2.0...v1.0.0

## v0.2.0 - 2024-09-30

Upgrades to extendability and testability

**Full Changelog**: https://github.com/samlev/recursion-guard/compare/v0.1.1...v0.2.0

## v0.1.1 - 2024-09-28

**Full Changelog**: https://github.com/samlev/recursion-guard/compare/v0.1.0...v0.1.1
