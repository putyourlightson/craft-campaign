# Using Campaign

To install the plugin, search for "Campaign" in the Craft Plugin Store, or install manually using composer.

    composer require putyourlightson/craft-campaign

### Requirements
The Campaign plugin requires Craft CMS 3.0.1 or later.

### Setting Things Up
1. Turn on "Test Mode" to disable live sending of emails in Campaign → Settings → General Settings.
2. Adjust your email settings in Campaign → Settings → Email Settings.
3. Add any custom fields you would like to contacts in Campaign → Settings → Contact Settings.
4. Enable GeoIP if you would like to geolocate contacts by their IP addresses in Campaign → Settings → GeoIP Settings.
5. Enable reCAPTCHA if you would like to protect mailing list subscription forms from bots in Campaign → Settings → reCAPTCHA Settings.
6. Create at least one campaign type in Campaign → Settings → Campaign Types.
7. Create at least one mailing list type in Campaign → Settings → Mailing List Types.
8. If you plan on using scheduled or automated sendouts then create a cron job on your web server as explained in Campaign → Settings → General Settings.

### Getting Started
1. Create a new campaign in Campaign → Campaigns.
2. Create a new mailing list in Campaign → Mailing Lists.
3. Add contacts to your mailing list by either:
    - Creating contacts in Campaign → Contacts and manually subscribe them in the Mailing Lists tab.
    - Or importing contacts from a CSV file in Campaign → Contacts → Import.
4. Create a new sendout in Campaign → Sendouts.

### Testing
Before sending to large mailing lists it is important to test that your campaign is correctly set up. By enabling on "Test Mode" in Campaign → Settings → General Settings, live sending of emails will be disabled and sendout emails  will instead be saved into local files (in storage/runtime/debug/mail) rather that actually being sent. You can also send a test email on both the campaign as well as the sendout edit page. Email testing services such as [Mailtrap](https://mailtrap.io/) can also be used for testing sendouts.

### Email Templates
The majority of email clients either offer no support at all for CSS and floated elements or are inconsistent in how they display them, so email templates should be built using tables. Since designing, building and testing a reliable email template (that works in all email clients) can be a daunting, time-consuming task, we've collected some resources that provide lots of useful information as well as some links to free tried-and-tested email templates that you can customise to your specific needs.  
[&raquo; More details](https://craftcampaign.com/docs/email-templates)

### Campaigns
Campaigns, just like entries, have their own custom field layout (limited to a single tab), determined by the campaign type they belong to. They each have their own URL and work with live preview. A campaign can be sent to one or more mailing lists by creating and assigning it to a sendout.  
[&raquo; More details](https://craftcampaign.com/docs/campaigns)

### Contacts
Contacts, just like users, have their own custom field layout (limited to a single tab). They can be subscribed to multiple mailing lists and can be segmented using conditions. They can be imported from CSV files and user groups, and exported in CSV format.  
[&raquo; More details](https://craftcampaign.com/docs/contacts)

### Mailing Lists
Mailing lists have their own custom field layout (limited to a single tab), determined by the mailing list type they belong to. They can contain an unlimited amount of subscribed contacts.  
[&raquo; More details](https://craftcampaign.com/docs/mailing-lists)

### Segments
Segments are sets of conditions that filter contacts by specific fields, operators and values. They can contain an unlimited amount of AND and OR conditions, and can be applied to sendouts.  
[&raquo; More details](https://craftcampaign.com/docs/segments)

### Sendouts
Sendouts are how you send campaigns to your mailing lists. Sendouts can be sent immediately, on a scheduled date and time, or at a specific delayed interval after a contact subscribes to a mailing list.  
[&raquo; More details](https://craftcampaign.com/docs/sendouts)