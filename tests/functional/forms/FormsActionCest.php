<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\tests\functional\forms;

use FunctionalTester;
use putyourlightson\campaign\tests\functional\BaseFunctionalCest;

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

    // Public methods
    // =========================================================================

    public function subscribe(FunctionalTester $I)
    {
        $I->amOnPage('?p=subscribe');
        $I->see('Subscribe');

        $I->submitForm('form', [
            'email' => $this->email,
            'mailingList' => $this->mailingList->slug,
        ]);

        $I->see('Success');
    }
}
