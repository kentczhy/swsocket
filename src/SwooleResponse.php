<?php
/**
 * Created by PhpStorm.
 * User: kent
 */

namespace Kent\Swsocket;

use Kent\Swsocket\Traits\ContainerTrait;

/**
 * swoole 数据格式返回
 *
 * Class SwooleResponse
 * @package Kent\Swsocket
 */
class SwooleResponse
{
    use ContainerTrait;

    public function json($cmd, $code, $msg, $data = [], $meta = [])
    {
        $arrReturn = [
            'cmd' => $cmd,
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'meta' => $meta
        ];
        $arrReturn = $this->_addMsectime($arrReturn);
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            return json_encode($arrReturn);
        }
        $arrReturn = swCamelKeyCase($arrReturn); // 统一格式
        return json_encode($arrReturn, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    protected function _addMsectime($arrReturn)
    {
        $s_arr = explode(' ', microtime());
        $arrReturn['debug'] = [
            'response_time' => $s_arr[1] + $s_arr[0]
        ];
        return $arrReturn;
    }
}
