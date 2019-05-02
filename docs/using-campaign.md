# Using Campaign

To install the plugin, search for "Campaign" in the Craft Plugin Store, or install manually using composer.

    composer require putyourlightson/craft-campaign

### Requirements
The Campaign plugin requires Craft CMS 3.1.0 or later.

### Setting Things Up
1. Turn on "Test Mode" to disable live sending of emails in Campaign → Settings → General Settings.
2. Adjust your email settings in Campaign → Settings → Email Settings.
3. Add any custom fields you would like to contacts in Campaign → Settings → Contact Settings.
4. Enable GeoIP if you would like to geolocate contacts by their IP addresses in Campaign → Settings → GeoIP Settings.
5. Create at least one campaign type in Campaign → Settings → Campaign Types.
6. Create at least one mailing list type in Campaign → Settings → Mailing List Types.
7. Create a cron job on your web server as explained in Campaign → Settings → General Settings.

### Getting Started
1. Create a new campaign in Campaign → Campaigns.
2. Create a new mailing list in Campaign → Mailing Lists.
3. Add contacts to your mailing list using one or more of the following techniques:
    - Create contacts in Campaign → Contacts and manually subscribe them in the Mailing Lists tab.
    - Import contacts from a CSV file in Campaign → Contacts → Import.
    - Import contacts from a user group in Campaign → Contacts → Import.
    - Sync contacts from users by syncing a mailing list with a user group in Campaign → Contacts → Sync (pro feature).
4. Create a new sendout in Campaign → Sendouts.

### Testing
Before sending to large mailing lists it is important to test that your campaign is correctly set up. By enabling on "Test Mode" in Campaign → Settings → General Settings, live sending of emails will be disabled and sendout emails  will instead be saved into local files (in storage/runtime/debug/mail) rather that actually being sent. You can also send a test email on both the campaign as well as the sendout edit page. Email testing services such as [Mailtrap](https://mailtrap.io/) can also be used for testing sendouts.

### Email Delivery
Campaign has its own email settings and can use any email delivery service that Craft supports. Craft natively supports Sendmail, SMTP and Gmail, and there are many plugins freely available which add third-party integrations (see "Mailer Transports" in the plugin store). SMTP can generally be used with most email delivery services, however using an API usually results in better performance, therefore the following plugins are recommended:

1. [Amazon SES](https://github.com/putyourlightson/craft-amazon-ses) by PutYourLightsOn
2. [Mailgun](https://github.com/craftcms/mailgun) by Pixel & Tonic
3. [Mandrill](https://github.com/craftcms/mandrill) by Pixel & Tonic
4. [Postmark](https://github.com/craftcms/postmark) by Pixel & Tonic
5. [SendGrid](https://github.com/putyourlightson/craft-sendgrid) by PutYourLightsOn

### Bounce and Complaint Handling

Campaign includes webhooks to handle bounce and complain notifications for the following services:

1. [Amazon SES](https://aws.amazon.com/ses/)
2. [Mailgun](https://www.mailgun.com/)
3. [Mandrill](https://www.mandrill.com/)
4. [Postmark](https://postmarkapp.com/)
5. [Sendgrid](https://sendgrid.com/)

To set up webhooks, copy the appropriate webhook URL from Campaign → Settings → General Settings and add it to the service you use (view each service's documentation for instructions). 
 
### Multi-Site Functionality
Campaign works with a multi-site set up by allowing campaigns, mailing lists, segments and sendouts to each be assignable to one and only one site. This enables the management of each of the above elements on a site by site basis.

### Email Templates
Email templates are defined in the campaign type's settings page. A HTML as well as a plaintext email template should be provided that exist in the site's `templates` folder. Several template tags are available and email templates should be built in a way that is supported by email clients.     
[More details &raquo;](https://craftcampaign.com/docs/email-templates)

### Campaigns
Campaigns, just like entries, have their own custom field layout (limited to a single tab), determined by the campaign type they belong to. They each have their own URL and work with live preview. A campaign can be sent to one or more mailing lists by creating and assigning it to a sendout.  
[More details &raquo;](https://craftcampaign.com/docs/campaigns)

### Contacts
Contacts, just like users, have their own custom field layout (limited to a single tab). They can be subscribed to multiple mailing lists and can be segmented using conditions. They can be imported from CSV files and user groups, and exported in CSV format.  
[More details &raquo;](https://craftcampaign.com/docs/contacts)

### Mailing Lists
Mailing lists have their own custom field layout (limited to a single tab), determined by the mailing list type they belong to. They can contain an unlimited amount of subscribed contacts.  
[More details &raquo;](https://craftcampaign.com/docs/mailing-lists)

### Segments
Segments are sets of conditions that filter contacts by specific fields, operators and values. They can contain an unlimited amount of AND and OR conditions, and can be applied to sendouts.  
[More details &raquo;](https://craftcampaign.com/docs/segments)

### Sendouts
Sendouts are how you send campaigns to your mailing lists. Sendouts can be sent immediately, on a scheduled date and time, or at a specific delayed interval after a contact subscribes to a mailing list.  
[More details &raquo;](https://craftcampaign.com/docs/sendouts)
