<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\base\BaseModel;

/**
 * LinkModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class LinkModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null Link ID
     */
    public $lid;

    /**
     * @var int Campaign ID
     */
    public $campaignId;

    /**
     * @var string|null URL
     */
    public $url;

    /**
     * @var string|null Title
     */
    public $title;

    /**
     * @var int Clicked
     */
    public $clicked = 0;

    /**
     * @var int Clicks
     */
    public $clicks = 0;
}