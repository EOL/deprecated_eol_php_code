<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = $GLOBALS['db_connection'];

ini_set('display_errors', true);





$he_id = 0;
$result = $mysqli->query("SELECT MAX(id) max FROM harvest_events WHERE resource_id=15");
if($result && $row=$result->fetch_assoc())
{
    $he_id = $row['max'];
}
if(!$he_id) exit;

$mysqli->begin_transaction();
$result = $mysqli->query("SELECT do.id, do.identifier, do.object_title FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE dohe.harvest_event_id=$he_id AND do.data_type_id=8");
while($result && $row=$result->fetch_assoc())
{
    $id = $row['id'];
    $identifier = $row['identifier'];
    if(preg_match("/^([0-9]+)_/", $identifier, $arr))
    {
        $identifier = $arr[1];
        $object_title = $mysqli->escape($row['object_title']);
        $result2 = $mysqli->query("SELECT id, object_cache_url FROM data_objects WHERE id!=$id AND identifier='$identifier' AND object_title='$object_title' ORDER BY id desc LIMIT 1");
        if($result2 && $row2=$result2->fetch_assoc())
        {
            $image_id = $row2['id'];
            $thumbnail_cache_url = $row2['object_cache_url'];
            echo "$id :: $image_id :: $thumbnail_cache_url\n";
            $mysqli->update("UPDATE data_objects SET thumbnail_cache_url=$thumbnail_cache_url WHERE id=$id");
        }
    }
}
$mysqli->end_transaction();


?>

