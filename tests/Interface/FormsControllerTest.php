<?php

use putyourlightson\campaign\Campaign;
use putyourlightson\campaign\controllers\FormsController;
use yii\web\ForbiddenHttpException;

/**
 * Tests the public form endpoints.
 */

beforeEach(function() {
    Campaign::$plugin->settings->unsubscribeAllFormAllowed = false;
    $_POST[Craft::$app->request->methodParam] = 'post';
});

afterEach(function() {
    Campaign::$plugin->settings->unsubscribeAllFormAllowed = false;
    Craft::$app->request->setBodyParams([]);
    Craft::$app->request->setQueryParams([]);
    unset($_POST[Craft::$app->request->methodParam]);
});

test('An unsubscribe all form request is forbidden when disabled', function() {
    (new FormsController('f', Campaign::$plugin))->actionUnsubscribeAll();
})->throws(ForbiddenHttpException::class);
