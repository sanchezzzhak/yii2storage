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
use yii\helpers\Json;
use yii\web\UploadedFile;
use yii\web\HttpException;
use kak\storage\Storage;

/**
 * Class UploadAction
 * @package kak\storage\actions
 */
class UploadAction extends BaseUploadAction
{

    public $form_name;
    public $form_model;
    public $header  = false;

    public $random_name = false;


    public function init()
    {
        parent::init();

        if( !isset($this->form_model))
        {
            $this->form_model = Yii::createObject(['class'=>$this->form_name ] );
        }
    }

    /**
     * @return string
     */
    public function run()
    {
        if($this->header )
            $this->sendHeaders();

        return $this->handleUploading();
    }

    /**
     * Send header
     */
    protected function sendHeaders()
    {
        header('Vary: Accept');
        if (Yii::$app->request->isAjax && isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
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
        $method  = Yii::$app->request->get('_method');

        $model = $this->form_model;
        if(! $file = UploadedFile::getInstance($model,'file'))
        {
            return;
        }

        // set model attr
        $model->file = $file->name;
        $model->size = $file->size;
        $model->mime_type = $file->type;

        if($model->validate()) {

            if (count($this->extension_allowed) && !in_array(pathinfo( strtolower($model->file), PATHINFO_EXTENSION), $this->extension_allowed ))
            {
                $model->addError('file','extension file not allowed');
            }
        }

        if(!count($model->errors))
        {
            $ext = strtolower(pathinfo($model->file, PATHINFO_EXTENSION));
            $storage = new Storage($this->storage);
            $adapter = $storage->getAdapter();

            $path_file = $adapter->uniqueFilePath($ext);
            $model->file = $adapter->getUrl($path_file);

            if(!count($model->getErrors()) && $file->error == 0 && $file->saveAs($path_file))
            {
                chmod($path_file , 0666);
                $returnValue = $this->beforeReturn();
                if ($returnValue === true)
                {
                    $this->_result = [
                        "name"         => $model->file,
                        "name_display" => $file->name,
                        "type"         => $model->mime_type,
                        "size"         => $model->size,
                        "url"          => $model->file,
                        "images"       => [],
                    ];

                    $this->_image($model->file);
                }
            }
        }
        else
        {
            $this->_result['errors'] =  $model->getErrors();
        }

        return  Json::encode($this->_result);
    }

    protected function beforeReturn()
    {
        return true;
    }
}