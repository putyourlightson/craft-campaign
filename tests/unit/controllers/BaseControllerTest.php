<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaigntests\unit\controllers;

use Craft;
use putyourlightson\campaign\Campaign;
use putyourlightson\campaigntests\unit\BaseUnitTest;

/**
 * @since 1.10.0
 */
class BaseControllerTest extends BaseUnitTest
{
    /**
     * @var string
     */
    protected string $email = 'email@anonymous.com';

    protected function _before(): void
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

    protected function runActionWithParams(string $action, array $params = []): mixed
    {
        Craft::$app->request->setBodyParams($params);

        return Campaign::$plugin->runAction($action);
    }
}
