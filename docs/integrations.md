# Integrations

You can easily integrate your plugin with Campaign by using any of the available elements in `src/elements` or the service classes in `src/services`.

### Getting/Creating a Contact
Before creating a contact, you should always check if one already exists with the same email address.

    $contact = Campaign::$plugin->contacts->getContactByEmail('name@email.com');

    if ($contact === null) {
        $contact = new ContactElement();
        $contact->email = 'name@email.com';
        $contact->customFieldName = $customFieldValue;
    }

    Craft::$app->getElements()->saveElement($contact)

### Subscribing a Contact to a Mailing List
You can subscribe a contact to a mailing list using the `subscribe` method of the `TrackerService` class.

    $mailingList = MailingListElement::find()->slug($mailingListSlug)->one();

    if ($mailingList === null) {
        throw new NotFoundHttpException(Craft::t('campaign', 'Mailing list not found'));
    }

    $source = Craft::$app->getRequest()->getReferrer();

    Campaign::$plugin->tracker->subscribe($contact, $mailingList, 'web', $source);