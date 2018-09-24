# Sendouts

Sendouts are how you send campaigns to your mailing lists. Sendouts can be sent immediately, on a scheduled date and time, or at a specific delayed interval after a contact subscribes to a mailing list.

### Sendout Types

The time at which sendouts are sent is determined by the sendout type they belong to.

#### Regular
Regular sendouts are queued for sending immediately after being saved and sent.

#### Scheduled
Scheduled sendouts allow you an exact date and time on which to send a campaign. As soon as the send date is reached, the sendout will be queued for sending.  
*Note: Scheduled sendouts require that you create a cron job as described in Settings → General Settings.*  
![Scheduled Sendout](https://raw.githubusercontent.com/putyourlightson/craft-campaign/develop/docs/images/sendout-scheduled-1.2.0.png)

#### Automated (pro feature)
Automated sendouts allow you to automate the delayed sending of a campaign to contacts, a specific amount of time after they subscribe to one or more mailing lists. As soon as the delayed period of time has passed, the sendout will be automatically queued for sending according to the schedule that you set.  
*Note: Automated sendouts require that you create a cron job as described in Settings → General Settings.*  
![Automated Sendout](https://raw.githubusercontent.com/putyourlightson/craft-campaign/develop/docs/images/sendout-automated-1.2.0.png)

#### Recurring (pro feature)
Recurring sendouts allow you to automate the sending of a campaign to contacts on a recurring schedule. You must consider and select whether the sendout can be sent to contacts multiple times. The sendout will be automatically queued for sending according to the schedule that you set.  
*Note: Recurring sendouts require that you create a cron job as described in Settings → General Settings.*  
![Recurring Sendout](https://raw.githubusercontent.com/putyourlightson/craft-campaign/develop/docs/images/sendout-recurring-1.2.0.png)

### How Sendouts Are Sent
Once a sendout is queued for sending, it will begin sending the next time that the queue is run. If Craft's `runQueueAutomatically`config setting is set to `true` (the default value), then this will happen immediately, otherwise it will happen the next time the queue is run (initiated from a cron job, for example). The sendout is sent in a background process, so the site will remain usable for all visitors. 

#### Running Pending Sendouts
Sendouts that are not immediately sent (scheduled, automated or recurring), require a cron job in order to be queued and run at the appropriate time. If you plan on using these sendout types then you should create a cron job to run pending sendouts on a scheduled basis (every 5 minutes for example). Change `/usr/bin/php` to your PHP path (if different) and `/var/www/my_craft_project` to your craft project path.

    5 * * * * /usr/bin/php /var/www/my_project/craft campaign/sendouts/run-pending-sendouts

You can queue pending sendouts with a controller action through a unique URL, see Settings → General Settings. You can also manually queue pending sendouts at any time using the utility at Utilities → Campaign.

A command line utility can also be used to queue and run pending sendouts with the following console command:

    ./craft campaign/sendouts/run-pending-sendouts

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

**`segmentId`**  
Only fetch sendouts with the given segment ID.