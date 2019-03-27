<?php namespace kak\storage\adapters;

use League\Flysystem\Adapter\NullAdapter;

class NullFs extends AbstractFs
{
    /**
     * @return NullAdapter
     */
    protected function initAdapter()
    {
        return new NullAdapter();
    }
}