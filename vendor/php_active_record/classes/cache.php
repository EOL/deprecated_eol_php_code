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
        $args = func_get_args();
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), $args);
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), $args);
        }
        return false;
    }
    
    public static function add()
    {
        $args = func_get_args();
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), $args);
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), $args);
        }
        return false;
    }
    
    public static function set()
    {
        $args = func_get_args();
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), $args);
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), $args);
        }
        return false;
    }
    
    public static function delete()
    {
        $args = func_get_args();
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), $args);
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), $args);
        }
        return false;
    }
    
    public static function flush()
    {
        $args = func_get_args();
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('MemoryCache', __FUNCTION__), $args);
        }elseif(@$GLOBALS['memcached_connection'])
        {
            return call_user_func_array(array('Memcached', __FUNCTION__), $args);
        }
        return false;
    }
    
}

?>