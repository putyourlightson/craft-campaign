<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use DateTime;
use DeviceDetector\DeviceDetector;
use GuzzleHttp\Exception\ConnectException;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use putyourlightson\campaign\records\ContactRecord;

/**
 * @since 1.10.0
 */
class ContactActivityHelper
{
    /**
     * @var array|null
     */
    private static ?array $_geoIp = null;

    /**
     * @var array|null
     */
    private static ?array $_device = null;

    /**
     * Updates contact activity.
     */
    public static function updateContactActivity(ContactElement $contact): void
    {
        // Get contact record
        $contactRecord = ContactRecord::findOne($contact->id);

        $contactRecord->lastActivity = new DateTime();

        // Get GeoIP if enabled
        if (Campaign::$plugin->settings->geoIp) {
            $geoIp = self::getGeoIp();

            // If GeoIP and country exist
            if ($geoIp && !empty($geoIp['countryName'])) {
                $contactRecord->country = $geoIp['countryName'];
                $contactRecord->geoIp = $geoIp;
            }
        }

        // Get device
        $device = self::getDevice();

        if ($device !== null) {
            $contactRecord->device = $device['device'];
            $contactRecord->os = $device['os'];
            $contactRecord->client = $device['client'];
        }

        $contactRecord->save();
    }

    /**
     * Gets geolocation based on IP address.
     */
    public static function getGeoIp(int $timeout = 5): ?array
    {
        if (self::$_geoIp !== null) {
            return self::$_geoIp;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return null;
        }

        $client = Craft::createGuzzleClient([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
        ]);

        try {
            $ip = Craft::$app->getRequest()->getRemoteIP();
            $apiKey = App::parseEnv(Campaign::$plugin->settings->ipstackApiKey);

            /** @noinspection HttpUrlsUsage */
            $response = $client->get('http://api.ipstack.com/' . $ip . '?access_key=' . $apiKey);

            if ($response->getStatusCode() == 200) {
                $geoIp = Json::decodeIfJson($response->getBody());
            }
        }
        catch (ConnectException) {
        }

        // If country is empty then return null
        if (empty($geoIp['country_code'])) {
            return null;
        }

        self::$_geoIp = [
            'continentCode' => $geoIp['continent_code'] ?? '',
            'continentName' => $geoIp['continent_name'] ?? '',
            'countryCode' => $geoIp['country_code'],
            'countryName' => $geoIp['country_name'] ?? '',
            'regionCode' => $geoIp['region_code'] ?? '',
            'regionName' => $geoIp['region_name'] ?? '',
            'city' => $geoIp['city'] ?? '',
            'postCode' => $geoIp['zip_code'] ?? '',
            'timeZone' => $geoIp['time_zone']['id'] ?? '',
        ];

        return self::$_geoIp;
    }

    /**
     * Gets device based on user agent provided it is not a bot.
     */
    public static function getDevice(): ?array
    {
        if (self::$_device !== null) {
            return self::$_device;
        }

        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            return null;
        }

        // Must be a string.
        $userAgent = Craft::$app->getRequest()->getUserAgent() ?? '';
        $deviceDetector = new DeviceDetector($userAgent);

        $deviceDetector->parse();

        if ($deviceDetector->isBot()) {
            return null;
        }

        self::$_device = [
            'device' => $deviceDetector->getDeviceName(),
            'os' => $deviceDetector->getOs('name'),
            'client' => $deviceDetector->getClient('name'),
        ];

        // Replace unknown values with blank string
        foreach (self::$_device as &$value) {
            $value = ($value == DeviceDetector::UNKNOWN) ? '' : $value;
        }

        return self::$_device;
    }
}
