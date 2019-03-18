<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Tutik Alexsandr
 * Date: 06.04.14
 * Time: 1:08
 * To change this template use File | Settings | File Templates.
 */

namespace kak\storage;

use Aws\S3\Enum\CannedAcl;
use Aws\S3\S3Client;
use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\log\Logger;


/**
 * Config for Storage
 *
 * 'storage' => [
 *
 * 'storages' => [
 * 'photo'  => [
 * 'level' => 3,
 * ],
 * 'file' => [
 * 'level' => 3,
 * ],
 * 'tmp'  => [
 * 'level' => 0,
 * ],
 * ],
 * ],
 */

/***
 * Class Storage
 * @package kak\storage
 */
class Storage extends Component
{
    const TYPE_STORAGE_AMAZON = 'amazon';
    const TYPE_STORAGE_FILE = 'file';
    const TYPE_STORAGE_SCP = 'scp';

    private $_apapter;
    private $_type;


    public $id;
    public $storages;


    public function __construct($id, $config = [])
    {
        if (!count($config)) {
            $config = ArrayHelper::getValue(Yii::$app->params, 'storage');
            if(!$config) {
                throw new Exception(Yii::t('yii', 'Storages not init config'));
            }
        }
        parent::__construct($config);

        $this->_type = ArrayHelper::remove($this->storages[$id], 'type', Storage::TYPE_STORAGE_FILE);

        if (!is_string($id)) {
            throw new Exception(Yii::t('yii', 'Storage ID must be string.'));
        }

        if (!isset($this->storages[$id])) {
            $message = Yii::t('yii', 'Storage ID={id} not exist.', ['id' => $id]);
            throw new Exception($message);
        }

        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }


    /**
     * @param $id
     * @return mixed
     */
    private function getStorageConfigById($id)
    {
        return ArrayHelper::getValue($this->storages, $id);
    }

    /**
     * @return \kak\storage\adapters\AmazonAdapter|\kak\storage\adapters\FileAdapter|\kak\storage\adapters\ScpAdapter
     * @throws \yii\base\InvalidConfigException
     */
    public function getAdapter()
    {
        if ($this->_apapter == null) {
            $config = $this->getStorageConfigById($this->id);
            $config['id'] = $this->id;

            switch ($this->_type) {
                case Storage::TYPE_STORAGE_AMAZON:
                    $this->_apapter = new adapters\AmazonAdapter($config);
                    break;
                case Storage::TYPE_STORAGE_FILE:
                    $this->_apapter = new adapters\FileAdapter($config);
                    break;
                case Storage::TYPE_STORAGE_SCP:
                    $this->_apapter = new adapters\ScpAdapter($config);
                    break;

            }
        }
        return $this->_apapter;
    }

    public function save($source, $options)
    {
        return $this->getAdapter()->save($source, $options);
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->_type;
    }
}