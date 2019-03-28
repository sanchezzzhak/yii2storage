<?php namespace kak\storage\models;

use kak\storage\Storage;
use \yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\Json;

class UploadForm extends Model
{
    public $file;
    public $mime_type;
    public $size;
    public $filename;
    public $name;

    public $meta = '';
    public $meta_name = 'meta';


    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['file', 'file'],
            ['meta','string']
        ];
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return [
            'file' => 'Upload file',
        ];
    }

    /**
     * @param $allowed
     * @throws \yii\base\InvalidConfigException
     */
    public function validateByExtensionAllowed($allowed)
    {
        if($allowed === []){
            return;
        }

        $mimeType = $file->type;
        if ($mimeType === 'application/octet-stream'){
            $mimeType = FileHelper::getMimeType($file->tempName);
        }

        if (!in_array($mimeType, $allowed)) {
            $model->addError('file', 'extension file not allowed');
        }
    }

}