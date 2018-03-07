<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\base\Model;

/**
 * ExportModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property MailingListElement[] $mailingLists
 */
class ExportModel extends Model
{
    // Properties
    // =========================================================================

    /**
     * @var string File path
     */
    public $filePath;

    /**
     * @var array|null Mailing list IDs
     */
    public $mailingListIds;

    /**
     * @var array|null Fields
     */
    public $fields;

    // Public Methods
    // =========================================================================

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
    public function rules(): array
    {
        return [
            [['filePath', 'mailingListIds'], 'required'],
            [['filePath'], 'string', 'max' => 255],
        ];
    }

    /**
     * Returns the mailing lists
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
