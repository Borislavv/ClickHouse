<?php

namespace Borislav\Clickhouse\Exception;

use Throwable;

class ClickHouseInstanceIsUnserializeableException extends ClickHouseException
{
    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message = 'instance cannot be unserialized.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
