<?php

namespace Borislav\Clickhouse\Exception;

use Borislav\Clickhouse\Exception\Interfaces\ClickHouseExceptionInterface;
use Exception;
use ReflectionClass;
use Throwable;

class ClickHouseException extends Exception implements ClickHouseExceptionInterface
{
    /**
     * @param string         $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                '%s: %s',
                (new ReflectionClass($this))->getShortName(),
                $message
            ),
            $code,
            $previous
        );
    }
}
