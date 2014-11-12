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
use yii\helpers\Json;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\Url;

use kak\storage\Storage;

/**
 * example use controller this uploading
```php
public function actions()
{
return [
'upload' => [
'class' => 'kak\storage\actions\UploadAction',
// validation model and Set attributes model
'form_name' => 'kak\storage\models\UploadForm',

// custom save file to path
//'path'  => Yii::$app->getBasePath() . '/web/uploads',
//'public_path' => '/uploads/',
//'random_name' => true,

// save file use Storage id=tmp
'storage' => 'tmp',

'successCallback' => [$this, 'successCallback'],
],
];
}
```
 */

class UploadAction extends Action {

    public $form_name;
    public $form_model;

    /** custom save path */
    public $path;
    public $public_path;

    public $storage = null;

    public $image_preview_height = 320;
    public $image_preview_width = 420;

    public $image_width_max = 1024;
    public $image_height_max = 768;

    public $random_name = false;

    public function init()
    {
        parent::init();

        // use storage
        if($this->storage)
        {
            try
            {
                $storage = new Storage($this->storage);
                $this->path = $storage->getBasePath();
                $this->public_path = $storage->getBaseUrl();
            }
            catch (ErrorException $error )
            {
                throw new HttpException(500, $error->getMessage() );
            }
        }
        // custom
        else if (!is_dir($this->path))
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

        if( !isset($this->form_model))
        {
            $this->form_model = Yii::createObject(['class'=>$this->form_name ] );
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

        // set model attr
        $model->file = $file->name;
        $model->size = $file->size;
        $model->mime_type = $file->type;

        if($model->validate())
        {
            $ext = pathinfo($model->file, PATHINFO_EXTENSION);
            if($this->storage)
            {
                $storage = new Storage($this->storage);
                $model->file =  $storage->rel_path($storage->unique_filepath($ext));
            }
            else if($this->random_name == true)
            {
                $model->file =  Yii::$app->security->generateRandomString(). ".{$ext}";
            }

            $path_file =  rtrim($this->path,DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $model->file;

            if(!count($model->getErrors()) && $file->error == 0 && $file->saveAs($path_file))
            {

                chmod($path_file , 0666);
                $returnValue = $this->beforeReturn();
                if ($returnValue === true)
                {

                    list($width, $height) = @getimagesize($path_file);
                    if($width > 0 || $height > 0)
                    {
                        $this->resizeImageMaxOptimisation($path_file);
                        list($width, $height) = @getimagesize($path_file);
                    }

                    $result = [
                        "name" => $model->file,
                        "type" => $model->mime_type,
                        "size" => $model->size,
                        "url"  => $this->public_path . $model->file,
                        "image_preview" => "",

                        "delete_url"  => Url::to([$this->id,
                            "_method" => "delete",
                            "file"    => $model->file
                        ]),

                        "crop_url"    => Url::to([$this->id,
                            "_method" => "crop",
                            "file"    => $model->file
                        ]),

                        "width"   => isset($width) ? $width : 0,
                        "height"  => isset($height) ? $height : 0,
                    ];
                }
            }
        }
        $result['errors'] = $model->getErrors();

        return Json::encode($result);
    }

    protected function beforeReturn()
    {
        $path = $this->path;
        return true;
    }

    /**
     * @param $path
     */
    protected function resizeImageMaxOptimisation($path)
    {
        $imagine = new \Imagine\Gd\Imagine;
        $img = $imagine->open($path);
        $size = $img->getSize();

        if( $size->getWidth() > $size->getHeight() && $size->getWidth() >  $this->image_width_max )
        {
            $img->resize(new \Imagine\Image\Box($this->image_width_max , $this->image_width_max * $size->getHeight() / $size->getWidth()) )
                ->save($path, ['quality' => 100]);
        }
        else if( $size->getWidth() < $size->getHeight() && $size->getHeight() >  $this->image_height_max )
        {
            $img->resize(new \Imagine\Image\Box(  $this->image_height_max *  $size->getWidth() / $size->getHeight()   , $this->image_height_max ))
                ->save($path, ['quality' => 100]);
        }
    }


}