<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/12/9
 * Time: 22:00
 */

namespace SeasStash\Server;

/**
 * Class UdpServer
 * @package SeasStash\Server
 */
class UdpServer extends SeasServer
{
    /**
     * @var string
     */
    protected $host = '0.0.0.0';
    /**
     * @var int
     */
    protected $port = 514;

    /**
     * @return \Swoole\Server
     */
    protected function createServer(): \Swoole\Server
    {
        $setting = $this->setting['setting'];
        return new \Swoole\Server($setting['host'], $setting['port'], $this->type, SWOOLE_SOCK_UDP);
    }

    /**
     * @param \Swoole\Server|null $server
     */
    protected function startServer(\Swoole\Server $server = null): void
    {
        parent::startServer($server);
        $server->on('Packet', array($this, 'onPacket'));
        $server->start();
    }

    /**
     * @param \Swoole\Server $server
     * @param string $data
     * @param array $client_info
     */
    public function onPacket(\Swoole\Server $server, string $data, array $client_info): void
    {
        $this->saveLog($data);
    }
}