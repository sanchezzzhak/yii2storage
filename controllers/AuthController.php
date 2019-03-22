<?php namespace kak\storage\controllers;
use yii\authclient\BaseOAuth;
use yii\web\Controller;

class AuthController extends Controller
{
    public function actions()
    {
        return [
            'token' => [
                'class' => 'yii\authclient\AuthAction',
                'successCallback' => [$this, 'onStorageAccessToken'],
            ],
        ];
    }

    /**
     * @param BaseOAuth $client
     */
   protected function onStorageAccessToken($client)
   {
       $attributes = $client->getUserAttributes();

       Yii::$app->session->set(
           sprinf('auth_%s', $client->getId() ),[
           'client' => $clientId,
           'attributes' => $attributes,
           'token' => ''
       ]);


       $text = <<<HTML_BLOCK
       <script>alert('CLose this window'); window.close();<script>
           
HTML_BLOCK;
       return $text;



   }



}