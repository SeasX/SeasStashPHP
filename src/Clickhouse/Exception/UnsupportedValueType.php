<?php

declare(strict_types=1);

namespace SeasStash\Clickhouse\Exception;

use InvalidArgumentException;
use function gettype;
use function sprintf;

/**
 * Class UnsupportedValueType
 * @package Clickhouse\Exception
 */
final class UnsupportedValueType extends InvalidArgumentException implements ClickHouseException
{
    /**
     * @param mixed $parameter
     */
    public static function new($parameter) : self
    {
        return new self(sprintf('Parameter of type "%s" cannot be bound', gettype($parameter)));
    }
}
