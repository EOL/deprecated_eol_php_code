<?php
namespace php_active_record;

class ControllerBase
{
    function __construct()
    {
        $parameters = @$_GET;
        if(!$parameters) $parameters = @$_POST;
            
        if(@!$parameters["f"]) $parameters["f"] = "index";
        $function = $parameters["f"];
        
        if(method_exists($this, $function)) $return = $this->$function($parameters);
        else $return = "method doesn't exist";
        
        echo $return;
    }
}

?>