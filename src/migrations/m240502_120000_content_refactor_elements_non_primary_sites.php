<?php

namespace putyourlightson\campaign\migrations;

use Craft;
use craft\migrations\BaseContentRefactorMigration;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SegmentElement;
use putyourlightson\campaign\elements\SendoutElement;

class m240502_120000_content_refactor_elements_non_primary_sites extends BaseContentRefactorMigration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        foreach (Campaign::$plugin->campaignTypes->getAllCampaignTypes() as $campaignType) {
            if ($campaignType->siteId !== $primarySiteId) {
                $this->updateElements(
                    CampaignElement::find()
                        ->campaignType($campaignType)
                        ->siteId($campaignType->siteId)
                        ->ids(),
                    $campaignType->getFieldLayout(),
                );
            }
        }

        foreach (Campaign::$plugin->mailingListTypes->getAllMailingListTypes() as $mailingListType) {
            if ($mailingListType->siteId !== $primarySiteId) {
                $this->updateElements(
                    MailingListElement::find()
                        ->mailingListType($mailingListType)
                        ->siteId($mailingListType->siteId)
                        ->ids(),
                    $mailingListType->getFieldLayout(),
                );
            }
        }

        $this->updateElements(
            SegmentElement::find()
                ->siteId(['not', $primarySiteId])
                ->ids(),
            Craft::$app->getFields()->getLayoutByType(SegmentElement::class),
        );

        $this->updateElements(
            SendoutElement::find()
                ->siteId(['not', $primarySiteId])
                ->ids(),
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
