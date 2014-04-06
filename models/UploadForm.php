<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Александр
 * Date: 06.04.14
 * Time: 2:57
 * To change this template use File | Settings | File Templates.
 */

namespace sanchezzzhak\yii2storage\models;

use \yii\base\Model;


class UploadForm extends Model
{
	public $file;
	public $size;
	public $type;

	public function rules()
	{
		return array(
			array('file', 'file'),
		);
	}

}