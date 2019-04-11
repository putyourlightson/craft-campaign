<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\errors\ElementNotFoundException;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;

use Craft;
use craft\base\Component;
use Throwable;
use yii\base\Exception;

/**
 * WebhookService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class WebhookService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Complain
     *
     * @param ContactElement          $contact
     * @param MailingListElement|null $mailingList
     * @param SendoutElement|null     $sendout
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function complain(ContactElement $contact, MailingListElement $mailingList = null, SendoutElement $sendout = null)
    {
        $this->_addInteraction('complained', $contact, $mailingList, $sendout);
    }

    /**
     * Bounce
     *
     * @param ContactElement          $contact
     * @param MailingListElement|null $mailingList
     * @param SendoutElement|null     $sendout
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public function bounce(ContactElement $contact, MailingListElement $mailingList = null, SendoutElement $sendout = null)
    {
        $this->_addInteraction('bounced', $contact, $mailingList, $sendout);
    }

    // Private Methods
    // =========================================================================

    /**
     * Add interaction
     *
     * @param string                  $interaction
     * @param ContactElement          $contact
     * @param MailingListElement|null $mailingList
     * @param SendoutElement|null     $sendout
     *
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    private function _addInteraction(string $interaction, ContactElement $contact, MailingListElement $mailingList = null, SendoutElement $sendout = null)
    {
        if ($mailingList !== null) {
            // Add contact interaction to mailing list
            Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, $interaction);
        }

        if ($sendout !== null) {
            // Add contact interaction to campaign
            Campaign::$plugin->campaigns->addContactInteraction($contact, $sendout, $interaction);
        }

        // Update contact
        if ($contact->{$interaction} === null) {
            $contact->{$interaction} = new DateTime();
            Craft::$app->getElements()->saveElement($contact);
        }
    }
}
