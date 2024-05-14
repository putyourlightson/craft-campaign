<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\helpers\Json;
use GuzzleHttp\Exception\ConnectException;
use putyourlightson\campaign\Campaign;
use yii\web\ForbiddenHttpException;

/**
 * @since 2.16.0
 */
class TurnstileHelper
{
    /**
     * @const string
     */
    public const TURNSTILE_ACTION = 'homepage';

    /**
     * Validates the response.
     */
    public static function validate(string $response, string $ip): void
    {
        $settings = Campaign::$plugin->settings;

        $result = '';

        $client = Craft::createGuzzleClient([
            'timeout' => 5,
            'connect_timeout' => 5,
        ]);

        try {
            $response = $client->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'form_params' => [
                    'secret' => $settings->getTurnstileSecretKey(),
                    'response' => $response,
                    'remoteip' => $ip,
                ],
            ]);

            if ($response->getStatusCode() === 200) {
                $result = Json::decodeIfJson($response->getBody());
            }
        } catch (ConnectException) {
        }

        $success = $result['success'] ?? false;

        if (!$success) {
            throw new ForbiddenHttpException($settings->getTurnstileErrorMessage());
        }
    }
}
