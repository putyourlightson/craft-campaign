<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\services;

use Craft;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaigntests\fixtures\ContactsFixture;
use putyourlightson\campaigntests\unit\BaseUnitTest;

/**
 * @since 1.14.0
 */
class ContactsServiceTest extends BaseUnitTest
{
    public function _fixtures(): array
    {
        return [
            'contacts' => [
                'class' => ContactsFixture::class,
            ],
        ];
    }

    /**
     * @var ContactElement
     */
    protected ContactElement $contact;

    protected function _before()
    {
        parent::_before();

        $this->contact = ContactElement::find()->one();
    }

    public function testSaveContactWithSameEmail()
    {
        $newContact = new ContactElement(['email' => $this->contact->email]);

        $elementsService = Craft::$app->getElements();

        // Assert that a contact with the same email cannot be saved
        $this->assertFalse($elementsService->saveElement($newContact));

        $elementsService->deleteElement($this->contact);

        // Assert that a contact with the same email can be saved if the other contact was soft-deleted
        $this->assertTrue($elementsService->saveElement($newContact));

        // Now delete the contacts so future fixtures won't fail
        $elementsService->deleteElement($this->contact, true);
        $elementsService->deleteElement($newContact, true);
    }
}
