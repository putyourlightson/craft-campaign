# Release Notes for Campaign

## 3.8.4 - 2026-06-01

- Mailing list slugs are now prefixed to help prevent conflicts with other element type slugs ([#558](https://github.com/putyourlightson/craft-campaign/issues/558)).
- Fixed a bug in which sendouts would not complete when their associated campaign was deleted mid-send ([#560](https://github.com/putyourlightson/craft-campaign/issues/560)).

## 3.8.3 - 2026-04-03

- Fixed a bug in which an error could occur when using date range conditional rules in sendout schedules ([#550](https://github.com/putyourlightson/craft-campaign/issues/550)).

## 3.8.2 - 2026-03-27

- Fixed a bug in which the number of recipients could be lower than expected when send failures occur ([#547](https://github.com/putyourlightson/craft-campaign/issues/547)).

## 3.8.1 - 2025-12-23

- Fixed a bug in which campaigns could not be closed ([#539](https://github.com/putyourlightson/craft-campaign/issues/539)).

## 3.8.0 - 2025-12-09

- Contacts with permission to edit contacts can now do so without having to have edit permissions for the primary site ([#532](https://github.com/putyourlightson/craft-campaign/issues/532)).
- Fixed a bug in which contacts were being unsubscribed from the oldest mailing list they were subscribed to, regardless of whether it was included in the sendout ([#538](https://github.com/putyourlightson/craft-campaign/issues/538)).
- Fixed a bug in which failed contacts could decrement the total number of sendable emails in sendouts ([#533](https://github.com/putyourlightson/craft-campaign/issues/533)).

## 3.7.2 - 2025-11-25

- Improved how IP addresses are determined for contact activity and webhook requests.
- Fixed a bug in which failed GeoIP lookups could cause errors.

## 3.7.1 - 2025-11-03

- Fixed a bug in which an error could occur when attempting to export contacts without a date subscribed ([#535](https://github.com/putyourlightson/craft-campaign/issues/535)).

## 3.7.0 - 2025-11-01

- Added a `contactInteraction` event to the `CampaignsService` class that is triggered when a contact interacts with a sendout, such as opening an email or clicking a link ([#518](https://github.com/putyourlightson/craft-campaign/issues/518)).
- Added a table of import failures to the contact import page ([#528](https://github.com/putyourlightson/craft-campaign/issues/528)).
- Added a `Date Subscribed` property to the contact element.
- Optimised the queries performed when exporting contacts ([#534](https://github.com/putyourlightson/craft-campaign/issues/534)).
- Fixed a bug in which exporting contacts with one or more date fields could fail.

## 3.6.0 - 2025-07-21

- Added a webhook controller action for [Elastic Email](https://elasticemail.com/).
- Added a permissions check for showing the sync contacts tab.
- Fixed a bug in which contacts could be unsubscribed from previously unsubscribed mailing lists, instead of subscribed mailing lists, when sending to multiple mailing lists ([#523](https://github.com/putyourlightson/craft-campaign/issues/523)).

## 3.5.11 - 2025-04-01

### Fixed

- Fixed a bug in which the scheduling of weekly recurring sendouts could have been miscalculated ([#512](https://github.com/putyourlightson/craft-campaign/issues/512)).

## 3.5.10 - 2025-03-03

### Changed

- Changed the use of the deprecated `|ucfirst` filter to `|capitalize` in all Twig templates.
- Moved the “Edit” button into the correct position on campaign report pages.

## 3.5.9 - 2024-12-31

### Changed

- Changed the field icons for campaigns, contacts and mailing lists ([#509](https://github.com/putyourlightson/craft-campaign/issues/509)).
- The webhook controller no longer logs unknown events as warnings.

## 3.5.8 - 2024-12-09

### Fixed

- Fixed the calculation of the pending recipient count for scheduled sendouts ([#505](https://github.com/putyourlightson/craft-campaign/issues/505)).
- Fixed a bug in which pending sendouts did not have a status colour assigned ([#507](https://github.com/putyourlightson/craft-campaign/issues/507)).

## 3.5.7 - 2024-11-29

### Fixed

- Fixed a bug in which duplicating campaigns was not resetting the new campaign’s stats.

## 3.5.6 - 2024-10-31

### Changed

- The “from names and emails” setting now accepts environment variables ([#503](https://github.com/putyourlightson/craft-campaign/issues/503)).

### Fixed

- Fixed the campaign status label on sendout preview pages.

## 3.5.5 - 2024-10-08

### Fixed

- Fixed a bug in which the “delete draft” menu item could appear twice when editing a draft contact.

## 3.5.4 - 2024-10-08

### Fixed

- Fixed the mailing lists view for draft contacts.

## 3.5.3 - 2024-10-08

### Fixed

- Fixed the calculation of pending recipients to not include drafts.
- Fixed the sendout status colour indicators on sendout preview pages.

## 3.5.2 - 2024-10-04

### Fixed

- Fixed the sendout status label on sendout preview pages.

## 3.5.1 - 2024-09-30

### Changed

- Improved French and German translations ([#499](https://github.com/putyourlightson/craft-campaign/issues/499)).

## 3.5.0 - 2024-09-19

### Added

- Added status colours to the “Status” column in element index pages.

### Changed

- Campaign now requires Craft CMS 5.2.0 or later.
- Improved the status colours of element types.
- Improved the French translation ([#493](https://github.com/putyourlightson/craft-campaign/issues/493)).
- Improved the German translation.
- Renamed the “Draft” sendout status to “Unsent”.

### Fixed

- Fixed a bug in which changing the subscription status of a draft contact multiple times before saving could fail ([#494](https://github.com/putyourlightson/craft-campaign/issues/494)).
- Fixed status icons for draft campaigns and contacts.
- Fixed some styling issues.

## 3.4.3 - 2024-08-12

### Changed

- Contact avatars are no longer fetched from Gravatar. Instead, a user profile photo is used, if one exists, falling back to an SVG with a coloured gradient and initial.

## 3.4.2 - 2024-08-02

### Changed

- IP addresses are now logged in failed webhook requests from Postmark.

## 3.4.1 - 2024-07-15

### Changed

- Improved the number formatting of counts on element index pages.
- Updated the table attributes for all element types.

### Fixed

- Fixed the displayed contact count on segment index pages ([#484](https://github.com/putyourlightson/craft-campaign/issues/484)).
- Fixed the missing draft status icons in sendouts.

## 3.4.0 - 2024-07-04

### Added

- Added the ability to create campaign types without public URLs.

### Changed

- Updated status colours to match those used in the control panel UI.

## 3.3.0 - 2024-07-02

### Added

- Added the ability to segment contacts by campaign activity with a “never opened” operator  ([#482](https://github.com/putyourlightson/craft-campaign/issues/482)).

### Changed

- At most one campaign activity rule can now be added to the contact condition in a segment.

## 3.2.0 - 2024-06-25

### Added

- Added the ability to enforce spam prevention on front-end forms using Cloudflare Turnstile ([#447](https://github.com/putyourlightson/craft-campaign/issues/447)).
- Added the `resave/campaigns`, `resave/contacts` and `resave/mailing-lists` console commands ([#481](https://github.com/putyourlightson/craft-campaign/issues/481)).

## 3.1.4 - 2024-05-06

### Fixed

- Fixed a bug in which contact subscriptions were failing when the referrer URL was longer than 255 characters ([#473](https://github.com/putyourlightson/craft-campaign/issues/473)).

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
