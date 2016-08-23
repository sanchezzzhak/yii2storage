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

Example use controller this uploading

```php
    public function actions()
    {
         return [
             'upload' => [
                 'class' => UploadAction::className(),
                 'form_name' => 'kak\storage\models\UploadForm',
                 'storage'  => 'tmp',   // save image default tmp storage
                 'resize_image' => [    // list formats
                     'preview'   => [1024,1024, UploadAction::IMAGE_RESIZE, 'options' => [] ],
                     'thumbnail' => [120,120, UploadAction::IMAGE_THUMB],
                     '350'       => [350,280, UploadAction::IMAGE_RESIZE],
                 ]
             ],
         ];
     }
```

resize_image.options 
- animate if true then save image gif animated else first moveclip
- quality 10 - 100

Save model then controller
```php

    /**
    * @param $id int edit post
    * @return string
    */         
    public function actionUpdate($id)
    {
        $model = $this->findPostById($id);
        $uploadForm = new \kak\storage\models\UploadForm(['meta_name' => 'image_base']);
        $uploadForm->meta = $postModel->images_json;

        if($this->savePostForm($model, $uploadForm)) {
            return $this->redirect(['/dashboard/post/update','id' => $postModel->id]);
        }
        return $this->render('form',compact(
            'model', 'uploadForm'
        ));
    }

    /**
     * @param $model Post
     * @param $uploadForm \kak\storage\models\UploadForm
     * @return bool
     */
    protected function savePostForm(&$model,&$uploadForm)
    {
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {

            $result = $uploadForm->saveToStorage('tmp','images',[]);
            $model->images_json = Json::encode($result);
            
            return $model->save();
        }
        
        return false;
    }
```

Once the extension is installed, simply use it in your code by:

```php
<div>
    <?=\kak\storage\Upload::widget([
        'model' => $uploadFormModel,
        'url' => ['/dashboard/default/upload', 'resize_type' => 'thumbnail,350']    
    ]); ?>
</div>
<hr>
```
if arg name resize_type in GET only these types will be saved resize images

usage pjax
==========

register assets the main layouts  
```php
StorageAsset::register($view);
```
pjax event add code
```js
    $(document).on('pjax:end','.pjax-wrapper',function(e){
        //init old gui
        $('.kak-storage-upload').kakStorageUpload({});
    });
```

        
        

