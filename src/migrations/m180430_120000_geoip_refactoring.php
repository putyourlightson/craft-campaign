<?php

namespace putyourlightson\campaign\migrations;

use putyourlightson\campaign\elements\ContactElement;

use Craft;
use craft\db\Migration;
use craft\helpers\Json;
use putyourlightson\campaign\records\ContactCampaignRecord;
use putyourlightson\campaign\records\ContactMailingListRecord;

/**
 * m180430_120000_geoip_refactoring migration.
 */
class m180430_120000_geoip_refactoring extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Resave contacts
        $contacts = ContactElement::find()
            ->where(['not', ['geoIp' => null]])
            ->andWhere(['not', ['geoIp' => '']])
            ->all();

        foreach ($contacts as $contact) {
            if (empty($contact->geoIp)) {
                continue;
            }

            $contact->geoIp = [
                'city' => $geoIp['city'] ?? '',
                'postCode' => $geoIp['zip_code'] ?? '',
                'regionCode' => $geoIp['region_code'] ?? '',
                'regionName' => $geoIp['region_name'] ?? '',
                'countryCode' => $geoIp['country_code'] ?? '',
                'countryName' => $geoIp['country_name'] ?? '',
                'timeZone' => $geoIp['time_zone'] ?? '',
            ];

            Craft::$app->elements->saveElement($contact);
        }

        // Resave contact campaigns
        $contactCampaigns = ContactCampaignRecord::find()
            ->where(['not', ['geoIp' => null]])
            ->andWhere(['not', ['geoIp' => '']])
            ->all();

        foreach ($contactCampaigns as $contactCampaign) {
            /** @var ContactCampaignRecord $contactCampaign */
            $geoIp = Json::decode($contactCampaign->geoIp);

            $contactCampaign->geoIp = [
                'city' => $geoIp['city'] ?? '',
                'postCode' => $geoIp['zip_code'] ?? '',
                'regionCode' => $geoIp['region_code'] ?? '',
                'regionName' => $geoIp['region_name'] ?? '',
                'countryCode' => $geoIp['country_code'] ?? '',
                'countryName' => $geoIp['country_name'] ?? '',
                'timeZone' => $geoIp['time_zone'] ?? '',
            ];

            $contactCampaign->save();
        }

        // Resave contact mailing lists
        $contactMailingLists = ContactMailingListRecord::find()
            ->where(['not', ['geoIp' => null]])
            ->andWhere(['not', ['geoIp' => '']])
            ->all();

        foreach ($contactMailingLists as $contactMailingList) {
            /** @var ContactMailingListRecord $contactMailingList */
            $geoIp = Json::decode($contactMailingList->geoIp);

            $contactMailingList->geoIp = [
                'city' => $geoIp['city'] ?? '',
                'postCode' => $geoIp['zip_code'] ?? '',
                'regionCode' => $geoIp['region_code'] ?? '',
                'regionName' => $geoIp['region_name'] ?? '',
                'countryCode' => $geoIp['country_code'] ?? '',
                'countryName' => $geoIp['country_name'] ?? '',
                'timeZone' => $geoIp['time_zone'] ?? '',
            ];

            $contactMailingList->save();
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m180430_120000_geoip_refactoring cannot be reverted.\n";

        return false;
    }
}
