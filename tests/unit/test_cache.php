<?php

class test_cache extends SimpletestUnitBase
{
    function testMemcached()
    {
        // test the Memcached connector if memcached is configured
        if(isset($GLOBALS['memcached_connection']))
        {
            Memcached::flush();
            Memcached::add('the_key', 'the_value');
            $this->assertTrue(Memcached::get('the_key') == 'the_value', 'Should be able to set a value in Memcached');
            
            Memcached::set('the_key', 'the_new_value');
            $this->assertTrue(Memcached::get('the_key') == 'the_new_value', 'Should be able to change a value in Memcached');
            
            Memcached::flush();
            $this->assertFalse(Memcached::get('the_key'), 'Should be able to flush Memcached');
            
            Memcached::set('the_key', 'another_value', 1);
            $this->assertTrue(Memcached::get('the_key') == 'another_value', 'Should be able to set a value when the key doesnt exist');
            $this->assertTrue(Memcached::get('the_key') == 'another_value', 'Should still be there');
            sleep(2);
            $this->assertFalse(Memcached::get('the_key'), 'Cache expiration should work');
            
            Memcached::set('the_key', 'yet_another_value');
            $this->assertTrue(Memcached::get('the_key') == 'yet_another_value', 'Key should exist before deleting');
            Memcached::delete('the_key');
            $this->assertFalse(Memcached::get('the_key'), 'Key deletion should work');
        }
    }
    
    function testMemoryCache()
    {
        MemoryCache::flush();
        MemoryCache::add('the_key', 'the_value');
        $this->assertTrue(MemoryCache::get('the_key') == 'the_value', 'Should be able to set a value in MemoryCache');
        
        MemoryCache::set('the_key', 'the_new_value');
        $this->assertTrue(MemoryCache::get('the_key') == 'the_new_value', 'Should be able to change a value in MemoryCache');
        
        MemoryCache::flush();
        $this->assertFalse(MemoryCache::get('the_key'), 'Should be able to flush MemoryCache');
        
        MemoryCache::set('the_key', 'another_value');
        $this->assertTrue(MemoryCache::get('the_key') == 'another_value', 'Should be able to set a value when the key doesnt exist');
        
        MemoryCache::set('the_key', 'yet_another_value');
        $this->assertTrue(MemoryCache::get('the_key') == 'yet_another_value', 'Key should exist before deleting');
        MemoryCache::delete('the_key');
        $this->assertFalse(MemoryCache::get('the_key'), 'Key deletion should work');
    }
}

?>