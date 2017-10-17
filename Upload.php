<?php
namespace kak\storage;

use Yii;
use yii\base\Widget;
use yii\base\Model;
use yii\helpers\Url;

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

    public $autoUpload = true;
	public $multiple = true;
    public $progressbarAll = false;
    public $crop = true;
    public $singleUpload = false;

    public $options = [];
    public $view = 'form';


    public $labelBtn               = 'Add files...';
    public $labelSuccess           = 'uploaded success';
    public $labelProcessingUpload  = 'Processing upload...';
    public $labelUploadError       = 'uploading error...';
    public $labelCrop              = 'Crop';
    public $labelStart             = 'Start';
    public $labelCancel            = 'Cancel';
    public $labelDelete            = 'Delete';

    /**
     * Init widget
     */
	public function init()
	{
		parent::init();
        $this->registerAssets();
        $this->url = Url::to($this->url);
        $this->options['multiple']  = ($this->multiple == true);

        if(!$this->id ) {
            $class = explode('\\',get_class($this->model));
            $this->id = array_pop($class). '-form';
        }
        if(!isset($this->options['id'])) {
            $this->options['id'] = $this->id . '-upload-btn';
        }
	}

    /**
     * Register assets
     */
    protected function registerAssets()
    {
        $view = $this->getView();
        bundles\CropperAsset::register($view);
        bundles\StorageAsset::register($view);
    }
    /**
     * Run widget
     * @return string
     */
	public function run()
	{
        $id   = $this->id;
        $view = $this->getView();
        $js = "jQuery('#$id').kakStorageUpload({})";
        $view->registerJs($js,$view::POS_READY, $id . ':kak-storage-upload ');

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