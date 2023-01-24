<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\campaign\base;

use Craft;
use craft\web\Controller;
use yii\web\ForbiddenHttpException;

/**
 * @since 2.5.2
*/
abstract class BaseSettingsController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!Craft::$app->getConfig()->getGeneral()->allowAdminChanges) {
            throw new ForbiddenHttpException('Administrative changes are disallowed in this environment.');
        }

        // Require permission
        $this->requirePermission('campaign:settings');

        return parent::beforeAction($action);
    }
}
