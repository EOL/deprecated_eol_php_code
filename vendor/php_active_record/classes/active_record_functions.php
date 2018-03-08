<?php
namespace php_active_record;




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
    elseif(preg_match("/^(.*)(ses)$/", $str, $arr)) $str = $arr[1] . 's';
    elseif(preg_match("/^(.*)(s)$/", $str, $arr)) $str = $arr[1];

    return $str;
}

function to_plural($str)
{
    if(preg_match("/^(.*)(y)$/", $str, $arr)) $str = $arr[1] . 'ies';
    elseif(preg_match("/^(.*)(s)$/", $str, $arr)) $str = $arr[1] . 'ses';
    elseif(preg_match("/^(.*)(o)$/", $str, $arr)) $str = $arr[1] . 'oes';
    else $str .= 's';

    return $str;
}

function display($str)
{
    if($GLOBALS['ENV_DEBUG_TO_FILE'] && @$GLOBALS['ENV_DEBUG_FILE_HANDLE'])
    {
        fwrite($GLOBALS['ENV_DEBUG_FILE_HANDLE'], date('m/d H:i:s') .":: $str\n");
    }else
    {
        echo "$str<br>\n";
        flush();
    }
}

function write_to_log($str)
{
    if($GLOBALS['ENV_DEBUG_TO_FILE'] && @$GLOBALS['ENV_DEBUG_FILE_HANDLE'])
    {
        fwrite($GLOBALS['ENV_DEBUG_FILE_HANDLE'], date('m/d H:i:s') .":: $str\n");
    }
}

function write_to_resource_harvesting_log($str)
{
    if ( ($GLOBALS['ENV_NAME'] != 'test') && isset($GLOBALS['currently_harvesting_resource_id']) ) {
        $resource_id = $GLOBALS['currently_harvesting_resource_id'];
        $file_handler = fopen(DOC_ROOT . 'log/' . $resource_id .  ".log", "a");
        if(!$file_handler)
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT . 'log/' . $resource_id .  ".log");
          return;
        }else {
            fwrite($file_handler, date('m/d H:i:s') .":: $str\n");
            fclose($file_handler); // TODO: Is this necessary? When do we clean up FHs?
        }
    } else {
        write_to_log($str);
    }
}

function debug($string)
{
    if($GLOBALS['ENV_NAME']=='test' || $GLOBALS['ENV_DEBUG'])
    {
        display($string . ' :: [' . get_last_function(3) . ']');
    }
    write_to_resource_harvesting_log($string);
}

function mysql_debug($string)
{
    if($GLOBALS['ENV_NAME']=='test' || ($GLOBALS['ENV_MYSQL_DEBUG'] && $GLOBALS['ENV_DEBUG']))
    {
        display($string . ' :: [' . get_last_function(4) . ']');
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

function time_elapsed($reset = false)
{
    static $a;
    if(!isset($a) || $reset) $a = microtime(true);
    return (string) round(microtime(true)-$a, 6);
}

function memory_get_usage_in_mb()
{
    return round(memory_get_usage() / 1024 / 1024, 2);
}

function get_last_function($index = 1)
{
    $backtrace = debug_backtrace();
    $line = @$backtrace[$index]['line'];
    $file = @$backtrace[$index]['file'];
    $file = str_replace(DOC_ROOT, "", $file);
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
    if($GLOBALS['ENV_MYSQL_DEBUG']) debug("Closing database connection\n");

    // close the log file handle
    if($GLOBALS['ENV_DEBUG'] && $GLOBALS['ENV_DEBUG_TO_FILE'] && @$GLOBALS['ENV_DEBUG_FILE_HANDLE'])
    {
        fclose($GLOBALS['ENV_DEBUG_FILE_HANDLE']);
    }

    //ensure to update the resource hierarchy_entries_count
    if( isset($GLOBALS['currently_harvesting_resource_id'])  )
    {
        $resource= Resource::find($GLOBALS['currently_harvesting_resource_id']);
        $resource->update_hierarchy_entries_count();
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

        $rows = \Spyc::YAMLLoad(DOC_ROOT . "tests/fixtures/$table.yml");
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
    if($cache = Cache::get('table_fields:' . $table)) return $cache;

    $fields = array();

    $result = $GLOBALS['db_connection']->query('SHOW fields FROM `' . $table . '`');
    while($result && $row=$result->fetch_assoc())
    {
        $fields[] = $row["Field"];
    }
    if($result && @$result->num_rows) $result->free();

    Cache::set('table_fields:' . $table, $fields);

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

function usleep_production($microseconds)
{
    //1000000 us = 1s
    if(@$GLOBALS['ENV_NAME'] == 'production')
    {
        usleep($microseconds);
    }
}


function random_digits($number, $start = 0)
{
    if(!$number) return null;
    // adding a 1 in front so we can get zeros in the first position
    $start = "1".$start.str_repeat(0, $number-1);
    $end = "1".str_repeat(9, $number);
    $random = rand($start, $end);
    // chop off the 1 to get the real random number
    return substr($random, 1);
}

function temp_filepath($relative_from_root = false, $extension = 'file')
{
    if($relative_from_root) $prefix = "";
    else $prefix = DOC_ROOT;

    $filepath = $prefix ."tmp/tmp_". random_digits(5) .".$extension";
    // make sure the name is unique
    while(glob($filepath))
    {
        $filepath = $prefix ."tmp/tmp_". random_digits(5) .".$extension";
    }
    return $filepath;
}

function create_temp_dir($prefix = 'dir')
{
    $filepath = DOC_ROOT ."tmp/". $prefix ."_". random_digits(5);
    // make sure the name is unique
    while(glob($filepath))
    {
        $filepath = DOC_ROOT ."tmp/". $prefix ."_". random_digits(5);
    }
    mkdir($filepath);
    return $filepath;
}

// function delete_dir($path)
// {
//     while(glob($path))
// }


function file_randomize($path)
{
    // loop through the file finding the offset of all newlines
    $newline_positions = array(0);
    if(!($FILE = fopen($path, "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$path);
      return;
    }
    while(!feof($FILE))
    {
        if(fgets($FILE, 4096))
        {
            $newline_positions[] = ftell($FILE);
        }
    }

    // randomize the offsets and seek around the file getting the random lines
    shuffle($newline_positions);
    $new_file_path = temp_filepath();
    if(!($NEW_FILE = fopen($new_file_path, "w+")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$new_file_path);
      return;
    }
    foreach($newline_positions as $position)
    {
        fseek($FILE, $position);
        if($line = fgets($FILE, 4096))
        {
            fwrite($NEW_FILE, $line);
        }
    }
    fclose($NEW_FILE);
    fclose($FILE);

    unlink($path);
    if(copy($new_file_path, $path))
      unlink($new_file_path);
}

function get_simpletest_name()
{
    static $test_number = 0;

    $test_name = "";
    if(isset($GLOBALS['group_test']->_test_cases[$test_number]->_reporter->_test_stack[2]))
    {
        $test_name = $GLOBALS['group_test']->_test_cases[$test_number]->_reporter->_test_stack[2];
    }else
    {
        $test_number++;
        if(isset($GLOBALS['group_test']->_test_cases[$test_number]->_reporter->_test_stack[2]))
        {
            $test_name = $GLOBALS['group_test']->_test_cases[$test_number]->_reporter->_test_stack[2];
        }
    }

    return $test_name;
}

function echo_each($array)
{
    while(list($key, $val) = each($array))
    {
        echo $val."\n";
    }
}

function merge_arrays(&$from_array, &$to_array)
{
    foreach($from_array as $key => $val)
    {
        $to_array[] = $val;
    }
}

function recursive_rmdir($dir)
{
    if(!trim($dir)) return;
    if(trim($dir) == "/") return;
    recursive_rmdir_contents($dir);
    @rmdir($dir);
}

function recursive_rmdir_contents($dir)
{
    if(!trim($dir)) return;
    if(trim($dir) == "/") return;
    foreach(glob($dir . '/{,.}*', GLOB_BRACE) as $dir_or_file)
    {
        if(preg_match("/\/\.gitignore$/", $dir_or_file)) continue;
        if(preg_match("/\/\.+$/", $dir_or_file)) continue;
        if(is_dir($dir_or_file)) recursive_rmdir($dir_or_file);
        else unlink($dir_or_file);
    }
}

function wildcard_rm($prefix)
{
    if(!$prefix || $prefix[0] == '.' || strlen($prefix) < 8) return false;
    if(is_dir($prefix)) recursive_rmdir($prefix);
    foreach(glob($prefix .".*") as $filename)
    {
        unlink($filename);
    }
    if(file_exists($prefix)) unlink($prefix);
}


?>
