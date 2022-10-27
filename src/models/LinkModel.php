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
    public ?int $id = null;

    /**
     * @var string|null Link ID
     */
    public ?string $lid = null;

    /**
     * @var int|null Campaign ID
     */
    public ?int $campaignId = null;

    /**
     * @var string|null URL
     */
    public ?string $url = null;

    /**
     * @var string|null Title
     */
    public ?string $title = null;

    /**
     * @var int Clicked
     */
    public int $clicked = 0;

    /**
     * @var int Clicks
     */
    public int $clicks = 0;
}
