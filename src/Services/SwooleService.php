<?php
/**
 * Created by PhpStorm.
 * User: kent
 */
namespace Kentczhy\Swsocket\Services;

use Kentczhy\Swsocket\Enum\SwsocketEnum;

/**
 * swoole 服务层一般写一些redis的操作
 *
 * Class SwooleService
 * @package Kentczhy\Swsocket\Services
 */
class SwooleService
{

    /**
     * 绑定一个链接对应一个用户id
     *
     * @param $oRedis
     * @param $userId
     * @param $fd
     * @return bool
     */
    private static function setUserIdByFd($oRedis, $userId, $fd)
    {
        $keyFd = sprintf(SwsocketEnum::SW_ONLINE_FD_TO_USER, $fd);
        $val = (int) $userId;
        $oRedis->set($keyFd, $val);
        return true;
    }

    /**
     * 绑定一个链接对应一个token
     *
     * @param $oRedis
     * @param $token
     * @param $fd
     * @return bool
     */
    private static function setTokenByFd($oRedis, $token, $fd)
    {
        $keyFd = sprintf(SwsocketEnum::SW_ONLINE_FD_TO_TOKEN, $fd);
        $val = (string) $token;
        $oRedis->set($keyFd, $val);
        return true;
    }

    /**
     * 加入集合 同时绑定一个链接对应一个用户id
     *
     * @param $oRedis
     * @param $userId
     * @param $fd
     * @return bool
     */
    public static function zadd($oRedis, $userId, $fd)
    {
        if (!$userId || !$fd) {
            return false;
        }
        $keyUserId = sprintf(SwsocketEnum::SW_ONLINE_USER_ALL_FD, $userId);
        $fd = (int) $fd;
        $oRedis->zadd($keyUserId, $fd, time());
        self::setUserIdByFd($oRedis, $userId, $fd);
        $oRedis->zadd(SwsocketEnum::SW_ONLINE_ALL_USER, $userId, time());
        return true;
    }

    /**
     * 获取用户ID
     *
     * @param $oRedis
     * @param $fd
     *
     * @return array|mixed|null|string
     */
    public static function getUserIdByFd($oRedis, $fd)
    {
        $keyFd = sprintf(SwsocketEnum::SW_ONLINE_FD_TO_USER, $fd);
        return $oRedis->get($keyFd);
    }

    /**
     * 获取用户token
     *
     * @param $oRedis
     * @param $fd
     *
     * @return array|mixed|null|string
     */
    public static function getTokenByFd($oRedis, $fd)
    {
        $keyFd = sprintf(SwsocketEnum::SW_ONLINE_FD_TO_TOKEN, $fd);
        return $oRedis->get($keyFd);
    }

    /**
     * 从用户队列里面移除某个链接
     *
     * @param $oRedis
     * @param $fd
     *
     * @return bool
     */
    public static function zremByFd($oRedis, $fd)
    {
        $userId = self::getUserIdByFd($oRedis, $fd);
        if ($userId > 0) {
            if ((int) $userId != $userId) {
                return false;
            }
            $keyUserId = sprintf(SwsocketEnum::SW_ONLINE_USER_ALL_FD, $userId);
            $oRedis->zrem($keyUserId, (int) $fd);

            $keyUserFd = sprintf(SwsocketEnum::SW_ONLINE_FD_TO_USER, $fd);
            $oRedis->del($keyUserFd);

            //一个链接都没有了，就清除掉
            $arrFd = self::getOnlineByUserId($oRedis, $userId);
            if (empty($arrFd)) {
                $oRedis->zrem(SwsocketEnum::SW_ONLINE_ALL_USER, $userId);
                // todo 如果需要发送在线通知在这里发
            }
        }

        // 绑定的 token 同时也清理掉
        $token = self::getTokenByFd($oRedis, $fd);
        if ($token != '') {
            $keyToken = sprintf(SwsocketEnum::SW_ONLINE_TOKEN_ALL_FD, $token);
            $oRedis->zrem($keyToken, (int) $fd);

            $keyTokenFd = sprintf(SwsocketEnum::SW_ONLINE_FD_TO_TOKEN, $fd);
            $oRedis->del($keyTokenFd);
        }
        return true;
    }

    /**
     * 获取所有在线的用户
     *
     * @param $oRedis
     *
     * @return array|bool
     */
    public static function getAllOnlineUserId($oRedis)
    {
        return $oRedis->zRevRangeByScore(SwsocketEnum::SW_ONLINE_ALL_USER, time(), 0, []);
    }

    /**
     * 获取某个用户在线的链接 把所有的都取出来
     *
     * @param $oRedis
     * @param $userId
     *
     * @return array|bool
     */
    public static function getOnlineByUserId($oRedis, $userId)
    {
        if ((int) $userId != $userId) {
            return false;
        }
        $keyUserId = sprintf(SwsocketEnum::SW_ONLINE_USER_ALL_FD, $userId);
        return $oRedis->zRevRangeByScore($keyUserId, time(), 0, []);
    }

    /**
     * 发布消息
     *
     * @param $oRedis
     * @param $uid
     * @param $data
     * @param int $expired
     */
    public static function produceMsg($oRedis, $uid, $data, $expired=600)
    {
        // 存到消息配置的redis 里面
        //设置信息缓存
        if (!isset($data['data'])) {
            return;
        }
        if (!isset($data['uid'])) {
            $data['uid'] = $uid;
        }
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $msg_key = SwsocketEnum::REDIS_MSG_QUEUE_DATA_PRE.$uid.'_'.time().'_'.swRandStr(4); //这个看并发量
        $oRedis->setex($msg_key, $data, $expired);
        //加入队列
        $oRedis->lpush(SwsocketEnum::REDIS_MSG_QUEUE, [$msg_key]);
    }

    /**
     * 消费消息
     *
     * @param $oRedis
     * @return array|mixed
     */
    public static function consumerMsg($oRedis)
    {
        $msgKey = $oRedis->rpop(SwsocketEnum::REDIS_MSG_QUEUE);
        $arrData = [];
        if ($msgKey) {
            $jsonData = $oRedis->get($msgKey);
            $oRedis->del($msgKey);
            $arrData = json_decode($jsonData, true);
        }
        return $arrData;
    }

    /**
     * 加入Token集合 (用户退出断开链接)
     *
     * @param $oRedis
     * @param $userId
     * @param $fd
     * @return bool
     */
    public static function zaddFdBindToken($oRedis, $token, $fd)
    {
        if (!$token || !$fd) {
            return false;
        }
        $keyToken = sprintf(SwsocketEnum::SW_ONLINE_TOKEN_ALL_FD, $token);
        $fd = (int) $fd;
        $oRedis->zadd($keyToken, $fd, time());
        self::setTokenByFd($oRedis, $token, $fd);
        return true;
    }

    /**
     * 清除Token集合 (用户退出断开链接)
     *
     * @param $oRedis
     * @param $token
     * @return array
     */
    public static function remTokenFd($oRedis, $token)
    {
        if (!$token) {
            return [];
        }
        $keyToken = sprintf(SwsocketEnum::SW_ONLINE_TOKEN_ALL_FD, $token);
        $arrFd = $oRedis->zRevRangeByScore($keyToken, time(), 0, []);
        if (is_array($arrFd) && !empty($arrFd)) {
            foreach ($arrFd as $k => $fd) {
                self::zremByFd($oRedis, $fd);
            }
            $oRedis->del($keyToken);
            return $arrFd;
        } else {
            return [];
        }
    }

    /**
     * token生产者
     *
     * @param $oRedis
     * @param $token
     */
    public static function produceLogoutToken($oRedis, $token)
    {
        $oRedis->lpush(SwsocketEnum::REDIS_TOKEN_QUEUE, [$token]);
    }

    /**
     * token消费者
     *
     * @param $oRedis
     * @return array
     */
    public static function consumerLogoutToken($oRedis)
    {
        $token = $oRedis->rpop(SwsocketEnum::REDIS_TOKEN_QUEUE);
        if (is_string($token)) {
            return self::remTokenFd($oRedis, $token);
        }
    }
}
