<?php
namespace php_active_record;

class Cache
{
    public static function __callStatic($function, $args)
    {
        if($function == "restart") return self::restart();
        if(!$GLOBALS["ENV_ENABLE_CACHING"]) return false;
        if(@$GLOBALS['ENV_CACHE'] == "memory")
        {
            return call_user_func_array(array('php_active_record\MemoryCache', $function), $args);
        }elseif(@$GLOBALS['memcached_connection'])
        {
            // making sure the key is valid
            if(isset($args[0])) $args[0] = strtolower(str_replace(" ", "_", $args[0]));
            $return = call_user_func_array(array('php_active_record\Memcached', $function), $args);
            return $return;
        }
        return false;
    }
    
    public static function restart()
    {
        if(@$GLOBALS['ENV_ENABLE_CACHING'])
        {
            // connect will close the existing connection and restart it
            if(Memcached::connect()) $GLOBALS['ENV_CACHE'] = 'memcached';
            else $GLOBALS['ENV_CACHE'] = 'memory';
        }
    }
}

?>