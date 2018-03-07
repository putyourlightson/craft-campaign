<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\events\CampaignTypeEvent;
use putyourlightson\campaign\models\CampaignTypeModel;
use putyourlightson\campaign\records\CampaignTypeRecord;
use putyourlightson\campaign\elements\CampaignElement;

use Craft;
use craft\base\Component;
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

        $campaignTypeModel = new CampaignTypeModel();
        $campaignTypeModel->setAttributes($campaignTypeRecord->getAttributes());

        return $campaignTypeModel;
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

        $campaignTypeModel = new CampaignTypeModel();
        $campaignTypeModel->setAttributes($campaignTypeRecord->getAttributes());

        return $campaignTypeModel;
    }

    /**
     * Saves a campaign type.
     *
     * @param CampaignTypeModel $campaignType  The campaign type to be saved
     * @param bool              $runValidation Whether the campaign type should be validated
     *
     * @return bool Whether the campaign type was saved successfully
     * @throws NotFoundHttpException if $campaignType->id is invalid
     * @throws \Throwable if reasons
     */
    public function saveCampaignType(CampaignTypeModel $campaignType, bool $runValidation = true): bool
    {
        $isNew = $campaignType->id === null;

        // Fire a 'beforeSaveCampaignType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
                'isNew' => $isNew,
            ]));
        }

        if ($runValidation AND !$campaignType->validate()) {
            Craft::info('Campaign type not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($campaignType->id) {
            $campaignTypeRecord = CampaignTypeRecord::findOne($campaignType->id);

            if ($campaignTypeRecord === null) {
                throw new NotFoundHttpException("No campaign type exists with the ID '{$campaignType->id}'");
            }
        }
        else {
            $campaignTypeRecord = new CampaignTypeRecord();
        }

        $campaignTypeRecord->setAttributes($campaignType->getAttributes(), false);

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Save the field layout
            $fieldLayout = $campaignType->getFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $campaignType->fieldLayoutId = $fieldLayout->id;
            $campaignTypeRecord->fieldLayoutId = $fieldLayout->id;

            // Save the campaign type
            $campaignTypeRecord->save(false);

            // Now that we have an campaign type ID, save it on the model
            if (!$campaignType->id) {
                $campaignType->id = $campaignTypeRecord->id;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterSaveCampaignType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
                'isNew' => $isNew,
            ]));
        }

        return true;
    }

    /**
     * Deletes a campaign type by its ID
     *
     * @param int $campaignTypeId
     *
     * @return bool Whether the campaign type was deleted successfully
     * @throws \Throwable if reasons
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
     * @throws \Throwable if reasons
     */
    public function deleteCampaignType(CampaignTypeModel $campaignType): bool
    {
        // Fire a 'beforeDeleteCampaignType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            if ($campaignType->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($campaignType->fieldLayoutId);
            }

            // Delete the campaigns
            $campaigns = CampaignElement::find()
                ->campaignTypeId($campaignType->id)
                ->all();

            foreach ($campaigns as $campaign) {
                Craft::$app->getElements()->deleteElement($campaign);
            }

            // Delete the campaign type
            $campaignTypeRecord = CampaignTypeRecord::findOne($campaignType->id);
            $campaignTypeRecord->delete();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire a 'afterDeleteCampaignType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_CAMPAIGN_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_CAMPAIGN_TYPE, new CampaignTypeEvent([
                'campaignType' => $campaignType,
            ]));
        }

        return true;
    }
}