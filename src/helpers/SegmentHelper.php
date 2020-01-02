<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\base\Field;
use craft\behaviors\FieldLayoutBehavior;
use craft\fields\BaseOptionsField;
use craft\fields\Checkboxes;
use craft\fields\Date;
use craft\fields\Dropdown;
use craft\fields\Email;
use craft\fields\Lightswitch;
use craft\fields\MultiSelect;
use craft\fields\Number;
use craft\fields\PlainText;
use craft\fields\RadioButtons;
use craft\fields\Url;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\RegisterSegmentAvailableFieldsEvent;
use putyourlightson\campaign\events\RegisterSegmentFieldOperatorsEvent;
use yii\base\Event;
use yii\base\InvalidConfigException;

/**
 * SegmentHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.7.3
 */
class SegmentHelper
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

    // Static Methods
    // =========================================================================

    /**
     * Returns supported fields along with their operators
     *
     * @return array
     */
    public static function getFieldOperators(): array
    {
        $isOperators = [
            '=' => Craft::t('campaign', 'is'),
            '!=' => Craft::t('campaign', 'is not'),
        ];

        $containsOperators = [
            'like' => Craft::t('campaign', 'contains'),
            'not like' => Craft::t('campaign', 'does not contain'),
        ];

        $standardOperators = array_merge(
            $isOperators,
            $containsOperators,
            [
                'like v%' => Craft::t('campaign', 'starts with'),
                'not like v%' => Craft::t('campaign', 'does not start with'),
                'like %v' => Craft::t('campaign', 'ends with'),
                'not like %v' => Craft::t('campaign', 'does not end with'),
            ]
        );

        $fieldOperators = [
            PlainText::class => $standardOperators,
            Email::class => $standardOperators,
            Url::class => $standardOperators,
            Number::class => [
                '=' => Craft::t('campaign', 'equals'),
                '!=' => Craft::t('campaign', 'does not equal'),
                '<' => Craft::t('campaign', 'is less than'),
                '>' => Craft::t('campaign', 'is greater than'),
            ],
            Date::class => [
                '=' => Craft::t('campaign', 'is on'),
                '<' => Craft::t('campaign', 'is before'),
                '>' => Craft::t('campaign', 'is after'),
            ],
            Lightswitch::class => $isOperators,
            Dropdown::class => $isOperators,
            RadioButtons::class => $isOperators,
            Checkboxes::class => $containsOperators,
            MultiSelect::class => $containsOperators,
        ];

        // Add field operators from config settings
        $settings = Campaign::$plugin->getSettings();

        foreach ($settings->extraSegmentFieldOperators as $fieldtype => $operators) {
            $fieldOperators[$fieldtype] = $operators;
        }

        $event = new RegisterSegmentFieldOperatorsEvent([
            'fieldOperators' => $fieldOperators
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_FIELD_OPERATORS, $event);

        return $event->fieldOperators;
    }

    /**
     * Returns available fields
     *
     * @return array
     * @throws InvalidConfigException
     */
    public static function getAvailableFields(): array
    {
        $settings = Campaign::$plugin->getSettings();
        $availableFields = [[
            'type' => Email::class,
            'handle' => 'email',
            'name' => $settings->emailFieldLabel,
            'options' => null,
        ]];

        /** @var FieldLayoutBehavior $fieldLayoutBehavior */
        $fieldLayoutBehavior = $settings->getBehavior('contactFieldLayout');
        $fieldLayout = $fieldLayoutBehavior->getFieldLayout();

        if ($fieldLayout !== null) {
            $supportedFields = SegmentHelper::getFieldOperators();

            /* @var Field[] $fields */
            $fields = $fieldLayout->getFields();

            foreach ($fields as $field) {
                $fieldType = get_class($field);

                if (!empty($supportedFields[$fieldType])) {
                    $availableFields[] = [
                        'type' => $fieldType,
                        'handle' => 'field_'.$field->handle,
                        'name' => $field->name,
                        'options' => ($field instanceof BaseOptionsField ? $field->options : null),
                    ];
                }
            }
        }

        // Add date fields
        $availableFields[] = [
            'type' => Date::class,
            'handle' => 'lastActivity',
            'name' => Craft::t('campaign', 'Last Activity'),
            'options' => null,
        ];
        $availableFields[] = [
            'type' => Date::class,
            'handle' => 'elements.dateCreated',
            'name' => Craft::t('campaign', 'Date Created'),
            'options' => null,
        ];

        $event = new RegisterSegmentAvailableFieldsEvent([
            'availableFields' => $availableFields
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_AVAILABLE_FIELDS, $event);

        return $event->availableFields;
    }
}
