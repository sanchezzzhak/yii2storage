<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Александр
 * Date: 06.04.14
 * Time: 2:57
 * To change this template use File | Settings | File Templates.
 */

namespace yii2\storage\models;

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