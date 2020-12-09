<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use Craft;
use craft\db\ActiveRecord;

/**
 * BaseModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.14.2
 *
 * @property int|null $id
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
        /**
         * TODO: remove in 2.0.0 as this is taken care of in Craft 3.5.0
         * https://github.com/craftcms/cms/commit/b7713a0c50e3b8b2f878e13ee9df81c29bda9ca9
         *
         * Unset ID if null to avoid Postgres throwing an error
         */
        if (Craft::$app->getDb()->getIsPgsql() && $this->hasAttribute('id') && $this->id === null) {
            unset($this->id);
        }

        return parent::beforeSave($insert);
    }
}
