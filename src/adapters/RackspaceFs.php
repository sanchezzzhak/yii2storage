<?php namespace kak\storage\adapters;

use League\Flysystem\Rackspace\RackspaceAdapter;
use OpenCloud\Rackspace;
use yii\base\InvalidConfigException;

class RackspaceFs extends AbstractFs
{
    /**
     * @var string
     */
    public $endpoint;
    /**
     * @var string
     */
    public $username;
    /**
     * @var string
     */
    public $apiKey;
    /**
     * @var string
     */
    public $region;
    /**
     * @var string
     */
    public $container;
    /**
     * @var string|null
     */
    public $prefix;
    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string)$this->endpoint === '') {
            throw new InvalidConfigException('The "endpoint" property must be set.');
        }

        if ((string)$this->username === '') {
            throw new InvalidConfigException('The "username" property must be set.');
        }

        if ((string)$this->apiKey === '') {
            throw new InvalidConfigException('The "apiKey" property must be set.');
        }

        if ((string)$this->region === '') {
            throw new InvalidConfigException('The "region" property must be set.');
        }

        if ((string)$this->container === '') {
            throw new InvalidConfigException('The "container" property must be set.');
        }
        parent::init();
    }
    /**
     * @return RackspaceAdapter
     */
    protected function initAdapter()
    {
        $client = new Rackspace($this->endpoint, ['username' => $this->username, 'apiKey' => $this->apiKey]);
        $container = $client
            ->objectStoreService('cloudFiles', $this->region)
            ->getContainer($this->container);
        return new RackspaceAdapter(
            $container,
            $this->prefix
        );
    }
}