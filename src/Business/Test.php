<?php
/**
 * Created by PhpStorm.
 * User: kent
 */

namespace Kentczhy\Swsocket\Business;

class Test extends BusinessBase
{
    public $loginType = 'unneed';

    public function test()
    {
        $oRedis = $this->wServer->redisPool->get();
        $content = $oRedis->get($this->data['key']);
        $this->wServer->redisPool->put($oRedis);
        $this->pushSuccess('发送成功', $content);
    }
}
