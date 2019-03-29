<?php namespace kak\storage;

use Yii;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\log\Logger;
use yii\web\UploadedFile;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
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

        if($isWrite && $result){
            $result['type'] = $adapter->getMimetype($fileStorePath);
            $result['base_url'] = $adapter->baseUrl;
            $result['path'] = $fileStorePath;
            return $result;
        }
        return [];
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
//        UPLOAD_ERR_NO_FILE
        $ext = pathinfo($fileSouceName, PATHINFO_EXTENSION);
        $stream = fopen($fileSourcePath, 'r+');
        $result = $this->saveStream($storageId, $stream, $ext, $options);
        fclose($stream);
        return $result;
    }


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
    private function autoFixImageOrentation(ImageInterface $img): void
    {
        $metadata = $img->metadata();
        $data = $metadata->toArray();

        $orientation = 0;
        if(array_key_exists('exif.Orientation', $data)){
            $orientation = $data['exif.Orientation'];
        }else if(array_key_exists('ifd0.Orientation', $data)) {
            $orientation = $data['ifd0.Orientation'];
        }

        $size = $img->getSize();
        if ($size->getWidth() > $size->getHeight()) {
            // Landscape
            switch ($orientation) {
                case 2:
                    $img->flipHorizontally();
                    break;
                case 3:
                    $img->rotate(180);
                    break;
                case 4:
                    $img->rotate(180)->flipHorizontally();
                    break;
                case 5:
                    $img->rotate(90)->flipHorizontally();
                    break;
                case 6:
                    $img->rotate(90);
                    break;
                case 7:
                    $img->rotate(-90)->flipHorizontally();
                    break;
                case 8:
                    $img->rotate(-90);
                    break;
            }
        } else {
            // Portrait or Square
            switch ($orientation) {
                case 2:
                    $img->flipHorizontally();;
                    break;
                case 3:
                    $img->flipVertically()->flipHorizontally();;
                    break;
                case 4:
                    $img->flipVertically();
                    break;
                case 5:
                    $img->rotate(90)->flipHorizontally();
                    break;
                case 6:
                    $img->rotate(90);
                    break;
                case 7:
                    $img->rotate(-90)->flipHorizontally();
                    break;
                case 8:
                    $img->rotate(-90);
                    break;
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

    public function getResizeImageFormatByMimeType($mimeType)
    {
        $followingExtensions = ["bmp", "gif", "jpeg", "png", "wbmp", "webp", "xbm"];
        $listExtensions  = FileHelper::getExtensionsByMimeType($mimeType);
        foreach ($followingExtensions as $extension){
            if(in_array($extension, $listExtensions)){
                return $extension;
            }
        }
        return null;
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
        $mimeType = $adapter->getMimetype($filePath);

        $resizeFormat = $this->getResizeImageFormatByMimeType($mimeType);
        if((string)$resizeFormat === '') {
            return [];
        }

        $stream = $adapter->readStream($filePath);
        $img = $this->readImageStream($stream);
        $size = $img->getSize();

        $info = pathinfo($filePath);

        if ($prefix !== '') {
            $fileStorePath = sprintf('%s/%s-%s', $info['dirname'], $prefix, $info['basename']);
        } else {
            $fileStorePath = sprintf('%s/%s', $info['dirname'], $info['basename']);
        }

        $width = $size->getWidth();
        $height = $size->getHeight();

        if ($size->getWidth() >= $size->getHeight() && $width > $resizeWidth) {
            $width = $resizeWidth;
            $height = $resizeWidth * $size->getHeight() / $size->getWidth();

        } else if ($size->getWidth() <= $size->getHeight() && $height > $resizeHeight) {
            $width = $resizeHeight * $size->getWidth() / $size->getHeight();
            $height = $resizeHeight;
        }

        $img->resize(new Box($width, $height));

        $tmpFile = tempnam(sys_get_temp_dir(), sprintf('img-%s', time()));
        $img->save($tmpFile, [
           'format' => $resizeFormat
        ]);

        if(is_resource($stream)){
            fclose($stream);
        }

        $stream = fopen($tmpFile, 'r+');
        if ($adapter->has($fileStorePath)) {
            $isWrite = $adapter->updateStream($fileStorePath, $stream);
        } else {
            $isWrite = $adapter->writeStream($fileStorePath, $stream);
        }
        if(is_resource($stream)){
            fclose($stream);
        }

        @unlink($tmpFile);
        $result = $adapter->getMetadata($fileStorePath);

        if($isWrite && $result){
            $result['type'] = $adapter->getMimetype($fileStorePath);
            $result['base_url'] = $adapter->baseUrl;
            $result['path'] = $fileStorePath;
            return $result;
        }
        return [];
    }

    /**
     * @param $storageId
     * @param $filePath
     * @throws InvalidConfigException
     * @throws \ImagickException
     */
    public function optimizationImageByStorageId($storageId, $filePath)
    {
        $adapter = $this->getAdapterByStorageId($storageId);

        $mimeType = $adapter->getMimetype($filePath);
        $resizeFormat = $this->getResizeImageFormatByMimeType($mimeType);
        if ((string)$resizeFormat === '') {
            return;
        }
        $stream = $adapter->readStream($filePath);
        $img = $this->readImageStream($stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $info = pathinfo($filePath);
        $fileStorePath = sprintf('%s/%s', $info['dirname'], $info['basename']);

        $tmpFile = tempnam(sys_get_temp_dir(), sprintf('img-%s', time()));
        $img->save($tmpFile, [
            'format' => $resizeFormat
        ]);
        $stream = fopen($tmpFile, 'r+');
        if ($adapter->has($fileStorePath)) {
            $isWrite = $adapter->updateStream($fileStorePath, $stream);
        } else {
            $isWrite = $adapter->writeStream($fileStorePath, $stream);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
        @unlink($tmpFile);

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

        $mimeType = $adapter->getMimetype($filePath);
        $resizeFormat = $this->getResizeImageFormatByMimeType($mimeType);
        if ((string)$resizeFormat === '') {
            return [];
        }

        $stream = $adapter->readStream($filePath);
        $img = $this->readImageStream($stream);

        $info = pathinfo($filePath);
        $fileStorePath = sprintf('%s/%s-%s', $info['dirname'], $prefix, $info['basename']);

        $tmpFile = tempnam(sys_get_temp_dir(), sprintf('img-%s', time()));

        $img->thumbnail(
            new Box($resizeWidth, $resizeHeight),
            ImageInterface::THUMBNAIL_OUTBOUND
        )->save($tmpFile, [
            'format' => $resizeFormat
        ]);

        if (is_resource($stream)) {
            fclose($stream);
        }

        $stream = fopen($tmpFile, 'r+');
        if ($adapter->has($fileStorePath)) {
            $isWrite = $adapter->updateStream($fileStorePath, $stream);
        } else {
            $isWrite = $adapter->writeStream($fileStorePath, $stream);
        }
        if (is_resource($stream)) {
            fclose($stream);
        }
        @unlink($tmpFile);
        $result = $adapter->getMetadata($fileStorePath);

        if ($isWrite && $result) {
            $result['type'] = $adapter->getMimetype($fileStorePath);
            $result['base_url'] = $adapter->baseUrl;
            $result['path'] = $fileStorePath;
            return $result;
        }
        return [];
    }

}