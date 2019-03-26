<?php
namespace kak\storage\adapters;

/**
 * Interface AdapterInterface
 * @package kak\storage\adapters
 */
interface AdapterInterface
{
    public function uniqueFilePath($ext = null);

    public function getAbsolutePath($name);

    public function fileExists($name);

    public function getUrl($name, $options = []);

    public function delete($name);

    public function rename($sourceKey, $targetKey);

    public function save($name, $options = []);

    public function copy($sourceKey, $targetKey, $options = []);
} 