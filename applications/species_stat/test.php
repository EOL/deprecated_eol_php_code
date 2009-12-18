<?php

define("DEBUG", false);
define("MYSQL_DEBUG", false);

require_once("../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

set_time_limit(0);


        //start user submitted do    ; per Peter M.
        $mysqli2 = load_mysql_environment('slave_eol');
        $query = "select count(udo.id) as 'total_user_text_objects' from eol_production.users_data_objects as udo 
        join eol_data_production.data_objects as do on do.id=udo.data_object_id WHERE do.published=1;";
        $result = $mysqli2->query($query);        
        $row = $result->fetch_row();			
        $user_submitted_text = $row[0];                
        $result->close();
        //end user submitted do                     
        
        print $user_submitted_text;


?>
