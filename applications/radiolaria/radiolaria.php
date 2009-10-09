<?php
exit;
include_once("../php/config.php");

//$GLOBALS['mysql_debug'] = true;

$mysqli =& $GLOBALS['mysqli_connection'];







$nodes_by_name = array();
$nodes_by_canonical_form = array();
$nodes_by_canonical_rank = array();
$names = array();

$names_to_match = array();
$FILE = file("files/radiolaria_matches.txt");
foreach($FILE as $k => $v)
{
    $parts = explode("\t",trim($v));
    $names_to_match[strtolower(trim($parts[0]))][] = strtolower(trim($parts[2]));
    $names_to_match[strtolower(trim($parts[2]))][] = strtolower(trim($parts[0]));
}



parse_spreadsheet("union2.txt", "1 1 1 family subfamily tribe genus binomial");
parse_spreadsheet("takahashi.txt", "subclass order suborder family subfamily binomial");
parse_spreadsheet("rad001.txt", "genus species authority synonym");
parse_spreadsheet("rad002.txt", "genus species authority synonym");
parse_spreadsheet("rad003.txt", "genus species subspecies authority synonym");
parse_spreadsheet("rad004.txt", "family genus species authority synonym");
parse_spreadsheet("rad013.txt", "subclass order suborder family subfamily genus species variety authority synonym");
parse_spreadsheet("rad015.txt", "order suborder family genus species variety authority 0 0 synonym");
parse_spreadsheet("rad017.txt", "family genus binomial");
parse_spreadsheet("rad019.txt", "kingdom phylum class subclass superorder order family genus species subspecies authority synonym");
parse_spreadsheet("rad024.txt", "genus species variety 0 authority");
parse_spreadsheet("rad025.txt", "genus species");
parse_spreadsheet("rad026.txt", "genus binomial");
parse_spreadsheet("rad027.txt", "binomial");
parse_spreadsheet("rad028.txt", "genus species variety authority");
parse_spreadsheet("rad033.txt", "class order suborder superfamily family subfamily 0 0 binomial");
// parse_spreadsheet("rad034.txt", "family genus binomial synonym");    <- something wrong with this one
parse_spreadsheet("rad035.txt", "genus species authority synonym");
parse_spreadsheet("rad099.txt", "class order family genus species subspecies authority");



$eukaryota_node = get_node("Eukaryota", "", "me");
$protista_node = get_node("Protista Haeckel 1886", "", "me");
$radioloaria_node = get_node("Radiolaria Willer, 1858", "", "me");
set_parent($protista_node, $eukaryota_node);

$unassigned = get_node("Unassigned radiolaria", "family", "me");
set_parent($unassigned, $radioloaria_node);

$unassigned_genera = get_node("Unassigned radiolaria genera", "genus", "me");
set_parent($unassigned_genera, $unassigned);

ksort($nodes_by_name);
foreach($nodes_by_name as $k => $v)
{
    if(!$v->parent && $v->id!=$eukaryota_node->id && $v->id!=$protista_node->id && $v->id!=$radioloaria_node->id)
    {
        if($v->rank=="genus") set_parent($v, $unassigned_genera);
        else set_parent($v, $unassigned);
    }
}



ksort($nodes_by_name);
foreach($nodes_by_name as $k => $v)
{
    if(!$v->parent)
    {
        show_children($v,0);
        //show_list($v,"");
        //insert_this($v, 0, "");
    }
}



//exit;
$mysqli->begin_transaction();

//$mysqli->query("DELETE FROM hierarchy_entries WHERE hierarchy_id=108");
$mysqli->query("DELETE FROM hierarchy_entries WHERE hierarchy_id=109");

ksort($nodes_by_name);
foreach($nodes_by_name as $k => $v)
{
    if(!$v->parent)
    {
        //show_children($v,0);
        //show_list($v,"");
        insert_this($v, 0, "", 109);
    }
}

$mysqli->end_transaction();





function create_union()
{
    global $nodes_by_name;
    global $nodes_by_canonical_form;
    global $names;
    
    $FILE = file("files/union.txt");
    foreach($FILE as $k => $v)
    {
        $v = trim($v);

        //if(preg_match("/^\"([^\"]+)\";\"([^\"]+)\"$/",$v,$arr))
        if(preg_match("/^([^\t]+)\t([^\t]+)$/",$v,$arr))
        {
            $child = trim($arr[1]);
            $parent = trim($arr[2]);

            $node = get_node($child, "", "union");

            if($parent = get_node($parent, "", "union"))
            {
                set_parent($node,$parent);
            }

            //echo "$child - $parent<br>";
        }
    }
}

function parse_spreadsheet($file, $columns)
{
    global $nodes_by_name;
    global $nodes_by_canonical_form;
    global $nodes_by_canonical_rank;
    global $names;
    global $names_to_match;
    
    $column_ranks = explode(" ", $columns);
    $rank_indices = array_flip($column_ranks);

    $FILE = file("files/".$file);
    foreach($FILE as $k => $v)
    {
        $names = explode("\t",$v);
        
        $node_column = 0;
        $authority = "";
        foreach($names as $k => $v)
        {
            $name = trim($v);
            $names[$k] = $name;
            if($column_ranks[$k]=="authority")
            {
                $authority = $name;
                break;
            }
            if($name) $node_column = $k;
        }
        
        $parent_node = 0;
        foreach($names as $k => $v)
        {
            $name = trim($v);
            if(!$name) continue;
            $rank = $column_ranks[$k];
            if($rank=="0") continue;
            if($rank=="synonym") break;
            if($rank=="1") $rank = "";
            
            if($k==$node_column) $name = $name." ".$authority;
            if($rank=="species") $name = $names[$rank_indices["genus"]]." ".$name;
            if($rank=="subspecies" || $rank=="variety") $name = $names[$rank_indices["genus"]]." ".$names[$rank_indices["species"]]." ".$name;
            
            $name = clean_name($name);
            
            if($rank=="binomial")
            {
                $rank = "species";
                
                if(preg_match("/^([^ ]+) /",$name,$arr))
                {
                    $genus = get_node($arr[1], "genus", $file);
                    
                    if($parent_node)
                    {
                        set_parent($genus,$parent_node);
                    }
                    
                    $parent_node = $genus;
                }
            }
            
            if($rank=="binomial" || $rank=="species")
            {    
                if(preg_match("/^([^ ]+) ([^ ]+) ([^ ]+)/",canonical_form($name),$arr))
                {
                    $species = get_node(ucfirst($arr[1])." ".$arr[2], "species", $file);
                    $rank = "subspecies";
                    
                    if($parent_node)
                    {
                        set_parent($species,$parent_node);
                    }
                    
                    $parent_node = $species;
                }
            }
            
            $node = get_node($name, $rank, $file);
            
            if($parent_node)
            {
                set_parent($node,$parent_node);
            }
            
            $parent_node = $node;
            
            if($k==$node_column) break;
        }
        
        if($parent_node && $synonym = @trim($names[$rank_indices["synonym"]]))
        {
            $synonym = clean_name($synonym);
            $parent_node->add_synonym($synonym, "synonym", $file);
        }
    }
}

function insert_this($node, $parent_id, $ancestry, $hierarchy_id)
{
    global $mysqli;
    
    $hierarchy_entry_parameters = array();
    $hierarchy_entry_parameters["name_id"] = Name::insert($node->name);
    $hierarchy_entry_parameters["parent_id"] = $parent_id;
    $hierarchy_entry_parameters["hierarchy_id"] = $hierarchy_id;
    $hierarchy_entry_parameters["rank_id"] = Rank::insert($node->rank);
    $hierarchy_entry_parameters["ancestry"] = $ancestry;
    
    $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($hierarchy_entry_parameters));
    if(@!$hierarchy_entry->id) return;
    
    if($synonyms = $node->synonyms)
    {
        foreach($synonyms as $k => $v)
        {
            $synonym_parameters = array();
            $synonym_parameters["name_id"] = Name::insert($v->name);
            $synonym_parameters["synonym_relation_id"] = SynonymRelation::insert("Synonym");
            $synonym_parameters["language_id"] = 0;
            $synonym_parameters["hierarchy_entry_id"] = $hierarchy_entry->id;
            $synonym_parameters["preferred"] = 0;
            $synonym_parameters["hierarchy_id"] = $hierarchy_id;
            
            $synonym = new Synonym(Synonym::insert($synonym_parameters));
            
            $synonym_source_id = Agent::insert($v->source, "", "");
            if($synonym_source_id)
            {
                $synonym->add_agent($synonym_source_id, "Source database", 1);
            }
        }
    }
    
    $agent_id = Agent::insert($node->source, "", "");
    if($agent_id)
    {
        $hierarchy_entry->add_agent($agent_id, AgentRole::insert("Source database"), 1);
    }
    
    if($ancestry) $ancestry .= "|".$hierarchy_entry->id;
    else $ancestry = $hierarchy_entry->id;
    
    if($children = $node->children)
    {
        foreach($children as $k => $v)
        {
            insert_this($v, $hierarchy_entry->id, $ancestry, $hierarchy_id);
        }
    }
}

function find_node_by_name($name, $canonical_form, $rank)
{
    global $nodes_by_name;
    global $nodes_by_canonical_form;
    global $nodes_by_canonical_rank;
    global $names;
    global $names_to_match;
    
    $node = @$nodes_by_name[strtolower($name)];
    if(!$node && @$names_to_match[strtolower($name)])
    {
        foreach($names_to_match[strtolower($name)] as $k => $v)
        {
            if($node) break;
            $node = @$nodes_by_name[$v];
        }
    }
    
    if(!$node && $rank=="species")
    {
        $node = @$nodes_by_canonical_rank[$canonical_form."|species"];
    }elseif(!$node && ($rank=="subspecies" || $rank=="variety"))
    {
        $node = @$nodes_by_canonical_rank[$canonical_form."|subspecies"];
    }elseif(!$node && !preg_match("/ /",$canonical_form))
    {
        $node = @$nodes_by_canonical_form[$canonical_form];
    }
    
    return $node;
}

$node_id = 1;
function create_node($name, $canonical_form, $rank)
{
    global $nodes_by_name;
    global $nodes_by_canonical_form;
    global $nodes_by_canonical_rank;
    global $names;
    global $node_id;
    
    $node = new Node($name, $node_id);
    $node_id++;
    $nodes_by_name[strtolower($name)] = $node;
    if($rank!="species" && $rank!="subspecies" && $rank!="variety") $nodes_by_canonical_form[$canonical_form] = $node;
    $nodes_by_canonical_rank[$canonical_form."|".$rank] = $node;
    if($rank=="variety") $nodes_by_canonical_rank[$canonical_form."|subspecies"] = $node;
    
    return $node;
}

function get_node($name, $rank, $source)
{
    global $nodes_by_name;
    global $nodes_by_canonical_form;
    global $nodes_by_canonical_rank;
    global $names;
    global $names_to_match;
    
    $canonical_form = canonical_form($name);
    
    if($node = find_node_by_name($name, $canonical_form, $rank))
    {
        //if(strlen($name) > strlen($node->name))
        if($name!=$node->name)
        {
            if(strlen($name) > strlen($node->name))
            {
                $node->add_synonym($node->name, "synonym", $node->source);
                
                $node->name = $name;
                $node->source = $source;
            }else $node->add_synonym($name, "synonym", $source);
            
            //if($name==$node->name && strtolower($node->name) != $canonical_form) $node->add_synonym($node->name, "synonym", $node->source);
            //if(strtolower($node->name) != $canonical_form && strlen($name) > (strlen($node->name)+1)) $node->add_synonym($node->name, "synonym", $node->source);
        }
        return $node;
    }
    
    $node = create_node($name, $canonical_form, $rank);
    $node->rank = $rank;
    $node->source = $source;
    
    $nodes_by_name[strtolower($node->name)] = $node;
    
    return $node;
}

function clean_name($name)
{
    while(preg_match("/  /",$name)) $name = str_replace("  "," ",$name);
    while(preg_match("/(^| |\()([".UPPER."]{2,})( |,|\)|$)/u",$name,$arr)) $name = str_replace($arr[2],ucfirst(strtolower($arr[2])),$name);
    return trim($name);
}

function canonical_form($name)
{
    $canonical_form = strtolower(Functions::canonical_form($name));
    if($name=="Unassigned genera of acantharea") $canonical_form = "unassigned genera of acantharea";
    if($name=="Unassigned acantharea") $canonical_form = "unassigned acantharea";
    if($name=="Beatricea? A") $canonical_form = "beatricea a";
    if($name=="Elodium? Mackenziei Carter n. sp.") $canonical_form = "elodium mackenziei";
    if($name=="Minocapsa? Megaglobosa (Matsuoka) 1991") $canonical_form = "minocapsa megaglobosa";
    if($name=="Parahsuum? A sensu Whalen & Carter 2002") $canonical_form = "parahsuum a";
    
    return $canonical_form;
}

function set_parent($child, $parent)
{
    if($child->parent)
    {
        if($child->parent && $child->parent->id!=$parent->id)
        {
            // echo "There is a problem with ".$child->name."<br>";
            // echo $child->parent->name."<br>";
            // echo $parent->name."<hr>";
        }
    }elseif($parent)
    {
        $parent->add_child($child);
        $child->set_parent($parent);
    }
}

function show_children($node, $indent)
{
    echo str_repeat("&nbsp;", $indent*8).$node->show($indent)."<br>";
    if($children = $node->children)
    {
        usort($children, "Functions::cmp_nodes");
        foreach($children as $k => $v)
        {
            show_children($v, $indent+1);
        }
    }
}

function show_list($node, $indent)
{
    static $show_list_id;
    if(!$show_list_id) $show_list_id = 1;
    echo $show_list_id."\t".$node->rank."\t".$node->source."\t\t\t".$indent.$node->name."\n";
    $show_list_id++;
    
    if($synonyms = $node->synonyms)
    {
        foreach($synonyms as $k => $v)
        {
            if(!$v->type) $v->type = "synonym";
            echo $show_list_id."\t".$node->rank."\t".$v->source."\t".$v->type."\t\t".$indent.$v->name."\n";
            $show_list_id++;
        }
    }
    if($children = $node->children)
    {
        foreach($children as $k => $v)
        {
            show_list($v, $indent.$node->name."\t");
        }
    }
}









?>