<?php

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

if (!function_exists('swRandStr')) {
    /**
     * 随机码
     * @param int $len 随机位数
     * @param string $addChars 额外字符
     * @return bool|string
     */
    function swRandStr($len = 6, $addChars = '')
    {
        // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
        $chars = 'abcdefghijkmnpqrstuvwxyz23456789' . $addChars;
        //重复5次
        $chars = str_repeat($chars, 5);
        //打乱字符串
        $chars = str_shuffle($chars);
        //截取字符串
        $str = substr($chars, 0, $len);
        return $str;
    }
}

if (!function_exists('swCamelKeyCase')) {
    /**
     * 把数组的key全变成小驼峰命名
     *
     * @param $param
     * @return array
     */
    function swCamelKeyCase($param)
    {
        if ($param instanceof LengthAwarePaginator || $param instanceof Paginator) {
            $arrTmp = $param->toArray();
            $arr = $arrTmp['data'];
        } elseif ($param instanceof Collection) {
            $arr = $param->toArray();
        } elseif ($param instanceof Model) {
            $arr = $param->toArray();
        } elseif (is_array($param)) {
            $arr = $param;
        } else {
            return $param;
        }
        $arrNew = [];
        foreach ($arr as $k => $v) {
            $newK = camel_case($k);
            $arrNew[$newK] = swCamelKeyCase($v);
        }
        return $arrNew;
    }
}

if (!function_exists('swSnakeKeyCase')) {
    /**
     * 把数组的key全变成下划线命名
     *
     * @param $param
     * @return array
     */
    function swSnakeKeyCase($param)
    {
        if ($param instanceof LengthAwarePaginator || $param instanceof Paginator) {
            $arrTmp = $param->toArray();
            $arr = $arrTmp['data'];
        } elseif ($param instanceof Collection) {
            $arr = $param->toArray();
        } elseif ($param instanceof Model) {
            $arr = $param->toArray();
        } elseif (is_array($param)) {
            $arr = $param;
        } else {
            return $param;
        }
        $arrNew = [];
        foreach ($arr as $k => $v) {
            $newK = snake_case($k);
            $arrNew[$newK] = swSnakeKeyCase($v);
        }
        return $arrNew;
    }
}


