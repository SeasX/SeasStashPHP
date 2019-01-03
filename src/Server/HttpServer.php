<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/28
 * Time: 14:34
 */

namespace SeasStash\Server;

/**
 * Class HttpServer
 * @package SeasStash\Server
 */
class HttpServer extends SeasServer
{

    /**
     * @return \Swoole\Server
     */
    protected function createServer(): \Swoole\Server
    {
        $setting = $this->setting['server'];
        return new \Swoole\Http\Server($setting['host'], $setting['port'], $this->type);
    }

    /**
     * @throws \Exception
     */
    protected function startServer(\Swoole\Server $server = null): void
    {
        parent::startServer($server);
        $server->on('request', [$this, 'onRequest']);
        $server->start();
    }

    /**
     * @param \Swoole\Http\Request $request
     * @param \Swoole\Http\Response $response
     */
    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response): void
    {
        try {
            if ($request->server['request_method'] !== 'POST') {
                $response->status(403);
                $response->end("only support method post!");
                return;
            }
            if ($data = $request->rawContent()) {
                $data && $data = json_decode($data, true);
            }
            if ($request->post) {
                $data += $request->post;
            }
            foreach ($data as $rfc) {
                $this->saveLog($rfc);
            }
            $response->end("OK");
        } catch (\Throwable $exception) {
            \Seaslog::error($exception->getMessage());
            $response->end("error");
        }
    }
}