Storage and Upload file widget

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