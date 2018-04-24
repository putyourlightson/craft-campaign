# Campaign Changelog

## Unreleased

### Changed
- Refactored code

### Fixed
- Fixed SQL bug when retrieving links report
- Fixed device icon positioning in reports
- Fixed permissions bug when viewing a campaign when not logged in

## 1.0.0-beta11 - 2018-04-11
> Warning: this update will delete any currently pending contacts.

### Added
- Added maxPendingContacts config setting

### Changed
- Changed how pending contacts are stored to be non-destructive

## 1.0.0-beta10 - 2018-04-09
### Added
- Added queuing of automated sendouts based on automated schedule
- Added "months" to time delay interval options

### Changed
- Changed how time delay intervals are stored
- Improved responses to controller actions

## 1.0.0-beta9 - 2018-04-06
### Added
- Added Campaign Pro features

## 1.0.0-beta8 - 2018-04-05
### Added
- Added call to queue pending sendouts after CP login

### Changed
- Removed expectedRecipients column

## 1.0.0-beta7 - 2018-04-05
### Changed
- Changed Craft version requirement to 3.0.1
- Changed "Plugin Settings" back to "Settings"
- Improved webhook response messages

## 1.0.0-beta6 - 2018-04-04
### Changed
- Changed Craft version requirement to 3.0.0
- Removed check for Craft Client edition 
- Disabled Campaign Pro features

## 1.0.0-beta5 - 2018-04-04
### Added
- Added ttr and maxRetryAttempts to sendout job

### Changed
- Changed test emails to require a contact
- Made classes gender neutral

### Fixed
- Fixed deprecation error on preflight screen

## 1.0.0-beta4 - 2018-04-03
### Added
- Added user photo to preflight screen

### Changed
- Improved how sendouts store recipients
- Improved warning messages when deleting elements
- Improved report headings
- Changed plugin icons to be clearer
- Changed max power to respect limits in import and export


## 1.0.0-beta3 - 2018-03-26
### Added
- Added smart batching in sendout jobs (credit to Oliver Stark @ostark for his input on this)
- Added system limits to preflight screen
- Added config settings

### Changed
- Changed Craft version requirement to 3.0.0-RC16
- Changed AJAX request to queue/run only when runQueueAutomatically is true

### Fixed
- Fixed error handling on preflight screen

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