<?php
namespace kak\storage\bundles;


class StorageAsset extends \yii\web\AssetBundle
{
    public function init()
    {
        $this->publishOptions['forceCopy'] = true;
        parent::init();
    }

    public $sourcePath = '@kak/storage/assets';

    public $css = [
        'css/kak-storage-upload.css',
    ];
    public $js = [
        'js/jquery.fileupload-image-editor.js',
        'js/kak-storage-advanced-upload.js',
    ];

    public $depends = [
        'yii\jui\JuiAsset',
        'kak\storage\bundles\FileUploadAsset',
    ];
}