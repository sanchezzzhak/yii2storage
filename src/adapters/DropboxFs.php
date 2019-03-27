<?php namespace kak\storage\adapters;

use Spatie\Dropbox\Client;
use Spatie\FlysystemDropbox\DropboxAdapter;
use yii\base\InvalidConfigException;

class DropboxFs extends AbstractFs
{
    /**
     * @var string
     */
    public $token;
    /**
     * @var string
     */
    public $prefix = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string)$this->token === '') {
            throw new InvalidConfigException('The "token" property must be set.');
        }

        if (!is_string($this->prefix)) {
            throw new InvalidConfigException('The "prefix" property must be a string.');
        }

        parent::init();
    }

    /**
     * @return DropboxAdapter
     */
    protected function initAdapter()
    {
        return new DropboxAdapter(
            new Client($this->token), $this->prefix
        );
    }
}