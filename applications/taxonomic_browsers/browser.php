<?php
namespace php_active_record;
?>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link href="style.css" media="screen" rel="stylesheet" type="text/css" />
</head>


<?php

include_once(dirname(__FILE__) . "/../../config/environment.php");
include_once("functions.php");

$id = @$_GET["id"];
$hierarchy_id = @$_GET["hierarchy_id"];
$expand = @$_GET["expand"];

$mysqli =& $GLOBALS['mysqli_connection'];





if($id)
{
    $hierarchy_entry = HierarchyEntry::find($id);
    echo "<h2>".$hierarchy_entry->hierarchy->label." ($hierarchy_entry->hierarchy_id)</h2>";
    
    
    $indent = show_ancestry_he($hierarchy_entry);
    
    echo show_name_he($hierarchy_entry, $indent, $expand);
    $indent++;
    
    if($expand) show_all_children_he($hierarchy_entry, $indent);
    else show_children_he($hierarchy_entry, $indent);
    
    show_synonyms_he($hierarchy_entry);
    
    show_references_he($hierarchy_entry);
    
    
    echo "<hr>";
    show_kingdoms_he($hierarchy_entry->hierarchy_id);
    
}elseif($hierarchy_id)
{
    $hierarchy = Hierarchy::find($hierarchy_id);
    echo "<h2>".$hierarchy->label."</h2>";
    
    show_kingdoms_he($hierarchy_id);
}else
{
    show_hierarchies_he();
}















// function show_hierarchies()
// {
//     global $mysqli;
//     
//     $kingdoms = array();
//     
//     $result = $mysqli->query("SELECT * FROM hierarchies");
//     while($result && $row=$result->fetch_assoc())
//     {
//         $hierarchy = new Hierarchy($row["id"]);
//         
//         echo "<a href='browser.php?hierarchy_id=$hierarchy->id'>$hierarchy->id :: $hierarchy->label :: $hierarchy->description</a><br>\n";
//     }
// }
// 
// function show_children($node, $indent)
// {
//     global $expand;
//     
//     $children = $node->children();
//     foreach($children as $k => $v)
//     {
//         echo show_name($v, $indent, $expand);
//     }
// }
// 
// function show_all_children($node, $indent)
// {
//     global $expand;
//     
//     $children = $node->children();
//     foreach($children as $k => $v)
//     {
//         echo show_name($v, $indent, $expand);
//         show_all_children($v, $indent+1);
//     }
// }
// 
// 
// 
// function show_kingdoms($hierarchy_id)
// {
//     global $mysqli;
//     global $expand;
//     
//     $kingdoms = array();
//     
//     $result = $mysqli->query("SELECT * FROM hierarchy_entries WHERE parent_id=0 AND hierarchy_id=$hierarchy_id");
//     while($result && $row=$result->fetch_assoc())
//     {
//         $kingdoms[] = new HierarchyEntry($row["id"]);
//     }
//     
//     usort($kingdoms, "Functions::cmp_hierarchy_entries");
//     
//     foreach($kingdoms as $k => $v)
//     {
//         echo show_name($v, 0, $expand);
//     }
//     
//     echo "<hr>";
// }
// 
// function show_name($hierarchy_entry, $indent, $expand)
// {
//     $display = str_repeat("&nbsp;", $indent*8);
//     if($expand) $display .= "(<a href='browser.php?id=".$hierarchy_entry->id."'>-</a>) ";
//     else $display .= "(<a href='browser.php?id=".$hierarchy_entry->id."&expand=1'>+</a>) ";
//     $display .= "<a href='browser.php?id=".$hierarchy_entry->id."'>".$hierarchy_entry->name()->string."</a>";
//     
//     if(@$rank = $hierarchy_entry->rank()->label) $display .= " <small>($rank)</small>";
//     if($agents = $hierarchy_entry->agents()) $display .= " <small>(".$agents[0]->agent->display_name.")</small>";
//     
//     $display .= "<br>";
//     
//     return $display;
// }


?>
