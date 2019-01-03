<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/12/5
 * Time: 10:12
 */

namespace SeasStash\Adapter;

use SeasStash\Clickhouse\Clickhouse;
use SeasStash\Message\Rfc5424;
use SeasStash\Timer\TimerCo;

/**
 * Class ClickhouseAdapter
 * @package Adapter
 */
class ClickhouseAdapter extends AbstractAdapter
{
    /** @var Clickhouse */
    private $clickHouse;
    /**
     * @var string
     */
    private $tableName = 'logs';

    /**
     * ClickhouseAdapter constructor.
     * @param Clickhouse $clickhouse
     */
    public function __construct($clickhouse, array $config = [])
    {
        $this->clickHouse = $clickhouse;
        if (isset($config['bufferSize'])) {
            $this->bufferSize = $config['bufferSize'];
        }
        if (isset($config['ticket'])) {
            $this->ticket = $config['ticket'];
        }
        if ($this->ticket > 0) {
            TimerCo::addTickTimer('flushaLogs', $this->ticket * 1000, [$this, 'flush']);
        }
    }

    /**
     * @param Rfc5424 $data
     */
    public function save(Rfc5424 $data): void
    {
        $tmpLog = $data->toArray();
        $this->buffer[] = array_merge(['appname' => $tmpLog['appName']], $tmpLog['templateMsg']);
        if ($this->bufferSize > 0 && count($this->buffer) >= $this->bufferSize) {
            $this->flush();
        }
    }

    /**
     * @param array $flushBuffer
     */
    public function flushBuffer(array $flushBuffer): void
    {
        $this->clickHouse->insert($this->tableName, $flushBuffer)->getReasonPhrase();
        unset($flushBuffer);
    }
}