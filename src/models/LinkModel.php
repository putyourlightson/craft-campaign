<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\base\Model;

class LinkModel extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id;

    /**
     * @var string|null Link ID
     */
    public ?string $lid;

    /**
     * @var int Campaign ID
     */
    public int $campaignId;

    /**
     * @var string|null URL
     */
    public ?string $url;

    /**
     * @var string|null Title
     */
    public ?string $title;

    /**
     * @var int Clicked
     */
    public int $clicked = 0;

    /**
     * @var int Clicks
     */
    public int $clicks = 0;
}
