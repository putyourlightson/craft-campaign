# Release Notes for Campaign

## 2.5.0 - 2023-01-17
### Added
- Added the ability to hide the title field in campaigns and have titles generated dynamically ([#355](https://github.com/putyourlightson/craft-campaign/issues/355)).
- Added the ability to hide the title field in sendouts and have titles generated from the subject ([#356](https://github.com/putyourlightson/craft-campaign/issues/356)).

### Changed
- The sendout title and subject fields are now autopopulated from the campaign title if they are empty ([#356](https://github.com/putyourlightson/craft-campaign/issues/356)).

## 2.4.2 - 2023-01-13
### Fixed
- Fixed a bug in which some stats in the mailing list dashboard widget were not being counted.

## 2.4.1 - 2023-01-13
### Fixed
- Fixed a bug in which the utilities page was throwing an exception ([#357](https://github.com/putyourlightson/craft-campaign/issues/357)).

## 2.4.0 - 2023-01-12
### Added
- Added the “Campaign Stats” dashboard widget ([#107](https://github.com/putyourlightson/craft-campaign/issues/107)).
- Added the “Mailing List Stats” dashboard widget ([#107](https://github.com/putyourlightson/craft-campaign/issues/107)).
- Added “Click Rate” as an available column in the campaign element index page.
- Added “Open Rate” to campaign reports and as an available column in the campaign element index page ([#354](https://github.com/putyourlightson/craft-campaign/issues/354)).
- Added “Click Rate” as an available column in the campaign element index page.
- Added the `campaign/reports/sync` console command that syncs campaign reports.

### Fixed
- Fixed when the view action is available on campaign index pages.

## 2.3.2 - 2023-01-02
### Changed
- Changed the sendout “Pause” button to read “Pause and Edit” ([#352](https://github.com/putyourlightson/craft-campaign/issues/352)).

### Fixed
- Fixed the “View all” link in the contacts tab of the mailing list edit page for Craft 4.3.0 and above.  

## 2.3.1 - 2022-12-10
### Changed
- The access utility permission was removed, in favour of Craft's own utility permission.

### Fixed
- Fixed a bug in which an error could be thrown on the imports page if imported mailing lists no longer existed ([#349](https://github.com/putyourlightson/craft-campaign/issues/349)).
- Fixed a bug in which errors could be thrown on reports pages if elements no longer existed ([#349](https://github.com/putyourlightson/craft-campaign/issues/349)).
- Fixed a bug in which sendout jobs could fail when run via console requests.

## 2.3.0 - 2022-12-05
### Added
- Added a new `ContactElement::getIsSubscribedTo()` method.

### Changed
- Rendered HTML templates now explicitly exclude any control panel asset bundles ([#347](https://github.com/putyourlightson/craft-campaign/issues/347)).

### Fixed
- Fixed a bug in which draft contacts were incorrectly being counted as expected recipients.
- Fixed a bug in which custom fields were not being validated in front-end contact subscribe forms ([#348](https://github.com/putyourlightson/craft-campaign/issues/348)).

## 2.2.3 - 2022-11-25
### Changed
- Contact imports now attempt to JSON decode imported values for relation fields ([#345](https://github.com/putyourlightson/craft-campaign/issues/345)).
- Search indexes are now updated only after contacts have finished being imported, rather that than once per contact ([#345](https://github.com/putyourlightson/craft-campaign/issues/345)).

### Fixed
- Fixed the updated column in the import index view.

## 2.2.2 - 2022-11-22
### Changed
- Contacts can now be subscribed to and unsubscribed from mailing lists when in a draft state ([#343](https://github.com/putyourlightson/craft-campaign/issues/343)).
- The email field now outputs a link to a contact if one already exists with the same email address during validation ([#343](https://github.com/putyourlightson/craft-campaign/issues/343)).

## 2.2.1 - 2022-11-07
### Changed
- Improved the performance of report pages ([#340](https://github.com/putyourlightson/craft-campaign/issues/340)).
- Changed the webhook controller action responses to ensure that correct status codes are sent (❤️@brandonkelly).
- Test requests from Mailgun now return a success response.

### Fixed
- Fixed a bug in which the Mailgun webhook controller action was not processing requests correctly ([#341](https://github.com/putyourlightson/craft-campaign/issues/341)).
- Fixed a bug in which the webhook controller actions could fail for singular sendouts.
- Fixed a bug in which some information was missing from reports.

## 2.2.0 - 2022-10-28
### Added
- Added the ability to use `{% html %}`, `{% css %}` and `{% js %}` tags in campaign templates.

### Fixed
- Fixed a bug in which Yii block comments could be unintentionally left over in rendered campaign templates ([#337](https://github.com/putyourlightson/craft-campaign/issues/337)).

## 2.1.17 - 2022-10-28
### Fixed
- Fixed a bug in which the unsubscribe webhook action could throw an exception ([#339](https://github.com/putyourlightson/craft-campaign/issues/339)).

## 2.1.16 - 2022-10-27
### Fixed
- Fixed all remaining uninitialized typed properties, as a precaution.

## 2.1.15 - 2022-10-27
### Fixed
- Fixed a missed uninitialized typed property that was causing verification links to fail ([#338](https://github.com/putyourlightson/craft-campaign/issues/338)).

## 2.1.14 - 2022-10-25
### Fixed
- Fixed a bug in which typed properties were being accessed before initialization, caused by a [breaking change](https://github.com/yiisoft/yii2/issues/19546#issuecomment-1291280606) in Yii 2.0.46.

## 2.1.13 - 2022-10-25
### Fixed
- Fixed a bug in which typed properties were being accessed before initialization.

## 2.1.12 - 2022-10-21
### Fixed
- Fixed a bug in which an exception was thrown when viewing recurring sendouts ([#336](https://github.com/putyourlightson/craft-campaign/issues/336)).

## 2.1.11 - 2022-10-18
### Fixed
- Fixed a bug in which inconsistencies could occur in campaign reports and added a migration to sync campaign report data ([#232](https://github.com/putyourlightson/craft-campaign/issues/232), [#285](https://github.com/putyourlightson/craft-campaign/issues/285)).

## 2.1.10 - 2022-10-17
### Fixed
- Fixed a bug in which saving sendout settings was throwing an exception when one or more of the fields were left blank ([#334](https://github.com/putyourlightson/craft-campaign/issues/334)).

## 2.1.9 - 2022-10-12
### Fixed
- Fixed a bug in which blocked contacts were being considered as pending contacts, meaning that sendouts could hang during sending ([#324](https://github.com/putyourlightson/craft-campaign/issues/324)).

## 2.1.8 - 2022-10-10
### Fixed
- Fixed a caching issue when applying project config changes to campaign types and mailing list types ([#332](https://github.com/putyourlightson/craft-campaign/issues/332)).

## 2.1.7 - 2022-09-16
### Fixed
- Fixed a bug in which the edit action was available for sendouts that were no longer modifiable ([#328](https://github.com/putyourlightson/craft-campaign/issues/328)).
- Fixed a bug in which an exception was thrown when previewing a sendout in which the campaign no longer exists ([#329](https://github.com/putyourlightson/craft-campaign/issues/329)).

## 2.1.6 - 2022-09-16
### Fixed
- Fixed a bug in which sending test emails of campaigns only worked for campaigns in the default site ([#327](https://github.com/putyourlightson/craft-campaign/issues/327)).

## 2.1.5 - 2022-08-28
### Fixed
- Fixed an error that could be thrown if the `set_time_limit()` function was undefined ([#322](https://github.com/putyourlightson/craft-campaign/issues/322)).

## 2.1.4 - 2022-08-07
### Fixed
- Fixed a bug that prevented the mailer transport defined in the Campaign email settings from overriding the Craft email settings ([#319](https://github.com/putyourlightson/craft-campaign/issues/319)).

## 2.1.3 - 2022-08-02
### Fixed
- Fixed an error that occurred when creating contacts in the control panel.

## 2.1.2 - 2022-07-25
### Fixed
- Fixed a bug when subscribing users via front-end forms.

## 2.1.1 - 2022-07-22
### Added
- Added a `fieldValues` parameter to the `FormsService::createAndSubscribeContact` method.

## 2.1.0 - 2022-07-22
### Added
- Added a `createAndSubscribeContact` method to `FormsService` for easier integration from other plugins and modules.

## 2.0.6 - 2022-07-18
### Fixed
- Fixed a bug in which an exception could be thrown if the user agent was unavailable when detecting device type.

## 2.0.5 - 2022-07-05
### Changed
- Tweaked plugin icon to fit better in control panel.

### Fixed
- Fixed subscription status translations.

## 2.0.4 - 2022-06-24
### Changed
- Removed the pruning of deleted fields according to the precedent set in [craftcms/cms#11054](https://github.com/craftcms/cms/discussions/11054#discussioncomment-2881106).

### Fixed
- Fixed an issue with viewing sendouts that have been sent in Craft 4.0.4 and above ([#316](https://github.com/putyourlightson/craft-campaign/issues/316)).

## 2.0.3 - 2022-06-21
### Fixed
- Fixed an issue with the contact index page when database tables contained a prefix.

## 2.0.2 - 2022-06-21
### Fixed
- Fixed the unique email validation when saving contacts.

## 2.0.1 - 2022-06-03
### Changed
- Improved the UI of sendout previews.
- Made the `matomo/device-detector` package requirement more flexible.

## 2.0.0 - 2022-05-04
> {warning} Support for reCAPTCHA version 2 has been removed, use version 3 instead. The `subscribeVerificationSuccessTemplate` setting has been removed, use the `subscribeSuccessTemplate` setting instead.

### Added
- Added compatibility with Craft 4.
- Added a new "Singular" sendout type to the Pro edition, for sending campaigns to individual contacts ([#263](https://github.com/putyourlightson/craft-campaign/issues/263)).
- Added a condition builder field to the sendout schedule for automated and recurring sendout types ([#305](https://github.com/putyourlightson/craft-campaign/issues/305)).
- Added the field layout designer to campaign types, mailing list types and contact layouts ([#163](https://github.com/putyourlightson/craft-campaign/issues/163), [#198](https://github.com/putyourlightson/craft-campaign/issues/198), [#269](https://github.com/putyourlightson/craft-campaign/issues/269)).
- Added autosaving drafts to campaigns, contacts, mailing lists, segments and sendouts.
- Added revisions to campaigns ([#301](https://github.com/putyourlightson/craft-campaign/issues/301)).
- Added a "Duplicate" action to campaigns, mailing lists, segments and sendouts ([#292](https://github.com/putyourlightson/craft-campaign/issues/292)).
- Added condition settings to the campaigns, contacts and mailing lists relation fields.
- Added user group permissions for campaign types and mailing list types.
- Added the ability to view disabled campaigns using a token URL.
- Added a contact condition builder to regular segment types, that should be used going forward since `legacy` and `template` segment types will be removed in Campaign 3.
- Added a "Campaign Activity" condition rule for segmenting by contacts who have opened or clicked a link in any or a specific campaign ([#244](https://github.com/putyourlightson/craft-campaign/issues/244)).
- Added a "Default Notification Contacts" field to sendout settings. 
- Added an "Export to CSV" button to all datatables in reports ([#245](https://github.com/putyourlightson/craft-campaign/issues/245)).
- Added the `enableAnonymousTracking` setting, which prevents tracking of contact interactions ([#115](https://github.com/putyourlightson/craft-campaign/issues/115)).
- Added the `campaign/reports/anonymize` console controller that anonymizes all previously collected personal data.
- Added a list of failed contacts to sendouts that have failures ([#311](https://github.com/putyourlightson/craft-campaign/issues/311)).
- Added a link to view all contacts from the mailing list edit page ([#282](https://github.com/putyourlightson/craft-campaign/issues/282)).

### Changed
- All `forms` controller actions now return a `400` HTTP status for JSON responses when unsuccessful.
- Improved the UI and security of links to external sites.
- Exports now include all contacts in the selected mailing lists, as well as columns for mailing list, subscription status and subscribed date ([#302](https://github.com/putyourlightson/craft-campaign/issues/302)).
- Verification emails are now sent in HTML and plaintext format ([#303](https://github.com/putyourlightson/craft-campaign/issues/303)).
- Renamed `regular` segment types to `legacy` segment types, which are being maintained because they provide functionality that the contact condition builder does not yet provide, but which will be removed in Campaign 3.
- Renamed the `maxSendFailsAllowed` config setting to `maxSendFailuresAllowed`.
- Replaced the `Log To File` helper package with a custom Monolog log target.
- Replaced all instances of `AdminTable` with `VueAdminTable`.
- Removed the `SettingsService` class. Use the `SettingsHelper` class instead.

### Removed
- Removed support for reCAPTCHA version 2, leaving support for version 3 only.
- Removed the `subscribeVerificationSuccessTemplate` setting from the mailing list type settings page. Use the `subscribeSuccessTemplate` setting instead.
