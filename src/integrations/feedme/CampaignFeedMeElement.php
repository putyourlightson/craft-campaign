<?php

namespace putyourlightson\campaign\integrations\feedme;

use Craft;
use craft\base\ElementInterface;
use craft\feedme\base\Element;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;

/**
 * @property-read string $mappingTemplate
 * @property-read array $groups
 * @property-write mixed $model
 * @property-read string $groupsTemplate
 * @property-read string $columnTemplate
 */
class CampaignFeedMeElement extends Element
{
    /**
     * @inheritDoc
     */
    public static string $class = CampaignElement::class;

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return CampaignElement::displayName();
    }

    /**
     * @inheritDoc
     */
    public function getGroupsTemplate(): string
    {
        return 'campaign/_integrations/feed-me/campaign/groups';
    }

    /**
     * @inheritDoc
     */
    public function getColumnTemplate(): string
    {
        return 'campaign/_integrations/feed-me/campaign/column';
    }

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'campaign/_integrations/feed-me/campaign/map';
    }

    /**
     * @inheritDoc
     */
    public function getGroups(): array
    {
        return Campaign::$plugin->campaignTypes->getEditableCampaignTypes();
    }

    /**
     * @inheritDoc
     */
    public function getQuery($settings, array $params = []): mixed
    {
        $query = CampaignElement::find()
            ->status(null)
            ->siteId('*')
            ->campaignTypeId($settings['elementGroup'][self::$class]['campaignTypeId']);

        Craft::configure($query, $params);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function setModel($settings): ElementInterface
    {
        $this->element = new CampaignElement();
        $this->element->campaignTypeId = $settings['elementGroup'][self::$class]['campaignTypeId'];

        return $this->element;
    }
}
