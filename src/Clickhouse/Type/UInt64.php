<?php

declare(strict_types=1);

namespace SeasStash\Clickhouse\Type;

final class UInt64 implements NumericType
{
    /** @var string */
    public $value;

    /**
     * UInt64 constructor.
     * @param string $uint64Value
     */
    private function __construct(string $uint64Value)
    {
        $this->value = $uint64Value;
    }

    /**
     * @return self
     */
    public static function fromString(string $uint64Value): self
    {
        return new self($uint64Value);
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }
}
