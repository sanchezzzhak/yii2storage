<?php

namespace kak\storage\adapters;
use Yii;
use yii\base\Exception;
use yii\helpers\ArrayHelper;

class FileAdapter extends BaseAdapter
{
    const MOVE_MODE = 1;
    const MOVE_COPY = 0;

    private $_basePath = '@webroot/storage';
    private $_baseUrl  = '@web/storage';

    /**
     * Returns the upload directory path
     * @return string
     */
    public function getBasePath()
    {
        return Yii::getAlias($this->_basePath);
    }

    /**
     * Sets the upload directory path
     * @param $value
     */
    public function setBasePath($value)
    {
        $this->_basePath = rtrim($value, '/\\');
    }

    /**
     * @param $name
     * @return mixed
     */
    public function getAbsolutePath($name)
    {
        $name = str_replace($this->getBasePath() . DIRECTORY_SEPARATOR . $this->id ,'', $name);
        $name = str_replace($this->getBaseUrl()  . DIRECTORY_SEPARATOR . $this->id ,'', $name);
        return $this->getBasePath() . DIRECTORY_SEPARATOR . $this->id . DIRECTORY_SEPARATOR . trim($name,'/\\');
    }



    /**
     * Returns the base url
     * @return string the url pointing to the directory where we saved the files
     */
    public function getBaseUrl()
    {
        return Yii::getAlias('@web/storage');
    }
    /**
     * Sets the base url
     * @param string $value the url pointing to the directory where to get the files
     */
    public function setBaseUrl($value)
    {
        $this->_baseUrl = rtrim($value, '/\\');
    }

    /**
     * Returns the url of the file or empty string if the file doesn't exist.
     * @param string $name the name of the file
     * @return string
     */
    public function getUrl($name)
    {
        $name = str_replace($this->getBasePath() . DIRECTORY_SEPARATOR . $this->id ,'',  $name );
        return $this->getBaseUrl() . DIRECTORY_SEPARATOR . $this->id . DIRECTORY_SEPARATOR . trim($name,'/\\');
    }

    /**
     * @param $ext
     * @return string|void
     * @throws Exception
     */
    public function uniqueFilePath($ext)
    {
        $filename = $this->generateName(FileAdapter::GENERATE_SHA1) . '.' . $ext;

        $filedir = $this->getBasePath() . DIRECTORY_SEPARATOR . $this->id;

        for ($i = 0; $i < $this->level; $i++)
        {
            if (!file_exists($filedir))
            {
                $message = Yii::t('yii', 'Directory not exists: :filedir', array(':filedir' => $filedir));
                throw new Exception($message);
            }
            if (!is_dir($filedir))
            {
                $message = Yii::t('yii', 'Not directory: :filedir', array(':filedir' => $filedir));
                throw new Exception($message);
            }
            if (!is_writable($filedir))
            {
                $message = Yii::t('yii', 'Not writable directory: :filedir', array(':filedir' => $filedir));
                throw new Exception($message);
            }
            $filedir .= DIRECTORY_SEPARATOR . substr($filename, $i * 2, 2);
            @mkdir($filedir);
        }

        $filepath = $filedir . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($filepath))
        {
            $filepath = $this->uniqueFilePath($ext);
        }
        return $filepath;
    }

    /**
     * @param $source
     * @param array $options
     */
    public function save($source, $options = [])
    {
        $ext = pathinfo($source,PATHINFO_EXTENSION);
        $storage_filepath = $this->uniqueFilePath($ext);

        @copy($source, $storage_filepath);
        @chmod($storage_filepath, 0666);

        $delete_after = ArrayHelper::getValue($options,'delete_after',FileAdapter::MOVE_MODE);
        if ($delete_after === FileAdapter::MOVE_MODE)
            @unlink($source);

        parent::save($source,$options);
        return $storage_filepath;
    }

    /**
     * Removes a file
     * @param string $name the name of the file to remove
     * @return boolean
     */
    public function delete($name)
    {
        $name = str_replace($this->getBasePath() . DIRECTORY_SEPARATOR . $this->id,'',$name);
        parent::delete($name);
        return $this->fileExists($name) ? @unlink($this->getBasePath() . DIRECTORY_SEPARATOR . $name) : false;
    }

    /**
     * @param $name
     * @return bool
     */
    public function fileExists($name)
    {
        $name = str_replace($this->getBasePath() . DIRECTORY_SEPARATOR . $this->id,'',$name);
        return file_exists($this->getBasePath() . DIRECTORY_SEPARATOR . $name);
    }


}