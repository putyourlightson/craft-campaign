<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\base\BaseModel;

/**
 * ContactActivityModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class ContactActivityModel extends BaseModel
{
    // Public Properties
    // =========================================================================

    /**
     * @var ContactCampaignModel|ContactMailingListModel
     */
    public $model;

    /**
     * @var string Title
     */
    public $title;

    /**
     * @var string Email
     */
    public $email;

    /**
     * @var string Interaction
     */
    public $interaction;

    /**
     * @var int Count
     */
    public $count;

    /**
     * @var \DateTime
     */
    public $date;

    /**
     * @var string Source URL
     */
    public $sourceUrl;

    /**
     * @var string URL
     */
    public $url;

    /**
     * @var array Links
     */
    public $links = [];

    // Public Methods
    // =========================================================================

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            [['title', 'interaction', 'date'], 'required']
        ];
    }
}
