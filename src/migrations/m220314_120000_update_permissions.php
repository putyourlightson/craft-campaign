<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\migrations\m220123_213619_update_permissions;
use putyourlightson\campaign\services\CampaignTypesService;
use putyourlightson\campaign\services\MailingListTypesService;

class m220314_120000_update_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        /**
         * Update campaign and mailing list type permissions.
         * @see m220123_213619_update_permissions::safeUp()
         */
        $map = [];

        $projectConfig = Craft::$app->getProjectConfig();
        $campaignTypeConfigs = $projectConfig->get(CampaignTypesService::CONFIG_CAMPAIGNTYPES_KEY, true) ?? [];
        $mailingListTypesConfigs = $projectConfig->get(MailingListTypesService::CONFIG_MAILINGLISTTYPES_KEY, true) ?? [];

        $campaignTypePermissions = [];
        foreach (array_keys($campaignTypeConfigs) as $campaignTypeUid) {
            $campaignTypePermissions[] = 'campaign:campaigns:' . $campaignTypeUid;
        }
        $map += ['campaign:campaigns' => $campaignTypePermissions];

        $mailingListTypePermissions = [];
        foreach (array_keys($mailingListTypesConfigs) as $mailingListTypeUid) {
            $mailingListTypePermissions[] = 'campaign:mailingLists:' . $mailingListTypeUid;
        }
        $map += ['campaign:mailingLists' => $mailingListTypePermissions];

        // Lowercase everything
        $map = array_combine(
            array_map('strtolower', array_keys($map)),
            array_map(fn($newPermissions) => array_map('strtolower', $newPermissions), array_values($map)));

        // Now add the new permissions to existing users where applicable
        foreach ($map as $oldPermission => $newPermissions) {
            $userIds = (new Query())
                ->select(['upu.userId'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
                ->where(['up.name' => $oldPermission])
                ->column($this->db);

            $userIds = array_unique($userIds);

            if (!empty($userIds)) {
                $insert = [];

                foreach ($newPermissions as $newPermission) {
                    $this->insert(Table::USERPERMISSIONS, [
                        'name' => $newPermission,
                    ]);
                    $newPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);
                    foreach ($userIds as $userId) {
                        $insert[] = [$newPermissionId, $userId];
                    }
                }

                $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
            }
        }

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('plugins.campaign.schemaVersion', true);

        if (version_compare($schemaVersion, '2.0.0', '<')) {
            foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
                $groupPermissions = array_flip($group['permissions'] ?? []);

                foreach ($map as $oldPermission => $newPermissions) {
                    if (isset($groupPermissions[$oldPermission])) {
                        foreach ($newPermissions as $newPermission) {
                            $groupPermissions[$newPermission] = true;
                        }
                    }
                }

                $projectConfig->set("users.groups.$uid.permissions", array_keys($groupPermissions));
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
