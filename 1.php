<?php

//    private $_apapter;
//    private $_type;
//

public $id;
public $storages;

//
//    public function __construct($id, $config = [])
//    {
//        if (!count($config)) {
//            $config = ArrayHelper::getValue(Yii::$app->params, 'storage');
//            if(!$config) {
//                throw new Exception(Yii::t('yii', 'Storages not init config'));
//            }
//        }
//        parent::__construct($config);
//
//        $this->_type = ArrayHelper::remove($this->storages[$id], 'type', Storage::TYPE_STORAGE_FILE);
//
//        if (!is_string($id)) {
//            throw new Exception(Yii::t('yii', 'Storage ID must be string.'));
//        }
//
//        if (!isset($this->storages[$id])) {
//            $message = Yii::t('yii', 'Storage ID={id} not exist.', ['id' => $id]);
//            throw new Exception($message);
//        }
//
//        $this->id = $id;
//    }

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
$config = ArrayHelper::getValue($this->storages, $id);
if($config === null){
throw new InvalidConfigException(
sprintf('storage "%s" config not found', $id)
);
}

return $config;
}

/**
* @return adapters\AbstractAdapter
* @throws Exception
* @throws InvalidConfigException
*/
public function getAdapter(): adapters\AbstractAdapter
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

//    public function save($source, $options)
//    {
//        return $this->getAdapter()->save($source, $options);
//    }

/**
* @return mixed
*/
public function getType()
{
return $this->_type;
}