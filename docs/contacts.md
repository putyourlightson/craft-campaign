# Contacts

Contacts, just like users, have their own custom field layout (limited to a single tab). They can be subscribed to multiple mailing lists and can be segmented using conditions. They can be imported from CSV files and user groups, and exported in CSV format.

### Creating Contacts
To create a new contact manually, go to the Contacts page and click the “New contact” button. Once saved, you can then manually subscribe or unsubscribe the contact from mailing lists in the Mailing Lists tab of the contact edit page. 

To **import** contacts in bulk, go to Contacts → Import and select a CSV file or a user group to import from.

To **export** contacts to a CSV file, go to Contacts → Export and select a mailing list and the fields to export.

To **sync** contacts with users, go to Contacts → Sync and select a mailing list and user group to sync.

### Getting Contacts
You can get contacts from your templates with `craft.campaign.contacts` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html).

    // Gets the first contact with the specified email address
    {% set contact = craft.campaign.contacts.email('jim@bean.com').one() %}
    {% if contact %}
       <a href="mailto: {{ contact.email }}">{{ contact.name }}</a>
    {% endif %} 

You can get contacts from your plugin with `ContactElement::find()` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html). 

    use putyourlightson\campaign\elements\ContactElement;

    $contact = ContactElement::find()->email('jim@bean.com')->one();

In addition to supporting the parameters that all element types in Craft support (`id`, `title`, etc.), the returned Element Query also supports the following parameters.

**`cid`**  
Only fetch contacts with the given CID (unique contact ID).

**`email`**  
Only fetch contacts with the given email address.

**`mailingListId`**  
Only fetch contacts with the given mailing list ID.

**`segmentId`**  
Only fetch contacts with the given segment ID.

### Outputting Contacts
In addition to having the properties that all element types in Craft have (`id`, `title`, etc.), contacts also have the following properties and methods.

#### Properties

**`bounced`**  
A DateTime object representing the date that the contact bounced.

**`cid`**  
The contact's CID (unique contact ID).

**`client`**  
The last client (web browser) that was detected for the contact.

**`complained`**  
A DateTime object representing the date that the contact complained.

**`country`**  
The last country that was detected for the contact.

**`device`**  
The last device that was detected for the contact.

**`email`**  
The contact's email address.

**`lastActivity`**  
A DateTime object representing the date that the contact was last active.

**`verified`**  
A DateTime object representing the date that the contact verified their email address (applies for double opt-in only).

**`status`**  
The status ('active', 'complained', 'bounced') of the contact.

**`os`**  
The last OS (operating system) that was detected for the contact.