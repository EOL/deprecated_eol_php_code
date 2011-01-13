<?php




system("clear");

//$argv[1] = "-f";
//$argv[2] = "itis100308.tar.gz";

if(@$argv[1]!="-f" || !preg_match("/^itis[0-9]{6}\.tar\.gz$/i",@$argv[2]))
{
    echo "\n\nIncorrect parameters:\n\n\titis.php -f [itisYYMMDD.tar.gz]\n\n\n\n";
    exit;
}







include_once(dirname(__FILE__)."/../../../config/environment.php");

$mysqli =& $GLOBALS['db_connection'];
//$mysqli->truncate_tables();
$mysqli->begin_transaction();




$filename = $argv[2];



if(preg_match("/^(itis)([0-9]{6})(\.tar)\.gz$/i",$filename,$arr))
{
    define("EXPORT_FILE",   $arr[1].$arr[2].$arr[3]);
    define("EXPORT_FOLDER", $arr[1].".".$arr[2]);
}

define("DOWNLOAD_ROOT",   "../downloads/");



get_file_names();
get_ranks();
$mysqli->commit();
get_authors();
$mysqli->commit();
get_vernaculars();
$mysqli->commit();
get_synonyms();
$mysqli->commit();
get_names();
$mysqli->commit();
add_vernaculars();
$mysqli->commit();
add_hierarchy();
$mysqli->commit();

Tasks::rebuild_nested_set($GLOBALS['hierarchy']->id);


//print_r($GLOBALS['filenames']);
//print_r($GLOBALS['ranks']);
//print_r($GLOBALS['authors']);
//print_r($GLOBALS['vernaculars']);
//print_r($GLOBALS['synonyms']);
//print_r($GLOBALS['names']);
//print_r($GLOBALS['parents']);
//print_r($GLOBALS['children']);



$mysqli->end_transaction();










function get_file_names()
{
    $GLOBALS['filenames'] = array();
    
    $flag = false;
    $FILE = file(DOWNLOAD_ROOT.EXPORT_FOLDER."/itis.sql");
    foreach($FILE as $line)
    {
        $line = trim($line);
        
        if(preg_match("/{ table \"itis\"\.([^ ]+) /i",$line, $arr))
        {
            $flag = $arr[1];
            continue;
        }
        
        if($flag && preg_match("/{ unload file name = ([^ ]+) /",$line,$arr))
        {
            $GLOBALS['filenames'][$flag] = $arr[1];
            $flag = false;
        }
        
        unset($line);
    }
    unset($FILE);
}

function get_ranks()
{
    //0    kingdom_id integer not null
    //1    rank_id smallint not null
    //2    rank_name char(15) not null
    //3    dir_parent_rank_id smallint not null
    //4    req_parent_rank_id smallint not null
    //5    update_date date not null
    
    $GLOBALS['ranks'] = array();
    $GLOBALS['rank_names'] = array();
    
    $FILE = file(DOWNLOAD_ROOT.EXPORT_FOLDER."/".$GLOBALS['filenames']['taxon_unit_types']);
    foreach($FILE as $line)
    {
        $line_data  = explode("|", trim($line));
        $id         = trim($line_data[1]);
        $GLOBALS['ranks'][$id] = Rank::insert(trim($line_data[2]));
        
        unset($line_data);
        unset($line);
    }
    unset($FILE);
}


function get_authors()
{
    //0    taxon_author_id serial not null
    //1    taxon_author varchar(100,30) not null
    //2    update_date date not null
    //3    kingdom_id smallint not null
    
    $GLOBALS['authors'] = array();
    
    $FILE = file(DOWNLOAD_ROOT.EXPORT_FOLDER."/".$GLOBALS['filenames']['taxon_authors_lkp']);
    foreach($FILE as $line)
    {
        $line_data  = explode("|", trim($line));
        $id         = trim($line_data[0]);
        $GLOBALS['authors'][$id] = trim($line_data[1]);
        
        unset($id);
        unset($line_data);
        unset($line);
    }
    unset($FILE);
}


function get_vernaculars()
{
    global $mysqli;
    //0    tsn integer not null
    //1    vernacular_name varchar(80,5) not null
    //2    language varchar(15) not null
    //3    approved_ind char(1)
    //4    update_date date not null
    //5    primary key (tsn,vernacular_name,language)  constraint "itis".vernaculars_key
    
    $GLOBALS['vernaculars'] = array();
    
    $FILE = file(DOWNLOAD_ROOT.EXPORT_FOLDER."/".$GLOBALS['filenames']['vernaculars']);
    $i = 0;
    foreach($FILE as $line)
    {
        if($i%10000==0)
        {
            debug("Vernaculars Line: $i");
            $mysqli->commit();
        }
        
        $i++;
        $line_data      = explode("|", trim($line));
        $name_tsn       = trim($line_data[0]);
        $string         = trim(utf8_encode($line_data[1]));
        $language       = trim($line_data[2]);
        
        if($language == "unspecified") $language = "Common name";
        
        $GLOBALS['vernaculars'][$name_tsn][] = array("name_id" => Name::insert($string), "language_id" => Language::insert($language));
        // if($i >= 1000) break;
        
        unset($name_tsn);
        unset($string);
        unset($language);
        unset($line_data);
        unset($line);
    }
    unset($FILE);
}


function get_synonyms()
{
    //0    tsn integer not null
    //1    tsn_accepted integer not null
    //2    update_date date not null
    
    $GLOBALS['synonyms'] = array();
    $GLOBALS['synonym_of'] = array();
    
    $FILE = file(DOWNLOAD_ROOT.EXPORT_FOLDER."/".$GLOBALS['filenames']['synonym_links']);
    foreach($FILE as $line)
    {
        $line_data          = explode("|", trim($line));
        $synonym_name_tsn   = trim($line_data[0]);
        $accepted_name_tsn  = trim($line_data[1]);
        
        $GLOBALS['synonyms'][$accepted_name_tsn][$synonym_name_tsn] = true;
        $GLOBALS['synonym_of'][$synonym_name_tsn] = $accepted_name_tsn;
        
        unset($synonym_name_tsn);
        unset($accepted_name_tsn);
        unset($line_data);
        unset($line);
    }
    unset($FILE);
}


function get_names()
{
    global $mysqli;
    
    //0    tsn serial not null
    //1    unit_ind1 char(1)
    //2    unit_name1 char(35) not null
    //3    unit_ind2 char(1)
    //4    unit_name2 varchar(35)
    //5    unit_ind3 varchar(7)
    //6    unit_name3 varchar(35)
    //7    unit_ind4 varchar(7)
    //8    unit_name4 varchar(35)
    //9    unnamed_taxon_ind char(1)
    //10   usage varchar(12,5) not null
    //11   unaccept_reason varchar(50,9)
    //12   credibility_rtng varchar(40,17) not null
    //13   completeness_rtng char(10)
    //14   currency_rating char(7)
    //15   phylo_sort_seq smallint
    //16   initial_time_stamp datetime year to second not null
    //17   parent_tsn integer
    //18   taxon_author_id integer
    //19   hybrid_author_id integer
    //20   kingdom_id smallint not null
    //21   rank_id smallint not null
    //22   update_date date not null
    //23   uncertain_prnt_ind char(3)
    
    $GLOBALS['names'] = array();
    $GLOBALS['parents'] = array();
    $GLOBALS['children'] = array();
    
    $FILE = file(DOWNLOAD_ROOT.EXPORT_FOLDER."/".$GLOBALS['filenames']['taxonomic_units']);
    $i = 0;
    foreach($FILE as $line)
    {
        if($i%10000==0)
        {
            debug("Names Line: $i");
            $mysqli->commit();
        }
        $i++;
        // if($i >= 1000 && $i <= 202300) continue;
        $line_data = explode("|", trim($line));
        
        $name_tsn       = trim($line_data[0]);
        $x1             = trim($line_data[1]);
        $name_part_1    = trim($line_data[2]);
        $x2             = trim($line_data[3]);
        $name_part_2    = trim($line_data[4]);
        $sp_marker_1    = trim($line_data[5]);
        $name_part_3    = trim($line_data[6]);
        $sp_marker_2    = trim($line_data[7]);
        $name_part_4    = trim($line_data[8]);
        $reason         = trim($line_data[11]);
        $parent_tsn     = trim($line_data[17]);
        $author_id      = trim($line_data[18]);
        $rank_id        = trim($line_data[21]);
        
        if(!$parent_tsn) $parent_tsn = 0;
        $GLOBALS['parents'][$name_tsn] = $parent_tsn;
        $GLOBALS['children'][$parent_tsn][$name_tsn] = 1;
        
        $name_string = $name_part_1;
        if($x1)             $name_string = $x1." ".$name_string;
        if($x2)             $name_string.=" ".$x2;
        if($name_part_2)    $name_string.=" ".$name_part_2;
        if($sp_marker_1)    $name_string.=" ".$sp_marker_1;
        if($name_part_3)    $name_string.=" ".$name_part_3;
        if($sp_marker_2)    $name_string.=" ".$sp_marker_2;
        if($name_part_4)    $name_string.=" ".$name_part_4;
        if(@$GLOBALS['authors'][$author_id]) $name_string.=" ".$GLOBALS['authors'][$author_id];
        
        $canonical_form = $name_part_1;
        if($name_part_2)    $canonical_form.=" ".$name_part_2;
        if($name_part_3)    $canonical_form.=" ".$name_part_3;
        if($name_part_4)    $canonical_form.=" ".$name_part_4;
        
        $name_string    = trim(utf8_encode($name_string));
        $canonical_form = trim(utf8_encode($canonical_form));
        
        $name_id = Name::insert($name_string, $canonical_form);
        // Name::make_scientific_by_name_id($name_id);
        $cf_name_id = Name::insert($canonical_form, $canonical_form);
        // Name::make_scientific_by_name_id($cf_name_id);
        
        $GLOBALS['names'][$name_tsn] = array(
                                            "name_id"           => $name_id,
                                            "unaccept_reason"   => $reason,
                                            "parent_tsn"        => $parent_tsn,
                                            "rank_id"           => $GLOBALS['ranks'][$rank_id]);
        
        unset($name_string);
        unset($canonical_form);
        unset($name_id);
        unset($cf_name_id);
        unset($name_tsn);
        unset($x1);
        unset($name_part_1);
        unset($x2);
        unset($name_part_2);
        unset($sp_marker_1);
        unset($name_part_3);
        unset($sp_marker_2);
        unset($name_part_4);
        unset($reason);
        unset($parent_tsn);
        unset($author_id);
        unset($rank_id);
        unset($line_data);
        unset($line);
        
        // if($i >= 203300) break;
    }
    unset($FILE);
}


// function add_vernaculars()
// {
//     foreach($GLOBALS['vernaculars'] as $name_tsn => $array)
//     {
//         if(@!$GLOBALS['names'][$name_tsn]) continue;
//         foreach($array as $vern_array)
//         {
//             $name = new Name($vern_array["name_id"]);
//             // $name->add_language($vern_array["language_id"], $GLOBALS['names'][$name_tsn]["name_id"], 0);
//             unset($name);
//         }
//         
//         unset($array);
//     }
// }


function add_hierarchy()
{
    global $mysqli;
    
    $agent_params = array(      "full_name"     => "Integrated Taxonomic Information System",
                                "acronym"       => "ITIS",
                                "homepage"      => "http://www.itis.gov/");
                                
    $itis_agent_id = Agent::insert(Functions::mock_object("Agent", $agent_params));
    $itis_hierarchy_id = Hierarchy::find_by_agent_id($itis_agent_id);
    if($itis_hierarchy_id)
    {
        $itis_hierarchy = new Hierarchy($itis_hierarchy_id);
        $hierarchy_group_id = $itis_hierarchy->hierarchy_group_id;
        $hierarchy_group_version = $itis_hierarchy->latest_group_version()+1;
    }else
    {
        $hierarchy_group_id = Hierarchy::next_group_id();
        $hierarchy_group_version = 1;
    }
    
    $hierarchy_params = array(  "label"                     => "Integrated Taxonomic Information System",
                                "description"               => "latest export",
                                "agent_id"                  => $itis_agent_id,
                                "hierarchy_group_id"        => $hierarchy_group_id,
                                "hierarchy_group_version"   => $hierarchy_group_version);
    
    $GLOBALS['hierarchy'] = new Hierarchy(Hierarchy::insert(Functions::mock_object("Hierarchy", $hierarchy_params)));
    
    foreach($GLOBALS['children'][0] as $child_name_tsn => $value)
    {
        add_hierarchy_entry($child_name_tsn, 0, "");
        $mysqli->commit();
    }
}


function add_hierarchy_entry($name_tsn, $parent_hierarchy_entry_id, $ancestry)
{
    $name_array = @$GLOBALS['names'][$name_tsn];
    if(!$name_array) return false;
    
    if(!$name_array["unaccept_reason"] && @!$GLOBALS['synonym_of'][$name_tsn])
    {
        $parent_name_id = $name_array["parent_tsn"] ? $GLOBALS['names'][$name_array["parent_tsn"]]["name_id"] : 0;
        
        $params = array("identifier"    => $name_tsn,
                        "name_id"       => $name_array["name_id"],
                        "parent_id"     => $parent_hierarchy_entry_id,
                        "hierarchy_id"  => $GLOBALS['hierarchy']->id,
                        "rank_id"       => $name_array["rank_id"],
                        "ancestry"      => $ancestry);
        
        $hierarchy_entry = new HierarchyEntry(HierarchyEntry::insert($params));
        unset($params);
        
        if(@$GLOBALS['synonyms'][$name_tsn])
        {
            foreach($GLOBALS['synonyms'][$name_tsn] as $synonym_name_tsn => $value)
            {
                if($synonym_array = @$GLOBALS['names'][$synonym_name_tsn])
                {
                    $hierarchy_entry->add_synonym($synonym_array["name_id"], SynonymRelation::insert($synonym_array["unaccept_reason"]), 0, 0);
                    unset($synonym_array);
                }
            }
            unset($GLOBALS['synonyms'][$name_tsn]);
        }
        
        if(@$GLOBALS['vernaculars'][$name_tsn])
        {
            foreach($GLOBALS['vernaculars'][$name_tsn] as $array)
            {
                $hierarchy_entry->add_synonym($array["name_id"], SynonymRelation::insert("Common name"), $array["language_id"], 0);
            }
            unset($GLOBALS['vernaculars'][$name_tsn]);
        }
        
        if($ancestry) $ancestry .= "|".$name_array["name_id"];
        else $ancestry = $name_array["name_id"];
        
        if(@$GLOBALS['children'][$name_tsn])
        {
            foreach($GLOBALS['children'][$name_tsn] as $child_name_tsn => $value)
            {
                add_hierarchy_entry($child_name_tsn, $hierarchy_entry->id, $ancestry);
            }
            unset($GLOBALS['children'][$name_tsn]);
        }
        
        unset($hierarchy_entry);
    }
}

?>