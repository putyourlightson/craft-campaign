<?php

namespace putyourlightson\campaign\integrations\feedme;

use Craft;
use craft\base\ElementInterface;
use craft\feedme\base\Element;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\models\ContactMailingListModel;

/**
 * @property ContactElement $element
 * @property-read string $mappingTemplate
 * @property-read array $groups
 * @property-write mixed $model
 * @property-read string $groupsTemplate
 * @property-read array $subscriptionStatusOptions
 * @property-read string $columnTemplate
 */
class ContactFeedMeElement extends Element
{
    /**
     * @inheritDoc
     */
    public static string $class = ContactElement::class;

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return ContactElement::displayName();
    }

    /**
     * @inheritDoc
     */
    public function getGroupsTemplate(): string
    {
        return 'campaign/_integrations/feed-me/contact/groups';
    }

    /**
     * @inheritDoc
     */
    public function getColumnTemplate(): string
    {
        return 'campaign/_integrations/feed-me/contact/column';
    }

    /**
     * @inheritDoc
     */
    public function getMappingTemplate(): string
    {
        return 'campaign/_integrations/feed-me/contact/map';
    }

    /**
     * @inheritDoc
     */
    public function getGroups(): array
    {
        return Campaign::$plugin->mailingLists->getAllMailingLists();
    }

    public function getSubscriptionStatusOptions(): array
    {
        $subscriptionStatuses = [];
        foreach (ContactMailingListModel::INTERACTIONS as $interaction) {
            $subscriptionStatuses[] = [
                'label' => Craft::t('campaign', ucfirst($interaction)),
                'value' => $interaction,
            ];
        }

        return $subscriptionStatuses;
    }

    /**
     * @inheritDoc
     */
    public function getQuery($settings, array $params = []): mixed
    {
        $query = ContactElement::find()
            ->status(null)
            ->siteId('*');

        Craft::configure($query, $params);

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function setModel($settings): ElementInterface
    {
        $this->element = new ContactElement();

        return $this->element;
    }

    /**
     * @inheritDoc
     */
    public function afterSave($data, $settings): void
    {
        $mailingListId = $settings['elementGroup'][self::$class]['mailingListId'];
        $subscriptionStatus = $settings['elementGroup'][self::$class]['subscriptionStatus'];

        if ($mailingListId && $subscriptionStatus) {
            $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);
            if ($mailingList) {
                Campaign::$plugin->mailingLists->addContactInteraction($this->element, $mailingList, $subscriptionStatus);
            }
        }
    }
}
