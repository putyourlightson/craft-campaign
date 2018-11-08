# Mailing Lists

Mailing lists have their own custom field layout (limited to a single tab), determined by the mailing list type they belong to. They can contain an unlimited amount of subscribed contacts.

### Mailing List Types
Before you can create a mailing list, you must create at least one mailing list type. Each mailing list type lets you define a custom field layout a well as the following settings.  

**Double Opt-In**  
Whether the user needs to verify their email address by clicking on a link in an email that will be sent to them (recommended for security).  

**Verify Email Template**  
The template to use for the verification email that is sent to users if double opt-in is enabled (leave blank for default message template). Available template tags: `url`, `mailingList`.

**Verify Success Template**  
The template to use when a user verifies their email address if double opt-in is enabled (leave blank for default message template). Available template tags: `mailingList`.

**Subscribe Success Template**  
The template to use when a user subscribes to a mailing list (leave blank for default message template). Available template tags: `mailingList`.

**Unsubscribe Success Template**  
The template to use when a user unsubscribes from a mailing list (leave blank for default message template). Available template tags: `mailingList`.

To create a new mailing list type, go to Settings → Mailing List Types and click the “New mailing list type” button.

### Mailing List Subscribe Form
You can create a mailing list subscribe form as follows. To avoid spam, we recommend you enable reCAPTCHA in Campaign → Settings → reCAPTCHA Settings or use the excellent [Snaptcha](https://putyourlightson.com/craft-plugins/snaptcha) plugin.

    {% set mailingList = craft.campaign.mailingLists.id(7).one() %}

    <form id="subscribe-form" method="post" action="">
        {{ csrfInput() }}
        <input type="hidden" name="action" value="campaign/t/subscribe" />
        <input type="hidden" name="mailingList" value="{{ mailingList.slug }}" />
        <input type="hidden" name="redirect" value="subscribe-success" />
    
        <h3><label for="email">Email</label></h3>
        <input id="email" type="email" name="email" value="" required />
    
        <h3><label for="name">First Name</label></h3>
        <input id="name" type="text" name="fields[firstName]" value="" required />
    
        <h3><label for="name">Last Name</label></h3>
        <input id="name" type="text" name="fields[lastName]" value="" required />
    
        <h3><label for="name">Other Custom Field</label></h3>
        <input id="name" type="text" name="fields[customFieldName]" value="" />
    
        <!-- Required if reCAPTCHA is enabled in plugin settings -->
        {{ craft.campaign.recaptcha }}
    
        <input type="submit" value="Subscribe" />
    </form>

To submit the form using an AJAX request, you'll need to send a POST request containing the fields. Here's an example using [jQuery.post()](http://api.jquery.com/jquery.post/).

    <script>
        // Get data by serializing form 
        var data = $('#subscribe-form').serialize();
        
        // Post to base URL
        $.post('/', data, function(response) {
            if (response.success) {
                alert('You have successfully subscribed to the mailing list.');
            }
            else {
                alert(response.error);
            }
        });
    </script>

### Getting Mailing Lists
You can get mailing lists from your templates with `craft.campaign.mailingLists` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html).

    // Gets the first mailing list with the specified ID
    {% set mailingList = craft.campaign.mailingLists.id(7).one() %}
    
    {% if mailingList %}
       Subscribe to {{ mailingList.title }}
    {% endif %} 

You can get mailing lists from your plugin with `MailingListElement::find()` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html). 

    use putyourlightson\campaign\elements\MailingListElement;

    $mailingList = MailingListElement::find()->id(7)->one();

In addition to supporting the parameters that all element types in Craft support (`id`, `title`, etc.), the returned Element Query also supports the following parameters.

**`mailingListType`**  
Only fetch mailing lists that belong to a given mailing list type(s). Accepted values include a mailing list type handle, an array of mailing list type handles, or a MailingListTypeModel object.

**`mailingListTypeId`**  
Only fetch mailing lists that belong to a given mailing list type(s), referenced by its ID.

### Outputting Mailing Lists
In addition to having the properties that all element types in Craft have (`id`, `title`, etc.), mailing lists also have the following properties and methods.

#### Properties

**`mailingListTypeId`**  
The mailing list's mailing list type ID.