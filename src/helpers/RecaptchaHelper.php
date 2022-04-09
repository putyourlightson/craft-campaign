<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\helpers;

use Craft;
use craft\helpers\App;
use craft\helpers\Json;
use GuzzleHttp\Exception\ConnectException;
use putyourlightson\campaign\Campaign;
use yii\web\ForbiddenHttpException;

/**
 * @since 1.8.0
 */
class RecaptchaHelper
{
    /**
     * @const string
     */
    public const RECAPTCHA_ACTION = 'homepage';

    /**
     * Validates reCAPTCHA.
     */
    public static function validateRecaptcha(string $recaptchaResponse, string $ip): void
    {
        $settings = Campaign::$plugin->getSettings();

        $result = '';

        $client = Craft::createGuzzleClient([
            'timeout' => 5,
            'connect_timeout' => 5,
        ]);

        try {
            $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => App::parseEnv($settings->reCaptchaSecretKey),
                    'response' => $recaptchaResponse,
                    'remoteip' => $ip,
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $result = Json::decodeIfJson($response->getBody());
            }
        }
        catch (ConnectException) {
        }

        if (empty($result['success'])) {
            throw new ForbiddenHttpException(App::parseEnv($settings->reCaptchaErrorMessage));
        }

        if (!empty($result['action']) && $result['action'] != self::RECAPTCHA_ACTION) {
            throw new ForbiddenHttpException(App::parseEnv($settings->reCaptchaErrorMessage));
        }
    }
}
