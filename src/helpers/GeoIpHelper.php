<?php
/**
 * @link      https://craftcampaign.com
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\helpers\Json;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

/**
 * GeoIpHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.0.0   
 */
class GeoIpHelper
{
    // Public Methods
    // =========================================================================

    /**
     * Gets geolocation based on IP address
     *
     * @param int|null
     *
     * @return string|null
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function getGeoIp(int $timeout = 3)
    {
        $geoIp = null;

        $client = new Client([
            'timeout' => $timeout,
            'connect_timeout' => $timeout,
        ]);

        $ipAddress = Craft::$app->request->getUserIP();

        try {
            $response = $client->request('get', 'http://freegeoip.net/json/'.$ipAddress);

            if ($response->getStatusCode() == 200) {
                $geoIp = $response->getBody();
            }
        }
        catch (ConnectException $e) {}
        catch (GuzzleException $e) {}

        // If country is empty then return empty string so we are not pointlessly saving IP addresses
        if (empty($geoIp->country_code)) {
            return null;
        }

        return $geoIp;
    }

    /**
     * Returns the country name
     *
     * @param mixed
     *
     * @return string
     */
    public static function getCountryName($geoIp): string
    {
        $location = static::getLocation($geoIp);

        return $location['country'] ?? '';
    }

    /**
     * Returns the country code
     *
     * @param mixed
     *
     * @return string
     */
    public static function getCountryCode($geoIp): string
    {
        if ($geoIp === null) {
            return '';
        }

        $geoIp = Json::decodeIfJson($geoIp);

        $countryCode = $geoIp['country_code'] ?? '';

        return strtolower($countryCode);
    }

    /**
     * Returns the location
     *
     * @param mixed
     *
     * @return array
     */
    public static function getLocation($geoIp): array
    {
        if ($geoIp === null) {
            return [];
        }

        $geoIp = Json::decodeIfJson($geoIp);

        return [
            'city' => $geoIp['city'] ?? '',
            'postCode' => $geoIp['zip_code'] ?? '',
            'region' => $geoIp['region_name'] ?? '',
            'country' => $geoIp['country_name'] ?? '',
            'timeZone' => $geoIp['time_zone'] ?? '',
        ];
    }
}