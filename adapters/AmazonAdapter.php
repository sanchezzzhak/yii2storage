<?php
/**
 * Created by PhpStorm.
 * User: PHPdev
 * Date: 25.12.2014
 * Time: 11:03
 */

namespace kak\storage\adapters;

use Aws\S3\Enum\CannedAcl;
use Aws\S3\S3Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use Yii;


class AmazonAdapter extends BaseAdapter
{
    public $key;
    public $secret;
    public $bucket;
    public $region;

    private $_client;

    /**
     * @param $ext
     * @return string|void
     * @throws Exception
     */
    public function uniqueFilePath($ext = null)
    {
        $filename = $this->generateName(AmazonAdapter::GENERATE_SHA1) . (!empty($ext) ? '.' . $ext: '');

        $filedir  = $this->id;

        for ($i = 0; $i < $this->level; $i++)
        {
            $filedir .= "/" . substr($filename, $i * 2, 2);
        }
        $filepath = $filedir . "/" . $filename;

        if ($this->fileExists($filepath))
        {
            $filepath = $this->uniqueFilePath($ext);
        }
        return $filepath;
    }

    /**
     * @param $source
     * @param array $options is set key overwrite
     * @return \Guzzle\Service\Resource\Model|void
     * @throws Exception
     */
    public function save($source, $options = [])
    {
        $ext = pathinfo($source, PATHINFO_EXTENSION);
        $unique_path = ArrayHelper::remove($options,'key',$this->uniqueFilePath($ext));
        $name = str_replace("/",'/',$unique_path);

        if(!file_exists($source))
        {
           throw new Exception(Yii::t('app','file source not exists'));
        }

        $options = ArrayHelper::merge([
            'Bucket' => $this->bucket,
            'Key' => $name,
            'SourceFile' => $source,
            'ACL' => CannedAcl::PUBLIC_READ
        ], $options);

        /** @docs putObject http://docs.aws.amazon.com/aws-sdk-php/latest/class-Aws.S3.S3Client.html#_putObject */
        $model = $this->getClient()->putObject($options);

        parent::save($source,$options);
        return $name;
    }

	/**
	 * @param $sourceKey
	 * @param $targetKey
	 * @param array $options
	 * @return \Guzzle\Service\Resource\Model|void
	 * @throws Exception
	 */
	public function copy($sourceKey, $targetKey, $options = [])
	{
		$options = ArrayHelper::merge([
			'Bucket'     => $this->bucket,
			'Key'        => $targetKey,
			'CopySource' => "{$this->bucket}/{$sourceKey}",
			'ACL' => CannedAcl::PUBLIC_READ
		], $options);
		$model = $this->getClient()->copyObject($options);
		return $targetKey;
	}

    /**
     * http://{MyBucketName}.s3.amazonaws.com/e73de643-4450-4b01-87c5-11c429d00209
     * @param string $url
     * @return string|null
     */
    public function getBucketByUrl($url)
    {
        if(preg_match('@^(?:(?:http|https)://)?([^/]+)@i',$url,$matches))
        {
            $host = $matches[1];
            if( preg_match('@^(.*)\.s3\.amazonaws\.com@i', $host, $matches))
                return $matches[1];
        }
        return null;
    }



    /**
     * Removes a file
     * @param string $name the name of the file to remove
     * @return boolean
     */
    public function delete($name)
    {
        $result = $this->getClient()->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $name
        ]);
        parent::delete($name);
        return $result['DeleteMarker'];
    }

    /**
     * Checks whether a file exists or not. This method only works for public resources, private resources will throw
     * a 403 error exception.
     * @param string $name the name of the file
     * @return boolean
     */
    public function fileExists($name)
    {
        $http = new \Guzzle\Http\Client();
        try {
            $response = $http->get($this->getUrl($name))->send();
        } catch(ClientErrorResponseException $e) {
            return false;
        }
        return $response->isSuccessful();
    }

    /**
     * Returns the url of the file or empty string if the file does not exists.
     * @param string $name the key name of the file to access
     * @param mixed $expires The time at which the URL should expire
     * @return string
     */
    public function getUrl($name, $expires = NULL)
    {
        return $this->getClient()->getObjectUrl($this->bucket, $name, $expires);
    }


    /**
     * Returns a S3Client instance
     * @return \Aws\S3\S3Client
     */
    public function getClient()
    {
        if ($this->_client === null)
        {
            $this->_client = S3Client::factory([
                'key' => $this->key,
                'secret' => $this->secret,
                'region' => $this->region
            ]);
        }
        return $this->_client;
    }



} 