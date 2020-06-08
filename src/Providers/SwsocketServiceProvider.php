<?php

namespace Kentczhy\Swsocket\Providers;

use Illuminate\Support\ServiceProvider;
use Kentczhy\Swsocket\SwooleStart;

class SwsocketServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../../config/swsocket.php' => config_path('swsocket.php'),
        ]);

        // 发布测试html文件
        $this->publishes([
            __DIR__.'/../../html/resocket.html' => public_path('swsocket/resocket.html'),
            __DIR__.'/../../html/jquery.cookie.js' => public_path('swsocket/jquery.cookie.js'),
            __DIR__.'/../../html/jquery-3.3.1.min.js' => public_path('swsocket/jquery-3.3.1.min.js'),
        ]);

        // 发布命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                SwooleStart::class,
            ]);
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
