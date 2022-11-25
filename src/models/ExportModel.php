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
     * @var string File path
     */
    public string $filePath = '';

    /**
     * @var array Mailing list IDs
     */
    public array $mailingListIds = [];

    /**
     * @var array Fields
     */
    public array $fields = [];

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
        $mailingLists = [];

        foreach ($this->mailingListIds as $mailingListId) {
            $mailingLists[] = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);
        }

        return $mailingLists;
    }
}
