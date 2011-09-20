<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];


$mysqli_production = load_mysql_environment('production');


//$result = $mysqli->query("SELECT * FROM users WHERE id=50137");
//if($result && $row=$result->fetch_assoc()) print_r($row);

//$result = $mysqli_production->query("SELECT * FROM users WHERE id=50137");
//if($result && $row=$result->fetch_assoc()) print_r($row);




//exit;



$table_fields_to_update = array();
$table_fields_to_update['users'] = array('logo_cache_url', 'logo_file_name', 'logo_content_type', 'logo_file_size', 'tag_line', 'agreed_with_terms', 'bio', 'admin');

foreach($table_fields_to_update as $table => $fields)
{
    $result = $mysqli->query("SELECT id, `". implode("`, `", $fields)."` FROM `$table` where id<=49834");
    while($result && $row=$result->fetch_assoc())
    {
        $skip_logos = false;
        $updates = array();
        foreach($row as $field => $value)
        {
            if($field == 'id') continue;
            if($field == 'logo_cache_url' && $value < 1000000)
            {
                $skip_logos = true;
                continue;
            }elseif($skip_logos && in_array($field, array('logo_file_name', 'logo_content_type', 'logo_file_size'))) continue;
            if($value) $updates[] = "`$field` = '". $mysqli->escape($value)."'";
        }
        if($updates)
        {
            $query = "UPDATE $table SET ". implode(", ", $updates) ." WHERE id = ". $row['id'].";";
            echo $query."\n";
            $mysqli_production->query($query);
        }
    }
}



?>
