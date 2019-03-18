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
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\web\UploadedFile;
use yii\web\HttpException;
use \kak\storage\Storage;

/**
 * Class UploadAction
 * @package kak\storage\actions
 */
class UploadAction extends BaseUploadAction
{

    public $form_name;
    public $form_model;
    public $header      = false;

    public $random_name = false;


    public function init()
    {
        parent::init();

        if( !isset($this->form_model)) {
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
            return;
        }
        header('Content-type: text/plain');
    }


    /**
     * @return string|null
     */
    protected function handleUploading()
    {
        /** @var $model \kak\storage\models\UploadForm */
        $method  = Yii::$app->request->get('_method');

        $model = $this->form_model;
        if(!$file = UploadedFile::getInstanceByName('file')) {
            return null;
        }

        // set model attr
        $model->file = $file->name;
        $model->size = $file->size;
        $model->mime_type = $file->type;

        $extList = [];

        if($model->validate()) {

            $mimeType = $file->type;
            if($mimeType == 'application/octet-stream' )
                $mimeType = FileHelper::getMimeType($file->tempName);

            if($mimeType != 'application/octet-stream')
                $extList  = FileHelper::getExtensionsByMimeType($mimeType);

            if($mimeType == 'application/octet-stream' && !in_array('application/octet-stream',$this->extension_allowed) )
                $mimeType = $this->getExtension($model->file);

            if (count($this->extension_allowed) && !in_array($mimeType , $this->extension_allowed )) {
                $model->addError('file','extension file not allowed');
            }
        }

        if(!count($model->errors)) {
            $extMimeType = count($extList) ? end($extList): null;

            $ext = $this->getExtension($model->file);
            if($ext === null) {
                $ext = $extMimeType;
            }

            $storage = new Storage($this->storage);
            $adapter = $storage->getAdapter();

            $pathFile = $adapter->uniqueFilePath($ext);
            $model->file = $adapter->getUrl($pathFile);

            if(!count($model->getErrors()) && $file->error == 0 && $file->saveAs($pathFile)) {
                chmod($pathFile , 0666);
                $returnValue = $this->beforeReturn();
                if ($returnValue === true) {
                    $this->_result = [
                        "name_display" => $file->name,
                        "type"         => $model->mime_type,
                        "size"         => $model->size,
                        "url"          => $model->file,
                        "storage"      => $storage->getId(),
                        "images"       => [],
                    ];

                    $this->_image($model->file);
                }
            }

        } else {
            $this->_result['errors'] =  $model->getErrors();
        }

        return  $this->response();
    }

    private function response()
    {
        return  Json::encode($this->_result);
    }

    protected function beforeReturn()
    {
        return true;
    }
}