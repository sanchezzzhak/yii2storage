<?php namespace kak\storage;

use yii\base\BaseObject;

/**
 * Class File
 * @package kak\storage
 */
class File extends BaseObject
{
    /**
     * @var
     */
    protected $path;
    /**
     * @var
     */
    protected $extension;
    /**
     * @var
     */
    protected $size;
    /**
     * @var
     */
    protected $mimeType;
    /**
     * @var
     */
    protected $pathinfo;

    /**
     * @param $file string|\yii\web\UploadedFile
     * @return self
     * @throws InvalidConfigException
     */
    public static function create($file)
    {
        if (is_a($file, self::class)) {
            return $file;
        }

        if (is_a($file, UploadedFile::class)) {
            if ($file->error) {
                throw new InvalidParamException("File upload error \"{$file->error}\"");
            }
            return \Yii::createObject([
                'class' => self::class,
                'path' => $file->tempName,
                'extension' => $file->getExtension()
            ]);
        }
        
        return \Yii::createObject([
            'class' => self::class,
            'path' => FileHelper::normalizePath($file)
        ]);
        
    }

    /**
     * @param $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }
    /**
     * @param $extension
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
    }
    /**
     * @return bool
     */
    public function hasErrors()
    {
        return $this->error !== false;
    }


}