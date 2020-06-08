<?php
/**
 * Created by PhpStorm.
 * User: kent
 */
namespace Kentczhy\Swsocket;

use Kentczhy\Swsocket\Services\SwooleService;

/**
 * swoole 绑定的方法
 *
 * Class SwooleFunc
 * @package Kentczhy\Swsocket
 */
class SwooleFunc
{
    public function open($wServer, $request)
    {
        // $request 是一个Http请求对象，包含了客户端发来的握手请求信息，有需要可以在这里加逻辑
    }

    public function message($wServer, $frame)
    {
        // 处理 message 信息
        \go(function () use ($wServer, $frame) {
            try {
                $oSwooleClient = new SwooleClient($wServer, $frame);
                $oSwooleClient->run();
                unset($oSwooleClient);
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                $code = $e->getCode() ? $e->getCode() : 400;
                if (in_array($code, ['400', '406', '-400'])) {
                    $msg = $msg . " " . $e->getTraceAsString();
                }
                // status === 8 心跳的回应
                if ($code === 8) {
                    $wServer->push($frame->fd, $msg);
                } else {
                    $data = @json_decode($frame->data, true);
                    if (isset($data['cmd'])) {
                        $jsonData = SwooleResponse::getInstance()->json($data['cmd'], $code, $msg, $data);
                    } else {
                        $jsonData = SwooleResponse::getInstance()->json('', $code, $msg, $data);
                    }
                    $wServer->push($frame->fd, $jsonData);
                }
                unset($oSwooleClient);
            }
        });
    }

    public function close($wServer, $fd)
    {
        // socket 关闭回调逻辑
    }
}
