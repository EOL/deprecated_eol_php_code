<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];




$table_fields_to_update = array();
$table_fields_to_update['users'] = array('logo_cache_url', 'logo_file_name', 'logo_content_type', 'logo_file_size', 'tag_line', 'bio', 'admin');

foreach($table_fields_to_update as $table => $fields)
{
    $result = $mysqli->query("SELECT id, `". implode("`, `", $fields)."` FROM `$table`");
    while($result && $row=$result->fetch_assoc())
    {
        $updates = array();
        foreach($row as $field => $value)
        {
            if($field == 'id') continue;
            if($value) $updates[] = "`$field` = '". $mysqli->escape($value)."'";
        }
        if($updates)
        {
            $query = "UPDATE $table SET ". implode(", ", $updates) ." WHERE id = ". $row['id'].";";
            echo $query."\n";
        }
    }
}



?>