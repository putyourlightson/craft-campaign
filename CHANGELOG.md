# Release Notes for Campaign

## 3.0.0-beta.1 - Unreleased

> {warning} “Legacy” and “Template” segments are no longer available will be deleted in this update. They should be replaced with regular segments
_before_ updating.

### Added

- Added compatibility with Craft 5.0.0.

### Changed

- Changed the default maximum size of sendout batches setting to `100`.
- Changed the default sendout batch job delay setting to `0`.

### Removed

- Removed the “Legacy” and “Template” segment types. Use regular segments instead.
- Removed the `segmentType` property and function from the segment element query.
- Removed the `SegmentHelper` class.
- Removed the `SendoutHelper` class.
- Removed the `Campaign::maxPowerLieutenant` method.
- Removed the `memoryLimit` config setting.
- Removed the `memoryThreshold` config setting.
- Removed the `timeThreshold` config setting.
