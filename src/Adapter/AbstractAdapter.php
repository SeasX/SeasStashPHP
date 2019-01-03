<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/12/5
 * Time: 9:28
 */

namespace SeasStash\Adapter;

use SeasStash\Message\Rfc5424;

/**
 * Class AbstractAdapter
 * @package Adapter
 */
abstract class AbstractAdapter
{
    /** @var int */
    protected $bufferSize = 0;
    /** @var int */
    protected $ticket = 1;
    /** @var array */
    protected $buffer = [];

    /**
     * @return int
     */
    public function getTicket(): int
    {
        return $this->ticket;
    }

    /**
     * @return int
     */
    public function getBufferSize(): int
    {
        return $this->bufferSize;
    }

    /**
     * @param int $size
     */
    public function setBufferSize(int $size): void
    {
        $this->bufferSize = $size;
    }

    /**
     * @return array
     */
    public function getBuffer(): array
    {
        return $this->buffer;
    }

    /**
     * @param array $buffer
     */
    public function setBuffer(array $buffer = []): void
    {
        $this->buffer = $buffer;
    }

    /**
     * @param Rfc5424 $data
     */
    abstract public function save(Rfc5424 $data): void;

    /**
     *
     */
    public function flush(): void
    {
        $flushBuffer = $this->buffer;
        $this->buffer = [];
        if ($flushBuffer) {
            $this->flushBuffer($flushBuffer);
        }
    }

    /**
     * @param array $flushBuffer
     */
    abstract function flushBuffer(array $flushBuffer): void;
}