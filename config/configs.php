<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/12/28
 * Time: 14:27
 */
$server_config = [
    'open_eof_check' => true,
    'open_eof_split' => true,
    'package_eof' => PHP_EOL,
    'package_max_length' => 1024 * 1024 * 32,
    'buffer_output_size' => 1024 * 1024 * 48,
    'pipe_buffer_size' => 1024 * 1024 * 128,
    'socket_buffer_size' => 128 * 1024 * 1024, //必须为数字
    'heartbeat_check_interval' => 60,
    'heartbeat_idle_time' => 600,
];
return [
    'server' => [
        'name' => 'SeasStash',
        'host' => '0.0.0.0',
        'port' => 80,
        'flushTicket' => 1
    ],
    'setting' => [
        'worker_num' => swoole_cpu_num(), //worker process num
        'dispatch_mode' => 2,
        'log_file' => dirname(__DIR__) . '/runtime/logs/SeasStash.log',
        'daemonize' => 0,
        'open_cpu_affinity' => true,
        'open_tcp_nodelay' => true,
        'buffer_output_size' => 128 * 1024 * 1024,
        'pid_file' => sys_get_temp_dir() . '/SeasStash.pid',
        'heartbeat_check_interval' => 60,
        'heartbeat_idle_time' => 600,
        'tcp_defer_accept' => 5,
        'enable_reuse_port' => true,
        'http_parse_post' => true,
        'reload_async' => true,
        'socket_buffer_size' => 128 * 1024 * 1024, //必须为数字
    ],
    'addlisten' => [
        'tcp' => [
            'server' => [
                'host' => '0.0.0.0',
                'port' => 514
            ],
            'setting' => $server_config
        ],
        'udp' => [
            'server' => [
                'host' => '0.0.0.0',
                'port' => 5014
            ],
            'setting' => $server_config
        ]
    ],
    'adapter' => [
        'bufferSize' => 0,
        'ticket' => 10
    ],
    'clickhouse' => [
        'database' => 'seaslog',
        'http' => [
            'base_uri' => 'http://clickhouse.SeasStash:8123',
            'timeout' => 600,
            'retry_time' => 3,
            'headers' => [
                'Accept' => \Swlib\Http\ContentType::JSON
            ],
            'auth' => ['username' => 'default', 'password' => ''],
        ]
    ],
    'create' => [
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
    ]
];