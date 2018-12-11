# Release Notes for Campaign

## 1.5.3 - 2018-12-11
### Added
- Added ability to save campaign and create scheduled sendout ([#33](https://github.com/putyourlightson/craft-campaign/issues/33)).

### Fixed
- Fixed error that could be thrown when editing automated sendouts ([#41](https://github.com/putyourlightson/craft-campaign/issues/41)).
 
## 1.5.2 - 2018-12-05
### Changed
- Optimised sendout methods for improved performance in the control panel and sending ([#39](https://github.com/putyourlightson/craft-campaign/issues/39)).

### Fixed
- Fixed bug that could prevent the correct from name and email from being selected on the sendout edit page ([#38](https://github.com/putyourlightson/craft-campaign/issues/38)).

## 1.5.1 - 2018-11-26
### Changed
- Changed Guzzle client to use default config values.

### Fixed
- Fixed bug that prevented users being assigned to the default user group from being added to a synced mailing list ([#35](https://github.com/putyourlightson/craft-campaign/issues/35)).
- Fixed error that occured when deleting a mailing list that was previously used for an import ([#36](https://github.com/putyourlightson/craft-campaign/issues/36)).

## 1.5.0 - 2018-11-09
### Added
- Added verify email subject field to mailing list type settings page ([#31](https://github.com/putyourlightson/craft-campaign/issues/31)).
- Added `campaign/tracker/update-contact` controller action for allowing contacts to update their details on the front-end ([#19](https://github.com/putyourlightson/craft-campaign/issues/19)).

### Fixed
- Fixed error that could occur when sending a test email from the email settings page ([#30](https://github.com/putyourlightson/craft-campaign/issues/30)).

## 1.4.4 - 2018-11-06
### Fixed
- Fixed bug with campaign types and mailing list types not being assigned a default site ID.

## 1.4.3 - 2018-11-02
### Added
- Added an optional `$campaignId` parameter to the `getContactCampaignActivity` method in the reports service.
- Added an optional `$mailingListId` parameter to the `getContactMailingListActivity` method in the reports service.

## 1.4.2 - 2018-11-02
### Fixed
- Fixed bug with campaign fields not appearing in live preview .
- Fixed missing table to drop on uninstall.
- Fixed migration from beta that didn't check for the existance of columns before adding them.

## 1.4.1 - 2018-10-19
### Fixed
- Fixed error when creating a new mailing list.
- Fixed a bug with site URLs not being respected in sendout emails that were not in the primary site  .

## 1.4.0 - 2018-10-18
### Added
- Added multi-site functionality to campaigns, mailing lists, segments and sendouts.
- Added editable table field for adding from name and email addresses on a per-site basis.
- Added checks to determine whether the live preview and share buttons should be shown on campaign edit page.
- Added PHP binary path to cron job instructions in general settings if it exists.
- Added template tags to mailing list type emails and templates.

### Changed
- Changed minimum version of Craft to 3.0.16 due to addition of `DateTime` parameters to the `parseDateParam` method.
- Changed API key back to being a required field.
- Changed report rates to round percentages up rather than down.
- Moved HTML and plaintext iframes into new tab in sendout view.

### Fixed
- Fixed a bug with campaign and mailing list charts not appearing correctly in reports tab on edit pages.
- Fixed a bug in the first sent date and time in campaign reports .
- Fixed a bug with contact activity in mailing list reports.

> {tip} This version adds multi-site functionality to campaign types, mailing list types, segments and sendouts. All existing elements will be assigned to the primary site by default.

## 1.3.4 - 2018-10-11
### Fixed
- Fixed a bug in which counts and percentages in location and device reports could be incorrectly calculated.

## 1.3.3 - 2018-10-09
### Changed
- Improved how elements are output in exported CSV file.
- Set a `$enableSnaptchaValidation` parameter to `false` in the webhook controller.

### Fixed
- Fixed a bug in which a completed sendout could be marked as pending if complained or bounced contacts existed in the selected mailing lists .
- Fixed a bug in which an exception could be thrown if a logged-in user was not found when trying to import contacts.

## 1.3.2 - 2018-09-28
### Added
- Added warning to general settings and preflight if `@web` alias is used in the base URL of any site or volume.

## 1.3.1 - 2018-09-25
### Changed
- Allowed utility to be used even if API key is not set .

## 1.3.0 - 2018-09-25
### Added
- Added a console command to run pending sendouts in order to avoid server limits being exceeded through web-based controller actions.

## 1.2.8 - 2018-09-15
### Fixed
- Fixed a bug which prevented recurring sendouts to be sent to contacts multiple times even if the setting was enabled.

## 1.2.7 - 2018-09-13
### Changed
- Improved checks for contacts that were sent to.

### Fixed
- Fixed a bug that could cause the sendout job to silently stall if a URL in a campaign was more than 255 characters .

## 1.2.6 - 2018-09-12
### Changed
- Put checks in place to ensure that the same contact cannot receive a recurring sendout more than once on the same day .
- Made the current sendout available in the email template as `sendout`.
- Set the `auto_detect_line_endings` run-time configuration to true before importing from a CSV file to ensure that line endings are recognised when delimited with "\r".

### Fixed
- Fixed a bug that could prevent the campaign report chart from displaying.

## 1.2.5 - 2018-09-11
### Fixed
- Fixed a bug when importing contacts.

## 1.2.4 - 2018-09-11
### Fixed
- Fixed a bug in the accuracy of determining whether a recurring sendout can be sent based on the time of day.

## 1.2.3 - 2018-09-10
### Fixed
- Fixed a bug in the accuracy of determining whether a recurring sendout can be sent based on the last send date.

## 1.2.2 - 2018-09-06
### Added
- Added info text next to Amazon SES webhook in settings .

### Changed
- Changed email header name for SID and improved Amazon SES webhook.

## 1.2.1 - 2018-09-05
### Fixed
- Fixed bug in determining when recurring sendouts are allowed to be sent.

## 1.2.0 - 2018-09-03
### Added
- Added custom template conditions to segments.
- Added recurring sendouts (pro version).
- Added ability to sync contacts with users by syncing mailing lists with user groups (pro version).
- Added info tooltip with available template tags to all template settings .
- Added utility to queue pending sendouts.
- Added sendgrid to webhooks in general settings.
- Added `segmentId` parameter to sendout element query.

### Changed
- Replaced Frappe charts with ApexCharts.
- Improved reliability of pausing and cancelling sendouts when sending has already begun .
- Changed "unsent" campaign status to "pending".
- Updated some potentially long text fields to MEDIUMTEXT.
- Automated sendouts are now only sent to contacts who subscribe after the sendout creation date.
- Added quotes to cron job URL to ensure that query parameters are respected.
- Removed unuseful edit action from sendout element index page.
- Refactored templates and added clearer instructions.

### Fixed
- Fixed bug in Amazon SES webhook controller action.
- Fixed possible inaccurate first send date in campaign report.
- Fixed date picker bug in segment conditions.
- Fixed bug in sendout progress calculation.

_Thank you to [Story Group](https://story.com.au/) for partly funding the features in this version._

## 1.1.9 - 2018-08-21
### Added
- Added "Verify Email Template" and "Verify Success Template" settings to mailing list types.

## 1.1.8 - 2018-07-30
### Fixed
- Fixed mailing list type fields that were not being saved.
- Fixed errors that occurred with PostgreSQL.

## 1.1.7 - 2018-07-25
### Fixed
- Fixed an error that could appear when deleted mailing lists or segments were saved in an existing sendout.

## 1.1.6 - 2018-07-24
### Added
- Added `run` parameter to `queue-pending-sendouts` action.

### Fixed
- Fixed missing status colours in sendouts.

## 1.1.5 - 2018-05-25
### Added
- Added source to contact's mailing list activity report.

### Changed
- Removed duplicate location and device columns.
- Removed timeline from contact report.
- Refactored reports variables.

## 1.1.4 - 2018-05-20
### Fixed
- Fixed bug when importing contacts without any fields defined.

## 1.1.3 - 2018-05-18
### Added
- Added reCAPTCHA invisible mode and extra settings.
- Added subscribe and unsubscribe events to tracker service.

### Changed
- Refactored template layouts.
- Improved contact import queue message.

## 1.1.2 - 2018-05-07
### Added
- Added language query parameter to reCAPTCHA.

## 1.1.1 - 2018-05-07
### Changed
- Made reCAPTCHA and GeoIP setting fields required when enabled.
- Improved reCAPTCHA field labels in errors.

## 1.1.0 - 2018-05-07
### Added
- Added reCAPTCHA spam protection to mailing list subscription forms.
- Added GeoIP settings for ipstack.com.

### Removed
- Removed MLID column and MLID required setting.

### Fixed
- Fixed import view template bug.
- Fixed mailing list type HTML attribute in mailing list index .

## 1.0.1 - 2018-05-03
### Fixed
- Fixed pending contacts source column.

## 1.0.0 - 2018-05-02
- Stable release.

### Changed
- Moved import and export pages into contacts navigation item.
- Nested import and export user permissions under manage contacts.

## 1.0.0-beta12.1 - 2018-05-02
### Added
- Added webhook request log message.

### Fixed
- Fixed Mailgun webhook.
- Fixed open tracking image.

## 1.0.0-beta12 - 2018-05-02
### Added
- Added webhook for Amazon SES complain and bounce notifications.

### Changed
- Removed IP address in location field for GDPR compliance.
- Changed how source URLs are stored.
- Changed order of contact campaign activity.
- Improved webhook handling.
- Refactored code.

### Fixed
- Fixed device icon positioning in reports.
- Fixed permissions bug when viewing a campaign when not logged in.
- Fixed lost settings when sending test email.

## 1.0.0-beta11.1 - 2018-04-30
### Fixed
- Fixed SQL bug when retrieving links report.

## 1.0.0-beta11 - 2018-04-11
> Warning: this update will delete any currently pending contacts.

### Added
- Added maxPendingContacts config setting.

### Changed
- Changed how pending contacts are stored to be non-destructive.

## 1.0.0-beta10 - 2018-04-09
### Added
- Added queuing of automated sendouts based on automated schedule.
- Added "months" to time delay interval options.

### Changed
- Changed how time delay intervals are stored.
- Improved responses to controller actions.

## 1.0.0-beta9 - 2018-04-06
### Added
- Added Campaign Pro features.

## 1.0.0-beta8 - 2018-04-05
### Added
- Added call to queue pending sendouts after CP login.

### Changed
- Removed expectedRecipients column.

## 1.0.0-beta7 - 2018-04-05
### Changed
- Changed Craft version requirement to 3.0.1.
- Changed "Plugin Settings" back to "Settings".
- Improved webhook response messages.

## 1.0.0-beta6 - 2018-04-04
### Changed
- Changed Craft version requirement to 3.0.0.
- Removed check for Craft Client edition .
- Disabled Campaign Pro features.

## 1.0.0-beta5 - 2018-04-04
### Added
- Added ttr and maxRetryAttempts to sendout job.

### Changed
- Changed test emails to require a contact.
- Made classes gender neutral.

### Fixed
- Fixed deprecation error on preflight screen.

## 1.0.0-beta4 - 2018-04-03
### Added
- Added user photo to preflight screen.

### Changed
- Improved how sendouts store recipients.
- Improved warning messages when deleting elements.
- Improved report headings.
- Changed plugin icons to be clearer.
- Changed max power to respect limits in import and export.


## 1.0.0-beta3 - 2018-03-26
### Added
- Added smart batching in sendout jobs (credit to Oliver Stark @ostark for his input on this).
- Added system limits to preflight screen.
- Added config settings.

### Changed
- Changed Craft version requirement to 3.0.0-RC16.
- Changed AJAX request to queue/run only when runQueueAutomatically is true.

### Fixed
- Fixed error handling on preflight screen.

## 1.0.0-beta2 - 2018-03-19
### Added
- Added preflight sendout screen.
- Added more user group permissions.
- Added verified timestamps for GDPR compliance.
- Added purging of pending contacts with "purgePendingUsersDuration" config setting.

### Changed
- Changed "Settings" to "Plugin Settings".
- Moved "actionVerifyEmail" into TrackerController.
- Improved performance of SQL queries in reports.
- Improved accuracy of tracked locations and devices.

### Fixed
- Fixed SQL error when only_full_group_by mode enabled.

## 1.0.0-beta1 - 2018-03-07
- Initial release of public beta.