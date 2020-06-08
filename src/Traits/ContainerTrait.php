<?php

namespace Kent\Swsocket\Traits;

trait ContainerTrait
{
    protected static $socketContainers = [];
    /**
     * @param array $args
     * @return static
     */
    public static function getInstance($args = [])
    {
        if (is_array($args) && !empty($args)) {
            $key = static::class.md5(implode('', array_keys($args)).implode('', $args));
        } elseif (!is_array($args) && $args != '') {
            $key = static::class.$args;
        } else {
            $key = static::class;
        }
        if (!isset(self::$socketContainers[$key])
            || !self::$socketContainers[$key] instanceof self
        ) {
            self::$socketContainers[$key] = new static($args);
        }
        return self::$socketContainers[$key];
    }
}
