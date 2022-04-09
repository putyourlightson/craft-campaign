<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use Craft;
use craft\base\Model;
use craft\elements\User;
use craft\helpers\UrlHelper;

use craft\models\UserGroup;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @property-read string $cpViewUrl
 * @property-read User|null $user
 * @property-read null|UserGroup $userGroup
 * @property-read null|MailingListElement $mailingList
 */
class ImportModel extends Model
{
    /**
     * @var int|null ID
     */
    public ?int $id = null;

    /**
     * @var int|null Asset ID
     */
    public ?int $assetId = null;

    /**
     * @var string|null File name
     */
    public ?string $fileName = null;

    /**
     * @var string|null File path
     */
    public ?string $filePath = null;

    /**
     * @var int|null User group ID
     */
    public ?int $userGroupId = null;

    /**
     * @var int|null User ID
     */
    public ?int $userId = null;

    /**
     * @var string|null Email field index
     */
    public ?string $emailFieldIndex = null;

    /**
     * @var array|null Field indexes
     */
    public ?array $fieldIndexes = null;

    /**
     * @var int|null Mailing list ID
     */
    public ?int $mailingListId = null;

    /**
     * @var bool Force subscribe
     */
    public bool $forceSubscribe = false;

    /**
     * @var int Added
     */
    public int $added = 0;

    /**
     * @var int Updated
     */
    public int $updated = 0;

    /**
     * @var int Fails
     */
    public int $fails = 0;

    /**
     * @var DateTime|null Date imported
     */
    public ?DateTime $dateImported = null;

    /**
     * Returns the file name as the string representation.
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
     * Returns the CP view URL.
     */
    public function getCpViewUrl(): string
    {
        return UrlHelper::cpUrl('campaign/contacts/import/' . $this->id);
    }

    /**
     * Returns the user group.
     */
    public function getUserGroup(): ?UserGroup
    {
        if ($this->userGroupId === null) {
            return null;
        }

        return Craft::$app->getUserGroups()->getGroupById($this->userGroupId);
    }

    /**
     * Returns the user.
     */
    public function getUser(): ?User
    {
        if ($this->userId === null) {
            return null;
        }

        /** @var User|null */
        return User::find()
            ->id($this->userId)
            ->one();
    }

    /**
     * Returns the mailing list.
     */
    public function getMailingList(): ?MailingListElement
    {
        if ($this->mailingListId === null) {
            return null;
        }

        return Campaign::$plugin->mailingLists->getMailingListById($this->mailingListId);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['id', 'assetId', 'userId', 'mailingListId'], 'integer'],
            [['mailingListId', 'emailFieldIndex'], 'required'],
            [['fileName', 'filePath'], 'string', 'max' => 255],
        ];
    }
}
