<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\elements\db;

use putyourlightson\campaign\elements\SegmentElement;

use craft\elements\db\ElementQuery;
use yii\db\Connection;

/**
 * SegmentElementQuery
 *
 * @method SegmentElement[]|array all($db = null)
 * @method SegmentElement|array|null one($db = null)
 * @method SegmentElement|array|null nth(int $n, Connection $db = null)
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class SegmentElementQuery extends ElementQuery
{
    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('campaign_segments');

        $this->query->select([
            'campaign_segments.conditions',
        ]);

        return parent::beforePrepare();
    }
}
