<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="style.css" media="screen" rel="stylesheet" type="text/css" />
</head>


<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
include_once("functions.php");

$taxon_concept_id = @$_REQUEST["taxon_concept_id"];
$expand = @$_REQUEST["expand"];



if($taxon_concept_id) $node = new TaxonConcept($taxon_concept_id);
else exit;



$hierarchy_entry_ids = $node->hierarchy_entry_ids();
foreach($hierarchy_entry_ids as $id)
{
    $hierarchy_entry = new HierarchyEntry($id);
    $bgcolor = "#ffffff";
    if($hierarchy_entry->published == 0) $bgcolor = "#eeeeee";
    if($hierarchy_entry->vetted_id == Vetted::unknown()->id) $bgcolor = "#ffffcc";
    
    echo "<div style='background-color: $bgcolor'>";
    echo "<p align='right'><b>".$hierarchy_entry->hierarchy()->label."</b><br>hierarchy_id: $hierarchy_entry->hierarchy_id<br>hierarchy_entry_id: $hierarchy_entry->id</p>";
    
    $indent = show_ancestry_he($hierarchy_entry);
    echo "<b>";
    echo show_name_he($hierarchy_entry, $indent, 0);
    echo "</b>";
    echo "<div style='max-height: 250px; max-width: 700px; overflow: auto;'>";
    show_children_he($hierarchy_entry, $indent+1);
    show_synonyms_he($hierarchy_entry);
    echo "</div></div>";
    echo "<hr>";
}












?>