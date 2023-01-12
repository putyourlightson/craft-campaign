<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use craft\i18n\Translation;

class m230112_120000_add_widget_announcement extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        Craft::$app->announcements->push(
            Translation::prep('campaign', 'New Dashboard Widgets'),
            Translation::prep('campaign', 'The new Campaign dashboard widgets allow you to quickly view campaign and mailing list stats.'),
            'campaign',
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return true;
    }
}
