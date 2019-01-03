<?php

namespace SeasStash\Clickhouse\Quote;

/**
 * @deprecated Left for compatibility
 */
class CSV
{
    /**
     * @param $row
     * @return string
     */
    public static function quoteRow(array $row): string
    {
        return FormatLine::CSV($row);
    }
}
