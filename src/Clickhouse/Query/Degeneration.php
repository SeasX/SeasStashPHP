<?php

namespace SeasStash\Clickhouse\Query;
/**
 * Interface Degeneration
 * @package Clickhouse\Query
 */
interface Degeneration
{
    /**
     * @param string $sql
     * @return mixed
     */
    public function process(string $sql);

    /**
     * @param array $bindings
     */
    public function bindParams(array $bindings): void;
}