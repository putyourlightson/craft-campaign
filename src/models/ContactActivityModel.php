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
     * @var string Title
     */
    public string $title;

    /**
     * @var string Email
     */
    public string $email;

    /**
     * @var string Interaction
     */
    public string $interaction;

    /**
     * @var int Count
     */
    public int $count;

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
