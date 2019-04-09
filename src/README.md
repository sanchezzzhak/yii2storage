StorageAPI by Flysystem and UploadWidget for Yii2
============
<small>Any contributions are welcome :)</small>

UploadAdvanced widget preview  
-----------
<img src="https://lh3.googleusercontent.com/--sDmh3Ca8UA/VbXsQf_UxoI/AAAAAAAAADo/STR3DrTrdDU/s477-Ic42/PreviewUpload.png">

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


Tok
-----

* [Widjet Upload](docs/widget-upload.md) docs

* StorageAPI support old flysystem adapters
     - [AwsS3Fs](docs/adapters.md#awss3fs): Interacts with Amazon S3 buckets. 
     - [AzureFs](docs/adapters.md#azurefs): Interacts with Microsoft Azure.
     - [DropboxFs](#dropboxfs): Interacts with Dropbox.
     - [FtpFs](docs/adapters.md#ftpfs): Interacts with an FTP server.
     - [GoogleCloudFs](docs/adapters.md#googlecloudfs): Interacts with Google Cloud Storage. 
     - [GridFSFs](docs/adapters.md#gridfsfs): Interacts with GridFS.
     - [LocalFs](docs/adapters.md#localfs): Interacts with your local filesystem storage.
     - [MemoryFs](docs/adapters.md#memoryfs): Interacts with memory.
     - [NullFs](docs/adapters.md#nullfs): Used for testing.
     - [RackspaceFs](docs/adapters.md#rackspacefs): Interacts with Rackspace.
     - [SftpFs](docs/adapters.md#sftpfscomponent): Interacts with an Sftp server.
     - [WebDAVFs](docs/adapters.md#webdavfscomponent): Interacts with WebDAV.
     - [ZipArchiveFs](docs/adapters.md#ziparchivefs): Interacts with zip archives.

* Storage component configuration 

```php
'storage' => [
    'class' => kak\storage\Storage::class,
    'storages' => [
        'tmp' => [
            'adapter' => 'local',
            'level' => 0,
        ],
        'images' => [
            'adapter' => 'local',
            'level' => 2,
        ],
       'files' => [
            'adapter' => 'local',
            'level' => 2,
       ],
    ],
    'adapters' => [
        'local' => [
            'class' => kak\storage\adapters\LocalFs::class,
            'baseUrl' => 'https://localhost/',
            'path' => '@webroot'
        ],
    ],
],
```


