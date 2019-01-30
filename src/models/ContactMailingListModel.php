<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * ContactMailingListModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property ContactElement     $contact
 * @property string             $interaction
 * @property array              $location
 * @property MailingListElement $mailingList
 */
class ContactMailingListModel extends BaseModel
{
    // Constants
    // =========================================================================

    const INTERACTIONS = ['subscribed', 'unsubscribed', 'complained', 'bounced'];

    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int Contact ID
     */
    public $contactId;

    /**
     * @var int Mailing list ID
     */
    public $mailingListId;

    /**
     * @var string Subscription status
     */
    public $subscriptionStatus;

    /**
     * @var \DateTime|null Subscribed
     */
    public $subscribed;

    /**
     * @var \DateTime|null Unsubscribed
     */
    public $unsubscribed;

    /**
     * @var \DateTime|null Complained
     */
    public $complained;

    /**
     * @var \DateTime|null Bounced
     */
    public $bounced;

    /**
     * @var \DateTime|null Verified
     */
    public $verified;

    /**
     * @var string Source type
     */
    public $sourceType = '';

    /**
     * @var string Source
     */
    public $source = '';

    /**
     * @var \DateTime
     */
    public $dateUpdated;

    // Public Methods
    // =========================================================================

    /**
     * Returns the contact
     *
     * @return ContactElement"null
     */
    public function getContact()
    {
        return Campaign::$plugin->contacts->getContactById($this->contactId);
    }

    /**
     * Returns the mailing list
     *
     * @return MailingListElement|null
     */
    public function getMailingList()
    {
        return Campaign::$plugin->mailingLists->getMailingListById($this->mailingListId);
    }

    /**
     * Returns the most significant interaction
     *
     * @return string
     */
    public function getInteraction(): string
    {
        $interactions = ['bounced', 'complained', 'unsubscribed', 'subscribed'];

        foreach ($interactions as $interaction) {
            if ($this->{$interaction} !== null) {
                return $interaction;
            }
        }

        return '';
    }
}