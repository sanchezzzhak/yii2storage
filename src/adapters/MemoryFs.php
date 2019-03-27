<?php namespace kak\storage\adapters;

use League\Flysystem\Memory\MemoryAdapter;

class MemoryFs extends AbstractFs
{
    /**
     * @return MemoryAdapter
     */
    protected function initAdapter()
    {
        return new MemoryAdapter();
    }
}