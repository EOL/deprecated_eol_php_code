<?php
namespace php_active_record;

include_once(dirname(__FILE__) . "/../config/environment.php");
require_vendor('namelink');
$GLOBALS['ENV_DEBUG'] = false;
ob_implicit_flush();
@ob_end_flush();

$start_id = @$argv[1];
$end_id = @$argv[2];

if(!$start_id && !$end_id)
{
    echo "tag_data_objects.php \$start_id \$end_id\n\n";
    exit;
}


$nametag = new \NameTag('');
if($end_id) $result = $GLOBALS['db_connection']->query("SELECT id, description FROM data_objects WHERE description!='' AND published=1 AND id BETWEEN $start_id AND $end_id");
else $result = $GLOBALS['db_connection']->query("SELECT id, description FROM data_objects WHERE id=$start_id");

$i = 0;
$GLOBALS['db_connection']->begin_transaction();
while($result && $row=$result->fetch_assoc())
{
    if($i%50 == 0)
    {
        echo "$i: ".time_elapsed()."\n";
        flush();
        $GLOBALS['db_connection']->commit();
        $GLOBALS['db_connection']->end_transaction();
        $GLOBALS['db_connection']->begin_transaction();
    }
    $i++;
    
    $id = $row['id'];
    $description = $row['description'];
    
    $nametag->reset($description);
    $description = $nametag->markup_html();
    
    $description = \NameLink::replace_tags_with_collection($description, 'php_active_record\\EOLLookup::check_db');
    $description = preg_replace("/(<a[^>]*>[^<]*?)<a href=\"\/pages\/[0-9]+\/overview\/\">(.*?)<\/a>/", "$1$2", $description);
    // echo "UPDATE data_objects SET description_linked='".$GLOBALS['db_connection']->real_escape_string($description)."' WHERE id=$id\n";
    $GLOBALS['db_connection']->query("UPDATE data_objects SET description_linked='".$GLOBALS['db_connection']->real_escape_string($description)."' WHERE id=$id");
}
$GLOBALS['db_connection']->end_transaction();





class EOLLookup
{
    public static function check_db($name_string)
    {
        $name_string = trim($name_string);
        if(!$name_string) return false;
        $canonical_form = Functions::canonical_form($name_string);
        
        $min_tc_id = 0;
        $result = $GLOBALS['db_connection']->query("SELECT tc.id FROM canonical_forms cf JOIN names n ON (cf.id=n.canonical_form_id) JOIN hierarchy_entries he ON (n.id=he.name_id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) WHERE cf.string='$canonical_form' AND tc.published=1 AND tc.vetted_id=5 AND tc.supercedure_id=0");
        if($result && $row=$result->fetch_assoc())
        {
            if(!$min_tc_id || $row['id'] < $min_tc_id) $min_tc_id = $row['id'];
        }
        
        $json = array();
        if($min_tc_id) $json[] = array('url' => '/pages/'. $min_tc_id .'/overview/');
        
        
        return $json;
    }
}
