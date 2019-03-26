<?php namespace kak\storage\controllers;
use http\Client\Response;
use kak\authclient\Instagram;
use yii\authclient\BaseOAuth;
use yii\web\Controller;

class AuthController extends Controller
{
    public $enableCsrfValidation = false;

    public function actions()
    {
        return [
            'token' => [
                'class' => 'yii\authclient\AuthAction',
                'clientIdGetParamName' => 'id',
                'successCallback' => [$this, 'onStorageSuccessAccessToken'],
                'cancelCallback' => [$this, 'onStorageCancellAccessToken'],
            ],
        ];
    }

    /**
     * @return \yii\authclient\Collection|null
     */
    private function getAuthClientCollection()
    {
        $authClientCollection = \Yii::$app->get('authClientCollection', false);
        return $authClientCollection;
    }

    /**
     * @param BaseOAuth $client
     */
    public function onStorageCancellAccessToken($client)
    {
       return false;
    }

    /**
     * @param BaseOAuth $client
     */
   public function onStorageSuccessAccessToken($client)
   {
        return true;
   }


   public function actionCheckAuth($id)
   {
       $authCollection = $this->getAuthClientCollection();
       if(!$authCollection){
           return [
               'error' => 'auth client collection component not installed'
           ];
       }
       /** @var Instagram $client */
       $client = $authCollection->getClient($id);



       $resonse =  $client->apiSearchTag('test');
       var_dump($resonse);
   }



}