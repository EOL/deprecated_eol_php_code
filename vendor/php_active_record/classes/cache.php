<?php

class Cache
{
    // public static function __callStatic($function, $args)
    // {
    //     if(@$GLOBALS['ENV_CACHE'] == "memory")
    //     {
    //         return call_user_func_array(array('MemoryCache', $function), $args);
    //     }elseif(@$GLOBALS['memcached_connection'])
    //     {
    //         return call_user_func_array(array('Memcached', $function), $args);
    //     }
    //     return false;
    // }
    
    public static function get()
    {
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), func_get_args());
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), func_get_args());
        }
        return false;
    }
    
    public static function add()
    {
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), func_get_args());
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), func_get_args());
        }
        return false;
    }
    
    public static function set()
    {
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), func_get_args());
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), func_get_args());
        }
        return false;
    }
    
    public static function delete()
    {
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), func_get_args());
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), func_get_args());
        }
        return false;
    }
    
    public static function flush()
    {
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), func_get_args());
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), func_get_args());
        }
        return false;
    }
    
}

?>