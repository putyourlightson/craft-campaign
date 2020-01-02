<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Field;
use craft\db\Table;
use craft\events\ConfigEvent;
use craft\events\DeleteSiteEvent;
use craft\events\FieldEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use craft\queue\Queue;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\CampaignTypeEvent;
use putyourlightson\campaign\helpers\ProjectConfigDataHelper;
use putyourlightson\campaign\jobs\ResaveElementsJob;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\elements\CampaignElement;

use Craft;
use craft\base\Component;
use Throwable;
use yii\base\Exception;
use yii\web\NotFoundHttpException;

/**
 * CampaignTypesService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property CampaignTypeModel[] $allCampaignTypes
 */
class CampaignTypesService extends Component
{
    // Constants
    // =========================================================================

    /**
     * @event CampaignTypeEvent
     */
    const EVENT_BEFORE_SAVE_CAMPAIGN_TYPE = 'beforeSaveCampaignType';

    /**
     * @event CampaignTypeEvent
     */
    const EVENT_AFTER_SAVE_CAMPAIGN_TYPE = 'afterSaveCampaignType';

    /**
     * @event CampaignTypeEvent
     */
    const EVENT_BEFORE_DELETE_CAMPAIGN_TYPE = 'beforeDeleteCampaignType';

    /**
     * @event CampaignTypeEvent
     */
    const EVENT_AFTER_DELETE_CAMPAIGN_TYPE = 'afterDeleteCampaignType';

    /**
     * @since 1.12.0
     */
    const CONFIG_CAMPAIGNTYPES_KEY = 'campaign.campaignTypes';

    // Public Methods
    // =========================================================================

    /**
     * Returns all campaign types
     *
     * @return CampaignTypeModel[]
     */
    public function getAllCampaignTypes(): array
    {
        $campaignTypeRecords = CampaignTypeRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return CampaignTypeModel::populateModels($campaignTypeRecords, false);
    }

    /**
     * Returns campaign type by ID
     *
     * @param int $campaignTypeId
     *
     * @return CampaignTypeModel|null
     */
    public function getCampaignTypeById(int $campaignTypeId)
    {
        if (!$campaignTypeId) {
            return null;
        }

        $campaignTypeRecord = CampaignTypeRecord::findOne($campaignTypeId);

        if ($campaignTypeRecord === null) {
            return null;
        }

        /** @var CampaignTypeModel $campaignType */
        $campaignType = CampaignTypeModel::populateModel($campaignTypeRecord, false);

        return $campaignType;
    }

    /**
     * Returns campaign type by UID
     *
     * @param string $uid
     *
     * @return CampaignTypeModel|null
     */
    public function getCampaignTypeByUid(string $uid)
    {
        $campaignTypeRecord = CampaignTypeRecord::findOne(['uid' => $uid]);

        if ($campaignTypeRecord === null) {
            return null;
        }

        /** @var CampaignTypeModel $campaignType */
        $campaignType = CampaignTypeModel::populateModel($campaignTypeRecord, false);

        return $campaignType;
    }

    /**
     * Returns campaign type by handle
     *
     * @param string $campaignTypeHandle
     *
     * @return CampaignTypeModel|null
     */
    public function getCampaignTypeByHandle(string $campaignTypeHandle)
    {
        $campaignTypeRecord = CampaignTypeRecord::findOne(['handle' => $campaignTypeHandle]);

        if ($campaignTypeRecord === null) {
            return null;
        }

        /** @var CampaignTypeModel $campaignType */
        $campaignType = CampaignTypeModel::populateModel($campaignTypeRecord, false);

        return $campaignType;
    }

    /**
     * Saves a campaign type.
     *
     * @param CampaignTypeModel $campaignType The campaign type to be saved
     * @param bool $runValidation Whether the campaign type should be validated
     *
     * @return bool Whether the campaign type was saved successfully
     */
    public function saveCampaignType(CampaignTypeModel $campaignType, bool $runValidation = true): bool
    {
        $isNew = $campaignType->id === null;

        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
                'isNew' => $isNew,
            ]));
        }

        if ($runValidation && !$campaignType->validate()) {
            Campaign::$plugin->log('Campaign type not saved due to validation error.');

            return false;
        }

        // Ensure the campaign type has a UID
        if ($isNew) {
            $campaignType->uid = StringHelper::UUID();
        }
        else if (!$campaignType->uid) {
            $campaignTypeRecord = CampaignTypeRecord::findOne($campaignType->id);

            if ($campaignTypeRecord === null) {
                throw new NotFoundHttpException('No campaign type exists with the ID '.$campaignType->id);
            }

            $campaignType->uid = $campaignTypeRecord->uid;
        }

        // Get config data
        $configData = ProjectConfigDataHelper::getCampaignTypeData($campaignType);

        // Save it to project config
        $path = self::CONFIG_CAMPAIGNTYPES_KEY.'.'.$campaignType->uid;
        Craft::$app->projectConfig->set($path, $configData);

        // Set the ID on the campaign type
        if ($isNew) {
            $campaignType->id = Db::idByUid(CampaignTypeRecord::tableName(), $campaignType->uid);
        }

        return true;
    }

    /**
     * Handles a changed campaign type.
     *
     * @param ConfigEvent $event
     */
    public function handleChangedCampaignType(ConfigEvent $event)
    {
        // Make sure all sites and fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $campaignTypeRecord = CampaignTypeRecord::findOne(['uid' => $uid]);

        $isNew = $campaignTypeRecord === null;

        if ($isNew) {
            $campaignTypeRecord = new CampaignTypeRecord();
        }

        // Save old site ID for resaving elements later
        $oldSiteId = $campaignTypeRecord->siteId;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $campaignTypeRecord->setAttributes($data, false);
            $campaignTypeRecord->siteId = Db::idByUid(Table::SITES, $data['siteUid']);
            $campaignTypeRecord->uid = $uid;

            $fieldsService = Craft::$app->getFields();

            if (!empty($data['fieldLayouts']) && !empty($config = reset($data['fieldLayouts']))) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig($config);
                $layout->id = $campaignTypeRecord->fieldLayoutId;
                $layout->type = CampaignElement::class;
                $layout->uid = key($data['fieldLayouts']);
                $fieldsService->saveLayout($layout);
                $campaignTypeRecord->fieldLayoutId = $layout->id;
            }
            else if ($campaignTypeRecord->fieldLayoutId) {
                // Delete the field layout
                $fieldsService->deleteLayoutById($campaignTypeRecord->fieldLayoutId);
                $campaignTypeRecord->fieldLayoutId = null;
            }

            // Unset ID if null to avoid making postgres mad
            if ($campaignTypeRecord->id === null) {
                unset($campaignTypeRecord->id);
            }

            // Save the campaign type
            if (!$campaignTypeRecord->save(false)) {
                throw new Exception('Couldn’t save campaign type.');
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Get campaign type model
        $campaignType = $this->getCampaignTypeById($campaignTypeRecord->id);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
                'isNew' => $isNew,
            ]));
        }

        if (!$isNew) {
            /** @var Queue $queue */
            $queue = Craft::$app->getQueue();

            // Re-save the campaigns in this campaign type
            $queue->push(new ResaveElementsJob([
                'description' => Craft::t('app', 'Resaving {type} campaigns ({site})', [
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
    }

    /**
     * Deletes a campaign type by its ID
     *
     * @param int $campaignTypeId
     *
     * @return bool Whether the campaign type was deleted successfully
     * @throws Throwable if reasons
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
     * Deletes a campaign type
     *
     * @param CampaignTypeModel $campaignType
     *
     * @return bool Whether the campaign type was deleted successfully
     * @throws Throwable if reasons
     */
    public function deleteCampaignType(CampaignTypeModel $campaignType): bool
    {
        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
            ]));
        }

        // Remove it from project config
        $path = self::CONFIG_CAMPAIGNTYPES_KEY.'.'.$campaignType->uid;
        Craft::$app->projectConfig->remove($path);

        return true;
    }

    /**
     * Handles a deleted campaign type.
     *
     * @param ConfigEvent $event
     */
    public function handleDeletedCampaignType(ConfigEvent $event)
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
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Get campaign type model
        $campaignType = $this->getCampaignTypeById($campaignTypeRecord->id);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
            ]));
        }
    }

    /**
     * Handles a deleted site.
     *
     * @param DeleteSiteEvent $event
     */
    public function handleDeletedSite(DeleteSiteEvent $event)
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
     * Prunes a deleted field from the field layouts.
     *
     * @param FieldEvent $event
     */
    public function pruneDeletedField(FieldEvent $event)
    {
        /** @var Field $field */
        $field = $event->field;
        $fieldUid = $field->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $campaignTypes = $projectConfig->get(self::CONFIG_CAMPAIGNTYPES_KEY);

        // Loop through the types and prune the UID from field layouts.
        if (is_array($campaignTypes)) {
            foreach ($campaignTypes as $campaignTypeUid => $campaignType) {
                if (!empty($campaignType['fieldLayouts'])) {
                    foreach ($campaignType['fieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_CAMPAIGNTYPES_KEY.'.'.$campaignTypeUid.'.fieldLayouts.'.$layoutUid.'.tabs.'.$tabUid.'.fields.'.$fieldUid);
                            }
                        }
                    }
                }
            }
        }
    }
}
