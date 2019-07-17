<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\errors\ElementNotFoundException;
use craft\helpers\Json;
use DateTime;
use DeviceDetector\DeviceDetector;
use GuzzleHttp\Exception\ConnectException;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\elements\ContactElement;
use Throwable;
use yii\base\Exception;

/**
 * ContactActivityHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */
class ContactActivityHelper
{
    // Properties
    // =========================================================================

    /**
     * @var mixed
     */
    private static $_geoIp;

    /**
     * @var DeviceDetector|null
     */
    private static $_deviceDetector;

    // Static Methods
    // =========================================================================

    /**
     * Update contact activity
     *
     * @param ContactElement $contact
     *
     * @throws ElementNotFoundException
     * @throws Exception
     * @throws Throwable
     */
    public static function updateContactActivity(ContactElement $contact)
    {
        $contact->lastActivity = new DateTime();

        // Get GeoIP if enabled
        if (Campaign::$plugin->getSettings()->geoIp) {
            if (self::$_geoIp === null) {
                self::$_geoIp = self::getGeoIp();
            }

            // If country exists
            if (!empty(self::$_geoIp['countryName'])) {
                $contact->country = self::$_geoIp['countryName'];
                $contact->geoIp = self::$_geoIp;
            }
        }

        // Get device detector
        if (self::$_deviceDetector === null) {
            $userAgent = Craft::$app->getRequest()->getUserAgent();
            self::$_deviceDetector = new DeviceDetector($userAgent);
        }

        self::$_deviceDetector->parse();
        $device = self::$_deviceDetector->getDeviceName();

        // If device exists and not a bot
        if ($device AND !self::$_deviceDetector->isBot()) {
            $contact->device = $device;

            $os = self::$_deviceDetector->getOs('name');
            $contact->os = $os == DeviceDetector::UNKNOWN ? '' : $os;

            $client = self::$_deviceDetector->getClient('name');
            $contact->client = $client == DeviceDetector::UNKNOWN ? '' : $client;
        }

        Craft::$app->getElements()->saveElement($contact);
    }

    /**
     * Gets geolocation based on IP address
     *
     * @param int|null
     *
     * @return array|null
     */
    public static function getGeoIp(int $timeout = null)
    {
        $timeout = $timeout ?? 5;

        $geoIp = null;

        $client = Craft::createGuzzleClient([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
        ]);

        try {
            $ip = Craft::$app->getRequest()->getUserIP();
            $apiKey = Craft::parseEnv(Campaign::$plugin->getSettings()->ipstackApiKey);

            $response = $client->get('http://api.ipstack.com/'.$ip.'?access_key='.$apiKey);

            if ($response->getStatusCode() == 200) {
                $geoIp = Json::decodeIfJson($response->getBody());
            }
        }
        catch (ConnectException $e) {}

        // If country is empty then return null
        if (empty($geoIp['country_code'])) {
            return null;
        }

        return [
            'continentCode' => $geoIp['continent_code'] ?? '',
            'continentName' => $geoIp['continent_name'] ?? '',
            'countryCode' => $geoIp['country_code'] ?? '',
            'countryName' => $geoIp['country_name'] ?? '',
            'regionCode' => $geoIp['region_code'] ?? '',
            'regionName' => $geoIp['region_name'] ?? '',
            'city' => $geoIp['city'] ?? '',
            'postCode' => $geoIp['zip_code'] ?? '',
            'timeZone' => $geoIp['time_zone']['id'] ?? '',
        ];
    }
}
