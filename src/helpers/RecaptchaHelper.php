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
 * RecaptchaHelper
 *
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.8.0
 */
class RecaptchaHelper
{
    // Constants
    // =========================================================================

    /**
     * @const string
     */
    const RECAPTCHA_ACTION = 'homepage';

    // Static Methods
    // =========================================================================

    /**
     * Validate reCAPTCHA
     *
     * @param string $recaptchaResponse
     * @param string $ip
     *
     * @throws ForbiddenHttpException
     */
    public static function validateRecaptcha(string $recaptchaResponse, string $ip)
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
                    'secret' => Craft::parseEnv($settings->reCaptchaSecretKey),
                    'response' => $recaptchaResponse,
                    'remoteip' => $ip,
                ]
            ]);

            if ($response->getStatusCode() == 200) {
                $result = Json::decodeIfJson($response->getBody());
            }
        }
        catch (ConnectException $e) {}

        if (empty($result['success'])) {
            throw new ForbiddenHttpException(Craft::parseEnv($settings->reCaptchaErrorMessage));
        }

        if (!empty($result['action']) && $result['action'] != self::RECAPTCHA_ACTION) {
            throw new ForbiddenHttpException(Craft::parseEnv($settings->reCaptchaErrorMessage));
        }
    }
}
