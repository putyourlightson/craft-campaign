<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\validators;

use craft\base\Element;
use craft\validators\UniqueValidator;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @since 2.9.0
 */
class SendableSegmentsValidator extends UniqueValidator
{
    /**
     * @inheritdoc
     *
     * @param SendoutElement $model
     */
    public function validateAttribute($model, $attribute): void
    {
        $segments = $model->getSegments();

        foreach ($segments as $segment) {
            $status = $segment->getStatus();
            if ($status !== Element::STATUS_ENABLED) {
                $this->addError($model, $attribute, Craft::t('campaign', 'One or more disabled segments were selected.'));

                return;
            }
        }
    }
}
