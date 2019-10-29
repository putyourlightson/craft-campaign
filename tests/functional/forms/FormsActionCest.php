<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\functional\forms;

use FunctionalTester;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaigntests\fixtures\MailingListsFixture;
use putyourlightson\campaigntests\functional\BaseFunctionalCest;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class FormsActionCest extends BaseFunctionalCest
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $email = 'email@anonynous.com';

    // Fixtures
    // =========================================================================

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'mailingLists' => [
                'class' => MailingListsFixture::class
            ],
        ];
    }

    // Public methods
    // =========================================================================

    public function subscribe(FunctionalTester $I)
    {
        $I->amOnPage('?p=subscribe');
        $I->see('Subscribe');

        $I->submitForm('form', [
            'email' => $this->email,
            'mailingList' => MailingListElement::find()->mailingListType('mailingListType1')->one()->slug,
        ]);

        $I->see('Success');
    }
}
