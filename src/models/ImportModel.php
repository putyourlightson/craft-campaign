<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\helpers\Json;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\base\BaseModel;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\elements\User;
use craft\helpers\UrlHelper;
use craft\models\UserGroup;

/**
 * ImportModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property string                       $cpViewUrl
 * @property User|null                    $user
 * @property null|UserGroup               $userGroup
 * @property null|MailingListElement      $mailingList
 */
class ImportModel extends BaseModel
{
    // Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var string|null File name
     */
    public $fileName;

    /**
     * @var string|null File path
     */
    public $filePath;

    /**
     * @var int|null User group ID
     */
    public $userGroupId;

    /**
     * @var int|null User ID
     */
    public $userId;

    /**
     * @var string Email field index
     */
    public $emailFieldIndex;

    /**
     * @var mixed Field indexes
     */
    public $fieldIndexes;

    /**
     * @var int|null Mailing list ID
     */
    public $mailingListId;

    /**
     * @var int Added
     */
    public $added = 0;

    /**
     * @var int Updated
     */
    public $updated = 0;

    /**
     * @var int Failed
     */
    public $failed = 0;

    /**
     * @var mixed Failures
     */
    public $failures;

    /**
     * @var \DateTime|null Date imported
     */
    public $dateImported;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        // Decode JSON properties
        $this->fieldIndexes = empty($this->fieldIndexes) ? [] : Json::decode($this->fieldIndexes);
        $this->failures = empty($this->failures) ? [] : Json::decode($this->failures);
    }

    /**
     * Use the handle as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->fileName;
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();

        // Set the field labels
        $labels['mailingListId'] = Craft::t('campaign', 'Mailing List');
        $labels['emailFieldIndex'] = Craft::t('campaign', 'Email');

        return $labels;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'userId', 'mailingListId'], 'integer'],
            [['mailingListId', 'emailFieldIndex'], 'required'],
            [['fileName', 'filePath'], 'string', 'max' => 255],
        ];
    }

    /**
     * Returns the CP view URL
     *
     * @return string
     */
    public function getCpViewUrl(): string
    {
        return UrlHelper::cpUrl('campaign/contacts/import/'.$this->id);
    }

    /**
     * Returns the user group
     *
     * @return UserGroup|null
     */
    public function getUserGroup()
    {
        if ($this->userGroupId === null) {
            return null;
        }

        return Craft::$app->getUserGroups()->getGroupById($this->userGroupId);
    }

    /**
     * Returns the user
     *
     * @return User|null
     */
    public function getUser()
    {
        if ($this->userId === null) {
            return null;
        }

        return User::find()->id($this->userId)->one();
    }

    /**
     * Returns the mailing list
     *
     * @return MailingListElement|null
     */
    public function getMailingList()
    {
        if ($this->mailingListId === null) {
            return null;
        }

        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($this->mailingListId);

        return $mailingList;
    }
}
