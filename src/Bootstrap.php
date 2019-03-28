<?php namespace kak\storage;

use yii\base\BootstrapInterface;
/**
 * Class Bootstrap
 * @package kak\storage
 */
class Bootstrap implements BootstrapInterface
{
    /** @inheritdoc */
    public function bootstrap($app)
    {
        // register translations
        if (!isset($app->get('i18n')->translations['storage*'])) {
            $app->get('i18n')->translations['storage*'] = [
                'class'    => 'yii\i18n\PhpMessageSource',
                'basePath' => __DIR__ . '/messages',
                'sourceLanguage' => 'en-US',
            ];
        }
    }

}