<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\base\Model;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

/**
 *
 * @property-read MailingListElement[] $mailingLists
 */
class ExportModel extends Model
{
    /**
     * @var string|null File path
     */
    public ?string $filePath = null;

    /**
     * @var array|null Mailing list ID
     */
    public ?array $mailingListIds = null;

    /**
     * @var array|null Fields
     */
    public ?array $fields = null;

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        // Set the field labels
        $labels['mailingListIds'] = Craft::t('campaign', 'Mailing Lists');

        return $labels;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['filePath', 'mailingListIds'], 'required'],
            [['filePath'], 'string', 'max' => 255],
        ];
    }

    /**
     * Returns the mailing lists.
     *
     * @return MailingListElement[]
     */
    public function getMailingLists(): array
    {
        if ($this->mailingListIds === null) {
            return [];
        }

        $mailingLists = [];

        foreach ($this->mailingListIds as $mailingListId) {
            $mailingLists[] = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);
        }

        return $mailingLists;
    }
}
