<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

use putyourlightson\campaign\elements\SendoutElement;

return [
    [
        'title' => 'Sendout 1',
        'subject' => 'Subject 1',
        'sendoutType' => 'regular',
        'sendStatus' => SendoutElement::STATUS_SENDING,
        'campaignId' => $this->campaignId,
        'mailingListIds' => $this->mailingListIds,
        'fromName' => 'From Name',
        'fromEmail' => 'from@email.com',
        'notificationEmailAddress' => 'notify@email.com',
    ],
    [
        'title' => 'Sendout 2',
        'subject' => 'Subject 2',
        'sendoutType' => 'regular',
        'sendStatus' => SendoutElement::STATUS_FAILED,
        'campaignId' => $this->campaignId,
        'mailingListIds' => $this->mailingListIds,
        'fromName' => 'From Name',
        'fromEmail' => 'from@email.com',
        'notificationEmailAddress' => 'notify@email.com',
    ],

];
