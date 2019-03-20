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

    public $storage = 'tmp';
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

    public function init()
    {
        parent::init();

        // use storage
        if ($this->storage) {
            try {
                $storage = new Storage($this->storage);
            } catch (ErrorException $error) {
                throw new HttpException(500, $error->getMessage());
            }
        }

    }

    /**
     * @param $path_file
     * @param $model
     */
    public function image($model_file)
    {

        $storage = new Storage($this->storage);
        $adapter = $storage->getAdapter();
        $path_file = $adapter->getAbsolutePath($model_file);

        $resize_type = Yii::$app->request->get('resize_type');
        if (!empty($resize_type) && $resize_type = explode(',', $resize_type)) {
            $sizes = [];
            foreach ($resize_type as $type) {
                if (isset($this->resize_image[$type])) {
                    $sizes[$type] = $this->resize_image[$type];
                }
            }
            $this->resize_image = $sizes;
        }


        /*** Image if */
        list($width, $height) = @getimagesize($path_file);
        if ($width > 0 || $height > 0) {

            //$this->resizeImageMaxOptimisation($path_file);

            foreach ($this->resize_image as $prefix => $param) {

                list($image_width, $image_height) = $param;
                $type = isset($param[2]) ? $param[2] : UploadAction::IMAGE_RESIZE;

                $options = ArrayHelper::getValue($param, 'options', []);
                $options = array_merge($this->default_image_options, $options);

                $info_path = pathinfo($path_file);
                $image_save = $info_path['dirname'] . '/' . $prefix . '_' . $info_path['basename'];
                $image_path_save = $image_save;

                switch ($type) {
                    case UploadAction::IMAGE_RESIZE:
                        $this->resizeImagePreview($path_file, $image_path_save, $image_width, $image_height, $options);
                        break;
                    case UploadAction::IMAGE_THUMB:
                        $this->resizeImageThumbnail($path_file, $image_path_save, $image_width, $image_height, $options);
                        break;
                }
                $this->result['images'][$prefix]['url'] = $adapter->getUrl($image_save);
            }
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


    /**
     * @param $path
     * @param $path_thumbnail_file
     * @param int $resize_width
     * @param int $resize_height
     * @param array $options
     * @return \Imagine\Image\ImageInterface|object
     */
    public function resizeImageThumbnail($path, $path_thumbnail_file, $resize_width = 0, $resize_height = 0, $options = [])
    {
        $img = $this->imageOpen($path);

        $result = $img->thumbnail(
            new \Imagine\Image\Box(
                $resize_width,
                $resize_height
            ),
            \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND
        )->save($path_thumbnail_file, $options);

        return $result;
    }

    /**
     * @return \Imagine\Gd\Imagine|\Imagine\Gmagick\Imagine|Imagine
     */
    protected function getImageDriver()
    {
        if (class_exists('Imagick', false))
            return new \Imagine\Imagick\Imagine;

        if (class_exists('Gmagick', false))
            return new \Imagine\Gmagick\Imagine;

        return new \Imagine\Gd\Imagine;
    }


    /**
     * @return \Imagine\Gd\Imagine|\Imagine\Gmagick\Imagine|Imagine
     */
    private function getImageMetadataReader()
    {
        if (function_exists('exif_read_data')) {
            return new \Imagine\Image\Metadata\ExifMetadataReader;
        }

        return new \Imagine\Image\Metadata\DefaultMetadataReader;
    }


    /**
     * @param \Imagine\Image\ImageInterface $img
     * @throws \ImagickException
     */
    private function autoFixImageOrentation(\Imagine\Image\ImageInterface $img): void
    {
        $metadata = $img->metadata();
        $data = $metadata->toArray();

        $orientation = $data['exif.Orientation'] ?? 0;
        if ($orientation) {
            switch ($orientation) {
                case 8:
                    $img->rotate(-90);
                case 3:
                    $img->rotate(180);
                case 6:
                    $img->rotate(90);
            }
        }
    }


    /**
     * @param $path
     * @return \Imagine\Image\ImageInterface|object
     * @throws \ImagickException
     */
    private function imageOpen($path)
    {
        $imagine = $this->getImageDriver();

        $imagine->setMetadataReader($this->getImageMetadataReader());
        $img = $imagine->open($path);

        $this->autoFixImageOrentation($img);

        return $img;
    }


    /**
     * @param $path
     * @param $path_preview_file
     * @param int $resize_width
     * @param int $resize_height
     * @param array $options
     * @return \Imagine\Image\ImageInterface|object
     */
    public function resizeImagePreview($path, $path_preview_file, $resize_width = 0, $resize_height = 0, $options = [])
    {
        $img = $this->imageOpen($path);

        $size = $img->getSize();

        $width = $size->getWidth();
        $height = $size->getHeight();

        if ($size->getWidth() >= $size->getHeight() && $width > $resize_width) {
            $width = $resize_width;
            $height = $resize_width * $size->getHeight() / $size->getWidth();

        } else if ($size->getWidth() <= $size->getHeight() && $height > $resize_height) {
            $width = $resize_height * $size->getWidth() / $size->getHeight();
            $height = $resize_height;
        }

        $result = $img->resize(new \Imagine\Image\Box($width, $height))
            ->save($path_preview_file, $options);

        return $result;
    }


    public function response(): ?string
    {
        return Json::encode($this->result);
    }


    /**
     * Big Image optimisation to config size
     * @param $path
     */
    protected function resizeImageMaxOptimisation($path): void
    {
        $this->resizeImagePreview($path, $path, $this->image_width_max, $this->image_height_max);
    }

}