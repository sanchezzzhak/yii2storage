<?php namespace kak\storage\actions;

use kak\storage\models\UploadForm;

use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Json;
use yii\web\UploadedFile;
use yii\web\HttpException;
use \kak\storage\Storage;

/**
 * Class UploadAction
 * @package kak\storage\actions
 */
class UploadAction extends BaseUploadAction
{

    public $form_name;
    public $form_model;
    public $header = false;

    public $random_name = false;

    public $download_max_size = 5 * 1048576; // 5MB


    public function init()
    {
        if (!isset($this->form_model)) {
            $this->form_model = Yii::createObject(['class' => $this->form_name]);
        }
        parent::init();

    }

    /**
     * Send header
     */
    protected function sendHeaders(): void
    {
        header('Vary: Accept');
        if (Yii::$app->request->isAjax && isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
            return;
        }
        header('Content-type: text/plain');
    }

    protected function saveModel(UploadedFile $file): void
    {
        $model = $this->form_model;

        $model->file = $file->name;
        $model->size = $file->size;
        $model->mime_type = $file->type;

        $extList = [];
        if ($model->validate()) {
            $mimeType = $file->type;
            if ($mimeType == 'application/octet-stream'){
                $mimeType = FileHelper::getMimeType($file->tempName);
            }

            if ($mimeType != 'application/octet-stream'){
                $extList = FileHelper::getExtensionsByMimeType($mimeType);
            }

            if ($mimeType == 'application/octet-stream' && !in_array('application/octet-stream', $this->extension_allowed)){
                $mimeType = $this->getExtension($model->file);
            }

            if (count($this->extension_allowed) && !in_array($mimeType, $this->extension_allowed)) {
                $model->addError('file', 'extension file not allowed');
            }
        }

        if (!count($model->errors)) {
            $extMimeType = count($extList) ? end($extList) : null;

            $ext = $this->getExtension($model->file);
            if ($ext === null) {
                $ext = $extMimeType;
            }

            $storage = $this->getStorage();
            $result = $storage->save($this->storageId, $file);
            $result = $this->prepareResult($result);

            if ($result !== []){

                $result = array_merge($result, [
                    "name_display" => $file->name,
                    "images" => [],
                ]);
                $this->result = $result;

                $this->processImageWithResult();

            }

        }

        $this->result['errors'] = $model->getErrors();
    }

    protected function handleRemoteUrlUploading(): ?string
    {
        $url = Yii::$app->request->post('remote');
        if ((string)$url === '') {
            return null;
        }

     //   $urlInfo = parse_url($url);

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

            if ($mimeType === 'application/octet-stream' && !in_array('application/octet-stream', $this->extension_allowed)){
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


                $displayName = '';
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
                    $result = $this->prepareResult($result);

                    if ($result !== []){
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
    protected function handleUploading(): ?string
    {
        $file = UploadedFile::getInstancesByName('file');
        if (!$file) {
            return null;
        }

        if (is_array($file)) {
            if (count($file) === 1) {
                $file = $file[0];
            }
            $this->saveModel($file);
        }

        return $this->response();
    }


    protected function handleCropping(): ?string
    {
        $file = UploadedFile::getInstanceByName('cropped');
        $replaceFile = Yii::$app->request->post('replace');

        if (!$file) {
            return null;
        }
        $this->saveModel($file);

        return $this->response();
    }


    /**
     * @return string
     */
    public function run(): ?string
    {
        $action = \Yii::$app->request->get('act');

        if ($this->header) {
            $this->sendHeaders();
        }
        switch ($action) {
            case 'crop':
                return $this->handleCropping();
            case 'remote-upload':
                return $this->handleRemoteUrlUploading();
        }

        return $this->handleUploading();
    }

}