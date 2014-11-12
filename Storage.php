<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Tutik Alexsandr
 * Date: 06.04.14
 * Time: 1:08
 * To change this template use File | Settings | File Templates.
 */

namespace kak\storage;

use Yii;
use yii\base\Exception;
use yii\log\Logger;


/**
 * Config for Storage

'storage' => [
    'base_path'  => __DIR__. '/../web/storage/',
    'base_url'   => '/storage/',
    'storages' => [
        'photo'  => [
            'level' => 3,
        ],
        'file' => [
            'level' => 3,
        ],
        'tmp'  => [
         'level' => 0,
        ],
    ],
],
 */

class Storage
{
    const COPY_MODE = 0;
    const MOVE_MODE = 1;

    private $_id;
    private $_basePath;
    private $_baseUrl;
    private $_storagePath;
    private $_storages = null;
    protected $_level;
    private $_source = null;
    private $_dest = null;

    /**
     * @var  string  delimiter for width prefix
     */
    private $_delimiter = '_';

    /**
     * @param string $id storage name
     * @throws Exception
     */
    public function __construct($id)
    {
        $log = Yii::$app->getLog()->getLogger();

        if (!is_string($id))
        {
            $message = Yii::t('yii', 'storage ID must be string.');
            $log->log($message, Logger::LEVEL_ERROR, 'storage');
            throw new Exception($message);
        }

        $config = Yii::$app->params['storage'];

        $this->_storages = array_keys($config['storages']);

        if (!$this->is_exist($id))
        {
            $message = Yii::t('yii', 'Storage ID={id} not exist.', array('id' => $id));
            $log->log($message, Logger::LEVEL_ERROR, 'storage');
            throw new Exception($message);
        }

        $this->_id = $id;
        $this->_level = $config['storages'][$id]['level'];

        if (($this->_basePath = realpath($config['base_path'])) === false || !is_dir($this->_basePath))
        {
            $message = Yii::t('yii', 'Base path "{path}" is not a valid directory.', ['path' => $config['base_path']]);
            $log->log($message, Logger::LEVEL_ERROR, 'storage');
            throw new Exception($message);
        }

        if (!is_writable($this->_basePath))
        {
            $message = Yii::t('yii', 'Not writable directory: {base_path}', [
                'base_path' => $this->_basePath
            ]);

            $log->log($message, Logger::LEVEL_ERROR, 'storage');
            throw new Exception($message);
        }

        $this->_storagePath = $this->_basePath . DIRECTORY_SEPARATOR . $this->_id;

        if (!file_exists($this->_storagePath)) mkdir($this->_storagePath);

        $this->_baseUrl =  $config['base_url'];
    }

    /**
     * Check exist storage
     * @param $id
     * @return bool
     */
    public function is_exist($id)
    {
        return in_array($id, $this->_storages);
    }

    /**
     * Get current storage
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     *
     * @return mixed base path to storage directory
     */
    public function getBasePath()
    {
        return $this->_basePath;
    }

    /**
     * @return string base url to storage directory
     */
    public function getBaseUrl()
    {
        return $this->_baseUrl;
    }

    /**
     * @return string
     */
    public function getStoragePath()
    {
        return $this->_storagePath;
    }


    /**
     * @param $path
     * @return bool|mixed
     */
    public static function has_id($path)
    {
        $config = Yii::$app->params['storage'];
        $storages = array_keys($config['storages']);
        $basePath = realpath($config['basePath']);

        $storagePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $path);
        $arr = explode(DIRECTORY_SEPARATOR, $storagePath);
        $id = array_shift($arr);

        return (in_array($id, $storages)) ?  $id : false;
    }

    const GENERATE_SYSTEM = 0;
    const GENERATE_SHA1 = 1;

    /**
     * @param $ext
     * @param $generate_type
     * @return string
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     */
    public function generateFileName($generate_type = self::GENERATE_SYSTEM)
    {
        switch($generate_type){
            case self::GENERATE_SHA1:
                $file =  sha1(Yii::$app->user->id.microtime());
                break;
            case self::GENERATE_SYSTEM:
            default:
                $file = Yii::$app->security->generateRandomKey();
        }
        return $file;
    }


    /**
     * get unique file path
     *
     * @param $ext
     * @return null|string
     * @throws \yii\base\Exception
     */
    public function unique_filepath($ext)
    {
        $logger = Yii::$app->getLog()->getLogger();

        if (!$ext) return null;

        $filename = $this->generateFileName(self::GENERATE_SHA1) . '.' . $ext;

        $filedir = $this->_storagePath;

        for ($i = 0; $i < $this->_level; $i++)
        {
            if (!file_exists($filedir))
            {
                $message = Yii::t('yii', 'Directory not exists: :filedir', array(':filedir' => $filedir));
                $logger->log($message, Logger::LEVEL_ERROR, 'storage');
                throw new Exception($message);
            }

            if (!is_dir($filedir))
            {
                $message = Yii::t('yii', 'Not directory: :filedir', array(':filedir' => $filedir));
                $logger->log($message, Logger::LEVEL_ERROR, 'storage');
                throw new Exception($message);
            }

            if (!is_writable($filedir))
            {
                $message = Yii::t('yii', 'Not writable directory: :filedir', array(':filedir' => $filedir));
                $logger->log($message, Logger::LEVEL_ERROR, 'storage');
                throw new Exception($message);
            }

            $filedir .= DIRECTORY_SEPARATOR.substr($filename, $i * 2, 2);

            @mkdir($filedir);
        }

        $filepath = $filedir.DIRECTORY_SEPARATOR.$filename;

        if (file_exists($filepath))
        {
            $filepath = $this->unique_filepath($ext);
        }

        return $filepath;
    }


    /**
     * Normalize path:
     * ```php
     * normalize('/srv/www/vhosts/devel/site.domen/storage/photo/0f/3d/3c/02/d2/0f3d3c02d2859a9f0a3d45916c06c256.jpg')
     * return '/srv/www/vhosts/devel/site.domen/storage/photo/0f/3d/3c/02/d2/0f3d3c02d2859a9f0a3d45916c06c256.jpg'
     *
     * normalize('photo/0f/3d/3c/02/d2/0f3d3c02d2859a9f0a3d45916c06c256.jpg')
     * return '/srv/www/vhosts/devel/gstnet.site.domen/storage/photo/0f/3d/3c/02/d2/0f3d3c02d2859a9f0a3d45916c06c256.jpg'
     * ```
     * @param $path
     * @return bool|string
     */
    public function normalize($path)
    {
        if (!$path || !is_string($path)) return null;
        //

        $file = $this->_basePath.DIRECTORY_SEPARATOR.$this->rel_path($path);
        if ($file === $this->_basePath.DIRECTORY_SEPARATOR.$path)
        {
            return $file;
        }

        return null;
    }

    /**
     * ```php
     * rel_path('/srv/www/vhosts/devel/site.domen/storage/photo/0f/3d/3c/02/d2/0f3d3c02d2859a9f0a3d45916c06c256.jpg')
     * return storage/photo/0f/3d/3c/02/d2/0f3d3c02d2859a9f0a3d45916c06c256.jpg
     * ```
     *
     * @param $path
     * @return string
     */
    public function rel_path($path)
    {
        if (trim($path) == '') return '';

        $prefix = null;
        $filename = basename($path);
        if (strpos($filename, $this->_delimiter))
        {
            $filename = explode($this->_delimiter, $filename);
            $prefix = $filename[0];
            $filename = $filename[1];
        }
        $filedir = $this->_id;

        for ($i = 0; $i < $this->_level; $i++)
        {
            $filedir .= DIRECTORY_SEPARATOR.substr($filename, $i * 2, 2);
        }

        if ($prefix) $filename = $prefix.$this->_delimiter.$filename;

        return $filedir.DIRECTORY_SEPARATOR.$filename;
    }

    /**
     *  Save file to path
     *
     * @param $path
     * @param $delete_after_save
     * @return null|string
     */
    public function save($path, $delete_after_save = self::MOVE_MODE)
    {
        $this->_source = $this->_dest = null;

//		if (($file = $this->normalize($path)) === false)
//			return false;

        if (($file = realpath($path)) === false || !is_file($file))
            return null;

        $ext = pathinfo($file, PATHINFO_EXTENSION);

        $storage_filepath = $this->unique_filepath($ext);
        if (!$storage_filepath)
            return null;

        $this->_source = $file;
        $this->_dest = $storage_filepath;
        @copy($file, $storage_filepath);
        @chmod($storage_filepath, 0666);

        if ($delete_after_save === self::MOVE_MODE)
            @unlink($file);

        return $storage_filepath;
    }

    /**
     * Transaction
     * @return bool
     */
    public function rollback()
    {
        if ($this->_source && $this->_dest)
        {
            @copy($this->_dest, $this->_source);
            @unlink($this->_dest);

            return true;
        }

        return false;
    }


    /**
     * Remove all files in the folder
     * @param $path
     * 	full path /www/host/storage/photo/a2/d4/34/a56e4890.jpg
     *	OR absolute basePath photo/a2/d4/34/a56e4890.jpg
     *
     * @return bool|int
     * ~ bool not find path
     * ~ int count delete file;
     */
    public function delete($path)
    {
        if (!$this->in_storage($path))
        {
            $path = $this->normalize($path);
        }

        if (!$path)
            return false;

        $pathinfo = pathinfo($path);
        $pattern = $pathinfo['dirname'].DIRECTORY_SEPARATOR.'*'.$this->_delimiter.$pathinfo['basename'];
        $i = 0;

        foreach (glob($pattern) as $p)
        {
            if (is_file($p))
            {
                $i++;
                unlink($p);
            }
        }
        if (is_file($path))
        {
            $i++;
            unlink($path);
        }

        return $i;
    }

    /**
     * Checks path whether the storage
     * --- The actual existence of a file on the file system is not checked
     * @param $path
     *
     * @return bool
     */
    public function in_storage($path)
    {
        if (!is_string($path))
            return false;

        $path1 = $this->_basePath.DIRECTORY_SEPARATOR.$this->rel_path($path);
        $p = pathinfo($path);
        if (!strlen($p['filename']) || !strlen($p['basename']))
            return false;

        $path2 = $p['dirname'].DIRECTORY_SEPARATOR.$p['basename'];

        return ($path1 && $path1 == $path2);
    }


    /**
     * Add prefix to file path
     * ```php
     * photo_path('photo/a2/d4/34/a56e4890.jpg',180,100)
     * return 'photo/a2/d4/34/180x100_a56e4890.jpg'
     * ```
     * @param $path
     * @param string $width
     * @param string $height
     * @return string
     */
    public static function photo_path($path, $width=0, $height = 0 )
    {
        if(empty($path)) return '';
        $path = pathinfo($path);
        $size = (!$width && !$height) ? '' : $width ."x".$height;
        $path_part = preg_replace('#[0-9]{1,3}x[0-9]{1,3}_#ixs', '', $path['basename']);

        return $path['dirname'].'/'.$size.$path_part;
    }


    /**
     * Возвращает url на фотку из storage, если фотку шириной $width не существует, создает ее.
     * Storage::instance('photo')->img($path, $width, true);
     * $path = '17/c9/e0/96/38/17c9e09638917af7d932cf9130603397.jpg'; файл, сохраненный в бд
     * $width = 118 или array(118, 178) - ширина фотки
     * $cap = true/false - если файла нет использовать или нет заглушку
     *
     * @param      $path
     * @param null $width
     * @param bool $cap
     *
     * @return bool|string
     *
    public function img($rel_path, $width = null, $cap = false)
    {
    $path = $this->normalize($rel_path);
    if (is_file($path))
    {
    if ($width == null) return $rel_path;

    if (is_numeric($width) && $width > 0)
    {
    $pathinfo = pathinfo($path);
    $file = $pathinfo['dirname'].DIRECTORY_SEPARATOR.$width.$this->_delimiter.$pathinfo['basename'];
    if (!is_file($file))
    {
    try
    {
    $imagine = new \Imagine\Gd\Imagine();
    $img = $imagine->open($path);
    $size = $img->getSize();
    $img->resize(new \Imagine\Image\Box($width, $width * $size->getHeight() / $size->getWidth()))
    ->save($file, array('quality' => 80));
    @chmod($file, 0777);
    }
    catch (Exception $e)
    {
    return false;
    }
    }

    $path = $this->rel_path($file);

    $a = $this->_baseUrl.DIRECTORY_SEPARATOR.$path;

    return $this->_baseUrl.DIRECTORY_SEPARATOR.$path;
    }

    return false;
    }
    }*/



}