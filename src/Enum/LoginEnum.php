<?php
/**
 * Created by PhpStorm.
 * User: kent
 * Date: 21/03/2018
 * Time: 10:46
 */

namespace Kent\Swsocket\Enum;

interface LoginEnum
{
    const LOGIN_VAR_NAME = 'loginType';  // 类文件设置 登录保护属性名称 这个不能改

    const LOGIN_TYPE_NEED = 'need';  // 必须登录, 否则返回请求登录信息，一定有用户信息
    const LOGIN_TYPE_HOPE = 'hope';  // 如果有登录信息就登录，没有就不登录 不一定有用户信息
    const LOGIN_TYPE_UNNDEED = 'unneed';  // 不需要登录，不会有用户信息
}
