<?php

use craft\elements\User;
use craft\helpers\App;
use craft\records\Element as ElementRecord;
use Faker\Factory as FakerFactory;
use markhuot\craftpest\test\TestCase;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\elements\MailingListElement;
use putyourlightson\campaign\elements\SendoutElement;
use putyourlightson\campaign\helpers\StringHelper;
use putyourlightson\campaign\models\ImportModel;
use putyourlightson\campaign\records\CampaignRecord;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactRecord;
use putyourlightson\campaign\records\ImportRecord;
use putyourlightson\campaign\records\LinkRecord;
use putyourlightson\campaign\records\MailingListRecord;
use putyourlightson\campaign\records\PendingContactRecord;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)
    ->afterAll(function() {
        cleanup();
        Craft::$app->queue->releaseAll();
    })
    ->in('./');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function debug(mixed $value): void
{
    ob_get_clean();

    var_dump($value);
}

function createCampaign(array $attributes = []): CampaignElement
{
    $faker = FakerFactory::create();
    $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeByHandle(App::env('TEST_CAMPAIGN_TYPE_HANDLE'));
    $campaign = new CampaignElement($attributes);
    $campaign->title = $faker->sentence();
    $campaign->campaignTypeId = $campaignType->id;
    Craft::$app->elements->saveElement($campaign);

    return $campaign;
}

function createMailingList(array $attributes = []): MailingListElement
{
    $faker = FakerFactory::create();
    $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeByHandle(App::env('TEST_MAILING_LIST_TYPE_HANDLE'));
    $mailingList = new MailingListElement($attributes);
    $mailingList->title = $faker->sentence();
    $mailingList->mailingListTypeId = $mailingListType->id;
    Craft::$app->elements->saveElement($mailingList);

    return $mailingList;
}

function createContact(string $email = null, array $attributes = []): ContactElement
{
    $faker = FakerFactory::create();
    $contact = new ContactElement($attributes);
    $contact->email = $email ?? $faker->email();
    Craft::$app->elements->saveElement($contact);

    return $contact;
}

function createSendout(int $campaignId = null, array $mailingListIds = null, array $attributes = []): SendoutElement
{
    $faker = FakerFactory::create();
    $sendout = new SendoutElement($attributes);
    $sendout->sendoutType = 'regular';
    $sendout->campaignId = $campaignId ?? createCampaign()->id;
    $sendout->mailingListIds = $mailingListIds ?? [createMailingList()->id];
    $sendout->fromName = $faker->name();
    $sendout->fromEmail = $faker->email();
    $sendout->title = $faker->sentence();
    $sendout->subject = $faker->sentence();
    Craft::$app->elements->saveElement($sendout);

    return $sendout;
}

function createSendoutWithSubscribedContact(?ContactElement $contact = null, array $attributes = []): SendoutElement
{
    $contact = $contact ?? createContact(null, $attributes);
    $campaign = createCampaign();
    $mailingList = createMailingList();
    Campaign::$plugin->mailingLists->addContactInteraction($contact, $mailingList, 'subscribed');

    $sendout = createSendout($campaign->id, [$mailingList->id]);

    $contactCampaignRecord = new ContactCampaignRecord();
    $contactCampaignRecord->campaignId = $campaign->id;
    $contactCampaignRecord->contactId = $contact->id;
    $contactCampaignRecord->mailingListId = $mailingList->id;
    $contactCampaignRecord->sendoutId = $sendout->id;
    $contactCampaignRecord->links = '';
    $contactCampaignRecord->save();

    return $sendout;
}

function createUser(): User
{
    $faker = FakerFactory::create();
    $user = new User();
    $user->active = true;
    $user->username = $faker->userName();
    $user->email = $faker->email();
    Craft::$app->elements->saveElement($user);

    return $user;
}

function createLinkRecord(int $campaignId): LinkRecord
{
    $faker = FakerFactory::create();
    $linkRecord = new LinkRecord();
    $linkRecord->campaignId = $campaignId;
    $linkRecord->url = $faker->url();
    $linkRecord->title = $faker->sentence();
    $linkRecord->save();

    return $linkRecord;
}

function createPendingContactRecord(int $mailingListId, ?string $email = null): PendingContactRecord
{
    $faker = FakerFactory::create();
    $pendingContactRecord = new PendingContactRecord();
    $pendingContactRecord->email = $email ?? $faker->email();
    $pendingContactRecord->pid = StringHelper::uniqueId('p');
    $pendingContactRecord->mailingListId = $mailingListId;
    $pendingContactRecord->fieldData = [];
    $pendingContactRecord->save();

    return $pendingContactRecord;
}

function createImport(int $mailingListId): ImportModel
{
    return new ImportModel([
        'emailFieldIndex' => 'email',
        'mailingListId' => $mailingListId,
    ]);
}

function cleanup(): void
{
    $campaignType = Campaign::$plugin->campaignTypes->getCampaignTypeByHandle(App::env('TEST_CAMPAIGN_TYPE_HANDLE'));
    $campaignIds = CampaignRecord::find()
        ->select('id')
        ->where(['campaignTypeId' => $campaignType->id])
        ->column();

    $mailingListType = Campaign::$plugin->mailingListTypes->getMailingListTypeByHandle(App::env('TEST_MAILING_LIST_TYPE_HANDLE'));
    $mailingListIds = MailingListRecord::find()
        ->select('id')
        ->where(['mailingListTypeId' => $mailingListType->id])
        ->column();

    $contactIds = ContactRecord::find()
        ->column();

    $userGroup = Craft::$app->userGroups->getGroupByHandle(App::env('TEST_USER_GROUP_HANDLE'));
    $userIds = User::find()
        ->select('id')
        ->groupId($userGroup->id)
        ->column();

    ElementRecord::deleteAll(['id' => array_merge($campaignIds, $mailingListIds, $contactIds, $userIds)]);

    ImportRecord::deleteAll();
    LinkRecord::deleteAll();
    PendingContactRecord::deleteAll();

    Craft::$app->elements->invalidateAllCaches();
}
