<?php
/**
 * Created by PhpStorm.
 * User: amuluowin
 * Date: 2018/11/26
 * Time: 16:45
 */

namespace SeasStash\Server;

/**
 * Class TcpServer
 * @package Server
 */
class TcpServer extends SeasServer
{
    /**
     * @return \Swoole\Server
     */
    protected function createServer(): \Swoole\Server
    {
        $setting = $this->setting['setting'];
        return new \Swoole\Server($setting['host'], $setting['port'], $this->type);
    }

    /**
     * @param \Swoole\Server|null $server
     */
    protected function startServer(\Swoole\Server $server = null): void
    {
        parent::startServer($server);
        $server->on('Receive', array($this, 'onReceive'));
        $server->start();
    }


    /**
     * @param \Swoole\Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param string $data
     */
    public function onReceive(\Swoole\Server $server, int $fd, int $reactor_id, string $data): void
    {
        $this->saveLog($data);
    }
}