Storage and Upload file widget

PLS!
This is non-stable alpha version. It's not recommended to go in production with it for now.
-------------------------------------------------------------------------------------------
Any contributions are welcome
-----------------------------

The plans:
----------
1 Widjet Upload preview images analog XUpload Yii.1
2 Widjet Select dealog files (`DropBox` `YaDisk` `GDrive` or popular services)
3 Storage files to directory categories ( `images`, `baners`, `videos`.. or `custom derictory` )
4 Store to Amason Server
5 Store upload to remote server
============
file upload/resize/crop file move storage

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yii2/storage "*"
```

or add

```
"yii2/storage": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
<?= \yii2\storage\Upload::widget([
    'model' => $model
    'name'  => 'attr_file'
]); ?>```
