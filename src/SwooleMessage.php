<?php
/**
 * Created by PhpStorm.
 * User: kent
 */

namespace Kentczhy\Swsocket;

use Kentczhy\Swsocket\Traits\ContainerTrait;
use Kentczhy\Swsocket\Services\SwooleService;

/**
 * 消息推送
 *
 * Class SwooleMessage
 * @package Kentczhy\Swsocket
 */
class SwooleMessage
{
    private static function _push($wServer, $wProcess, $oRedis, $fd, $arrData)
    {
        $connectInfo = $wServer->connection_info($fd);
        // 如果不存在了，去掉fd链接
        if (!$connectInfo || !is_array($connectInfo)) {
            SwooleService::zremByFd($oRedis, $fd);
            return;
        }
        //监听的端口 看是否需要 某些 端口不推
        $server_port = $connectInfo['server_port'];
//        $arrData['connectInfo'] = $connectInfo;
        $arrData = swCamelKeyCase($arrData); // 统一格式
        $jsonData = json_encode($arrData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $wServer->push($fd, $jsonData);
    }

    public static function sendDataToUser($wServer, $wProcess, $oRedis, $userId, $arrFormatData)
    {
        $arrFd = SwooleService::getOnlineByUserId($oRedis, $userId);
        if (!empty($arrFd)) {
            foreach ($arrFd as $fd) {
                $fd = (int) $fd;
                self::_push($wServer, $wProcess, $oRedis, $fd, $arrFormatData);
            }
        } else {
            return;
            // todo 其它不在线的推送 例如jpush 或者 数据重新入列 或者根据数据类型继续选择推或者不推送
        }
    }

    public static function sendOnlineLoginOrLogoutMsg($wServer, $wProcess, $oRedis, $arrFormatData)
    {
        $arrData = $arrFormatData['data'];
        if (!empty($arrData) && isset($arrData['id'])) {  //$arrData['id'] 就是用户id
            $arrAllOnlieUserId = SwooleService::getAllOnlineUserId($oRedis);
            if (!empty($arrAllOnlieUserId)) {
                foreach ($arrAllOnlieUserId as $uid) {
                    if ($uid == $arrData['id']) {
                        continue;
                    }
                    self::sendDataToUser($wServer, $wProcess, $oRedis, $uid, $arrFormatData);
                }
            }
        }
    }
}
