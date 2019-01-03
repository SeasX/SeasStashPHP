<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/10/8
 * Time: 19:09
 */

namespace SeasStash\Server;

/**
 * Class Server
 * @package SeasStash\Server
 */
abstract class Server
{
    /**
     * @var int
     */
    protected $type = SWOOLE_PROCESS;

    /** @var array */
    protected $setting = [];
    /** @var \Swoole\Server */
    protected $server;

    /**
     * Server constructor.
     * @param array $setting
     * @throws \Exception
     */
    public function __construct(array $setting = [])
    {
        $this->parseServer($setting);
        $this->parseSetting($setting);
        $this->parseAddlisten($setting);
        $this->setting = $setting;
    }

    /**
     * @param array $setting
     */
    private function parseServer(array &$setting): void
    {
        if (isset($setting['server'])) {
            if (!isset($setting['server']['name'])) {
                $setting['server']['name'] = 'SeasStash';
            }
            if (!isset($setting['server']['host'])) {
                $setting['server']['host'] = '0.0.0.0';
            }
            if (!isset($setting['server']['port'])) {
                $setting['server']['port'] = 80;
            }
            if (!isset($setting['server']['flushTicket'])) {
                $setting['server']['flushTicket'] = 1;
            }
        } else {
            $setting['server']['name'] = 'SeasStash';
            $setting['server']['host'] = '0.0.0.0';
            $setting['server']['port'] = 80;
            $setting['server']['flushTicket'] = 1;
        }
    }

    /**
     * @param array $setting
     */
    private function parseSetting(array &$setting): void
    {
        $defSetting = [
            'worker_num' => swoole_cpu_num(), //worker process num
            'dispatch_mode' => 2,
            'daemonize' => 0,
            'open_cpu_affinity' => true,
            'open_tcp_nodelay' => true,
            'buffer_output_size' => 128 * 1024 * 1024,
            'heartbeat_check_interval' => 60,
            'heartbeat_idle_time' => 600,
            'tcp_defer_accept' => 5,
            'enable_reuse_port' => true,
            'http_parse_post' => true,
            'reload_async' => true,
            'socket_buffer_size' => 128 * 1024 * 1024, //必须为数字
        ];
        if (!isset($setting['setting'])) {
            $setting['setting'] = $defSetting;
        } else {
            $setting['setting'] = array_merge($defSetting, $setting['setting']);
        }
    }

    /**
     * @param array $setting
     */
    private function parseAddlisten(array &$setting): void
    {
        $defSetting = [
            'open_eof_check' => true,
            'open_eof_split' => true,
            'package_eof' => PHP_EOL,
            'package_max_length' => 1024 * 1024 * 32,
            'buffer_output_size' => 1024 * 1024 * 48,
            'pipe_buffer_size' => 1024 * 1024 * 128,
            'socket_buffer_size' => 128 * 1024 * 1024, //必须为数字
            'heartbeat_check_interval' => 60,
            'heartbeat_idle_time' => 86400,
        ];
        $defAddlisten = [
            'tcp' => [
                'server' => [
                    'host' => '0.0.0.0',
                    'port' => 514
                ],
                'setting' => $defSetting
            ],
            'udp' => [
                'server' => [
                    'host' => '0.0.0.0',
                    'port' => 5014
                ],
                'setting' => $defSetting
            ]
        ];
        if (!isset($setting['addlisten'])) {
            $setting['addlisten'] = $defAddlisten;
        } else {
            $setting['addlisten'] = array_merge($defAddlisten, $setting['addlisten']);
        }
    }

    public function start(): void
    {
        $this->startServer($this->createServer());
    }

    /**
     * @return \Swoole\Server
     */
    abstract protected function createServer(): \Swoole\Server;

    /**
     * @param \Swoole\Server|null $server
     */
    protected function startServer(\Swoole\Server $server = null): void
    {
        $this->server = $server;
        $server->on('start', [$this, 'onStart']);
        $server->on('shutdown', [$this, 'onShutdown']);
        $server->on('workerStart', [$this, 'onWorkerStart']);
        $server->on('workerStop', [$this, 'onWorkerStop']);
        $server->set($this->setting['setting']);
        $this->beforeStart($this->setting['addlisten']);
    }

    /**
     * @param array $listen
     * @throws \Exception
     */
    protected function beforeStart(array $listen): void
    {
        foreach ($listen as $schme => $data) {
            if ($schme === 'tcp') {
                $port = $this->server->listen($data['server']['host'], $data['server']['port'], SWOOLE_SOCK_TCP);
                $port->on('Receive', [new TcpServer($this->setting), 'onReceive']);
            } elseif ($schme === 'udp') {
                $port = $this->server->listen($data['server']['host'], $data['server']['port'], SWOOLE_SOCK_UDP);
                $port->on('packet', [new UdpServer($this->setting), 'onPacket']);
            }
            $port->set($data['setting']);
        }
    }

    /**
     * @param string $name
     */
    protected function setProcessTitle(string $name): void
    {
        if (function_exists('swoole_set_process_name')) {
            @swoole_set_process_name($name);
        } else {
            @cli_set_process_title($name);
        }
    }

    public function stop(): void
    {
        if ($this->server->setting['pid_file']) {
            $pid = file_get_contents($this->server->setting['pid_file']);
            \swoole_process::kill(intval($pid));
        }
    }

    /**
     * @param \Swoole\Server $server
     */
    public function onStart(\Swoole\Server $server): void
    {
        $this->setProcessTitle($this->setting['server']['name'] . ': master');
        if ($server->setting['pid_file']) {
            file_put_contents($server->setting['pid_file'], $server->master_pid);
        }
    }

    /**
     * @param \Swoole\Server $server
     */
    public function onShutdown(\Swoole\Server $server): void
    {
        if ($server->setting['pid_file']) {
            unlink($server->setting['pid_file']);
        }
    }

    /**
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function onWorkerStart(\Swoole\Server $server, int $worker_id): void
    {
        if (!$server->taskworker) {
            //worker
            $this->setProcessTitle($this->setting['server']['name'] . ': worker' . ": {$worker_id}");
        } else {
            //task
            $this->setProcessTitle($this->setting['server']['name'] . ': task' . ": {$worker_id}");
        }
        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
        $this->workerStart($server, $worker_id);
    }

    abstract public function workerStart(\Swoole\Server $server, int $worker_id): void;

    /**
     * @param \Swoole\Server $server
     * @param int $worker_id
     */
    public function onWorkerStop(\Swoole\Server $server, int $worker_id): void
    {
        if (extension_loaded('Zend OPcache')) {
            opcache_reset();
        }
    }
}