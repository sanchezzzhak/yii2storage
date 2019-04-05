<?php namespace kak\storage\adapters\exceptions;

use Throwable;

class InvalidStreamException extends \Exception
{
    /**
     * InvalidStreamException constructor.
     * @param $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct($message = null, int $code = 0, Throwable $previous = null)
    {
        $result[] = sprintf('Invalid stream resource given: %s' , gettype($message));

        if (is_resource($message)) {
            $message[] = get_resource_type($message);
        } elseif (is_object($message)) {
            $message[] = get_class($message);
        }else {
            $result[] = $message;
        }
        parent::__construct(implode(' ', $result), $code, $previous);
    }

}


