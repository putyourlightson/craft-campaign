# Campaign Changelog

## Roadmap
- Review queue job implementation

## Unreleased
### Added
- Added smart batching in sendout jobs (credit to Oliver Stark @ostark for his expert input on this)
- Added system limits to preflight screen
- Added config settings

### Changed
- Changed Craft version requirement to 3.0.0-RC16

### Fixed
- Fixed error handling on preflight screen
- Fixed AJAX request to queue/run to only happen when runQueueAutomatically is true

## 1.0.0-beta2 - 2018-03-19
### Added
- Added preflight sendout screen
- Added more user group permissions
- Added verified timestamps for GDPR compliance
- Added purging of pending contacts with "purgePendingUsersDuration" config setting

### Changed
- Changed "Settings" to "Plugin Settings"
- Moved "actionVerifyEmail" into TrackerController
- Improved performance of SQL queries in reports
- Improved accuracy of tracked locations and devices

### Fixed
- Fixed SQL error when only_full_group_by mode enabled

## 1.0.0-beta1 - 2018-03-07
- Initial release of public beta