<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\validators\SiteIdValidator;
use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\records\MailingListTypeSiteRecord;

/**
 * MailingListTypeSiteModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.4.0
 */
class MailingListTypeSiteModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Mailing list type ID
     */
    public $mailingListTypeId;

    /**
     * @var int|null Site ID
     */
    public $siteId;

    /**
     * @var string|null Verify email template
     */
    public $verifyEmailTemplate;

    /**
     * @var string|null Subscribe success template
     */
    public $subscribeSuccessTemplate;

    /**
     * @var string|null Unsubscribe success template
     */
    public $unsubscribeSuccessTemplate;

    // Public Methods
    // =========================================================================

    /**
     * Returns a mailing list type’s site-specific settings.
     *
     * @param int $mailingListTypeId
     *
     * @return MailingListTypeSiteModel[]
     */
    public function getCampaignTypeSites(int $mailingListTypeId): array
    {
        $mailingListTypeSiteRecords = MailingListTypeSiteRecord::find()
            ->where(['campaignTypeId' => $mailingListTypeId])
            ->all();

        /** @var CampaignTypeSiteModel[] $campaignTypeSiteModels */
        $campaignTypeSiteModels = CampaignTypeSiteModel::populateModels($campaignTypeSiteRecords);

        return $campaignTypeSiteModels;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'mailingListTypeId', 'siteId'], 'number', 'integerOnly' => true],
            [['siteId'], SiteIdValidator::class],
            [['verifyEmailTemplate', 'subscribeSuccessTemplate', 'unsubscribeSuccessTemplate'], 'string', 'max' => 500],
        ];

        return $rules;
    }
}
