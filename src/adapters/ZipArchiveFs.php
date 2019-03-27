<?php namespace kak\storage\adapters;

use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Yii;
use yii\base\InvalidConfigException;


class ZipArchiveFs extends AbstractFs
{
    /**
     * @var string
     */
    public $path;
    /**
     * @var string|null
     */
    public $prefix;

    /**
     * @inheritdoc
     */
    public function init()
    {
        if ((string)$this->path === '') {
            throw new InvalidConfigException('The "path" property must be set.');
        }

        $this->path = Yii::getAlias($this->path);
        parent::init();
    }

    /**
     * @return ZipArchiveAdapter
     */
    protected function initAdapter()
    {
        return new ZipArchiveAdapter($this->path, null, $this->prefix);
    }
}