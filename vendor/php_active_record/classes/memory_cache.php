<?php
// namespace php_active_record;
// 
class MemoryCache
{
    public static function get($key)
    {
        if(!is_string($key)) return false;
        if(!isset($GLOBALS['memory_cache'][$key])) return false;
        return unserialize($GLOBALS['memory_cache'][$key]);
    }
    
    public static function add($key, $value, $expire = 10, $force = false)
    {
        if(!is_string($key)) return false;
        if(!$force && isset($GLOBALS['memory_cache'][$key])) return false;
        $GLOBALS['memory_cache'][$key] = serialize($value);
        return true;
    }
    
    public static function set($key, $value, $expire = 10)
    {
        if(!is_string($key)) return false;
        return self::add($key, $value, $expire, true);
    }
    
    public static function delete($key, $timeout = 0)
    {
        if(!is_string($key)) return false;
        if(isset($GLOBALS['memory_cache'][$key])) unset($GLOBALS['memory_cache'][$key]);
    }
    
    public static function flush()
    {
        $GLOBALS['memory_cache'] = array();
    }
}

?>