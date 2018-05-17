# Campaign Changelog

## Unreleased
### Added
- Added subscribe and unsubscribe events to tracker service

### Changed
- Refactored template layouts
- Improved contact import queue message

## 1.1.2 - 2018-05-07
### Added
- Added language query parameter to reCAPTCHA

## 1.1.1 - 2018-05-07
### Changed
- Made reCAPTCHA and GeoIP setting fields required when enabled
- Improved reCAPTCHA field labels in errors

## 1.1.0 - 2018-05-07
### Added
- Added reCAPTCHA spam protection to mailing list subscription forms
- Added GeoIP settings for ipstack.com

### Removed
- Removed MLID column and MLID required setting

### Fixed
- Fixed import view template bug
- Fixed mailing list type HTML attribute in mailing list index 

## 1.0.1 - 2018-05-03
### Fixed
- Fixed pending contacts source column

## 1.0.0 - 2018-05-02
- Stable release

### Changed
- Moved import and export pages into contacts navigation item
- Nested import and export user permissions under manage contacts

## 1.0.0-beta12.1 - 2018-05-02
### Added
- Added webhook request log message

### Fixed
- Fixed Mailgun webhook
- Fixed open tracking image

## 1.0.0-beta12 - 2018-05-02
### Added
- Added webhook for Amazon SES complain and bounce notifications

### Changed
- Removed IP address in location field for GDPR compliance
- Changed how source URLs are stored
- Changed order of contact campaign activity
- Improved webhook handling
- Refactored code

### Fixed
- Fixed device icon positioning in reports
- Fixed permissions bug when viewing a campaign when not logged in
- Fixed lost settings when sending test email

## 1.0.0-beta11.1 - 2018-04-30
### Fixed
- Fixed SQL bug when retrieving links report

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