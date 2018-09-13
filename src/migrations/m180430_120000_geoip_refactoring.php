<?php

namespace putyourlightson\campaign\migrations;

use putyourlightson\campaign\elements\ContactElement;

use Craft;
use craft\db\Migration;

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

            $geoIp = $contact->geoIp;

            $contact->geoIp = [
                'city' => $geoIp['city'] ?? '',
                'postCode' => $geoIp['zip_code'] ?? '',
                'regionCode' => $geoIp['region_code'] ?? '',
                'regionName' => $geoIp['region_name'] ?? '',
                'countryCode' => $geoIp['country_code'] ?? '',
                'countryName' => $geoIp['country_name'] ?? '',
                'timeZone' => $geoIp['time_zone'] ?? '',
            ];

            Craft::$app->getElements()->saveElement($contact);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class." cannot be reverted.\n";

        return false;
    }
}
