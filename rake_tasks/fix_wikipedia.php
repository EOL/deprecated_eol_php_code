<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");
$mysqli = &$GLOBALS['db_connection'];
exit;

$latest_harvest_event_id = get_latest_harvest_event();
if(!$latest_harvest_event_id) exit;
define('LATEST_WIKIPEDIA_HARVEST_EVENT_ID', $latest_harvest_event_id);


$data_object_guids = get_guids_of_wikipedia_objects();
$guids_of_curated_objects = get_guids_of_curated_objects($data_object_guids);
print_r($guids_of_curated_objects);
// check_revision_history(array_keys($guids_of_curated_objects));

// fix_previous_events();
// turn_preview_objects_visible();
// reindex_current_and_previous_events();

index_old_objects();

exit;


function get_latest_harvest_event()
{
    $result = $GLOBALS['mysqli_connection']->query("SELECT max(id) as max FROM harvest_events WHERE resource_id = 80");
    if($result && $row=$result->fetch_assoc())
    {
        return $row['max'];
    }
}

function get_guids_of_wikipedia_objects()
{
    $data_object_guids = array();
    $wikipedia_resource = Resource::wikipedia();
    $current_harvest_id = $wikipedia_resource->most_recent_published_harvest_event_id();
    $i = 0;
    foreach($GLOBALS['mysqli_connection']->iterate_file("SELECT dohe.guid FROM harvest_events he JOIN data_objects_harvest_events dohe ON (he.id=dohe.harvest_event_id) WHERE he.resource_id=80 AND he.began_at>'2011-06-01' ORDER BY he.id DESC LIMIT 600000") as $row_num => $row)
    {
        if($i % 1000 == 0) echo "$i :: ". count($data_object_guids) ." :: ". memory_get_usage() ." :: ". time_elapsed() ."\n";
        $i++;
        $data_object_guids[$row[0]] = 1;
    }
    return $data_object_guids;
}

function check_revision_history($guids_of_curated_objects)
{
    $GLOBALS['mysqli_connection']->begin_transaction();
    foreach($guids_of_curated_objects as $guid)
    {
        // check for curation actions
        $query = "SELECT do.guid, ta.name
            FROM data_objects do
            JOIN
                (".LOGGING_DB.".curator_activity_logs cal
                JOIN ".LOGGING_DB.".translated_activities ta ON (cal.activity_id=ta.activity_id AND ta.language_id=". Language::english()->id ." AND ta.name != 'choose_exemplar_article'))
                ON (do.id=cal.target_id AND (cal.changeable_object_type_id=1 OR cal.changeable_object_type_id=9))
            WHERE do.guid = '$guid'
            ORDER BY cal.created_at ASC";
        $actions_on_this_object = array();
        $result = $GLOBALS['mysqli_connection']->query($query);
        while($result && $row=$result->fetch_assoc())
        {
            if($row['name'] == 'trusted') unset($actions_on_this_object['untrusted']);
            if($row['name'] == 'untrusted') unset($actions_on_this_object['trusted']);
            if($row['name'] == 'show') unset($actions_on_this_object['hide']);
            if($row['name'] == 'hide') unset($actions_on_this_object['show']);
            if(isset($actions_on_this_object[$row['name']]))
            {
                $actions_on_this_object[$row['name']]++;
            }else $actions_on_this_object[$row['name']] = 1;
        }
        unset($actions_on_this_object['add_association']);
        unset($actions_on_this_object['remove_association']);
        if(count($actions_on_this_object) == 1 && @$actions_on_this_object['trusted'] >= 1)
        {
            set_vetted_status($guid, Vetted::trusted()->id);
            set_visibility($guid, Visibility::visible()->id);
        }elseif(count($actions_on_this_object) == 2 && @$actions_on_this_object['trusted'] >= 1 && @$actions_on_this_object['show'] >= 1)
        {
            set_vetted_status($guid, Vetted::trusted()->id);
            set_visibility($guid, Visibility::visible()->id);
        }elseif(count($actions_on_this_object) == 1 && @$actions_on_this_object['untrusted'] >= 1)
        {
            set_vetted_status($guid, Vetted::untrusted()->id);
            set_visibility($guid, Visibility::invisible()->id);
        }elseif(count($actions_on_this_object) == 2 && @$actions_on_this_object['untrusted'] >= 1 && @$actions_on_this_object['show'] >= 1)
        {
            set_vetted_status($guid, Vetted::untrusted()->id);
            set_visibility($guid, Visibility::invisible()->id);
        }elseif(count($actions_on_this_object) == 2 && @$actions_on_this_object['untrusted'] >= 1 && @$actions_on_this_object['hide'] >= 1)
        {
            set_vetted_status($guid, Vetted::untrusted()->id);
            set_visibility($guid, Visibility::invisible()->id);
        }elseif(count($actions_on_this_object) == 1 && @$actions_on_this_object['inappropriate'] >= 1)
        {
            set_vetted_status($guid, Vetted::untrusted()->id);
            set_visibility($guid, Visibility::invisible()->id);
        }elseif(count($actions_on_this_object) == 2 && @$actions_on_this_object['inappropriate'] >= 1 && @$actions_on_this_object['untrusted'] >= 1)
        {
            set_vetted_status($guid, Vetted::untrusted()->id);
            set_visibility($guid, Visibility::invisible()->id);
        }elseif(count($actions_on_this_object) == 1 && @$actions_on_this_object['hide'] >= 1)
        {
            set_visibility($guid, Visibility::invisible()->id);
        }elseif(count($actions_on_this_object) == 2 && @$actions_on_this_object['trusted'] >= 1 && @$actions_on_this_object['hide'] >= 1)
        {
            if($best_version_attributes = get_representative_version($guid))
            {
                set_vetted_status($guid, $best_version_attributes['vetted_id']);
                set_visibility($guid, $best_version_attributes['visibility_id']);
            }
        }elseif(count($actions_on_this_object) == 1 && @$actions_on_this_object['show'] >= 1)
        {
            // do nothing, it was just shown
            // echo "XXXXXXXXXA $guid\n";
        }else
        {
            if($best_version_attributes = get_representative_version($guid))
            {
                set_vetted_status($guid, $best_version_attributes['vetted_id']);
                set_visibility($guid, $best_version_attributes['visibility_id']);
            }
        }
    }
    $GLOBALS['mysqli_connection']->end_transaction();
    
    $count = count($guids_of_curated_objects);
    echo "Count: $count\n";
}

function get_guids_of_curated_objects($data_object_guids)
{
    $guids_of_objects_with_activities = array();
    $guids_of_objects_with_different_values = array();
    $guids_of_curated_objects = array();
    $batches = array_chunk(array_keys($data_object_guids), 5000);
    $i = 0;
    foreach($batches as $batch)
    {
        echo "$i :: ". count($guids_of_curated_objects) ." :: ". memory_get_usage() ." :: ". time_elapsed() ."\n";
        $i++;
        
        // Objects which curator actions
        $query = "SELECT do.guid, do.id, ta.name, cal.created_at
            FROM data_objects do
            JOIN ".LOGGING_DB.".curator_activity_logs cal ON (do.id=cal.target_id AND cal.changeable_object_type_id=1)
            JOIN ".LOGGING_DB.".translated_activities ta ON (cal.activity_id=ta.activity_id AND ta.language_id=". Language::english()->id ." AND ta.name != 'choose_exemplar_article')
            WHERE do.guid IN ('". implode("','", $batch) ."')";
        $result = $GLOBALS['mysqli_connection']->query($query);
        while($result && $row=$result->fetch_assoc())
        {
            $guids_of_curated_objects[$row['guid']] = 1;
            $guids_of_objects_with_activities[$row['guid']] = 1;
        }
        
        // Objects whose vetted or visibility IDs have changed
        $query = "SELECT do.guid, do.id, dohe.*
            FROM data_objects do
            JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            WHERE do.guid IN ('". implode("','", $batch) ."')
            AND do.id > 2919703
            AND (dohe.vetted_id != ". Vetted::unknown()->id ." OR dohe.visibility_id != ". Visibility::visible()->id .")";
        $result = $GLOBALS['mysqli_connection']->query($query);
        while($result && $row=$result->fetch_assoc())
        {
            $guids_of_curated_objects[$row['guid']] = 1;
            $guids_of_objects_with_different_values[$row['guid']] = 1;
        }
    }
    return $guids_of_curated_objects;
}

function get_representative_version($guid)
{
    // this object will look like it should be hidden, but it shouldn't
    if($guid == '09532fbf0b3e88d7d949aabf437a1670') return;
    
    $query = "SELECT do.guid, do.id, do.published, do.created_at, dohe.*
        FROM data_objects do
        JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
        WHERE do.guid IN ('$guid')
        ORDER BY do.id DESC";
    $result = $GLOBALS['mysqli_connection']->query($query);
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['id'];
        $vetted_id = $row['vetted_id'];
        $visibility_id = $row['visibility_id'];
        $published = $row['published'];
        if($vetted_id != 0)
        {
            if($vetted_id == 4) $visibility_id = 0;
            elseif($vetted_id == 6) { $vetted_id = 4; $visibility_id = 0; }
            return array('data_object_id' => $id, 'vetted_id' => $vetted_id, 'visibility_id' => $visibility_id);
        }
    }
    $result->data_seek(0);
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['id'];
        $vetted_id = $row['vetted_id'];
        $visibility_id = $row['visibility_id'];
        $published = $row['published'];
        if($visibility_id != 2 && $visibility_id != 1)
        {
            if($vetted_id == 4) $visibility_id = 0;
            elseif($vetted_id == 6) { $vetted_id = 4; $visibility_id = 0; }
            return array('data_object_id' => $id, 'vetted_id' => $vetted_id, 'visibility_id' => $visibility_id);
        }
    }
}

function set_vetted_status($guid, $vetted_id)
{
    $query = "UPDATE data_objects_harvest_events dohe JOIN data_objects_hierarchy_entries dohent ON (dohe.data_object_id=dohent.data_object_id) SET dohent.vetted_id=$vetted_id WHERE dohe.harvest_event_id=".LATEST_WIKIPEDIA_HARVEST_EVENT_ID." AND dohe.guid='$guid' AND dohent.vetted_id!=$vetted_id";
    $GLOBALS['mysqli_connection']->update($query);
}

function set_visibility($guid, $visibility_id)
{
    $query = "UPDATE data_objects_harvest_events dohe JOIN data_objects_hierarchy_entries dohent ON (dohe.data_object_id=dohent.data_object_id) SET dohent.visibility_id=$visibility_id WHERE dohe.harvest_event_id=".LATEST_WIKIPEDIA_HARVEST_EVENT_ID." AND dohe.guid='$guid' AND dohent.visibility_id!=$visibility_id";
    $GLOBALS['mysqli_connection']->update($query);
}

function turn_preview_objects_visible()
{
    // set objects in latest event which are preview, to visible
    $result = $GLOBALS['mysqli_connection']->update("UPDATE data_objects_harvest_events dohe JOIN data_objects_hierarchy_entries dohent ON (dohe.data_object_id=dohent.data_object_id) SET dohent.visibility_id=1 WHERE dohe.harvest_event_id=".LATEST_WIKIPEDIA_HARVEST_EVENT_ID." AND dohent.visibility_id=2");
    // make objects published
    $GLOBALS['db_connection']->update_where("data_objects", "id", "SELECT do.id FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE dohe.harvest_event_id=".LATEST_WIKIPEDIA_HARVEST_EVENT_ID." and do.published!=1", "published=1");
}

function fix_previous_events()
{
    $result = $GLOBALS['mysqli_connection']->query("SELECT id FROM harvest_events WHERE resource_id = 80 AND id!=".LATEST_WIKIPEDIA_HARVEST_EVENT_ID." ORDER BY id desc LIMIT 3");
    while($result && $row=$result->fetch_assoc())
    {
        $harvest_event_id = $row['id'];
        // set objects in previous event which are preview, to visible
        $GLOBALS['mysqli_connection']->update("UPDATE data_objects_harvest_events dohe JOIN data_objects_hierarchy_entries dohent ON (dohe.data_object_id=dohent.data_object_id) SET dohent.visibility_id=1 WHERE dohe.harvest_event_id=$harvest_event_id AND dohent.visibility_id=2");
        // // make objects unpublished
        $GLOBALS['db_connection']->update_where("data_objects", "id", "SELECT do.id FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE dohe.harvest_event_id=$harvest_event_id and do.published=1", "published=0");
    }
}

function reindex_current_and_previous_events()
{
    $latest_harvest_event = HarvestEvent::find(LATEST_WIKIPEDIA_HARVEST_EVENT_ID);
    $latest_harvest_event->index_for_search();
    $result = $GLOBALS['mysqli_connection']->query("SELECT id FROM harvest_events WHERE resource_id = 80 AND id!=".LATEST_WIKIPEDIA_HARVEST_EVENT_ID." ORDER BY id desc LIMIT 3");
    while($result && $row=$result->fetch_assoc())
    {
        $harvest_event_id = $row['id'];
        $latest_harvest_event = HarvestEvent::find($harvest_event_id);
        $latest_harvest_event->index_for_search(LATEST_WIKIPEDIA_HARVEST_EVENT_ID);
    }
}

function index_old_objects()
{
    $data_object_ids = array();
    $result = $GLOBALS['mysqli_connection']->query("SELECT id FROM harvest_events WHERE resource_id = 80 AND id!=".LATEST_WIKIPEDIA_HARVEST_EVENT_ID." ORDER BY id desc LIMIT 2");
    while($result && $row=$result->fetch_assoc())
    {
        $harvest_event_id = $row['id'];
        $query = "SELECT dohe.data_object_id
            FROM data_objects_harvest_events dohe
            LEFT JOIN data_objects_harvest_events dohe2 ON (dohe.data_object_id = dohe2.data_object_id AND dohe2.harvest_event_id = ".LATEST_WIKIPEDIA_HARVEST_EVENT_ID.")
            WHERE dohe.harvest_event_id = $harvest_event_id AND dohe2.data_object_id IS NULL";
        foreach($GLOBALS['db_connection']->iterate_file($query) as $row_num => $row) $data_object_ids[$row[0]] = 1;
    }
    
    $data_object_ids = array_keys($data_object_ids);
    $object_indexer = new DataObjectAncestriesIndexer();
    $object_indexer->index_data_objects($data_object_ids);

    $search_indexer = new SiteSearchIndexer();
    if($data_object_ids) $search_indexer->index_type('DataObject', $data_object_ids);
}

?>

