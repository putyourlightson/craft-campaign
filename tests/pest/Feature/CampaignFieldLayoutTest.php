<?php

use craft\models\FieldLayout;
use craft\services\Fields;
use craft\web\Request;
use putyourlightson\campaign\elements\CampaignElement;
use putyourlightson\campaign\fieldlayoutelements\reports\CampaignReportFieldLayoutTab;

test('Sent campaign instances add at most one Report tab', function(bool $isCpRequest) {
    $request = Craft::$app->getRequest();
    $fields = Craft::$app->getFields();
    $layout = new FieldLayout([
        'id' => 123,
        'type' => CampaignElement::class,
        'tabs' => [
            ['name' => 'Content'],
        ],
    ]);
    $mockFields = Mockery::mock(Fields::class)->makePartial();
    $mockFields->shouldReceive('getLayoutById')->with(123)->andReturn($layout);

    try {
        Craft::$app->set('request', new Request(['isCpRequest' => $isCpRequest]));
        Craft::$app->set('fields', $mockFields);

        // The provisional draft and its two canonical instances share the same cached layout.
        for ($i = 0; $i < 3; $i++) {
            $campaign = new CampaignElement(['fieldLayoutId' => 123, 'recipients' => 1]);
            $campaignLayout = $campaign->getFieldLayout();
            $reportTabs = array_filter($campaignLayout->getTabs(), fn($tab) => $tab instanceof CampaignReportFieldLayoutTab);

            expect($reportTabs)->toHaveCount($isCpRequest ? 1 : 0)
                ->and($campaign->getFieldLayout())->toBe($campaignLayout);
        }

        expect($layout->getTabs())->toHaveCount($isCpRequest ? 2 : 1);
    } finally {
        Craft::$app->set('request', $request);
        Craft::$app->set('fields', $fields);
    }
})->with([true, false]);
