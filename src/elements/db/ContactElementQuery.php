<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\records\ContactMailingListRecord;
use yii\db\Connection;

/**
 * @method ContactElement[]|array all($db = null)
 * @method ContactElement|array|null one($db = null)
 * @method ContactElement|array|null nth(int $n, Connection $db = null)
 */
class ContactElementQuery extends ElementQuery
{
    /**
     * @var int|null
     */
    public ?int $userId = null;

    /**
     * @var string|null
     */
    public ?string $cid = null;

    /**
     * @var string|null The email address that the resulting contact must have.
     */
    public ?string $email = null;

    /**
     * @var int|null The mailing list ID that the resulting contacts must be in.
     */
    public ?int $mailingListId = null;

    /**
     * @var int|null The segment ID that the resulting contacts must be in.
     */
    public ?int $segmentId = null;

    /**
     * @var mixed When the resulting contacts were last active.
     */
    public mixed $lastActivity = null;

    /**
     * Sets the [[userId]] property.
     */
    public function userId(int $value): static
    {
        $this->userId = $value;

        return $this;
    }

    /**
     * Sets the [[cid]] property.
     */
    public function cid(string $value): static
    {
        $this->cid = $value;

        return $this;
    }

    /**
     * Sets the [[email]] property.
     */
    public function email(string $value): static
    {
        $this->email = $value;

        return $this;
    }

    /**
     * Sets the [[mailingListId]] property.
     */
    public function mailingListId(int $value): static
    {
        $this->mailingListId = $value;

        return $this;
    }

    /**
     * Sets the [[segmentId]] property.
     */
    public function segmentId(int $value): static
    {
        $this->segmentId = $value;

        return $this;
    }

    /**
     * Sets the [[lastActivity]] property.
     */
    public function lastActivity(mixed $value): static
    {
        $this->lastActivity = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('campaign_contacts');

        $this->query->select([
            'campaign_contacts.userId',
            'campaign_contacts.cid',
            'campaign_contacts.email',
            'campaign_contacts.country',
            'campaign_contacts.geoIp',
            'campaign_contacts.device',
            'campaign_contacts.os',
            'campaign_contacts.client',
            'campaign_contacts.lastActivity',
            'campaign_contacts.verified',
            'campaign_contacts.complained',
            'campaign_contacts.bounced',
            'campaign_contacts.blocked',
        ]);

        if ($this->userId) {
            $this->subQuery->andWhere(Db::parseParam('campaign_contacts.userId', $this->userId));
        }

        if ($this->cid) {
            $this->subQuery->andWhere(Db::parseParam('campaign_contacts.cid', $this->cid));
        }

        if ($this->email) {
            $this->subQuery->andWhere(Db::parseParam('campaign_contacts.email', $this->email));
        }

        if ($this->mailingListId) {
            $this->query->addSelect('subscriptionStatus');
            $this->subQuery->innerJoin(ContactMailingListRecord::tableName() . ' campaign_contacts_mailinglists', '[[campaign_contacts.id]] = [[campaign_contacts_mailinglists.contactId]]');
            $this->subQuery->select('campaign_contacts_mailinglists.subscriptionStatus AS subscriptionStatus');
            $this->subQuery->andWhere(Db::parseParam('campaign_contacts_mailinglists.mailingListId', $this->mailingListId));
        } else {
            // Add a dummy subscriptionStatus value to prevent sorted queries from failing
            // https://github.com/putyourlightson/craft-campaign/issues/374
            $this->subQuery->addSelect(['subscriptionStatus' => 'cid']);
        }

        if ($this->segmentId) {
            $segment = Campaign::$plugin->segments->getSegmentById($this->segmentId);

            if ($segment !== null) {
                $this->subQuery->andWhere(['elements.id' => $segment->getContactIds()]);
            }
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            ContactElement::STATUS_ACTIVE => [
                'campaign_contacts.complained' => null,
                'campaign_contacts.bounced' => null,
                'campaign_contacts.blocked' => null,
            ],
            ContactElement::STATUS_COMPLAINED => [
                'not', ['campaign_contacts.complained' => null],
            ],
            ContactElement::STATUS_BOUNCED => [
                'not', ['campaign_contacts.bounced' => null],
            ],
            ContactElement::STATUS_BLOCKED => [
                'not', ['campaign_contacts.blocked' => null],
            ],
            default => parent::statusCondition($status),
        };
    }
}
