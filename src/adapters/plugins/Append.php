<?php namespace kak\storage\adapters\plugins;

use League\Flysystem\Plugin\AbstractPlugin;
use League\Flysystem\Adapter\NullAdapter;
use League\Flysystem\FileNotFoundException;
use kak\storage\adapters\ext\AppendStream;

/**
 * Class Append
 * @package kak\storage\adapters\plugins
 */
final class Append extends AbstractPlugin
{
    /**
     * Get the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'append';
    }

    /**
     * @param $target
     * @param $content
     * @return bool
     * @throws FileNotFoundException
     * @throws \League\Flysystem\FileExistsException
     *
     * ```php
     *
     *  $filesystem->addPlugin(new Append);
     *  $this->filesystem->write('file.txt', 'first_data');
     *  $this->filesystem->append('file.txt', '|append_data');
     *
     * ```
     */
    public function handle($target, $content)
    {
        if ($this->filesystem->getAdapter() instanceof NullAdapter) {
            return false;
        }
        if (!$this->filesystem->has($target)) {
            throw new FileNotFoundException($target);
        }

        $backupPath = sprintf('%s.backup', $target);

        $this->filesystem->rename($target, $backupPath);
        $contentToAppend = is_resource($content)
            ? $content
            : fopen(sprintf('data://text/plain,%s', $content), 'r');

        $stream = (new AppendStream([
            $this->filesystem->readStream($backupPath),
            $contentToAppend,
        ]))->getResource();

        if ($this->filesystem->writeStream($target, $stream)) {
            $this->filesystem->delete($backupPath);
            return true;
        }

        $this->filesystem->rename($backupPath, $target);
        return false;
    }
}