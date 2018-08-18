<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use craft\base\Model;
use craft\validators\DateTimeValidator;

/**
 * ScheduleModel
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.2.0
*/
abstract class ScheduleModel extends Model implements ScheduleInterface
{
    // Properties
    // =========================================================================

    /**
     * @var \DateTime Start date
     */
    public $startDate;

    /**
     * @var \DateTime|null End date
     */
    public $endDate;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['startDate'], DateTimeValidator::class],
        ];
    }
}
