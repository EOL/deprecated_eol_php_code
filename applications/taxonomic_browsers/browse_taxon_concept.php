<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="style.css" media="screen" rel="stylesheet" type="text/css" />
</head>


<?php

//define('MYSQL_DEBUG', true);
//define('DEBUG', true);
define("ENVIRONMENT", "production_read_only");
include_once("../../config/start.php");
include_once("functions.php");

$taxon_concept_id = @$_GET["taxon_concept_id"];
$hierarchy_entry_id = @$_GET["hierarchy_entry_id"];
$expand = @$_GET["expand"];

$mysqli =& $GLOBALS['mysqli_connection'];

$variable = "taxon_concept_id";


show_kingdoms($variable);


echo "<hr>";




if($taxon_concept_id) $node = new TaxonConcept($taxon_concept_id);
elseif($hierarchy_entry_id) $node = new HierarchyEntry($hierarchy_entry_id);
else exit;


$indent = show_ancestries($node, $variable);

echo show_node($node, $indent, $variable);

if($expand) show_all_children($node, $indent+1, array(), $variable);
else show_children($node, $indent+1, $variable);

echo "<hr>";
echo "<hr>";
echo "<hr>";
echo "<hr>";
echo "<hr>";

$hierarchy_entry_ids = $node->hierarchy_entry_ids();
foreach($hierarchy_entry_ids as $id)
{
    $hierarchy_entry = new HierarchyEntry($id);
    
    echo "<b>".$hierarchy_entry->hierarchy()->label."</b><br>";
    
    $indent = show_ancestry_he($hierarchy_entry);
    echo show_name_he($hierarchy_entry, $indent, 0);
    show_children_he($hierarchy_entry, $indent+1);
    show_synonyms_he($hierarchy_entry);
    
    echo "<hr>";
}












?>