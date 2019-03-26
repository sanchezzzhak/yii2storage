<?php namespace kak\storage;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\log\Logger;
use yii\web\UploadedFile;
use kak\storage\adapters\AbstractFs;


/***
 * Class Storage
 * @package kak\storage
 */
class Storage extends Component implements StorateInterface
{
    /**
     * @var array
     */
    public $storages;
    /**
     * @var array
     */
    public $adapters;

    /**
     * @param $storageId
     * @return AbstractFs
     * @throws InvalidConfigException
     */
    public function getAdapterByStorageId($storageId): AbstractFs
    {
        $storageCongif = $this->getStorageConfigById($storageId);

        $adapterId = $storageCongif['adapter'] ?? '';
        if ((string)$adapterId === '') {
            throw new \Exception('Adapter link not set to config storage["%s"]["adapter"]');
        }
        $adapterConfig = $this->getAdapterConfigById($adapterId);

        return Yii::createObject($adapterConfig);
    }

    public function getAdapterConfigById($adapterId): array
    {
        $adapterConfig = $this->adapters[$adapterId] ?? [];
        if ($adapterConfig === []) {
            throw new \Exception('Config adapter not found');
        }
        return $adapterConfig;
    }


    public function getStorageConfigById($storageId): array
    {
        $storageCongif = $this->storages[$storageId] ?? [];
        if ($storageCongif === []) {
            throw new \Exception(
                sprintf('Config storage "%s" not found', $storageId)
            );
        }
        return $storageCongif;
    }


    public function getStorageLevelById($storageId): int
    {
        $storageCongif = $this->storages[$storageId] ?? [];
        return $storageCongif['level'] ?? 0;
    }

    /**
     * @param int $storageId
     * @param $stream
     * @param string $ext
     * @return array
     * @throws InvalidConfigException
     */
    public function saveStream(string $storageId, $stream, string $ext): array
    {
        $adapter = $this->getAdapterByStorageId($storageId);

        $level = $this->getStorageLevelById($storageId);
        $fileName = $adapter->uniqueFilePath($ext, $level);

        $fileStorePath = sprintf('%s/%s', $storageId, $fileName);
        $isWrite = $adapter->writeStream($fileStorePath, $stream);
        $result = $adapter->getMetadata($fileStorePath);

        return $isWrite && $result
            ? $result
            : [];
    }

    public function save(string $storageId, $file, array $options = []): array
    {
        $fileSourcePath = '';
        $fileSouceName = '';
        if ($file instanceof UploadedFile) {
            $fileSourcePath = $file->tempName;
            $fileSouceName = $file->name;
        } else if (is_array($file) && array_key_exists('tmp_name', $file)) {
            $fileSourcePath = $file['tmp_name'];
            $fileSouceName = $file['name'];
        }

        $ext = pathinfo($fileSouceName, PATHINFO_EXTENSION);
        $stream = fopen($fileSourcePath, 'r+');
        $result = $this->saveStream($storageId, $stream, $ext, $options);
        fclose($stream);
        return $result;
    }


   // ===== addons =====











    /**
     * @return \Imagine\Gd\Imagine|\Imagine\Gmagick\Imagine|Imagine
     */
    private function getImageDriver()
    {
        if (class_exists('Imagick', false))
            return new \Imagine\Imagick\Imagine;

        if (class_exists('Gmagick', false))
            return new \Imagine\Gmagick\Imagine;

        return new \Imagine\Gd\Imagine;
    }


    /**
     * @return \Imagine\Gd\Imagine|\Imagine\Gmagick\Imagine|Imagine
     */
    private function getImageMetadataReader()
    {
        if (function_exists('exif_read_data')) {
            return new \Imagine\Image\Metadata\ExifMetadataReader;
        }

        return new \Imagine\Image\Metadata\DefaultMetadataReader;
    }


    /**
     * @param \Imagine\Image\ImageInterface $img
     * @throws \ImagickException
     */
    private function autoFixImageOrentation(\Imagine\Image\ImageInterface $img): void
    {
        $metadata = $img->metadata();
        $data = $metadata->toArray();

        $orientation = $data['exif.Orientation'] ?? 0;
        if ($orientation) {
            switch ($orientation) {
                case 8:
                    $img->rotate(-90);
                case 3:
                    $img->rotate(180);
                case 6:
                    $img->rotate(90);
            }
        }
    }

    /**
     * @param resource $stream
     * @return \Imagine\Image\ImageInterface|object
     * @throws \ImagickException
     */
    private function readImageStream($stream)
    {
        $imagine = $this->getImageDriver();
        $imagine->setMetadataReader($this->getImageMetadataReader());
        $img = $imagine->read($stream);
        $this->autoFixImageOrentation($img);

        return $img;
    }


    /**
     * @param $storageId
     * @param $filePath
     * @param $prefix
     * @param int $resizeWidth
     * @param int $resizeHeight
     * @param array $options
     * @return array
     * @throws InvalidConfigException
     * @throws \ImagickException
     */
    public function resizeImagePreviewByStorageId(
        $storageId,
        $filePath,
        $prefix,
        $resizeWidth = 0,
        $resizeHeight = 0,
        $options = []
    ): array
    {
        $adapter = $this->getAdapterByStorageId($storageId);
        $stream = $adapter->readStream($filePath);


        $img = $this->readImageStream($stream);
        $size = $img->getSize();

        $info = pathinfo($filePath);
        $fileStorePath = sprintf('%s/%s-%s', $info['dirname'], $prefix, $info['basename']);

        $width = $size->getWidth();
        $height = $size->getHeight();

        if ($size->getWidth() >= $size->getHeight() && $width > $resizeWidth) {
            $width = $resizeWidth;
            $height = $resizeWidth * $size->getHeight() / $size->getWidth();

        } else if ($size->getWidth() <= $size->getHeight() && $height > $resizeHeight) {
            $width = $resizeHeight * $size->getWidth() / $size->getHeight();
            $height = $resizeHeight;
        }

        $img->resize(new \Imagine\Image\Box($width, $height));

        $tmpFile = tempnam(sys_get_temp_dir(), sprintf('img-%s', time()));
        $img->save($tmpFile);

        if(is_resource($stream)){
            fclose($stream);
        }

        $stream = fopen($tmpFile, 'r+');
        $isWrite = $adapter->writeStream($fileStorePath, $stream);
        if(is_resource($stream)){
            fclose($stream);
        }
        @unlink($tmpFile);
        $result = $adapter->getMetadata($fileStorePath);

        return $isWrite && $result
            ? $result
            : [];
    }

    /**
     * @param $storageId
     * @param $filePath
     * @param $prefix
     * @param int $resizeWidth
     * @param int $resizeHeight
     * @param array $options
     * @return mixed
     */
    public function resizeImageThumbnailByStorageId(
        $storageId,
        $filePath,
        $prefix,
        $resizeWidth = 0,
        $resizeHeight = 0,
        $options = []
    ): array
    {
        $adapter = $this->getAdapterByStorageId($storageId);
        $stream = $adapter->readStream($filePath);

        $img = $this->readImageStream($stream);

        $info = pathinfo($filePath);
        $fileStorePath = sprintf('%s/%s-%s', $info['dirname'], $prefix, $info['basename']);

        $img->thumbnail(new \Imagine\Image\Box(
            $resizeWidth,
            $resizeHeight
        ), \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND);

        $tmpFile = tempnam(sys_get_temp_dir(), sprintf('img-%s', time()));
        $img->save($tmpFile);

        if(is_resource($stream)){
            fclose($stream);
        }

        $stream = fopen($tmpFile, 'r+');
        $isWrite = $adapter->writeStream($fileStorePath, $stream);
        if(is_resource($stream)){
            fclose($stream);
        }
        @unlink($tmpFile);
        $result = $adapter->getMetadata($fileStorePath);

        return $isWrite && $result
            ? $result
            : [];
    }







}