<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\records\Site;
use putyourlightson\campaign\base\BaseActiveRecord;
use yii\db\ActiveQuery;

/**
 * MailingListTypeRecord
 *
 * @property int $id
 * @property int $siteId
 * @property int $fieldLayoutId
 * @property string $name
 * @property string $handle
 * @property bool $subscribeVerificationRequired
 * @property string $subscribeVerificationEmailSubject
 * @property string $subscribeVerificationEmailTemplate
 * @property string $subscribeVerificationSuccessTemplate
 * @property string $subscribeSuccessTemplate
 * @property bool $unsubscribeFormAllowed
 * @property string $unsubscribeVerificationEmailSubject
 * @property string $unsubscribeVerificationEmailTemplate
 * @property string $unsubscribeSuccessTemplate
 * @property ActiveQuery $site
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class MailingListTypeRecord extends BaseActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%campaign_mailinglisttypes}}';
    }

    /**
     * @return ActiveQuery
     */
    public static function find()
    {
        return parent::find()
            ->innerJoinWith(['site site'])
            ->where(['site.dateDeleted' => null]);
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the associated site.
     *
     * @return ActiveQuery
     */
    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
