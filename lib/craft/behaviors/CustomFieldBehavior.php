<?php
/**
 * @link http://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license http://craftcms.com/license
 */

namespace craft\behaviors;

use craft\elements\Asset;
use craft\elements\db\AssetQuery;
use craft\elements\ElementCollection;
use yii\base\Behavior;

/**
 * Based on https://github.com/craftcms/cms/blob/develop/lib/craft/behaviors/CustomFieldBehavior.php
 *
 * @internal
 */
class CustomFieldBehavior extends Behavior
{
    /**
     * Custom fields required for PHPStan.
     */
    public AssetQuery|ElementCollection|Asset $images;

    /**
     * @var bool Whether the behavior should provide methods based on the field handles.
     */
    public bool $hasMethods = false;

    /**
     * @var bool Whether properties on the class should be settable directly.
     */
    public bool $canSetProperties = true;

    /**
     * @var string[] List of supported field handles.
     */
    public static $fieldHandles = [];
}
