<?php
namespace sanchezzzhak\yii2storage;

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
	/**
	 * @var string the input name. This must be set if [[model]] and [[attribute]] are not set.
	 */
	public $name;
	/**
	 * @var string the input value.
	 */
	public $value;

	public $options = [];

	/**
	 * Initializes the widget.
	 * If you override this method, make sure you call the parent implementation first.
	 */
	public function init()
	{
		parent::init();
		if (!$this->hasModel() && $this->name === null)
		{
			throw new InvalidConfigException("Either 'name', or 'model' and 'attribute' properties must be specified.");
		}

		UploadAsset::register($this->getView());
	}

	public function run()
	{

		echo $this->render($this->uploadView);
		echo $this->render($this->downloadView);
	}

	/**
	 * @return boolean whether this widget is associated with a data model.
	 */
	protected function hasModel()
	{
		return $this->model instanceof Model && $this->attribute !== null;
	}

}