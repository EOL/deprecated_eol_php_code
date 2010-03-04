<?php

class Memcached
{
    public static function get($key)
    {
        if(!self::connected())
        {
            trigger_error("Memcached: not connected", E_WARNING);
            return false;
        }
        return $GLOBALS['memcached_connection']->get($key);
    }
    
    public static function add($key, $value, $expire = 10)
    {
        if(!self::connected())
        {
            trigger_error("Memcached: not connected", E_WARNING);
            return false;
        }
        return $GLOBALS['memcached_connection']->add($key, $value, false, $expire);
    }
    
    public static function set($key, $value, $expire = 10)
    {
        if(!self::connected())
        {
            trigger_error("Memcached: not connected", E_WARNING);
            return false;
        }
        return $GLOBALS['memcached_connection']->set($key, $value, false, $expire);
    }
    
    public static function delete($key, $timeout = 0)
    {
        if(!self::connected())
        {
            trigger_error("Memcached: not connected", E_WARNING);
            return false;
        }
        return $GLOBALS['memcached_connection']->set($key, false);
        
        // apparently the actual delete method is broken as of right now: http://php.net/manual/en/function.memcache-delete.php
        //return $GLOBALS['memcached_connection']->delete($key, $timeout);
    }
    
    public static function flush()
    {
        if(!self::connected())
        {
            trigger_error("Memcached: not connected", E_WARNING);
            return false;
        }
        return $GLOBALS['memcached_connection']->flush();
    }
    
    public static function connected()
    {
        if(@!$GLOBALS['memcached_connection'] && @$GLOBALS['ENV_MEMCACHED_SERVER'])
        {
            $memcached_connection = new Memcache;
            $memcached_connection->connect($GLOBALS['ENV_MEMCACHED_SERVER'], 11211);
            $GLOBALS['memcached_connection'] = $memcached_connection;
            if(@!$GLOBALS['memcached_connection']) return false;
        }
        return true;
    }
}

?>