<?php namespace kak\storage\adapters\ext;


/**
 * Class AppendStream
 */
class AppendStream
{
    /**
     * @var resource[]
     */
    private $streams = [];
    /**
     * @var int
     */
    private $chunkSize;

    public function __construct(iterable $streams = [], int $chunkSize = 8192)
    {
        foreach ($streams as $stream) {
            $this->append($stream);
        }
        $this->chunkSize = $chunkSize;
    }

    /**
     * @param resource $stream
     */
    public function append($stream): void
    {
        if (!is_resource($stream)) {
            throw new InvalidStreamException($stream);
        }
        if (get_resource_type($stream) !== 'stream') {
            throw new InvalidStreamException($stream);
        }

        $this->streams[] = $stream;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        if (!$this->streams) {
            return fopen('data://text/plain,', 'r');
        }
        if (count($this->streams) == 1) {
            return reset($this->streams);
        }
        $head = tmpfile();
        fwrite($head, fread($this->streams[0], 8192));
        rewind($head);

        $anonymous = new class($this->streams, $this->chunkSize) extends \php_user_filter
        {
            private static $streams = [];
            private static $maxLength;

            public function __construct(array $streams = [], int $maxLength = 8192)
            {
                self::$streams = $streams;
                self::$maxLength = $maxLength;
            }

            /**
             *
             * @param resource $in Incoming bucket brigade
             * @param resource $out Outgoing bucket brigade
             * @param int $consumed Number of bytes consumed
             * @param bool $closing Last bucket brigade in stream?
             */
            public function filter($in, $out, &$consumed, $closing)
            {
                while ($bucket = stream_bucket_make_writeable($in)) {
                    stream_bucket_append($out, $bucket);
                }
                foreach (self::$streams as $stream) {
                    while (feof($stream) !== true) {
                        $bucket = stream_bucket_new($stream, fread($stream, self::$maxLength));
                        stream_bucket_append($out, $bucket);
                    }
                }
                return PSFS_PASS_ON;
            }

        };

        stream_filter_register($filter = bin2hex(random_bytes(32)), get_class($anonymous));
        stream_filter_append($head, $filter);
        return $head;
    }
}