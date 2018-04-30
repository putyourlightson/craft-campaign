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
use GuzzleHttp\Exception\GuzzleException;

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
     * @return array|null
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

        // If country is empty then return null
        if (empty($geoIp->country_code)) {
            return null;
        }

        return [
            'city' => $geoIp->city ?? '',
            'postCode' => $geoIp->zip_code ?? '',
            'regionCode' => $geoIp->region_code ?? '',
            'regionName' => $geoIp->region_name ?? '',
            'countryCode' => $geoIp->country_code ?? '',
            'countryName' => $geoIp->country_name ?? '',
            'timeZone' => $geoIp->time_zone ?? '',
        ];
    }
}