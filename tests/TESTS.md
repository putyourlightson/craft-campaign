# Test Specification

This document outlines the test specification for the Campaign plugin.

---

## Feature Tests

### [ContactInteraction](pest/Feature/ContactInteractionTest.php)

_Tests contact interactions with campaigns._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A contact clicking a link in a sendout registers interactions.  

### [Contact](pest/Feature/ContactTest.php)

_Tests properties of contacts._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A contact with the same email address as another contact cannot be saved.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A contact with the same email address as a soft deleted contact can be saved.  

### [Form](pest/Feature/FormTest.php)

_Tests interacting with contacts via forms._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A verify subscribe email is sent to a pending contact on subscribe.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A verify unsubscribe email is sent to a contact on unsubscribe.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Subscribing and then unsubscribing a contact works.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Updating a contact modifies its last activity timestamp.  

### [Import](pest/Feature/ImportTest.php)

_Tests importing contacts into mailing lists._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Importing a new contact creates a subscribed contact.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Importing a new contact with unsubscribed enabled creates an unsubscribed contact.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Importing an already unsubscribed contact results in it remaining unsubscribed.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Importing an already unsubscribed contact with force subscribe enabled results in it becoming subscribed.  

### [PendingContact](pest/Feature/PendingContactTest.php)

_Tests verifying pending contacts._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Verifying a pending contact creates a contact.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Verifying a pending contact for a soft deleted contact restores the contact.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Verifying a pending contact soft deletes the pending contact.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Verifying a soft deleted pending does nothing.  

### [PendingRecipient](pest/Feature/PendingRecipientTest.php)

_Tests calculating the pending recipient count of sendouts._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A sendout’s pending recipient count equals the sum of its mailing list subscribers.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A sendout’s pending recipient count does not include complained subscribers.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A sendout’s pending recipient count does not include bounced subscribers.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A sendout’s pending recipient count does not include blocked subscribers.  

### [Tracking](pest/Feature/TrackingTest.php)

_Tests tracking contact interactions with sendouts._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Sendout opens are tracked on the campaign.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Sendout clicks are tracked on the campaign and link and register an open.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Unsubscribes are tracked on the campaign and update the contact’s mailing list status.  

### [UserSync](pest/Feature/UserSyncTest.php)

_Tests syncing user groups with mailing lists._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) Syncing a user group with a mailing list creates contacts and subscribes them.  

## Interface Tests

### [Webhook](pest/Interface/WebhookTest.php)

_Tests the webhook API endpoints._

![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A signed MailerSend bounce request marks the contact as bounced and returns a success.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) An unsigned MailerSend request returns an error.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A signed Mailgun bounce request marks the contact as bounced and returns a success.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) An unsigned Mailgun request returns an error.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A legacy Mailgun bounce request marks the contact as bounced and returns a success.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A Postmark bounce request with an allowed IP address marks the contact as bounced and returns a success.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A Postmark bounce request with a disallowed IP address returns an error.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) A signed SendGrid bounce request marks the contact as bounced and returns a success.  
![Pass](https://raw.githubusercontent.com/putyourlightson/craft-generate-test-spec/main/icons/pass.svg) An unsigned SendGrid request returns an error.  
