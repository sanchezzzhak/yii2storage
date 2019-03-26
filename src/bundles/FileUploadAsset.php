<?php
namespace kak\storage\bundles;

class FileUploadAsset extends \yii\web\AssetBundle
{
	public $sourcePath = '@bower/blueimp-file-upload';

	public $js = [
		'https://blueimp.github.io/JavaScript-Load-Image/js/load-image.all.min.js',
		'https://blueimp.github.io/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js',
		'js/jquery.iframe-transport.js',
		'js/jquery.fileupload.js',
		'js/jquery.fileupload-process.js',
		'js/jquery.fileupload-validate.js',
        'js/jquery.fileupload-image.js'
	];

	public $depends = [
		'yii\jui\JuiAsset',
		'kak\storage\bundles\TmplAsset',
		'kak\storage\bundles\CropperAsset',
	];
}