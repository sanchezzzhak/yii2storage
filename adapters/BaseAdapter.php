<?php
/**
 * Created by PhpStorm.
 * User: PHPdev
 * Date: 25.12.2014
 * Time: 11:04
 */

namespace kak\storage\adapters;
use yii\base\Component;
use yii\base\Event;
use Yii;

class BaseAdapter extends Component
{
    const EVENT_SAVE      = 'save';
    const EVENT_DELETE    = 'delete';
    const EVENT_RENAME    = 'rename';

    const GENERATE_SYSTEM = 0;
    const GENERATE_SHA1 = 1;

    public $id;
    public $level = 1;

    /**
     * @param $name
     * @return mixed
     */
    public function getAbsolutePath($name)
    {
        return $name;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getUrl($name)
    {
        return $name;
    }

    /**
     * @param $name
     * @return bool
     */
    public function fileExists($name)
    {
        return true;
    }

    public function save($source, $options = [])
    {
        $event = new Event;
        $this->trigger(BaseAdapter::EVENT_SAVE , $event);
    }

    /**
     * @param $sourceKey
     * @param $targetKey
     */
    public function copy($sourceKey, $targetKey, $options = [])
	{

	}

    public function delete($name)
    {
        $event = new Event;
        $this->trigger(BaseAdapter::EVENT_DELETE , $event);
    }

    public function rename($sourceKey, $targetKey)
    {
        $event = new Event;
        $this->trigger(BaseAdapter::EVENT_RENAME , $event);
    }

    public function uniqueFilePath($ext = null){}

    /**
     * @param $type
     * @return string
     */
    public function generateName($type = null)
    {
        switch($type)
        {
            case BaseAdapter::GENERATE_SHA1:
                return sha1(Yii::$app->user->id.microtime());
            case BaseAdapter::GENERATE_SYSTEM:
            default:
                return Yii::$app->security->generateRandomKey();
        }
    }

    /**
     * Returns the upload directory path
     * @return string
     */
    public function getBasePath(){}

    /**
     * Sets the upload directory path
     * @param $value
     */
    public function setBasePath($value){}

    /**
     * Returns the base url
     * @return string the url pointing to the directory where we saved the files
     */
    public function getBaseUrl(){}
    /**
     * Sets the base url
     * @param string $value the url pointing to the directory where to get the files
     */
    public function setBaseUrl($value){}







} 