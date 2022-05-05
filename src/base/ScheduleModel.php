<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use Craft;
use craft\base\Model;
use craft\elements\conditions\ElementConditionInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\Json;
use DateTime;
use putyourlightson\campaign\elements\conditions\sendouts\SendoutScheduleCondition;
use putyourlightson\campaign\elements\SendoutElement;

/**
 * @property ElementConditionInterface|array|string|null $condition
 * @property-read array $intervalOptions
 */
abstract class ScheduleModel extends Model implements ScheduleInterface
{
    /**
     * @var bool Whether contacts can be sent to multiple times
     */
    public bool $canSendToContactsMultipleTimes = false;

    /**
     * @var DateTime|null End date
     */
    public ?DateTime $endDate = null;

    /**
     * @var array|null Days of the week
     */
    public ?array $daysOfWeek = null;

    /**
     * @var DateTime|null Time of day
     */
    public ?DateTime $timeOfDay = null;

    /**
     * @var ElementConditionInterface|null
     * @see getCondition()
     * @see setCondition()
     */
    private ?ElementConditionInterface $_condition = null;

    /**
     * @inheritdoc
     */
    public function getAttributes($names = null, $except = []): array
    {
        $attributes = parent::getAttributes($names, $except);
        $attributes['condition'] = $this->getCondition()->getConfig();

        return $attributes;
    }

    /**
     * Returns the schedule's interval options.
     */
    public function getIntervalOptions(): array
    {
        return [];
    }

    /**
     * Returns the sendout condition.
     */
    public function getCondition(): ElementConditionInterface
    {
        $condition = $this->_condition ?? Craft::createObject(SendoutScheduleCondition::class, [SendoutElement::class]);
        $condition->mainTag = 'div';
        $condition->name = 'condition';

        return $condition;
    }

    /**
     * Sets the sendout condition.
     */
    public function setCondition(ElementConditionInterface|array|string|null $condition): void
    {
        if (is_string($condition)) {
            $condition = Json::decodeIfJson($condition);
        }

        if (!$condition instanceof ElementConditionInterface) {
            $condition['class'] = SendoutScheduleCondition::class;
            $condition = Craft::$app->getConditions()->createCondition($condition);
        }
        $condition->forProjectConfig = false;

        /** @var SendoutScheduleCondition $condition */
        $this->_condition = $condition;
    }

    /**
     * @inheritdoc
     */
    public function canSendNow(SendoutElement $sendout): bool
    {
        // Ensure send date is in the past
        if (!DateTimeHelper::isInThePast($sendout->sendDate)) {
            return false;
        }

        // Ensure end date is not in the past
        if ($this->endDate !== null && DateTimeHelper::isInThePast($this->endDate)) {
            return false;
        }

        // Ensure time of day has past
        if ($this->timeOfDay !== null) {
            $now = new DateTime();
            $format = 'H:i';

            if ($this->timeOfDay->format($format) > $now->format($format)) {
                return false;
            }
        }

        // Ensure sendout passes condition rules
        if (!$this->getCondition()->matchElement($sendout)) {
            return false;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['canSendToContactsMultipleTimes'], 'boolean'],
            [['endDate', 'daysOfWeek', 'timeOfDay', 'condition'], 'safe'],
        ];
    }
}
