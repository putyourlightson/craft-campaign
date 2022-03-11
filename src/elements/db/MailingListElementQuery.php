<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use craft\db\Table;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\records\MailingListTypeRecord;


use craft\elements\db\ElementQuery;
use craft\helpers\Db;
use yii\db\Connection;

/**
 * @method MailingListElement[]|array all($db = null)
 * @method MailingListElement|array|null one($db = null)
 * @method MailingListElement|array|null nth(int $n, Connection $db = null)
 */
class MailingListElementQuery extends ElementQuery
{
    /**
     * @var int|int[]|null The mailing list type ID(s) that the resulting mailing lists must have.
     */
    public array|int|null $mailingListTypeId;

    /**
     * @var int|int[]|null The synced user group ID(s) that the resulting mailing lists must have.
     */
    public array|int|null $syncedUserGroupId;

    /**
     * @var bool Whether to only return mailing lists that have synced user groups.
     */
    public bool $synced = false;

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'mailingListType':
                $this->mailingListType($value);
                break;
            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Sets the [[mailingListType]] property.
     */
    public function mailingListType(array|MailingListTypeModel|string|null $value): static
    {
        if ($value instanceof MailingListTypeModel) {
            $this->mailingListTypeId = $value->id;
        }
        elseif ($value !== null) {
            $this->mailingListTypeId = MailingListTypeRecord::find()
                ->select(['id'])
                ->where(Db::parseParam('handle', $value))
                ->column();
        }
        else {
            $this->mailingListTypeId = null;
        }

        return $this;
    }

    /**
     * Sets the [[mailingListTypeId]] property.
     */
    public function mailingListTypeId(array|int|null $value): static
    {
        $this->mailingListTypeId = $value;

        return $this;
    }

    /**
     * Sets the [[syncedUserGroupId]] property.
     */
    public function syncedUserGroupId(array|int|null $value): static
    {
        $this->syncedUserGroupId = $value;

        return $this;
    }

    /**
     * Sets the [[synced]] property.
     */
    public function synced(bool $value = null): static
    {
        $value = $value ?? true;

        $this->synced = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('campaign_mailinglists');

        $this->query->select([
            'campaign_mailinglists.mailingListTypeId',
            'campaign_mailinglists.syncedUserGroupId',
        ]);

        if ($this->mailingListTypeId) {
            $this->subQuery->andWhere(Db::parseParam('campaign_mailinglists.mailingListTypeId', $this->mailingListTypeId));
        }

        $this->subQuery->innerJoin(MailingListTypeRecord::tableName().' campaign_mailinglisttypes', '[[campaign_mailinglisttypes.id]] = [[campaign_mailinglists.mailingListTypeId]]');
        $this->subQuery->select('campaign_mailinglisttypes.name AS mailingListType');

        $this->subQuery->innerJoin(Table::SITES.' sites', '[[sites.id]] = [[campaign_mailinglisttypes.siteId]]');
        $this->subQuery->andWhere(['[[sites.dateDeleted]]' => null]);

        if ($this->syncedUserGroupId) {
            $this->subQuery->andWhere(Db::parseParam('campaign_mailinglists.syncedUserGroupId', $this->syncedUserGroupId));
        }

        if ($this->synced === true) {
            $this->subQuery->andWhere(['not', ['syncedUserGroupId' => 'null']]);
        }

        return parent::beforePrepare();
    }
}
