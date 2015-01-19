<?php
/**
 * Created by PhpStorm.
 * User: PHPdev
 * Date: 24.12.2014
 * Time: 15:29
 */

namespace kak\storage\actions;
use kak\storage\Storage;
use Yii;
use yii\helpers\FileHelper;
use yii\helpers\Json;

/**
 * Class HttpUploadAction
 * @package kak\storage\actions
 *
 *
 * ```php
 *
    if($url = Yii::$app->request->post('url'))
    {
        $action = new \kak\storage\actions\HttpUploadAction($this->id, $this, [
            'storage'  => 'tmp',
            'url' => $url,
            'extension_allowed' => \kak\storage\actions\HttpUploadAction::$EXTENSION_IMAGE
        ]);
        $meta[] = $action->run();
    }
 *
 *
 *
 * ````
 *
 *
 */
class HttpUploadAction  extends BaseUploadAction
{
    public $url;
    public $download_max_size;

    public function init()
    {
        parent::init();

        if(empty($this->download_max_size))
            $this->download_max_size = 5*1024*1024;
    }

    public function run()
    {
        $url = $this->url;

        $ch = curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_HEADER => false,
            CURLOPT_NOBODY => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        if(curl_exec($ch)!==false)
        {
            $info = curl_getinfo($ch);
            list($mime_type) =  explode(';',  $info['content_type'], 2);
            $ext_list = FileHelper::getExtensionsByMimeType($mime_type);

            if($info['http_code'] == 200)
            {
                $this->_result['errors'] = [];

                $ext_mime_type = count($ext_list) ? end($ext_list):'';

                if(count($this->extension_allowed) && !in_array($mime_type,$this->extension_allowed))
                {
                    $this->_result['errors']['file'] = Yii::t('app','Wrong format of the file extension');
                    return  $this->response();
                }

                if($info['download_content_length'] >  $this->download_max_size )
                {
                    $this->_result['errors']['file'] = Yii::t('app','Remote file is too large');
                    return  $this->response();
                }

                $ext = $this->getExtension($url);
                if($ext === null)
                {
                    $ext = $ext_mime_type;
                }

                $storage = new Storage($this->storage);
                $save_url = $storage->getAdapter()->getAbsolutePath($storage->getAdapter()->uniqueFilePath($ext));

                if(!count($this->_result['errors']))
                {
                    $ch = curl_init($url);
                    $fp = fopen($save_url, 'w+');
                    curl_setopt($ch, CURLOPT_FILE, $fp);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_HEADER, 0);
                    curl_exec($ch);
                    curl_close($ch);
                    fclose($fp);

                    $rel_path = $storage->getAdapter()->getUrl($save_url);

                    $this->_result = [
                        "name"         => $rel_path,
                        "name_display" => null,
                        "type"         => null,
                        "size"         => $info['download_content_length'],
                        "url"          => $rel_path,
                        "images"       => [],
                    ];
                    $this->_image( $rel_path);
                    return  $this->response();
                }

            }
        }
        pre(curl_getinfo($ch, CURLINFO_CONTENT_TYPE));

        $info = curl_getinfo($ch);

        pre($info);
        exit;


        $this->_result['error']['file'] = Yii::t('app','Remote file not exists');
        return  $this->response();
    }

    private function response()
    {
        return  Json::encode($this->_result);
    }



} 