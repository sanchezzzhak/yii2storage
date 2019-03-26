<?php namespace kak\storage\actions;

use kak\storage\Storage;
use Yii;
use yii\base\Action;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;
use yii\web\HttpException;
use yii\helpers\Json;

/**
 * Class BaseUploadAction
 * @package kak\storage\actions
 */
class BaseUploadAction extends Action
{
    public const IMAGE_RESIZE = 0;
    public const IMAGE_THUMB = 1;

    public static $EXTENSION_IMAGE = ['image/gif', 'image/png', 'image/jpg', 'image/jpeg'];



    public $extension_allowed = [];
    public $image_width_max = 1024;
    public $image_height_max = 768;

    public $resize_image = [
        'preview' => [600, 400, UploadAction::IMAGE_RESIZE],
        'thumbnail' => [120, 120, UploadAction::IMAGE_THUMB]
    ];

    public $default_image_options = [
        'quality' => 100
    ];

    public $result = [];


    public $storageId = 'tmp';

    public $storageClass = 'storage';

    /**
     * @return Storage
     * @throws \yii\base\InvalidConfigException
     */
    public function getStorage(): Storage
    {
        return \Yii::$app->get($this->storageClass);
    }




    /**
     * @param $path_file
     * @param $model
     */
    public function processImageWithResult()
    {

        $storage = $this->getStorage();

        $adapter = $storage->getAdapterByStorageId($this->storageId);
        $filePath = $this->result['url'];


//
//        if ($width > 0 || $height > 0) {

            //$this->resizeImageMaxOptimisation($path_file);

            foreach ($this->resize_image as $prefix => $param) {


                list($image_width, $image_height) = $param;
                $type = isset($param[2]) ? $param[2] : UploadAction::IMAGE_RESIZE;

                $options = ArrayHelper::getValue($param, 'options', []);
                $options = array_merge($this->default_image_options, $options);

                switch ($type) {
                    case UploadAction::IMAGE_RESIZE:

                        $result = $storage->resizeImagePreviewByStorageId(
                            $this->storageId,
                            $filePath,
                            $prefix,
                            $image_width,
                            $image_height,
                            $options
                        );
                        break;
                    case UploadAction::IMAGE_THUMB:
                        $result = $storage->resizeImageThumbnailByStorageId(
                            $this->storageId,
                            $filePath,
                            $prefix,
                            $image_width,
                            $image_height,
                            $options
                        );
                        break;
                }

                $this->result['images'][$prefix] = $result;
            }


        /*** Image end */
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return ArrayHelper::getValue($this->result, 'errors', []);
    }

    /**
     * @param $url
     * @return string|null
     */
    public function getExtension($url)
    {
        if (preg_match('#\.([\w\d]+)(?:\?|$)#is', $url, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }


    public function response(): ?string
    {
        return Json::encode($this->result);
    }


}