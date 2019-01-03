<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/28
 * Time: 14:23
 */

namespace SeasStash\Server;

use SeasStash\Adapter\AbstractAdapter;
use SeasStash\Adapter\ClickhouseAdapter;
use SeasStash\Clickhouse\Clickhouse;
use SeasStash\Clickhouse\Http;
use SeasStash\Message\Rfc5424;
use SeasStash\Timer\TimerCo;

/**
 * Class SeasServer
 * @package SeasStash\Server
 */
abstract class SeasServer extends Server
{

    /** @var AbstractAdapter */
    protected $storageAdapter;
    /** @var Clickhouse */
    protected $clickhouse;

    public function createAdapter(): void
    {
        $defAdapter = [
            'bufferSize' => 0,
            'ticket' => 10
        ];
        if (!isset($this->setting['adapter'])) {
            $this->setting['adapter'] = $defAdapter;
        } else {
            $this->setting['adapter'] = array_merge($defAdapter, $this->setting['adapter']);
        }
        $defClickhouse = [
            'database' => 'seaslog',
            'http' => [
                'base_uri' => 'http://clickhouse.SeasStashPHP_default',
                'timeout' => 600,
                'retry_time' => 3,
                'headers' => [
                    'Accept' => \Swlib\Http\ContentType::JSON
                ],
                'auth' => ['username' => 'default', 'password' => ''],
            ]
        ];
        if (!isset($this->setting['clickhouse'])) {
            $this->setting['clickhouse'] = $defClickhouse;
        } else {
            $this->setting['clickhouse'] = array_merge($defClickhouse, $this->setting['clickhouse']);
        }
        $http = new Http($this->setting['clickhouse']['http']);
        $clicehouse = new Clickhouse($http, $this->setting['clickhouse']['database']);
        $this->storageAdapter = new ClickhouseAdapter($clicehouse);
        $this->clickhouse = $clicehouse;
    }

    /**
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function workerStart(\Swoole\Server $server, int $worker_id): void
    {
        $setting = $this->setting;
        $this->createAdapter();
        TimerCo::addTickTimer('flushBuffer', $setting['server']['flushTicket'] * 1000, function () {
            \Seaslog::flushBuffer();
        });
        if (!isset($this->setting['create'])) {
            $this->setting['create'] = [
                'database' => ' CREATE DATABASE IF NOT EXISTS seaslog',
                'table' => ' CREATE TABLE IF NOT EXISTS seaslog.logs (
                      logdate Date DEFAULT toDate(datetime),
                      appname String,
                      datetime DateTime,
                      level String,
                      request_uri String,
                      request_method String,
                      clientip String,
                      requestid String,
                      filename String,
                      memoryusage UInt16,
                      message String
                    ) ENGINE = MergeTree(logdate, datetime, 8192)'
            ];
        }
        foreach ($this->setting['create'] as $sql) {
            $this->clickhouse->execute($sql)->getReasonPhrase();
        }
    }

    /**
     * @param string $data
     */
    protected function saveLog(string $data): void
    {
        try {
            $log = new Rfc5424($data);
            if (!$this->storageAdapter) {
                $this->createAdapter();
            }
            $this->storageAdapter->save($log);
        } catch (\Throwable $exception) {
            \Seaslog::error($exception->getMessage());
        }
    }
}