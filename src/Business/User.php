<?php
/**
 * Created by PhpStorm.
 * User: kent
 */
namespace Kentczhy\Swsocket\Business;

class User extends BusinessBase
{

    public $loginType = 'need';

    public function info()
    {
        $this->pushSuccess('success', $this->userInfo);
    }

}
