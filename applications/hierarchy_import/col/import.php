<?php
exit;
include_once(dirname(__FILE__)."/../../../config/environment.php");


$mysqli =& $GLOBALS['mysqli_connection'];
$mysqli_col = load_mysql_environment("col2010");


//$mysqli->truncate_tables("test_db");
$mysqli->begin_transaction();


$GLOBALS['agents_file_path'] = temp_filepath();
$GLOBALS['synonyms_file_path'] = temp_filepath();
if(!($GLOBALS['agents_file'] = fopen($GLOBALS['agents_file_path'], "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $GLOBALS['agents_file_path']);
}
if(!($GLOBALS['synonyms_file'] = fopen($GLOBALS['synonyms_file_path'], "w+")))
{
  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $GLOBALS['synonyms_file_path']);
}

get_agents();
get_parents();
add_hierarchy();
start_process();
Tasks::rebuild_nested_set($GLOBALS['hierarchy']->id);

fclose($GLOBALS['agents_file']);
fclose($GLOBALS['synonyms_file']);
$mysqli->load_data_infile($GLOBALS['agents_file_path'], 'agents_hierarchy_entries'); 
$mysqli->load_data_infile($GLOBALS['synonyms_file_path'], 'synonyms'); 
// 
// unlink($GLOBALS['agents_file_path']);
// unlink($GLOBALS['synonyms_file_path']);

$mysqli->end_transaction();
exit;












function get_parents()
{
    global $mysqli_col;
    
    $result = $mysqli_col->query("SELECT t.record_id id, t.parent_id FROM taxa t WHERE is_accepted_name=1");
    while($result && $row=$result->fetch_assoc())
    {
        $GLOBALS['children'][$row['parent_id']][] = $row['id'];
    }
    if($result && $result->num_rows) $result->free();
}




function get_agents()
{
    global $mysqli_col;
    
    $GLOBALS['agent_ids'] = array();
    
    $result = $mysqli_col->query("SELECT * FROM `databases` ORDER BY record_id");
    while($result && $row=$result->fetch_assoc())
    {
        $database_id = $row["record_id"];
        $database_url = $row["web_site"];
        $database_url = preg_replace("/^#/", "", $database_url);
        $database_url = preg_replace("/#.*$/", "", $database_url);
        
        $database_name = html_entity_decode(htmlspecialchars_decode(trim($row["database_full_name"])), ENT_COMPAT, "UTF-8");
        $contact_name = html_entity_decode(htmlspecialchars_decode(trim($row["contact_person"])), ENT_COMPAT, "UTF-8");
        
        $database_mock = Functions::mock_object("Agent", array( "full_name"     => $database_name,
                                                                "display_name"  => $database_name,
                                                                "homepage"      => $database_url));
        $database_agent_id = Agent::insert($database_mock);
        
        
        
        $contact_mock = Functions::mock_object("Agent", array(  "full_name"     => $contact_name,
                                                                "display_name"  => $contact_name));
        $contact_agent_id = Agent::insert($contact_mock);
        
        $GLOBALS['agent_ids'][$database_id][AgentRole::insert("Source Database")] = $database_agent_id;
        $GLOBALS['agent_ids'][$database_id][AgentRole::insert("Source")] = $contact_agent_id;
    }
}

function add_hierarchy()
{
    global $mysqli;
    
    $agent_params = array(      "full_name"     => "Catalogue of Life",
                                "acronym"       => "CoLP",
                                "homepage"      => "http://www.catalogueoflife.org/");
                                
    $agent_id = Agent::insert(Functions::mock_object("Agent", $agent_params));
    $hierarchy_id = Hierarchy::find_by_agent_id($agent_id);
    if($hierarchy_id)
    {
        $hierarchy = new Hierarchy($hierarchy_id);
        $hierarchy_group_id = $hierarchy->hierarchy_group_id;
        $hierarchy_group_version = $hierarchy->latest_group_version()+1;
    }else
    {
        $hierarchy_group_id = Hierarchy::next_group_id();
        $hierarchy_group_version = 1;
    }
    
    $hierarchy_params = array(  "label"                     => "Species 2000 & ITIS Catalogue of Life: Annual Checklist 2010",
                                "description"               => "2010 edition",
                                "agent_id"                  => $agent_id,
                                "hierarchy_group_id"        => $hierarchy_group_id,
                                "hierarchy_group_version"   => $hierarchy_group_version);
    
    $GLOBALS['hierarchy'] = new Hierarchy(Hierarchy::insert($hierarchy_params));
}

function start_process()
{
    global $mysqli_col;
    
    $result = $mysqli_col->query("SELECT t.record_id id, t.lsid, t.name taxon_name, t.taxon rank, t.parent_id, t.name_code, t.is_accepted_name, sn.genus, sn.species, sn.infraspecies, sn.infraspecies_marker, sn.author, sn.database_id FROM taxa t LEFT JOIN scientific_names sn ON (t.name_code=sn.name_code) WHERE t.record_id IN (". implode(",", @$GLOBALS['children'][0]) .")");
    $i=0;
    while($result && $row=$result->fetch_assoc())
    {
        $i++;
        //if($i<4) continue;
        //if($row['id']!=2242856) continue;
        add_col_taxon($row, 0, '', 0);
    }
}

function add_col_taxon($row, $parent_hierarchy_entry_id, $ancestry, $depth)
{
    global $mysqli_col;
    global $mysqli;
    
    static $counter = 0;
    if($counter % 1000 == 0) echo "counter: $counter; memory: ".memory_get_usage()."; time: ".Functions::time_elapsed()."\n";
    $counter++;
    
    //if($depth==5) return;
    if($counter % 200 == 0)
    {
        echo "COMMITING\n";
        $mysqli->commit();
    }
    
    //if($counter>5000) return false;
    
    $id = $row["id"];
    $lsid = $row["lsid"];
    $name_code = $row["name_code"];
    $database_id = $row["database_id"];
    $is_accepted_name = $row["is_accepted_name"];
    $taxon_name = html_entity_decode(htmlspecialchars_decode(trim($row["taxon_name"])), ENT_COMPAT, "UTF-8");
    $rank_id = Rank::insert($row["rank"]);
    $scientific = true;
    
    if($name_code)
    {
        $genus = html_entity_decode(htmlspecialchars_decode(trim($row["genus"])), ENT_COMPAT, "UTF-8");
        $species = html_entity_decode(htmlspecialchars_decode(trim($row["species"])), ENT_COMPAT, "UTF-8");
        $infraspecies = html_entity_decode(htmlspecialchars_decode(trim($row["infraspecies"])), ENT_COMPAT, "UTF-8");
        $infraspecies_marker = trim($row["infraspecies_marker"]);
        $author = html_entity_decode(htmlspecialchars_decode(trim($row["author"])), ENT_COMPAT, "UTF-8");
        $database_id = trim($row["database_id"]);
        list($name_string, $canonical_form) = create_col_name($genus, $species, $infraspecies, $infraspecies_marker, $author, $database_id);
        
        if($database_id == 14) $scientific = false;
    }else
    {
        $name_string = $taxon_name;
        $canonical_form = $name_string;
        
        if(preg_match("/ /", $name_string))
        {
            $canonical_form = "";
            $scientific = false;
        }
    }
    if(!$name_string)
    {
        $name_string = 'Not assigned';
        echo "$id: no name_string\n";
        //return;
    }
    
    $name_id = Name::insert($name_string, $canonical_form);
    // if($scientific)
    // {
    //     //Name::make_scientific_by_name_id($name_id);
    //     
    //     if($canonical_form && $canonical_form != $name_string)
    //     {
    //         $canonical_form_name_id = Name::insert($canonical_form, $canonical_form);
    //         //Name::make_scientific_by_name_id($canonical_form_name_id);
    //     }
    // }
    
    if(!$name_id)
    {
        echo "$id: no name_id\n";
        return;
    }
    
    
    $params = array("identifier"    => $id,
                    "name_id"       => $name_id,
                    "parent_id"     => $parent_hierarchy_entry_id,
                    "hierarchy_id"  => $GLOBALS['hierarchy']->id,
                    "rank_id"       => $rank_id,
                    "ancestry"      => $ancestry);
    
    $hierarchy_entry_id = HierarchyEntry::insert($params, true);
    //$hierarchy_entry = new HierarchyEntry($hierarchy_entry_id);
    unset($params);
    
    if($name_code)
    {
        add_col_synonyms($hierarchy_entry_id, $name_code);
        add_col_common_names($hierarchy_entry_id, $name_code);
        add_col_agents($hierarchy_entry_id, $database_id);
    }
    
    if($ancestry) $ancestry .= "|".$name_id;
    else $ancestry = $name_id;
    
    if(@$GLOBALS['children'][$id])
    {
        $result = $mysqli_col->query("SELECT t.record_id id, t.lsid, t.name taxon_name, t.taxon rank, t.parent_id, t.name_code, t.is_accepted_name, sn.genus, sn.species, sn.infraspecies, sn.infraspecies_marker, sn.author, sn.database_id FROM taxa t LEFT JOIN scientific_names sn ON (t.name_code=sn.name_code) WHERE t.record_id IN (". implode(",", $GLOBALS['children'][$id]) .")");
        while($result && $row=$result->fetch_assoc())
        {
            add_col_taxon($row, $hierarchy_entry_id, $ancestry, $depth+1);
        }
    }
}

function create_col_name($genus, $species, $infraspecies, $infraspecies_marker, $author, $database_id)
{
    $name_string = "";
    $canonical_form = "";
    
    // its a virus
    if($database_id == 14)
    {
        $name_string = $species;
        $canonical_form = $species;
    }else
    {
        $name_string = $genus;
        $canonical_form = $genus;
        if($species)
        {
            $name_string .= " $species";
            $canonical_form .= " $species";
        }
        
        if($infraspecies_marker) $name_string .= " $infraspecies_marker";
        
        if($infraspecies)
        {
            $name_string .= " $infraspecies";
            $canonical_form .= " $infraspecies";
        }
        if($author) $name_string .= " $author";
    }
    
    return array($name_string, $canonical_form);
}

function add_col_synonyms($hierarchy_entry_id, $name_code)
{
    global $mysqli;
    global $mysqli_col;
    
    $result = $mysqli_col->query("SELECT * FROM scientific_names sn LEFT JOIN sp2000_statuses st ON (sn.sp2000_status_id=st.record_id) WHERE accepted_name_code='$name_code' AND name_code != accepted_name_code");
    $hierarchy_id = $GLOBALS['hierarchy']->id;
    while($result && $row=$result->fetch_assoc())
    {
        $genus = html_entity_decode(htmlspecialchars_decode(trim($row["genus"])), ENT_COMPAT, "UTF-8");
        $species = html_entity_decode(htmlspecialchars_decode(trim($row["species"])), ENT_COMPAT, "UTF-8");
        $infraspecies = html_entity_decode(htmlspecialchars_decode(trim($row["infraspecies"])), ENT_COMPAT, "UTF-8");
        $infraspecies_marker = trim($row["infraspecies_marker"]);
        $author = html_entity_decode(htmlspecialchars_decode(trim($row["author"])), ENT_COMPAT, "UTF-8");
        $database_id = trim($row["database_id"]);
        $relationship_id = SynonymRelation::insert(trim($row["sp2000_status"]));
        $scientific = true;
        list($name_string, $canonical_form) = create_col_name($genus, $species, $infraspecies, $infraspecies_marker, $author, $database_id);
        
        if($database_id == 14) $scientific = false;
        if(!$name_string) continue;
        
        $name_id = Name::insert($name_string, $canonical_form);
        // if($scientific)
        // {
        //     //Name::make_scientific_by_name_id($name_id);
        //     
        //     if($canonical_form && $canonical_form != $name_string)
        //     {
        //         $canonical_form_name_id = Name::insert($canonical_form, $canonical_form);
        //         //Name::make_scientific_by_name_id($canonical_form_name_id);
        //     }
        // }
        
        fwrite($GLOBALS['synonyms_file'], "NULL\t$name_id\t$relationship_id\t0\t$hierarchy_entry_id\t0\t$hierarchy_id\t0\t0\n");
    }
    if($result && $result->num_rows) $result->free();
}

function add_col_common_names($hierarchy_entry_id, $name_code)
{
    global $mysqli;
    global $mysqli_col;
    
    $result = $mysqli_col->query("SELECT common_name, language FROM common_names WHERE name_code='$name_code'");
    $common_name_id = SynonymRelation::insert("Common name");
    $hierarchy_id = $GLOBALS['hierarchy']->id;
    while($result && $row=$result->fetch_assoc())
    {
        $name_string = html_entity_decode(htmlspecialchars_decode(trim($row["common_name"])), ENT_COMPAT, "UTF-8");
        $language_id = Language::find_or_create_for_parser(trim($row["language"]))->id;
        
        $name_id = Name::insert($name_string);
        fwrite($GLOBALS['synonyms_file'], "NULL\t$name_id\t$common_name_id\t$language_id\t$hierarchy_entry_id\t0\t$hierarchy_id\t0\t0\n");
    }
    if($result && $result->num_rows) $result->free();
}

function add_col_agents($hierarchy_entry_id, $database_id)
{
    global $mysqli;
    if($database_id)
    {
        foreach(@$GLOBALS['agent_ids'][$database_id] as $role_id => $agent_id)
        {
            fwrite($GLOBALS['agents_file'], "$hierarchy_entry_id\t$agent_id\t$role_id\t0\n");
        }
    }
}

?>