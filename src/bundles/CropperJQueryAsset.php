<?php

namespace kak\storage\bundles;

class CropperJQueryAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/jquery-cropper';
    public $js = [
        'dist/jquery-cropper.min.js',
    ];

    // todo добавить потом jquery-cropper.min.js

    public $depends = [
        'yii\jui\JuiAsset',
    ];
} 