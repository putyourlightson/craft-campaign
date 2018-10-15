<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use craft\records\Element_SiteSettings;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\models\ContactCampaignModel;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\LinkRecord;

use Craft;
use craft\base\Component;
use craft\errors\ElementNotFoundException;
use craft\mail\Message;
use yii\base\Exception;

/**
 * CampaignsService
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0
 */
class CampaignsService extends Component
{
    // Public Methods
    // =========================================================================

    /**
     * Returns campaign by ID
     *
     * @param int $campaignId
     *
     * @return CampaignElement|null
     */
    public function getCampaignById(int $campaignId)
    {
        // Get site ID from element site settings
        $siteId = Element_SiteSettings::find()
            ->select('siteId')
            ->where(['elementId' => $campaignId])
            ->scalar();

        if ($siteId === null) {
            return null;
        }

        $campaign = CampaignElement::find()
            ->id($campaignId)
            ->siteId($siteId)
            ->status(null)
            ->one();

        return $campaign;
    }

    /**
     * Adds a contact interaction
     *
     * @param ContactElement $contact
     * @param SendoutElement $sendout
     * @param string         $interaction
     * @param LinkRecord     $linkRecord
     *
     * @throws \Throwable
     * @throws ElementNotFoundException
     * @throws Exception
     */
    public function addContactInteraction(ContactElement $contact, SendoutElement $sendout, string $interaction, LinkRecord $linkRecord = null)
    {
        // Ensure that interaction exists
        if (!\in_array($interaction, ContactCampaignModel::INTERACTIONS, true)) {
            return;
        }

        // Get campaign
        $campaign = Campaign::$plugin->campaigns->getCampaignById($sendout->campaignId);

        if ($campaign === null) {
            return;
        }

        $contactCampaignRecord = ContactCampaignRecord::findOne([
            'contactId' => $contact->id,
            'sendoutId' => $sendout->id,
        ]);

        if ($contactCampaignRecord === null) {
            return;
        }

        // If first time for this interaction
        if ($contactCampaignRecord->{$interaction} === null) {
            $contactCampaignRecord->{$interaction} = new \DateTime();
            $campaign->{$interaction}++;
        }

        // If opened
        if ($interaction == 'opened') {
            $contactCampaignRecord->opens = $contactCampaignRecord->opens ? $contactCampaignRecord->opens + 1 : 1;
            $campaign->opens++;
        }
        // If clicked
        else if ($interaction == 'clicked') {
            // If not yet opened
            if ($contactCampaignRecord->opened === null) {
                $contactCampaignRecord->opened = new \DateTime();
                $contactCampaignRecord->opens = 1;
                $campaign->opened++;
                $campaign->opens++;
            }

            // Increment clicks
            $contactCampaignRecord->clicks = $contactCampaignRecord->clicks ? $contactCampaignRecord->clicks + 1 : 1;
            $campaign->clicks++;

            // If link record exists
            if ($linkRecord !== null) {
                // Increment clicks
                $linkRecord->clicks = $linkRecord->clicks ? $linkRecord->clicks + 1 : 1;

                // Increment clicked if first link click for this contact
                if (!\in_array($linkRecord->id, explode(',', $contactCampaignRecord->links), false)) {
                    $linkRecord->clicked = $linkRecord->clicked ? $linkRecord->clicked + 1 : 1;
                }

                // Append link ID
                $contactCampaignRecord->links = $contactCampaignRecord->links ? $contactCampaignRecord->links.','.$linkRecord->id : $linkRecord->id;

                $linkRecord->save();
            }
        }

        $contactCampaignRecord->save();

        Craft::$app->getElements()->saveElement($campaign);
    }

    /**
     * Sends a test
     *
     * @param CampaignElement $campaign
     * @param ContactElement $contact
     *
     * @return bool Whether the test was sent successfully
     * @throws Exception
     */
    public function sendTest(CampaignElement $campaign, ContactElement $contact): bool
    {
        // Get settings
        $settings = Campaign::$plugin->getSettings();

        // Get body
        $htmlBody = $campaign->getHtmlBody($contact);
        $plaintextBody = $campaign->getPlaintextBody($contact);

        // Get mailer
        $mailer = Campaign::$plugin->createMailer();

        // Get first from name and email
        $fromNameEmail = Campaign::$plugin->settings->getFirstFromNameEmail($campaign->siteId);

        // Compose message
        /** @var Message $message*/
        $message = $mailer->compose()
            ->setFrom([$fromNameEmail['email'] => $fromNameEmail['name']])
            ->setTo($contact->email)
            ->setSubject('[Test] '.$campaign->title)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        return $message->send();
    }
}