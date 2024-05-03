# Release Notes for Campaign

## 3.1.3 - 2024-05-03

### Fixed

- Fixed a bug in which non-admin users without permissions to edit segments were not seeing content ([#472](https://github.com/putyourlightson/craft-campaign/issues/472)).

## 3.1.2 - 2024-05-02

### Fixed

- Fixed a bug in which the content for elements in non-primary sites was not migrated after upgrading from Campaign 2 ([#470](https://github.com/putyourlightson/craft-campaign/issues/470)).
- Fixed the PHPDoc type for relation field values.

## 3.1.1 - 2024-04-16

### Changed

- Changed the order of fetched mailing lists to be more deterministic.

### Fixed

- Fixed the syntax used in one-click unsubscribe headers.

## 3.1.0 - 2024-04-08

### Added

- Added one-click unsubscribe headers to sent emails ([#467](https://github.com/putyourlightson/craft-campaign/issues/467)).
- Added a new one-click unsubscribe controller action.
- Added an `addOneClickUnsubscribeHeaders` config setting that determines whether one-click unsubscribe headers should be added to emails, defaulting to `true`.

## 3.0.0 - 2024-04-08

> {warning} “Legacy” and “Template” segments are no longer available will be deleted in this update. They should be replaced with regular segments before updating, or they will be lost.

### Added

- Added compatibility with Craft 5.

### Removed

- Removed the “Legacy” and “Template” segment types. Use regular segments instead.
- Removed the `memoryLimit` config setting.
- Removed the `memoryThreshold` config setting.
- Removed the `timeLimit` config setting.
- Removed the `timeThreshold` config setting.
- Removed the `segmentType` property and function from the segment element query.
- Removed the `SegmentHelper` class.
- Removed the `SendoutHelper` class.
- Removed the `Campaign::maxPowerLieutenant` method.
- Removed the `SendoutElement::getPendingRecipients()` method. Use `Campaign::$plugin->sendouts->getPendingRecipients()` instead.
- Removed the `SendoutElement::getPendingRecipientCount()` method. Use `Campaign::$plugin->sendouts->getPendingRecipientCount()` instead.
