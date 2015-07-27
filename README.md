Storage and Upload file for Yii2
============
file upload/resize

Any contributions are welcome
Preview
-----------
<img src="https://lh3.googleusercontent.com/--sDmh3Ca8UA/VbXsQf_UxoI/AAAAAAAAADo/STR3DrTrdDU/s477-Ic42/PreviewUpload.png">

<a href="https://picasaweb.google.com/104329650875154427869/KakGithub#6176102228563362898" target="_blank">Crop Preview (large image)<a>

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run
```
php composer.phar require --prefer-dist kak/storage "*"
```

or add

```
"kak/storage": "*"
```

to the require section of your `composer.json` file and run command `composer update`


Usage
-----
PLS set config params Yii::$app->params


```php
/** @docs https://console.aws.amazon.com/iam/home Generation access key and secret */
$amazon_config = [
    'key' => '', 
    'secret' => '',
    'bucket' => 'my',
    'level'  => 2,
    'type'   => 'amazon',
    'region' => 'us-east-1',
]

//...
'storage' => [
    'storages' => [
         // use amazon config
        'photo'  => $amazon_config,
        'custom_name' => [],
        // local server save files
        'tmp'  => [       
            'level' => 0,
        ],
    ],
],

```
Once the extension is installed, simply use it in your code by:

```php
/** @var kak\storage\models\UploadForm $model */
<?= \kak\storage\Upload::widget([
    'model' => $model
]); ?>
```

Example use controller this uploading 
Custom run action uploading
```php
    $action = new \kak\storage\actions\UploadAction($this->id, $this, [
        'form_name' => 'kak\storage\models\UploadForm',
        'storage'  => 'tmp'
        'resize_image' => [
            'preview'   => [600,400, UploadAction::IMAGE_RESIZE],
            'thumbnail' => [120,120, UploadAction::IMAGE_THUMB],
            '320' => [320,280],  // add custom new size 320x280
        ]        
    ]);
    return $action->run();
```

Use my project this controller
```php

$storage_tmp = new Storage('tmp');      // local tmp dir
$storage_photo = new Storage('photo');  // amazon

// ajax upload result
$result = [];
$arr_meta =  Yii::$app->request->post('meta',[]);
// native upload old history mobile
if(!Yii::$app->request->isAjax)
{
   $action = new \kak\storage\actions\UploadAction($this->id, $this, [
        'form_name' => 'kak\storage\models\UploadForm',
        'storage'  => 'tmp',
        'extension_allowed' => \kak\storage\actions\UploadAction::$EXTENSION_IMAGE
    ]);
   $data = $action->run();
    
    if(!empty($data['name']))
    {
        $arr_meta[] = Json::encode($data);
    }
}
// download server file
if($url = Yii::$app->request->post('url'))
{
    $action = new \kak\storage\actions\HttpUploadAction($this->id, $this, [
        'storage'  => 'tmp',
        'url' => $url,
        'extension_allowed' => \kak\storage\actions\HttpUploadAction::$EXTENSION_IMAGE
    ]);
    $arr_meta[] = $action->run();
}
 $adapter_tmp = $storage_tmp->getAdapter();

    // SAVE STORAGE Photo
foreach($arr_meta as $meta)
{
    $data = Json::decode($meta);
    $file_source = $adapter_tmp->getAbsolutePath($data['name']);

    $photo_path =  $storage_photo->save($file_source,[]);

    $images = [
        'original' => $storage_photo->getAdapter()->getUrl($photo_path)
    ];

    foreach($data['images'] as $prefix => $image)
    {
        $normalize_source = $adapter_tmp->getAbsolutePath($image['url']);
        $info_normalize_source  = pathinfo($normalize_source);
        $image_name = pathinfo($image['url'], PATHINFO_BASENAME);
        $info_image = pathinfo($photo_path);

        $images[$prefix] = $storage_photo->getAdapter()->getUrl( $storage_photo->save($info_normalize_source['dirname'] . '/'. $image_name  ,[
            'key' =>  $info_image['dirname'] . '/'. $image_name
        ]));

    }

    $photo = new Photo;
    $photo->user_id     = Yii::$app->user->id;
    $photo->name        = Yii::$app->request->post('name');
    $photo->description = Yii::$app->request->post('description');
    $photo->album_id    = (int)Yii::$app->request->post('album',0);
    $photo->path        = JSON::encode($images);
    $photo->adults      = (int)(Yii::$app->request->post('adults',false));
    if( $photo->save())
        $result[] = $photo->id;

}
```