<?php namespace kak\storage;

/**
 * Interface StorateInterface
 * @package kak\storage
 */
interface StorateInterface
{
    const TYPE_STORAGE_AMAZON = 'amazon';
    const TYPE_STORAGE_FILE = 'file';
    const TYPE_STORAGE_SCP = 'scp';
}