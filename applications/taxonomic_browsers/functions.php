<?php
namespace php_active_record;

function show_kingdoms($variable)
{
    global $mysqli;
    
    $children = array();

    $result = $mysqli->query("SELECT DISTINCT (taxon_concept_id) FROM hierarchy_entries WHERE parent_id=0 AND taxon_concept_id!=0 AND hierarchy_id=".Hierarchy::$CatalogueOfLife);
    while($result && $row=$result->fetch_assoc())
    {
        $id = $row["taxon_concept_id"];
        //$result2 = $mysqli->query("SELECT parent_id FROM hierarchy_entries WHERE taxon_concept_id=$id AND parent_id!=0");
        //if($result2 && $row2=$result2->fetch_assoc()) continue;

        $children[] = TaxonConcept::find($id);
    }

    usort($children, "Functions::cmp_hierarchy_entries");
    foreach($children as $k => $v)
    {
        echo show_node($v, 0, $variable);
    }
}

function show_ancestries($node, $variable)
{
    $ancestries = get_ancestries($node, array());
    if(count($ancestries)<2) $indent = show_parents($node, $variable);
    else
    {
        echo "<table cellspacing=0 cellpadding=4 border=1 style='font-size: 14px;'><tr>";
        foreach($ancestries as $k => $parents)
        {
            echo "<td valign=top>";
            $indent = 0;
            foreach($parents as $k2 => $v2)
            {
                echo show_node($v2, $indent, $variable);
                $indent++;
            }
            echo "</td>";
        }
        echo "</tr></table><hr>";
    }
    return $indent;
}

function get_ancestries($node, $previous_ids)
{
    $ancestries = array();
    
    
    foreach($node->parents() as $k => $v)
    {
        if(in_array($v->id, $previous_ids))
        {
            echo "Some kind of loop with: <b>".$v->name()->string."</b><br><br>";
            $ancestries[] = array($v);
            return $ancestries;
        }
        
        $array = $previous_ids;
        $array[] = $v->id;
        $parentAncestries = get_ancestries($v, $array);
        
        if($parentAncestries)
        {
            foreach($parentAncestries as $k2 => $v2)
            {
                $v2[] = $v;
                $ancestries[] = $v2;
            }
        }else $ancestries[] = array($v);
    }
    
    
    return $ancestries;
}

function show_parents($node, $variable)
{
//        echo "<pre>";
//        print_r($this->getAncestries($node));
//        echo "</pre>";
    
    $parents = array();
    while($p = $node->parents())
    {
        $node = $p[0];
        $parents[] = $node;
    }
    
    $indent = 0;
    krsort($parents);
    foreach($parents as $k => $v)
    {
        echo show_node($v, $indent, $variable);
        $indent++;
    }
    echo "<hr>";
    
    return $indent;
}

function show_node($node, $indent, $variable)
{
    $style = "tc";
    
    $source = "";
    if(count($node->hierarchy_entry_ids())==1)
    {
        $style = "he";
        $hierarchies = $node->hierarchies();
        $source = $hierarchies[0]->label;
    }
    
    $html = str_repeat("&nbsp;",$indent*5)."<a class='$style' href='?$variable=$node->id'>".$node->name()->string."</a> <small>($node->id)</small>";
    
    $homonyms = $node->homonyms();
    if($homonyms)
    {
        $html .= " ---> homonyms: ";
        foreach($homonyms as $taxon_concept_id)
        {
            $html .= "<a class='$style' href='?$variable=$taxon_concept_id'>$taxon_concept_id</a> ";
        }
    }
    
    $html .= " (<a class='$style' href='?$variable=$node->id&expand=1'>+</a>)";
    
    if($rank = $node->rank()) $html .= " <small><i>".$rank."</i></small>";
    if($source) $html .= str_repeat("&nbsp;", 10)."<small>($source)</small>";
    return $html."<br>";
}

function show_children($node, $indent, $variable)
{
    $children = $node->children();
    $i = 0;
    foreach($children as $k => $v)
    {
        echo show_node($v, $indent, $variable);
        $i++;
        if($i >= 300)
        {
            echo str_repeat("&nbsp;",$indent*5).".........<br>";
            break;
        }
    }
}    

function show_all_children($node, $indent, $previous_ids, $variable)
{
    $children = $node->children();
    
    foreach($children as $k => $v)
    {
        if(in_array($v->id, $previous_ids))
        {
            echo "********************************<br>";
            echo show_node($v, $indent, $variable);
            echo "********************************<br>";
            debug(show_node($v, $indent, $variable));
            return;
        }
        
        $array = $previous_ids;
        $array[] = $v->id;
        echo show_node($v, $indent, $variable);
        show_all_children($v, $indent+1, $array, $variable);
    }
}







////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////

function show_hierarchies_he()
{
    global $mysqli;
    
    $kingdoms = array();
    
    $result = $mysqli->query("SELECT * FROM hierarchies");
    while($result && $row=$result->fetch_assoc())
    {
        $hierarchy = Hierarchy::find($row["id"]);
        
        echo "<a href='?hierarchy_id=$hierarchy->id&ENV_NAME=".$GLOBALS['ENV_NAME']."'>$hierarchy->id :: $hierarchy->label :: $hierarchy->description</a><br>\n";
    }
}

function show_ancestry_he($entry)
{
    $indent = 0;
    
    $parents = array();
    while($entry = $entry->parent())
    {
        $parents[] = $entry;
    }
    
    krsort($parents);
    foreach($parents as $k => $v)
    {
        echo show_name_he($v, $indent, 0);
        $indent++;
    }
    
    return $indent;
}

function show_children_he($node, $indent)
{
    $children = $node->children();
    @usort($children, array('\php_active_record\Functions', 'cmp_hierarchy_entries'));
    
    $i = 0;
    foreach($children as $k => $v)
    {
        echo show_name_he($v, $indent, 0);
        $i++;
        if($i >= 300)
        {
            echo str_repeat("&nbsp;",$indent*5).".........<br>";
            break;
        }
    }
}

function show_all_children_he($node, $indent)
{
    $children = $node->children();
    foreach($children as $k => $v)
    {
        echo show_name_he($v, $indent, 1);
        show_all_children_he($v, $indent+1);
    }
}

function show_name_he($hierarchy_entry, $indent, $expand)
{
    $display = str_repeat("&nbsp;", $indent*2);
    if($expand) $display .= "(<a href='?id=$hierarchy_entry->id&taxon_concept_id=$hierarchy_entry->taxon_concept_id&ENV_NAME=".$GLOBALS['ENV_NAME']."'>-</a>) ";
    else $display .= "(<a href='?id=$hierarchy_entry->id&taxon_concept_id=$hierarchy_entry->taxon_concept_id&expand=1&ENV_NAME=".$GLOBALS['ENV_NAME']."'>+</a>) ";
    $display .= "<small><b><u>[$hierarchy_entry->lft]</u></b></small> <a href='?id=$hierarchy_entry->id&taxon_concept_id=$hierarchy_entry->taxon_concept_id&ENV_NAME=".$GLOBALS['ENV_NAME']."'>".$hierarchy_entry->name->string."</a> <small><b><u>[$hierarchy_entry->rgt]</u></b></small>";
    
    if(@$rank = $hierarchy_entry->rank->label) $display .= " <small>($rank)</small>";
    if($agents = $hierarchy_entry->agents)
    {
        $arr = array();
        foreach($agents as $agent)
        {
            $arr[] = $agent->full_name;
        }
        
        $display .= " <small>(".implode(", ", $arr).")</small>";
    }
    
    $display .= " <small>he_id:".$hierarchy_entry->id." ; tc_id:".$hierarchy_entry->taxon_concept_id."; id:".$hierarchy_entry->identifier." ;</small>";
    if($cf = @$hierarchy_entry->name->ranked_canonical_form->string) $display .= str_repeat("&nbsp;", 10)."<small>$cf</small>";
    else $display .= str_repeat("&nbsp;", 10)."<small>-----</small>";
    if($r = @$hierarchy_entry->rank->translation->label) $display .= str_repeat("&nbsp;", 10)."<small>$r</small>";
    $display .= "<br>";
    
    return $display;
}

function show_synonyms_he($hierarchy_entry)
{
    if($synonyms = $hierarchy_entry->synonyms())
    {
        echo "<hr>";
        foreach($synonyms as $k => $v)
        {
            echo $v->name->string;
            if($v->synonym_relation_id) echo " (".$v->synonym_relation->translation->label.")";
            $language = $v->language;
            if($label = @$language->translation->label) echo " ($label)";
            if($cf = @$v->name->ranked_canonical_form->string) echo str_repeat("&nbsp;", 10)."<small>$cf</small>";
            else echo str_repeat("&nbsp;", 10)."<small>-----</small>";
            echo "<br>";
        }
    }
}

function show_references_he($hierarchy_entry)
{
    if($references = $hierarchy_entry->references)
    {
        echo "<hr>";
        foreach($references as $r)
        {
            echo "=> $r->full_reference<br>";
        }
    }
}

function show_kingdoms_he($hierarchy_id)
{
    global $mysqli;
    
    $kingdoms = array();
    
    $result = $mysqli->query("SELECT * FROM hierarchy_entries WHERE parent_id=0 AND hierarchy_id=$hierarchy_id");
    while($result && $row=$result->fetch_assoc())
    {
        $kingdoms[] = HierarchyEntry::find($row["id"]);
    }
    
    @usort($kingdoms, "Functions::cmp_hierarchy_entries");
    
    foreach($kingdoms as $k => $v)
    {
        echo show_name_he($v, 0, 0);
    }
    
    echo "<hr>";
}


?>
