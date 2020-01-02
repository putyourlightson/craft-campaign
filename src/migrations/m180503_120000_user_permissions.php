<?php

namespace putyourlightson\campaign\migrations;

use craft\records\UserPermission;

use craft\db\Migration;

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

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class." cannot be reverted.\n";

        return false;
    }
}
