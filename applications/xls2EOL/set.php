<?php

    include_once(dirname(__FILE__) . "/../../config/environment.php");
    $mysqli =& $GLOBALS['mysqli_connection'];

    $query="update resources set resource_status_id = 11 where id = 201";    
    $result = $mysqli->query($query);    
    $num_rows = $mysqli->affected_rows();
    print $num_rows;        
    
?>