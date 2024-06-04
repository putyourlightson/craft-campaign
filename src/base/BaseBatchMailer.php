<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use Craft;
use craft\base\Model;
use GuzzleHttp\Client;

abstract class BaseBatchMailer extends Model implements BatchMailerInterface
{
    /**
     * Returns a Guzzle client using the provided config.
     */
    public function getClient(array $config = []): Client
    {
        $config = array_merge([
            'timeout' => 60,
            'connect_timeout' => 60,
        ], $config);

        return Craft::createGuzzleClient($config);
    }
}
