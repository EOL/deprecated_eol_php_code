<?php

define('ENVIRONMENT', 'slave');
//define('MYSQL_DEBUG', true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


$schema = array(
    'preferred_scientific_name' => array(),
    'scientific_name'           => array(),
    'common_name'               => array(),
    'rank_id'                   => array(),
    'hierarchy_id'              => array(),
    'top_image_id'              => '',
    'vetted_id'                 => '',
    'published'                 => '',
    'supercedure_id'            => '');
        
$solr = new SolrAPI('http://10.19.19.219:8080/solr/', 'taxon_concepts_swap', 'taxon_concept_id', $schema);

$solr->delete_all_documents();

echo "starting\n";

$start = 0;
$max_id = 0;
$limit = 100000;
$filter = "1=1";
//$filter = "he.hierarchy_id IN (113)";

$result = $mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc  WHERE $filter");
if($result && $row=$result->fetch_assoc())
{
    $start = $row["min"];
    $max_id = $row["max"];
}
//$start = $start + 100000;
//$max_id = $start+$limit-1;

for($i=$start ; $i<$max_id ; $i+=$limit)
{
    unset($GLOBALS['fields']);
    unset($GLOBALS['objects']);
    
    lookup_names($i, $limit);
    lookup_ranks($i, $limit);
    lookup_top_images($i, $limit);
    
    if(isset($GLOBALS['objects'])) $solr->send_attributes($GLOBALS['objects'], $GLOBALS['fields']);
}

if(isset($GLOBALS['objects'])) $solr->send_attributes($GLOBALS['objects'], $GLOBALS['fields']);
$solr->commit();
$solr->optimize();

$solr->swap('taxon_concepts_swap', 'taxon_concepts');







function lookup_names($start, $limit)
{
    global $mysqli;
    
    echo "\nquerying names\n";
    $result = $mysqli->query("SELECT tc.id,  tc.published, tc.vetted_id, tc.supercedure_id, tcn.preferred, tcn.vern, tcn.language_id, n.string FROM taxon_concepts tc LEFT JOIN (taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id)) ON (tc.id=tcn.taxon_concept_id)  WHERE tc.id BETWEEN $start AND ".($start+$limit));
    echo "done querying names\n";
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['id'];
        $string = $row['string'];
        
        if($row['vern'] && $string)
        {
            $attr = 'common_name';
            $GLOBALS['fields'][$attr] = 1;
            $GLOBALS['objects'][$id][$attr][$string] = 1;
        }elseif($string)
        {
            if($row['preferred']) $attr = 'preferred_scientific_name';
            else $attr = 'scientific_name';
            
            $GLOBALS['fields'][$attr] = 1;
            $GLOBALS['objects'][$id][$attr][$string] = 1;
        }
        
        $GLOBALS['fields']['vetted_id'] = 1;
        $GLOBALS['objects'][$id]['vetted_id'] = $row['vetted_id'];
        
        $GLOBALS['fields']['published'] = 1;
        $GLOBALS['objects'][$id]['published'] = $row['published'];
        
        $GLOBALS['fields']['supercedure_id'] = 1;
        $GLOBALS['objects'][$id]['supercedure_id'] = $row['supercedure_id'];
    }
}

function lookup_ranks($start, $limit)
{
    global $mysqli;
    
    echo "\nquerying ranks\n";
    $result = $mysqli->query("SELECT taxon_concept_id, rank_id, hierarchy_id FROM hierarchy_entries  WHERE taxon_concept_id BETWEEN $start AND ".($start+$limit));
    echo "done querying ranks\n";
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['taxon_concept_id'];
        $rank_id = $row['rank_id'];
        $hierarchy_id = $row['hierarchy_id'];
        
        $GLOBALS['fields']['hierarchy_id'] = 1;
        $GLOBALS['objects'][$id]['hierarchy_id'][$hierarchy_id] = 1;
        
        if($rank_id)
        {
            $GLOBALS['fields']['rank_id'] = 1;
            $GLOBALS['objects'][$id]['rank_id'][$rank_id] = 1;
        }
    }
}

function lookup_top_images($start, $limit)
{
    global $mysqli;
    
    echo "\nquerying top_images\n";
    $result = $mysqli->query("SELECT he.taxon_concept_id id, ti.data_object_id FROM hierarchy_entries he JOIN top_images ti  ON (he.id=ti.hierarchy_entry_id) WHERE he.taxon_concept_id BETWEEN $start AND ".($start+$limit)." AND view_order=1");
    echo "done querying top_images\n";
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['id'];
        $data_object_id = $row['data_object_id'];
        
        if(@!$GLOBALS['objects'][$id]['top_image_id'])
        {
            $GLOBALS['fields']['top_image_id'] = 1;
            $GLOBALS['objects'][$id]['top_image_id'][$data_object_id] = 1;
        }
    }
}

?>