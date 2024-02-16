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
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\events\MailingListTypeEvent;
use putyourlightson\campaign\helpers\ProjectConfigDataHelper;
use putyourlightson\campaign\jobs\ResaveElementsJob;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\records\MailingListTypeRecord;
use Throwable;
use yii\base\Exception;
use yii\web\NotFoundHttpException;

/**
 * @property-read MailingListTypeModel[] $editableMailingListTypes
 * @property-read MailingListTypeModel[] $allMailingListTypes
 */
class MailingListTypesService extends Component
{
    /**
     * @event MailingListTypeEvent
     */
    public const EVENT_BEFORE_SAVE_MAILINGLIST_TYPE = 'beforeSaveMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    public const EVENT_AFTER_SAVE_MAILINGLIST_TYPE = 'afterSaveMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    public const EVENT_BEFORE_DELETE_MAILINGLIST_TYPE = 'beforeDeleteMailingListType';

    /**
     * @event MailingListTypeEvent
     */
    public const EVENT_AFTER_DELETE_MAILINGLIST_TYPE = 'afterDeleteMailingListType';

    /**
     * @since 1.12.0
     */
    public const CONFIG_MAILINGLISTTYPES_KEY = 'campaign.mailingListTypes';

    /**
     * @var MemoizableArray<MailingListTypeModel>|null
     * @see mailingListTypes()
     */
    private ?MemoizableArray $mailingListTypes = null;

    /**
     * Returns all mailing list types.
     *
     * @return MailingListTypeModel[]
     */
    public function getAllMailingListTypes(): array
    {
        return $this->mailingListTypes()->all();
    }

    /**
     * Returns all editable mailing list types.
     *
     * @return MailingListTypeModel[]
     */
    public function getEditableMailingListTypes(): array
    {
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return $this->getAllMailingListTypes();
        }

        $user = Craft::$app->getUser()->getIdentity();

        if (!$user) {
            return [];
        }

        return ArrayHelper::where($this->getAllMailingListTypes(),
            function(MailingListTypeModel $mailingListType) use ($user) {
                return $user->can('campaign:mailingLists:' . $mailingListType->uid);
            },
            true, true, false
        );
    }

    /**
     * Returns mailing list type by ID.
     */
    public function getMailingListTypeById(int $mailingListTypeId): ?MailingListTypeModel
    {
        /** @var MailingListTypeRecord|null $mailingListTypeRecord */
        $mailingListTypeRecord = MailingListTypeRecord::find()
            ->innerJoinWith('site')
            ->where([MailingListTypeRecord::tableName() . '.id' => $mailingListTypeId])
            ->one();

        if ($mailingListTypeRecord === null) {
            return null;
        }

        $mailingListType = new MailingListTypeModel();
        $mailingListType->setAttributes($mailingListTypeRecord->getAttributes(), false);

        return $mailingListType;
    }

    /**
     * Returns mailing list type by handle.
     */
    public function getMailingListTypeByHandle(string $mailingListTypeHandle): ?MailingListTypeModel
    {
        /** @var MailingListTypeRecord|null $mailingListTypeRecord */
        $mailingListTypeRecord = MailingListTypeRecord::find()
            ->innerJoinWith('site')
            ->where([MailingListTypeRecord::tableName() . '.handle' => $mailingListTypeHandle])
            ->one();

        if ($mailingListTypeRecord === null) {
            return null;
        }

        $mailingListType = new MailingListTypeModel();
        $mailingListType->setAttributes($mailingListTypeRecord->getAttributes(), false);

        return $mailingListType;
    }

    /**
     * Returns mailing list type by UID.
     */
    public function getMailingListTypeByUid(string $uid): ?MailingListTypeModel
    {
        return $this->mailingListTypes()->firstWhere('uid', $uid, true);
    }

    /**
     * Saves a mailing list type.
     */
    public function saveMailingListType(MailingListTypeModel $mailingListType, bool $runValidation = true): bool
    {
        $isNew = $mailingListType->id === null;

        // Fire a before event
        $event = new MailingListTypeEvent([
            'mailingListType' => $mailingListType,
            'isNew' => $isNew,
        ]);
        $this->trigger(self::EVENT_BEFORE_SAVE_MAILINGLIST_TYPE, $event);

        if (!$event->isValid) {
            return false;
        }

        if ($runValidation && !$mailingListType->validate()) {
            Campaign::$plugin->log('Mailing list type not saved due to validation error.');

            return false;
        }

        // Ensure the mailing list type has a UID
        if ($isNew) {
            $mailingListType->uid = StringHelper::UUID();
        } elseif (!$mailingListType->uid) {
            /** @var MailingListTypeRecord|null $mailingListTypeRecord */
            $mailingListTypeRecord = MailingListTypeRecord::find()
                ->andWhere([MailingListTypeRecord::tableName() . '.id' => $mailingListType->id])
                ->one();

            if ($mailingListTypeRecord === null) {
                throw new NotFoundHttpException('No mailing list type exists with the ID ' . $mailingListType->id);
            }

            $mailingListType->uid = $mailingListTypeRecord->uid;
        }

        // Get config data
        $configData = ProjectConfigDataHelper::getMailingListTypeData($mailingListType);

        // Save it to project config
        $path = self::CONFIG_MAILINGLISTTYPES_KEY . '.' . $mailingListType->uid;
        Craft::$app->getProjectConfig()->set($path, $configData);

        // Set the ID on the mailing list type
        if ($isNew) {
            $mailingListType->id = Db::idByUid(MailingListTypeRecord::tableName(), $mailingListType->uid);
        }

        return true;
    }

    /**
     * Handles a changed mailing list type.
     */
    public function handleChangedMailingListType(ConfigEvent $event): void
    {
        // Get the UID that was matched in the config path
        $uid = $event->tokenMatches[0];
        $data = $event->newValue;

        // Make sure all sites and fields are processed
        ProjectConfigHelper::ensureAllSitesProcessed();
        ProjectConfigHelper::ensureAllFieldsProcessed();

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            $mailingListTypeRecord = MailingListTypeRecord::findOne(['uid' => $uid]);
            $isNew = $mailingListTypeRecord === null;

            if ($isNew) {
                $mailingListTypeRecord = new MailingListTypeRecord();
            }

            // Save old site ID for resaving elements later
            $oldSiteId = $mailingListTypeRecord->siteId;

            $mailingListTypeRecord->setAttributes($data, false);
            $mailingListTypeRecord->siteId = Db::idByUid(Table::SITES, $data['siteUid']);
            $mailingListTypeRecord->uid = $uid;

            $fieldsService = Craft::$app->getFields();

            if (!empty($data['fieldLayouts'])) {
                // Save the field layout
                $layout = FieldLayout::createFromConfig(reset($data['fieldLayouts']));
                $layout->id = $mailingListTypeRecord->fieldLayoutId;
                $layout->type = MailingListElement::class;
                $layout->uid = key($data['fieldLayouts']);
                $fieldsService->saveLayout($layout, false);
                $mailingListTypeRecord->fieldLayoutId = $layout->id;
            } elseif ($mailingListTypeRecord->fieldLayoutId) {
                // Delete the field layout
                $fieldsService->deleteLayoutById($mailingListTypeRecord->fieldLayoutId);
                $mailingListTypeRecord->fieldLayoutId = null;
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

        // Clear caches
        $this->mailingListTypes = null;

        // Get mailing list type model
        $mailingListType = $this->getMailingListTypeById($mailingListTypeRecord->id);

        if (!$isNew) {
            /** @var Queue $queue */
            $queue = Craft::$app->getQueue();

            // Re-save the mailing lists in this mailing list type
            $queue->push(new ResaveElementsJob([
                'description' => Craft::t('campaign', 'Resaving {type} mailing lists', [
                    'type' => $mailingListType->name,
                    'site' => $mailingListType->getSite()->name,
                ]),
                'elementType' => MailingListElement::class,
                'criteria' => [
                    'siteId' => $oldSiteId,
                    'mailingListTypeId' => $mailingListType->id,
                    'status' => null,
                ],
                'siteId' => $mailingListType->siteId,
            ]));
        }

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
                'isNew' => $isNew,
            ]));
        }

        // Invalidate element caches
        Craft::$app->getElements()->invalidateCachesForElementType(MailingListElement::class);
    }

    /**
     * Deletes a mailing list type by its ID.
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
     * Deletes a mailing list type.
     */
    public function deleteMailingListType(MailingListTypeModel $mailingListType): bool
    {
        // Fire a before event
        $event = new MailingListTypeEvent([
            'mailingListType' => $mailingListType,
        ]);
        $this->trigger(self::EVENT_BEFORE_DELETE_MAILINGLIST_TYPE, $event);

        if (!$event->isValid) {
            return false;
        }

        // Remove it from project config
        $path = self::CONFIG_MAILINGLISTTYPES_KEY . '.' . $mailingListType->uid;
        Craft::$app->getProjectConfig()->remove($path);

        return true;
    }

    /**
     * Handles a deleted mailing list type.
     */
    public function handleDeletedMailingListType(ConfigEvent $event): void
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

        // Clear caches
        $this->mailingListTypes = null;

        // Get mailing list type model
        $mailingListType = $this->getMailingListTypeById($mailingListTypeRecord->id);

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
            ]));
        }

        // Invalidate element caches
        Craft::$app->getElements()->invalidateCachesForElementType(MailingListElement::class);
    }

    /**
     * Handles a deleted site.
     */
    public function handleDeletedSite(DeleteSiteEvent $event): void
    {
        $siteUid = $event->site->uid;

        $projectConfig = Craft::$app->getProjectConfig();
        $mailingListTypes = $projectConfig->get(self::CONFIG_MAILINGLISTTYPES_KEY);

        if (is_array($mailingListTypes)) {
            foreach ($mailingListTypes as $mailingListTypeUid => $mailingListType) {
                if ($mailingListTypeUid == $siteUid) {
                    $this->deleteMailingListType($mailingListType);
                }
            }
        }
    }

    /**
     * Returns a memoizable array of all mailing list types.
     *
     * @return MemoizableArray<MailingListTypeModel>
     */
    private function mailingListTypes(): MemoizableArray
    {
        if (!isset($this->mailingListTypes)) {
            $mailingListTypes = [];

            /** @var MailingListTypeRecord[] $mailingListTypeRecords */
            $mailingListTypeRecords = MailingListTypeRecord::find()
                ->innerJoinWith('site')
                ->orderBy(['name' => SORT_ASC])
                ->all();

            foreach ($mailingListTypeRecords as $mailingListTypeRecord) {
                $mailingListType = new MailingListTypeModel();
                $mailingListType->setAttributes($mailingListTypeRecord->getAttributes(), false);
                $mailingListTypes[] = $mailingListType;
            }

            $this->mailingListTypes = new MemoizableArray($mailingListTypes);
        }

        return $this->mailingListTypes;
    }
}
