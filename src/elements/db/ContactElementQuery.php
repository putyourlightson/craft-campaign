<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\records\ContactMailingListRecord;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\db\Connection;

/**
 * ContactElementQuery
 *
 * @method ContactElement[]|array all($db = null)
 * @method ContactElement|array|null one($db = null)
 * @method ContactElement|array|null nth(int $n, Connection $db = null)
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class ContactElementQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    // General parameters
    // -------------------------------------------------------------------------

    /**
     * @var string CID
     */
    public $cid;

    /**
     * @var string The email address that the resulting contact must have.
     */
    public $email;

    /**
     * @var int The mailing list ID that the resulting contacts must be in.
     */
    public $mailingListId;

    /**
     * @var int The segment ID that the resulting contacts must be in.
     */
    public $segmentId;

    // Public Methods
    // =========================================================================

    /**
     * Sets the [[cid]] property.
     *
     * @param string $value The property value
     *
     * @return static self reference
     */
    public function cid(string $value)
    {
        $this->cid = $value;

        return $this;
    }

    /**
     * Sets the [[email]] property.
     *
     * @param string $value The property value
     *
     * @return static self reference
     */
    public function email(string $value)
    {
        $this->email = $value;

        return $this;
    }

    /**
     * Sets the [[mailingListId]] property.
     *
     * @param int $value The property value
     *
     * @return static self reference
     */
    public function mailingListId(int $value)
    {
        $this->mailingListId = $value;

        return $this;
    }

    /**
     * Sets the [[segmentId]] property.
     *
     * @param int $value The property value
     *
     * @return static self reference
     */
    public function segmentId(int $value)
    {
        $this->segmentId = $value;

        return $this;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('campaign_contacts');

        $this->query->select([
            'campaign_contacts.cid',
            'campaign_contacts.email',
            'campaign_contacts.pending',
            'campaign_contacts.country',
            'campaign_contacts.geoIp',
            'campaign_contacts.device',
            'campaign_contacts.os',
            'campaign_contacts.client',
            'campaign_contacts.lastActivity',
            'campaign_contacts.complained',
            'campaign_contacts.bounced',
        ]);

        if ($this->cid) {
            $this->subQuery->andWhere(Db::parseParam('campaign_contacts.cid', $this->cid));
        }

        if ($this->email) {
            $this->subQuery->andWhere(Db::parseParam('campaign_contacts.email', $this->email));
        }

        if ($this->mailingListId) {
            $this->query->addSelect('subscriptionStatus');
            $this->subQuery->innerJoin(ContactMailingListRecord::tableName().' campaign_contacts_mailinglists', 'campaign_contacts.id = campaign_contacts_mailinglists.contactId');
            $this->subQuery->select('campaign_contacts_mailinglists.subscriptionStatus AS subscriptionStatus');
            $this->subQuery->andWhere(Db::parseParam('campaign_contacts_mailinglists.mailingListId', $this->mailingListId));
        }

        if ($this->segmentId) {
            $segment = Campaign::$plugin->segments->getSegmentById($this->segmentId);
            $contactIds = $segment->getContactIds();
            $this->subQuery->andWhere(['elements.id' => $contactIds]);
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status)
    {
        switch ($status) {
            case ContactElement::STATUS_ACTIVE:
                return [
                    'campaign_contacts.complained' => null,
                    'campaign_contacts.bounced' => null,
                    'campaign_contacts.pending' => 0,
                ];
            case ContactElement::STATUS_PENDING:
                return [
                    'campaign_contacts.complained' => null,
                    'campaign_contacts.bounced' => null,
                    'campaign_contacts.pending' => 1,
                ];
            case ContactElement::STATUS_COMPLAINED:
                return [
                    'not', ['campaign_contacts.complained' => null],
                ];
            case ContactElement::STATUS_BOUNCED:
                return [
                    'not', ['campaign_contacts.bounced' => null],
                ];
            default:
                return parent::statusCondition($status);
        }
    }
}
