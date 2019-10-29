<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit;

use Craft;
use putyourlightson\campaign\Campaign;
use yii\base\InvalidRouteException;

/**
 * @author    PutYourLightsOn
 * @package   Campaign
 * @since     1.10.0
 */

class BaseControllerTest extends BaseUnitTest
{
    // Properties
    // =========================================================================

    /**
     * @var string
     */
    protected $email = 'email@anonymous.com';

    // Public methods
    // =========================================================================

    protected function _before()
    {
        parent::_before();

        // Set controller namespace to web
        Campaign::$plugin->controllerNamespace = str_replace('\\console', '', Campaign::$plugin->controllerNamespace);

        // Force post request
        $_POST[Craft::$app->request->methodParam] = 'post';

        // Disable CSRF validation
        Craft::$app->request->enableCsrfValidation = false;

        // Disable reCAPTCHA
        Campaign::$plugin->getSettings()->reCaptcha = false;
    }

    /**
     * @param string $action
     * @param array $params
     *
     * @return mixed
     * @throws InvalidRouteException
     */
    protected function runActionWithParams(string $action, array $params)
    {
        Craft::$app->request->setBodyParams($params);

        return Campaign::$plugin->runAction($action);
    }
}
