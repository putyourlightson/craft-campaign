<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\services;

use Craft;
use craft\base\Component;
use craft\helpers\App;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use craft\helpers\UrlHelper;
use DateTime;
use DOMDocument;
use DOMElement;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\events\SendoutEmailEvent;
use putyourlightson\campaign\helpers\SendoutHelper;
use putyourlightson\campaign\jobs\SendoutJob;
use putyourlightson\campaign\models\AutomatedScheduleModel;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\SendoutRecord;
use Twig\Error\Error;
use yii\db\ActiveQuery;

class SendoutsService extends Component
{
    /**
     * @event SendoutEvent
     */
    public const EVENT_BEFORE_SEND = 'beforeSend';

    /**
     * @event SendoutEvent
     */
    public const EVENT_AFTER_SEND = 'afterSend';

    /**
     * @event SendoutEmailEvent
     */
    public const EVENT_BEFORE_SEND_EMAIL = 'beforeSendEmail';

    /**
     * @event SendoutEmailEvent
     */
    public const EVENT_AFTER_SEND_EMAIL = 'afterSendEmail';

    /**
     * @var array
     */
    private array $_mailingLists = [];

    /**
     * @var array
     */
    private array $_links = [];

    /**
     * Returns a sendout by ID.
     */
    public function getSendoutById(int $sendoutId): ?SendoutElement
    {
        /** @var SendoutElement|null */
        return SendoutElement::find()
            ->id($sendoutId)
            ->site('*')
            ->status(null)
            ->one();
    }

    /**
     * Returns a sendout by SID.
     */
    public function getSendoutBySid(string $sid): ?SendoutElement
    {
        if (!$sid) {
            return null;
        }

        $sendoutId = SendoutRecord::find()
            ->select('id')
            ->where(['sid' => $sid])
            ->scalar();

        if ($sendoutId === null) {
            return null;
        }

        return $this->getSendoutById($sendoutId);
    }

    /**
     * Returns sendout send status by ID.
     */
    public function getSendoutSendStatusById(int $sendoutId): bool|string|null
    {
        return SendoutRecord::find()
            ->select('sendStatus')
            ->where(['id' => $sendoutId])
            ->scalar();
    }

    /**
     * Returns the sendout’s pending recipients.
     */
    public function getPendingRecipients(SendoutElement $sendout): array
    {
        if ($sendout->sendoutType == 'automated') {
            return $this->_getPendingRecipientsAutomated($sendout);
        }

        if ($sendout->sendoutType == 'singular') {
            return $this->_getPendingRecipientsSingular($sendout);
        }

        return $this->_getPendingRecipientsStandard($sendout);
    }

    /**
     * Returns the number of pending recipients, not including failed attempts.
     */
    public function getPendingRecipientCount(SendoutElement $sendout): int
    {
        $count = 0;

        // TODO Do this for all sendout types, so only IDs are queried at this stage
        if ($sendout->sendoutType == 'regular') {
            $count = count($this->_getPendingRecipientsStandardIds($sendout));
        } else {
            $count = count($this->getPendingRecipients($sendout));
        }

        return $count - $sendout->failures;
    }

    /**
     * Queues pending sendouts.
     */
    public function queuePendingSendouts(): int
    {
        $count = 0;
        $now = new DateTime();

        // Get sites to loop through, so we can ensure that we get all sendouts.
        $sites = Craft::$app->getSites()->getAllSites();

        foreach ($sites as $site) {
            // Find pending sendouts whose send date is in the past.
            $sendouts = SendoutElement::find()
                ->site($site)
                ->status(SendoutElement::STATUS_PENDING)
                ->where(Db::parseDateParam('sendDate', $now, '<='))
                ->all();

            /** @var SendoutElement $sendout */
            foreach ($sendouts as $sendout) {
                if ($sendout->getCanSendNow()) {
                    Queue::push(
                        job: new SendoutJob([
                            'sendoutId' => $sendout->id,
                            'title' => SendoutHelper::encodeEmojis($sendout->title),
                        ]),
                        priority: Campaign::$plugin->settings->sendoutJobPriority,
                        ttr: Campaign::$plugin->settings->sendoutJobTtr,
                        queue: Campaign::$plugin->queue,
                    );

                    $sendout->sendStatus = SendoutElement::STATUS_QUEUED;
                    $this->_updateSendoutRecord($sendout, ['sendStatus']);
                    $count++;
                }
            }
        }

        return $count;
    }

    /**
     * Sends a test.
     */
    public function sendTest(SendoutElement $sendout, ContactElement $contact): bool
    {
        // Get campaign
        $campaign = $sendout->getCampaign();

        if ($campaign === null) {
            return false;
        }

        // Set the current site from the sendout's site ID
        Craft::$app->getSites()->setCurrentSite($sendout->siteId);

        // Get body, catching template rendering errors
        try {
            $htmlBody = $campaign->getHtmlBody($contact, $sendout);
            $plaintextBody = $campaign->getPlaintextBody($contact, $sendout);
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (Error) {
            Campaign::$plugin->log('Testing of the sendout "{title}" failed due to a Twig error when rendering the template.', [
                'title' => $sendout->title,
            ]);

            return false;
        }

        // Convert links in HTML body
        $htmlBody = $this->_convertLinks($htmlBody, $contact, $sendout);

        // Compose message
        $message = Campaign::$plugin->mailer->compose()
            ->setFrom([$sendout->fromEmail => $sendout->fromName])
            ->setTo($contact->email)
            ->setSubject('[Test] ' . $sendout->subject)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        if ($sendout->replyToEmail) {
            $message->setReplyTo($sendout->replyToEmail);
        }

        return $message->send();
    }

    /**
     * Sends an email.
     */
    public function sendEmail(SendoutElement $sendout, ContactElement $contact, int $mailingListId = null): void
    {
        if ($sendout->getIsSendable() === false) {
            return;
        }

        if ($contact->canReceiveEmail() === false) {
            return;
        }

        // Get campaign
        $campaign = $sendout->getCampaign();

        if ($campaign === null) {
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
            $contactCampaignRecord = new ContactCampaignRecord();
            $contactCampaignRecord->contactId = $contact->id;
            $contactCampaignRecord->sendoutId = $sendout->id;
        } elseif ($contactCampaignRecord->sent !== null) {
            // Ensure this is a recurring sendout that can be sent to contacts multiple times
            $schedule = $sendout->getSchedule();

            if (!($sendout->sendoutType == 'recurring' && $schedule->canSendToContactsMultipleTimes)) {
                return;
            }

            $now = new DateTime();

            // Ensure not already sent today
            if ($contactCampaignRecord->sent > $now->format('Y-m-d')) {
                return;
            }
        }

        $contactCampaignRecord->campaignId = $campaign->id;
        $contactCampaignRecord->mailingListId = $mailingListId;

        $mailingList = $mailingListId ? $this->_getMailingListById($mailingListId) : null;

        // Get body, catching template rendering errors
        try {
            $htmlBody = $campaign->getHtmlBody($contact, $sendout, $mailingList);
            $plaintextBody = $campaign->getPlaintextBody($contact, $sendout, $mailingList);
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (Error) {
            $sendout->sendStatus = SendoutElement::STATUS_FAILED;

            $this->_updateSendoutRecord($sendout, ['sendStatus']);

            Campaign::$plugin->log('Sending of the sendout "{title}" failed due to a Twig error when rendering the template.', [
                'title' => $sendout->title,
            ]);

            return;
        }

        // Convert links in HTML body
        $htmlBody = $this->_convertLinks($htmlBody, $contact, $sendout);

        // Add tracking image to HTML body
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/campaign/t/open';
        $trackingImageUrl = UrlHelper::siteUrl($path, ['cid' => $contact->cid, 'sid' => $sendout->sid]);
        $htmlBody .= '<img src="' . $trackingImageUrl . '" width="1" height="1" alt="" />';

        // If test mode is enabled then use file transport instead of sending emails
        if (Campaign::$plugin->settings->testMode) {
            Campaign::$plugin->mailer->useFileTransport = true;
        }

        // Create message
        $message = Campaign::$plugin->mailer->compose()
            ->setFrom([$sendout->fromEmail => $sendout->fromName])
            ->setTo($contact->email)
            ->setSubject($sendout->subject)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        if ($sendout->replyToEmail) {
            $message->setReplyTo($sendout->replyToEmail);
        }

        // Fire a before event
        $event = new SendoutEmailEvent([
            'sendout' => $sendout,
            'contact' => $contact,
            'message' => $message,
        ]);
        $this->trigger(self::EVENT_BEFORE_SEND_EMAIL, $event);

        if (!$event->isValid) {
            return;
        }

        $success = false;

        // Attempt to send message
        for ($i = 0; $i < Campaign::$plugin->settings->maxSendAttempts; $i++) {
            $success = $message->send();

            if ($success) {
                break;
            }

            // Wait a second, in case we're being throttled
            sleep(1);
        }

        if ($success) {
            // Update sent date and save
            $contactCampaignRecord->sent = new DateTime();
            $contactCampaignRecord->save();

            // Update recipients and last sent
            $sendout->recipients++;
            $sendout->lastSent = new DateTime();

            $this->_updateSendoutRecord($sendout, ['recipients', 'lastSent']);
        } else {
            // Update failures and send status
            $sendout->failures++;

            if ($sendout->failures >= Campaign::$plugin->settings->maxSendFailuresAllowed) {
                $sendout->sendStatus = SendoutElement::STATUS_FAILED;
            }

            $this->_updateSendoutRecord($sendout, ['failures', 'sendStatus']);

            Campaign::$plugin->log('Sending of the sendout "{title}" to {email} failed after {sendAttempts} send attempt(s). Please check that your Campaign email settings are correctly configured and check the error in the Craft log.', [
                'title' => $sendout->title,
                'email' => $contact->email,
                'sendAttempts' => Campaign::$plugin->settings->maxSendAttempts,
            ]);
        }

        // Fire an after event
        if ($this->hasEventHandlers(self::EVENT_AFTER_SEND_EMAIL)) {
            $this->trigger(self::EVENT_AFTER_SEND_EMAIL, new SendoutEmailEvent([
                'sendout' => $sendout,
                'contact' => $contact,
                'message' => $message,
                'success' => $success,
            ]));
        }
    }

    /**
     * Sends a notification.
     */
    public function sendNotification(SendoutElement $sendout): void
    {
        if ($sendout->sendStatus != SendoutElement::STATUS_SENT &&
            $sendout->sendStatus != SendoutElement::STATUS_FAILED
        ) {
            return;
        }

        $notificationEmailAddresses = $sendout->getNotificationEmailAddresses();

        if (empty($notificationEmailAddresses)) {
            return;
        }

        $variables = [
            'title' => $sendout->title,
            'emailSettingsUrl' => UrlHelper::cpUrl('campaign/settings/email'),
            'sendoutUrl' => $sendout->cpEditUrl,
            'sendAttempts' => Campaign::$plugin->settings->maxSendAttempts,
        ];

        if ($sendout->sendStatus == SendoutElement::STATUS_SENT) {
            $subject = Craft::t('campaign', 'Sending completed: {title}', $variables);
            $htmlBody = Craft::t('campaign', 'Sending of the sendout "<a href="{sendoutUrl}">{title}</a>" has been successfully completed!!', $variables);
            $plaintextBody = Craft::t('campaign', 'Sending of the sendout "{title}" [{sendoutUrl}] has been successfully completed!!', $variables);
        } else {
            $subject = Craft::t('campaign', 'Sending failed: {title}', $variables);
            $htmlBody = Craft::t('campaign', 'Sending of the sendout "<a href="{sendoutUrl}">{title}</a>" failed after {sendAttempts} send attempt(s). Please check that your <a href="{emailSettingsUrl}">Campaign email settings</a> are correctly configured and check the error in the Craft log.', $variables);
            $plaintextBody = Craft::t('campaign', 'Sending of the sendout "{title}" [{sendoutUrl}] failed after {sendAttempts} send attempt(s). Please check that your Campaign email settings [{emailSettingsUrl}] are correctly configured and check the error in the Craft log.', $variables);
        }

        // Compose message
        $message = Campaign::$plugin->mailer->compose()
            ->setFrom([$sendout->fromEmail => $sendout->fromName])
            ->setTo($notificationEmailAddresses)
            ->setSubject($subject)
            ->setHtmlBody($htmlBody)
            ->setTextBody($plaintextBody);

        $message->send();
    }

    /**
     * Prepares sending.
     */
    public function prepareSending(SendoutElement $sendout, ?int $batch = null): void
    {
        if ($sendout->sendStatus !== SendoutElement::STATUS_SENDING) {
            $sendout->sendStatus = SendoutElement::STATUS_SENDING;

            $this->_updateSendoutRecord($sendout, ['sendStatus']);
        }

        // Set the current site from the sendout’s site ID
        Craft::$app->getSites()->setCurrentSite($sendout->siteId);

        if ($batch !== null) {
            Campaign::$plugin->log('Sending batch {batch} of sendout "{title}".', [
                'batch' => $batch,
                'title' => $sendout->title,
            ]);
        }
    }

    /**
     * Finalises sending.
     */
    public function finaliseSending(SendoutElement $sendout): void
    {
        if ($sendout->sendStatus != SendoutElement::STATUS_FAILED) {
            if ($sendout->sendStatus == SendoutElement::STATUS_SENDING) {
                $sendout->sendStatus = SendoutElement::STATUS_SENT;
            }

            // Update send status to pending if automated or recurring or not fully complete
            if ($sendout->sendoutType == 'automated' ||
                $sendout->sendoutType == 'recurring' ||
                $this->getPendingRecipientCount($sendout) > 0
            ) {
                $sendout->sendStatus = SendoutElement::STATUS_PENDING;
            }

            Campaign::$plugin->log('Sending of sendout "{title}" completed.', [
                'title' => $sendout->title,
            ]);
        }

        $campaign = $sendout->getCampaign();

        if ($campaign !== null) {
            // Update HTML and plaintext body
            $contact = new ContactElement();
            $sendout->htmlBody = $campaign->getHtmlBody($contact, $sendout);
            $sendout->plaintextBody = $campaign->getPlaintextBody($contact, $sendout);

            if (Craft::$app->getDb()->getIsMysql()) {
                // Encode any 4-byte UTF-8 characters
                $sendout->htmlBody = StringHelper::encodeMb4($sendout->htmlBody);
                $sendout->plaintextBody = StringHelper::encodeMb4($sendout->plaintextBody);
            }
        }

        $this->_updateSendoutRecord($sendout, ['sendStatus', 'htmlBody', 'plaintextBody']);

        // Update campaign recipients
        $recipients = ContactCampaignRecord::find()
            ->where(['campaignId' => $campaign->id])
            ->count();

        $campaign->recipients = $recipients;

        Craft::$app->getElements()->saveElement($campaign, false);

        // Send notification email
        $this->sendNotification($sendout);
    }

    /**
     * Pauses a sendout.
     */
    public function pauseSendout(SendoutElement $sendout): bool
    {
        if (!$sendout->getIsPausable()) {
            return false;
        }

        $sendout->sendStatus = SendoutElement::STATUS_PAUSED;

        return $this->_updateSendoutRecord($sendout, ['sendStatus']);
    }

    /**
     * Cancels a sendout.
     */
    public function cancelSendout(SendoutElement $sendout): bool
    {
        if (!$sendout->getIsCancellable()) {
            return false;
        }

        $sendout->sendStatus = SendoutElement::STATUS_CANCELLED;

        return $this->_updateSendoutRecord($sendout, ['sendStatus']);
    }

    /**
     * Deletes a sendout.
     */
    public function deleteSendout(SendoutElement $sendout): bool
    {
        if (!$sendout->getIsDeletable()) {
            return false;
        }

        return Craft::$app->getElements()->deleteElement($sendout);
    }

    /**
     * Returns a mailing list by ID.
     */
    private function _getMailingListById(int $mailingListId): MailingListElement
    {
        if (!empty($this->_mailingLists[$mailingListId])) {
            return $this->_mailingLists[$mailingListId];
        }

        $this->_mailingLists[$mailingListId] = Campaign::$plugin->mailingLists->getMailingListById($mailingListId);

        return $this->_mailingLists[$mailingListId];
    }

    /**
     * Returns excluded mailing list recipients query.
     */
    private function _getExcludedMailingListRecipientsQuery(SendoutElement $sendout): ActiveQuery
    {
        return ContactMailingListRecord::find()
            ->select('contactId')
            ->where([
                'mailingListId' => $sendout->excludedMailingListIds,
                'subscriptionStatus' => 'subscribed',
            ]);
    }

    /**
     * Returns excluded recipients query.
     */
    private function _getSentRecipientsQuery(SendoutElement $sendout, bool $todayOnly = false): ActiveQuery
    {
        $query = ContactCampaignRecord::find()
            ->select('contactId')
            ->where(['sendoutId' => $sendout->id])
            ->andWhere(['not', ['sent' => null]]);

        if ($todayOnly) {
            $now = new DateTime();

            // Add condition that sent is today
            $query->andWhere(Db::parseDateParam('sent', $now->format('Y-m-d'), '>'));
        }

        return $query;
    }

    /**
     * Returns the standard sendout’s shared base query condition config.
     */
    private function _getPendingRecipientsStandardBaseCondition(SendoutElement $sendout): array
    {
        $baseCondition = [
            'mailingListId' => $sendout->mailingListIds,
            'subscriptionStatus' => 'subscribed',
        ];

        return $baseCondition;
    }

    /**
     * Returns the standard sendout’s pending contact IDs.
     */
    private function _getPendingRecipientsStandardIds(SendoutElement $sendout): array
    {
        App::maxPowerCaptain();

        $baseCondition = $this->_getPendingRecipientsStandardBaseCondition($sendout);

        // Get contacts subscribed to sendout's mailing lists
        $query = ContactMailingListRecord::find()
            ->select(['contactId', 'min([[mailingListId]]) as mailingListId', 'min([[subscribed]]) as subscribed'])
            ->groupBy('contactId')
            ->andWhere($baseCondition);

        // Ensure contacts have not complained, bounced, or been blocked (in contact record)
        $query->innerJoin(ContactRecord::tableName() . ' contact', '[[contact.id]] = [[contactId]]')
            ->andWhere([
                'contact.complained' => null,
                'contact.bounced' => null,
                'contact.blocked' => null,
            ]);

        // Exclude contacts subscribed to sendout's excluded mailing lists
        $query->andWhere(['not', ['contactId' => $this->_getExcludedMailingListRecipientsQuery($sendout)]]);

        // Check whether we should exclude recipients that were sent to today only
        $schedule = $sendout->getSchedule();
        $excludeSentTodayOnly = $sendout->sendoutType == 'recurring' && $schedule->canSendToContactsMultipleTimes;

        // Exclude sent recipients
        $query->andWhere(['not', ['contactId' => $this->_getSentRecipientsQuery($sendout, $excludeSentTodayOnly)]]);

        // Get contact IDs
        $contactIds = $query->column();

        // Filter recipients by segments
        if ($sendout->segmentIds) {
            foreach ($sendout->getSegments() as $segment) {
                $contactIds = Campaign::$plugin->segments->getFilteredContactIds($segment, $contactIds);
            }
        }

        return $contactIds;
    }

    /**
     * Returns the standard sendout’s pending contacts.
     */
    private function _getPendingRecipientsStandard(SendoutElement $sendout): array
    {
        App::maxPowerCaptain();

        $baseCondition = $this->_getPendingRecipientsStandardBaseCondition($sendout);
        $contactIds = $this->_getPendingRecipientsStandardIds($sendout);

        // Get recipients as array
        return ContactMailingListRecord::find()
            ->select(['contactId', 'min([[mailingListId]]) as mailingListId', 'min([[subscribed]]) as subscribed'])
            ->groupBy('contactId')
            ->where($baseCondition)
            ->andWhere(['contactId' => $contactIds])
            ->orderBy(['contactId' => SORT_ASC])
            ->asArray()
            ->all();
    }

    /**
     * Returns the automated sendout's pending recipients.
     */
    private function _getPendingRecipientsAutomated(SendoutElement $sendout): array
    {
        $recipients = $this->_getPendingRecipientsStandard($sendout);

        /** @var AutomatedScheduleModel $schedule */
        $schedule = $sendout->getSchedule();

        // Remove any contacts that do not meet the conditions
        foreach ($recipients as $key => $recipient) {
            $subscribedDateTime = DateTimeHelper::toDateTime($recipient['subscribed']);
            $subscribedDateTimePlusDelay = $subscribedDateTime->modify('+' . $schedule->timeDelay . ' ' . $schedule->timeDelayInterval);

            // If subscribed date was before sendout was created or time plus delay has not yet passed
            if ($subscribedDateTime < $sendout->dateCreated || !DateTimeHelper::isInThePast($subscribedDateTimePlusDelay)) {
                unset($recipients[$key]);
            }
        }

        return $recipients;
    }

    /**
     * Returns the singular sendout's pending contact IDs.
     */
    private function _getPendingRecipientsSingular(SendoutElement $sendout): array
    {
        $recipients = [];
        $excludeContactIds = $this->_getSentRecipientsQuery($sendout)->column();
        $contactIds = array_diff($sendout->contactIds, $excludeContactIds);

        // Filter recipients by segments
        if ($sendout->segmentIds) {
            foreach ($sendout->getSegments() as $segment) {
                $contactIds = Campaign::$plugin->segments->getFilteredContactIds($segment, $contactIds);
            }
        }

        foreach ($contactIds as $contactId) {
            $recipients[] = [
                'contactId' => $contactId,
                'mailingListId' => null,
                'subscribed' => null,
            ];
        }

        return $recipients;
    }

    /**
     * Updates a sendout's record with the provided fields.
     */
    private function _updateSendoutRecord(SendoutElement $sendout, array $fields): bool
    {
        /** @var SendoutRecord|null $sendoutRecord */
        $sendoutRecord = SendoutRecord::find()->where(['id' => $sendout->id])->one();

        if ($sendoutRecord === null) {
            return false;
        }

        // Set attributes from sendout's fields
        $sendoutRecord->setAttributes($sendout->toArray($fields), false);

        if (!$sendoutRecord->save()) {
            return false;
        }

        // Invalidate caches for the sendout, since the update may have been
        // made programmatically.
        Craft::$app->getElements()->invalidateCachesForElement($sendout);

        return true;
    }

    /**
     * Converts links.
     */
    private function _convertLinks(string $body, ContactElement $contact, SendoutElement $sendout): string
    {
        // Get base URL
        $path = Craft::$app->getConfig()->getGeneral()->actionTrigger . '/campaign/t/click';
        $baseUrl = UrlHelper::siteUrl($path, ['cid' => $contact->cid, 'sid' => $sendout->sid]);

        // Use DOMDocument to parse links
        $dom = new DOMDocument();

        // Suppress markup errors and prepend XML tag to force utf-8 encoding (https://gist.github.com/Xeoncross/9401853)
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $body);

        /** @var DOMElement[] $elements */
        $elements = $dom->getElementsByTagName('a');

        foreach ($elements as $element) {
            $url = $element->getAttribute('href');
            $title = $element->getAttribute('title');

            // If URL begins with http
            if (str_starts_with($url, 'http')) {
                // Ignore if unsubscribe link
                if (preg_match('/\/campaign\/(t|tracker)\/unsubscribe/i', $url)) {
                    continue;
                }

                $key = $url . ':' . $title;

                // If link has not yet been converted
                if (!isset($this->_links[$key])) {
                    // Check for link record in database
                    $linkRecord = LinkRecord::findOne([
                        'campaignId' => $sendout->campaignId,
                        'url' => $url,
                        'title' => $title,
                    ]);

                    // Create new record if not found
                    if ($linkRecord === null) {
                        $linkRecord = new LinkRecord();
                        $linkRecord->campaignId = $sendout->campaignId;
                        $linkRecord->url = $url;
                        $linkRecord->title = $title;

                        $linkRecord->save();
                    }

                    // Add link to converted links
                    $this->_links[$key] = $linkRecord->lid;
                }

                $lid = $this->_links[$key];

                // Replace href attribute
                $element->setAttribute('href', $baseUrl . '&lid=' . $lid);
            }
        }

        // Save document element to maintain utf-8 encoding (https://gist.github.com/Xeoncross/9401853)
        return $dom->saveHTML($dom->documentElement);
    }
}
