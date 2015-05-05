<?php
namespace php_active_record;

// system('clear');
// define('ENVIRONMENT', 'staging_production');
// //define('ENVIRONMENT', 'integration');
include_once(dirname(__FILE__) . "/../config/environment.php");

$mysqli =& $GLOBALS['mysqli_connection'];

$dump_to_location = dirname(__FILE__) . "/../temp/modified_mysqldump_". date("Y") ."_". date("m") ."_". date("d") .".sql";
$modified_dump_tmp_file = dirname(__FILE__) . "/../temp/modified_mysqldump_tmp_". date("Y") ."_". date("m") ."_". date("d") .".sql";

$fields_to_modify = array(
    'agent_contacts' => array(
        'full_name'         => 'full name',
        'given_name'        => 'name',
        'family_name'       => 'name',
        'homepage'          => 'url',
        'email'             => 'email',
        'telephone'         => 'phone',
        'address'           => 'address'
    ),
    'agents' => array(
        'full_name'         => 'full name',
        'display_name'      => 'full name',
        'homepage'          => 'url',
        'email'             => 'email',
        'username'          => 'username',
        'hashed_password'   => 'password',
        'address'           => 'address'
    ),
    'content_partner_agreements' => array(
        'ip_address'        => 'ip'
    )
);
$tables_to_modify = array_keys($fields_to_modify);
$tables_to_export = array();

$result = $mysqli->query("SHOW TABLES");
while($result && $row=$result->fetch_row())
{
    $table = $row[0];
    if(!in_array($table, $tables_to_modify)) $tables_to_export[] = $table;
}

$command = "mysqldump --host=".$GLOBALS['environments'][ENVIRONMENT]['host']." --user=".$GLOBALS['environments'][ENVIRONMENT]['username']." --password=".$GLOBALS['environments'][ENVIRONMENT]['password']." --databases ".$GLOBALS['environments'][ENVIRONMENT]['database']." --tables ".implode(" ", $tables_to_export)." > $dump_to_location";
exec($command);

$result->data_seek(0);
while($result && $row=$result->fetch_row())
{
    $table = $row[0];
    if(in_array($table, $tables_to_modify))
    {
        $insert_query = "SET @saved_cs_client = @@character_set_client;\n";
        $insert_query .= "SET character_set_client = utf8;\n";
        
        $result2 = $mysqli->query("SHOW CREATE TABLE `$table`");
        if($result2 && $row2=$result2->fetch_row())
        {
            $insert_query .= $row2[1].";\n";
        }
        
        $insert_query .= "SET character_set_client = @saved_cs_client;\n";
        $insert_query .= "LOCK TABLES `visibilities` WRITE;\n";
        $insert_query .= "INSERT INTO `$table` VALUES ";
        
        $result2 = $mysqli->query("SELECT * FROM `$table`");
        while($result2 && $row2=$result2->fetch_assoc())
        {
            foreach($row2 as $field => $value)
            {
                if(array_key_exists($field, $fields_to_modify[$table])) $row2[$field] = get_fake_data($fields_to_modify[$table][$field]);
            }
            
            $insert_query .= "('".implode("','", $row2) ."'),";
        }
        $insert_query = substr($insert_query, 0, -1) . ";\n";
        $insert_query .= "UNLOCK TABLES;\n";
        
        if(!($FILE = fopen($modified_dump_tmp_file, 'w+')))
        {
           debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$modified_dump_tmp_file);
           return;
        }
        fwrite($FILE, "\n\n".$insert_query."\n\n");
        fclose($FILE);
        
        exec("cat >>$dump_to_location $modified_dump_tmp_file");
        exec("rm $modified_dump_tmp_file");
    }
}

function get_fake_data($type)
{        
    if($type=='email')      $format = "(8,'C')|@|(3,'L')|.com|";
    if($type=='address')    $format = "(3,'N')| |(15,'L')| |(10,'L')|, |(15,'L')| |(4,'N')";
    if($type=='full name')  $format = "(8,'L')| |(1,'L')|.| |(10,'L')";        
    if($type=='name')       $format = "(15,'L')";
    if($type=='phone')      $format = "(3,'N')|-|(3,'N')|-|(4,'N')";    
    if($type=='url')        $format = "http://www.|(8,'L')|.|(3,'L')|/|(8,'L')|.|(3,'L')";    
    if($type=='username')   $format = "(6,'C')";
    if($type=='password')   $format = "(6,'C')";
    if($type=='ip')         $format = "(3,'N')|.|(3,'N')|.|(3,'N')|.|(2,'N')";
    
    /*
    L = literal e.g. a, b, z
    N = numeric e.g. 0,4,9
    C = combo e.g. 9,z,e,4,a
    */
        
    /* future version can have selection for $with_space or $fixed_length, not yet on this version */    
        
    $format = explode("|", $format);   
    $string = "";    
    foreach($format as $task)
    { 
        if(substr($task,0,1)=="(")    
        {   
           $task = str_ireplace("(", "", $task);
           $task = str_ireplace(")", "", $task);
           $task = str_ireplace("'", "", $task);           
           $arr = explode(",", $task);           
           $string .= gets($arr[0],$arr[1]);
        }
        else $string .= $task;
    }    
    return $string;    
}
function gets($length,$type)
{
    if($type == 'L') {$min=97;$max=122;}
    if($type == 'N') {$min=48;$max=57;}    
    $str="";
    for ($i = 1; $i <= $length; $i++) 
    {   
        if($type == 'C') 
        {   if(rand(0,1)==1) {$min=97; $max=122;}
            else             {$min=48; $max=57;}    
        }    
        $k = rand($min,$max);
        $str .= chr($k);
    }
    return $str;
}

?>