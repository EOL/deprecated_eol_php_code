<?php
namespace php_active_record;

class ActiveRecordError extends \Exception
{
    public static function printException(\Exception $e)
    {
        if(@$GLOBALS['ENV_DEBUG_TO_FILE'])
        {
            debug("Uncaught ". trim_namespace(get_class($e)) ."</b>: ". $e->getMessage() . "<br/>\n" .
                 "in $e->file [$e->line]<br/>\n" .
                 "<pre>". $e->getTraceAsString());
        }
        
        echo "<b>Uncaught ". trim_namespace(get_class($e)) ."</b>: ". $e->getMessage() . "<br/>\n" .
             "in ". $e->file ."[". $e->line ."]<br/>\n" .
             "<pre>". $e->getTraceAsString() ."</pre>\n";
    }

    public static function handleException(\Exception $e)
    {
        //static::printException($e);
        self::printException($e);
    }
    
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        $replevel = error_reporting();
        
        // this would be nice to default it to all errors in the test ENV,
        // but then this bypasses suppressing errors with @ so we end up
        // seeing some notices that were intended to be hidden
        // if($GLOBALS['ENV_NAME'] == 'test') $replevel = 22527;
        
        // bitwise comparison of error number and error reporting level
        if( ($errno & $replevel) == 0 || !$errno) return true;
        
        $error_types = array (
            E_ERROR              => 'Error',
            E_WARNING            => 'Warning',
            E_PARSE              => 'Parsing Error',
            E_NOTICE             => 'Notice',
            E_CORE_ERROR         => 'Core Error',
            E_CORE_WARNING       => 'Core Warning',
            E_COMPILE_ERROR      => 'Compile Error',
            E_COMPILE_WARNING    => 'Compile Warning',
            E_USER_ERROR         => 'User Error',
            E_USER_WARNING       => 'User Warning',
            E_USER_NOTICE        => 'User Notice',
            E_STRICT             => 'E_STRICT',
            E_RECOVERABLE_ERROR  => 'Catchable Fatal Error');
        if(defined('PHP_MAJOR_VERSION') && (PHP_MAJOR_VERSION > 5 || (PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION > 3)))
        {
            $error_types[E_DEPRECATED] = 'E_DEPRECATED';
            $error_types[E_USER_DEPRECATED] = 'E_USER_DEPRECATED';
        }
        $error_type_string = isset($error_types[$errno]) ? $error_types[$errno] : 'Unknown Error Type';
        
        $error_message =  $error_type_string .": $errstr in $errfile on line $errline";
        write_to_log($error_message);
        echo "$error_message\n";
        
        // stop the script if this was a fatal error
        if(in_array($error_type_string, array('Error', 'Core Error', 'User Error')))
        {
            exit(1);
            break;
        }
       
        /* Don't execute PHP internal error handler */
        return true;
    }
}

?>