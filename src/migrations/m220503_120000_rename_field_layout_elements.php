<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use putyourlightson\campaign\elements\ContactElement;

class m220503_120000_rename_field_layout_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $fieldLayout = Craft::$app->getFields()->getLayoutByType(ContactElement::class);

        if ($fieldLayout->id === null) {
            return true;
        }

        $fieldLayoutTabs = (new Query())
            ->select(['id', 'elements'])
            ->from([Table::FIELDLAYOUTTABS])
            ->where(['layoutId' => $fieldLayout->id])
            ->all();

        foreach ($fieldLayoutTabs as $fieldLayoutTab) {
            $elementConfigs = Json::decodeIfJson($fieldLayoutTab['elements']);

            if (is_array($elementConfigs)) {
                foreach ($elementConfigs as &$elementConfig) {
                    $elementConfig['type'] = str_replace(
                        'ContactEmailField',
                        'ContactEmailFieldLayoutElement',
                        $elementConfig['type'],
                    );

                    $this->update(Table::FIELDLAYOUTTABS, [
                        'elements' => Json::encode($elementConfigs),
                    ], ['id' => $fieldLayoutTab['id']]);
                }
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
