<?php

// swoole 配置
return [
    // database 里面的配置
    'redis' => 'swoole',
    // redis 链接数
    'redis_connection_count' => 20,

    // 启动进程类（默认，可以自己复制指定修改）
    'process_class' => 'Kent\\Swsocket\\SwooleProcess',

    // swoole 几个固定方法类（默认，可以自己复制指定修改）
    'swoole_func_class' => 'Kent\\Swsocket\\SwooleFunc',

    // socket 访问命名空间（默认，可以自己复制指定修改）
    'business_namespace' => 'Kent\\Swsocket\\Business',

    'host' => 'localhost',
    'port' => '9700',

    // swoole 的启动配置参数, ssl 一般使用nginx的反向代理
    'swoole' => [
        'daemonize'     => 0,
        'reactor_num'   => 2,
        'worker_num'    => 4,
        'backlog'       => 128,
        'max_request'   => 50,
        'dispatch_mode' => 2,
        'pid_file'      => storage_path('app/swoole.pid'),
        'heartbeat_check_interval' => 20, //启用心跳检测 每20秒遍历所有连接
        'heartbeat_idle_time' => 60, //一个连接如果60秒未向服务器发送任何数据,此连接将被强制关闭
        'ssl_key_file' => '',
        'ssl_cert_file' => '',
    ]
];
