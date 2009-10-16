<?php

define('ENVIRONMENT', 'integration');
include_once(dirname(__FILE__) . "/../../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


define('PRIMARY_KEY', 'taxon_concept_id');
define('FILE_DELIMITER', '|');
define('MULTI_VALUE_DELIMETER', ';');
define('SOLR_SERVER', 'http://10.19.19.203:8080/solr');



echo "starting\n";



$max_id = 0;
$limit = 10000;

$result = $mysqli->query("SELECT MAX(id) as max FROM taxon_concepts");
if($result && $row=$result->fetch_assoc()) $max_id = $row["max"];
//$max_id = 90000;

for($start=0 ; $start<$max_id ; $start+=$limit)
{
    unset($GLOBALS['fields']);
    unset($GLOBALS['objects']);
    
    lookup_names($start, $limit);
    lookup_ranks($start, $limit);
    
    if(isset($GLOBALS['objects'])) send_attributes();
}


exec("curl ". SOLR_SERVER ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/commit.xml");









function lookup_names($start, $limit)
{
    global $mysqli;
    
    echo "\nquerying names\n";
    $result = $mysqli->query("SELECT tc.id, tc.published, tc.vetted_id, tc.supercedure_id, tcn.preferred, tcn.vern, tcn.language_id, n.string FROM taxon_concepts tc LEFT JOIN (taxon_concept_names tcn JOIN names n ON (tcn.name_id=n.id)) ON (tc.id=tcn.taxon_concept_id)  WHERE tc.id>$start AND tc.id<=".($start+$limit));
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row['id'];
        $string = $row['string'];
        
        if($row['vern'])
        {
            $attr = 'common_name';
            $GLOBALS['fields'][$attr] = 1;
            $GLOBALS['objects'][$id][$attr][$string] = 1;
        }else
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
    $result = $mysqli->query("SELECT taxon_concept_id, rank_id, hierarchy_id FROM hierarchy_entries WHERE taxon_concept_id>$start AND taxon_concept_id<=".($start+$limit));
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



function send_attributes()
{
    $OUT = fopen(LOCAL_ROOT . "temp/data.csv", "w+");
    
    $fields = array_keys($GLOBALS['fields']);
    fwrite($OUT, PRIMARY_KEY . FILE_DELIMITER . implode(FILE_DELIMITER, $fields) . "\n");
    
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
                    $this_attr[] = implode(MULTI_VALUE_DELIMETER, $values);
                }else
                {
                    $this_attr[] = clean_text($attributes[$attr]);
                }
            }
            // default value is empty string
            else $this_attr[] = "";
        }
        fwrite($OUT, implode(FILE_DELIMITER, $this_attr) . "\n");
    }
    fclose($OUT);
    
    unset($GLOBALS['fields']);
    unset($GLOBALS['objects']);
    
    
    
    
    $curl = "curl ". SOLR_SERVER ."/update/csv -F separator='". FILE_DELIMITER ."'";
    foreach($multi_values as $field => $bool)
    {
        $curl .= " -F f.$field.split=true -F f.$field.separator='". MULTI_VALUE_DELIMETER ."'";
    }
    $curl .= " -F stream.url=".LOCAL_WEB_ROOT."temp/data.csv -F stream.contentType=text/plain;charset=utf-8 -F overwrite=true";
    
    echo "calling: $curl\n";
    exec($curl);
    exec("curl ". SOLR_SERVER ."/update -F stream.url=".LOCAL_WEB_ROOT."applications/solr/commit.xml");
}

function clean_text($text)
{
    if(!Functions::is_utf8($text)) return "";
    $text = str_replace("|", "", $text);
    $text = str_replace(";", " ", $text);
    $text = str_replace("\n", "", $text);
    $text = str_replace("\r", "", $text);
    return trim($text);
}

?>