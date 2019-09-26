# Front-End Forms

The following front-end forms are available so that you can allow contacts to take certain actions.
  
To avoid spam, we recommend you enable reCAPTCHA in Campaign → Settings → reCAPTCHA Settings or use the excellent [Snaptcha](https://putyourlightson.com/craft-plugins/snaptcha) plugin.

### Mailing List Subscribe Form
You can create a mailing list subscribe form as follows.

    {% set mailingList = craft.campaign.mailingLists.id(7).one() %}

    <form id="subscribe-form" method="post" action="">
        {{ csrfInput() }}
        <input type="hidden" name="action" value="campaign/forms/subscribe" />
        <input type="hidden" name="mailingList" value="{{ mailingList.slug }}" />
        <input type="hidden" name="redirect" value="{{ 'subscribe-success'|hash }}" />
    
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
        // Get data by serializing the form 
        var data = $('#subscribe-form').serialize();
        
        // Post the data to the current URL
        $.post('', data, function(response) {
            if (response.success) {
                alert('You have successfully subscribed to the mailing list.');
            }
            else {
                alert(response.error);
            }
        });
    </script>
    
### Mailing List Unsubscribe Form

You should always make it possible for contacts to unsubscribe from a mailing list by providing them with an unsubscribe link in campaigns.

You can additionally create a mailing list unsubscribe form as follows. The *Unsubscribe Form Allowed* setting must be enabled in the mailing list type for this to work.

    {% set mailingList = craft.campaign.mailingLists.id(7).one() %}

    <form id="unsubscribe-form" method="post" action="">
        {{ csrfInput() }}
        <input type="hidden" name="action" value="campaign/forms/unsubscribe" />
        <input type="hidden" name="mailingList" value="{{ mailingList.slug }}" />
        <input type="hidden" name="redirect" value="{{ 'unsubscribe-success'|hash }}" />
    
        <h3><label for="email">Email</label></h3>
        <input id="email" type="email" name="email" value="" required />
    
        <!-- Required if reCAPTCHA is enabled in plugin settings -->
        {{ craft.campaign.recaptcha }}
    
        <input type="submit" value="Unsubscribe" />
    </form>

If you have contacts that are synced to users then you can provide them with a way to unsubscribe provided they are logged in. 

    {% set contact = craft.campaign.contacts.userId(currentUser.id).one() %}
    {% set mailingLists = contact.getMailingLists() %}

    {% for mailingList in mailingLists %}
      <form id="unsubscribe-form" method="post" action="">
        {{ csrfInput() }}
        <input type="hidden" name="action" value="campaign/contacts/unsubscribe-mailing-list" />
        <input type="hidden" name="contactId" value="{{ contact.id }}" />
        <input type="hidden" name="mailingListId" value="{{ mailingList.id }}" />
        
        <input type="submit" value="Unsubscribe" />
      </form>
    {% endfor %} 
    
### Contact Update Form

You can create a contact update form as follows. Note that the contact’s `cid` and `uid` are both required in order to authenticate the request.

    {% set contact = craft.campaign.contacts.userId(currentUser.id).one() %}
    
    <form id="update-form" method="post" action="">
        {{ csrfInput() }}
        <input type="hidden" name="action" value="campaign/forms/update-contact" />
        <input type="hidden" name="cid" value="{{ contact.cid }}" />
        <input type="hidden" name="uid" value="{{ contact.uid }}" />
        
        <h3><label for="name">First Name</label></h3>
        <input id="name" type="text" name="fields[firstName]" value="{{ contact.firstName }}" required />
    
        <h3><label for="name">Last Name</label></h3>
        <input id="name" type="text" name="fields[lastName]" value="{{ contact.lastName }}" required />
    
        <h3><label for="name">Other Custom Field</label></h3>
        <input id="name" type="text" name="fields[customFieldName]" value="{{ contact.customFieldName }}" />
    
        <!-- Required if reCAPTCHA is enabled in plugin settings -->
        {{ craft.campaign.recaptcha }}
    
        <input type="submit" value="Update" />
    </form>
