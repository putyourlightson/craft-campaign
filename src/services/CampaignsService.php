<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\db\ActiveRecord;
use craft\mail\Message;
use craft\web\View;
use DateTime;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\SettingsHelper;
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\records\CampaignRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\LinkRecord;
use yii\base\Event;

class CampaignsService extends Component
{
    /**
     * @var bool|null
     */
    public ?bool $_requestPrepared = null;

    /**
     * Returns a campaign by ID.
     */
    public function getCampaignById(int $campaignId): ?CampaignElement
    {
        /** @var CampaignElement|null */
        return CampaignElement::find()
            ->id($campaignId)
            ->site('*')
            ->status(null)
            ->one();
    }

    /**
     * Returns an array of campaigns by IDs.
     *
     * @param int[]|null $campaignIds
     * @return CampaignElement[]
     */
    public function getCampaignsByIds(?array $campaignIds): array
    {
        if (empty($campaignIds)) {
            return [];
        }

        /** @var CampaignElement[] */
        return CampaignElement::find()
            ->id($campaignIds)
            ->site('*')
            ->status(null)
            ->fixedOrder()
            ->all();
    }

    /**
     * Adds a contact interaction.
     */
    public function addContactInteraction(ContactElement $contact, SendoutElement $sendout, string $interaction, LinkRecord $linkRecord = null): void
    {
        // Ensure that interaction exists
        if (!in_array($interaction, ContactCampaignModel::INTERACTIONS)) {
            return;
        }

        /** @var CampaignRecord|null $campaignRecord */
        $campaignRecord = CampaignRecord::find()
            ->where(['id' => $sendout->campaignId])
            ->with('campaignType')
            ->one();

        if ($campaignRecord === null) {
            return;
        }

        /** @var ContactCampaignRecord|null $contactCampaignRecord */
        $contactCampaignRecord = ContactCampaignRecord::find()
            ->where([
                'contactId' => $contact->id,
                'sendoutId' => $sendout->id,
            ])
            ->one();

        if ($contactCampaignRecord === null) {
            return;
        }

        // If first time for this interaction
        if ($contactCampaignRecord->{$interaction} === null) {
            $contactCampaignRecord->{$interaction} = new DateTime();
            $this->_incrementRecordColumn($campaignRecord, $interaction);
        }

        if ($interaction == 'opened') {
            $this->_incrementRecordColumn($contactCampaignRecord, 'opens');
            $this->_incrementRecordColumn($campaignRecord, 'opens');
        } elseif ($interaction == 'clicked') {
            // If not yet opened
            if ($contactCampaignRecord->opened === null) {
                $contactCampaignRecord->opened = new DateTime();
                $contactCampaignRecord->opens = 1;
                $this->_incrementRecordColumn($campaignRecord, 'opened');
                $this->_incrementRecordColumn($campaignRecord, 'opens');
            }

            // Increment clicks
            $this->_incrementRecordColumn($contactCampaignRecord, 'clicks');
            $this->_incrementRecordColumn($campaignRecord, 'clicks');

            // If link record exists
            if ($linkRecord !== null) {
                // Increment clicks
                $this->_incrementRecordColumn($linkRecord, 'clicks');

                // Increment clicked if first link click for this contact
                if (!in_array($linkRecord->id, explode(',', $contactCampaignRecord->links))) {
                    $this->_incrementRecordColumn($linkRecord, 'clicked');
                }

                // Append link ID
                $contactCampaignRecord->links = $contactCampaignRecord->links ? $contactCampaignRecord->links . ',' . $linkRecord->id : $linkRecord->id;
            }
        }

        // Only save if anonymous tracking is not enabled
        if (!Campaign::$plugin->settings->enableAnonymousTracking) {
            $contactCampaignRecord->save();
        }
    }

    /**
     * Sends a test.
     */
    public function sendTest(CampaignElement $campaign, ContactElement $contact): bool
    {
        // Get body
        $htmlBody = $campaign->getHtmlBody($contact);
        $plaintextBody = $campaign->getPlaintextBody($contact);

        // Get from name and email
        $fromNameEmail = SettingsHelper::getFromNameEmail($campaign->siteId);

        // Compose message
        /** @var Message $message*/
        $message = Campaign::$plugin->mailer->compose()
            ->setFrom([$fromNameEmail['email'] => $fromNameEmail['name']])
            ->setTo($contact->email)
            ->setSubject('[Test] ' . $campaign->title)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        if ($fromNameEmail['replyTo']) {
            $message->setReplyTo($fromNameEmail['replyTo']);
        }

        return $message->send();
    }

    /**
     * Prepares the request for getting a campaign's HTML body, ensuring that
     * all assets and resources are removed before rendering the template.
     * This is especially important since requests may be interpreted by Craft,
     * plugins and modules as CP requests on init.
     *
     * @see https://github.com/putyourlightson/craft-campaign/issues/347
     */
    public function prepareRequestToGetHtmlBody(): void
    {
        if ($this->_requestPrepared === true) {
            return;
        }

        // Force a site request only if a CP request
        $request = Craft::$app->getRequest();
        if ($request->getIsCpRequest()) {
            $request->setIsCpRequest(false);
        }

        // Clear up all registered files and asset bundles before rendering a template
        Event::on(View::class, View::EVENT_BEFORE_RENDER_TEMPLATE,
            function() {
                Craft::$app->getView()->clear();
            }
        );

        $this->_requestPrepared = true;
    }

    /**
     * Increments a record's column value by one. This method updates counters
     * rather than saving records, to ensure that reports remain accurate.
     *
     * @see https://github.com/putyourlightson/craft-campaign/issues/232
     * @see https://github.com/putyourlightson/craft-campaign/issues/285
     */
    private function _incrementRecordColumn(ActiveRecord $record, string $column): void
    {
        // Respect anonymous tracking for contact campaign records.
        if (Campaign::$plugin->settings->enableAnonymousTracking && $record instanceof ContactCampaignRecord) {
            return;
        }

        // https://www.yiiframework.com/doc/guide/2.0/en/db-active-record#updating-counters
        $record->updateCounters([$column => 1]);
    }
}
