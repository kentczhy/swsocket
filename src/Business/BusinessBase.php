<?php
/**
 * Created by PhpStorm.
 * User: kent
 */
namespace Kentczhy\Swsocket\Business;

use Kentczhy\Swsocket\Services\SwooleService;
use Kentczhy\Swsocket\SwooleResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;

class BusinessBase
{
    protected $uid = 0;
    protected $userInfo = [];
    public $params = [];
    public $data = [];
    public $wServer;
    public $frame;

    public function __construct($wServer, $frame, $params)
    {
//        $frame->fd，客户端的socket id，使用$server->push推送数据时需要用到
//        $frame->data，数据内容，可以是文本内容也可以是二进制数据，可以通过opcode的值来判断
//        $frame->opcode，WebSocket的OpCode类型，可以参考WebSocket协议标准文档
//        $frame->finish， 表示数据帧是否完整，一个WebSocket请求可能会分成多个数据帧进行发送（底层已经实现了自动合并数据帧，现在不用担心接收到的数据帧不完整）
        $this->wServer = $wServer;
        $this->frame = $frame;
        $this->params = $params;
        if (isset($params['data'])) {
            $this->data = $params['data'];
        }
    }

    protected function _push($jsonData)
    {
        $connectInfo = $this->wServer->connection_info($this->frame->fd);
        if (!$connectInfo || !is_array($connectInfo)) {
            throw new \Exception('no connectInfo');
        }
        $this->wServer->push($this->frame->fd, $jsonData);
    }

    public function throwError($msg, $code = 201)
    {
        throw new \Exception($msg, $code);
    }

    public function pushSuccess($msg, $data = [], $code = 200, $meta = [])
    {
        $cmd = $this->params['cmd'];
        $jsonData = SwooleResponse::getInstance()->json($cmd, $code, $msg, $data, $meta);
        $this->_push($jsonData);
    }

    public function _getArrPageList($objects, $func = '')
    {
        $list = [];
        if (!$objects->isEmpty()) {
            $objects->each(function($value, $key) use (&$list, $func){
                if ($func != '') {
                    $list[$key] = $func($value);
                } else {
                    $list[$key] = $value;
                }
            });
        }
        return $list;
    }

    public function pushPageList($objects, $func = '', $code = 200, $msg = '获取数据成功')
    {
        $meta = [];
        if ($objects instanceof LengthAwarePaginator) {
            //如果没有传入处理好的数据则默认取出来
            $list = $this->_getArrPageList($objects, $func);
            if (empty($list)) {
                // 如有需要status则加
                $meta['have_data'] = 0;
            } else {
                $meta['have_data'] = 1;
            }
            $meta["total"] = $objects->total();
            $meta["per_page"] = $objects->perPage();
            $meta["current_page"] = $objects->currentPage();
            $meta["last_page"] = $objects->lastPage();
            $meta["next_page_url"] = $objects->nextPageUrl();
            $meta["prev_page_url"] = $objects->previousPageUrl();
            $this->pushSuccess($msg, $list, $code, $meta);
        } else {
            throw new \Exception('非page对象');
        }
    }

    public function pushSimplePageList($objects, $func = '', $code = 200, $msg = '获取数据成功')
    {
        $meta = [];
        if ($objects instanceof Paginator) {
            //如果没有传入处理好的数据则默认取出来
            $list = $this->_getArrPageList($objects, $func);
            if (empty($list)) {
                // 如有需要status则加
                $meta['have_data'] = 0;
            } else {
                $meta['have_data'] = 1;
            }
            $meta["per_page"] = $objects->perPage();
            $meta["current_page"] = $objects->currentPage();
            $meta["first_page_url"] = $objects->url(1);
            $meta["next_page_url"] = $objects->nextPageUrl();
            $meta["prev_page_url"] = $objects->previousPageUrl();
            $meta["from"] = $objects->firstItem();
            $meta["to"] = $objects->lastItem();
            $this->pushSuccess($msg, $list, $code, $meta);
        } else {
            throw new \Exception('非simple page对象');
        }
    }

    public function pushList($objects, $func = '',  $code = 200, $msg = '获取数据成功')
    {
        $meta = [];
        if ($objects instanceof Model) {
            //如果没有传入处理好的数据则默认取出来
            $list = [];
            if ($objects->isEmpty()) {
                // 如有需要status则加
            } else {
                $objects->each(function($value, $key) use (&$list, $func){
                    if ($func != '') {
                        $list[$key] = $func($value);
                    } else {
                        $list[$key] = $value;
                    }
                });
            }
            $this->pushSuccess($msg, $list, $code, $meta);
        } else {
            $this->pushSuccess($msg, $objects, $code, $meta);
        }
    }

    //检测用户是否登录
    public function checkUserLogin()
    {
        //如果登录了，fd就会绑定到uid
        $oRedis = $this->wServer->redisPool->get();
        $uid = SwooleService::getUserIdByFd($oRedis, $this->frame->fd);
        $this->wServer->redisPool->put($oRedis);
        if ($uid > 0) {
            $this->uid = $uid;
            return true;
        } else {
            return false;
        }
    }

}
