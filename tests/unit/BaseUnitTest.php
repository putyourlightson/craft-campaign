<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit;

use Codeception\Test\Unit;
use putyourlightson\campaign\Campaign;
use UnitTester;
use yii\mail\MessageInterface;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class BaseUnitTest extends Unit
{
    // Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var MessageInterface
     */
    protected $message;

    // Protected methods
    // =========================================================================

    /**
     * Set up the class properties before running all tests
     */
    protected function _before()
    {
        parent::_before();

        // Mock the mailer
        $this->tester->mockMethods(
            Campaign::$plugin,
            'mailer',
            [
                'send' => function(MessageInterface $message) {
                    if ($message->getSubject() == 'Fail') {
                        return false;
                    }

                    $this->message = $message;

                    return true;
                },
            ]
        );
    }
}
