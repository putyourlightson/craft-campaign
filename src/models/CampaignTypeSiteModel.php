<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\Campaign;

/**
 * CampaignTypeSiteModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.4.0
 */
class CampaignTypeSiteModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Campaign type ID
     */
    public $campaignTypeId;

    /**
     * @var int|null Site ID
     */
    public $siteId;

    /**
     * @var string|null URI format
     */
    public $uriFormat;

    /**
     * @var string|null HTML template
     */
    public $htmlTemplate;

    /**
     * @var string|null Plaintext template
     */
    public $plaintextTemplate;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['id', 'campaignTypeId', 'siteId'], 'number', 'integerOnly' => true],
            [['siteId'], SiteIdValidator::class],
            [['uriFormat'], 'string'],
            [['htmlTemplate', 'plaintextTemplate'], 'string', 'max' => 500],
        ];

        return $rules;
    }
}
