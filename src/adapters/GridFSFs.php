<?php namespace kak\storage\adapters;

use League\Flysystem\GridFS\GridFSAdapter;
use MongoClient;
use yii\base\InvalidConfigException;

class GridFSFs extends AbstractFs
{
    /**
     * @var string
     */
    public $server;
    /**
     * @var string
     */
    public $database;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string)$this->server === '') {
            throw new InvalidConfigException('The "server" property must be set.');
        }

        if ((string)$this->database === '') {
            throw new InvalidConfigException('The "database" property must be set.');
        }

        parent::init();
    }

    /**
     * @return GridFSAdapter
     */
    protected function initAdapter()
    {
        $mongo = new MongoClient($this->server);
        return new GridFSAdapter($mongo->selectDB($this->database)->getGridFS());
    }
}