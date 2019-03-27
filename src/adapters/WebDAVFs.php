<?php namespace kak\storage\adapters;

use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;
use yii\base\InvalidConfigException;

class WebDAVFs extends AbstractFs
{
    /**
     * @var string
     */
    public $baseUri;
    /**
     * @var string
     */
    public $userName;
    /**
     * @var string
     */
    public $password;
    /**
     * @var string
     */
    public $proxy;
    /**
     * @var integer
     */
    public $authType;
    /**
     * @var integer
     */
    public $encoding;
    /**
     * @var string|null
     */
    public $prefix;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string)$this->baseUri === '') {
            throw new InvalidConfigException('The "baseUri" property must be set.');
        }
        parent::init();
    }

    /**
     * @return WebDAVAdapter
     */
    protected function initAdapter()
    {
        $config = array_filter(
            [
                'baseUri' => $this->baseUri,
                'userName' => $this->userName,
                'password' => $this->password,
                'proxy' => $this->proxy,
                'authType' => $this->authType,
                'encoding' => $this->encoding,
            ]
        );
        return new WebDAVAdapter(new Client($config), $this->prefix);
    }
}