<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Александр
 * Date: 06.04.14
 * Time: 2:43
 * To change this template use File | Settings | File Templates.
 */

namespace yii2\storage\actions;

use sanchezzzhak\yii2storage\models\UploadForm;
use Yii;
use yii\base\Action;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\Url;

/**
 * example
 *
 	```php
 		public function actions()
 		{
 			return [
 				'upload' => [
 					'class' => 'yii2\storage\actions\UploadAction',
  					'form_name' => 'yii2\storage\actions\models\UploadForm',
 					'path'  => Yii::$app->getBasePath() . '/../uploads/',
 					'public_path' => '/uploads/'
 *
 				],
 			];
 		}
 	```
*/

class UploadAction extends Action {

	public $form_name;
	public $form_model;
	public $path;
	public $public_path;

	public function init()
	{
		$base_path = Yii::$app->getBasePath();

		$this->path = (!isset($this->path)) ? realpath( $base_path ."/../uploads/") : realpath($this->path);
		if (!is_dir($this->path))
		{
			mkdir($this->path, 0777, true);
			chmod($this->path, 0777);
			throw new HttpException(500, "{$this->path} does not exists.");
		}
		else if (!is_writable($this->path))
		{
			chmod($this->path, 0777);
			throw new HttpException(500, "{$this->path} is not writable.");
		}

		if( !isset($this->form_model)) {
			$this->form_model = Yii::createObject(['class'=>$this->form_name]);
		}
	}

	public function run()
	{

		$this->sendHeaders();
		$this->handleUploading();
	}

	/**
	 *
	 */
	protected function sendHeaders()
	{
		header('Vary: Accept');
		if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
			header('Content-type: application/json');
		} else {
			header('Content-type: text/plain');
		}
	}


	protected function handleUploading()
	{
		$model = $this->form_model;
		$file = UploadedFile::getInstance($model,'file');
		$model->file = $file->name;

		if($model->validate())
		{
			$path = $this->path;
			$path_file = $path . $model->file;

			if (!is_dir($path))
			{
				mkdir($path, 0777, true);
				chmod($path, 0777);
			}
			if($file->saveAs($path_file ))
			{
				chmod($path_file , 0666);

				$returnValue = $this->beforeReturn();
				if ($returnValue === true) {
				// Image size
					list($width, $height) = @getimagesize($path_file);

					$json = [
						"name" => $model->file,
						"type" => $model->type,
						"size" => $model->size,
						"url"  => $this->public_path . $model->file,

						"delete_url" => Url::to([$this->id,
							"_method" => "delete",
							"file"    => $model->file
						]),
						"crop_url" => Url::to([$this->id,
							"_method" => "crop",
							"file"    => $model->file
						]),

						"path"    => $path_file,
						"width"   => isset($width) ? $width : 0,
						"height"  => isset($height) ? $height : 0,
					];

					return json_encode($json);
				}

			}
		}
	}


	protected function beforeReturn()
	{
		$path = $this->path;





		return true;
	}
}