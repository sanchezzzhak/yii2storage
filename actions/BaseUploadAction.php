<?php
/**
 * Created by PhpStorm.
 * User: PHPdev
 * Date: 24.12.2014
 * Time: 15:32
 */

namespace kak\storage\actions;

use kak\storage\Storage;
use Yii;
use yii\base\Action;
use yii\base\ErrorException;
use yii\web\HttpException;

class BaseUploadAction extends Action
{
    const IMAGE_RESIZE = 0;
    const IMAGE_THUMB  = 1;


    public static $EXTENSION_IMAGE = ['image/gif','image/png','image/jpg','image/jpeg'];

    public $storage = 'tmp';
    public $extension_allowed = [];

    public $image_width_max = 1024;
    public $image_height_max = 768;

    public $resize_image = [
        'preview'   => [600,400, UploadAction::IMAGE_RESIZE],
        'thumbnail' => [120,120, UploadAction::IMAGE_THUMB]
    ];

    public $_result = [];


    public function init()
    {
        parent::init();

        // use storage
        if($this->storage)
        {
            try
            {
                $storage  = new Storage($this->storage);
            }
            catch (ErrorException $error )
            {
                throw new HttpException(500, $error->getMessage() );
            }
        }

    }

    /**
     * @param $path_file
     * @param $model
     */
    public function _image($model_file)
    {
        $storage = new Storage($this->storage);
        $adapter = $storage->getAdapter();
        $path_file = $adapter->getAbsolutePath($model_file);

        /*** Image if */
        list($width, $height) = @getimagesize($path_file);
        if($width > 0 || $height > 0)
        {
            $this->resizeImageMaxOptimisation($path_file);

            foreach($this->resize_image as $prefix => $param )
            {
                list($image_width, $image_height) = $param;
                $type = isset($param[2]) ? $param[2] : UploadAction::IMAGE_RESIZE;

                $info_path       = pathinfo($path_file);
                $image_save      = $info_path['dirname'] . '/' . $prefix . '_' . $info_path['basename'];
                $image_path_save = $image_save;

                switch($type)
                {
                    case UploadAction::IMAGE_RESIZE:
                        $this->resizeImagePreview($path_file , $image_path_save , $image_width, $image_height );
                        break;
                    case UploadAction::IMAGE_THUMB:
                        $this->resizeImageThumbnail($path_file , $image_path_save , $image_width, $image_height );
                        break;
                }
                //$this->_result['images'][$prefix]['path'] = $image_save;
                $this->_result['images'][$prefix]['url']  = $adapter->getUrl($image_save) ;
            }
        }
        /*** Image end */
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->_result;
    }

    public function getErrors()
    {
        return $this->_result['errors'];
    }

    
    /**
     * @param $url
     * @return string|null
     */
    public function getExtension($url)
    {
        if(preg_match('#\.([\w\d]+)(?:\?|$)#is',$url,$matches))
        {
            return $matches[1];
        }
        return null;
    }


    /**
     * @param $path
     * @param $path_thumbnail_file
     * @param int $resize_width
     * @param int $resize_height
     */
    public function resizeImageThumbnail($path , $path_thumbnail_file,  $resize_width = 0, $resize_height = 0)
    {
        $imagine = $this->getImageDriver();
        $img = $imagine->open($path);
        return $img->thumbnail(new \Imagine\Image\Box($resize_width , $resize_height ),\Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND )
            ->save($path_thumbnail_file, ['quality' => 100]);
    }

    /**
     * @return \Imagine\Gd\Imagine|\Imagine\Gmagick\Imagine|\Imagine\Imagick\Imagine
     */
    protected function getImageDriver()
    {
        if(class_exists('Imagick',false))
            return new \Imagine\Imagick\Imagine;

        if(class_exists('Gmagick',false))
            return new \Imagine\Gmagick\Imagine;

        return new \Imagine\Gd\Imagine;
    }


    /***
     * @param $path
     * @param $path_preview_file
     * @param int $resize_width
     * @param int $resize_height
     */
    public function resizeImagePreview($path , $path_preview_file , $resize_width = 0, $resize_height = 0)
    {
        $imagine = $this->getImageDriver();

        $img = $imagine->open($path);
        $size = $img->getSize();

        $width  = $size->getWidth();
        $height =  $size->getHeight();

        if( $size->getWidth() >= $size->getHeight() && $width > $resize_width )
        {
            $width  = $resize_width;
            $height = $resize_width * $size->getHeight() / $size->getWidth();

        }
        else if( $size->getWidth() <= $size->getHeight() && $height > $resize_height )
        {
            $width =  $resize_height *  $size->getWidth() / $size->getHeight();
            $height = $resize_height;
        }

        return $img->resize(new \Imagine\Image\Box($width, $height) )
            ->save($path_preview_file, ['quality' => 100]);

    }

    /**
     * Big Image optimisation to config size
     * @param $path
     */
    protected function resizeImageMaxOptimisation($path)
    {
        $this->resizeImagePreview($path,$path,$this->image_width_max, $this->image_height_max );
    }

}