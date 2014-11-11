<?php
namespace kak\storage;

use Yii;
use yii\base\Widget;
use yii\base\Model;

/**
 * Class Upload Widget
 */
class Upload extends Widget
{
	/**
	 * @var Model the data model that this widget is associated with.
	 */
	public $model;
    public $options = [];

    public $url = '/upload';

	public $multiple = true;
	public $max_uploads = 2;
	public $auto_upload = false;

	public $label_btn = 'Add files...';


    public $id = false;
    public $view = 'form';

	public function init()
	{

		parent::init();
		UploadAssets::register($this->getView());

        if(!$this->id )
        {
            $class = explode('\\',get_class($this->model));
            $this->id = array_pop($class). '-form';
        }

	}

	public function run()
	{
		if($this->multiple == true )
		{
			$this->options['multiple'] = true;
		}

		return $this->render($this->view ,[
            'id'        => $this->id,
            'url'       => $this->url,
			'model'     => $this->model,
			'options'   => $this->options,
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