<?php namespace kak\storage\adapters;

use League\Flysystem\Adapter\Local;
use Yii;
use yii\base\InvalidConfigException;

class LocalFs extends AbstractFs
{
    /**
     * @var string
     */
    public $path;
    /**
     * @var int
     */
    public $writeFlags = FILE_APPEND;
    /**
     * @var int
     */
    public $linkHandling = Local::SKIP_LINKS;
    /**
     * @var array
     */
    public $permissions = [
        [
            'file' => [
                'public' => 0755,
                'private' => 0755,
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ]
        ]
    ];

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
     * @return Local
     */
    protected function initAdapter()
    {
        return new Local($this->path, 0);
    }

}