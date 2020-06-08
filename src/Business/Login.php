<?php
/**
 * Created by PhpStorm.
 * User: kent
 * Date: 31/03/2018
 * Time: 10:50
 */
namespace Kent\Swsocket\Business;

use Kent\Swsocket\Services\SwooleService;
use Main\Service\LoginTokenService;

class Login extends BusinessBase
{

    public $loginType = 'unneed';

    public function token()
    {
        if (!empty($this->params) && isset($this->params['token'])) {
            // 目前定义的名称是 token
            $token = $this->params['token'];
            $oRedis = $this->wServer->redisPool->get();
            // todo 补充业务绑定逻辑
            $arrRes = LoginTokenService::getInstance()->getUserInfoByToken($token);
            if ($arrRes['code'] == 200 && !empty($arrRes['data'])) {
                // 登录绑定 fd绑定到用户id上就可以接收消息了
                SwooleService::zadd($oRedis, $arrRes['data']['id'], $this->frame->fd);
                // fd绑定到token上
                SwooleService::zaddFdBindToken($oRedis, $token, $this->frame->fd);
                // 登录成功返回用户登录信息
                $this->wServer->redisPool->put($oRedis);
                $this->pushSuccess('登录成功', $arrRes['data']);
            } else {
                //定位到 登录页面
                $this->wServer->redisPool->put($oRedis);
                $this->throwError($arrRes['msg'], 401);
            }
        } else {
            $this->throwError('登录失败', 401);
        }
    }

    public function push()
    {
        $this->pushSuccess('登录成功', $this->params);
    }

    public function userPwd()
    {

    }

    /**
     * 退出
     */
    public function logout()
    {

    }

}
