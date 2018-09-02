# Campaigns

Campaigns, just like entries, have their own custom field layout (limited to a single tab), determined by the campaign type they belong to. They each have their own URL and work with live preview. A campaign can be sent to one or more mailing lists by creating and assigning it to a sendout.

### Campaign Types
Before you can create a campaign, you must create at least one campaign type. Each campaign type lets you define a custom field layout a well as the following settings.

**Campaign URI Format**  
What the campaign URIs should look like. You can include tags that output campaign properties.
    newsletter/{slug}

**HTML Template**  
The HTML template to use when a campaign’s URL is requested, located in the main templates folder.
    _newsletter/html

**Plaintext Template**  
The plaintext template to use when sending a plaintext version, located in the main templates folder.
    _newsletter/plaintext

To create a new campaign type, go to Settings → Campaign Types and click the “New campaign type” button.

### Campaign Template Variables
You have access to the following variables in your campaign templates.

**`campaign`**  
The current campaign.

**`contact`**  
The contact that received the campaign. This will be `null` if there is no contact, so you should always test for it in your templates before outputting properties.

**`sendout`**  
The sendout that sent the campaign. This will be `null` if there is no sendout, so you should always test for it in your templates before outputting properties.

**`browserVersionUrl`**  
The URL to the browser version of the campaign.

**`unsubscribeUrl`**  
The URL to unsubscribe from the mailing list that the campaign was sent to. 

    <h1>{{ campaign.title }}</h1>

    {% if contact %}
        <p>Hello {{ contact.name }}</p>
    {% endif %}

    {{ campaign.body }}

    <a href="{{ browserVersionUrl }}">View this email in your browser</a>

    {% if unsubscribeUrl %}
        <a href="{{ unsubscribeUrl }}">Unsubscribe here</a>
    {% endif %}

### Getting Campaigns
You can get campaigns from your templates with `craft.campaign.campaigns` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html).

    // Gets all campaigns that are of the specified campaign type
    {% set campaigns = craft.campaign.campaigns.campaignType('newsletter').all() %}
    
    {% for campaign in campaigns %}
       <a href="{{ campaign.url }}">{{ campaign.title }}</a>
    {% endfor %}  

You can get campaigns from your plugin with `CampaignElement::find()` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html). 

    use putyourlightson\campaign\elements\CampaignElement;

    $campaigns = CampaignElement::find()->campaignType('newsletter')->all();

In addition to supporting the parameters that all element types in Craft support (`id`, `title`, etc.), the returned Element Query also supports the following parameters.

**`campaignType`**  
Only fetch campaigns that belong to a given campaign type(s). Accepted values include a campaign type handle, an array of campaign type handles, or a CampaignTypeModel object.

**`campaignTypeId`**  
Only fetch campaigns that belong to a given campaign type(s), referenced by its ID.

### Outputting Campaigns
In addition to having the properties that all element types in Craft have (`id`, `title`, etc.), campaigns also have the following properties and methods.

#### Properties

**`bounced`**  
The number of contacts that have bounced from the campaign.

**`campaignTypeId`**  
The campaign's campaign type ID.

**`campaignType`**  
Alias of getCampaignType().

**`clicked`**  
The number of contacts that have clicked on a link in the campaign.

**`clicks`**  
The number of times that the campaign was clicked.

**`clickThroughRate`**  
Alias of getClickThroughRate().

**`complained`**  
The number of contacts that have complained about the campaign.

**`dateClosed`**  
A DateTime object representing the date the campaign was closed.

**`opened`**  
The number of contacts that have opened the campaign.

**`opens`**  
The number of times that the campaign was opened.

**`recipients`**  
The number of contacts that have received the campaign.

**`status`**  
The status ('sent', 'unsent', 'closed', 'disabled') of the campaign.

**`unsubscribed`**  
The number of contacts that have unsubscribed from the campaign.

#### Methods

**`getCampaignType()`**  
Returns a CampaignTypeModel object representing the campaign's campaign type.

**`getClickThroughRate()`**  
Returns the campaign's click-through rate.

**`getHtmlBody( contact, sendout )`**  
Returns the campaign's HTML body, optionally with a contact and sendout.

**`getPlaintextBody( contact, sendout )`**  
Returns the campaign's plaintext body, optionally with a contact and sendout.