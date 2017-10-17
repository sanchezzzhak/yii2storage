<?php
namespace kak\storage\bundles;


class StorageAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@kak/storage/assets';

    public $css = [
        'css/kak-storage-upload.css',
    ];
    public $js = [
        'js/kak-storage-upload.js',
    ];

    public $depends = [
        'yii\jui\JuiAsset',
        'kak\storage\bundles\FileUploadAsset',
    ];
}