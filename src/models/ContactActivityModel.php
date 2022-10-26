<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\base\Model;
use DateTime;

class ContactActivityModel extends Model
{
    /**
     * @var ContactCampaignModel|ContactMailingListModel
     */
    public ContactCampaignModel|ContactMailingListModel $model;

    /**
     * @var string|null Title
     */
    public ?string $title = null;

    /**
     * @var string|null Email
     */
    public ?string $email = null;

    /**
     * @var string|null Interaction
     */
    public ?string $interaction = null;

    /**
     * @var int Count
     */
    public int $count = 0;

    /**
     * @var DateTime
     */
    public DateTime $date;

    /**
     * @var string Source URL
     */
    public string $sourceUrl;

    /**
     * @var string URL
     */
    public string $url;

    /**
     * @var array Links
     */
    public array $links = [];

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['title', 'interaction', 'date'], 'required'],
        ];
    }
}
