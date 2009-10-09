<?php

define('ENVIRONMENT', 'integration');
include_once(dirname(__FILE__) . "/../config/start.php");
$mysqli =& $GLOBALS['mysqli_connection'];


define('PRIMARY_KEY', 'taxon_concept_id');
define('FILE_DELIMITER', '|');
define('MULTI_VALUE_DELIMETER', ';');
define('SOLR_SERVER', 'http://localhost:8983/solr');



echo "starting\n";



$attributes = array();

$count = 0;
$limit = 100000;
$result = $mysqli->query("SELECT tc.id, tc.published, tc.vetted_id, tcn.preferred, tcn.vern, tcn.language_id, n.string FROM taxon_concepts tc STRAIGHT_JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) STRAIGHT_JOIN names n ON (tcn.name_id=n.id) WHERE tc.id<20000 ORDER BY tc.id");
while($result && $row=$result->fetch_assoc())
{
    $id = $row['id'];
    $string = $row['string'];
    
    if($row['vern'])
    {
        if($row['preferred']) $attr = 'pref_vern_name';
        else $attr = 'vern_name';
        $attr .= "_".$row["language_id"];
        
        $GLOBALS['fields'][$attr] = 1;
        $GLOBALS['objects'][$id][$attr][$string] = 1;
        
    }else
    {
        if($row['preferred']) $attr = 'pref_sci_name';
        else $attr = 'sci_name';
        
        $GLOBALS['fields'][$attr] = 1;
        $GLOBALS['objects'][$id][$attr][$string] = 1;
    }
    
    $GLOBALS['fields']['vetted_id'] = 1;
    $GLOBALS['objects'][$id]['vetted_id'] = $row['vetted_id'];
    
    $GLOBALS['fields']['published'] = 1;
    $GLOBALS['objects'][$id]['published'] = $row['published'];
    
    
    // $count++;
    // if($count%$limit==0)
    // {
    //     send_attributes();
    // }
}

if(isset($GLOBALS['objects'])) send_attributes();















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
    $curl .= " -F stream.file=". LOCAL_ROOT ."temp/data.csv -F stream.contentType=text/plain;charset=utf-8 -H 'Content-type:text/xml; charset=utf-8'";
    
    echo "calling: $curl\n";
    exec($curl);
    exec("curl ". SOLR_SERVER ."/update -F stream.file=". LOCAL_ROOT ."temp/commit.xml");
}


function clean_text($text)
{
    if(!$text || !Functions::is_utf8($text)) return "";
    $text = str_replace("|", "", $text);
    $text = str_replace(";", " ", $text);
    $text = str_replace("\n", "", $text);
    $text = str_replace("\r", "", $text);
    return trim($text);
}





?>