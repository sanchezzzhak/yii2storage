<?php

namespace kak\storage\models;
use kak\storage\actions\UploadAction;
use kak\storage\Storage;
use \yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class UploadForm extends Model
{
    public $file;
    public $mime_type;
    public $size;
    public $name;
    public $filename;

    public $meta = '';  // import


    private $_newValue = false;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            ['file', 'file'],
            ['meta','string']
        ];
    }

    /**
     * Declares attribute labels.
     */
    public function attributeLabels()
    {
        return [
            'file' => 'Upload file',
        ];
    }

    /**
     * @param $storageIn
     * @param $storageTo
     * @param array $options
     * @return array
     */
    public function saveToStorage($storageIn,$storageTo, $options = [])
    {
        $storageSource = new Storage($storageIn);
        $storageRemote = new Storage($storageTo);
        $adapterTmp = $storageSource->getAdapter();
        $arrMeta =  \Yii::$app->request->post('meta',[]);
        $result = [];
        if(count($arrMeta)) {
            foreach($arrMeta as $meta) {

                $data = Json::decode($meta);

                $url = ArrayHelper::getValue($data,'url',false);
                $storageId = ArrayHelper::getValue($data,'storage');

                if($url && ($storageId && $storageId!=$storageTo) )
                {
                    $fileSource   =  $adapterTmp->getAbsolutePath($url);
                    $photoPath    =  $storageRemote->save($fileSource,$options);

                    $resultItem['path']         = $storageRemote->getAdapter()->getUrl($photoPath);
                    $resultItem['storage']      = $storageTo;
                    $resultItem['name_display'] = ArrayHelper::getValue($data,'name_display',null);
                    $resultItem['size']         = ArrayHelper::getValue($data,'size',0);
                    $resultItem['type']         = ArrayHelper::getValue($data,'type','null');

                    $images = [];
                    foreach($data['images'] as $prefix => $image) {
                        $normalizeSource = $adapterTmp->getAbsolutePath($image['url']);
                        $imageName = pathinfo($image['url'], PATHINFO_BASENAME);

                        $url = $storageRemote->getAdapter()->getUrl(
                        $storageRemote->save( pathinfo($normalizeSource,PATHINFO_DIRNAME) . '/'. $imageName  ,[
                            'key' =>  pathinfo($photoPath,PATHINFO_DIRNAME) . '/'. $imageName
                        ]));
                        $images[$prefix] = [ 'url' => $url, 'storage' => $storageTo ];
                    }
                    $resultItem['images'] = $images;
                    $result[] = $resultItem;

                    $this->_newValue = true;
                }
            }
        }
        return $result;
    }

    public function hasNewValue()
    {
        return $this->_newValue;
    }


}