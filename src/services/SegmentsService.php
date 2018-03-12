<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\events\RegisterSegmentAvailableFieldsEvent;
use putyourlightson\campaign\events\RegisterSegmentFieldOperatorsEvent;
use putyourlightson\campaign\helpers\StringHelper;

use Craft;
use craft\base\Component;
use craft\base\Field;
use craft\behaviors\FieldLayoutBehavior;
use craft\fields\Date;
use craft\fields\Number;
use craft\fields\PlainText;
use yii\base\InvalidConfigException;

/**
 * SegmentsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property array $segmentAvailableFields
 * @property array $segmentFieldOperators
 */
class SegmentsService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event RegisterSegmentFieldOperatorsEvent
     */
    const EVENT_REGISTER_FIELD_OPERATORS = 'registerFieldOperators';

    /**
     * @event RegisterSegmentAvailableFieldsEvent
     */
    const EVENT_REGISTER_AVAILABLE_FIELDS = 'registerAvailableFields';

    // Public Methods
    // =========================================================================

    /**
     * Returns segment by ID
     *
     * @param int $segmentId
     *
     * @return SegmentElement|null
     */
    public function getSegmentById(int $segmentId)
    {
        if (!$segmentId) {
            return null;
        }

        $segment = SegmentElement::find()
            ->id($segmentId)
            ->status(null)
            ->one();

        return $segment;
    }

    /**
     * Returns segment field operators
     *
     * @return array
     */
    public function getSegmentFieldOperators(): array
    {
        $fieldOperators = [
            StringHelper::getClassName(PlainText::class) =>  [
                '=' => Craft::t('campaign', 'is'),
                '!=' => Craft::t('campaign', 'is not'),
                'like' => Craft::t('campaign', 'contains'),
                'not like' => Craft::t('campaign', 'does not contain'),
                'like v%' => Craft::t('campaign', 'starts with'),
                'not like v%' => Craft::t('campaign', 'does not start with'),
                'like %v' => Craft::t('campaign', 'ends with'),
                'not like %v' => Craft::t('campaign', 'does not end with'),
            ],
            StringHelper::getClassName(Number::class) => [
                '=' => Craft::t('campaign', 'equals'),
                '!=' => Craft::t('campaign', 'does not equal'),
                '<' => Craft::t('campaign', 'is less than'),
                '>' => Craft::t('campaign', 'is greater than'),
            ],
            StringHelper::getClassName(Date::class) => [
                '=' => Craft::t('campaign', 'is on'),
                '<' => Craft::t('campaign', 'is before'),
                '>' => Craft::t('campaign', 'is after'),
            ],
        ];

        $event = new RegisterSegmentFieldOperatorsEvent([
            'fieldOperators' => $fieldOperators
        ]);
        $this->trigger(self::EVENT_REGISTER_FIELD_OPERATORS, $event);

        return $event->fieldOperators;
    }

    /**
     * Returns segment available fields
     *
     * @return array
     * @throws InvalidConfigException
     */
    public function getSegmentAvailableFields(): array
    {
        $settings = Campaign::$plugin->getSettings();
        $availableFields = [[
            'type' => StringHelper::getClassName(PlainText::class),
            'handle' => 'email',
            'name' => $settings->emailFieldLabel,
        ]];

        /** @var FieldLayoutBehavior $fieldLayoutBehavior */
        $fieldLayoutBehavior = Campaign::$plugin->getSettings()->getBehavior('contactFieldLayout');
        $fieldLayout = $fieldLayoutBehavior->getFieldLayout();

        if ($fieldLayout !== null) {
            $fieldOperators = $this->getSegmentFieldOperators();
            $fields = $fieldLayout->getFields();
            foreach ($fields as $field) {
                /* @var Field $field */
                $fieldType = StringHelper::getClassName(\get_class($field));
                if (isset($fieldOperators[$fieldType])) {
                    $availableFields[] = [
                        'type' => $fieldType,
                        'handle' => 'field_'.$field->handle,
                        'name' => $field->name,
                    ];
                }
            }
        }

        // Add date fields
        $availableFields[] = [
            'type' => StringHelper::getClassName(Date::class),
            'handle' => 'lastActivity',
            'name' => Craft::t('campaign', 'Last Activity'),
        ];
        $availableFields[] = [
            'type' => StringHelper::getClassName(Date::class),
            'handle' => 'elements.dateCreated',
            'name' => Craft::t('campaign', 'Date Created'),
        ];

        $event = new RegisterSegmentAvailableFieldsEvent([
            'availableFields' => $availableFields
        ]);
        $this->trigger(self::EVENT_REGISTER_AVAILABLE_FIELDS, $event);

        return $event->availableFields;
    }
}