<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use Craft;
use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use putyourlightson\campaign\elements\SendoutElement;
use yii\db\Connection;
use yii\db\Expression;

/**
 * @method SendoutElement[]|array all($db = null)
 * @method SendoutElement|array|null one($db = null)
 * @method SendoutElement|array|null nth(int $n, Connection $db = null)
 */
class SendoutElementQuery extends ElementQuery
{
    /**
     * @var string|null SID
     */
    public ?string $sid = null;

    /**
     * @var array|string|null The sendout type(s) that the resulting sendouts must have.
     */
    public string|array|null $sendoutType = null;

    /**
     * @var int|null The campaign ID that the resulting sendouts must be to.
     */
    public ?int $campaignId = null;

    /**
     * @var int|null The mailing list ID that the resulting sendouts must contain.
     */
    public ?int $mailingListId = null;

    /**
     * @var int|null The segment ID that the resulting sendouts must contain.
     */
    public ?int $segmentId = null;

    /**
     * Sets the [[sid]] property.
     */
    public function sid(string $value): static
    {
        $this->sid = $value;

        return $this;
    }

    /**
     * Sets the [[sendoutType]] property.
     */
    public function sendoutType(string $value): static
    {
        $this->sendoutType = $value;

        return $this;
    }

    /**
     * Sets the [[campaignId]] property.
     */
    public function campaignId(int $value): static
    {
        $this->campaignId = $value;

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
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('campaign_sendouts');

        $this->query->select([
            'campaign_sendouts.sid',
            'campaign_sendouts.campaignId',
            'campaign_sendouts.senderId',
            'campaign_sendouts.sendoutType',
            'campaign_sendouts.sendStatus',
            'campaign_sendouts.fromName',
            'campaign_sendouts.fromEmail',
            'campaign_sendouts.replyToEmail',
            'campaign_sendouts.subject',
            'campaign_sendouts.notificationContactIds',
            'campaign_sendouts.contactIds',
            'campaign_sendouts.failedContactIds',
            'campaign_sendouts.mailingListIds',
            'campaign_sendouts.excludedMailingListIds',
            'campaign_sendouts.segmentIds',
            'campaign_sendouts.recipients',
            'campaign_sendouts.failures',
            'campaign_sendouts.schedule',
            'campaign_sendouts.htmlBody',
            'campaign_sendouts.plaintextBody',
            'campaign_sendouts.sendDate',
            'campaign_sendouts.lastSent',
        ]);

        if ($this->sid) {
            $this->subQuery->andWhere(Db::parseParam('campaign_sendouts.sid', $this->sid));
        }

        if ($this->sendoutType) {
            $this->subQuery->andWhere(Db::parseParam('campaign_sendouts.sendoutType', $this->sendoutType));
        }

        if ($this->campaignId) {
            $this->subQuery->andWhere(Db::parseParam('campaign_sendouts.campaignId', $this->campaignId));
        }

        if ($this->mailingListId) {
            $this->subQuery->andWhere(['like', 'mailingListIds', '"' . $this->mailingListId . '"']);
        }

        if ($this->segmentId) {
            $this->subQuery->andWhere(['like', 'segmentIds', '"' . $this->segmentId . '"']);
        }

        return parent::beforePrepare();
    }

    /**
     * @inheritdoc
     */
    protected function statusCondition(string $status): mixed
    {
        $statuses = SendoutElement::statuses();

        if (isset($statuses[$status])) {
            return ['campaign_sendouts.sendStatus' => $status];
        }

        return parent::statusCondition($status);
    }
}
