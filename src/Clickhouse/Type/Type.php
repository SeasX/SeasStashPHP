<?php

declare(strict_types=1);

namespace SeasStash\Clickhouse\Type;
/**
 * Interface Type
 * @package Clickhouse\Type
 */
interface Type
{
    /**
     * @return mixed
     */
    public function getValue();
}
