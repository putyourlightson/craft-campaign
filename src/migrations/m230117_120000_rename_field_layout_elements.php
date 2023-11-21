<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;

class m230117_120000_rename_field_layout_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->_renameFieldLayoutElements(
            Craft::$app->getFields()->getLayoutsByType(CampaignElement::class), 'putyourlightson\\campaign\\fieldlayoutelements\\campaigns',
        );

        $this->_renameFieldLayoutElements(
            Craft::$app->getFields()->getLayoutsByType(MailingListElement::class), 'putyourlightson\\campaign\\fieldlayoutelements\\NonTranslatableTitleField',
        );

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

    private function _renameFieldLayoutElements(array $fieldLayouts, string $newType): void
    {
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
                            $newType,
                            $elementConfig['type'],
                        );

                        $this->update(Table::FIELDLAYOUTTABS, [
                            'elements' => Json::encode($elementConfigs),
                        ], ['id' => $fieldLayoutTab['id']]);
                    }
                }
            }
        }
    }
}
