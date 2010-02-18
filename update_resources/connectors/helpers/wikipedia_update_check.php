<?php

//define('DEBUG', true);
//define('MYSQL_DEBUG', true);
//define('DEBUG_TO_FILE', true);
define("DOWNLOAD_WAIT_TIME", "500000");

include_once(dirname(__FILE__) . "/../../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];



$GLOBALS['objects_updated'] = array();
$GLOBALS['objects_unchanged'] = array();
$GLOBALS['objects_deleted'] = array();

// get the latest harvest even for Wikipedia
$result = $mysqli->query("SELECT MAX(id) max FROM harvest_events he WHERE resource_id=80");
if($result && $row=$result->fetch_assoc())
{
    $max_he_id = $row['max'];
    
    $revision_ids = array();
    // get all DataObjects from that harvest
    $result = $mysqli->query("SELECT do.id, do.source_url FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE dohe.harvest_event_id=$max_he_id");
    while($result && $row=$result->fetch_assoc())
    {
        $data_object_id = $row['id'];
        $source_url = $row['source_url'];
        if(preg_match("/&oldid=([0-9]+)$/", $source_url, $arr))
        {
            $revision_ids[$arr[1]] = $data_object_id;
            if(count($revision_ids)==50)
            {
                check_revisions($revision_ids);
                $revision_ids = array();
            }
        }
    }
    if($revision_ids) check_revisions($revision_ids);
}

$FILE = fopen(LOCAL_ROOT . "/temp/wikipedia_unchanged.txt", "w+");
fwrite($FILE, implode("\n", $GLOBALS['objects_unchanged']));
fclose($FILE);

$FILE = fopen(LOCAL_ROOT . "/temp/wikipedia_deleted.txt", "w+");
fwrite($FILE, implode("\n", $GLOBALS['objects_deleted']));
fclose($FILE);

$FILE = fopen(LOCAL_ROOT . "/temp/wikipedia_updated.txt", "w+");
foreach($GLOBALS['objects_updated'] as $old_id => $new_id)
{
    fwrite($FILE, "$old_id\t$new_id\n");
}
fclose($FILE);









function check_revisions($revision_ids)
{
    static $count_checks = 0;
    static $count_new = 0;
    static $count_same = 0;
    $count_checks++;
    echo "CHECKING BATCH $count_checks (".Functions::time_elapsed().")\n";
    
    $url = revision_query_url(array_keys($revision_ids));
    $response_xml = Functions::get_hashed_response_fake_browser($url);
    if(!$response_xml) return false;
    foreach($response_xml->query->pages->page as $page)
    {
        $latest_revision_id = (int) $page['lastrevid'];
        $query_revision_id = (int) $page->revisions->rev['revid'];
        $data_object_id = @$revision_ids[$query_revision_id];
        
        // some record that wasn't queried for. This should never happen
        if(!$data_object_id) continue;
        // an ID asked for which doesn't currently have a page - possible deletion
        if(!isset($page['pageid'])) continue;
        
        if($latest_revision_id == $query_revision_id) $GLOBALS['objects_unchanged'][] = $data_object_id;
        else $GLOBALS['objects_updated'][$data_object_id] = $latest_revision_id;
        $revisions_seen[] = $query_revision_id;
    }
    
    // loop through everything asked for and make sure it came back, otherwise it should be deleted
    foreach($revision_ids as $revision_id => $data_object_id)
    {
        if(!in_array($revision_id, $revisions_seen)) $GLOBALS['objects_deleted'][] = $data_object_id;
    }
    echo "New:  ".count($GLOBALS['objects_updated'])."\n";
    echo "Same: ".count($GLOBALS['objects_unchanged'])."\n";
}

function revision_query_url($ids)
{
    $prefix = "http://en.wikipedia.org/w/api.php?action=query&format=xml&prop=info|revisions&revids=";
    $url = $prefix . implode($ids, "|");
    return $url;
}

?>