<?php
namespace php_active_record;
/* DATA-1817 */

include_once(dirname(__FILE__) . "/../../config/environment.php");
$timestart = time_elapsed();
// $GLOBALS['ENV_DEBUG'] = true;

/* test
require_library('connectors/SpeciesChecklistAPI');
$func = new SpeciesChecklistAPI(false, false);
$url = 'http://gimmefreshdata.github.io/?limit=5000000&taxonSelector=Enhydra lutris&traitSelector=&wktString=GEOMETRYCOLLECTION%28POLYGON%20%28%28-65.022%2063.392%2C%20-74.232%2064.672%2C%20-84.915%2071.353%2C%20-68.482%2068.795%2C%20-67.685%2066.286%2C%20-65.022%2063.392%29%29%2CPOLYGON%20%28%28-123.126%2049.079%2C%20-129.911%2053.771%2C%20-125.34%2069.52%2C%20-97.874%2068.532%2C%20-85.754%2068.217%2C%20-91.525%2063.582%2C%20-77.684%2060.542%2C%20-64.072%2059.817%2C%20-55.85%2053.249%2C%20-64.912%2043.79%2C%20-123.126%2049.079%29%29%29';
$new = $func->convert_2gbif_url($url);
exit("\n$new\n");
exit("\nend test\n");
*/

/* main operation
require_library('connectors/SpeciesChecklistAPI');
$func = new SpeciesChecklistAPI(false, false);
generate_new_dwca($func);                   //main script to generate DwCA
create_new_resources_in_opendata($func);    //script to create resources in two pre-defined datasets in opendata.eol.org.
unset($func);
*/

// /* utility report for: https://eol-jira.bibalex.org/browse/DATA-1817?focusedCommentId=63653&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63653
                       // https://eol-jira.bibalex.org/browse/DATA-1817?focusedCommentId=63654&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63654
require_library('connectors/SpeciesChecklistAPI');
$func = new SpeciesChecklistAPI(false, false);
utility_rep1($func);
// */

$elapsed_time_sec = time_elapsed() - $timestart;
echo "\n\n";
echo "elapsed time = " . $elapsed_time_sec/60 . " minutes \n";
echo "elapsed time = " . $elapsed_time_sec/60/60 . " hours \n";
echo "\nDone processing.\n";

function utility_rep1($func)
{
    $datasets = array('national-checklists-2019', 'water-body-checklists-2019');
    // $datasets = array('national-checklists-2019');
    // $datasets = array('water-body-checklists-2019');
    foreach($datasets as $dataset) {
        unlink(CONTENT_RESOURCE_LOCAL_PATH."/$dataset".".txt");
        $resources = $func->get_opendata_resources($dataset, true); //2nd param true means get all records (resources)
        $i = 0;
        foreach($resources as $resource) { $i++; echo "\n[$i]";
            // print_r($resource); exit;
            /*stdClass Object(
                [state] => active
                [description] => A list of species from Afghanistan collected using effechecka and geonames polygons
                [format] => Darwin Core Archive
                [name] => Afghanistan Species List
                [url] => https://editors.eol.org/eol_php_code/applications/content_server/resources/SC_afganistan.tar.gz
                more fields below and above...
            )*/
            $func->parse_dwca_for_report($resource, $dataset);
            if(($i % 100) == 0) sleep(30);
            // if($i >= 3) break; //debug only
        }
    }
}
function create_new_resources_in_opendata($func)
{
    /*
    description 	"A list of species from G…a and geonames polygons"
    format      	"Darwin Core Archive"
    url_type    	"upload"
    name        	"Guatemala Species List"
    */
    /* worked OK. Using a test dataset.
    ~$ curl https://opendata.eol.org/api/3/action/resource_create \
    -d '{"package_id": "eli-test-dataset", "clear_upload": "true", "url": "https://editors.eol.org/eol_php_code/applications/content_server/resources/EOL_FreshData_connectors.txt", "name": "7th api-uploaded resource"}' \
    -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"
    */
    $datasets['nationalchecklists'] = 'national-checklists-2019';
    $datasets['water-body-checklists'] = 'water-body-checklists-2019';
    foreach($datasets as $dataset => $destination_dataset) {
        $resources = $func->get_opendata_resources($dataset, true); //2nd param true means get all records (resources)
        $i = 0;
        foreach($resources as $r) { $i++;
            echo "\n[$i]";
            /*stdClass Object(
                [description] => A list of species from Afghanistan collected using effechecka and geonames polygons
                [format] => Darwin Core Archive
                [name] => Afghanistan Species List
            )*/
            $basename = 'SC_'.get_basename($r->url);
            
            $rec = array();
            $rec['package_id'] = $destination_dataset;
            $rec['clear_upload'] = "true";
            $rec['url'] = 'https://editors.eol.org/eol_php_code/applications/content_server/resources/'.$basename.'.tar.gz';
            $rec['name'] = $r->name;
            $rec['description'] = $r->description;
            $rec['format'] = $r->format;
            $json = json_encode($rec);
            
            $cmd = 'curl https://opendata.eol.org/api/3/action/resource_create';
            $cmd .= " -d '".$json."'";
            $cmd .= ' -H "Authorization: b9187eeb-0819-4ca5-a1f7-2ed97641bbd4"';
            
            sleep(2);
            $output = shell_exec($cmd);
            echo "\n$output\n";
            // if($i >= 2) break; //debug only
        }
    }
}
function generate_new_dwca($func)
{
    $datasets = array('nationalchecklists', 'water-body-checklists');
    $datasets = array('nationalchecklists');
    $datasets = array('water-body-checklists');
    foreach($datasets as $dataset) {
        $urls = $func->get_opendata_resources($dataset); // print_r($urls);
        $i = 0;
        foreach($urls as $url) { $i++;
            echo "\n[$i]";
            /* breakdown
            $s = 195; $m = 10;
            $cont = false;
            // if($i >=  $s    && $i < $s+$m)    $cont = true; running
            // if($i >=  $s+$m   && $i < $s+($m*2))  $cont = true; running
            // if($i >=  $s+($m*2)   && $i < $s+($m*3))  $cont = true; running
            // if($i >=  $s+($m*3)   && $i < $s+($m*4))  $cont = true; running
            // if($i >=  $s+($m*4)   && $i < $s+($m*5))  $cont = true; running
            // if($i >=  $s+($m*5)   && $i < $s+($m*6))  $cont = true;
            if(!$cont) continue;
            */
            process_resource_url($url);
        }
    }
}
function process_resource_url($dwca_file)
{
    require_library('connectors/DwCA_Utility');
    // $dwca_file = 'http://localhost/cp/DATA-1817/indianocean.zip';
    // $dwca_file = 'https://opendata.eol.org/dataset/c99917cf-7790-4608-a7c2-5532fb47da32/resource/f6f7145c-bc58-4182-ac23-e5a80cf0edcc/download/indianocean.zip';
    // $dwca_file = 'https://opendata.eol.org/dataset/6c70b436-5503-431f-8bf3-680fea5e1b05/resource/6207f9ba-3c93-4a22-9a18-7ae4fc47df56/download/afganistan.zip';
    $resource_id = 'SC_'.get_basename($dwca_file); echo " Processing $resource_id"."...";
    $func = new DwCA_Utility($resource_id, $dwca_file);
    /* No preferred. Will get all.
    $preferred_rowtypes = array('http://rs.tdwg.org/dwc/terms/taxon', 'http://eol.org/schema/reference/reference');
    */
    $func->convert_archive();
    Functions::finalize_dwca_resource($resource_id);
}
function get_basename($url)
{
    return pathinfo($url, PATHINFO_FILENAME);
}
?>
