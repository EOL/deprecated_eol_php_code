<?php

class Cache
{
    public static function __callStatic($function, $args)
    {
        if(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', $function), $args);
        }elseif(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', $function), $args);
        }
        return false;
    }
}

?>