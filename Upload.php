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
	public $max_uploads = 2;

    public $crop = true;


    public $options = [];
	public $label_btn = 'Add files...';

    public $view = 'form';

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

	public function run()
	{
        $this->options['multiple']  = ($this->multiple == true);

        $preview = '';
        if(!empty($this->model->file))
        {
            $path_info = pathinfo($this->model->file);
            if(in_array($path_info['extension'],['gif','png','jpg','jpeg']))
                $preview  = \yii\helpers\Html::img( $path_info['dirname'] . '/' . 'preview_'.$path_info['basename'] );
        }

		return $this->render($this->view ,[
            'url'         => $this->url,
            'multiple'    => $this->multiple,
            'auto_upload' => $this->auto_upload,
			'model'       => $this->model,
            'options'     => $this->options,
			'label_btn'   => $this->label_btn,
            'preview'     => $preview,
            'crop'        => $this->crop,
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