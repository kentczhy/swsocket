<?php
/**
 * Created by PhpStorm.
 * User: kent
 */
namespace Kent\Swsocket;

use Illuminate\Support\Facades\Log;
use Kent\Swsocket\Services\SwooleService;

/**
 * swoole 常驻进程
 *
 * Class SwooleProcess
 * @package Kent\Swsocket
 */
class SwooleProcess
{
    /**
     * 消费者推送消息
     */
    public static function sendMsg($wServer, $process)
    {
        // 0.1 s
        $wServer->tick(100, function() use ($wServer, $process) {
            \go(function() use ($wServer, $process){
                try {
                    $oRedis = $wServer->redisPool->get();
                    $arrAllData = SwooleService::consumerMsg($oRedis);
                    if (isset($arrAllData['uid']) && isset($arrAllData['data'])) {
                        SwooleMessage::sendDataToUser($wServer, $process, $oRedis, $arrAllData['uid'], $arrAllData['data']);
                    }
                    $wServer->redisPool->put($oRedis);
                } catch (\Exception $e) {
                }
            });
        });
    }


//    /**
//     * 20秒钟检测清理无效token （一般）
//     */
//    public static function removeTheUserOfLogout($wServer, $process)
//    {
//        $wServer->tick(20000, function() use ($wServer, $process) {
//            \go(function() use ($wServer, $process){
//                try {
//                    LoginTokenService::getInstance()->removeUserOfLogout();
//                } catch (\Exception $e) {
//                }
//            });
//        });
//    }

    /**
     * 10秒钟检测无效链接去掉
     */
    public static function removeDisableLink($wServer, $process)
    {
        $wServer->tick(10000, function() use ($wServer, $process) {
            \go(function() use ($wServer, $process){
                try {
                    $oRedis = $wServer->redisPool->get();
                    $arrUserId = SwooleService::getAllOnlineUserId($oRedis);
                    if (is_array($arrUserId) && !empty($arrUserId)) {
                        foreach ($arrUserId as $userId) {
                            $arrFd = SwooleService::getOnlineByUserId($oRedis, $userId);
                            foreach ($arrFd as $fd) {
                                $connectInfo = $wServer->connection_info($fd);
                                // 如果不存在了，去掉fd链接
                                if (!$connectInfo || !is_array($connectInfo)) {
                                    SwooleService::zremByFd($oRedis, $fd);
                                    return;
                                }
                            }
                        }
                    }
                    $wServer->redisPool->put($oRedis);
                } catch (\Exception $e) {
                }
            });
        });
    }

    /**
     * 消费者清除登录链接
     */
    public static function removeTokenFd($wServer, $process)
    {
        $wServer->tick(10000, function() use ($wServer, $process) {
            \go(function() use ($wServer, $process){
                try {
                    $oRedis = $wServer->redisPool->get();
                    $arrFd = SwooleService::consumerLogoutToken($oRedis);
                    if (!empty($arrFd)) {
                        foreach ($arrFd as $fd) {
                            $wServer->close($fd);
                        }
                    }
                    $wServer->redisPool->put($oRedis);
                } catch (\Exception $e) {
                }
            });
        });
    }
}
