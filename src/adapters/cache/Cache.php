<?php


namespace dosamigos\flysystem\cache;
use League\Flysystem\Cached\Storage\AbstractCache;
use yii\caching\Cache as BaseCache;

/**
 * Class YiiCache
 * @package dosamigos\flysystem\cache
 */
class Cache extends AbstractCache
{
    /**
     * @var BaseCache
     */
    protected $yiiCache;
    /**
     * @var string
     */
    protected $key;
    /**
     * @var integer
     */
    protected $duration;
    /**
     * @param Cache $cache
     * @param string $key
     * @param integer $duration
     */
    public function __construct(BaseCache $cache, $key = 'flysystem', $duration = 0)
    {
        $this->cache = $cache;
        $this->key = $key;
        $this->duration = $duration;
    }
    /**
     * @inheritdoc
     */
    public function load()
    {
        $contents = $this->cache->get($this->key);
        if ($contents !== false) {
            $this->setFromStorage($contents);
        }
    }
    /**
     * @inheritdoc
     */
    public function save()
    {
        return $this->cache->set($this->key, $this->getForStorage(), $this->duration);
    }
}
