<?php namespace kak\storage\adapters;

use CedricZiel\FlysystemGcs\GoogleCloudStorageAdapter;
use yii\base\InvalidConfigException;

class GoogleCloudFs extends AbstractFs
{
    /**
     * @var string
     */
    public $projectId;
    /**
     * @var string
     */
    public $bucket;
    /**
     * @var string
     */
    public $prefix;
    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string) $this->projectId === '') {
            throw new InvalidConfigException('The "projectId" property must be set.');
        }

        if ((string)$this->bucket === '') {
            throw new InvalidConfigException('The "bucket" property must be set.');
        }

        parent::init();
    }
    /**
     * @return GoogleCloudStorageAdapter
     */
    protected function initAdapter()
    {
        $config = array_filter(
            [
                'projectId' => $this->projectId,
                'bucket' => $this->bucket,
                'prefix' => $this->prefix
            ]
        );
        return new GoogleCloudStorageAdapter(null, $config);
    }
}