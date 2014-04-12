<?php
namespace yii2\storage;

use Yii;
use yii\base\Widget;
use yii\base\Model;
use yii\base\InvalidConfigException;

/**
 * Class Upload Widget
 * @package sanchezzzhak\yii2storage
 *
 *
 */
class Upload extends Widget
{
	/**
	 * @var Model the data model that this widget is associated with.
	 */
	public $model;
	/**
	 * @var string the model attribute that this widget is associated with.
	 */
	public $attribute;

	public $form = 'form';
	public $upload = 'upload';
	public $download = 'download';

	public $multiple = false;
	public $max_upload = 2;
	public $auto_upload = false;

	/**
	 * Initializes the widget.
	 * If you override this method, make sure you call the parent implementation first.
	 */
	public function init()
	{

		parent::init();
		if (!$this->hasModel() && $this->attribute === null)
		{
			throw new InvalidConfigException("Either 'attribute', or 'model' and 'attribute' properties must be specified.");
		}
		UploadAssets::register($this->getView());
	}

	public function run()
	{
		echo $this->render($this->form,[
			'model' => $this->model,
			'attribute' => $this->attribute

		]);
		//echo $this->render($this->upload);
		//echo $this->render($this->download);
	}

	/**
	 * @return boolean whether this widget is associated with a data model.
	 */
	protected function hasModel()
	{
		return $this->model instanceof Model && $this->attribute !== null;
	}

}