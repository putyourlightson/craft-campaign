<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\migrations\BaseContentRefactorMigration;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;

class m240326_120000_content_refactor_elements extends BaseContentRefactorMigration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        foreach (Campaign::$plugin->campaignTypes->getAllCampaignTypes() as $campaignType) {
            $this->updateElements(
                CampaignElement::find()->campaignType($campaignType)->ids(),
                $campaignType->getFieldLayout(),
            );
        }

        foreach (Campaign::$plugin->mailingListTypes->getAllMailingListTypes() as $mailingListType) {
            $this->updateElements(
                MailingListElement::find()->mailingListType($mailingListType)->ids(),
                $mailingListType->getFieldLayout(),
            );
        }

        $this->updateElements(
            ContactElement::find()->ids(),
            Craft::$app->getFields()->getLayoutByType(ContactElement::class),
        );

        $this->updateElements(
            SegmentElement::find()->ids(),
            Craft::$app->getFields()->getLayoutByType(SegmentElement::class),
        );

        $this->updateElements(
            SendoutElement::find()->ids(),
            Craft::$app->getFields()->getLayoutByType(SendoutElement::class),
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
