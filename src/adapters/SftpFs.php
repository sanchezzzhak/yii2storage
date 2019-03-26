<?php namespace kak\storage\adapters;

use League\Flysystem\Sftp\SftpAdapter;
use Yii;
use yii\base\InvalidConfigException;

class SftpFs extends AbstractFs
{
    /**
     * @var string
     */
    public $host;
    /**
     * @var string
     */
    public $port;
    /**
     * @var string
     */
    public $username;
    /**
     * @var string
     */
    public $password;
    /**
     * @var integer
     */
    public $timeout;
    /**
     * @var string
     */
    public $root;
    /**
     * @var string
     */
    public $privateKey;
    /**
     * @var integer
     */
    public $permPrivate;
    /**
     * @var integer
     */
    public $permPublic;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string)$this->host === '') {
            throw new InvalidConfigException('The "host" property must be set.');
        }
        if ((string)$this->username === '') {
            throw new InvalidConfigException('The "username" property must be set.');
        }
        if ((string)$this->password === '' || (string)$this->privateKey === '') {
            throw new InvalidConfigException('Either "password" or "privateKey" property must be set.');
        }
        if ((string)$this->root !== '') {
            $this->root = Yii::getAlias($this->root);
        }
        parent::init();
    }

    /**
     * @return SftpAdapter
     */
    protected function initAdapter()
    {
        $config = array_filter(
            [
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => $this->password,
                'timeout' => $this->timeout,
                'root' => $this->root,
                'permPrivate' => $this->permPrivate,
                'permPublic' => $this->permPublic,
                'privatekey' => $this->privateKey
            ]
        );
        return new SftpAdapter($config);
    }
}