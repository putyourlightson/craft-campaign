<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\base\FieldInterface;
use craft\db\Migration;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\helpers\SegmentHelper;

class m210810_120000_update_segment_field_columns extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get contact fields
        $fields = Campaign::$plugin->getSettings()->getContactFields();

        foreach ($fields as $field) {
            $this->_updateFieldColumn($field);
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

    private function _updateFieldColumn(FieldInterface $field)
    {
        $newFieldColumn = SegmentHelper::fieldColumnFromField($field);
        $oldFieldColumn = 'field_'.$field->handle;

        if ($newFieldColumn && $newFieldColumn == $oldFieldColumn) {
            return;
        }

        $modified = false;

        $segments = SegmentElement::find()
            ->status(null)
            ->all();

        foreach ($segments as $segment) {
            foreach ($segment->conditions as &$andCondition) {
                foreach ($andCondition as &$orCondition) {
                    if ($orCondition[1] == $oldFieldColumn) {
                        $orCondition[1] = $newFieldColumn;
                        $modified = true;
                    }
                }
            }

            if ($modified) {
                Craft::$app->elements->saveElement($segment);
            }
        }
    }
}
