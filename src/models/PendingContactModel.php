<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\models;

use craft\base\Model;
use putyourlightson\campaign\helpers\StringHelper;

class PendingContactModel extends Model
{
    /**
     * @var string|null Pending ID
     */
    public ?string $pid = null;

    /**
     * @var string|null Email
     */
    public ?string $email = null;

    /**
     * @var int|null Mailing list ID
     */
    public ?int $mailingListId = null;

    /**
     * @var string|null Source
     */
    public ?string $source = null;

    /**
     * @var mixed Field data
     */
    public mixed $fieldData = null;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        if ($this->pid === null) {
            $this->pid = StringHelper::uniqueId('p');
        }
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['pid', 'email'], 'required'],
            [['pid'], 'string', 'max' => 32],
            [['email'], 'email'],
        ];
    }
}
