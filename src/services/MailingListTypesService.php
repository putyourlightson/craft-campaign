<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use putyourlightson\campaign\events\MailingListTypeEvent;
use putyourlightson\campaign\models\MailingListTypeModel;
use putyourlightson\campaign\records\MailingListTypeRecord;
use putyourlightson\campaign\elements\MailingListElement;

use Craft;
use craft\base\Component;
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
     * @param bool              $runValidation Whether the mailing list type should be validated
     *
     * @return bool Whether the mailing list type was saved successfully
     * @throws \Throwable if reasons
     */
    public function saveMailingListType(MailingListTypeModel $mailingListType, bool $runValidation = true): bool
    {
        $isNew = $mailingListType->id === null;

        // Fire a 'beforeSaveMailingListType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_SAVE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_SAVE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
                'isNew' => $isNew,
            ]));
        }

        if ($runValidation AND !$mailingListType->validate()) {
            Craft::info('Mailing list type not saved due to validation error.', __METHOD__);

            return false;
        }

        if ($mailingListType->id) {
            $mailingListTypeRecord = MailingListTypeRecord::findOne($mailingListType->id);

            if ($mailingListTypeRecord === null) {
                throw new NotFoundHttpException("No mailing list type exists with the ID '{$mailingListType->id}'");
            }
        } else {
            $mailingListTypeRecord = new MailingListTypeRecord();
        }

        $mailingListTypeRecord->setAttributes($mailingListType->getAttributes(), false);

        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // Save the field layout
            $fieldLayout = $mailingListType->getFieldLayout();
            Craft::$app->getFields()->saveLayout($fieldLayout);
            $mailingListType->fieldLayoutId = $fieldLayout->id;
            $mailingListTypeRecord->fieldLayoutId = $fieldLayout->id;

            // Save the mailing list type
            $mailingListTypeRecord->save(false);

            // Now that we have an mailing list type ID, save it on the model
            if (!$mailingListType->id) {
                $mailingListType->id = $mailingListTypeRecord->id;
            }

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire a 'afterSaveMailingListType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SAVE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_AFTER_SAVE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
                'isNew' => $isNew,
            ]));
        }

        return true;
    }

    /**
     * Deletes a mailing list type by its ID
     *
     * @param int $mailingListTypeId
     *
     * @return bool Whether the mailing list type was deleted successfully
     * @throws \Throwable if reasons
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
     * @throws \Throwable if reasons
     */
    public function deleteMailingListType(MailingListTypeModel $mailingListType): bool
    {
        // Fire a 'beforeDeleteMailingListType' event
        if ($this->hasEventHandlers(self::EVENT_BEFORE_DELETE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_BEFORE_DELETE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
            ]));
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Delete the field layout
            if ($mailingListType->fieldLayoutId) {
                Craft::$app->getFields()->deleteLayoutById($mailingListType->fieldLayoutId);
            }

            // Delete the mailing lists
            $mailingLists = MailingListElement::findAll(['mailingListTypeId' => $mailingListType->id]);

            foreach ($mailingLists as $mailingList) {
                Craft::$app->getElements()->deleteElement($mailingList);
            }

            // Delete the mailing list type
            $mailingListTypeRecord = MailingListTypeRecord::findOne($mailingListType->id);
            $mailingListTypeRecord->delete();

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();

            throw $e;
        }

        // Fire an 'afterDeleteMailingListType' event
        if ($this->hasEventHandlers(self::EVENT_AFTER_DELETE_MAILINGLIST_TYPE)) {
            $this->trigger(self::EVENT_AFTER_DELETE_MAILINGLIST_TYPE, new MailingListTypeEvent([
                'mailingListType' => $mailingListType,
            ]));
        }

        return true;
    }
}