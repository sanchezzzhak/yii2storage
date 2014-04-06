<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Александр
 * Date: 06.04.14
 * Time: 1:08
 * To change this template use File | Settings | File Templates.
 */

namespace sanchezzzhak\yii2storage;

use Yii;
use yii\base\Exception;
use yii\log\Logger;


/**
* config for Storage
*
*  base_path .  . . . . . . . . . . . . . . .  путь к корневому директорию
*   |
 *   `-- banner  . . . . . . . . . . . . . . .  директорий для баннеров
 *   |   `-- nesting_level directories . . . .
 *   |
 *   `-- photo . . . . . . . . . . . . . . . .  директорий для фоток
 *   |   `-- nesting_level directories . . . .
 *   |
 *   `-- video   . . . . . . . . . . . . . . .  директорий для видеороликов
 *      `-- nesting_level directories  . . . .
 *
 *  base_url . . . . . . . . . . . . . . . . . url корневого директория
 *
 *
 *     class  => '',
 *    'storage'       => array(
 *        'base_path'  => $commonConfigDir."/../../storage/",
 *        'base_url'   => "/storage/",
 *        'types' => array(
 *            'photo'  => array(
 *                'level' => 5,
 *            ),
 *            'banner' => array(
 *                'level' => 5,
 *            ),
 *            'video'  => array(
 *                'level' => 5,
 *            ),
 *        ),
 *    ),
 *
 */
class Storage
{

	const COPY_MODE = false;
	const MOVE_MODE = true;

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

	public function __construct($id)
	{
		$log = Yii::$app->getLog()->getLogger();

		if (!is_string($id))
		{
			$message = Yii::t('storage', 'storage ID must be string.');
			$log->log($message, Logger::LEVEL_ERROR, 'storage');
			throw new Exception($message);
		}

		$config = Yii::$app->storage['storage'];
		$this->_storages = array_keys($config['storages']);

		if (!$this->is_exist($id))
		{
			$message = Yii::t('storage', 'Storage ID={id} not exist.', array('{id}' => $id));
			$log->log($message, Logger::LEVEL_ERROR, 'storage');
			throw new Exception($message);
		}

		$this->_id = $id;
		$this->_level = $config['storages'][$id]['level'];

		if (($this->_basePath = realpath($config['basePath'])) === false || !is_dir($this->_basePath))
		{
			$message = Yii::t('storage', 'Base path "{path}" is not a valid directory.',
				array('{path}' => $config['basePath']));
			$log->log($message, Logger::LEVEL_ERROR, 'storage');
			throw new Exception($message);
		}

		if (!is_writable($this->_basePath))
		{
			$message = Yii::t('storage', 'Not writable directory: {basePath}', [
				'{basePath}' => $this->_basePath
			]);

			$log->log($message, Logger::LEVEL_ERROR, 'storage');
			throw new Exception($message);
		}

		$this->_storagePath = $this->_basePath.DIRECTORY_SEPARATOR.$this->_id;

		if (!file_exists($this->_storagePath))
			mkdir($this->_storagePath);

		$this->_baseUrl = Yii::$app->basePath . DIRECTORY_SEPARATOR . (trim($config['baseUrl'], DIRECTORY_SEPARATOR));
	}

	/**
	 * @param $id
	 * @return bool
	 */
	public function is_exist($id)
	{
		return in_array($id, $this->_storages);
	}

	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @return mixed base path to storage directory
	 */
	public function getBasePath()
	{
		return $this->_basePath;
	}

	/**
	 * @return string
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
		$config = Yii::$app->storage['storage'];
		$storages = array_keys($config['storages']);
		$basePath = realpath($config['basePath']);

		$storagePath = str_replace($basePath.DIRECTORY_SEPARATOR, '', $path);
		$arr = explode(DIRECTORY_SEPARATOR, $storagePath);
		$id = array_shift($arr);

		return (in_array($id, $storages)) ?  $id : false;
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

		//$filename = md5(uniqid()).'.'.$ext;
		$filename = sha1(Yii::$app->user->id.microtime()).'.'.$ext;

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
	 * @param $path full path /www/host/storage/photo/a2/d4/34/a56e4890.jpg
	 *					 OR absolute basePath photo/a2/d4/34/a56e4890.jpg
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








}