UploadAdvanced
-

Если нужна языквая подержка, подключите bootstap

```php
'bootstrap' => [
    kak\storage\Bootstrap::class,
],
```

Код для view
```php
<?= UploadAdvanced::widget([
    'id' => 'widget-upload',
    'url' => ['ajax-upload'],
    'pluginDeviceUploadOptions' => [
        'autoUpload' => true,
        'singleFileUploads' => true,
    ]
]); ?>
```

Код для контроллера

```php

/***
 * @return string json
 */
public function actionAjaxUpload()
{
    $action = new UploadAction($this->id, $this, [
        'form_name' => UploadForm::class,
        'storageId' => 'tmp',
        // is image then resize file
        'resizeImage' => [
            'preview' => [600, 600, UploadAction::IMAGE_RESIZE],
            'thumbnail' => [120, 120, UploadAction::IMAGE_THUMB]
        ],
    ]);
    return $action->run();
}
```

Pjax
В главном шаблоне необходимо подключить asset `kak\storage\bundles\StorageAsset::register($this)`Добавить код на перезагрузку js кода. 

```js
$(document).on('pjax:end', function(e){
    jQuery('#widget-upload').kakStorageAdvancedUpload({});
});
```














<!--
Configurate social plugins 
-
composer require kak/authclient
-->