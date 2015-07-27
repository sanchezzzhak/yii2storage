<?php
namespace kak\storage;


class StorageAsset extends \yii\web\AssetBundle
{
    public $sourcePath = '@kak/storage/assets';

    public $css = [
        'storage-upload.css',
    ];
    public $js = [
        'storage-upload.js',
    ];

    public $depends = [
        'yii\jui\JuiAsset',
    ];
}