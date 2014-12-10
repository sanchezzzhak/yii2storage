<?php

namespace kak\storage\models;
use kak\storage\actions\UploadAction;
use \yii\base\Model;

class UploadForm extends Model
{
    public $file;

    public $mime_type;
    public $size;
    public $name;
    public $filename;

    public $meta = '';  // JSON save result storage

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




}