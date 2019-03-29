<?php namespace kak\storage;

use yii\base\Widget;
use yii\base\Model;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\StringHelper;
use yii\helpers\Url;


/**
 * Class UploadAdvanced
 * @usage
 * ```php
 * <?= \kak\storage\UploadAdvanced::widget([
      'model' => $upload_form,
      'url' => '/upload',
 * ]); ?>
 * ```
 */
class UploadAdvanced extends Widget
{
	/**
	 * @var Model the data model that this widget is associated with.
	 */
	public $model;

    public $url = '/upload';
    public $options = [];

    /**
     * @var bool use auto translation
     */
    public $i18n = true;
    /**
     * @var array = [
     * 'maxChunkSize' => 0,
     * 'multiple' => true,
     * 'autoUpload' => false,
     * 'dropZone' => true,
     * 'dropZoneEffect' => true,
     * 'singleFileUploads' => false,
     * 'labelUpload' => 'Select files ...',
     * 'labelDropZone' => 'Drop files here',
     * 'labelStart' => 'Start',
     * 'labelCancel' => 'Cancel',
     * 'labelDelete' => 'Delete',
     * 'labelProcessingUpload' => 'Processing',
     * ]
     */
    public $pluginDeviceUploadOptions = [
        'maxChunkSize' => 1024 * 1024
    ];
    /**
     * @var array = [
     * 'labelInputTitle' => 'Enter URL to import a file',
     * 'labelImport' => 'Import',
     * ]
     */
    public $pluginLinkUploadOptions = [];
    public $pluginCropImageOptions = [];
    public $pluginAdaptersOptions = [];
    /**
     * @var array = [
     * 'labelDelete' => 'Delete',
     * 'labelEdit' => 'Edit',
     * 'labelCrop' => 'Crop',
     * ]
     */
    public $pluginViewOptions = [];

    public $view = 'advanced';

    public $instagramEnable = false;
    public $facebookEnable  = false;
    public $vkontakteEnable = false;
    public $dropboxEnable = false;


    /**
     * Init widget
     */
	public function init()
	{
		parent::init();
        $this->registerAssets();
        $this->url = Url::to($this->url);

        if(!$this->id ) {
            $class = StringHelper::basename(get_class($this->model));
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

        $pluginOptions = [];
        $pluginOptions['url'] = $this->url;


        $pluginOptions['deviceUpload'] = $this->pluginDeviceUploadOptions;
        $pluginOptions['linkUpload'] = $this->pluginLinkUploadOptions;
        $pluginOptions['cropImage'] = $this->pluginCropImageOptions;
        $pluginOptions['view'] = $this->pluginViewOptions;
        $pluginOptions['adapters'] = $this->pluginAdaptersOptions;

        $this->prepatePluginOptionsWithAuth($pluginOptions);
        $this->prepatePluginOptionsWithI18n($pluginOptions);

        $pluginOptionsString = Json::htmlEncode($pluginOptions);
        $js = "jQuery('#{$id}').kakStorageAdvancedUpload({$pluginOptionsString})";
        $view->registerJs($js,$view::POS_READY, $id . ':kak-storage-advanced-upload ');

		return $this->render($this->view ,[
			'model'       => $this->model,
            'options'     => $this->options
		]);
	}

    /**
     * @return yii\authclient\Collection|null
     */
	private function getAuthClientCollection()
    {
        $authClientCollection = \Yii::$app->get('authClientCollection', false);
        return $authClientCollection;
    }

    private function prepatePluginOptionsWithI18n( array &$options): void
    {
        if(!$this->i18n){
            return;
        }

        $options['deviceUpload'] = array_merge([
            'labelUpload' => \Yii::t('storage', 'Select files ...'),
            'labelDropZone' => \Yii::t('storage', 'Drop files here'),
            'labelStart' => \Yii::t('storage', 'Start'),
            'labelCancel' => \Yii::t('storage', 'Cancel'),
            'labelProcessingUpload' => \Yii::t('storage', 'Delete'),
            'labelProcessingUpload' => \Yii::t('storage', 'Processing'),
        ], $options['deviceUpload']);

        $options['linkUpload'] = array_merge([
            'labelInputTitle' => \Yii::t('storage','Enter URL to import a file'),
            'labelImport' => \Yii::t('storage','Import'),
        ], $options['linkUpload']);


        $options['view'] = array_merge([
            'labelDelete' =>  \Yii::t('storage', 'Delete'),
            'labelEdit' =>  \Yii::t('storage', 'Edit'),
            'labelCrop' => \Yii::t('storage', 'Crop'),
        ], $options['view']);

    }

    /**
     * @param array $options
     */
	private function prepatePluginOptionsWithAuth(array &$options): void
    {
        $authClientCollection = $this->getAuthClientCollection();
        if(!$authClientCollection) {
            return;
        }

        $baseRoute = '/storage/auth/token';

        // todo new add version social upload files/photo
        if($this->instagramEnable && $authClientCollection->hasClient('instagram')){
            /** @var $client \kak\authclient\Instagram  */
            $client = $authClientCollection->getClient('instagram');
            $options['instagram'] = [
                'authUrl' => $client->buildAuthUrl([
                    'redirect_uri' => Url::to([$baseRoute, 'id' => $client->getName()], true)
                ])
            ];
        }

//        if($this->dropboxEnable && $authClientCollection->hasClient('dropbox')){
//            /** @var $client \kak\authclient\DropBox  */
//            $client = $authClientCollection->getClient('dropbox');
//            $options['vkontakte'] = [
//                'authUrl' => $client->buildAuthUrl([
//                    'redirect_uri' => Url::to([$baseRoute, 'id' => $client->getName()], true)
//                ])
//            ];
//        }

        if($this->facebookEnable && $authClientCollection->hasClient('facebook')){
            /** @var $client \yii\authclient\clients\Facebook  */
            $client = $authClientCollection->getClient('facebook');
            $options['facebook'] = [
                'authUrl' => $client->buildAuthUrl([
                    'redirect_uri' => Url::to([$baseRoute, 'id' => $client->getName()], true)
                ])
            ];
        }

        if($this->vkontakteEnable && $authClientCollection->hasClient('vkontakte')){
            /** @var $client \yii\authclient\clients\VKontakte  */
            $client = $authClientCollection->getClient('vkontakte');
            $options['vkontakte'] = [
                'authUrl' => $client->buildAuthUrl([
                    'redirect_uri' => Url::to([$baseRoute, 'id' => $client->getName()], true)
                ])
            ];
        }
    }


	/**
	 * @return boolean whether this widget is associated with a data model.
	 */
	protected function hasModel()
	{
		return $this->model instanceof Model;
	}

}