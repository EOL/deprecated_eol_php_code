<?php

define('ENVIRONMENT', 'slave');
//define('MYSQL_DEBUG', true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


        
$solr = new SolrAPI('http://10.19.19.43:8080/solr/', 'hierarchy_entries_swap');
$solr->delete_all_documents();


$GLOBALS['ancestries'] = array();
$GLOBALS['rank_labels'] = array();
if($r = Rank::find('kingdom')) $GLOBALS['rank_labels'][$r] = 'kingdom';
if($r = Rank::find('phylum')) $GLOBALS['rank_labels'][$r] = 'phylum';
if($r = Rank::find('class')) $GLOBALS['rank_labels'][$r] = 'class';
if($r = Rank::find('order')) $GLOBALS['rank_labels'][$r] = 'order';
if($r = Rank::find('family')) $GLOBALS['rank_labels'][$r] = 'family';
if($r = Rank::find('genus')) $GLOBALS['rank_labels'][$r] = 'genus';
if($r = Rank::find('species')) $GLOBALS['rank_labels'][$r] = 'species';

$start = 0;
$max_id = 0;
$limit = 30000;
$filter = "1=1";
//$filter = "he.hierarchy_id IN (119, 120)";

$result = $mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM hierarchy_entries he WHERE $filter");
if($result && $row=$result->fetch_assoc())
{
    $start = $row["min"];
    $max_id = $row["max"];
}
//$start = $start + 100000;
//$max_id = $start+$limit-1;

for($i=$start ; $i<$max_id ; $i+=$limit)
{
    unset($GLOBALS['objects']);
    unset($GLOBALS['ancestries']);
    unset($GLOBALS['node_metadata']);
    
    lookup_names($i, $limit);
    lookup_ancestries($i, $limit);
    lookup_synonyms($i, $limit);
    
    if(isset($GLOBALS['objects'])) $solr->send_attributes($GLOBALS['objects']);
}


if(isset($GLOBALS['objects'])) $solr->send_attributes($GLOBALS['objects']);
$solr->commit();
$solr->optimize();
$solr->swap('hierarchy_entries_swap', 'hierarchy_entries');







function lookup_names($start, $limit)
{
    global $mysqli;
    global $filter;
    
    echo "\nquerying names ($start, $limit)\n";
    $result = $mysqli->query("SELECT he.*, n.string, cf.string canonical_form FROM hierarchy_entries he LEFT JOIN (names n LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)) ON (he.name_id=n.id) WHERE he.id  BETWEEN $start AND ".($start+$limit)." AND $filter");
    echo "done querying names\n";
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['id'];
        $rank_id = $row['rank_id'];
        $parent_id = $row['parent_id'];
        
        $GLOBALS['objects'][$id]['parent_id'] = $row['parent_id'];
        $GLOBALS['objects'][$id]['taxon_concept_id'] = $row['taxon_concept_id'];
        $GLOBALS['objects'][$id]['hierarchy_id'] = $row['hierarchy_id'];
        $GLOBALS['objects'][$id]['rank_id'] = $row['rank_id'];
        $GLOBALS['objects'][$id]['vetted_id'] = $row['vetted_id'];
        $GLOBALS['objects'][$id]['published'] = $row['published'];
        $GLOBALS['objects'][$id]['name'] = $row['string'];
        $GLOBALS['objects'][$id]['canonical_form'] = $row['canonical_form'];
        $GLOBALS['objects'][$id]['canonical_form_string'] = $row['canonical_form'];
    }
}

function lookup_ancestries()
{
    global $mysqli;
    
    echo "\nlooking up ancestries\n";

    $ids_to_lookup = array();
    if(@$GLOBALS['objects'])
    {
        foreach($GLOBALS['objects'] as $id => $junk)
        {
            $ids_to_lookup[$id] = 1;
        }
    }
    
    while($ids_to_lookup)
    {
        $result = $mysqli->query("SELECT he.id, he.parent_id, he.rank_id, n.string, cf.string canonical_form FROM hierarchy_entries he LEFT JOIN (names n LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)) ON (he.name_id=n.id) WHERE he.id IN (". implode(",", array_keys($ids_to_lookup)) .")");
        $ids_to_lookup = array();
        while($result && $row=$result->fetch_assoc())
        {
            if($row['canonical_form']) $string = $row['canonical_form'];
            else $string = $row['string'];
            
            $GLOBALS['node_metadata'][$row['id']] = array('parent_id' => $row['parent_id'], 'rank_id' => $row['rank_id'], 'string' => $string);
            if($row['parent_id']) $ids_to_lookup[$row['parent_id']] = 1;
        }
    }
    
    echo "done looking up ancestries\n";

    if(@$GLOBALS['objects'])
    {
        foreach(@$GLOBALS['objects'] as $id => $junk)
        {
            if(@$GLOBALS['node_metadata'][$id]['parent_id'] && $ancestry = get_ancestry($GLOBALS['node_metadata'][$id]['parent_id']))
            {
                foreach($ancestry as $rank => $name)
                {
                    $GLOBALS['objects'][$id][$rank] = $name;
                }
            }
        }
    }
}

function get_ancestry($id)
{
    global $mysqli;
    
    if(isset($GLOBALS['ancestries'][$id])) return $GLOBALS['ancestries'][$id];
    
    $ancestry = array();
    if($GLOBALS['node_metadata'][$id])
    {
        if($GLOBALS['node_metadata'][$id]['parent_id'])
        {
            $ancestry = get_ancestry($GLOBALS['node_metadata'][$id]['parent_id']);
        }
        
        if(@$GLOBALS['rank_labels'][$GLOBALS['node_metadata'][$id]['rank_id']])
        {
            $ancestry[$GLOBALS['rank_labels'][$GLOBALS['node_metadata'][$id]['rank_id']]] = $GLOBALS['node_metadata'][$id]['string'];
        }
    }
    
    $GLOBALS['ancestries'][$id] = $ancestry;
    return $ancestry;
}

function lookup_synonyms($start, $limit)
{
    global $mysqli;
    global $filter;
    
    $sci = Language::find('scientific name');
    echo "\nquerying synonyms\n";
    $result = $mysqli->query("SELECT s.*, n.string, cf.string canonical_form FROM synonyms s JOIN hierarchy_entries he ON (s.hierarchy_entry_id=he.id) JOIN (names n LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)) ON (s.name_id=n.id) WHERE he.id BETWEEN $start AND ".($start+$limit)." AND $filter");
    echo "done querying synonyms\n";
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['hierarchy_entry_id'];
        $relation_id = $row['synonym_relation_id'];
        
        if(($row['language_id'] && $row['language_id'] != $sci) || $relation_id == SynonymRelation::insert('common name')) $field = 'common_name';
        else $field = 'synonym';
        
        $GLOBALS['objects'][$id][$field][$row['string']] = 1;
        
        if($field == 'synonym' && $row['canonical_form'])
        {
            $GLOBALS['objects'][$id]['synonym_canonical'][$row['canonical_form']] = 1;
        }
    }
}


?>