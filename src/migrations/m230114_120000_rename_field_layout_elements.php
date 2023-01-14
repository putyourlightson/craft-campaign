<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use putyourlightson\campaign\elements\CampaignElement;

class m230114_120000_rename_field_layout_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $fieldLayouts = Craft::$app->getFields()->getLayoutsByType(CampaignElement::class);

        foreach ($fieldLayouts as $fieldLayout) {
            if ($fieldLayout->id === null) {
                continue;
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
                            'craft\\fieldlayoutelements\\TitleField',
                            'putyourlightson\\campaign\\fieldlayoutelements\\campaigns\\CampaignTitleField',
                            $elementConfig['type'],
                        );

                        $this->update(Table::FIELDLAYOUTTABS, [
                            'elements' => Json::encode($elementConfigs),
                        ], ['id' => $fieldLayoutTab['id']]);
                    }
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
