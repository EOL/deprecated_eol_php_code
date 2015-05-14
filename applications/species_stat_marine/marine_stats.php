<?php
namespace php_active_record;

/* 
This code processes the latest WORMS resource XML and generates stats for it.
A successful run of this script will append a new record in this report: 
http://services.eol.org/species_stat_marine/display.php    

2nd function of this script is to compute:
How many WORMS pages have wikipedia and flickr
    
as of April 21:
    execution time: 2.24 hours
    Names from XML: 164223
    Names in EOL: 143075
    Marine pages: 145265
    Pages with objects: 80659
    Pages with vetted objects: 80241
    Marine pages with Wikipedia content = 5167
    Marine pages with Flickr content = 1548    

as of August 2:  execution time: 2.10 hrs
as of Sep 17:    execution time: 1.40 hrs
as of 2011 01 10 execution time: 1.66 hrs
*/

$timestart = microtime(1);
require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

$names = array();
$names_in_eol = array();
$marine_pages = array();
$pages_with_objects = array();
$pages_with_vetted_objects = array();
$temp_names_array = array();
$batch_size = 10000; //debug orig 10000
$file = CONTENT_RESOURCE_LOCAL_PATH . "26.xml";
print "Reading WORMS XML file... \n";
$xml = simplexml_load_file($file , null, LIBXML_NOCDATA);
print "Start loop... \n";
foreach($xml->taxon as $t)
{
    $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
    $name = Functions::import_decode($t_dwc->ScientificName);
    $names[$name] = 1;
    $temp_names_array[] = $mysqli->escape($name);
    if(count($temp_names_array) >= $batch_size)
    {
        static $batch_num;
        $batch_num++;        
        print "Batch $batch_num \n";        
        $arr = get_stats($temp_names_array, $names_in_eol, $marine_pages, $pages_with_objects, $pages_with_vetted_objects, $mysqli);                
        $names_in_eol               = $arr[0];
        $marine_pages               = $arr[1]; 
        $pages_with_objects         = $arr[2]; 
        $pages_with_vetted_objects  = $arr[3];
        $temp_names_array = array();
        //if($batch_num >= 4) break;
    }
}
$arr = get_stats($temp_names_array, $names_in_eol, $marine_pages, $pages_with_objects, $pages_with_vetted_objects, $mysqli);
$names_in_eol               = $arr[0];
$marine_pages               = $arr[1]; 
$pages_with_objects         = $arr[2]; 
$pages_with_vetted_objects  = $arr[3];
$names_in_eol               = count($names_in_eol);
$marine_pages_count         = count(array_keys($marine_pages));
$pages_with_objects         = count($pages_with_objects);
$pages_with_vetted_objects  = count($pages_with_vetted_objects);
$names_from_xml             = count($names);
print "\n Final numbers: \n";
print "Names from XML: ". $names_from_xml ."\n";
print "Names in EOL: ". $names_in_eol ."\n";
print "Marine pages: ". $marine_pages_count ."\n";
print "Pages with objects: ". $pages_with_objects ."\n";
print "Pages with vetted objects: ". $pages_with_vetted_objects ."\n";
$date_created = date('Y-m-d');
$time_created = date('H:i:s');
$qry = "INSERT INTO page_stats_marine(names_from_xml, names_in_eol, marine_pages, pages_with_objects, pages_with_vetted_objects, date_created, time_created, active)
        SELECT $names_from_xml, $names_in_eol, $marine_pages_count, $pages_with_objects, $pages_with_vetted_objects, '$date_created', '$time_created', 'n'";
$update = $mysqli->query($qry);

/*
//to call the Wikipedia Flickr stat
wikipedia_flickr_stat($marine_pages,$mysqli) //working
*/

$elapsed_time_sec = microtime(1)-$timestart;
print "\n";
print "elapsed time = $elapsed_time_sec sec              \n";
print "elapsed time = " . $elapsed_time_sec/60 . " min   \n";
print "elapsed time = " . $elapsed_time_sec/60/60 . " hr \n";
echo "\n\n Done processing.";

//#############################################################################################################

function get_stats($names, $names_in_eol, $marine_pages, $pages_with_objects, $pages_with_vetted_objects, $mysqli)
{
    $ids = array();
    $result = $mysqli->query("SELECT taxon_concept_id id, n.string 
    FROM names n 
    JOIN taxon_concept_names tcn ON (n.id=tcn.name_id) 
    JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) 
    WHERE n.string IN ('".implode("','", $names)."') AND tc.published = 1 AND tc.supercedure_id = 0 AND tc.vetted_id IN (" . Vetted::trusted()->id . ") ");
    while($result && $row = $result->fetch_assoc())
    {
        $id = $row["id"];
        $names_in_eol[$row["string"]] = 1;
        $marine_pages[$id] = 1;
        $ids[] = $id;
    }
    //original has DISTINCT
    $result = $mysqli->query("SELECT he.taxon_concept_id id, dohe.vetted_id, dohe.visibility_id
        FROM hierarchy_entries he 
        JOIN data_objects_hierarchy_entries dohe ON (he.id = dohe.hierarchy_entry_id) 
        JOIN data_objects do ON (dohe.data_object_id = do.id) 
        WHERE he.taxon_concept_id IN (".implode(",", $ids).") 
        AND do.published = 1 
        AND dohe.vetted_id <> " . Vetted::untrusted()->id . "
        AND dohe.visibility_id = " . Visibility::visible()->id . ";");
    while($result && $row=$result->fetch_assoc())
    {
        $pages_with_objects[$row["id"]] = 1;
        if($row["vetted_id"] == Vetted::trusted()->id) $pages_with_vetted_objects[$row["id"]] = 1;
    }    
    print"\n Batch totals: \n";
    print "names_in_eol: " . count($names_in_eol) . "\n";
    print "marine_pages: " . count($marine_pages) . "\n";
    print "pages_with_objects: " . count($pages_with_objects) . "\n";
    print "pages_with_vetted_objects: " . count($pages_with_vetted_objects) . "\n\n";
    return array($names_in_eol, $marine_pages, $pages_with_objects, $pages_with_vetted_objects);
}

function save2txt($arr,$filename)
{    
    $str = "";        
    foreach($arr as $id) $str .= $id . "\n";
    $filename .= ".txt";
    if($fp = fopen($filename, "w"))
    {
        fwrite($fp, $str);
        fclose($fp);
    }else{
      debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
    }
}

/*
function wikipedia_flickr_stat($marine_pages, $mysqli)
{
    $marine_pages = array_keys($marine_pages);
    //$marine_pages = array(1,2,3,5,6,7,206692,333); //debug
    $wikipedia = count_pages_per_agent_id(38132,$marine_pages,$mysqli);//wikipedia = agent_id 38132
    $flickr = count_pages_per_agent_id(8246,$marine_pages,$mysqli);//flickr = agent_id  8246
    print"\n 
    Marine pages with Wikipedia content = " . count($wikipedia) . " \n
    Marine pages with Flickr content = " . count($flickr) . " \n";
    save2txt($marine_pages,"marine_pages");
    save2txt($wikipedia,"worms_with_wikipedia");
    save2txt($flickr,"worms_with_flickr");    
}
function count_pages_per_agent_id($agent_id,$marine_pages, $mysqli)
{
    $query="SELECT agents.full_name, Max(harvest_events.id) latest_harvest_event_id,
    agents.id FROM agents
    JOIN agents_resources ON agents.id = agents_resources.agent_id
    JOIN harvest_events ON agents_resources.resource_id = harvest_events.resource_id
    WHERE agents.id = $agent_id Group By agents.full_name ";
    $result = $mysqli->query($query);
    $row = $result->fetch_row();            
    $latest_harvest_event_id   = $row[1];
    $query="Select data_objects_taxon_concepts.taxon_concept_id id
    From data_objects_harvest_events
    Join data_objects_taxon_concepts ON data_objects_harvest_events.data_object_id = data_objects_taxon_concepts.data_object_id
    Join data_objects do ON data_objects_harvest_events.data_object_id = do.id
    JOIN data_objects_hierarchy_entries dohe on do.id = dohe.data_object_id
    Where data_objects_harvest_events.harvest_event_id = $latest_harvest_event_id and dohe.vetted_id <> " . Vetted::untrusted()->id . "";
    $result = $mysqli->query($query);    
    while($result && $row=$result->fetch_assoc()) $partner_tc_id_list[$row["id"]] = 1;
    $partner_tc_id_list = array_keys($partner_tc_id_list);    
    $return_arr=array();
    foreach($marine_pages as $id)
    {
        if(in_array($id, $partner_tc_id_list)) $return_arr[]=$id;               
    }    
    return $return_arr;
}
*/

?>