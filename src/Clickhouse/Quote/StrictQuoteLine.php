<?php

namespace SeasStash\Clickhouse\Quote;

use SeasStash\Clickhouse\Exception\QueryException;
use SeasStash\Clickhouse\Type\NumericType;
use function array_map;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function preg_replace;
use function str_replace;

class StrictQuoteLine
{
    /** @var array */
    private $preset = [
        'CSV' => [
            'EnclosureArray' => '"',
            'EncodeEnclosure' => '"',
            'Enclosure' => '"',
            'Null' => "\\N",
            'Delimiter' => ",",
            'TabEncode' => false,
        ],
        'Insert' => [
            'EnclosureArray' => '',
            'EncodeEnclosure' => '\\',
            'Enclosure' => '\'',
            'Null' => "NULL",
            'Delimiter' => ",",
            'TabEncode' => false,
        ],
        'TSV' => [
            'EnclosureArray' => '',
            'EncodeEnclosure' => '',
            'Enclosure' => '\\',
            'Null' => " ",
            'Delimiter' => "\t",
            'TabEncode' => true,
        ],
    ];
    /** @var array|mixed */
    private $settings = [];

    /**
     * StrictQuoteLine constructor.
     * @param string $format
     */
    public function __construct(string $format)
    {
        if (empty($this->preset[$format])) {
            throw new QueryException("Unsupport format encode line:" . $format);
        }

        $this->settings = $this->preset[$format];
    }

    /**
     * @param array $row
     * @return string
     */
    public function quoteRow(array $row): string
    {
        return implode($this->settings['Delimiter'], $this->quoteValue($row));
    }

    /**
     * @param array $row
     * @return array
     */
    public function quoteValue(array $row): array
    {
        $enclosure = $this->settings['Enclosure'];
        $delimiter = $this->settings['Delimiter'];
        $encode = $this->settings['EncodeEnclosure'];
        $encodeArray = $this->settings['EnclosureArray'];
        $null = $this->settings['Null'];
        $tabEncode = $this->settings['TabEncode'];

        $quote = function ($value) use ($enclosure, $delimiter, $encode, $encodeArray, $null, $tabEncode) {
            $delimiter_esc = preg_quote($delimiter, '/');

            $enclosure_esc = preg_quote($enclosure, '/');

            $encode_esc = preg_quote($encode, '/');

            $encode = true;
            if ($value instanceof NumericType) {
                $encode = false;
            }

            if (is_array($value)) {
                // Arrays are formatted as a list of values separated by commas in square brackets.
                // Elements of the array - the numbers are formatted as usual, and the dates, dates-with-time, and lines are in
                // single quotation marks with the same screening rules as above.
                // as in the TabSeparated format, and then the resulting string is output in InsertRow in double quotes.
                $value = array_map(
                    function ($v) use ($enclosure_esc, $encode_esc) {
                        return is_string($v) ? $this->encodeString($v, $enclosure_esc, $encode_esc) : $v;
                    },
                    $value
                );
                $resultArray = FormatLine::Insert($value);

                return $encodeArray . '[' . $resultArray . ']' . $encodeArray;
            }

//            $value = ValueFormatter::formatValue($value, false);

            if (is_float($value) || is_int($value)) {
                return (string)$value;
            }

            if (is_string($value) && $encode) {
                if ($tabEncode) {
                    return str_replace(["\t", "\n"], ['\\t', '\\n'], $value);
                }

                $value = $this->encodeString($value, $enclosure_esc, $encode_esc);

                return $enclosure . $value . $enclosure;
            }

            if ($value === null) {
                return $null;
            }

            return $value;
        };

        return array_map($quote, $row);
    }

    /**
     * @return string
     */
    public function encodeString(string $value, string $enclosureEsc, string $encodeEsc): string
    {
        return preg_replace('/(' . $enclosureEsc . '|' . $encodeEsc . ')/', $encodeEsc . '\1', $value);
    }
}
