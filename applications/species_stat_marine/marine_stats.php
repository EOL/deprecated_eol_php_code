<?php
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

as of August 2:
    execution time: 2.10 hours
    Names from XML: 181476
    Names in EOL: 158889
    Marine pages: 161323
    Pages with objects: 80676
    Pages with vetted objects: 80178
    
*/

$timestart = microtime(1);

$wrap="\n";
//$wrap="<br>";

//$GLOBALS['ENV_NAME'] = "slave_215";
require_once(dirname(__FILE__) ."/../../config/environment.php");
$mysqli =& $GLOBALS['mysqli_connection'];

//#####################################################################################################
// /* use to comment the first part of the code

$names = array();
$names_in_eol = array();
$marine_pages = array();
$pages_with_objects = array();
$pages_with_vetted_objects = array();
$temp_names_array = array();

$batch_size = 10000;

//$xml = simplexml_load_file("../content_server/resources/666.xml", null, LIBXML_NOCDATA);
//$xml = simplexml_load_file("../../../mtce/worms/txt/2009_04_09_WORMS.xml", null, LIBXML_NOCDATA);
//$xml = simplexml_load_file("../../../mtce/worms/txt/2009_06_05_WORMS.xml", null, LIBXML_NOCDATA);
//$xml = simplexml_load_file("http://services.eol.org/eol_php_code/applications/content_server/resources/26.xml", null, LIBXML_NOCDATA);
//$xml = simplexml_load_file("http://10.19.19.226/resources/26.xml", null, LIBXML_NOCDATA);

//on beast:
//$file = "../../../resources/26.xml";
$file = CONTENT_RESOURCE_LOCAL_PATH . "26.xml";
$xml = simplexml_load_file($file , null, LIBXML_NOCDATA);

foreach($xml->taxon as $t)
{
    $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
    $name = Functions::import_decode($t_dwc->ScientificName);
    //print $name . "$wrap";    
    $names[$name] = 1;
    $temp_names_array[] = $mysqli->escape($name);
    
    if(count($temp_names_array) >= $batch_size)
    {
        static $batch_num;
        $batch_num++;        
        echo "Batch $batch_num $wrap";        
        get_stats($temp_names_array);        
        $temp_names_array = array();
        //if($batch_num >= 4) break;
    }
}
get_stats($temp_names_array);

$names_in_eol = count($names_in_eol);
$marine_pages_count = count(array_keys($marine_pages));
$pages_with_objects = count($pages_with_objects);
$pages_with_vetted_objects = count($pages_with_vetted_objects);
$names_from_xml = count($names);

//print"<hr>";
echo "$wrap Final numbers: $wrap";
echo "Names from XML: ". $names_from_xml ."$wrap";
echo "Names in EOL: ". $names_in_eol ."$wrap";
echo "Marine pages: ". $marine_pages_count ."$wrap";
echo "Pages with objects: ". $pages_with_objects ."$wrap";
echo "Pages with vetted objects: ". $pages_with_vetted_objects ."$wrap";

$date_created = date('Y-m-d');
$time_created = date('H:i:s');
$qry = " insert into page_stats_marine(names_from_xml  ,names_in_eol  ,marine_pages       ,pages_with_objects  ,pages_with_vetted_objects   ,date_created   ,time_created ,active )
                               select $names_from_xml ,$names_in_eol ,$marine_pages_count ,$pages_with_objects ,$pages_with_vetted_objects ,'$date_created','$time_created','n' ";
$update = $mysqli->query($qry);

// */

//===============================================================================
//===============================================================================
//===============================================================================
//start wikipedia flickr stat -- working ok
/*
$marine_pages = array_keys($marine_pages);
//$marine_pages = array(1,2,3,5,6,7,206692,333);

$wikipedia = count_pages_per_agent_id(38132,$marine_pages);//wikipedia = agent_id 38132
//    print"<pre>";print_r($wikipedia);print"</pre>";
$flickr = count_pages_per_agent_id(8246,$marine_pages);//flickr = agent_id  8246
//    print"<pre>";print_r($flickr);print"</pre>";

print"$wrap 
Marine pages with Wikipedia content = " . count($wikipedia) . " $wrap
Marine pages with Flickr content = " . count($flickr) . " $wrap
";

save2txt($marine_pages,"marine_pages");
save2txt($wikipedia,"worms_with_wikipedia");
save2txt($flickr,"worms_with_flickr");
*/  
//end wikipedia flickr stat
//===============================================================================
//===============================================================================
//===============================================================================
$elapsed_time_sec = microtime(1)-$timestart;
echo "$wrap";
echo "elapsed time = $elapsed_time_sec sec              $wrap";
echo "elapsed time = " . $elapsed_time_sec/60 . " min   $wrap";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hr $wrap";

exit("\n\n Done processing.");
//#############################################################################################################
//#############################################################################################################
//#############################################################################################################
function get_stats($names)
{
    global $mysqli;
    global $names_in_eol;
    global $marine_pages;
    global $pages_with_objects;
    global $pages_with_vetted_objects;
    
    global $wrap;
    //print "<hr> names = " . count($names) . "<hr><hr> ";

    $ids = array();
    $result = $mysqli->query("SELECT taxon_concept_id id, n.string 
    FROM names n 
    JOIN taxon_concept_names tcn ON (n.id=tcn.name_id) 
    JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) 
    WHERE n.string IN ('".implode("','", $names)."') 
    AND tc.published=1 AND tc.supercedure_id=0 AND tc.vetted_id IN (" . Vetted::find("trusted") . ") ");

    while($result && $row=$result->fetch_assoc())
    {
        $id = $row["id"];
        $names_in_eol[$row["string"]] = 1;
        $marine_pages[$id] = 1;
        $ids[] = $id;
    }    

    $result = $mysqli->query("SELECT DISTINCT he.taxon_concept_id id, do.vetted_id
        FROM hierarchy_entries he 
        JOIN data_objects_hierarchy_entries dohe ON (he.id=dohe.hierarchy_entry_id) 
        JOIN data_objects do ON (dohe.data_object_id=do.id) 
        WHERE he.taxon_concept_id IN (".implode(",", $ids).") 
    AND do.published=1 
    AND do.vetted_id <> " . Vetted::find("untrusted") . "
    AND do.visibility_id = " . Visibility::find("visible") . "
    ;");

    while($result && $row=$result->fetch_assoc())
    {
        $pages_with_objects[$row["id"]] = 1;
        if($row["vetted_id"] == Vetted::find("trusted")) $pages_with_vetted_objects[$row["id"]] = 1;
    }    
    
    print"$wrap Batch numbers: $wrap";
    echo "names_in_eol: ".count($names_in_eol)."$wrap";
    echo "marine_pages: ".count($marine_pages)."$wrap";
    echo "pages_with_objects: ".count($pages_with_objects)."$wrap";
    echo "pages_with_vetted_objects: ".count($pages_with_vetted_objects)."$wrap $wrap";        
}
function count_pages_per_agent_id($agent_id,$marine_pages)
{
    global $mysqli;
    global $wrap;
    
    $query="Select agents.full_name, Max(harvest_events.id) latest_harvest_event_id,
    agents.id From agents
    Inner Join agents_resources ON agents.id = agents_resources.agent_id
    Inner Join harvest_events ON agents_resources.resource_id = harvest_events.resource_id
    Where agents.id = $agent_id Group By agents.full_name ";
    
    $result = $mysqli->query($query);
    $row = $result->fetch_row();            
    $latest_harvest_event_id   = $row[1];

    $query="Select data_objects_taxon_concepts.taxon_concept_id id
    From data_objects_harvest_events
    Inner Join data_objects_taxon_concepts ON data_objects_harvest_events.data_object_id = data_objects_taxon_concepts.data_object_id
    Inner Join data_objects ON data_objects_harvest_events.data_object_id = data_objects.id
    Where 
    data_objects_harvest_events.harvest_event_id = $latest_harvest_event_id and 
    data_objects.vetted_id <> " . Vetted::find("untrusted") . "";
    
    $result = $mysqli->query($query);    
    while($result && $row=$result->fetch_assoc())
    {        
        $partner_tc_id_list[$row["id"]] = 1;
    }
    $partner_tc_id_list = array_keys($partner_tc_id_list);
    
    
    $return_arr=array();
    foreach($marine_pages as $id)
    {
        if(in_array($id, $partner_tc_id_list)) $return_arr[]=$id;               
    }
    
    return $return_arr;    
}
function save2txt($arr,$filename)
{    
	$str="";        
    foreach($arr as $id)
    {
        $str .= $id . "\n";   
    }    
	$filename .= ".txt";
	if($fp = fopen($filename,"w")){fwrite($fp,$str);fclose($fp);}		
    return "";    
}


?>
