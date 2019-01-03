<?php

declare(strict_types=1);

namespace SeasStash\Clickhouse\Exception;

use LogicException;

/**
 * Class TransportException
 * @package Clickhouse\Exception
 */
final class TransportException extends LogicException implements ClickHouseException
{
}
