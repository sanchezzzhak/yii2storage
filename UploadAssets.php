<?php
namespace yii2\storage;
use yii\web\AssetBundle;

class UploadAssets extends AssetBundle
{
	public $sourcePath = '@yii2/storage/assets';

	public $js = [
		'fileupload/js/vendor/jquery.ui.widget.js',
		'fileupload/js/jquery.iframe-transport.js',
		'fileupload/js/jquery.fileupload.js',
		'fileupload/js/jquery.fileupload-process.js',
		'fileupload/js/jquery.fileupload-image.js',
		'fileupload/js/jquery.fileupload-audio.js',
		'fileupload/js/jquery.fileupload-video.js',
		'fileupload/js/jquery.fileupload-validate.js',
		'fileupload/js/jquery.fileupload-ui.js',
	];
	public $css = [
		'yii2storage.css',
	];

	public $depends = [
		'yii\jui\CoreAsset',
	];
}