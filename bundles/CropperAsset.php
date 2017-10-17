<?php
namespace kak\storage\bundles;

class CropperAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@bower/cropper';
    public $js = [
        'dist/cropper.min.js',
    ];
    public $css = [
        'dist/cropper.css',
    ];
    public $depends = [
        'yii\jui\JuiAsset',
    ];
} 