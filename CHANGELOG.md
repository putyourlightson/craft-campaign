# Release Notes for Campaign

## 1.26.0 - 2024-04-17

### Added

- Added one-click unsubscribe headers to sent emails ([#467](https://github.com/putyourlightson/craft-campaign/issues/467)).
- Added a new one-click unsubscribe controller action.
- Added an `addOneClickUnsubscribeHeaders` config setting that determines whether one-click unsubscribe headers should be added to emails, defaulting to `true`.

### Changed

- Campaign now requires Craft CMS 3.5.0 or later.
- Element index caches are now cleared when certain actions take place.

## 1.25.1 - 2024-03-05
### Changed
- Logs are now output to the Craft log, in addition to the Campaign log file.

### Security
- Removed the ability to use `contact` and `mailingList` variables in sendout subjects.

## 1.25.0 - 2023-11-09
### Added
- Added the `sendoutJobPriority` config setting ([#433](https://github.com/putyourlightson/craft-campaign/issues/433)).

### Changed
- Sendouts are now sent to contacts in a deterministic order ([#423](https://github.com/putyourlightson/craft-campaign/issues/423)).

## 1.24.5 - 2023-10-09
### Fixed
- Fixed the status filter for campaigns, mailing lists and contacts selectable in sendouts ([#419](https://github.com/putyourlightson/craft-campaign/issues/419)).

## 1.24.4 - 2023-09-20
### Fixed
- Fixed a bug in which the cached sendout element view was not invalidated after a sendout completed ([#416](https://github.com/putyourlightson/craft-campaign/issues/416)).

## 1.24.3 - 2023-08-21
### Added
- Added the `has a value` and `is empty` options to segment conditions ([#407](https://github.com/putyourlightson/craft-campaign/issues/407)).

## 1.24.2 - 2023-06-01
- Added logging of sendout batches and when a sendout completes ([#385](https://github.com/putyourlightson/craft-campaign/issues/385)).

## 1.24.1 - 2023-01-25
### Fixed
- Fixed a bug where Date and Number fields could be misinterpreted in campaign live preview requests, if the site’s language used different date/number formats than the user’s formatting locale ([#360](https://github.com/putyourlightson/craft-campaign/issues/360)).

## 1.24.0 - 2022-11-18
### Added
- Added the ability to use `{% html %}`, `{% css %}` and `{% js %}` tags in campaign templates.

### Fixed
- Fixed a bug in which Yii block comments could be unintentionaly left over in rendered campaign templates ([#337](https://github.com/putyourlightson/craft-campaign/issues/337)).
- Fixed a bug in which the unsubscribe webhook action could throw an exception ([#339](https://github.com/putyourlightson/craft-campaign/issues/339)).

## 1.23.6 - 2022-11-07
### Changed
- Test requests from Mailgun now return a success response.

### Fixed
- Fixed a bug in which the Mailgun webhook controller action was not processing requests correctly ([#341](https://github.com/putyourlightson/craft-campaign/issues/341)).

## 1.23.5 - 2022-10-12
### Fixed
- Fixed a bug in which blocked contacts were being considered as pending contacts, meaning that sendouts could hang during sending ([#324](https://github.com/putyourlightson/craft-campaign/issues/324)).

## 1.23.4 - 2022-07-18
### Fixed
- Fixed a bug in which an exception could be thrown if the user agent was unavailable when detecting device type.

## 1.23.3 - 2022-07-17
### Changed
- Made the `matomo/device-detector` package requirement more flexible.

## 1.23.2 - 2022-07-10
### Changed
- Tweaked plugin icon to fit better in control panel.

### Fixed
- Fixed subscription status translations.

## 1.23.1 - 2022-05-04
### Changed
- Mailing list slugs must now be unique and are updated automatically when a mailing list is duplicated.

## 1.23.0 - 2022-02-28
### Added
- Added a German translation of the plugin ([#296](https://github.com/putyourlightson/craft-campaign/issues/296) ❤️@andreasbecker).

### Changed
- Improved the French translation.

## 1.22.5 - 2022-02-26
### Changed
- The tracking image now has an empty `alt`  attribute for accessibility ([#298](https://github.com/putyourlightson/craft-campaign/issues/298)).

### Fixed
- Fixed a bug that was throwing an exception on the contact export page when contacts had a multi option field ([#297](https://github.com/putyourlightson/craft-campaign/issues/297)).

## 1.22.4 - 2022-02-23
### Changed
- The `subscribeVerificationSuccessTemplate` is now used even in cases where the subscription verification email link has already been clicked ([#290](https://github.com/putyourlightson/craft-campaign/issues/290)).

### Fixed
- Fixed a bug that was throwing an exception on the segment edit page when contacts had an option field ([#294](https://github.com/putyourlightson/craft-campaign/issues/294)).

## 1.22.3 - 2022-01-20
### Added
- Added a "Subscription Change" event to the Postmark webhook that listens for unsubscribe events ([#286](https://github.com/putyourlightson/craft-campaign/issues/286)).

### Fixed
- Fixed a bug that was preventing the importing of user groups from succeeding ([#289](https://github.com/putyourlightson/craft-campaign/issues/289)).

## 1.22.2 - 2021-12-07
### Fixed
- Fixed a bug that was throwing an error when viewing a campaign or editing a sendout in Craft 3.7.24 ([#284](https://github.com/putyourlightson/craft-campaign/issues/284)).

## 1.22.1 - 2021-11-10
### Fixed
- Fixed a bug that was throwing an error when viewing a sendout ([#281](https://github.com/putyourlightson/craft-campaign/issues/281)).

## 1.22.0 - 2021-11-10
### Added
- Added a French translation of the plugin ([#273](https://github.com/putyourlightson/craft-campaign/issues/273) ❤️@scandella).
- Added a `sendVerificationEmailsViaCraft` config setting that sends verification emails via the Craft mailer, instead of the Campaign mailer.

### Fixed
- Fixed a bug in which the times in the x-axis of the reports chart was not being converted to the local timezone ([#274](https://github.com/putyourlightson/craft-campaign/issues/274)).
- Fixed a bug in which the `getMailingListBySlug` method did not specify the current site ([#278](https://github.com/putyourlightson/craft-campaign/issues/278)).

## 1.21.1 - 2021-10-07
### Changed
- Improved handling of live previewing campaigns across multiple domains.

### Fixed
- Fixed an error that could occur when updating to version 1.21.0 using a Postgres database ([#272](https://github.com/putyourlightson/craft-campaign/issues/272)).

## 1.21.0 - 2021-09-21
### Added
- Added an `allowedOrigins` config setting that enables live previewing campaigns across multiple domains ([#194](https://github.com/putyourlightson/craft-campaign/issues/194), [#251](https://github.com/putyourlightson/craft-campaign/issues/251)).
- Added an `isWebRequest` variable to campaign templates that evaluates to `true` only if the campaign is being viewed via a web request using the browser URL ([#246](https://github.com/putyourlightson/craft-campaign/issues/246)).
- Added a `mailingList` variable that is available in the sendout email subject and campaign template ([#250](https://github.com/putyourlightson/craft-campaign/issues/250)).
- Added the ability to send test emails to multiple contacts at once and to set multiple default test contacts in the campaign type settings ([#256](https://github.com/putyourlightson/craft-campaign/issues/256)).

### Changed
- Unsubscribe links clicked in test emails without a sendout now display an appropriate message ([#212](https://github.com/putyourlightson/craft-campaign/issues/212)).
- The plugin display name is now translatable ([#249](https://github.com/putyourlightson/craft-campaign/issues/249)).
- Spaces are now trimmed from email addresses when importing contacts from a CSV file ([#268](https://github.com/putyourlightson/craft-campaign/issues/268)).

### Fixed
- Fixed a bug in which field handle updates could cause an error on the segments index page ([#242](https://github.com/putyourlightson/craft-campaign/issues/242)).
- Fixed button text that was not being translated on element index pages ([#249](https://github.com/putyourlightson/craft-campaign/issues/249)).
- Fixed a bug in which the Mailgun Webhook Signing Key was not being saved via the plugin settings in the control panel ([#253](https://github.com/putyourlightson/craft-campaign/issues/253)).
- Fixed info dialogs not opening in the contact activity reports after filtering ([#271](https://github.com/putyourlightson/craft-campaign/issues/271)).

## 1.20.2 - 2021-08-05
### Fixed
- Fixed a bug in which segments were not working with fields created in Craft 3.7.0 and above ([#241](https://github.com/putyourlightson/craft-campaign/issues/241)).

## 1.20.1 - 2021-07-27
### Fixed
- Fixed a bug in which the mime type of a CSV file was not recognised in PHP 8.0 when importing ([#240](https://github.com/putyourlightson/craft-campaign/issues/240)).

## 1.20.0 - 2021-07-07
### Added
- Added the ability to view, filter and sort all campaign recipients in reports ([#228](https://github.com/putyourlightson/craft-campaign/issues/228)).
- Added the ability to filter and sort all activity, locations and devices in reports ([#231](https://github.com/putyourlightson/craft-campaign/issues/231)).

### Changed
- Imports now force allow CSV file uploads even if they are excluded from the `allowedFileExtensions` general config setting ([#234](https://github.com/putyourlightson/craft-campaign/issues/234)).
- The latest version of ApexCharts is now loaded from a CDN.

### Fixed
- Fixed inconsistent report data for campaigns that were sent from multiple sendouts ([#232](https://github.com/putyourlightson/craft-campaign/issues/232)).

## 1.19.2 - 2021-04-26
### Changed
- Sendouts are now marked as failed if template rendering errors are encountered ([#225](https://github.com/putyourlightson/craft-campaign/issues/225)).
- Improved positioning of test mode banner.

### Fixed
- Fixed the empty space that appeared next to a contact in a contact field when no avatar was available ([#221](https://github.com/putyourlightson/craft-campaign/issues/221)).

## 1.19.1 - 2021-02-25
### Added
- Added reference tags for campaigns, contacts, mailing lists, segments and sendouts ([#218](https://github.com/putyourlightson/craft-campaign/issues/218)).

## 1.19.0 - 2021-02-24
### Added
- Added a `mailgunWebhookSigningKey` control panel setting that validates incoming webhook requests using Mailgun’s signing key.
- Added a `postmarkAllowedIpAddresses` config setting that only accepts incoming webhook requests from Postmark’s IP addresses.

### Fixed
- Fixed a bug in which mailing lists in non-primary sites were not being synced with users.
- Fixed a bug in which an error was thrown when saving a user and contact first/last name fields did not exist ([#214](https://github.com/putyourlightson/craft-campaign/issues/214)).
- Fixed the day of the month checkbox labels for values over 29.

## 1.18.2 - 2021-02-10
### Fixed
- Fixed a bug in which some contact fields were not being synced with Craft 3.6.

## 1.18.1 - 2021-01-21
### Added
- Added the `campaign/sendouts/queue` console command to queue pending sendouts without actually running them ([#209](https://github.com/putyourlightson/craft-campaign/issues/209)).
- Added the `campaign/sendouts/run` console command to queue and run pending sendouts.

### Changed
- The `@web` alias warning is now only displayed when not explicitly defined ([#208](https://github.com/putyourlightson/craft-campaign/issues/208)).
- Improved formatting of UI elements.
- Improved enforcing of permissions in controllers.

### Deprecated
- Deprecated the `campaign/sendouts/run-pending-sendouts` console command. Use `campaign/sendouts/run` instead.

## 1.18.0 - 2021-01-04
> {tip} Read the updated docs on [Sending to Large Lists](https://putyourlightson.com/plugins/campaign#sending-to-large-lists).

### Added
- Added compatibility with Craft 3.6.0.
- Added the ability to mark a contact as blocked, preventing them from being subscribed to mailing lists ([#140](https://github.com/putyourlightson/craft-campaign/issues/140)).

### Changed
- Optimised the sending process of large sendouts.
- Changed the default max batch size to 10,000 for new installs.
- Removed the `unlimitedMemoryLimit` and `unlimitedTimeLimit` config settings.

### Fixed
- Fixed failed sendout statuses that were being set to pending.
- Fixed element thumbnails not appearing in contact edit page.

## 1.17.7 - 2020-12-23
### Changed
- Improved the contact importing process.

## 1.17.6 - 2020-12-14
### Fixed
- Fixed the contact import functionality added in 1.17.3.

## 1.17.5 - 2020-12-11
### Fixed
- Fixed a bug that was preventing campaign types and mailing list types from being created when installing the plugin via project config ([#191](https://github.com/putyourlightson/craft-campaign/issues/191)).

## 1.17.4 - 2020-12-11
### Fixed
- Fixed a bug in the Amazon SES webhook that was preventing bounce and complain notifications from being received through SNS ([#202](https://github.com/putyourlightson/craft-campaign/issues/202)).

## 1.17.3 - 2020-12-09
### Changed
- Improved the reliability of marking contacts as bounced when using the Mailgun webhook ([#178](https://github.com/putyourlightson/craft-campaign/issues/178)).
- Importing contact data now stores CSV files in the temporary asset upload location as defined in _Settings → Assets → Settings_ ([#197](https://github.com/putyourlightson/craft-campaign/issues/197)).

## 1.17.2 - 2020-11-24
### Changed
- Added compatibility with Craft 3.6.0.

## 1.17.1 - 2020-11-24
### Fixed
- Fixed a bug that prevented the site ID of contacts being updated when the primary site was changed.

## 1.17.0 - 2020-11-05
### Added
- Added the ability to subscribe contacts to a mailing list when imported, even if previously unsubscribed (disabled by default).

### Changed
- Replaced abandoned package htmlstrip with active package Html2Text.
- Changed test class namespaces to be PSR-4 compliant.

### Fixed
- Fixed a bug in which a sendout could get stuck at 100% if there were failed send attempts ([#170](https://github.com/putyourlightson/craft-campaign/issues/170)).

## 1.16.5 - 2020-10-28
### Changed
- The “Share” button on the Edit Campaign page has been renamed to “View” ([#188](https://github.com/putyourlightson/craft-campaign/issues/188)).
- Optimised queries that display contact activity on reports pages to better respect result limits ([#193](https://github.com/putyourlightson/craft-campaign/issues/193)).
- Plugin settings are deleted from project config on uninstall.

### Fixed
- Fixed an error that could be thrown when updating a contact from a queue job ([#187](https://github.com/putyourlightson/craft-campaign/issues/187)).

## 1.16.4 - 2020-10-18
### Fixed
- Fixed error that could be thrown when using Postgres ([#186](https://github.com/putyourlightson/craft-campaign/issues/186)).

## 1.16.3 - 2020-10-08
### Added
- Added logging of bounce and complain events.

### Fixed
- Fixed a bug in which the mailing lists tab was incorrectly listing contacts on the contact edit page ([#182](https://github.com/putyourlightson/craft-campaign/issues/182)).

## 1.16.2 - 2020-10-07
### Fixed
- Fixed a bug in which links could be invalidly created if using Craft CMS 3.5.12 or above ([#180](https://github.com/putyourlightson/craft-campaign/issues/180)).

## 1.16.1 - 2020-10-07
### Fixed
- Fixed a bug in which campaign and mailing list reports were throwing errors ([#179](https://github.com/putyourlightson/craft-campaign/issues/179)).

## 1.16.0 - 2020-10-06
### Added
- Added a default contact to use for test emails to campaign types ([#166](https://github.com/putyourlightson/craft-campaign/issues/166)).
- Added a contacts tab to the mailing list edit page ([#174](https://github.com/putyourlightson/craft-campaign/issues/174)).

### Changed
- Pending contacts now persist so that verification links remain valid until they are purged ([#169](https://github.com/putyourlightson/craft-campaign/issues/169)).

### Fixed
- Fixed a bug in which the default system from name and email were not parsed when loaded from environment settings ([#176](https://github.com/putyourlightson/craft-campaign/issues/176)).
- Fixed site selector for contacts ([#153](https://github.com/putyourlightson/craft-campaign/issues/153), [#177](https://github.com/putyourlightson/craft-campaign/issues/177)).

## 1.15.7 - 2020-09-11
### Added
- Added a sortable `lastSent` attribute to campaign elements.

### Changed
- Made sendout titles editable from the sendout index page ([#161](https://github.com/putyourlightson/craft-campaign/issues/161)).
- The campaign report now lists campaigns ordered by last sent descending ([#168](https://github.com/putyourlightson/craft-campaign/issues/168)).
- Campaigns and sendouts are now ordered by last sent descending by default.

### Fixed
- Fixed a bug with pending contact verification when a contact already exists.

## 1.15.6 - 2020-08-25
### Changed
- Custom fields on contacts are now validated before being saved ([#160](https://github.com/putyourlightson/craft-campaign/issues/160)).
- Added the ability to edit sendout titles when a sendout is already sent, in the sendout index page ([#161](https://github.com/putyourlightson/craft-campaign/issues/161)).
- Campaign first sent on dates are now output in full date time format.

### Fixed
- Fixed a bug that could throw an error when sending a test email using Craft 3.5.0 ([#164](https://github.com/putyourlightson/craft-campaign/issues/164)).

## 1.15.5 - 2020-07-28
### Changed
- The reCAPTCHA lightswitch settings field is no longer marked as required with an asterisk ([#155](https://github.com/putyourlightson/craft-campaign/issues/155)).

### Fixed
- Fixed the contact element background image in CSS ([#151](https://github.com/putyourlightson/craft-campaign/issues/151)).
- Fixed the delete contact permanently button in the contact edit page ([#154](https://github.com/putyourlightson/craft-campaign/issues/154)).
- Fixed a bug that could throw errors in the CP in Craft 3.5.0 ([#157](https://github.com/putyourlightson/craft-campaign/issues/157)).
- Fixed a bug that could throw an error when saving email settings using Craft 3.5.0 ([#159](https://github.com/putyourlightson/craft-campaign/issues/159)).

## 1.15.4 - 2020-06-18
### Added
- Added the `maxSendFailsAllowed` config setting which defines the failed attempts to send to contacts that are allowed before failing the entire sendout and defaults to 1 ([#138](https://github.com/putyourlightson/craft-campaign/issues/138)).
- Added the English translation file ([#148](https://github.com/putyourlightson/craft-campaign/issues/148)).

### Changed
- Improved log messages of failed sendouts ([#138](https://github.com/putyourlightson/craft-campaign/issues/138)).
- Improved the error message when trying to import a CSV file without selecting a mailing list ([#141](https://github.com/putyourlightson/craft-campaign/issues/141)).

### Fixed
- Fixed a bug that could throw the wrong exception when a pending contact is not found when verifying a subscription ([#149](https://github.com/putyourlightson/craft-campaign/issues/149)).

## 1.15.3 - 2020-05-12
### Fixed
- Fixed a bug in the Amazon SES webhook for bounce and complaint notifications ([#103](https://github.com/putyourlightson/craft-campaign/issues/103)).
- Fixed a bug that was preventing the status from being set to `sent` immediately after sending.

## 1.15.2 - 2020-04-29
### Fixed
- Fixed a bug that was causing an error when creating a new user ([#136](https://github.com/putyourlightson/craft-campaign/issues/136)).

## 1.15.1 - 2020-04-27
### Added
- Added user to the contact edit page and contact to the user edit page if one is found ([#131](https://github.com/putyourlightson/craft-campaign/issues/131)).

### Fixed
- Fixed a bug that prevented the first and last name fields being copied when importing a user group ([#134](https://github.com/putyourlightson/craft-campaign/issues/134)).
- Fixed a bug in the campaign and mailing list report charts ([#135](https://github.com/putyourlightson/craft-campaign/issues/135)).

## 1.15.0 - 2020-04-14
### Added
- Added site selector to campaign and mailing list reports ([#125](https://github.com/putyourlightson/craft-campaign/issues/125)).
- Added the ability to import contacts to multiple lists in one go ([#126](https://github.com/putyourlightson/craft-campaign/issues/126)).
- Added the contact field layout to project config ([#129](https://github.com/putyourlightson/craft-campaign/issues/129)).
- Added `EVENT_BEFORE_SAVE_SETTINGS` and `EVENT_AFTER_SAVE_SETTINGS` events.

### Changed
- Changed database table column length for short UIDs ([#128](https://github.com/putyourlightson/craft-campaign/issues/128)).

### Fixed
- Fixed an error that could appear if a campaign type's or mailing list type's site was deleted.
- Fixed a bug that was causing errors when using Postgres ([#128](https://github.com/putyourlightson/craft-campaign/issues/128)).
- Fixed a bug in which a contact could be unsubscribed from a mailing list other than the one they opted to unsubscribe from when clicking the unsubscribe link ([#132](https://github.com/putyourlightson/craft-campaign/issues/132)).

## 1.14.5 - 2020-04-03
### Fixed
- Fixed an invalid field layout error that could occur in an environment where the contact field layout was missing ([#124](https://github.com/putyourlightson/craft-campaign/issues/124)).

## 1.14.4 - 2020-03-27
### Fixed
- Fixed custom fields not being saved on contact import ([#122](https://github.com/putyourlightson/craft-campaign/issues/122)).

## 1.14.3 - 2020-03-27
### Fixed
- Fixed an error that could occur when editing a contact that did not have a field layout ID ([#121](https://github.com/putyourlightson/craft-campaign/issues/121)).

## 1.14.2 - 2020-03-23
### Fixed
- Fixed bugs that were causing errors when using Postgres ([#117](https://github.com/putyourlightson/craft-campaign/issues/117)).
- Fixed a bug when editing a contact when using Postgres ([#118](https://github.com/putyourlightson/craft-campaign/issues/118)).
- Fixed a bug when editing or creating a new contact when the field layout was empty.
- Fixed a bug when importing a user group when no custom fields exist on users.
- Fixed minimum height of background image on welcome screen.
- Fixed behaviour of contact activity report limits.
- Fixed broken contact activity report links.

## 1.14.1 - 2020-03-18
> {warning} This update contains an important bug fix and should be applied as soon as possible.

### Fixed
- Fixed a bug introduced in 1.14.0 in which contacts unsubscribing from a mailing list was throwing an error ([#116](https://github.com/putyourlightson/craft-campaign/issues/116)).  

## 1.14.0 - 2020-03-16
### Added
- Added a hard delete element action for contacts.

### Changed
- It is now possible to add a new contact with the same email address as a soft-deleted contact.

### Fixed
- Fixed an issue where soft deleted contacts were counting towards mailing list subscription and pending contacts count ([#112](https://github.com/putyourlightson/craft-campaign/issues/112)).
- Fixed subscriptions to mailing lists that exist on a site other than the current site ([#113](https://github.com/putyourlightson/craft-campaign/issues/113)).

## 1.13.3 - 2020-03-03
### Changed
- The `contact` variable is now available in the email subject when sending a sendout test.
- Improved UI of tables in settings.

### Fixed
- Fixed the possibility of the `subscribeSuccessTemplate` mailing list type setting not being saved ([#110](https://github.com/putyourlightson/craft-campaign/issues/110)).
- Fixed an error that could occur if the timezone was not explicitly set on the server.

## 1.13.2 - 2020-01-20
### Added
- Added an `unknown` flag icon when a contact's country is unknown.

### Changed
- Improved UI elements for Craft 3.4.
- Made date created and date updated columns sortable in element index pages.

### Fixed
- Fixed an error that could occur if contact activity included deleted contacts.

## 1.13.1 - 2020-01-08 [CRITICAL]
> {warning} This update contains a critical bug fix and should be applied as soon as possible.

### Fixed
- Fixed a bug introduced in 1.13.0 in which emails could be sent to mailing lists not selected in sendouts.

## 1.13.0 - 2020-01-07
### Added
- Added custom relation field types for campaigns, contacts and mailing lists.

### Changed
- Improved performance of pending recipients query.

## 1.12.4 - 2019-12-11
### Changed
- Made sendout `notificationEmailAddress` field optional.

### Fixed
- Fixed a bug with anonymous actions in  the `WebhookController` ([#99](https://github.com/putyourlightson/craft-campaign/issues/99)).
- Fixed a bug that was preventing updating search index jobs from completing because of an emoji in the sendout subject. ([#101](https://github.com/putyourlightson/craft-campaign/issues/101)).

## 1.12.3 - 2019-12-04
### Fixed
- Fixed an error that could occur if using a deprecated subscribe method.
- Fixed a bug in which the field layout was not being saved for mailing list types ([#97](https://github.com/putyourlightson/craft-campaign/issues/97)).
- Fixed a bug with creating new template segments ([#98](https://github.com/putyourlightson/craft-campaign/issues/98)).

## 1.12.2 - 2019-10-29
### Changed
- Improved how contact interactions are tracked to avoid creating unnecessary search index updates.

### Fixed
- Fixed an error that could occur if mailing list was not selected when syncing a user group.
- Fixed new segment button functionality in modal windows.
- Fixed a bug that could cause automated sendout jobs to fail.

## 1.12.1 - 2019-10-23
### Fixed
- Fixed error that could occur if email settings were not saved since a recent update ([#90](https://github.com/putyourlightson/craft-campaign/issues/90)).
- Fixed validation of required fields when saving an enabled campaign.

## 1.12.0 - 2019-10-21
### Added
- Added campaign and mailing list types to project config.

### Changed
- Changed minimum requirement of Craft to version 3.1.20.

### Fixed
- Fixed the number of expected recipients when there were contacts who were subscribed to multiple mailing lists.

## 1.11.1 - 2019-08-13
### Changed
- Changed reCAPTCHA error message to better reflect reCAPTCHA v3.

### Fixed
- Fixed the campaign live preview button.

## 1.11.0 - 2019-08-08
### Added
- Added compatibility with reCAPTCHA version 3.

### Fixed
- Fixed the reCAPTCHA code that is output when not set to invisible ([#85](https://github.com/putyourlightson/craft-campaign/issues/85)).
- Fixed the reCAPTCHA code that is output when more than one instance of the template tag is used ([#86](https://github.com/putyourlightson/craft-campaign/issues/86)).

### Deprecated
- Deprecated the use of reCAPTCHA version 2.

## 1.10.1 - 2019-08-02
### Fixed
- Fixed bug in which automated and recurring sendouts could be marked as sent when they are still pending.

## 1.10.0 - 2019-07-29
> {note} The Google Analytics lightswitch field has been removed from sendouts. Use the new Query String Parameters field in campaign types instead.

### Added
- Added the `Unsubscribe Verification Email Subject` and `Unsubscribe Verification Email Template` fields to mailing list type settings.
- Added the `FormsController` with the `actionUnsubscribe()` method so that contacts can unsubscribe themselves from a mailing list by submitting their email in a form and clicking a link in a verification email ([#81](https://github.com/putyourlightson/craft-campaign/issues/81)).
- Added the `maxSendAttempts` config setting which defines the maximum number of times to attempt sending a sendout before failing and defaults to 3 ([#82](https://github.com/putyourlightson/craft-campaign/issues/82)).
- Added the `Query String Parameters` field to campaign types and removed the `Google Analytics` lightswitch field from sendouts ([#83](https://github.com/putyourlightson/craft-campaign/issues/83)).
- Added unit tests to the plugin using Craft’s testing framework.

### Changed
- Adjusted positioning of campaign preview and share buttons for Craft 3.2.

### Fixed
- Fixed bug in which email notifications about failed sendouts were not being sent.

### Deprecated
- Deprecated the `Subscribe Verification Success Template` field in mailing list type settings.
- Deprecated `TrackerController::actionSubscribe()`.
- Deprecated `TrackerController::actionUpdateContact()`.
- Deprecated `ContactsService::savePendingContact()`.
- Deprecated `ContactsService::sendVerificationEmail()`.
- Deprecated `ContactsService::verifyPendingContact()`.
- Deprecated `ContactsService::purgeExpiredPendingContacts()`.
- Deprecated `TrackerService::EVENT_BEFORE_SUBSCRIBE_CONTACT`.
- Deprecated `TrackerService::EVENT_AFTER_SUBSCRIBE_CONTACT`.
- Deprecated `TrackerService::EVENT_BEFORE_UPDATE_CONTACT`.
- Deprecated `TrackerService::EVENT_AFTER_UPDATE_CONTACT`.

## 1.9.3 - 2019-07-01
### Fixed
- Fixed bug in which reply to email address was not being correctly saved for sendouts.
- Fixed an issue where the settings page could be reached even if `allowAdminChanges` was set to `false` ([#77](https://github.com/putyourlightson/craft-campaign/issues/77)).

## 1.9.2 - 2019-06-06
### Fixed
- Fixed an issue in which relation fields were not being saved on a contact for mailing list types with with double opt-in enabled ([#75](https://github.com/putyourlightson/craft-campaign/issues/75)).

## 1.9.1 - 2019-05-16
> {warning} Template conditions in segments have been broken out into their own segment type. You will therefore need to manually recreate any segments that used template conditions as new segments.

### Fixed
- Fixed an error that could occur when creating a new mailing list in the control panel.

## 1.9.0 - 2019-05-14
### Added
- Added regular and template segment types.
- Added the ability to use emojis in the sendout subject ;)

### Changed
- Changed segment template conditions to be their own segment type and use inline template code rather than a template file.
- Optimised the sending process.
- Changed memory check to get the total the amount of memory being used by PHP.
- Changed sendout job TTR to be set from a new  `sendoutJobTtr` config setting.

### Fixed
- Fixed elements from incorrect sites appearing in relation fields when creating a new campaign or mailing list.
- Fixed issue with sendouts not displaying the correct table attributes based on the selected sendout type.

## 1.8.2 - 2019-05-03
### Fixed
- Fixed issue that prevented max batch size being saved in sendout settings.
- Fixed memory limit exceeded warnings in sendout settings.

## 1.8.1 - 2019-05-02
### Fixed
- Fixed migration that could fail in some edge cases.

## 1.8.0 - 2019-05-02
### Added
- Added ability to add Reply To email addresses.
- Added sendout settings page with tips and warnings for memory and time limits.
- Added Dropdown, Radio Buttons, Checkboxes and Multi-select field options to segments.
- Added `is not` option to lightswitch fields in segments.

### Changed
- Changed sendout job progress indicator from being a percentage of the entire sendout to the current batch size.

### Fixed
- Fixed check for whether the `@web` alias is used with sites or volumes.

## 1.7.5 - 2019-04-26
### Changed
- First and last names are now synced along with other custom user fields that exist for contacts.
- User actions, import failures and some exceptions are now logged in `storage/logs/campaign.log`.
- Complains and bounces no longer require an `SID` to be sent in webhooks.
- Complains and bounces are now applied to all campaigns and mailing lists that a contact engaged with.

### Fixed
- Fixed pending sendout count not displaying in pending scheduled sendouts.

## 1.7.4 - 2019-04-11
### Changed
- Improved wording of message when sendout fails.
- Changed contacts controller actions to work with requests other than JSON requests.
- Changed webhook controller to work with new Mailgun webhook API and added info text in general settings.

### Fixed
- Fixed user thumbnail image not appearing correctly in meta sidebar.

## 1.7.3 - 2019-03-26
### Added
- Added dropdown, email and URL fieldtypes to segments.
- Added `extraSegmentFieldOperators` config setting for adding fieldtypes to segments.

### Changed
- Replaced deprecated Twig classes.

### Fixed
- Really fixed memory limit and max execution times not being recognised as unlimited in some situations.

## 1.7.2 - 2019-03-25
### Changed
- Improved mailing lists tab explanation when creating new contacts.

### Fixed
- Fixed memory limit and max execution times not being recognised as unlimited in some situations.
- Fixed expected recipients positioning and spinner on pending sendouts.

## 1.7.1 - 2019-03-25
### Added
- Added `unlimitedMemoryLimit` and `unlimitedTimeLimit` config settings.
- Added a phablet icon, since those are a thing.

### Changed
- Contacts that were soft deleted are now restored when verified using an email verification link.
- Number of expected recipients is now calculated in the background on the edit sendout screen to prevent slow page load.
- Settings navigation link is removed from navigation if `allowAdminChanges` is disabled.
- Improved how the `@web` alias is determined to be in the site or asset URLs in the preflight check.
- Changed `Craft::warning` to `Craft::info` when logging user actions.

### Fixed
- Fixed error that could occur when returning tracking image ([#57](https://github.com/putyourlightson/craft-campaign/pull/57)).
- Fixed bug with number of failed recipients not being incremented on failed sendouts.
- Fixed styling of send test email button on email settings page.

## 1.7.0 - 2019-03-07
### Added
- Added LITE and PRO editions that can be purchased from within the plugin store.

## 1.6.8 - 2019-03-04
### Fixed
- Fixed custom field values not being correctly applied when importing a user group into a mailing list ([#53](https://github.com/putyourlightson/craft-campaign/issues/53)).

## 1.6.7 - 2019-02-17
### Changed
- Reverted saving initial settings after plugin is installed.

### Fixed
- Fixed error that could occur when syncing a user group to a mailing list.

## 1.6.6 - 2019-02-11
### Fixed
- Fixed redirect to welcome screen after the plugin is installed.
- Fixed error that could occur when the plugin is uninstalled.
- Fixed unused edition showing in plugin badge.

## 1.6.5 - 2019-02-10
### Changed
- Improved contact syncing using the user ID.
- Contacts are now deleted when a synced user is deleted.

## 1.6.4 - 2019-02-01
### Fixed
- Fixed bug in which trashed campaigns, contacts and mailing lists were not being filtered out of report results.

## 1.6.3 - 2019-01-30
### Fixed
- Fixed broken link for contact import view.
- Fixed added count in case of contact import failure.

## 1.6.2 - 2019-01-29
### Fixed
- Fixed bug that changed the value to `0` when saving segment values.

## 1.6.1 - 2019-01-28
### Fixed
- Fixed bug that could prevent sendouts from being marked as complete when the campaign body contained 4 byte Unicode characters ([#50](https://github.com/putyourlightson/craft-campaign/issues/50)).

## 1.6.0 - 2019-01-23
> {warning} Due to some significant changes in Craft 3.1, the email settings should be checked after updating.

### Added
- Added compatibility with live preview in Craft 3.1.
- Added restore action to deleted elements.
- Added auto suggest template fields to campaign types and mailing list types.
- Added environment variables to API key field.
- Added environment variables to ipstack.com API key field.
- Added environment variables to reCAPTCHA fields.

### Changed
- Minimum requirement of Craft has been changed to version 3.1.0.
- Removed deprecated code.

## 1.5.9 - 2019-01-21
### Fixed
- Fixed error that could occur during updating the plugin when using Craft 3.1.

## 1.5.8 - 2019-01-17
### Added
- Added lightswitch field support to segment condition fields.

### Fixed
- Fixed bug where translations were not being applied in the campaign’s site language ([#47](https://github.com/putyourlightson/craft-campaign/issues/47)).

## 1.5.7 - 2019-01-10
### Fixed
- Fixed error when displaying segments that contain date conditionals in element index.

## 1.5.6 - 2019-01-07
### Fixed
- Fixed bug that marked sendouts as pending even when they were completely sent.

## 1.5.5 - 2019-01-04
### Fixed
- Fixed error that appeared after running pending sendouts console command.

## 1.5.4 - 2019-01-04
### Added
- Added subscribed date to exportable fields.

### Changed
- Changed some text fields to code fields within the plugin settings.
- Improved console command instructions.
- Improved cron job instructions.
- The "Send Test" button is disabled when clicked and re-enabled on completion.

### Fixed
- Fixed a bug that could occur when sending test emails failed.

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
> {warning} This update will delete any currently pending contacts.

### Added
- Added the `maxPendingContacts` config setting.

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
