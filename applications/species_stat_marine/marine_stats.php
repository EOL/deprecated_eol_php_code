<!--- #!/usr/local/bin/php --->
<?php
/* 
    This code processes the latest WORMS resource XML and generates stats for it.
    A successful run of this script will append a new record in this report:
        http://services.eol.org/species_stat_marine/display.php
        
                
*/

define('MYSQL_DEBUG', false);
define('DEBUG', false);
//define('DEBUG_TO_FILE', false);

//define("ENVIRONMENT", "development");//source of saved stats
//define("ENVIRONMENT", "integration");//source of saved stats
//define("ENVIRONMENT", "data_main");//source of saved stats

$path = "";
include_once($path . "../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];

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
$xml = simplexml_load_file("http://10.19.19.226/resources/26.xml", null, LIBXML_NOCDATA);

foreach($xml->taxon as $t)
{
    $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
    $name = Functions::import_decode($t_dwc->ScientificName);
    //print $name . "<br>";    
    $names[$name] = 1;
    $temp_names_array[] = $mysqli->escape($name);
    
    if(count($temp_names_array) >= $batch_size)
    {
        static $batch_num;
        $batch_num++;        
        echo "Batch $batch_num<br>\n";        
        get_stats($temp_names_array);        
        $temp_names_array = array();
        //if($batch_num >= 4) break;
    }
}

get_stats($temp_names_array);

function get_stats($names)
{
    global $mysqli;
    global $names_in_eol;
    global $marine_pages;
    global $pages_with_objects;
    global $pages_with_vetted_objects;
    //print "<hr> names = " . count($names) . "<hr><hr> ";
    if (mysqli_connect_errno()) 
    { 
       printf("Can't connect to MySQL database (). Errorcode: %s\n", mysqli_connect_error()); 
       exit; 
    }     
    $ids = array();
    //$result = $mysqli->query("SELECT taxon_concept_id id, n.string FROM names n JOIN taxon_concept_names tcn ON (n.id=tcn.name_id) JOIN 
    $result = $mysqli->query("SELECT taxon_concept_id id, n.string FROM names n JOIN taxon_concept_names tcn ON (n.id=tcn.name_id) 
    JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) WHERE n.string IN ('".implode("','", $names)."') 
    AND tc.published=1 
    AND tc.supercedure_id=0 
    AND tc.vetted_id IN (5) ");

    while($result && $row=$result->fetch_assoc())
    {
        $id = $row["id"];
        $names_in_eol[$row["string"]] = 1;
        $marine_pages[$id] = 1;
        $ids[] = $id;
    }
    
    /*
    print "<hr>
    ids = " . count($ids) . "<hr>
    marine_pages = " . count($marine_pages) . "<hr>
    names_in_eol = " . count($names_in_eol) . "<hr>
    ";
    */

    $result = $mysqli->query("SELECT DISTINCT tcn.taxon_concept_id id, vetted_id FROM taxon_concept_names tcn 
    JOIN taxa t ON (tcn.name_id=t.name_id) JOIN data_objects_taxa dot ON (t.id=dot.taxon_id) 
    JOIN data_objects do ON (dot.data_object_id=do.id) 
    WHERE tcn.taxon_concept_id IN (".implode(",", $ids).") 
    AND do.published=1 
    AND do.vetted_id IN (0,5) 
    AND do.visibility_id=1;");

    while($result && $row=$result->fetch_assoc())
    {
        $pages_with_objects[$row["id"]] = 1;
        if($row["vetted_id"] == 5) $pages_with_vetted_objects[$row["id"]] = 1;
    }    
    echo "names_in_eol: ".count($names_in_eol)."<br>\n";
    echo "marine_pages: ".count($marine_pages)."<br>\n";
    echo "pages_with_objects: ".count($pages_with_objects)."<br>\n";
    echo "pages_with_vetted_objects: ".count($pages_with_vetted_objects)."<br>\n\n";        
}

$names_in_eol = count($names_in_eol);
$marine_pages = count($marine_pages);
$pages_with_objects = count($pages_with_objects);
$pages_with_vetted_objects = count($pages_with_vetted_objects);
$names_from_xml = count($names);

//print"<hr>";
echo "\n";
echo "Names from XML: ". $names_from_xml ."<br>\n";
echo "Names in EOL: ". $names_in_eol ."<br>\n";
echo "Marine pages: ". $marine_pages ."<br>\n";
echo "Pages with objects: ". $pages_with_objects ."<br>\n";
echo "Pages with vetted objects: ". $pages_with_vetted_objects ."<br>\n";

$date_created = date('Y-m-d');
$time_created = date('H:i:s');

$qry = " insert into page_stats_marine(names_from_xml  ,names_in_eol  ,marine_pages  ,pages_with_objects  ,pages_with_vetted_objects   ,date_created   ,time_created ,active )
                               select $names_from_xml ,$names_in_eol ,$marine_pages ,$pages_with_objects ,$pages_with_vetted_objects ,'$date_created','$time_created','n' ";
$update = $mysqli->query($qry);//1
?>