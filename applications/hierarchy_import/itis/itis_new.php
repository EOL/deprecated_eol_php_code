<?php

include_once(dirname(__FILE__) . "/../../../config/environment.php");




// shell_exec("rm -f ".dirname(__FILE__)."/itis.tar.gz");
// if($dir = dump_directory_path())
// {
//     shell_exec("rm -fr ".dirname(__FILE__)."/$dir");
// }



//  Full ITIS Data Set (Informix 7) from http://www.itis.gov/downloads/
$latest_itis_url = "http://www.itis.gov/downloads/itis121610_v3.TAR.gz";

// shell_exec("curl $latest_itis_url -o ".dirname(__FILE__)."/itis.tar.gz");
// // unzip the download
// shell_exec("tar -zxf ".dirname(__FILE__)."/itis.tar.gz");

$GLOBALS['itis_dump_dir'] = dump_directory_path();
if(!$GLOBALS['itis_dump_dir']) exit;



$GLOBALS['names'] = array();



// 
// get_file_names();
// get_ranks();
// get_authors();
// get_vernaculars();
// echo "Memory: ".memory_get_usage()."\n";
// get_synonyms();
// echo "Memory: ".memory_get_usage()."\n";
// get_names();
// 
// echo "Memory: ".memory_get_usage()."\n";
// 
// unset($GLOBALS['authors']);
// unset($GLOBALS['ranks']);
// unset($GLOBALS['vernaculars']);
// unset($GLOBALS['synonyms']);
// unset($GLOBALS['synonym_of']);
// echo "Memory: ".memory_get_usage()."\n";



$agent_params = array(  "full_name"     => "Integrated Taxonomic Information System",
                        "acronym"       => "ITIS");
                            
$agent_id = Agent::insert(Functions::mock_object("Agent", $agent_params));
$agent_hierarchy_id = Hierarchy::find_by_agent_id($agent_id);
if($agent_hierarchy_id)
{
    $agent_hierarchy = new Hierarchy($agent_hierarchy_id);
    $hierarchy_group_id = $agent_hierarchy->hierarchy_group_id;
    $hierarchy_group_version = $agent_hierarchy->latest_group_version() + 1;
    
    $hierarchy_params = array(
        "agent_id"                  => $agent_hierarchy->agent_id,
        "label"                     => $agent_hierarchy->label,
        "descriptive_label"         => $agent_hierarchy->descriptive_label,
        "description"               => $agent_hierarchy->description,
        "hierarchy_group_id"        => $agent_hierarchy->hierarchy_group_id,
        "hierarchy_group_version"   => $hierarchy_group_version,
        "url"                       => $agent_hierarchy->url,
        "outlink_uri"               => $agent_hierarchy->outlink_uri,
        "ping_host_url"             => $agent_hierarchy->ping_host_url,
        "complete"                  => $agent_hierarchy->complete);
}else
{
    $hierarchy_group_id = Hierarchy::next_group_id();
    $hierarchy_group_version = 1;
    
    $hierarchy_params = array(
        "label"                     => "Integrated Taxonomic Information System (ITIS)",
        "description"               => "latest export",
        "agent_id"                  => $agent_id,
        "url"                       => "http://www.itis.gov/",
        "outlink_uri"               => "http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=%%ID%%",
        "complete"                  => 1,
        "hierarchy_group_id"        => $hierarchy_group_id,
        "hierarchy_group_version"   => $hierarchy_group_version);
}

$hierarchy = new Hierarchy(Hierarchy::insert(Functions::mock_object("Hierarchy", $hierarchy_params)));


$uri = dirname(__FILE__) . "/out.xml";
DarwinCoreHarvester::harvest($uri, $hierarchy);






function get_file_names()
{
    $GLOBALS['filenames'] = array();
    
    $current_table_name = false;
    foreach(new FileIterator($GLOBALS['itis_dump_dir']."/itis.sql") as $line)
    {
        if(!$line) continue;
        if(preg_match("/{ table \"itis\"\.([^ ]+) /i", $line, $arr))
        {
            $current_table_name = $arr[1];
            continue;
        }
        
        if($current_table_name && preg_match("/{ unload file name = ([^ ]+) /", $line,$arr))
        {
            $GLOBALS['filenames'][$current_table_name] = $arr[1];
            $current_table_name = false;
        }
    }
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
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['taxon_unit_types'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data  = explode("|", $line);
        $id         = trim($line_data[1]);
        $GLOBALS['ranks'][$id] = trim($line_data[2]);
    }
}

function get_comments()
{
    //0     comment_id serial not null ,
    //1     commentator varchar(100),
    //2     comment_detail char(2000) not null ,
    //3     comment_time_stamp datetime year to second not null ,
    //4     update_date date not null 
    
    $GLOBALS['comments'] = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['comments'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data  = explode("|", $line);
        $id         = trim($line_data[0]);
        $comment    = trim($line_data[2]);
        $GLOBALS['comments'][$id] = $comment
    }
}

function get_comment_links()
{
    //0     tsn integer not null ,
    //1     comment_id integer not null ,
    //2     update_date date not null
    
    $GLOBALS['comment_links'] = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['tu_comments_links'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data  = explode("|", $line);
        $tsn        = trim($line_data[0]);
        $comment_id = trim($line_data[1]);
        $GLOBALS['comment_links'][$tsn][] = $comment_id;
    }
}


function get_authors()
{
    //0    taxon_author_id serial not null
    //1    taxon_author varchar(100,30) not null
    //2    update_date date not null
    //3    kingdom_id smallint not null
    
    $GLOBALS['authors'] = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['taxon_authors_lkp'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data  = explode("|", $line);
        $id         = trim($line_data[0]);
        $GLOBALS['authors'][$id] = trim($line_data[1]);
    }
}

function get_vernaculars()
{
    //0    tsn integer not null
    //1    vernacular_name varchar(80,5) not null
    //2    language varchar(15) not null
    //3    approved_ind char(1)
    //4    update_date date not null
    //5    primary key (tsn,vernacular_name,language)  constraint "itis".vernaculars_key
    
    $GLOBALS['vernaculars'] = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['vernaculars'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data      = explode("|", $line);
        $name_tsn       = trim($line_data[0]);
        $string         = trim(utf8_encode($line_data[1]));
        $language       = trim($line_data[2]);
        
        if($language == "unspecified") $language = "";
        
        $GLOBALS['vernaculars'][$name_tsn][] = array("name" => $string, "language" => $language);
    }
}

function get_synonyms()
{
    //0    tsn integer not null
    //1    tsn_accepted integer not null
    //2    update_date date not null
    
    $GLOBALS['synonyms'] = array();
    $GLOBALS['synonym_of'] = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['synonym_links'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data          = explode("|", $line);
        $synonym_name_tsn   = trim($line_data[0]);
        $accepted_name_tsn  = trim($line_data[1]);
        
        $GLOBALS['synonyms'][$accepted_name_tsn][$synonym_name_tsn] = true;
        $GLOBALS['synonym_of'][$synonym_name_tsn] = $accepted_name_tsn;
    }
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
    
    $OUT = fopen(dirname(__FILE__)."/out.xml", "w+");
    fwrite($OUT, DarwinCoreRecordSet::xml_header());
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['taxonomic_units'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data = explode("|", $line);
        
        $name_tsn       = trim($line_data[0]);
        $x1             = trim($line_data[1]);
        $name_part_1    = trim($line_data[2]);
        $x2             = trim($line_data[3]);
        $name_part_2    = trim($line_data[4]);
        $sp_marker_1    = trim($line_data[5]);
        $name_part_3    = trim($line_data[6]);
        $sp_marker_2    = trim($line_data[7]);
        $name_part_4    = trim($line_data[8]);
        $validity       = trim($line_data[10]);
        $reason         = trim($line_data[11]);
        $comp_rating    = trim($line_data[12]);
        $cred_rating    = trim($line_data[13]);
        $curr_rating    = trim($line_data[14]);
        $parent_tsn     = trim($line_data[17]);
        $author_id      = trim($line_data[18]);
        $rank_id        = trim($line_data[21]);
        
        if(!$parent_tsn) $parent_tsn = 0;
        
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
        
        $remarks = "";
        if($comp_rating && $comp_rating != "unknown") $remarks .= "Completeness: $comp_rating. ";
        if($cred_rating && $cred_rating != "unknown") $remarks .= "Credibility: $cred_rating. ";
        if($curr_rating && $curr_rating != "unknown") $remarks .= "Currency: $curr_rating. ";
        $remarks = trim($remarks);
        
        if(isset($GLOBALS['synonym_of'][$name_tsn]))
        {
            $dwc_taxon = new DarwinCoreTaxon(array(
                    "taxonID"           => $name_tsn,
                    "scientificName"    => $name_string,
                    "parentNameUsageID" => $GLOBALS['synonym_of'][$name_tsn],
                    "taxonRank"         => $GLOBALS['ranks'][$rank_id],
                    "taxonRemarks"      => $remarks,
                    "taxonomicStatus"   => $reason));
            fwrite($OUT, $dwc_taxon->__toXML());
        }else
        {
            // first loop and find all vernacular names
            $vernacular_names = array();
            if(isset($GLOBALS['vernaculars'][$name_tsn]))
            {
                foreach($GLOBALS['vernaculars'][$name_tsn] as $name_hash)
                {
                    $vernacular_names[$name_hash['language']][] = $name_hash['name'];
                }
            }
            
            $dwc_taxon = new DarwinCoreTaxon(array(
                    "taxonID"           => $name_tsn,
                    "scientificName"    => $name_string,
                    "parentNameUsageID" => $parent_tsn,
                    "taxonRank"         => $GLOBALS['ranks'][$rank_id],
                    "taxonRemarks"      => $remarks,
                    "taxonomicStatus"   => $validity,
                    "vernacularName"   => $vernacular_names));
            fwrite($OUT, $dwc_taxon->__toXML());
        }
    }
    
    fwrite($OUT, DarwinCoreRecordSet::xml_footer());
    fclose($OUT);
}



function dump_directory_path()
{
    if($handle = opendir(dirname(__FILE__)))
    {
        while(false !== ($file = readdir($handle)))
        {
            // e.g. itis.121710
            if(preg_match("/^itis\.[0-9]{6}$/", $file)) return dirname(__FILE__)."/".$file;
        }
        closedir($handle);
    }
    return false;
}


?>