<?php
namespace kak\storage;

class FileUploadAsset extends \yii\web\AssetBundle
{
	public $sourcePath = '@vendor/bower/blueimp-file-upload';

	public $js = [
		'js/vendor/jquery.ui.widget.js',
		'js/jquery.iframe-transport.js',
		'js/jquery.fileupload.js',
		'js/jquery.fileupload-process.js',
		'js/jquery.fileupload-validate.js',
		'js/jquery.fileupload-ui.js',
	];

	public $depends = [
		'yii\jui\JuiAsset',
		'kak\storage\TmplAsset',
	];
}