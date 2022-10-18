# Release Notes for Campaign

## 2.1.11 - 2022-10-18
### Fixed
- Fixed a bug in which inconsistencies could occur in campaign reports and added a migration to sync campaign reports ([#232](https://github.com/putyourlightson/craft-campaign/issues/232), [#285](https://github.com/putyourlightson/craft-campaign/issues/285)).

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
