<?php namespace kak\storage\adapters;

/**
 * Class FtpFs
 * @package kak\storage\adapters
 */
class FtpFs extends AbstractFs
{
    /**
     * @var string
     */
    public $host;
    /**
     * @var integer
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
     * @var boolean
     */
    public $ssl;
    /**
     * @var integer
     */
    public $timeout;
    /**
     * @var string
     */
    public $root;
    /**
     * @var integer
     */
    public $permPrivate;
    /**
     * @var integer
     */
    public $permPublic;
    /**
     * @var boolean
     */
    public $passive;
    /**
     * @var integer
     */
    public $transferMode;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string)$this->host === '') {
            throw new InvalidConfigException('The "host" property must be set.');
        }

        if ((string)$this->root !== '') {
            $this->root = Yii::getAlias($this->root);
        }

        parent::init();
    }

    /**
     * @return Ftp
     */
    protected function initAdapter()
    {
        $config = array_filter(
            [
                'host' => $this->host,
                'port' => $this->port,
                'username' => $this->username,
                'password' => $this->password,
                'ssl' => $this->ssl,
                'timeout' => $this->timeout,
                'root' => $this->root,
                'permPrivate' => $this->permPrivate,
                'permPublic' => $this->permPublic,
                'passive' => $this->passive,
                'transferMode' => $this->transferMode,
            ]
        );
        return new Ftp($config);
    }
}