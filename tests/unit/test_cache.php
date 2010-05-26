<?php

class test_cache extends SimpletestUnitBase
{
    function testMemcached()
    {
        // test the Memcached connector if memcached is configured
        if(isset($GLOBALS['ENV_MEMCACHED_SERVER']))
        {
            $this->assertTrue(Memcached::connected(), "Should be able to connect to the Memcached server");
            
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
        }else echo "\nYOU DO NOT HAVE MEMCACHED CONFIGURED\n";
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
    
    function testCachePassthroughClass()
    {
        $original_value = $GLOBALS["ENV_ENABLE_CACHING"];
        $GLOBALS["ENV_ENABLE_CACHING"] = true;
        
        Cache::flush();
        Cache::add('the_key', 'the_value');
        $this->assertTrue(Cache::get('the_key') == 'the_value', 'Should be able to set a value in default cache');
        
        Cache::set('the_key', 'the_new_value');
        $this->assertTrue(Cache::get('the_key') == 'the_new_value', 'Should be able to change a value in default cache');
        
        Cache::flush();
        $this->assertFalse(Cache::get('the_key'), 'Should be able to flush default cache');
        
        Cache::set('the_key', 'another_value');
        $this->assertTrue(Cache::get('the_key') == 'another_value', 'Should be able to set a value when the key doesnt exist');
        
        Cache::set('the_key', 'yet_another_value');
        $this->assertTrue(Cache::get('the_key') == 'yet_another_value', 'Key should exist before deleting');
        Cache::delete('the_key');
        $this->assertFalse(Cache::get('the_key'), 'Key deletion should work');
        
        $GLOBALS["ENV_ENABLE_CACHING"] = $original_value;
    }
    
    function testCacheEnabling()
    {
        $original_value = $GLOBALS["ENV_ENABLE_CACHING"];
        
        $GLOBALS["ENV_ENABLE_CACHING"] = true;
        Cache::flush();
        Cache::add('the_key', 'the_value');
        $this->assertTrue(Cache::get('the_key') == 'the_value', 'Cache should work when turned on');
        
        $GLOBALS["ENV_ENABLE_CACHING"] = false;
        Cache::flush();
        Cache::add('the_key', 'the_value');
        $this->assertTrue(Cache::get('the_key') === false, 'Cache should not work when turned off');
        
        $GLOBALS["ENV_ENABLE_CACHING"] = $original_value;
    }
    
    function testCacheLanguages()
    {
        // we want memory cache for testing, and will revert to old value after test
        $saved_value = @$GLOBALS['ENV_CACHE'];
        $GLOBALS['ENV_CACHE'] = "memory";
        
        Language::insert('fr');
        Language::insert('sp');
        $language_id = Language::insert('en');
        $this->assertTrue($language_id > 0, 'Language should have an ID');
        $this->assertTrue($language_id == Language::insert('en'), 'Language shouldnt get reinserted');
        
        $GLOBALS['db_connection']->truncate_tables('test');
        $this->assertTrue($language_id == Language::insert('en'), 'Cache should be maintained');
        
        MemoryCache::flush();
        $this->assertTrue($language_id != Language::insert('en'), 'Cache flushing should work');
        
        $GLOBALS['ENV_CACHE'] = $saved_value;
    }
    
    function testCacheIgnoring()
    {
        $saved_value = @$GLOBALS['no_cache']['names'];
        
        $GLOBALS['no_cache']['names'] = false;
        $this->assertTrue(cache_model('names'), 'Names should get cached');
        
        $GLOBALS['no_cache']['names'] = true;
        $this->assertFalse(cache_model('names'), 'Names shouldnt get cached');
        
        $GLOBALS['no_cache']['names'] = $saved_value;
    }
    
}

?>