<?php
namespace sanchezzzhak\storage;

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
	 * @var string the model attribute name that this widget is associated with.
	 */
	public $name;

	public $form = 'form';
	public $upload = 'upload';
	public $download = 'download';

	public $options = [];

	public $multiple = true;
	public $max_upload = 2;
	public $auto_upload = false;

	public $label_btn = 'Add files...';

	/**
	 * Initializes the widget.
	 * If you override this method, make sure you call the parent implementation first.
	 */
	public function init()
	{

		parent::init();
		if (!$this->hasModel() && $this->name === null)
		{
			throw new InvalidConfigException("Either 'name
			', or 'model' and 'attribute' properties must be specified.");
		}
		UploadAssets::register($this->getView());
	}

	public function run()
	{
		if($this->multiple == true )
		{
			$this->options['multiple'] = true;
		}

		echo $this->render($this->form,[
			'model'   => $this->model,
			'name'    => $this->name,
			'options' => $this->options,
			'label_btn' => $this->label_btn,

		]);

	}

	/**
	 * @return boolean whether this widget is associated with a data model.
	 */
	protected function hasModel()
	{
		return $this->model instanceof Model;
	}

}