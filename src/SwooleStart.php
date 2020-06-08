<?php
namespace Kentczhy\Swsocket;

use Illuminate\Console\Command;
use Swoole\Coroutine;
use Swoole\Database\RedisConfig;
use Swoole\Database\RedisPool;
use Swoole\Runtime;

/**
 * Created by PhpStorm.
 * User: kent
 */
class SwooleStart extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:server';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "swoole:server";

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->checkVersion();
        $host = config('swsocket.host');
        $port = config('swsocket.port');
        if ($host == '' || $port == '') {
            $this->error("host 或者 port 不能为空");
            exit;
        }
        $this->checkPort($port);
        // 初始化参数
        $wServer = $this->_initSwoole($host, $port);
        //启动服务
        $wServer->start();
    }

    protected function checkPort($port)
    {
        if ($port == '') {
            $this->error("请在 配置文件 swsocket 配置指定端口");
            exit;
        }
        $arrNotAllowPort = [80, 11211, 27017, 6379];
        if (in_array($port, $arrNotAllowPort)) {
            $this->error("端口不允许指定为".implode(',', $arrNotAllowPort));
            exit;
        }
    }

    protected function checkVersion()
    {
        if (!in_array('swoole', get_loaded_extensions())) {
            $this->error('请安装 >= 4.2.0 版本的 swoole');
            exit;
        }
        if (version_compare('4.2.0', swoole_version(), '>')) {
            $this->error('您的swoole版本当前为 '.swoole_version().' 应该大于等于 4.2.0');
            exit;
        }
    }

    protected function _initSwoole($host, $port)
    {
        Runtime::enableCoroutine();
        // todo 初始化战场 删除指定前缀的redis key 清扫旧战场 抽离出去
        $arrConfig = config('swsocket.swoole');
        if ($arrConfig['ssl_key_file'] != '' && $arrConfig['ssl_cert_file'] != '') {
            $wServer = new \swoole_websocket_server($host, $port, SWOOLE_BASE, SWOOLE_SOCK_TCP | SWOOLE_SSL);
        } else {
            if (isset($arrConfig['ssl_key_file'])) {
                unset($arrConfig['ssl_key_file']);
            }
            if (isset($arrConfig['ssl_cert_file'])) {
                unset($arrConfig['ssl_cert_file']);
            }
            $wServer = new \swoole_websocket_server($host, $port);
        }
        //初始化参数
        $wServer->set($arrConfig);

        // 添加redis连接池
        $redisConf = config('database.redis.' . config('swsocket.redis'));
        \Co\run(function () use (&$wServer, $redisConf) {
            $wServer->redisPool = new RedisPool((new RedisConfig)
                ->withHost($redisConf['host'])
                ->withPort((int) $redisConf['port'])
                ->withAuth('')
                ->withDbIndex((int) $redisConf['database'])
                ->withTimeout(1)
            );
            $countRedis = config('swsocket.redis_connection_count') ?? 20;
            for ($c = $countRedis; $c--;) {
                \go(function () use ($wServer) {//创建100个协程
                    $redis = $wServer->redisPool->get();
                    $result = $redis->set('connection', 'go');
                    if (!$result) {
                        throw new \RuntimeException('Set failed');
                    }
                    $result = $redis->get('connection');
                    if ($result !== 'go') {
                        throw new \RuntimeException('Get failed');
                    }
                    $wServer->redisPool->put($redis);
                });
            }
        });

        // 统一添加启动进程
        if (class_exists(config('swsocket.process_class'))) {
            $refSwooleProcess = new \ReflectionClass(config('swsocket.process_class'));
            $arrOReflectionMethod = $refSwooleProcess->getMethods(\ReflectionMethod::IS_PUBLIC); //返回类中所有公共方法
            if (!empty($arrOReflectionMethod)) {
                foreach ($arrOReflectionMethod as $oReflectionMethod) {
                    $method = $oReflectionMethod->name;
                    $class = $oReflectionMethod->class;
                    if (in_array($method, ['getInstance', '__construct'])) {
                        continue;
                    }
                    $oProcess = new \swoole_process(function($process) use ($wServer, $class, $method) {
                        $class::$method($wServer, $process);
                    });
                    $wServer->addProcess($oProcess);
                }
            }
        }

        // 添加方法
        if (class_exists(config('swsocket.swoole_func_class'))) {
            $classSwooleFunc = config('swsocket.swoole_func_class');
            $oSwooleFunc = new $classSwooleFunc();
        } else {
            $oSwooleFunc = new \Kentczhy\Swsocket\SwooleFunc();
        }
        $wServer->on('open', [$oSwooleFunc, 'open']);
        $wServer->on('message', [$oSwooleFunc, 'message']);
        $wServer->on('close', [$oSwooleFunc, 'close']);
        return $wServer;
    }
}
