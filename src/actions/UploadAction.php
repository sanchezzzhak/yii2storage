<?php namespace kak\storage\actions;

use kak\storage\adapters\AbstractFs;
use League\Flysystem\Adapter\Local;
use Yii;
use yii\base\Action;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\web\Response;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\base\ErrorException;
use yii\helpers\ArrayHelper;
use kak\storage\Storage;
use kak\storage\models\UploadForm;

/**
 * Class UploadAction
 * @package kak\storage\actions
 */
class UploadAction extends Action
{
    public const IMAGE_RESIZE = 0;
    public const IMAGE_THUMB = 1;

    public $form_name;
    public $form_model;

    public $download_max_size = 5 * 1048576; // 5MB
    public $extension_allowed = [];
    public $image_width_max = 1024;
    public $image_height_max = 768;

    public $resize_image = [
        'preview' => [600, 400, UploadAction::IMAGE_RESIZE],
        'thumbnail' => [120, 120, UploadAction::IMAGE_THUMB]
    ];

    public $defaultImageOptions = [
        'quality' => 100
    ];

    public $result = [];
    public $storageId = 'tmp';
    public $storageClass = 'storage';


    /**
     * @return Storage
     * @throws \yii\base\InvalidConfigException
     */
    public function getStorage(): Storage
    {
        return \Yii::$app->get($this->storageClass);
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param $url
     * @return string|null
     */
    public function getExtension($url)
    {
        if (preg_match('#\.([\w\d]+)(?:\?|$)#is', $url, $matches)) {
            return strtolower($matches[1]);
        }
        return null;
    }


    public function response(): ?array
    {
        return $this->result;
    }


    public function init()
    {
        if (!isset($this->form_model)) {
            $this->form_model = Yii::createObject(['class' => $this->form_name]);
        }
        parent::init();
    }


    protected function handleRemoteUrlUploading()
    {
        $url = Yii::$app->request->post('remote');
        if ((string)$url === '') {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HEADER => false,
            CURLOPT_NOBODY => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        if (curl_exec($ch) !== false) {
            $info = curl_getinfo($ch);
            list($mimeType) = explode(';', $info['content_type'], 2);
            $extList = FileHelper::getExtensionsByMimeType($mimeType);

            if ($mimeType === 'application/octet-stream' && !in_array('application/octet-stream', $this->extension_allowed)) {
                $mimeType = $this->getExtension($url);
            }

            if ($info['http_code'] == 200) {
                $this->result['errors'] = [];

                $extMimeType = count($extList) ? end($extList) : '';
                if (count($this->extension_allowed) && !in_array($mimeType, $this->extension_allowed)) {
                    $this->result['errors']['file'] = Yii::t('app', 'Wrong format of the file extension');
                    return $this->response();
                }

                if ($info['download_content_length'] > $this->download_max_size) {
                    $this->result['errors']['file'] = Yii::t('app', 'Remote file is too large');
                    return $this->response();
                }

                $ext = $this->getExtension($url);
                if ((string)$ext === '') {
                    $ext = $extMimeType;
                }


                $displayName = sprintf('%s.%s', time(), $ext);
                $storage = $this->getStorage();

                $tmpFile = tempnam(sys_get_temp_dir(), sprintf('img-%s', time()));

                if (!count($this->result['errors'])) {
                    $ch = curl_init($url);
                    $fp = fopen($tmpFile, 'w+');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    $result = $storage->save($this->storageId, [
                        'tmp_name' => $tmpFile,
                        'name' => $displayName
                    ]);
                    $result = $this->processPrepareResult($result);

                    if ($result !== []) {
                        $result = array_merge($result, [
                            "name_display" => $displayName,
                            "images" => [],
                        ]);
                        $this->result = $result;
                        $this->processImageWithResult();
                    }
                    return $this->response();
                }

            }
        }

        $this->result['errors']['file'] = Yii::t('app', 'Remote file not exists');

        return $this->response();
    }


    /**
     * @return string|null
     */
    protected function handleUploading()
    {
        $file = UploadedFile::getInstancesByName('file');
        if (!$file) {
            return null;
        }


        if (is_array($file)) {
            if (count($file) > 0) {
                $file = $file[0];
            }
        }

        /** @var UploadForm $model */
        $model = $this->form_model;

        $model->file = $file->name;
        $model->size = $file->size;
        $model->mime_type = $file->type;


        $model->validate();
        $model->validateByExtensionAllowed($this->extension_allowed);

        if ($model->hasErrors()) {
            return null;
        }

        $storage = $this->getStorage();

        $fileSourcePath = '';
        $fileSouceName = '';
        if ($file instanceof UploadedFile) {
            $fileSourcePath = $file->tempName;
            $fileSouceName = $file->name;
        } else if (is_array($file) && array_key_exists('tmp_name', $file)) {
            $fileSourcePath = $file['tmp_name'];
            $fileSouceName = $file['name'];
        }

        $contentRange = \Yii::$app->request->headers->get('content-range');

        $xFileId = \Yii::$app->request->headers->get('x-file-id');
        $xFileChunkSize = \Yii::$app->request->headers->get('x-file-chunk-size');
        $xFileName = \Yii::$app->request->headers->get('x-file-name');

        $key = sprintf('xfile-%s', $xFileId);

        $contentRangePart = $contentRange ? preg_split('/[^0-9]+/', $contentRange) : null;

        $rangeTotalSize = $contentRangePart ? $contentRangePart[3] : null;
        $rangeCurrentSize = $contentRangePart ? $contentRangePart[2] : null;

        $ext = pathinfo($fileSouceName, PATHINFO_EXTENSION);
        /** @var AbstractFs $adapter */
        $adapter = $storage->getAdapterByStorageId($this->storageId);
        $level = $storage->getStorageLevelById($this->storageId);

        $fileStorePath = Yii::$app->session->get($key, null);
        if (!$fileStorePath || !$contentRange) {
            $fileName = $adapter->uniqueFilePath($ext, $level);
            $fileStorePath = sprintf('%s/%s', $this->storageId, $fileName);
        }

        if ($contentRange) {
            Yii::$app->session->set($key, $fileStorePath);
        }

        $stream = fopen($fileSourcePath, 'r+');
        if ($adapter->has($fileStorePath) && $contentRange) {
            $isWrite = $adapter->append($fileStorePath, $stream);
        } else {
            $isWrite = $adapter->writeStream($fileStorePath, $stream);
        }

        if (is_resource($stream)) {
            fclose($stream);
        }

        $isUploadFinish = true;
        if ($contentRange) {
            $isUploadFinish = (ceil($rangeTotalSize / $xFileChunkSize) - ceil($rangeCurrentSize / $xFileChunkSize)) == 0;
        }

        $result = $adapter->getMetadata($fileStorePath);
        if ($result) {
            $result['name_display'] = $xFileName ?? $file->name;
            $result['type'] = $adapter->getMimetype($fileStorePath);
            $result['base_url'] = $adapter->baseUrl;
            $result['path'] = $fileStorePath;
            $this->result = $result;
        }

        if ($isUploadFinish) {
            $this->processImageWithResult();
        }

        $this->result['errors'] = $model->getErrors();

        return $this->response();
    }


    protected function handleCropping()
    {
        $file = UploadedFile::getInstanceByName('cropped');

        if (!$file) {
            return null;
        }

        $replace = Yii::$app->request->post('replace');
        $replace = json_decode(json_decode($replace), true);

        $storageId = ArrayHelper::getValue($replace, 'storage');
        $fileStorePath = ArrayHelper::getValue($replace, 'path');
        $filenameDisplay = ArrayHelper::getValue($replace, 'name_display', $file->name);


        /** @var UploadForm $model */
        $model = $this->form_model;
        $model->file = $filenameDisplay;
        $model->size = $file->size;
        $model->mime_type = $file->type;

        $model->validate();
        $model->validateByExtensionAllowed($this->extension_allowed);

        if (!$model->hasErrors()) {

            $storage = $this->getStorage();
            $adapter = $storage->getAdapterByStorageId($storageId);
            $stream = fopen($file->tempName, 'r+');
            if ($adapter->has($fileStorePath)) {
                $isWrite = $adapter->updateStream($fileStorePath, $stream);
            } else {
                $isWrite = $adapter->writeStream($fileStorePath, $stream);
            }
            fclose($stream);

            $result = $adapter->getMetadata($fileStorePath);

            if ($isWrite && $result) {
                $result['type'] = $adapter->getMimetype($fileStorePath);
                $result['base_url'] = $adapter->baseUrl;
                $result['path'] = $fileStorePath;

                $result = array_merge($result, [
                    "name_display" => $filenameDisplay,
                    "images" => [],
                ]);
                $result = $this->processPrepareResult($result);
                $this->result = $result;
                $this->processImageWithResult();
            }

        }
        $this->result['errors'] = $model->getErrors();
        return $this->response();
    }

    /**
     * @param $path_file
     * @param $model
     */
    private function processImageWithResult()
    {
        $storage = $this->getStorage();

        $adapter = $storage->getAdapterByStorageId($this->storageId);
        $filePath = $this->result['path'];

        $storage->optimizationImageByStorageId(
            $this->storageId,
            $filePath
        );

        foreach ($this->resize_image as $prefix => $param) {
            list($image_width, $image_height) = $param;
            $type = isset($param[2]) ? $param[2] : UploadAction::IMAGE_RESIZE;

            $options = ArrayHelper::getValue($param, 'options', []);
            $options = array_merge($this->defaultImageOptions, $options);

            switch ($type) {
                case UploadAction::IMAGE_RESIZE:
                    $result = $storage->resizeImagePreviewByStorageId(
                        $this->storageId,
                        $filePath,
                        $prefix,
                        $image_width,
                        $image_height,
                        $options
                    );
                    break;
                case UploadAction::IMAGE_THUMB:
                    $result = $storage->resizeImageThumbnailByStorageId(
                        $this->storageId,
                        $filePath,
                        $prefix,
                        $image_width,
                        $image_height,
                        $options
                    );
                    break;
            }

            if ($result !== []) {
                $result = $this->processPrepareResult($result);
                $this->result['images'][$prefix] = $result;
            }
        }
    }

    /**
     * @param array $result
     * @return array
     */
    private function processPrepareResult(array $result): array
    {
        $result["storage"] = $this->storageId;
        return $result;
    }

    /**
     * @return string
     */
    public function run()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $action = \Yii::$app->request->get('act');
        switch ($action) {
            case 'crop':
                return $this->handleCropping();
            case 'remote-upload':
                return $this->handleRemoteUrlUploading();
        }

        return $this->handleUploading();
    }

}