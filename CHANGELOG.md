# Release Notes for Campaign

## 3.0.0-beta.3 - 2024-03-27

### Added

- Added a content migration for when upgrading from Craft 4 to 5.

## 3.0.0-beta.2 - 2024-03-26

### Added

- Added batching to import queue jobs.
- Added batching to sync queue jobs.

### Fixed

- Fixed a bug in which the expected recipients count could fail in sendouts with very large numbers of contacts.
- Fixed a bug in which sendout actions were unavailable on the sendout index page.
- Fixed a bug in which newly created contacts were not being indexed for searching if only an email address was the only field added ([#463](https://github.com/putyourlightson/craft-campaign/issues/463)).

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
