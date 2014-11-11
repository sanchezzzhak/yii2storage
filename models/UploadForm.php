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

	public function rules()
	{
		return array(
			array('file', 'file'),
		);
	}

	/**
	 * Declares attribute labels.
	 */
	public function attributeLabels()
	{
		return array(
			'file'=>'Upload files',
		);
	}

}