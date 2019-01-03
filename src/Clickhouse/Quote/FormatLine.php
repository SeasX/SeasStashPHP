<?php

namespace SeasStash\Clickhouse\Quote;

class FormatLine
{
    /**
     *
     * @var array
     */
    private static $strict = [];

    /**
     * Format
     *
     * @param string $format
     * @return StrictQuoteLine
     */
    public static function strictQuote(string $format): StrictQuoteLine
    {
        if (empty(self::$strict[$format])) {
            self::$strict[$format] = new StrictQuoteLine($format);
        }
        return self::$strict[$format];
    }

    /**
     * Array in a string for a query Insert
     *
     * @param mixed[] $row
     * @return string
     */
    public static function Insert(array $row): string
    {
        return self::strictQuote('Insert')->quoteRow($row);
    }

    /**
     * Array to TSV
     *
     * @param array $row
     * @return string
     */
    public static function TSV(array $row): string
    {
        return self::strictQuote('TSV')->quoteRow($row);
    }

    /**
     * Array to CSV
     *
     * @param array $row
     * @return string
     */
    public static function CSV(array $row): string
    {
        return self::strictQuote('CSV')->quoteRow($row);
    }
}
