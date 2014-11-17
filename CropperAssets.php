<?php
/**
 * Created by PhpStorm.
 * User: PHPdev
 * Date: 13.11.2014
 * Time: 14:30
 */

namespace kak\storage;


class CropperAssets extends UploadAssets
{
    public $js = [
        'cropper/cropper.min.js',
    ];

    public $css = [
        'cropper/cropper.css',
    ];

    public $depends = [
        'yii\jui\JuiAsset',
    ];
} 