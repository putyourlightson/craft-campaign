<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Model;
use craft\db\ActiveRecord;
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
use putyourlightson\campaign\models\LinkModel;
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\models\ContactMailingListModel;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;

/**
 * ReportsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property array $mailingListsChartData
 * @property array $contactsReportData
 * @property array $campaignsReportData
 * @property array $campaignsChartData
 * @property array $mailingListsReportData
 */
class ReportsService extends Component
{
    // Constants
    // =========================================================================

    const MIN_INTERVALS = 5;

    // Public Methods
    // =========================================================================

    /**
     * Returns max intervals
     *
     * @param string $interval
     * @return int
     */
    public function getMaxIntervals(string $interval): int
    {
        $maxIntervals = ['minutes' => 60, 'hours' => 24, 'days' => 14, 'months'=> 12, 'years' => 10];

        return $maxIntervals[$interval] ?? 12;
    }

    /**
     * Returns campaigns report data
     *
     * @return array
     */
    public function getCampaignsReportData(): array
    {
        // Get all sent campaigns
        $data['campaigns'] = CampaignElement::find()
            ->status(CampaignElement::STATUS_SENT)
            ->all();

        // Get data
        $data['recipients'] = 0;
        $data['opened'] = 0;
        $data['clicked'] = 0;

        /** @var CampaignElement $campaign */
        foreach ($data['campaigns'] as $campaign) {
            $data['recipients'] += $campaign->recipients;
            $data['opened'] += $campaign->opened;
            $data['clicked'] += $campaign->clicked;
        }

        $data['clickThroughRate'] = $data['opened'] ? NumberHelper::floorOrOne($data['clicked'] / $data['opened'] * 100) : 0;

        // Get sendouts count
        $data['sendouts'] = SendoutElement::find()->count();

        return $data;
    }

    /**
     * Returns campaign report data
     *
     * @param int
     *
     * @return array
     */
    public function getCampaignReportData(int $campaignId): array
    {
        // Get campaign
        $data['campaign'] = Campaign::$plugin->campaigns->getCampaignById($campaignId);

        // Get sendouts
        $data['sendouts'] = SendoutElement::find()
            ->campaignId($campaignId)
            ->orderBy(['sendDate' => SORT_ASC])
            ->all();

        // Get date first sent
        $contactCampaignRecord = ContactCampaignRecord::find()
            ->where(['campaignId' => $campaignId])
            ->orderBy(['dateCreated' => SORT_ASC])
            ->limit(1)
            ->one();

        /** @var ContactCampaignRecord $contactCampaignRecord */
        $data['dateFirstSent'] = $contactCampaignRecord === null ? null : DateTimeHelper::toDateTime($contactCampaignRecord->dateCreated);

        // Check if chart exists
        $data['hasChart'] = count($this->getCampaignContactActivity($campaignId, 'opened', 1)) > 0;

        return $data;
    }

    /**
     * Returns campaign chart data
     *
     * @param int
     * @param string|null
     *
     * @return array
     */
    public function getCampaignChartData(int $campaignId, string $interval = null): array
    {
        $interval = $interval ?? 'hours';

        return $this->_getChartData(
            ContactCampaignRecord::class,
            ['campaignId' => $campaignId],
            ContactCampaignModel::INTERACTIONS,
            $interval
        );
    }

    /**
     * Returns campaign contact activity
     *
     * @param int
     * @param string|null
     * @param int|null
     *
     * @return ContactActivityModel[]
     */
    public function getCampaignContactActivity(int $campaignId, string $interaction = null, int $limit = null): array
    {
        $limit = $limit ?? 100;

        // Get contact campaigns
        $query = ContactCampaignRecord::find()
            ->where(['campaignId' => $campaignId])
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->limit($limit);

        if ($interaction !== null) {
            $query->andWhere(['not', [ContactCampaignRecord::tableName().'.'.$interaction => null]]);
        }

        $contactCampaignRecords = $query->all();

        $contactCampaignModels = ContactCampaignModel::populateModels($contactCampaignRecords, false);

        // Return contact activity
        return $this->_getActivity($contactCampaignModels, $interaction, $limit);
    }

    /**
     * Returns campaign links
     *
     * @param int
     * @param int|null
     *
     * @return LinkModel[]
     */
    public function getCampaignLinks(int $campaignId, int $limit = null): array
    {
        $limit = $limit ?? 100;

        // Get campaign links
        $linkRecords = LinkRecord::find()
            ->where(['campaignId' => $campaignId])
            ->orderBy(['clicked' => SORT_DESC, 'clicks' => SORT_DESC])
            ->limit($limit)
            ->all();

        return LinkModel::populateModels($linkRecords, false);
    }

    /**
     * Returns campaign locations
     *
     * @param int
     * @param int|null
     *
     * @return array
     */
    public function getCampaignLocations(int $campaignId, int $limit = null): array
    {
        $limit = $limit ?? 100;

        // Get campaign
        $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

        if ($campaign === null) {
            return [];
        }

        // Return locations of contact campaigns
        return $this->_getLocations(ContactCampaignRecord::tableName(), ['and', ['campaignId' => $campaignId], ['not', ['opened' => null]]], $campaign->opened, $limit);
    }

    /**
     * Returns campaign devices
     *
     * @param int
     * @param bool|null
     * @param int|null
     *
     * @return array
     */
    public function getCampaignDevices(int $campaignId, bool $detailed = null, int $limit = null): array
    {
        $detailed = $detailed ?? false;
        $limit = $limit ?? 100;

        // Get campaign
        $campaign = Campaign::$plugin->campaigns->getCampaignById($campaignId);

        if ($campaign === null) {
            return [];
        }

        // Return device, os and client of contact campaigns
        return $this->_getDevices(ContactCampaignRecord::tableName(), ['and', ['campaignId' => $campaignId], ['not', ['opened' => null]]], $detailed, $campaign->opened, $limit);
    }

    /**
     * Returns contacts report data
     *
     * @return array
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

        $data['total'] = $count = ContactMailingListRecord::find()->count();

        return $data;
    }

    /**
     * Returns contacts activity
     *
     * @param int|null
     *
     * @return array
     */
    public function getContactsActivity(int $limit = null): array
    {
        $limit = $limit ?? 100;

        // Get recently active contacts
        return ContactElement::find()
            ->orderBy(['lastActivity' => SORT_DESC])
            ->limit($limit)
            ->all();
    }

    /**
     * Returns contacts locations
     *
     * @param int|null
     *
     * @return array
     */
    public function getContactsLocations(int $limit = null): array
    {
        $limit = $limit ?? 100;

        // Get total active contacts
        $total = ContactElement::find()
            ->where(['complained' => null, 'bounced' => null])
            ->count();

        // Return locations of contacts
        return $this->_getLocations(ContactRecord::tableName(), [], $total, $limit);
    }

    /**
     * Returns contacts devices
     *
     * @param bool|null
     * @param int|null
     *
     * @return array
     */
    public function getContactsDevices(bool $detailed = null, int $limit = null): array
    {
        $detailed = $detailed ?? false;
        $limit = $limit ?? 100;$limit = $limit ?? 100;

        // Get total active contacts
        $total = ContactElement::find()
            ->where(['complained' => null, 'bounced' => null])
            ->count();

        // Return device, os and client of contacts
        return $this->_getDevices(ContactRecord::tableName(), [], $detailed, $total, $limit);
    }

    /**
     * Returns contact campaigns
     *
     * @param int
     * @param int|null
     * @param int|int[]|null
     *
     * @return ContactActivityModel[]
     */
    public function getContactCampaignActivity(int $contactId, int $limit = null, $campaignId = null): array
    {
        $limit = $limit ?? 100;

        $conditions = ['contactId' => $contactId];

        if ($campaignId !== null) {
            $conditions['campaignId'] = $campaignId;
        }

        // Get contact campaigns
        $contactCampaignRecords = ContactCampaignRecord::find()
            ->where($conditions)
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->limit($limit)
            ->all();

        $contactCampaignModels = ContactCampaignModel::populateModels($contactCampaignRecords, false);

        // Return contact activity
        return $this->_getActivity($contactCampaignModels, null, $limit);
    }

    /**
     * Returns contact mailing list activity
     *
     * @param int
     * @param int|null
     * @param int|int[]|null
     *
     * @return ContactActivityModel[]
     */
    public function getContactMailingListActivity(int $contactId, int $limit = null, $mailingListId = null): array
    {
        $limit = $limit ?? 100;

        $conditions = ['contactId' => $contactId];

        if ($mailingListId !== null) {
            $conditions['mailingListId'] = $mailingListId;
        }

        // Get mailing lists
        $contactMailingListRecords = ContactMailingListRecord::find()
            ->where($conditions)
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->limit($limit)
            ->all();

        $contactMailingListModels = ContactMailingListModel::populateModels($contactMailingListRecords, false);

        // Return contact activity
        return $this->_getActivity($contactMailingListModels, null, $limit);
    }

    /**
     * Returns mailing lists report data
     *
     * @return array
     */
    public function getMailingListsReportData(): array
    {
        // Get all mailing lists
        $data['mailingLists'] = MailingListElement::findAll();

        // Get data
        $data['subscribed'] = 0;
        $data['unsubscribed'] = 0;
        $data['complained'] = 0;
        $data['bounced'] = 0;

        /** @var MailingListElement $mailingList */
        foreach ($data['mailingLists'] as $mailingList) {
            $data['subscribed'] += $mailingList->getSubscribedCount();
            $data['unsubscribed'] += $mailingList->getUnsubscribedCount();
            $data['complained'] += $mailingList->getComplainedCount();
            $data['bounced'] += $mailingList->getBouncedCount();
        }

        return $data;
    }

    /**
     * Returns mailing list report data
     *
     * @param int
     *
     * @return array
     */
    public function getMailingListReportData(int $mailingListId): array
    {
        // Get mailing list
        $data['mailingList'] = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

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
     * Returns mailing list chart data
     *
     * @param int
     * @param string|null
     *
     * @return array
     */
    public function getMailingListChartData(int $mailingListId, string $interval = null): array
    {
        $interval = $interval ?? 'days';

        return $this->_getChartData(
            ContactMailingListRecord::class,
            ['mailingListId' => $mailingListId],
            ContactMailingListModel::INTERACTIONS,
            $interval
        );
    }

    /**
     * Returns mailing list contact activity
     *
     * @param int
     * @param string|null
     * @param int|null
     *
     * @return ContactMailingListModel[]
     */
    public function getMailingListContactActivity(int $mailingListId, string $interaction = null, int $limit = null): array
    {
        $limit = $limit ?? 100;

        // Get contact mailing lists
        $contactMailingListRecords = ContactMailingListRecord::find()
            ->where(['mailingListId' => $mailingListId])
            ->orderBy(['dateUpdated' => SORT_DESC])
            ->limit($limit)
            ->all();

        $contactMailingListModels = ContactMailingListModel::populateModels($contactMailingListRecords, false);

        // Return contact activity
        return $this->_getActivity($contactMailingListModels, $interaction, $limit);
    }

    /**
     * Returns mailing list locations
     *
     * @param int
     * @param int|null
     *
     * @return array
     */
    public function getMailingListLocations(int $mailingListId, int $limit = null): array
    {
        $limit = $limit ?? 100;

        // Get mailing list
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            return [];
        }

        // Return locations of contact mailing lists
        return $this->_getLocations(ContactMailingListRecord::tableName(), ['and', ['mailingListId' => $mailingListId], ['not', ['subscribed' => null]]], $mailingList->getSubscribedCount(), $limit);
    }

    /**
     * Returns mailing list devices
     *
     * @param int
     * @param bool|null
     * @param int|null
     *
     * @return array
     */
    public function getMailingListDevices(int $mailingListId, bool $detailed = null, int $limit = null): array
    {
        $detailed = $detailed ?? false;
        $limit = $limit ?? 100;

        // Get mailing list
        $mailingList = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        if ($mailingList === null) {
            return [];
        }

        // Return device, os and client of contact mailing lists
        return $this->_getDevices(ContactMailingListRecord::tableName(), ['and', ['mailingListId' => $mailingListId], ['not', ['subscribed' => null]]], $detailed, $mailingList->getSubscribedCount(), $limit);
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns chart data
     *
     * @param string $recordClass
     * @param array $condition
     * @param array $interactions
     * @param string $interval
     *
     * @return Model[]
     */
    private function _getChartData(string $recordClass, array $condition, array $interactions, string $interval): array
    {
        $data = [];

        // Get date time format ensuring interval is valid
        $format = $this->_getDateTimeFormat($interval);

        if ($format === null) {
            return $data;
        }

        // Get first record
        /** @var ActiveRecord $recordClass */
        $record = $recordClass::find()
            ->where($condition)
            ->orderBy(['dateCreated' => SORT_ASC])
            ->one();

        if ($record === null) {
            return $data;
        }

        /** @var ActiveRecord $record */
        // Get start and end date times
        $startDateTime = DateTimeHelper::toDateTime($record->dateCreated)->modify('-1 '.$interval);
        $endDateTime = clone $startDateTime;
        $endDateTime->modify('+'.$this->getMaxIntervals($interval).' '.$interval);

        // Get records within date range
        $records = $recordClass::find()
            ->where($condition)
            ->andWhere(Db::parseDateParam(
            $recordClass::tableName().'.dateCreated', $endDateTime, '<'
            ))
            ->orderBy(['dateCreated' => SORT_ASC])
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
                        if ($lastInteraction === null OR $interactionDateTime > $lastInteraction) {
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
     * Returns activity
     *
     * @param ContactCampaignModel[]|ContactMailingListModel[]
     * @param string|null
     * @param int|null
     *
     * @return ContactActivityModel[]
     */
    private function _getActivity(array $models, string $interaction = null, int $limit = null): array
    {
        $limit = $limit ?? 100;

        $activity = [];

        foreach ($models as $model) {
            /** @var ContactCampaignModel|ContactMailingListModel $model */
            $interactionTypes = ($interaction !== null AND in_array($interaction, $model::INTERACTIONS, true)) ? [$interaction] : $model::INTERACTIONS;

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
                    }
                    else if ($interactionType == 'clicked') {
                        $contactActivityModel->count = $model->clicks;
                    }

                    if (!empty($model->sourceType)) {
                        switch ($model->sourceType) {
                            case 'import':
                                $contactActivityModel->sourceUrl = UrlHelper::cpUrl('campaign/contacts/import/'.$model->source);
                                break;
                            case 'user':
                                $path = (Craft::$app->getEdition() === Craft::Pro AND $model->source) ? 'users/'.$model->source : 'myaccount';
                                $contactActivityModel->sourceUrl = UrlHelper::cpUrl($path);
                                break;
                            default:
                                $contactActivityModel->sourceUrl = $model->source;
                        }
                    }

                    $activity[$contactActivityModel->date->getTimestamp().'-'.$key.'-'.$interactionType] = $contactActivityModel;
                }
            }
        }

        // Sort by key in reverse order
        krsort($activity);

        // Enforce the limit
        $activity = array_slice($activity, 0, $limit);

        return $activity;
    }

    /**
     * Returns locations
     *
     * @param string
     * @param array
     * @param int
     * @param int|null
     *
     * @return array
     */
    private function _getLocations(string $table, array $conditions, int $total, int $limit = null): array
    {
        $limit = $limit ?? 100;

        $countArray = [];

        $query = (new Query())
            ->select(['country', 'MAX(geoIp) AS geoIp', 'COUNT(*) AS count'])
            ->from($table.' t')
            ->where($conditions)
            ->groupBy('country');

        if ($table !== ContactRecord::tableName()) {
            $query->innerJoin(ContactRecord::tableName().' contacts', '[[contacts.id]] = [[t.contactId]]');
        }

        $results = $query->all();

        // Set default unknown count
        $unknownCount = 0;

        foreach ($results as $key => &$result) {
            // Increment and unset unknown results
            if (empty($result['country'])) {
                $unknownCount += $result['count'];
                unset($results[$key]);
                continue;
            }

            // Decode GeoIp
            $geoIp = $result['geoIp'] ? Json::decodeIfJson($result['geoIp']) : [];

            $result['countryCode'] = strtolower($geoIp['countryCode'] ?? '');
            $result['countRate'] = $total ? NumberHelper::floorOrOne($result['count'] / $total * 100) : 0;

            $countArray[] = $result['count'];
        }

        // Unset variable reference to avoid possible side-effects
        unset($result);

        // If there is an unknown count then add it to results
        if ($unknownCount > 0) {
            $results[] = [
                'country' => '',
                'countryCode' => '',
                'count' => $unknownCount,
                'countRate' => $total ? NumberHelper::floorOrOne($unknownCount / $total * 100) : 0,
            ];
            $countArray[] = $unknownCount;
        }

        // Sort results by count array descending
        array_multisort($countArray, SORT_DESC, $results);

        // Enforce the limit
        $results = array_slice($results, 0, $limit);

        return $results;
    }

    /**
     * Returns devices
     *
     * @param string
     * @param array
     * @param bool
     * @param int
     * @param int|null
     *
     * @return array
     */
    private function _getDevices(string $table, array $conditions, bool $detailed, int $total, int $limit = null): array
    {
        $limit = $limit ?? 100;

        $countArray = [];

        $fields = $detailed ? ['device', 'os', 'client'] : ['device'];

        $query = (new Query())
            ->select(array_merge($fields, ['COUNT(*) AS count']))
            ->from($table.' t')
            ->where($conditions)
            ->andWhere(['not', ['device' => null]])
            ->groupBy($fields);

        if ($table !== ContactRecord::tableName()) {
            $query->innerJoin(ContactRecord::tableName().' contacts', '[[contacts.id]] = [[t.contactId]]');
        }

        $results = $query->all();

        foreach ($results as &$result) {
            $result['countRate'] = $total ? NumberHelper::floorOrOne($result['count'] / $total * 100) : 0;
            $countArray[] = $result['count'];
        }

        // Unset variable reference to avoid possible side-effects
        unset($result);

        // Sort results by count array descending
        array_multisort($countArray, SORT_DESC, $results);

        // Enforce the limit
        $results = array_slice($results, 0, $limit);

        return $results;
    }

    /**
     * Returns date time format
     *
     * @param string
     *
     * @return string|null
     */
    private function _getDateTimeFormat(string $interval)
    {
        $formats = [
            'minutes' => str_replace(':s', '', DATE_ATOM),
            'hours' => str_replace(['i', ':s'], ['00', ''], DATE_ATOM),
            'days' => 'Y-m-d',
            'months' => 'Y-m',
            'years' => 'Y',
        ];

        return $formats[$interval] ?? null;
    }
}
