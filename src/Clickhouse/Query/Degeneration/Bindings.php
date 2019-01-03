<?php

declare(strict_types=1);

namespace SeasStash\Clickhouse\Query\Degeneration;

use SeasStash\Clickhouse\Query\Degeneration;
use SeasStash\Clickhouse\Quote\ValueFormatter;
use function array_map;
use function implode;
use function is_array;

/**
 * Class Bindings
 * @package Clickhouse\Query\Degeneration
 */
class Bindings implements Degeneration
{
    /**
     * @var array
     */
    protected $bindings = [];

    /**
     * @param array $bindings
     */
    public function bindParams(array $bindings): void
    {
        $this->bindings = [];
        foreach ($bindings as $column => $value) {
            $this->bindParam($column, $value);
        }
    }

    /**
     * @param string $column
     * @param mixed $value
     */
    public function bindParam(string $column, $value): void
    {
        $this->bindings[$column] = $value;
    }

    /**
     * Binds a list of values to the corresponding parameters.
     * This is similar to [[bindValue()]] except that it binds multiple values at a time.
     *
     * @param string $sql
     * @param array $binds
     * @param string $pattern
     * @return string
     */
    public function compile_binds(string $sql, array $binds, string $pattern): string
    {
        return preg_replace_callback($pattern, function ($m) use ($binds) {
            if (isset($binds[$m[1]])) { // If it exists in our array
                return $binds[$m[1]]; // Then replace it from our array
            }

            return $m[0]; // Otherwise return the whole match (basically we won't change it)
        }, $sql);
    }

    /**
     * Compile Bindings
     *
     * @param string $sql
     * @return mixed
     */
    public function process(string $sql)
    {
        $bindFormatted = [];
        $bindRaw = [];
        foreach ($this->bindings as $key => $value) {
            if (is_array($value)) {
                $valueSet = implode(', ', $value);

                $values = array_map(
                    function ($value) {
                        return ValueFormatter::formatValue($value);
                    },
                    $value
                );

                $formattedParameter = implode(',', $values);
            } else {
                $valueSet = $value;
                $formattedParameter = ValueFormatter::formatValue($value);
            }

            if ($formattedParameter !== null) {
                $bindFormatted[$key] = $formattedParameter;
            }

            if ($valueSet !== null) {
                $bindRaw[$key] = $valueSet;
            }
        }

        for ($loop = 0; $loop < 2; $loop++) {
            // dipping in binds
            // example ['A' => '{B}' , 'B'=>':C','C'=>123]
            $sql = $this->compile_binds($sql, $bindRaw, '#{([\w+]+)}#');
        }
        $sql = $this->compile_binds($sql, $bindFormatted, '#:([\w+]+)#');

        return $sql;
    }
}
