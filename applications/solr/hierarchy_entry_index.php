<?php

define('ENVIRONMENT', 'integration');
//define('MYSQL_DEBUG', true);
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


define('PRIMARY_KEY', 'id');
//define('SOLR_SERVER', 'http://localhost:8983/solr');


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
$filter = "hierarchy_id IN (431)";

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
    unset($GLOBALS['fields']);
    unset($GLOBALS['objects']);
    unset($GLOBALS['ancestries']);
    unset($GLOBALS['node_metadata']);
    
    lookup_names($i, $limit);
    lookup_ancestries($i, $limit);
    lookup_synonyms($i, $limit);
    
    if(isset($GLOBALS['objects'])) send_attributes();
}


if(isset($GLOBALS['objects'])) send_attributes();
exec("curl ". SOLR_SERVER ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/commit.xml");
exec("curl ". SOLR_SERVER ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/optimize.xml");









function lookup_names($start, $limit)
{
    global $mysqli;
    global $filter;
    
    echo "\nquerying names\n";
    $result = $mysqli->query("SELECT he.*, n.string, cf.string canonical_form FROM hierarchy_entries he LEFT JOIN (names n LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)) ON (he.name_id=n.id) WHERE he.id BETWEEN $start AND ".($start+$limit)." AND $filter");
    echo "done querying names\n";
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['id'];
        $rank_id = $row['rank_id'];
        $parent_id = $row['parent_id'];
        
        $GLOBALS['fields']['parent_id'] = 1;
        $GLOBALS['objects'][$id]['parent_id'] = $row['parent_id'];
        
        $GLOBALS['fields']['taxon_concept_id'] = 1;
        $GLOBALS['objects'][$id]['taxon_concept_id'] = $row['taxon_concept_id'];
        
        $GLOBALS['fields']['hierarchy_id'] = 1;
        $GLOBALS['objects'][$id]['hierarchy_id'] = $row['hierarchy_id'];
        
        $GLOBALS['fields']['rank_id'] = 1;
        $GLOBALS['objects'][$id]['rank_id'] = $row['rank_id'];
        
        $GLOBALS['fields']['vetted_id'] = 1;
        $GLOBALS['objects'][$id]['vetted_id'] = $row['vetted_id'];
        
        $GLOBALS['fields']['published'] = 1;
        $GLOBALS['objects'][$id]['published'] = $row['published'];
        
        $GLOBALS['fields']['name'] = 1;
        $GLOBALS['objects'][$id]['name'] = $row['string'];
        
        $GLOBALS['fields']['canonical_form'] = 1;
        $GLOBALS['fields']['canonical_form_string'] = 1;
        $GLOBALS['objects'][$id]['canonical_form'] = $row['canonical_form'];
        $GLOBALS['objects'][$id]['canonical_form_string'] = $row['canonical_form'];
        
        // if($ancestry = get_ancestry($id, $rank_id, $row['string'], $parent_id))
        // {
        //     foreach($ancestry as $rank => $name)
        //     {
        //         $GLOBALS['fields'][$rank] = 1;
        //         $GLOBALS['objects'][$id][$rank] = $name;
        //     }
        // }
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
    
    echo "\ndone looking up ancestries\n";

    if(@$GLOBALS['objects'])
    {
        foreach(@$GLOBALS['objects'] as $id => $junk)
        {
            if(@$GLOBALS['node_metadata'][$id]['parent_id'] && $ancestry = get_ancestry($GLOBALS['node_metadata'][$id]['parent_id']))
            {
                foreach($ancestry as $rank => $name)
                {
                    $GLOBALS['fields'][$rank] = 1;
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
    $result = $mysqli->query("SELECT s.*, n.string, cf.string canonical_form FROM synonyms s JOIN names n ON (s.name_id=n.id) LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id) WHERE s.hierarchy_entry_id BETWEEN $start AND ".($start+$limit)." AND $filter");
    echo "done querying synonyms\n";
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['hierarchy_entry_id'];
        $relation_id = $row['synonym_relation_id'];
        
        if(($row['language_id'] && $row['language_id'] != $sci) || $relation_id == SynonymRelation::insert('common name')) $field = 'common_name';
        else $field = 'synonym';
        
        $GLOBALS['fields'][$field] = 1;
        $GLOBALS['objects'][$id][$field][$row['string']] = 1;
        
        if($field == 'synonym' && $row['canonical_form'])
        {
            $GLOBALS['fields']['synonym_canonical'] = 1;
            $GLOBALS['objects'][$id]['synonym_canonical'][$row['canonical_form']] = 1;
        }
    }
}




function send_attributes()
{
    $OUT = fopen(LOCAL_ROOT . "temp/data.csv", "w+");
    
    $fields = array_keys($GLOBALS['fields']);
    fwrite($OUT, PRIMARY_KEY . SOLR_FILE_DELIMITER . implode(SOLR_FILE_DELIMITER, $fields) . "\n");
    
    $multi_values = array();
    
    foreach($GLOBALS['objects'] as $primary_key => $attributes)
    {
        $this_attr = array();
        $this_attr[] = $primary_key;
        foreach($fields as $attr)
        {
            // this object has this attribute
            if(isset($attributes[$attr]))
            {
                // the attribute is multi-valued
                if(is_array($attributes[$attr]))
                {
                    $multi_values[$attr] = 1;
                    $values = array_map("clean_text", array_keys($attributes[$attr]));
                    $this_attr[] = implode(SOLR_MULTI_VALUE_DELIMETER, $values);
                }else
                {
                    $this_attr[] = clean_text($attributes[$attr]);
                }
            }
            // default value is empty string
            else $this_attr[] = "";
        }
        fwrite($OUT, implode(SOLR_FILE_DELIMITER, $this_attr) . "\n");
    }
    fclose($OUT);
    
    unset($GLOBALS['fields']);
    unset($GLOBALS['objects']);
    
    
    
    
    $curl = "curl ". SOLR_SERVER ."/update/csv -F separator='". SOLR_FILE_DELIMITER ."'";
    foreach($multi_values as $field => $bool)
    {
        $curl .= " -F f.$field.split=true -F f.$field.separator='". SOLR_MULTI_VALUE_DELIMETER ."'";
    }
    $curl .= " -F stream.url=".LOCAL_WEB_ROOT."temp/data.csv -F stream.contentType=text/plain;charset=utf-8 -F overwrite=true";
    
    echo "calling: $curl\n";
    exec($curl);
    exec("curl ". SOLR_SERVER ."/update -F stream.url=". LOCAL_WEB_ROOT ."applications/solr/commit.xml");
}

function clean_text($text)
{
    if(!Functions::is_utf8($text)) return "";
    $text = str_replace(";", " ", $text);
    $text = str_replace("×", " ", $text);
    $text = str_replace("\"", " ", $text);
    $text = str_replace("'", " ", $text);
    $text = str_replace("|", "", $text);
    $text = str_replace("\n", "", $text);
    $text = str_replace("\r", "", $text);
    $text = str_replace("\t", "", $text);
    $text = Functions::utf8_to_ascii($text);
    while(preg_match("/  /", $text)) $text = str_replace("  ", " ", $text);
    return trim($text);
}

?>