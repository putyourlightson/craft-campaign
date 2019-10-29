<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\records;

use craft\db\ActiveRecord;
use craft\records\Site;
use yii\db\ActiveQueryInterface;


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
 * @property ActiveQueryInterface $site
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class MailingListTypeRecord extends ActiveRecord
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

    // Public Methods
    // =========================================================================

    /**
     * Returns the associated site.
     *
     * @return ActiveQueryInterface The relational query object.
     */
    public function getSite(): ActiveQueryInterface
    {
        return $this->hasOne(Site::class, ['id' => 'siteId']);
    }
}
