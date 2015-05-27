<?php
namespace php_active_record;

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
    
    public static function add($key, $value, $expire = 600)
    {
        if(!self::connected())
        {
            trigger_error("Memcached: not connected", E_WARNING);
            return false;
        }
        return $GLOBALS['memcached_connection']->add($key, $value, false, $expire);
    }
    
    public static function set($key, $value, $expire = 600)
    {
        if(!self::connected())
        {
            trigger_error("Memcached: not connected", E_WARNING);
            return false;
        }
        if(self::get($key))
        {
            return self::replace($key, $value, $expire);
        }
        return $GLOBALS['memcached_connection']->set($key, $value, false, $expire);
    }
    
    public static function replace($key, $value, $expire = 600)
    {
        if(!self::connected())
        {
            trigger_error("Memcached: not connected", E_WARNING);
            return false;
        }
        return $GLOBALS['memcached_connection']->replace($key, $value, false, $expire);
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
        $value = $GLOBALS['memcached_connection']->flush();
        sleep(1);
        return $value;
    }
    
    public static function connect()
    {
        // if there was any open connection to memcached - close it
        self::close();
        // caching is not enabled - so cannot connect
        if(!@$GLOBALS['ENV_ENABLE_CACHING']) {
            // This debug was being called too often.
        	// debug("Caching is not enabled");
        	return false;
        }
        // memcached is not configured - so cannot connect
        if(!@$GLOBALS['ENV_MEMCACHED_SERVER']){
            // This debug was being called too often.
        	// debug("memcached is not configured");
        	return false;
        }
        
        $memcached_connection = new Memcache;
        $success = @$memcached_connection->connect($GLOBALS['ENV_MEMCACHED_SERVER'], 11211);
        if(!$success)
        {
            // could not connect
            $GLOBALS['memcached_connection'] = null;
            debug("Could not connect");
            return false;
        }
        $GLOBALS['memcached_connection'] = $memcached_connection;
        if(@!$GLOBALS['memcached_connection']) {
        	debug("memcached connection error");
        	return false;
        }
        return true;
    }
    
    public static function connected()
    {
        if(@!$GLOBALS['memcached_connection']) {
        	debug("memcached connection error");
        	return false;
        }
        $stats = @$GLOBALS['memcached_connection']->getStats();
        if(!is_array($stats)) {
        	debug("No stats array found");
        	return false;
        }
        return true;
    }
    
    public static function close()
    {
        if(@$GLOBALS['memcached_connection'])
        {
            $GLOBALS['memcached_connection']->close();
            unset($GLOBALS['memcached_connection']);
        }
    }
}

?>
