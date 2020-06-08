# Instantiator

This library provides a way of easy to set up websocket.

## Installation

The suggested installation method is via [composer](https://getcomposer.org/):

```sh
composer require "kentczhy/swsocket"
```

## Usage

PHP extension Swoole > 4.2.0 needs to be installed

config/app.php

\Kentczhy\Swsocket\Providers\SwsocketServiceProvider::class


```shell script
# add config swsocket.php and copy html test file

php artisan vendor:publish
 
# start server

php artisan swoole:server
```
