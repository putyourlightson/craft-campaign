<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\base\FieldInterface;
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
use craft\helpers\ElementHelper;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\RegisterSegmentAvailableFieldsEvent;
use putyourlightson\campaign\events\RegisterSegmentFieldOperatorsEvent;
use yii\base\Event;

/**
 * @since 1.7.3
 */
class SegmentHelper
{
    /**
     * @event RegisterSegmentFieldOperatorsEvent
     */
    public const EVENT_REGISTER_FIELD_OPERATORS = 'registerFieldOperators';

    /**
     * @event RegisterSegmentAvailableFieldsEvent
     */
    public const EVENT_REGISTER_AVAILABLE_FIELDS = 'registerAvailableFields';

    /**
     * Returns supported fields along with their operators.
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
            ],
            [
                'notempty' => Craft::t('campaign', 'has a value'),
                'empty' => Craft::t('campaign', 'is empty'),
            ],
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
        $settings = Campaign::$plugin->settings;

        foreach ($settings->extraSegmentFieldOperators as $fieldtype => $operators) {
            $fieldOperators[$fieldtype] = $operators;
        }

        $event = new RegisterSegmentFieldOperatorsEvent([
            'fieldOperators' => $fieldOperators,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_FIELD_OPERATORS, $event);

        return $event->fieldOperators;
    }

    /**
     * Returns available fields.
     */
    public static function getAvailableFields(): array
    {
        $settings = Campaign::$plugin->settings;

        $availableFields = [
            [
                'type' => Email::class,
                'column' => 'email',
                'name' => $settings->getEmailFieldLabel(),
                'options' => null,
            ],
        ];

        // Get contact fields
        $fields = Campaign::$plugin->settings->getContactFields();

        if (!empty($fields)) {
            $supportedFields = SegmentHelper::getFieldOperators();

            foreach ($fields as $field) {
                $fieldType = get_class($field);

                if (!empty($supportedFields[$fieldType])) {
                    $availableFields[] = [
                        'type' => $fieldType,
                        // TODO: find the right method to use here.
                        'column' => ElementHelper::fieldColumnFromField($field),
                        'name' => $field->name,
                        'options' => ($field instanceof BaseOptionsField ? $field->options : null),
                    ];
                }
            }
        }

        // Add date fields
        $availableFields[] = [
            'type' => Date::class,
            'column' => 'lastActivity',
            'name' => Craft::t('campaign', 'Last Activity'),
            'options' => null,
        ];
        $availableFields[] = [
            'type' => Date::class,
            'column' => 'elements.dateCreated',
            'name' => Craft::t('campaign', 'Date Created'),
            'options' => null,
        ];

        $event = new RegisterSegmentAvailableFieldsEvent([
            'availableFields' => $availableFields,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_AVAILABLE_FIELDS, $event);

        return $event->availableFields;
    }

    /**
     * Returns whether the provided field is a contact field.
     *
     * @since 1.20.3
     */
    public static function isContactField(FieldInterface $field): bool
    {
        $contactFields = Campaign::$plugin->settings->getContactFields();

        foreach ($contactFields as $contactField) {
            if ($contactField->id == $field->id) {
                return true;
            }
        }

        return false;
    }
}
