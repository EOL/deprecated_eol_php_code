<?php
namespace php_active_record;
define("DOWNLOAD_WAIT_TIME", "1000000");
include_once(dirname(__FILE__) . "/../../../config/environment.php");

if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $mysqli = load_mysql_environment('slave');
else $mysqli =& $GLOBALS['db_connection'];



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
    $outfile = $mysqli->select_into_outfile("SELECT do.id, do.source_url FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE dohe.harvest_event_id=$max_he_id");
    if(!($RESULT = fopen($outfile, "r")))
    {
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$outfile);
      return;
    }
    while(!feof($RESULT))
    {
        if($line = fgets($RESULT, 4096))
        {
            $fields = explode("\t", trim($line));
            $data_object_id = $fields[0];
            $source_url = $fields[1];
            if(preg_match("/index\.php\?title=(.*?)\&oldid=([0-9]+)$/", $source_url, $arr))
            {
                $title = str_replace("_", " ", $arr[1]);
                $revision_ids[$arr[2]] = $data_object_id;
                // the Wikipedia API can only take 50 records at a time
                if(count($revision_ids)==50)
                {
                    check_revisions($revision_ids);
                    $revision_ids = array();
                }
            }
        }
    }
    fclose($RESULT);
    unlink($outfile);
    if($revision_ids) check_revisions($revision_ids);
}

if(!($FILE = fopen(DOC_ROOT . "temp/wikipedia_unchanged.txt", "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT . "temp/wikipedia_unchanged.txt");
  return;
}
fwrite($FILE, "data_object_id\tpageid\trevision_date\n");
foreach($GLOBALS['objects_unchanged'] as $data_object_id => $rest)
{
    fwrite($FILE, "$data_object_id\t$rest\n");
}
fclose($FILE);

if(!($FILE = fopen(DOC_ROOT . "temp/wikipedia_deleted.txt", "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT . "temp/wikipedia_deleted.txt");
  return;
}
fwrite($FILE, implode("\n", $GLOBALS['objects_deleted']));
fclose($FILE);

if(!($FILE = fopen(DOC_ROOT . "temp/wikipedia_updated.txt", "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT . "temp/wikipedia_updated.txt");
  return;
}
fwrite($FILE, "data_object_id\tlatest_revision_id\tpageid\tcurrent title\trevision_date\n");
foreach($GLOBALS['objects_updated'] as $data_object_id => $rest)
{
    fwrite($FILE, "$data_object_id\t$rest\n");
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
    $revisions_seen = array();
    foreach($response_xml->query->pages->page as $page)
    {
        $title = (string) $page['title'];
        $pageid = (int) $page['pageid'];
        $date = (string) $page->revisions->rev['timestamp'];
        $latest_revision_id = (int) $page['lastrevid'];
        $query_revision_id = (int) $page->revisions->rev['revid'];
        $data_object_id = @$revision_ids[$query_revision_id];
        
        // some record that wasn't queried for. This should never happen
        if(!$data_object_id) continue;
        // an ID asked for which doesn't currently have a page - possible deletion
        if(!isset($page['pageid'])) continue;
        // this page has been redirected and is no longer current
        if(isset($page['redirect'])) continue;
        
        if($latest_revision_id == $query_revision_id)
        {
            $GLOBALS['objects_unchanged'][$data_object_id] = "$pageid\t$date";
        }else
        {
            $GLOBALS['objects_updated'][$data_object_id] = "$latest_revision_id\t$pageid\t$title\t$date";
        }
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
