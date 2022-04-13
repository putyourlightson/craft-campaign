<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\SendoutElement;

return [
    [
        'title' => 'Sendout 1',
        'subject' => 'Subject 1',
        'sendoutType' => 'regular',
        'sendStatus' => SendoutElement::STATUS_PENDING,
        'sendDate' => new DateTime(),
        'senderId' => $this->senderId,
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
        'sendDate' => new DateTime(),
        'senderId' => $this->senderId,
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
        'schedule' => [
            'timeDelay' => 1,
            'timeDelayInterval' => 'days',
            'daysOfWeek' => [1, 2, 3, 4, 5, 6, 7],
        ],
        'sendStatus' => SendoutElement::STATUS_PENDING,
        'senderId' => $this->senderId,
        'campaignId' => $this->campaignId,
        'mailingListIds' => $this->mailingListIds,
        'segmentIds' => $this->segmentIds,
        'fromName' => 'From Name',
        'fromEmail' => 'from@email.com',
        'notificationEmailAddress' => 'notify@email.com',
    ],
];
