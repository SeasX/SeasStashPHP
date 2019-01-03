<?php

declare(strict_types=1);

namespace SeasStash\Clickhouse\Exception;

use LogicException;

/**
 * Class QueryException
 * @package Clickhouse\Exception
 */
class QueryException extends LogicException implements ClickHouseException
{
}
