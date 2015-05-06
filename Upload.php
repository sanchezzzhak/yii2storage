<?php
namespace kak\storage;

use Yii;
use yii\base\Widget;
use yii\base\Model;

/**
 * Class Upload Widget
 * @usage
 * ```php
    <?= \kak\storage\Upload::widget([
        'model' => $upload_form,
        'label_btn' => 'Select File',
        'auto_upload' => true,
        'multiple' => true,
        'url' => '/upload'
    ]); ?>
 * ```
 */
class Upload extends Widget
{
	/**
	 * @var Model the data model that this widget is associated with.
	 */
	public $model;
    /***
     * Config JQuery Upload File
     */
    public $url = '/upload';
    public $auto_upload = true;
	public $multiple = true;
    public $progressbarall = false;
    public $crop = true;
    public $single_upload = true;

    public $options = [];
    public $view = 'form';


    public $label_btn               = 'Add files...';
    public $label_success           = 'uploaded success';
    public $label_processing_upload = 'Processing upload...';
    public $label_upload_error      = 'uploading error...';
    public $label_crop              = 'Crop';
    public $label_start             = 'Start';
    public $label_cancel            = 'Cancel';
    public $label_delete            = 'Delete';

	public function init()
	{

		parent::init();

        CropperAssets::register($this->getView());
        UploadAssets::register($this->getView());

        if(!$this->id )
        {
            $class = explode('\\',get_class($this->model));
            $this->id = array_pop($class). '-form';
        }
	}

    /**
     * @return string
     */
	public function run()
	{
        $this->options['multiple']  = ($this->multiple == true);

		return $this->render($this->view ,[
			'model'       => $this->model,
            'options'     => $this->options
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