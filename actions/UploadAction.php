<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Александр
 * Date: 06.04.14
 * Time: 2:43
 * To change this template use File | Settings | File Templates.
 */

namespace kak\storage\actions;
use kak\storage\models\UploadForm;

use Yii;
use yii\base\Action;
use yii\base\ErrorException;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\Url;

/**
 * example use controller this uploading
 	```php
 		public function actions()
 		{
 			return [
 				'upload' => [
 					'class' => 'kak\storage\actions\UploadAction',
  					'form_name' => 'kak\storage\models\UploadForm',
 					'path'  => Yii::$app->getBasePath() . '/../uploads/',
 					'public_path' => '/uploads/',
                    'successCallback' => [$this, 'successCallback'],
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


    public $image_height_max = 0;
    public $image_width_max  = 0;

    public $random_name = true;

	public function init()
	{
		parent::init();

		if (!is_dir($this->path))
		{
			try
			{
				mkdir($this->path, 0777, true);
				chmod($this->path, 0777);
			}
			catch (ErrorException $error )
			{
				throw new HttpException(500, "{$this->path} does not exists.");
			}
		}
		else if (!is_writable($this->path))
		{
			try
			{
				chmod($this->path, 0777);
			}
			catch (ErrorException $error )
			{
				throw new HttpException(500, "{$this->path} is not writable.");
			}
		}
		
		if( !isset($this->form_model)) {
			$this->form_model = Yii::createObject(['class'=>$this->form_name]);

		}
	}

	public function run()
	{
		$this->sendHeaders();
		return $this->handleUploading();
	}


	protected function sendHeaders()
	{
		header('Vary: Accept');
		if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
			header('Content-type: application/json');
		} else {
			header('Content-type: text/plain');
		}
	}

    /**
     * @return string
     */
	protected function handleUploading()
	{
        $result = [];
        /** @var \kak\storage\models\UploadForm $model */
		$model = $this->form_model;
		if(! $file = UploadedFile::getInstance($model,'file'))
		{
			return null;
		}

		$model->file = $file->name;
        $model->size = $file->size;

		if($model->validate())
		{
			$path = $this->path;

            $ext = pathinfo($model->file, PATHINFO_EXTENSION);
            if($this->random_name)
            {
                $model->file = Yii::$app->security->generateRandomString(). ".{$ext}";

            }

			$path_file = $path . $model->file;

			if(!empty($path_file) && $file->saveAs($path_file ))
			{
				chmod($path_file , 0666);
				$returnValue = $this->beforeReturn();
				if ($returnValue === true)
                {
					list($width, $height) = @getimagesize($path_file);

                    $result = [
						"name" => $model->file,
						"type" => $model->mime_type,
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
				}
			}
		}
        $result['errors'] = $model->getErrors();

        return $result;
	}



	protected function beforeReturn()
	{
		$path = $this->path;
		return true;
	}
}