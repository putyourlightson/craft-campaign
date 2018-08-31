# Sendouts

Sendouts are how you send campaigns to your mailing lists. Sendouts can be sent immediately, on a scheduled date and time, or at a specific delayed interval after a contact subscribes to a mailing list.

### Sendout Types

The time at which sendouts are sent is determined by the sendout type they belong to.

#### Regular
Regular sendouts are queued for sending immediately after being saved and sent.

#### Scheduled
Scheduled sendouts allow you an exact date and time on which to send a campaign. As soon as the send date is reached, the sendout will be queued for sending.  
*Note: Scheduled sendouts require that you create a cron job as described in Settings → General Settings.*
![Scheduled Sendout](/images/scheduled-sendout.png)

#### Automated (pro feature)
Automated sendouts allow you to automate the delayed sending of a campaign to contacts, a specific amount of time after they subscribe to one or more mailing lists. As soon as the delayed period of time has passed, the sendout will be automatically queued for sending.  
*Note: Automated sendouts require that you create a cron job as described in Settings → General Settings.*
![Automated Sendout](/images/automated-sendout.png)

### How Sendouts Are Sent
Once a sendout is queued for sending, it will begin sending the next time that the queue is run. If Craft's `runQueueAutomatically`config setting is set to `true` (the default value), then this will happen on the next control panel page request, otherwise it will happen on the next manual `queue/run` call (initiated from a cron job, for example). The sendout is sent in a background process, so the site will remain usable for all visitors. 

#### Delayed Batch Sending
In order to avoid a timeout or the memory limit being exceeded while sending, the plugin will initiate a new delayed batch job when it exceeds a threshold of either of the limits. The thresholds, the limits, the max batch size and the batch job delay can all be defined in the plugin's config settings.

    // The threshold for memory usage per sendout batch as a fraction
    'memoryThreshold' => 0.8,

    // The threshold for execution time per sendout batch as a fraction
    'timeThreshold' => 0.8,

    // The memory usage limit per sendout batch in bytes or a shorthand byte value (set to -1 for unlimited)
    'memoryLimit' => '1024M',

    // The execution time limit per sendout batch in seconds (set to 0 for unlimited)
    'timeLimit' => 300,

    // The max size of sendout batches
    'maxBatchSize' => 1000,

    // The amount of time in seconds to delay jobs between sendout batches
    'batchJobDelay' => 10,

### Getting Sendouts
You can access sendouts from your templates with `craft.campaign.sendouts` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html).

    // Gets all sendouts that were sent to the campaign with the specified ID
    {% set sendouts = craft.campaign.sendouts.sendoutType('sent').campaignId(5).all %}
    {% for sendout in sendouts %}
       {{ sendout.title }} sent on {{ sendout.sendDate|date }}
    {% endfor %}  

You can get sendouts from your plugin with `SendoutElement::find()` which returns an [Element Query](https://docs.craftcms.com/v3/element-queries.html). 

    use putyourlightson\campaign\elements\SendoutElement;

    $sendouts = SendoutElement::find()->sendoutType('sent')->campaignId(5)->all();

In addition to supporting the parameters that all element types in Craft support (`id`, `title`, etc.), the returned Element Query also supports the following parameters.

**`sid`**  
Only fetch sendouts with the given SID (unique sendout ID).

**`sendoutType`**  
Only fetch sendouts with the given sendout type (regular / scheduled / automated).

**`campaignId`**  
Only fetch sendouts with the given campaign ID.

**`mailingListId`**  
Only fetch sendouts with the given mailing list ID.