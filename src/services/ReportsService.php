<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\db\ActiveRecord;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\helpers\UrlHelper;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\NumberHelper;
use putyourlightson\campaign\models\ContactActivityModel;
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\models\ContactMailingListModel;
use putyourlightson\campaign\models\LinkModel;
use putyourlightson\campaign\records\CampaignRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\SendoutRecord;

/**
 * @property-read array $contactsReportData
 */
class ReportsService extends Component
{
    /**
     * @const int
     */
    public const MIN_INTERVALS = 5;

    /**
     * Returns max intervals.
     */
    public function getMaxIntervals(string $interval): int
    {
        $maxIntervals = ['minutes' => 60, 'hours' => 24, 'days' => 14, 'months' => 12, 'years' => 10];

        return $maxIntervals[$interval] ?? 12;
    }

    /**
     * Returns campaigns report data.
     */
    public function getCampaignsReportData(int $siteId = null): array
    {
        // Get all sent campaigns
        $campaigns = CampaignElement::find()
            ->status(CampaignElement::STATUS_SENT)
            ->orderBy('lastSent DESC')
            ->siteId($siteId)
            ->all();

        // Get data
        $data['campaigns'] = $campaigns;
        $data['recipients'] = 0;
        $data['opened'] = 0;
        $data['clicked'] = 0;

        foreach ($campaigns as $campaign) {
            $data['recipients'] += $campaign->recipients;
            $data['opened'] += $campaign->opened;
            $data['clicked'] += $campaign->clicked;
        }

        $data['openRate'] = $data['recipients'] ? NumberHelper::floorOrOne($data['opened'] / $data['recipients'] * 100) : 0;
        $data['clickRate'] = $data['opened'] ? NumberHelper::floorOrOne($data['clicked'] / $data['opened'] * 100) : 0;

        // Get sendouts count
        $data['sendouts'] = SendoutElement::find()
            ->siteId($siteId)
            ->count();

        return $data;
    }

    /**
     * Returns campaign report data.
     */
    public function getCampaignReportData(int $campaignId): array
    {
        // Get campaign
        $data = ['campaign' => Campaign::$plugin->campaigns->getCampaignById($campaignId)];

        // Get sendouts
        $data['sendouts'] = SendoutElement::find()
            ->campaignId($campaignId)
            ->orderBy(['sendDate' => SORT_ASC])
            ->all();

        // Get date first sent
        /** @var ContactCampaignRecord|null $contactCampaignRecord */
        $contactCampaignRecord = ContactCampaignRecord::find()
            ->where(['campaignId' => $campaignId])
            ->orderBy(['dateCreated' => SORT_ASC])
            ->limit(1)
            ->one();

        $data['dateFirstSent'] = $contactCampaignRecord === null ? null : DateTimeHelper::toDateTime($contactCampaignRecord->dateCreated);

        // Check if chart exists
        $data['hasChart'] = count($this->getCampaignContactActivity($campaignId, 'opened', 1)) > 0;

        return $data;
    }

    /**
     * Returns campaign chart data.
     */
    public function getCampaignChartData(int $campaignId, string $interval = null): array
    {
        $interval = $interval ?? 'hours';

        return $this->getChartData(
            ContactCampaignRecord::class,
            ['campaignId' => $campaignId],
            ContactCampaignModel::INTERACTIONS,
            $interval
        );
    }

    /**
     * Returns campaign recipients.
     *
     * @return ContactCampaignModel[]
     */
    public function getCampaignRecipients(int $campaignId, int $sendoutId = null): array
    {
        $condition = ['contactCampaigns.campaignId' => $campaignId];

        if ($sendoutId !== null) {
            $condition['sendoutId'] = $sendoutId;
        }

        $results = (new Query())
            ->select(['contactCampaigns.*', 'contacts.email', 'sendouts.sendoutType', 'content.title'])
            ->from(['contactCampaigns' => ContactCampaignRecord::tableName()])
            ->innerJoin(['contacts' => ContactRecord::tableName()], '[[contacts.id]] = [[contactId]]')
            ->innerJoin(['sendouts' => SendoutRecord::tableName()], '[[sendouts.id]] = [[sendoutId]]')
            ->innerJoin(['content' => Table::CONTENT], '[[content.elementId]] = [[sendoutId]]')
            ->where($condition)
            ->orderBy(['sent' => SORT_DESC])
            ->all();

        $contactCampaigns = [];

        foreach ($results as $result) {
            $contactCampaign = new ContactCampaignModel();
            $contactCampaign->setAttributes($result, false);
            $contactCampaign->contactEmail = $result['email'];
            $contactCampaign->contactReportUrl = UrlHelper::cpUrl('campaign/reports/contacts/' . $result['contactId']);
            $contactCampaign->sendoutTitle = $result['title'];
            $contactCampaign->sendoutEditUrl = UrlHelper::cpUrl('campaign/sendouts/' . $result['sendoutType'] . '/' . $result['sendoutId']);
            $contactCampaigns[] = $contactCampaign;
        }

        return $contactCampaigns;
    }

    /**
     * Returns campaign contact activity.
     *
     * @return ContactActivityModel[]
     */
    public function getCampaignContactActivity(int $campaignId, string $interaction = null, int $limit = null): array
    {
        $condition = ['contactCampaigns.campaignId' => $campaignId];

        // If no interaction was specified then set check for any interaction that is not null
        $interactionCondition = [
            'not',
            $interaction ? ['contactCampaigns.' . $interaction => null] : [
                'or',
                [
                    'contactCampaigns.opened' => null,
                    'contactCampaigns.clicked' => null,
                    'contactCampaigns.unsubscribed' => null,
                    'contactCampaigns.complained' => null,
                    'contactCampaigns.bounced' => null,
                ],
            ],
        ];

        $results = (new Query())
            ->select(['contactCampaigns.*', 'contacts.email', 'elements.dateDeleted'])
            ->from(['contactCampaigns' => ContactCampaignRecord::tableName()])
            ->innerJoin(['contacts' => ContactRecord::tableName()], '[[contacts.id]] = [[contactId]]')
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[contactId]]')
            ->where($condition)
            ->andWhere($interactionCondition)
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->all();

        $contactCampaigns = [];

        foreach ($results as $result) {
            $contactCampaign = new ContactCampaignModel();
            $contactCampaign->setAttributes($result, false);
            $contactCampaign->contactEmail = $result['email'];

            if ($result['dateDeleted'] === null) {
                $contactCampaign->contactReportUrl = UrlHelper::cpUrl('campaign/reports/contacts/' . $result['contactId']);
            }

            $contactCampaigns[] = $contactCampaign;
        }

        // Return contact activity
        return $this->getActivity($contactCampaigns, $interaction, $limit);
    }

    /**
     * Returns campaign links.
     *
     * @return LinkModel[]
     */
    public function getCampaignLinks(int $campaignId, int $limit = null): array
    {
        /** @var LinkRecord[] $linkRecords */
        $linkRecords = LinkRecord::find()
            ->where(['campaignId' => $campaignId])
            ->orderBy(['clicked' => SORT_DESC, 'clicks' => SORT_DESC])
            ->limit($limit)
            ->all();

        $links = [];

        foreach ($linkRecords as $linkRecord) {
            $link = new LinkModel();
            $link->setAttributes($linkRecord->getAttributes(), false);
            $links[] = $link;
        }

        return $links;
    }

    /**
     * Returns campaign locations.
     */
    public function getCampaignLocations(int $campaignId, int $limit = null): array
    {
        // Get campaign
        $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

        if ($campaign === null) {
            return [];
        }

        // Return locations of contact campaigns
        return $this->getLocations(ContactCampaignRecord::class, ['and', ['campaignId' => $campaignId], ['not', ['opened' => null]]], $campaign->opened, $limit);
    }

    /**
     * Returns campaign devices.
     */
    public function getCampaignDevices(int $campaignId, bool $detailed = false, int $limit = null): array
    {
        // Get campaign
        $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

        if ($campaign === null) {
            return [];
        }

        // Return device, os and client of contact campaigns
        return $this->getDevices(ContactCampaignRecord::class, ['and', ['campaignId' => $campaignId], ['not', ['opened' => null]]], $detailed, $campaign->opened, $limit);
    }

    /**
     * Returns contacts report data.
     */
    public function getContactsReportData(): array
    {
        $data = [];

        // Get interactions
        $interactions = ContactMailingListModel::INTERACTIONS;

        foreach ($interactions as $interaction) {
            $count = ContactMailingListRecord::find()
                ->where(['subscriptionStatus' => $interaction])
                ->count();

            $data[$interaction] = $count;
        }

        $data['total'] = ContactMailingListRecord::find()->count();

        return $data;
    }

    /**
     * Returns contacts activity.
     */
    public function getContactsActivity(int $limit = null): array
    {
        // Get recently active contacts
        return ContactElement::find()
            ->orderBy(['lastActivity' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Returns contacts locations.
     */
    public function getContactsLocations(int $limit = null): array
    {
        // Get total active contacts
        $total = ContactElement::find()
            ->where(['complained' => null, 'bounced' => null])
            ->count();

        // Return locations of contacts
        return $this->getLocations(ContactRecord::class, [], $total, $limit);
    }

    /**
     * Returns contacts devices.
     */
    public function getContactsDevices(bool $detailed = false, int $limit = null): array
    {
        // Get total active contacts
        $total = ContactElement::find()
            ->where(['complained' => null, 'bounced' => null])
            ->count();

        // Return device, os and client of contacts
        return $this->getDevices(ContactRecord::class, [], $detailed, $total, $limit);
    }

    /**
     * Returns contact campaigns.
     *
     * @return ContactActivityModel[]
     */
    public function getContactCampaignActivity(int $contactId, int $limit = null, array|int $campaignId = null): array
    {
        $condition = ['contactId' => $contactId];

        if ($campaignId !== null) {
            $condition['campaignId'] = $campaignId;
        }

        $results = (new Query())
            ->select(['contactCampaigns.*', 'contacts.email', 'elements.dateDeleted', 'content.title'])
            ->from(['contactCampaigns' => ContactCampaignRecord::tableName()])
            ->innerJoin(['contacts' => ContactRecord::tableName()], '[[contacts.id]] = [[contactId]]')
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[campaignId]]')
            ->innerJoin(['content' => Table::CONTENT], '[[content.elementId]] = [[campaignId]]')
            ->where($condition)
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->all();

        $contactCampaigns = [];

        foreach ($results as $result) {
            $contactCampaign = new ContactCampaignModel();
            $contactCampaign->setAttributes($result, false);
            $contactCampaign->contactEmail = $result['email'];
            $contactCampaign->contactReportUrl = UrlHelper::cpUrl('campaign/reports/contacts/' . $result['contactId']);
            $contactCampaign->campaignTitle = $result['title'];

            if ($result['dateDeleted'] === null) {
                $contactCampaign->campaignReportUrl = UrlHelper::cpUrl('campaign/reports/campaigns/' . $result['campaignId']);
            }

            $contactCampaigns[] = $contactCampaign;
        }

        // Return contact activity
        return $this->getActivity($contactCampaigns, null, $limit);
    }

    /**
     * Returns contact mailing list activity.
     *
     * @return ContactActivityModel[]
     */
    public function getContactMailingListActivity(int $contactId, int $limit = null, array|int $mailingListId = null): array
    {
        $condition = ['contactId' => $contactId];

        if ($mailingListId !== null) {
            $condition['mailingListId'] = $mailingListId;
        }

        $results = (new Query())
            ->select(['contactMailingLists.*', 'contacts.email', 'elements.dateDeleted', 'content.title'])
            ->from(['contactMailingLists' => ContactMailingListRecord::tableName()])
            ->innerJoin(['contacts' => ContactRecord::tableName()], '[[contacts.id]] = [[contactId]]')
            ->innerJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[mailingListId]]')
            ->innerJoin(['content' => Table::CONTENT], '[[content.elementId]] = [[mailingListId]]')
            ->where($condition)
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->all();

        $contactMailingLists = [];

        foreach ($results as $result) {
            $contactMailingList = new ContactMailingListModel();
            $contactMailingList->setAttributes($result, false);
            $contactMailingList->contactEmail = $result['email'];
            $contactMailingList->contactReportUrl = UrlHelper::cpUrl('mailinglists/reports/contacts/' . $result['contactId']);
            $contactMailingList->mailingListTitle = $result['title'];

            if ($result['dateDeleted'] === null) {
                $contactMailingList->mailingListReportUrl = UrlHelper::cpUrl('campaign/reports/mailinglists/' . $result['mailingListId']);
            }

            $contactMailingLists[] = $contactMailingList;
        }

        // Return contact activity
        return $this->getActivity($contactMailingLists, null, $limit);
    }

    /**
     * Returns mailing lists report data.
     */
    public function getMailingListsReportData(int $siteId = null): array
    {
        // Get all mailing lists in all sites
        $mailingLists = MailingListElement::find()
            ->siteId($siteId)
            ->all();

        // Get data
        $data['mailingLists'] = $mailingLists;
        $data['subscribed'] = 0;
        $data['unsubscribed'] = 0;
        $data['complained'] = 0;
        $data['bounced'] = 0;

        foreach ($mailingLists as $mailingList) {
            $data['subscribed'] += $mailingList->getSubscribedCount();
            $data['unsubscribed'] += $mailingList->getUnsubscribedCount();
            $data['complained'] += $mailingList->getComplainedCount();
            $data['bounced'] += $mailingList->getBouncedCount();
        }

        return $data;
    }

    /**
     * Returns mailing list report data.
     */
    public function getMailingListReportData(int $mailingListId): array
    {
        // Get mailing list
        $data = ['mailingList' => Campaign::$plugin->mailingLists->getMailingListById($mailingListId)];

        // Get sendouts
        $data['sendouts'] = SendoutElement::find()
            ->mailingListId($mailingListId)
            ->orderBy(['sendDate' => SORT_ASC])
            ->all();

        // Get first contact mailing list
        $contactMailingListRecord = ContactMailingListRecord::find()
            ->where(['mailingListId' => $mailingListId])
            ->orderBy(['dateCreated' => SORT_ASC])
            ->one();

        // Check if chart exists
        $data['hasChart'] = ($contactMailingListRecord !== null);

        return $data;
    }

    /**
     * Returns mailing list chart data.
     */
    public function getMailingListChartData(int $mailingListId, string $interval = 'days'): array
    {
        return $this->getChartData(
            ContactMailingListRecord::class,
            ['mailingListId' => $mailingListId],
            ContactMailingListModel::INTERACTIONS,
            $interval
        );
    }

    /**
     * Returns mailing list contact activity.
     *
     * @return ContactActivityModel[]
     */
    public function getMailingListContactActivity(int $mailingListId, string $interaction = null, int $limit = null): array
    {
        $condition = ['mailingListId' => $mailingListId];

        // If no interaction was specified then set check for any interaction that is not null
        $interactionCondition = [
            'not',
            $interaction ? ['contactMailingLists.' . $interaction => null] : [
                'or',
                [
                    'contactMailingLists.subscribed' => null,
                    'contactMailingLists.unsubscribed' => null,
                    'contactMailingLists.complained' => null,
                    'contactMailingLists.bounced' => null,
                ],
            ],
        ];

        $results = (new Query())
            ->select(['contactMailingLists.*', 'contacts.email'])
            ->from(['contactMailingLists' => ContactMailingListRecord::tableName()])
            ->innerJoin(['contacts' => ContactRecord::tableName()], '[[contacts.id]] = [[contactId]]')
            ->where($condition)
            ->andWhere($interactionCondition)
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->all();

        $contactMailingLists = [];

        foreach ($results as $result) {
            $contactMailingList = new ContactMailingListModel();
            $contactMailingList->setAttributes($result, false);
            $contactMailingList->contactEmail = $result['email'];
            $contactMailingList->contactReportUrl = UrlHelper::cpUrl('campaign/reports/contacts/' . $result['contactId']);
            $contactMailingLists[] = $contactMailingList;
        }

        // Return contact activity
        return $this->getActivity($contactMailingLists, $interaction, $limit);
    }

    /**
     * Returns mailing list locations.
     */
    public function getMailingListLocations(int $mailingListId, int $limit = null): array
    {
        // Get mailing list
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            return [];
        }

        // Return locations of contact mailing lists
        return $this->getLocations(ContactMailingListRecord::class, ['and', ['mailingListId' => $mailingListId], ['not', ['subscribed' => null]]], $mailingList->getSubscribedCount(), $limit);
    }

    /**
     * Returns mailing list devices.
     */
    public function getMailingListDevices(int $mailingListId, bool $detailed = false, int $limit = null): array
    {
        // Get mailing list
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            return [];
        }

        // Return device, os and client of contact mailing lists
        return $this->getDevices(ContactMailingListRecord::class, ['and', ['mailingListId' => $mailingListId], ['not', ['subscribed' => null]]], $detailed, $mailingList->getSubscribedCount(), $limit);
    }

    /**
     * Anonymizes campaign reports.
     *
     * @since 2.6.0
     */
    public function anonymize(): void
    {
        /** @var ContactCampaignRecord[] $contactCampaignRecords */
        $contactCampaignRecords = ContactCampaignRecord::find()->all();

        foreach ($contactCampaignRecords as $contactCampaignRecord) {
            $contactCampaignRecord->opened = null;
            $contactCampaignRecord->clicked = null;
            $contactCampaignRecord->opens = 0;
            $contactCampaignRecord->clicks = 0;
            $contactCampaignRecord->links = null;
            $contactCampaignRecord->save();
        }
    }

    /**
     * Syncs campaign reports.
     *
     * @since 2.4.0
     */
    public function sync(): void
    {
        /** @var CampaignRecord[] $campaignRecords */
        $campaignRecords = CampaignRecord::find()->all();

        foreach ($campaignRecords as $campaignRecord) {
            $campaignRecord->recipients = $this->getCampaignColumnCount($campaignRecord->id, 'sent');
            $campaignRecord->opened = $this->getCampaignColumnCount($campaignRecord->id, 'opened');
            $campaignRecord->clicked = $this->getCampaignColumnCount($campaignRecord->id, 'clicked');
            $campaignRecord->unsubscribed = $this->getCampaignColumnCount($campaignRecord->id, 'unsubscribed');
            $campaignRecord->complained = $this->getCampaignColumnCount($campaignRecord->id, 'complained');
            $campaignRecord->bounced = $this->getCampaignColumnCount($campaignRecord->id, 'bounced');
            $campaignRecord->opens = $this->getCampaignColumnSum($campaignRecord->id, 'opens');
            $campaignRecord->clicks = $this->getCampaignColumnSum($campaignRecord->id, 'clicks');
            $campaignRecord->save();
        }
    }

    /**
     * Returns chart data.
     */
    private function getChartData(string $recordClass, array $condition, array $interactions, string $interval): array
    {
        $data = [];

        // Get date time format ensuring interval is valid
        $format = $this->getDateTimeFormat($interval);

        if ($format === null) {
            return [];
        }

        // Get first record
        /** @var ActiveRecord $recordClass */
        /** @var ActiveRecord|null $record */
        $record = $recordClass::find()
            ->where($condition)
            ->orderBy(['dateCreated' => SORT_ASC])
            ->one();

        if ($record === null) {
            return [];
        }

        // Get start and end date times
        $startDateTime = DateTimeHelper::toDateTime($record->dateCreated)->modify('-1 ' . $interval);
        $endDateTime = clone $startDateTime;
        $endDateTime->modify('+' . $this->getMaxIntervals($interval) . ' ' . $interval);

        $fields = [];

        foreach ($record->fields() as $field) {
            $fields[] = 'MIN([[' . $field . ']]) AS ' . $field;
        }

        // Get records within date range
        $records = $recordClass::find()
            ->select(array_merge(['contactId'], $fields))
            ->where($condition)
            ->andWhere(Db::parseDateParam('dateCreated', $endDateTime, '<'))
            ->orderBy(['dateCreated' => SORT_ASC])
            ->groupBy('contactId')
            ->all();

        // Get activity
        $activity = [];

        /** @var DateTime|null $lastInteraction */
        $lastInteraction = null;

        foreach ($records as $record) {
            foreach ($interactions as $interaction) {
                // If the interaction exists for the record
                if ($record->{$interaction}) {
                    // Convert interaction to datetime
                    $interactionDateTime = DateTimeHelper::toDateTime($record->{$interaction});

                    // If interaction datetime is before the specified end time
                    if ($interactionDateTime < $endDateTime) {
                        // Update last interaction if null or if interaction dateTime is greater than it
                        if ($lastInteraction === null || $interactionDateTime > $lastInteraction) {
                            $lastInteraction = $interactionDateTime;
                        }

                        // Get interaction dateTime as timestamp in the correct format
                        $index = DateTimeHelper::toDateTime($interactionDateTime->format($format))->getTimestamp();

                        $activity[$interaction][$index] = isset($activity[$interaction][$index]) ? $activity[$interaction][$index] + 1 : 1;
                    }
                }
            }
        }

        // Set data
        $data['startDateTime'] = $startDateTime;
        $data['interval'] = $interval;
        $data['format'] = $format;
        $data['interactions'] = $interactions;
        $data['activity'] = $activity;
        $data['lastInteraction'] = $lastInteraction;

        return $data;
    }

    /**
     * Returns activity.
     *
     * @return ContactActivityModel[]
     */
    private function getActivity(array $models, string $interaction = null, int $limit = null): array
    {
        $activity = [];

        foreach ($models as $model) {
            /** @var ContactCampaignModel|ContactMailingListModel $model */
            $interactionTypes = ($interaction !== null && in_array($interaction, $model::INTERACTIONS)) ? [$interaction] : $model::INTERACTIONS;

            foreach ($interactionTypes as $key => $interactionType) {
                if ($model->{$interactionType} !== null) {
                    $contactActivityModel = new ContactActivityModel([
                        'model' => $model,
                        'interaction' => $interactionType,
                        'date' => $model->{$interactionType},
                        'links' => $interactionType == 'clicked' ? $model->getLinks() : [],
                        'count' => 1,
                    ]);

                    if ($interactionType == 'opened') {
                        $contactActivityModel->count = $model->opens;
                    } elseif ($interactionType == 'clicked') {
                        $contactActivityModel->count = $model->clicks;
                    }

                    if (!empty($model->sourceType)) {
                        switch ($model->sourceType) {
                            case 'import':
                                $contactActivityModel->sourceUrl = UrlHelper::cpUrl('campaign/contacts/import/' . $model->source);
                                break;
                            case 'user':
                                $path = (Craft::$app->getEdition() === Craft::Pro && $model->source) ? 'users/' . $model->source : 'myaccount';
                                $contactActivityModel->sourceUrl = UrlHelper::cpUrl($path);
                                break;
                            default:
                                $contactActivityModel->sourceUrl = $model->source;
                        }
                    }

                    $activity[$contactActivityModel->date->getTimestamp() . '-' . $key . '-' . $interactionType . '-' . $model->contactId] = $contactActivityModel;
                }
            }
        }

        // Sort by key in reverse order
        krsort($activity);

        // Enforce the limit
        if ($limit !== null) {
            $activity = array_slice($activity, 0, $limit);
        }

        return $activity;
    }

    /**
     * Returns locations.
     */
    private function getLocations(string $recordClass, array $condition, int $total, int $limit = null): array
    {
        $results = [];
        $fields = ['country', 'MAX([[geoIp]]) AS geoIp'];

        /** @var ActiveRecord $recordClass */
        $query = ContactRecord::find()
            ->select(array_merge($fields, ['COUNT(*) AS count']))
            ->groupBy('country');

        if ($recordClass != ContactRecord::class) {
            $contactIds = $recordClass::find()
                ->select('contactId')
                ->where($condition)
                ->groupBy('contactId')
                ->column();

            $query->andWhere([ContactRecord::tableName() . '.id' => $contactIds]);
        }

        /** @var ContactRecord[]|ContactCampaignRecord[]|ContactMailingListRecord[] $records */
        $records = $query->all();

        // Set default unknown count
        $unknownCount = 0;

        foreach ($records as $record) {
            // Increment unknown results
            if (empty($record->country)) {
                $unknownCount++;
                continue;
            }

            $result = $record->toArray();
            $result['count'] = $record->count;

            // Decode GeoIp
            $geoIp = $record->geoIp ? Json::decodeIfJson($record->geoIp) : [];

            $result['countryCode'] = strtolower($geoIp['countryCode'] ?? '');
            $result['countRate'] = $total ? NumberHelper::floorOrOne($record->count / $total * 100) : 0;
            $results[] = $result;
        }

        // If there is an unknown count then add it to results
        if ($unknownCount > 0) {
            $results[] = [
                'country' => '',
                'countryCode' => '',
                'count' => $unknownCount,
                'countRate' => $total ? NumberHelper::floorOrOne($unknownCount / $total * 100) : 0,
            ];
        }

        // Sort results
        usort($results, [$this, 'compareCount']);

        // Enforce the limit
        if ($limit !== null) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * Returns devices.
     */
    private function getDevices(string $recordClass, array $condition, bool $detailed, int $total, int $limit = null): array
    {
        $results = [];
        $fields = $detailed ? ['device', 'os', 'client'] : ['device'];

        /** @var ActiveRecord $recordClass */
        $query = ContactRecord::find()
            ->select(array_merge($fields, ['COUNT(*) AS count']))
            ->where(['not', ['device' => null]])
            ->groupBy($fields);

        if ($recordClass != ContactRecord::class) {
            $contactIds = $recordClass::find()
                ->select('contactId')
                ->where($condition)
                ->groupBy('contactId')
                ->column();

            $query->andWhere([ContactRecord::tableName() . '.id' => $contactIds]);
        }

        /** @var ContactRecord[]|ContactCampaignRecord[]|ContactMailingListRecord[] $records */
        $records = $query->all();

        foreach ($records as $record) {
            $result = $record->toArray();
            $result['count'] = $record->count;
            $result['countRate'] = $total ? NumberHelper::floorOrOne($record->count / $total * 100) : 0;
            $results[] = $result;
        }

        // Sort results
        usort($results, [$this, 'compareCount']);

        // Enforce the limit
        if ($limit !== null) {
            $results = array_slice($results, 0, $limit);
        }

        return $results;
    }

    /**
     * Returns date time format.
     */
    private function getDateTimeFormat(string $interval): ?string
    {
        /**
         * @see DATE_ATOM
         */
        $formats = [
            'minutes' => 'Y-m-d\TH:iP',
            'hours' => 'Y-m-d\TH:00P',
            'days' => 'Y-m-d',
            'months' => 'Y-m',
            'years' => 'Y',
        ];

        return $formats[$interval] ?? null;
    }

    /**
     * Compares two count values by count descending.
     */
    private function compareCount(array $a, array $b): int
    {
        return (int)$a['count'] < (int)$b['count'] ? 1 : -1;
    }

    private function getCampaignColumnCount(int $campaignId, string $column): int
    {
        $count = ContactCampaignRecord::find()
            ->where(['campaignId' => $campaignId])
            ->andWhere(['not', [$column => null]])
            ->count();

        return $count ?? 0;
    }

    private function getCampaignColumnSum(int $campaignId, string $column): int
    {
        $sum = ContactCampaignRecord::find()
            ->where(['campaignId' => $campaignId])
            ->sum($column);

        return $sum ?? 0;
    }
}
