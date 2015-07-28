<?php

namespace kak\storage\adapters;
use yii\base\Application;
use yii\base\Exception;
use Yii;
use yii\helpers\ArrayHelper;

class ScpAdapter extends BaseAdapter implements AdapterInterface
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
				throw new Exception('SSH2 login is invalid public_key/private');
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
		$fileName = $this->generateName(FileAdapter::GENERATE_SHA1) . (!empty($ext) ? '.' . $ext: '');
		$fileDir = $this->public_path . "/" . $this->id;
		for ($i = 0; $i < $this->level; $i++)
		{
			if (!$this->fileExists($fileDir))
			{
				$message = Yii::t('yii', 'Directory not exists: {fileDir}', array('fileDir' => $fileDir));
				throw new Exception($message);
			}
			$fileDir .= "/" . substr($fileName, $i * 2, 2);
			$this->mkDir($fileDir);
		}
		$filePath = $fileDir . "/" . $fileName;
		if ($this->fileExists($filePath))
		{
			$filePath = $this->uniqueFilePath($ext);
		}
		return $filePath;
	}


	private function mkDir($name)
	{
		$session = $this->getResource();
		return @mkdir('ssh2.sftp://' . $session .  $this->getBasePath() . "/" . ltrim($name,'/') );
	}


	public function getBasePath()
	{
		return $this->base_path;
	}

    public function setBasePath($path)
    {
        return $this->base_path = $path;
    }

    /**
     * @param $source
     * @param array $options
     * @return string|void
     * @throws Exception
     */
    public function save($source, $options = [])
	{
		$ext = pathinfo($source,PATHINFO_EXTENSION);
		$storageFilePath = $this->uniqueFilePath($ext);

		$remote = $this->getBasePath() . "/" . ltrim( $storageFilePath,"/");
		ssh2_scp_send($this->getConnect(), $source,  $remote);

		$deleteAfter = ArrayHelper::getValue($options,'delete_after',self::MOVE_MODE);
		if ($deleteAfter === self::MOVE_MODE)
			@unlink($source);


		parent::save($source,$options);
		return $storageFilePath;
	}


    /**
     * Renames a remote file
     *
     * @param  string $from The current file that is being renamed
     * @param  string $to   The new file name that replaces from
     *
     * @return Boolean TRUE on success, or FALSE on failure
     */
    public function rename($from, $to)
    {
        return ssh2_sftp_rename($this->getResource(), $from, $to);
    }

    /**
     * @param $name
     * @return bool|void
     */
    public function delete($name){
        $name = ltrim($name,'/');
        $session = $this->getResource();
        return ($this->fileExists($name) && !empty($name)) ?
            @unlink("ssh2.sftp://". $session .  $this->getBasePath() . "/" . $name) : false;

    }

    /**
     * @return resource
     * @throws Exception
     */
    private function getResource()
    {
        return ssh2_sftp( $this->getConnect() );
    }

	/**
	 * @param $name
	 * @return bool
	 */
	public function fileExists($name)
	{
		$session = $this->getResource();
		return file_exists('ssh2.sftp://' . $session .  $this->getBasePath() . "/" . ltrim($name,'/') );
	}
}