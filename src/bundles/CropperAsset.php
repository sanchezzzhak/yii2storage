<?php

namespace kak\storage\bundles;

class CropperAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/cropper';
    public $js = [
        '1.5.1/dist/cropper.js',
        'jquery-cropper.min.js',
    ];

    // добавить потом jquery-cropper.min.js

    public $css = [
        '1.5.1/dist/cropper.css',
    ];
    public $depends = [
        \yii\jui\JuiAsset::class,
      //  CropperJQueryAsset::class,
    ];
} 