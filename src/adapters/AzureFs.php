<?php namespace kak\storage\adapters;

use League\Flysystem\Azure\AzureAdapter;
use MicrosoftAzure\Storage\Common\ServicesBuilder;
use yii\base\InvalidConfigException;

class AzureFs extends AbstractFs
{
    /**
     * @var string
     */
    public $accountName;
    /**
     * @var string
     */
    public $accountKey;
    /**
     * @var string
     */
    public $container;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string)$this->accountName === '') {
            throw new InvalidConfigException('The "accountName" property must be set.');
        }
        if ((string)$this->accountKey === '') {
            throw new InvalidConfigException('The "accountKey" property must be set.');
        }
        if ((string)$this->container === '') {
            throw new InvalidConfigException('The "container" property must be set.');
        }
        parent::init();
    }

    /**
     * @return AzureAdapter
     */
    protected function initAdapter()
    {
        return new AzureAdapter(
            ServicesBuilder::getInstance()->createBlobService(sprintf(
                'DefaultEndpointsProtocol=https;AccountName=%s;AccountKey=%s',
                base64_encode($this->accountName),
                base64_encode($this->accountKey)
            )),
            $this->container
        );
    }
}