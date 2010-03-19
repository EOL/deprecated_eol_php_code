<?php




/*
===================================
    String functions
*/

function to_camel_case($str)
{
    $str = str_replace('_', ' ', $str);
    $str = ucwords($str);
    $str = str_replace(' ', '', $str);
    return $str;
}

function is_camel_case($str)
{
    if(preg_match("/^[A-Z][A-Za-z]*$/", $str)) return true;
    return false;
}

function to_underscore($str)
{
    $str = preg_replace('/([A-Z])/', '_' . strtolower('\\1'), $str);
    $str = preg_replace('/^_/', '', $str);
    $str = strtolower($str);
    return $str;
}

function is_underscore($str)
{
    if(preg_match("/^[a-z\/]+(_[a-z\/]+)*$/", $str)) return true;
    return false;
}

function to_singular($str)
{
    if(preg_match("/^(.*)(ies)$/", $str, $arr)) $str = $arr[1] . 'y';
    elseif(preg_match("/^(.*)(oes)$/", $str, $arr)) $str = $arr[1] . 'o';
    elseif(preg_match("/^(.*)(s)$/", $str, $arr)) $str = $arr[1];
    
    return $str;
}

function to_plural($str)
{
    if(preg_match("/^(.*)(y)$/", $str, $arr)) $str = $arr[1] . 'ies';
    elseif(preg_match("/^(.*)(o)$/", $str, $arr)) $str = $arr[1] . 'oes';
    else $str .= 's';
    
    return $str;
}

function display($str)
{
    if(@$GLOBALS['ENV_DEBUG_TO_FILE']) fwrite($GLOBALS['ENV_DEBUG_FILE_HANDLE'], str_pad(time_elapsed(), 12, ' ', STR_PAD_LEFT) . ' -> ' . $str . "\n");
    else
    {
        echo "$str<br>\n";
        flush();
    }
}

function write_to_log($str)
{
    if(@$GLOBALS['ENV_DEBUG_TO_FILE']) fwrite($GLOBALS['ENV_DEBUG_FILE_HANDLE'], str_pad(time_elapsed(), 12, ' ', STR_PAD_LEFT) . ' -> ' . $str . "\n");
}

function debug($string)
{
    if(@$GLOBALS['ENV_DEBUG'])
    {
        display($string . ' :: [' . get_last_function(2) . ']');
    }
}

function mysql_debug($string)
{
    if(@$GLOBALS['ENV_MYSQL_DEBUG'])
    {
        display($string . ' :: [' . get_last_function(3) . ']');
    }
}








/*
===================================
    Misc
*/

function render_view($view, $parameters = NULL, $return = false)
{
    $filename = DOC_ROOT . 'app/views/' . $view . '.php';
    if(file_exists($filename))
    {
        if(is_array($parameters)) extract($parameters);
        
        ob_start();
        include $filename;
        $contents = ob_get_contents();
        ob_end_clean();
        
        if($return) return $contents;
        
        echo $contents;
        return true;
    }
    
    trigger_error('Unknown view `' . $view . '` in '.get_last_function(1), E_USER_ERROR);
}

function render_template($filename, $parameters = NULL, $return = false)
{
    $filename = "templates/" . $filename . ".php";
    if(is_file($filename))
    {
        if(is_array($parameters)) extract($parameters);
        
        ob_start();
        include $filename;
        $contents = ob_get_contents();
        ob_end_clean();
        
        if($return) return $contents;
        else echo $contents;
    }else print "template $filename does not exist";
    
    return false;
}


function require_module($module)
{
    $module_path = DOC_ROOT . "classes/modules/$module/module.php";
    require_once($module_path);
}

function require_vendor($module)
{
    $module_path = DOC_ROOT . "vendor/$module/module.php";
    require_once($module_path);
}

function require_library($library)
{
    $library_path = DOC_ROOT . "lib/$library.php";
    require_once($library_path);
}


function print_pre($arr)
{
    echo '<pre>';
    print_r($arr);
    echo '</pre>';
}

function start_timer()
{
    return time_elapsed();
}

function time_elapsed()
{
    static $a;
    if(!isset($a)) $a = microtime(true);
    return (string) round(microtime(true)-$a, 6);
}

function get_last_function($index = 1)
{
    $backtrace = debug_backtrace();
    $line = @$backtrace[$index]['line'];
    $file = @$backtrace[$index]['file'];
    
    return "$file [$line]";
}

function shutdown_check()
{
    $isError = false;
    if($error = error_get_last())
    {
        switch($error['type'])
        {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            $isError = true;
            break;
        }
    }
    if ($isError) debug("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
    else debug("Completed ". $_SERVER['SCRIPT_FILENAME']);
    
    // close any open database connections
    if($GLOBALS['db_connection']->transaction_in_progress) $GLOBALS['db_connection']->rollback();
    if($GLOBALS['ENV_MYSQL_DEBUG']) debug("\n\nClosing database connection");
    
    // close the log file handle
    if($GLOBALS['ENV_DEBUG'] && $GLOBALS['ENV_DEBUG_TO_FILE'] && @$GLOBALS['ENV_DEBUG_FILE_HANDLE'])
    {
        fclose($GLOBALS['ENV_DEBUG_FILE_HANDLE']);
    }
}

function load_fixtures($environment = "test")
{
    if(@$GLOBALS['ENV_NAME'] != $environment) return false;
    
    $files = get_fixture_files();
    
    $fixture_data = (object) array();
    
    $GLOBALS['db_connection']->begin_transaction();
    foreach($files as $table)
    {
        $fixture_data->$table = (object) array();
        
        $rows = Horde_Yaml::loadFile(DOC_ROOT . "tests/fixtures/$table.yml");
        foreach($rows as $id => $row)
        {
            $fixture_data->$table->$id = (object) array();
            
            foreach($row as $key => $val)
            {
                if(!is_field_in_table($key, $table)) unset($row[$key]);
            }
            
            $query = "INSERT INTO $table (`";
            $query .= implode("`, `", array_keys($row));
            $query .= "`) VALUES ('";
            $query .= implode("', '", $row);
            $query .= "')";
            
            $GLOBALS['db_connection']->insert($query);
            
            foreach($row as $k => $v)
            {
                $fixture_data->$table->$id->$k = $v;
            }
        }
    }
    $GLOBALS['db_connection']->end_transaction();
    
    return $fixture_data;
}

function get_fixture_files()
{
    $files = array();
    
    $dir = DOC_ROOT . 'tests/fixtures/';
    if($handle = opendir($dir))
    {
       while(false !== ($file = readdir($handle)))
       {
           if(preg_match("/^(.*)\.yml$/",trim($file), $arr))
           {
               $files[] = $arr[1];
           }
       }
       closedir($handle);
    }
    
    return $files;
}

function read_dir($dir)
{
    $files = array();
    if($handle = opendir($dir))
    {
       while(false !== ($file = readdir($handle)))
       {
           $files[] = trim($file);
       }
       closedir($handle);
    }
    sort($files);
    return $files;
}

function is_field_in_table($field, $table)
{
    $fields = table_fields($table);
    foreach($fields as $f)
    {
        if($f == $field) return true;
    }
    return false;
}

/* currently storing field information in memory only */
function table_fields($table)
{
    if($cache = MemoryCache::get('table_fields_' . $table)) return $cache;
    
    $fields = array();
    
    $result = $GLOBALS['db_connection']->query('SHOW fields FROM `' . $table . '`');
    while($result && $row=$result->fetch_assoc())
    {
        $fields[] = $row["Field"];
    }
    if($result && @$result->num_rows) $result->free();
    
    MemoryCache::set('table_fields_' . $table, $fields);
    
    return $fields;
}

function cache_model($table)
{
    // there is no memcached connection and ENV_CACHE is not set to memory
    if(@!$GLOBALS['memcached_connection'] && @$GLOBALS['ENV_CACHE'] != 'memory') return false;
    if(@$GLOBALS['no_cache'][$table]) return false;
    return true;
}

function trim_namespace($class)
{
    return preg_replace("/^(.*)\\\/", "", $class);
}

function sleep_production($seconds)
{
    if(@$GLOBALS['ENV_NAME'] == 'production')
    {
        sleep($seconds);
    }
}

?>