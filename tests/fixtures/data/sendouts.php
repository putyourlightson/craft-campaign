<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

use craft\helpers\Db;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\SendoutElement;

return [
    [
        'title' => 'Sendout 1',
        'subject' => 'Subject 1',
        'sendoutType' => 'regular',
        'sendStatus' => SendoutElement::STATUS_PENDING,
        'sendDate' => Db::prepareDateForDb(new DateTime()),
        'campaignId' => $this->campaignId,
        'mailingListIds' => $this->mailingListIds,
        'segmentIds' => $this->segmentIds,
        'fromName' => 'From Name',
        'fromEmail' => 'from@email.com',
        'notificationEmailAddress' => 'notify@email.com',
    ],
    [
        'title' => 'Sendout 2',
        'subject' => 'Subject 2',
        'sendoutType' => 'regular',
        'sendStatus' => SendoutElement::STATUS_PENDING,
        'sendDate' => Db::prepareDateForDb(new DateTime()),
        'campaignId' => CampaignElement::find()->campaignType('campaignType2')->one()->id,
        'mailingListIds' => $this->mailingListIds,
        'segmentIds' => $this->segmentIds,
        'fromName' => 'From Name',
        'fromEmail' => 'from@email.com',
        'notificationEmailAddress' => 'notify@email.com',
    ],
    [
        'title' => 'Sendout 3',
        'subject' => 'Subject 3',
        'sendoutType' => 'automated',
        'sendStatus' => SendoutElement::STATUS_PENDING,
        'campaignId' => $this->campaignId,
        'mailingListIds' => $this->mailingListIds,
        'segmentIds' => $this->segmentIds,
        'fromName' => 'From Name',
        'fromEmail' => 'from@email.com',
        'notificationEmailAddress' => 'notify@email.com',
    ],
];
