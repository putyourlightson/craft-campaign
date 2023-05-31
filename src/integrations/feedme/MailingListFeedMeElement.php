<?php

namespace putyourlightson\campaign\integrations\feedme;

use Craft;
use craft\base\ElementInterface;
use craft\feedme\base\Element;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\MailingListElement;

/**
 * @property-read string $mappingTemplate
 * @property-read array $groups
 * @property-write mixed $model
 * @property-read string $groupsTemplate
 * @property-read string $columnTemplate
 */
class MailingListFeedMeElement extends Element
{
    /**
     * @inheritDoc
     */
    public static string $class = MailingListElement::class;

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return MailingListElement::displayName();
    }

    /**
     * @inheritDoc
     */
    public function getGroupsTemplate(): string
    {
        return 'campaign/_integrations/feed-me/mailing-list/groups';
    }

    /**
     * @inheritDoc
     */
    public function getColumnTemplate(): string
    {
        return 'campaign/_integrations/feed-me/mailing-list/column';
    }

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'campaign/_integrations/feed-me/mailing-list/map';
    }

    /**
     * @inheritDoc
     */
    public function getGroups(): array
    {
        return Campaign::$plugin->mailingListTypes->getEditableMailingListTypes();
    }

    /**
     * @inheritDoc
     */
    public function getQuery($settings, array $params = []): mixed
    {
        $query = MailingListElement::find()
            ->status(null)
            ->siteId('*')
            ->mailingListTypeId($settings['elementGroup'][self::$class]['mailingListTypeId']);

        Craft::configure($query, $params);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function setModel($settings): ElementInterface
    {
        $this->element = new MailingListElement();
        $this->element->mailingListTypeId = $settings['elementGroup'][self::$class]['mailingListTypeId'];

        return $this->element;
    }
}
