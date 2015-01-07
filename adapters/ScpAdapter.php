<?php

namespace kak\storage\adapters;


use yii\base\Exception;
use Yii;
use yii\helpers\ArrayHelper;

class ScpAdapter extends BaseAdapter
{
	const MOVE_MODE = 1;
	const COPY_MODE = 0;


	public $host;
	public $port = 22;

	public $base_path;
	public $public_path = 'storage';


	public $username;
	public $password;
	public $public_key;
	public $private_key;

	private $_conn;


	public function __construct($config = [])
	{
		parent::__construct($config);

		if(!function_exists('ssh2_connect'))
		{
			throw new Exception('libssh2, php_ssh2 library is not required');
		}

		if(empty($this->username))
		{
			throw new Exception('property username required');
		}

	}



	/**
	 * @return resource
	 * @throws \Exception
	 */
	public function getConnect()
	{
		$this->_conn = ssh2_connect($this->host, $this->port);

		if(!empty($this->public_key) && !empty($this->private_key))
		{
			if (ssh2_auth_pubkey_file($this->_conn, $this->username, Yii::getAlias($this->public_key) , Yii::getAlias($this->private_key)) === false) {
				throw new Exception('SSH2 login is invalid');
			}
			return $this->_conn;
		}

		if (!empty($this->password))
		{
			if(ssh2_auth_password($this->_conn, $this->username, $this->password) === false)
			{
				throw new Exception('SSH2 login is invalid');
			}
			return $this->_conn;
		}
		throw new Exception('Unknown SSH2 login type');
	}

	/**
	 * @param $ext
	 * @return string|void
	 * @throws Exception
	 */
	public function uniqueFilePath($ext = null)
	{
		$filename = $this->generateName(FileAdapter::GENERATE_SHA1) . (!empty($ext) ? '.' . $ext: '');
		$filedir = $this->public_path . "/" . $this->id;
		for ($i = 0; $i < $this->level; $i++)
		{
			if (!$this->fileExists($filedir))
			{
				$message = Yii::t('yii', 'Directory not exists: {filedir}', array('filedir' => $filedir));
				throw new Exception($message);
			}
			$filedir .= "/" . substr($filename, $i * 2, 2);
			$this->_mkdir($filedir);
		}
		$filepath = $filedir . "/" . $filename;
		if ($this->fileExists($filepath))
		{
			$filepath = $this->uniqueFilePath($ext);
		}
		return $filepath;
	}


	private function _mkdir($name)
	{
		$session = ssh2_sftp( $this->getConnect() );
		return @mkdir('ssh2.sftp://' . $session .  $this->getBasePath() . "/" . ltrim($name,'/') );
	}


	public function getBasePath()
	{
		return $this->base_path;
	}

	/*
	 * @param $source
	 * @param array $options
	 */
    public function save($source, $options = [])
	{
		$ext = pathinfo($source,PATHINFO_EXTENSION);
		$storage_filepath = 		$this->uniqueFilePath($ext);

		$remote = $this->getBasePath() . "/" . ltrim( $storage_filepath,"/");
		ssh2_scp_send($this->getConnect(), $source,  $remote);

		$delete_after = ArrayHelper::getValue($options,'delete_after',self::MOVE_MODE);
		if ($delete_after === self::MOVE_MODE)
			@unlink($source);


		parent::save($source,$options);
		return $storage_filepath;
	}

	/**
	 * @param $sourceKey
	 * @param $targetKey
	 * @param array $options
	 */
	public function copy($sourceKey, $targetKey, $options = [])
	{

	}


	/**
	 * @param $name
	 * @return bool
	 */
	public function fileExists($name)
	{
		$session = ssh2_sftp( $this->getConnect() );
		return file_exists('ssh2.sftp://' . $session .  $this->getBasePath() . "/" . ltrim($name,'/') );
	}
}