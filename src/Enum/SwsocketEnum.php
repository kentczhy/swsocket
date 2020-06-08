<?php
/**
 * Created by PhpStorm.
 * User: kent
 * Date: 21/03/2018
 * Time: 10:46
 */

namespace Kentczhy\Swsocket\Enum;

interface SwsocketEnum
{
    //swoole
    const REDIS_MSG_QUEUE = 'msg_queue';
    const REDIS_TOKEN_QUEUE = 'msg_token';
    const REDIS_MSG_QUEUE_DATA_PRE = 'msg_queue_data_pre_';

    const SW_ONLINE_PREFIX = "sw_online";
    const SW_ONLINE_ALL_USER = "sw_online_all_user"; //所有用户的连接（主要用来遍历清除连接）
    const SW_ONLINE_USER_ALL_FD = "sw_online_user_%s_all_fd"; //一个用户对应所有连接
    const SW_ONLINE_TOKEN_ALL_FD = "sw_online_token_%s_all_fd"; //一个登录token对应所有连接
    const SW_ONLINE_FD_TO_USER = "sw_online_fd_%s_to_user";   //一个连接对应的用户
    const SW_ONLINE_FD_TO_TOKEN = "sw_online_fd_%s_to_token";   //一个连接对应的token
}
