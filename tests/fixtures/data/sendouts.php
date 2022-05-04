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
        'notificationContactIds' => $this->notificationContactIds,
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
        'notificationContactIds' => $this->notificationContactIds,
    ],
    [
        'title' => 'Sendout 3',
        'subject' => 'Subject 3',
        'sendoutType' => 'automated',
        'schedule' => [
            'timeDelay' => 1,
            'timeDelayInterval' => 'minutes',
            'daysOfWeek' => [1 => 1, 2 => 1, 3 => 1, 4 => 1, 5 => 1, 6 => 1, 7 => 1],
            'condition' => null,
        ],
        'sendStatus' => SendoutElement::STATUS_PENDING,
        'senderId' => $this->senderId,
        'campaignId' => $this->campaignId,
        'mailingListIds' => $this->mailingListIds,
        'segmentIds' => $this->segmentIds,
        'fromName' => 'From Name',
        'fromEmail' => 'from@email.com',
        'notificationContactIds' => $this->notificationContactIds,
    ],
];
