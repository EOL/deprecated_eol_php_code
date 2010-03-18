<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="style.css" media="screen" rel="stylesheet" type="text/css" />
</head>


<?php

// include_once(dirname(__FILE__) . "/../../config/environment.php");
// include_once("functions.php");
// 
// $taxon_concept_id_1 = @$_GET["taxon_concept_id_1"];
// $taxon_concept_id_2 = @$_GET["taxon_concept_id_2"];
// $function = @$_GET["function"];
// 
// $mysqli =& $GLOBALS['mysqli_connection'];
// 
// 
// 
// 
// if($function && $taxon_concept_id_1 && $taxon_concept_id_2 && $taxon_concept_id_1!=$taxon_concept_id_2)
// {
//     $taxon_concept_id_1 = merge_concepts($taxon_concept_id_1, $taxon_concept_id_2);
//     $taxon_concept_id_2 = $taxon_concept_id_1;
// }
// 
// 
// 
// echo "<a href=?taxon_concept_id_1=$taxon_concept_id_1&taxon_concept_id_2=$taxon_concept_id_2&function=merge>Merge concepts</a><br>";
// echo "<a href=?taxon_concept_id_1=$taxon_concept_id_1&taxon_concept_id_2=$taxon_concept_id_1&function=merge>Match window2 to window1</a><br>";
// echo "<a href=?taxon_concept_id_1=$taxon_concept_id_2&taxon_concept_id_2=$taxon_concept_id_2&function=merge>Match window1 to window2</a><br>";
// 
// echo "<table border=1><tr>";
// 
// 
// echo "<td valign=top width=50%>";
// $variable = "taxon_concept_id_2=$taxon_concept_id_2&taxon_concept_id_1";
// 
// show_kingdoms($variable);
// echo "<hr>";
// 
// if($taxon_concept_id_1) $node_1 = new TaxonConcept($taxon_concept_id_1);
// else exit;
// 
// $indent = show_ancestries($node_1, $variable);
// 
// echo show_node($node_1, $indent, $variable);
// 
// show_children($node_1, $indent+1, $variable);
// 
// echo "</td>";
// 
// 
// //////////////////////////////////////////
// //////////////////////////////////////////
// //////////////////////////////////////////
// 
// 
// echo "<td valign=top>";
// 
// $variable = "taxon_concept_id_1=$taxon_concept_id_1&taxon_concept_id_2";
// 
// show_kingdoms($variable);
// echo "<hr>";
// 
// if($taxon_concept_id_2) $node_2 = new TaxonConcept($taxon_concept_id_2);
// else exit;
// 
// $indent = show_ancestries($node_2, $variable);
// 
// echo show_node($node_2, $indent, $variable);
// 
// show_children($node_2, $indent+1, $variable);
// 
// 
// echo "</td></tr></table>";
// 
// 
// 
// 
// 
// 
// 
// function merge_concepts($id1, $id2)
// {
//     $concept = new TaxonConcept($id1);
//     $concept->supercede($id2);
//     
//     //echo "Superceding $id1 with $id2<br>";
//     
//     return min($id1, $id2);
// }
// 
// 


?>