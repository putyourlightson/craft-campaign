<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use craft\db\ActiveRecord;

/**
 * BaseModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.14.2
*/
abstract class BaseActiveRecord extends ActiveRecord
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function beforeSave($insert): bool
    {
        if ($insert) {
            // Unset ID if null to avoid Postgres throwing an error
            if ($this->hasAttribute('id') && $this->id === null) {
                unset($this->id);
            }
        }

        return parent::beforeSave($insert);
    }
}
