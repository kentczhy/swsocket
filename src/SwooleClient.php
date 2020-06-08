<?php
/**
 * Created by PhpStorm.
 * User: kent
 */
namespace Kentczhy\Swsocket;

use Kentczhy\Swsocket\Enum\LoginEnum;
use Illuminate\Support\Str;

/**
 * swoole 调用业务里面的方法
 *
 * Class SwooleClient
 * @package Kentczhy\Swsocket
 */
class SwooleClient
{
    public $wServer;
    public $frame;
    public $data = [];
    public $params = [];

    public function __construct($wServer, $frame)
    {
        $this->wServer = $wServer;
        $this->frame = $frame;
        if ($frame->data == 'q') {
            $this->params = $frame->data;
        } else {
            $this->params = @json_decode($frame->data, true);
        }
    }

    public function run()
    {
        if (!$this->params) {
            throw new \Exception('参数不存在', 400);
        } elseif ($this->params == 'q') {
            //发送的心跳 直接提交q过来 直接抛出返回
            throw new \Exception('a', 8);
        } elseif (!isset($this->params["cmd"]) || empty($this->params["cmd"])) {
            throw new \Exception('没有设置cmd参数或者cmd为空', 400);
        }
        $arrCmd = explode(".", $this->params['cmd']);
        if (count($arrCmd) < 2) {
            throw new \Exception('cmd参数错误', 400);
        }
        $className = Str::studly($arrCmd[0]); // 类名 驼峰法 首字母大写
        $classFunc = lcfirst(Str::studly($arrCmd[1])); //函数名 驼峰法 首字母小写
        $businessNameSpace = config('swsocket.business_namespace');
        $classFullPath = $businessNameSpace."\\".$className;
        if (!class_exists($classFullPath)) {
            throw new \Exception('非法访问', 400);
        }
        $refClass = new \ReflectionClass($classFullPath);
        $this->checkExecute($refClass, $classFunc);
        $this->execute($refClass, $classFullPath, $classFunc);
        unset($refClass);
    }

    public function checkExecute(\ReflectionClass $refClass, $classFunc)
    {
        $arrOMethods = $refClass->getMethods(\ReflectionMethod::IS_PUBLIC); //返回类中公共方法，只有公共方法才可以访问
        $arrPublicFuncName = [];
        foreach ($arrOMethods as $oMethod) {
            $arrPublicFuncName[] = $oMethod->getName();
        }
        if (!in_array($classFunc, $arrPublicFuncName)) {
            throw new \Exception('非法访问', 400);
        }
    }

    public function execute($refClass, $classFullPath, $classFunc)
    {
        $obj = new $classFullPath($this->wServer, $this->frame, $this->params);
        // 检测是否指明需要登录
        if ($refClass->hasProperty(LoginEnum::LOGIN_VAR_NAME)) {
            if ($obj->loginType != LoginEnum::LOGIN_TYPE_UNNDEED) {
                $res = $obj->checkUserLogin();
                if ($obj->loginType != LoginEnum::LOGIN_TYPE_HOPE && !$res) {
                    throw new \Exception('请先登录1', 401);
                }
            }
        } else {
            if (!$obj->checkUserLogin()) {
                throw new \Exception('请先登录2', 401);
            }
        }
        $obj->$classFunc();
        unset($obj);
    }
}
