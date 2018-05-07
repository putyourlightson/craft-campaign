<?php

namespace putyourlightson\campaign\migrations;

use craft\records\UserPermission;
use putyourlightson\campaign\records\ContactMailingListRecord;

use craft\db\Migration;

/**
 * m180503_120000_user_permissions migration.
 */
class m180503_120000_user_permissions extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $userPermissions = UserPermission::find()
            ->where(['like', 'name', 'campaign-'])
            ->all();

        foreach ($userPermissions as $userPermission) {
            /** @var UserPermission $userPermission */
            $userPermission->name = str_replace('-', ':', $userPermission->name);

            $userPermission->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180503_120000_user_permissions cannot be reverted.\n";

        return false;
    }
}
