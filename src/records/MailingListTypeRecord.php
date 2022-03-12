<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use yii\db\ActiveQuery;

/**
 * @property int $id
 * @property int $siteId
 * @property int $fieldLayoutId
 * @property string $name
 * @property string $handle
 * @property bool $subscribeVerificationRequired
 * @property string $subscribeVerificationEmailSubject
 * @property string $subscribeVerificationEmailTemplate
 * @property string $subscribeSuccessTemplate
 * @property bool $unsubscribeFormAllowed
 * @property string $unsubscribeVerificationEmailSubject
 * @property string $unsubscribeVerificationEmailTemplate
 * @property string $unsubscribeSuccessTemplate
 * @property string $uid
 *
 * @property-read Site|null $site
 */
class MailingListTypeRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%campaign_mailinglisttypes}}';
    }

    /**
     * Returns the associated site.
     */
    public function getSite(): ActiveQuery
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
