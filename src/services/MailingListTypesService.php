<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\base\Field;
use craft\db\Table;
use craft\events\ConfigEvent;
use craft\events\FieldEvent;
use craft\helpers\Db;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\helpers\StringHelper;
use craft\models\FieldLayout;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\events\MailingListTypeEvent;
use putyourlightson\campaign\helpers\ProjectConfigDataHelper;
use putyourlightson\campaign\jobs\ResaveElementsJob;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\base\Component;
use Throwable;
use yii\base\Exception;
use yii\web\NotFoundHttpException;

/**
 * MailingListTypesService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 *
 * @property MailingListTypeModel[] $allMailingListTypes
 */
class MailingListTypesService extends Component
{
    /**
     * @event MailingListTypeEvent
     */
    const EVENT_BEFORE_SAVE_MAILINGLIST_TYPE = 'beforeSaveMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    const EVENT_AFTER_SAVE_MAILINGLIST_TYPE = 'afterSaveMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    const EVENT_BEFORE_DELETE_MAILINGLIST_TYPE = 'beforeDeleteMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    const EVENT_AFTER_DELETE_MAILINGLIST_TYPE = 'afterDeleteMailingListType';

    const CONFIG_MAILINGLISTTYPES_KEY = 'campaign.mailingListTypes';

    // Public Methods
    // =========================================================================

    /**
     * Returns all mailing list types
     *
     * @return MailingListTypeModel[]
     */
    public function getAllMailingListTypes(): array
    {
        $mailingListTypeRecords = MailingListTypeRecord::find()
            ->orderBy(['name' => SORT_ASC])
            ->all();

        return MailingListTypeModel::populateModels($mailingListTypeRecords, false);
    }

    /**
     * Returns mailing list type by ID
     *
     * @param int $mailingListTypeId
     *
     * @return MailingListTypeModel|null
     */
    public function getMailingListTypeById(int $mailingListTypeId)
    {
        $mailingListTypeRecord = MailingListTypeRecord::findOne($mailingListTypeId);

        if ($mailingListTypeRecord === null) {
            return null;
        }

        /** @var MailingListTypeModel $mailingListType */
        $mailingListType = MailingListTypeModel::populateModel($mailingListTypeRecord, false);

        return $mailingListType;
    }

    /**
     * Returns mailing list type by handle
     *
     * @param string $mailingListTypeHandle
     *
     * @return MailingListTypeModel|null
     */
    public function getMailingListTypeByHandle(string $mailingListTypeHandle)
    {
        $mailingListTypeRecord = MailingListTypeRecord::findOne(['handle' => $mailingListTypeHandle]);

        if ($mailingListTypeRecord === null) {
            return null;
        }

        /** @var MailingListTypeModel $mailingListType */
        $mailingListType = MailingListTypeModel::populateModel($mailingListTypeRecord, false);

        return $mailingListType;
    }

    /**
     * Saves a mailing list type.
     *
     * @param MailingListTypeModel $mailingListType  The mailing list type to be saved
     * @param bool|null $runValidation Whether the mailing list type should be validated
     *
     * @return bool Whether the mailing list type was saved successfully
     * @throws Throwable if reasons
     */
    public function saveMailingListType(MailingListTypeModel $mailingListType, bool $runValidation = true): bool
    {
        $isNew = $mailingListType->id === null;

        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
                'isNew' => $isNew,
            ]));
        }

        if ($runValidation && !$mailingListType->validate()) {
            Campaign::$plugin->log('Mailing list type not saved due to validation error.');

            return false;
        }

        // Ensure the mailing list type has a UID
        if ($isNew) {
            $mailingListType->uid = StringHelper::UUID();
        }
        else if (!$mailingListType->uid) {
            $mailingListTypeRecord = MailingListTypeRecord::findOne($mailingListType->id);

            if ($mailingListTypeRecord === null) {
                throw new NotFoundHttpException('No mailing list type exists with the ID '.$mailingListType->id);
            }

            $mailingListType->uid = $mailingListTypeRecord->uid;
        }

        // Get config data
        $configData = ProjectConfigDataHelper::getMailingListTypeData($mailingListType);

        // Save it to project config
        $path = self::CONFIG_MAILINGLISTTYPES_KEY.'.'.$mailingListType->uid;
        Craft::$app->projectConfig->set($path, $configData);

        // Set the ID on the mailing list type
        if ($isNew) {
            $mailingListType->id = Db::idByUid(MailingListTypeRecord::tableName(), $mailingListType->uid);
        }

        return true;
    }

    public function handleChangedMailingListType(ConfigEvent $event)
    {
        // Make sure all sites and fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        $mailingListTypeRecord = MailingListTypeRecord::findOne(['uid' => $uid]);

        $isNew = $mailingListTypeRecord === null;

        if ($isNew) {
            $mailingListTypeRecord = new MailingListTypeRecord();
        }

        // Save old site ID for resaving elements later
        $oldSiteId = $mailingListTypeRecord->siteId;

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $mailingListTypeRecord->setAttributes($data, false);
            $mailingListTypeRecord->siteId = Db::idByUid(Table::SITES, $data['siteUid']);
            $mailingListTypeRecord->uid = $uid;

            $fieldsService = Craft::$app->getFields();

            if (!empty($data['fieldLayouts']) && !empty($config = reset($data['fieldLayouts']))) {
                // Save the main field layout
                $layout = FieldLayout::createFromConfig($config);
                $layout->id = $mailingListTypeRecord->fieldLayoutId;
                $layout->type = MailingListElement::class;
                $layout->uid = key($data['fieldLayouts']);
                $fieldsService->saveLayout($layout);
                $mailingListTypeRecord->fieldLayoutId = $layout->id;
            }
            else if ($mailingListTypeRecord->fieldLayoutId) {
                // Delete the main field layout
                $fieldsService->deleteLayoutById($mailingListTypeRecord->fieldLayoutId);
                $mailingListTypeRecord->fieldLayoutId = null;
            }

            // Unset ID if null to avoid making postgres mad
            if ($mailingListTypeRecord->id === null) {
                unset($mailingListTypeRecord->id);
            }

            // Save the mailing list type
            if (!$mailingListTypeRecord->save(false)) {
                throw new Exception('Couldn’t save mailing list type.');
            }

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        // Get mailing list type model
        $mailingListType = $this->getMailingListTypeById($mailingListTypeRecord->id);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
                'isNew' => $isNew,
            ]));
        }

        if (!$isNew) {
            // Re-save the mailing lists in this mailing list type
            Craft::$app->getQueue()->push(new ResaveElementsJob([
                'description' => Craft::t('app', 'Resaving {type} mailing lists ({site})', [
                    'type' => $mailingListType->name,
                    'site' => $mailingListType->getSite()->name,
                ]),
                'elementType' => MailingListElement::class,
                'criteria' => [
                    'siteId' => $oldSiteId,
                    'campaignTypeId' => $mailingListType->id,
                    'status' => null,
                ],
                'siteId' => $mailingListType->siteId,
            ]));
        }
    }

    /**
     * Deletes a mailing list type by its ID
     *
     * @param int $mailingListTypeId
     *
     * @return bool Whether the mailing list type was deleted successfully
     * @throws Throwable if reasons
     */
    public function deleteMailingListTypeById(int $mailingListTypeId): bool
    {
        $mailingListType = $this->getMailingListTypeById($mailingListTypeId);

        if ($mailingListType === null) {
            return false;
        }

        return $this->deleteMailingListType($mailingListType);
    }

    /**
     * Deletes a mailing list type
     *
     * @param MailingListTypeModel $mailingListType
     *
     * @return bool Whether the mailing list type was deleted successfully
     * @throws Throwable if reasons
     */
    public function deleteMailingListType(MailingListTypeModel $mailingListType): bool
    {
        // Fire a before event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
            ]));
        }

        // Remove it from project config
        $path = self::CONFIG_MAILINGLISTTYPES_KEY.'.'.$mailingListType->uid;
        Craft::$app->projectConfig->remove($path);

        return true;
    }

    public function handleDeletedMailingListType(ConfigEvent $event)
    {
        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];

        $mailingListTypeRecord = MailingListTypeRecord::findOne(['uid' => $uid]);

        if ($mailingListTypeRecord == null) {
            return;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Delete the mailing lists
            $mailingLists = MailingListElement::find()
                ->mailingListTypeId($mailingListTypeRecord->id)
                ->all();

            foreach ($mailingLists as $mailingList) {
                Craft::$app->getElements()->deleteElement($mailingList);
            }

            // Delete the field layout
            if ($mailingListTypeRecord->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($mailingListTypeRecord->fieldLayoutId);
            }

            // Delete the mailing list type
            $mailingListTypeRecord->delete();

            $transaction->commit();
        } catch (Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Get mailing list type model
        $mailingListType = $this->getMailingListTypeById($mailingListTypeRecord->id);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
            ]));
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
        $mailingListTypes = $projectConfig->get(self::CONFIG_MAILINGLISTTYPES_KEY);

        // Loop through the types and prune the UID from field layouts.
        if (is_array($mailingListTypes)) {
            foreach ($mailingListTypes as $mailingListTypeUid => $mailingListType) {
                if (!empty($mailingListType['fieldLayouts'])) {
                    foreach ($mailingListType['fieldLayouts'] as $layoutUid => $layout) {
                        if (!empty($layout['tabs'])) {
                            foreach ($layout['tabs'] as $tabUid => $tab) {
                                $projectConfig->remove(self::CONFIG_MAILINGLISTTYPES_KEY.'.'.$mailingListTypeUid.'.fieldLayouts.'.$layoutUid.'.tabs.'.$tabUid.'.fields.'.$fieldUid);
                            }
                        }
                    }
                }
            }
        }
    }
}
