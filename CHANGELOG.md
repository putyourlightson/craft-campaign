# Release Notes for Campaign

## 3.0.0-beta.2 - Unreleased

### Added

- Added batching to import queue jobs.
- Added batching to sync queue jobs.

### Fixed

- Fixed a bug in which the expected recipients count could fail in sendouts with very large numbers of contacts.

### Removed

- Removed the `SendoutElement::getPendingRecipients()` method. Use `Campaign::$plugin->sendouts->getPendingRecipients()` instead.
- Removed the `SendoutElement::getPendingRecipientCount()` method. Use `Campaign::$plugin->sendouts->getPendingRecipients()` instead.

## 3.0.0-beta.1 - 2024-02-19

> {warning} “Legacy” and “Template” segments are no longer available will be deleted in this update. They should be replaced with regular segments
_before_ updating.

### Added

- Added compatibility with Craft 5.0.0.

### Removed

- Removed the “Legacy” and “Template” segment types. Use regular segments instead.
- Removed the `segmentType` property and function from the segment element query.
- Removed the `SegmentHelper` class.
- Removed the `SendoutHelper` class.
- Removed the `Campaign::maxPowerLieutenant` method.
- Removed the `memoryLimit` config setting.
- Removed the `memoryThreshold` config setting.
- Removed the `timeLimit` config setting.
- Removed the `timeThreshold` config setting.
