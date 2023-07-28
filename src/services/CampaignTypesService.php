<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\base\MemoizableArray;
use craft\db\Table;
use craft\events\ConfigEvent;
use craft\events\DeleteSiteEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\queue\Queue;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\events\CampaignTypeEvent;
use putyourlightson\campaign\helpers\ProjectConfigDataHelper;
use putyourlightson\campaign\jobs\ResaveElementsJob;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\records\CampaignTypeRecord;
use Throwable;
use yii\base\Exception;
use yii\web\NotFoundHttpException;

/**
 * @property-read CampaignTypeModel[] $editableCampaignTypes
 * @property-read CampaignTypeModel[] $allCampaignTypes
 */
class CampaignTypesService extends Component
{
    /**
     * @event CampaignTypeEvent
     */
    public const EVENT_BEFORE_SAVE_CAMPAIGN_TYPE = 'beforeSaveCampaignType';

    /**
     * @event CampaignTypeEvent
     */
    public const EVENT_AFTER_SAVE_CAMPAIGN_TYPE = 'afterSaveCampaignType';

    /**
     * @event CampaignTypeEvent
     */
    public const EVENT_BEFORE_DELETE_CAMPAIGN_TYPE = 'beforeDeleteCampaignType';

    /**
     * @event CampaignTypeEvent
     */
    public const EVENT_AFTER_DELETE_CAMPAIGN_TYPE = 'afterDeleteCampaignType';

    /**
     * @since 1.12.0
     */
    public const CONFIG_CAMPAIGNTYPES_KEY = 'campaign.campaignTypes';

    /**
     * @var MemoizableArray<CampaignTypeModel>|null
     * @see _campaignTypes()
     */
    private ?MemoizableArray $_campaignTypes = null;

    /**
     * Returns all campaign types.
     *
     * @return CampaignTypeModel[]
     */
    public function getAllCampaignTypes(): array
    {
        return $this->_campaignTypes()->all();
    }

    /**
     * Returns all editable campaign types.
     *
     * @return CampaignTypeModel[]
     */
    public function getEditableCampaignTypes(): array
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $this->getAllCampaignTypes();
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return [];
        }

        return ArrayHelper::where($this->getAllCampaignTypes(),
            function (CampaignTypeModel $campaignType) use ($user) {
                return $user->can('campaign:campaigns:' . $campaignType->uid);
            },
            true, true, false
        );
    }

    /**
     * Returns a campaign type by ID.
     */
    public function getCampaignTypeById(int $campaignTypeId): ?CampaignTypeModel
    {
        return $this->_campaignTypes()->firstWhere('id', $campaignTypeId);
    }

    /**
     * Returns a campaign type by UID.
     */
    public function getCampaignTypeByUid(string $uid): ?CampaignTypeModel
    {
        return $this->_campaignTypes()->firstWhere('uid', $uid, true);
    }

    /**
     * Returns a campaign type by handle.
     */
    public function getCampaignTypeByHandle(string $campaignTypeHandle): ?CampaignTypeModel
    {
        return $this->_campaignTypes()->firstWhere('handle', $campaignTypeHandle, true);
    }

    /**
     * Saves a campaign type.
     */
    public function saveCampaignType(CampaignTypeModel $campaignType, bool $runValidation = true): bool
    {
        $isNew = $campaignType->id === null;

        // Fire a before event
        $event = new CampaignTypeEvent([
            'campaignType' => $campaignType,
            'isNew' => $isNew,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE_CAMPAIGN_TYPE, $event);

        if (!$event->isValid) {
            return false;
        }

        if ($runValidation && !$campaignType->validate()) {
            Campaign::$plugin->log('Campaign type not saved due to validation error.');

            return false;
        }

        // Ensure the campaign type has a UID
        if ($isNew) {
            $campaignType->uid = StringHelper::UUID();
        } elseif (!$campaignType->uid) {
            /** @var CampaignTypeRecord|null $campaignTypeRecord */
            $campaignTypeRecord = CampaignTypeRecord::find()
                ->andWhere([CampaignTypeRecord::tableName() . '.id' => $campaignType->id])
                ->one();

            if ($campaignTypeRecord === null) {
                throw new NotFoundHttpException('No campaign type exists with the ID ' . $campaignType->id);
            }

            $campaignType->uid = $campaignTypeRecord->uid;
        }

        // Get config data
        $configData = ProjectConfigDataHelper::getCampaignTypeData($campaignType);

        // Save it to project config
        $path = self::CONFIG_CAMPAIGNTYPES_KEY . '.' . $campaignType->uid;
        Craft::$app->getProjectConfig()->set($path, $configData);

        // Set the ID on the campaign type
        if ($isNew) {
            $campaignType->id = Db::idByUid(CampaignTypeRecord::tableName(), $campaignType->uid);
        }

        return true;
    }

    /**
     * Handles a changed campaign type.
     */
    public function handleChangedCampaignType(ConfigEvent $event): void
    {
        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure all sites and fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $campaignTypeRecord = CampaignTypeRecord::findOne(['uid' => $uid]);
            $isNew = $campaignTypeRecord === null;

            if ($isNew) {
                $campaignTypeRecord = new CampaignTypeRecord();
            }

            // Save old site ID for resaving elements later
            $oldSiteId = $campaignTypeRecord->siteId;

            $campaignTypeRecord->setAttributes($data, false);
            $campaignTypeRecord->siteId = Db::idByUid(Table::SITES, $data['siteUid']);
            $campaignTypeRecord->uid = $uid;

            $fieldsService = Craft::$app->getFields();

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $campaignTypeRecord->fieldLayoutId;
                $layout->type = CampaignElement::class;
                $layout->uid = key($data['fieldLayouts']);
                $fieldsService->saveLayout($layout, false);
                $campaignTypeRecord->fieldLayoutId = $layout->id;
            } elseif ($campaignTypeRecord->fieldLayoutId) {
                // Delete the field layout
                $fieldsService->deleteLayoutById($campaignTypeRecord->fieldLayoutId);
                $campaignTypeRecord->fieldLayoutId = null;
            }

            // Save the campaign type
            if (!$campaignTypeRecord->save(false)) {
                throw new Exception('Couldn’t save campaign type.');
            }

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();
            throw $exception;
        }

        // Clear caches
        $this->_campaignTypes = null;

        // Get campaign type model
        $campaignType = $this->getCampaignTypeById($campaignTypeRecord->id);

        if (!$isNew) {
            /** @var Queue $queue */
            $queue = Craft::$app->getQueue();

            // Re-save the campaigns in this campaign type
            $queue->push(new ResaveElementsJob([
                'description' => Craft::t('campaign', 'Resaving {type} campaigns', [
                    'type' => $campaignType->name,
                    'site' => $campaignType->getSite()->name,
                ]),
                'elementType' => CampaignElement::class,
                'criteria' => [
                    'siteId' => $oldSiteId,
                    'campaignTypeId' => $campaignType->id,
                    'status' => null,
                ],
                'siteId' => $campaignType->siteId,
            ]));
        }

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
                'isNew' => $isNew,
            ]));
        }

        // Invalidate element caches
        Craft::$app->getElements()->invalidateCachesForElementType(CampaignElement::class);
    }

    /**
     * Deletes a campaign type by its ID.
     */
    public function deleteCampaignTypeById(int $campaignTypeId): bool
    {
        $campaignType = $this->getCampaignTypeById($campaignTypeId);

        if ($campaignType === null) {
            return false;
        }

        return $this->deleteCampaignType($campaignType);
    }

    /**
     * Deletes a campaign type.
     */
    public function deleteCampaignType(CampaignTypeModel $campaignType): bool
    {
        // Fire a before event
        $event = new CampaignTypeEvent([
            'campaignType' => $campaignType,
        ]);
        $this->trigger(self::EVENT_BEFORE_DELETE_CAMPAIGN_TYPE, $event);

        if (!$event->isValid) {
            return false;
        }

        // Remove it from project config
        $path = self::CONFIG_CAMPAIGNTYPES_KEY . '.' . $campaignType->uid;
        Craft::$app->getProjectConfig()->remove($path);

        return true;
    }

    /**
     * Handles a deleted campaign type.
     */
    public function handleDeletedCampaignType(ConfigEvent $event): void
    {
        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];

        $campaignTypeRecord = CampaignTypeRecord::findOne(['uid' => $uid]);

        if ($campaignTypeRecord == null) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Delete the campaigns
            $campaigns = CampaignElement::find()
                ->campaignTypeId($campaignTypeRecord->id)
                ->all();

            foreach ($campaigns as $campaign) {
                Craft::$app->getElements()->deleteElement($campaign);
            }

            // Delete the field layout
            if ($campaignTypeRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($campaignTypeRecord->fieldLayoutId);
            }

            // Delete the campaign type
            $campaignTypeRecord->delete();

            $transaction->commit();
        } catch (Throwable $exception) {
            $transaction->rollBack();

            throw $exception;
        }

        // Clear caches
        $this->_campaignTypes = null;

        // Get campaign type model
        $campaignType = $this->getCampaignTypeById($campaignTypeRecord->id);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
            ]));
        }

        // Invalidate element caches
        Craft::$app->getElements()->invalidateCachesForElementType(CampaignElement::class);
    }

    /**
     * Handles a deleted site.
     */
    public function handleDeletedSite(DeleteSiteEvent $event): void
    {
        $siteUid = $event->site->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $campaignTypes = $projectConfig->get(self::CONFIG_CAMPAIGNTYPES_KEY);

        if (is_array($campaignTypes)) {
            foreach ($campaignTypes as $campaignTypeUid => $campaignType) {
                if ($campaignTypeUid == $siteUid) {
                    $this->deleteCampaignType($campaignType);
                }
            }
        }
    }

    /**
     * Returns a memoizable array of all campaign types.
     *
     * @return MemoizableArray<CampaignTypeModel>
     */
    private function _campaignTypes(): MemoizableArray
    {
        if (!isset($this->_campaignTypes)) {
            $campaignTypes = [];

            /** @var CampaignTypeRecord[] $campaignTypeRecords */
            $campaignTypeRecords = CampaignTypeRecord::find()
                ->innerJoinWith('site')
                ->orderBy(['name' => SORT_ASC])
                ->all();

            foreach ($campaignTypeRecords as $campaignTypeRecord) {
                $campaignType = new CampaignTypeModel();
                $campaignType->setAttributes($campaignTypeRecord->getAttributes(), false);
                $campaignTypes[] = $campaignType;
            }

            $this->_campaignTypes = new MemoizableArray($campaignTypes);
        }

        return $this->_campaignTypes;
    }
}
