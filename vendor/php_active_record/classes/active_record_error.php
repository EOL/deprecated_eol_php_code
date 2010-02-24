<?php

class ActiveRecordError extends Exception
{
    public static function printException(Exception $e)
    {
        echo "<b>Uncaught ". trim_namespace(get_class($e)) ."</b>: ". $e->getMessage() . "<br/>\n" .
             "in ". $e->file ."[". $e->line ."]<br/>\n" .
             "<pre>". $e->getTraceAsString() ."</pre>\n";
    }
    
    public static function handleException(Exception $e)
    {
        static::printException($e);
    }
}

?>