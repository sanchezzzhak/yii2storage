<?php

namespace kak\storage\models;
use \yii\base\Model;

class UploadForm extends Model
{
    public $file;

    public $mime_type;
    public $size;
    public $name;
    public $filename;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['file', 'file'],
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


}