<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../../../config/environment.php");



/*
shell_exec("rm -f ".dirname(__FILE__)."/itis.tar.gz");
if($dir = dump_directory_path())
{
    shell_exec("rm -fr ".dirname(__FILE__)."/$dir");
}



//  Full ITIS Data Set (Informix 7) from http://www.itis.gov/downloads/
$latest_itis_url = "http://www.itis.gov/downloads/itisInformix.tar.gz";

shell_exec("curl $latest_itis_url -o ".dirname(__FILE__)."/itis.tar.gz");
// unzip the download
shell_exec("tar -zxf ".dirname(__FILE__)."/itis.tar.gz");
exit;
*/


/*
$GLOBALS['itis_dump_dir'] = dump_directory_path();
if(!$GLOBALS['itis_dump_dir']) exit;


$GLOBALS['all_statuses'] = array();

echo "Getting file names...\n";
get_file_names();
echo "Getting ranks...\n";
get_ranks();
echo "Getting authors...\n";
get_authors();
echo "Getting locations...\n";
get_locations();
echo "Getting publications...\n";
get_publications();
echo "Getting publication links...\n";
get_publication_links();
echo "Getting comments...\n";
get_comments();
echo "Getting comment links...\n";
get_comment_links();
echo "Getting vernaculars...\n";
get_vernaculars();
echo "Getting synonyms...\n";
get_synonyms();
*/


// // create the test resource
// $agent = Agent::find_or_create(array('full_name' => 'Integrated Taxonomic Information System 2012'));
// $user = User::find_or_create(array('display_name' => 'Integrated Taxonomic Information System 2012', 'agent_id' => $agent->id));
// 
// // create the content partner
// $content_partner = ContentPartner::find_or_create(array('user_id' => $user->id));
// $hierarchy = Hierarchy::find_or_create(array('agent_id' => $agent->id, 'label' => 'Integrated Taxonomic Information System 2012'));
// 
// // create the resource
// $attr = array(  'content_partner_id'    => $content_partner->id,
//                 'service_type'          => ServiceType::find_or_create_by_translated_label('EOL Transfer Schema'),
//                 'refresh_period_hours'  => 1,
//                 'auto_publish'          => 1,
//                 'vetted'                => 1,
//                 'title'                 => "Integrated Taxonomic Information System 20122",
//                 'hierarchy_id'          => $hierarchy->id,
//                 'resource_status'       => ResourceStatus::validated());
// $resource = Resource::find_or_create($attr);
// 
$resource = Resource::find(8);

/*
echo "Starting to create document\n";
echo "Memory: ".memory_get_usage()."\n";
echo "Time: ".time_elapsed()."\n\n";
get_names($resource->id);
echo "Memory: ".memory_get_usage()."\n";
echo "Time: ".time_elapsed()."\n\n";


print_r($GLOBALS['all_statuses']);

unset($GLOBALS['authors']);
unset($GLOBALS['ranks']);
unset($GLOBALS['comments']);
unset($GLOBALS['comment_links']);
unset($GLOBALS['locations']);
unset($GLOBALS['publications']);
unset($GLOBALS['publication_links']);
unset($GLOBALS['vernaculars']);
unset($GLOBALS['synonyms']);
unset($GLOBALS['synonym_of']);
echo "Memory: ".memory_get_usage()."\n";
echo "Time: ".time_elapsed()."\n\n";
*/

/*
$archive_reader = new ContentArchiveReader(null, CONTENT_RESOURCE_LOCAL_PATH . $resource->id);
$archive_validator = new ContentArchiveValidator($archive_reader);

$archive_validator->get_validation_errors();
if($errors = $archive_validator->errors())
{
    $errors_as_string = array();
    foreach($errors as $error)
    {
        $this_error_string = "<b>Error</b> in $error->file on line $error->line field $error->uri: $error->message";
        if($error->value) $this_error_string .= " [value was \"$error->value\"]";
        $errors_as_string[] = $this_error_string;
    }
    print_r($errors_as_string);
}
if($warnings = $archive_validator->warnings())
{
    $warnings_as_string = array();
    foreach($warnings as $warning)
    {
        $this_warning_string = "<b>Warning</b> in $warning->file on line $warning->line field $warning->uri: $warning->message";
        if($warning->value) $this_warning_string .= " [value was \"$warning->value\"]";
        $warnings_as_string[] = $this_warning_string;
    }
    print_r($warnings_as_string);
}
*/

$GLOBALS['db_connection']->begin_transaction();
$resource->insert_hierarchy();

$resource->start_harvest();
$ingester = new ArchiveDataIngester($resource->harvest_event);
$ingester->parse(false);
unset($ingester);

$GLOBALS['db_connection']->commit();










// 
// 
// 
// 
// $agent = Agent::find_or_create_by_full_name("Integrated Taxonomic Information System", array("acronym" => "ITIS"));
// 
// $agent_hierarchy = Hierarchy::find_last_by_agent_id($agent->id);
// if($agent_hierarchy)
// {
//     $hierarchy_group_id = $agent_hierarchy->hierarchy_group_id;
//     $hierarchy_group_version = $agent_hierarchy->latest_group_version()+1;
//     
//     $hierarchy_params = array(
//         "agent_id"                  => $agent_hierarchy->agent_id,
//         "label"                     => $agent_hierarchy->label,
//         "descriptive_label"         => $agent_hierarchy->descriptive_label,
//         "description"               => $agent_hierarchy->description,
//         "hierarchy_group_id"        => $agent_hierarchy->hierarchy_group_id,
//         "hierarchy_group_version"   => $hierarchy_group_version,
//         "url"                       => $agent_hierarchy->url,
//         "outlink_uri"               => $agent_hierarchy->outlink_uri,
//         "ping_host_url"             => $agent_hierarchy->ping_host_url,
//         "complete"                  => $agent_hierarchy->complete);
// }else
// {
//     $hierarchy_group_id = Hierarchy::next_group_id();
//     $hierarchy_group_version = 1;
//     
//     $hierarchy_params = array(
//         "label"                     => "Integrated Taxonomic Information System (ITIS)",
//         "description"               => "latest export",
//         "agent_id"                  => $agent->id,
//         "url"                       => "http://www.itis.gov/",
//         "outlink_uri"               => "http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=%%ID%%",
//         "complete"                  => 1,
//         "hierarchy_group_id"        => $hierarchy_group_id,
//         "hierarchy_group_version"   => $hierarchy_group_version);
// }
// 
// $hierarchy = Hierarchy::find_or_create($hierarchy_params);
// 
// 
// $uri = dirname(__FILE__) . "/out.xml";
// DarwinCoreHarvester::harvest($uri, $hierarchy);










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

function get_publications()
{
    //0     pub_id_prefix char(3) not null ,
    //1     publication_id serial not null ,
    //2     reference_author varchar(100,1) not null ,
    //3     title varchar(255,10),
    //4     publication_name varchar(255,1) not null ,
    //5     listed_pub_date date,
    //6     actual_pub_date date not null ,
    //7     publisher varchar(80,10),
    //8     pub_place varchar(40,10),
    //9     isbn varchar(16),
    //10    issn varchar(16),
    //11    pages varchar(15),
    //12    pub_comment varchar(500),
    //13    update_date date not null
    
    $GLOBALS['publications'] = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['publications'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data      = explode("|", $line);
        $id_prefix      = trim($line_data[0]);
        $id             = trim($line_data[1]);
        $author         = trim(utf8_encode($line_data[2]));
        $title          = trim(utf8_encode($line_data[3]));
        $publication    = trim(utf8_encode($line_data[4]));
        $listed_date    = trim($line_data[5]);
        $actual_date    = trim($line_data[6]);
        $publisher      = trim($line_data[7]);
        $location       = trim($line_data[8]);
        $pages          = trim($line_data[11]);
        $comment        = trim($line_data[12]);
        
        $citation_order = array();
        if($author) $citation_order[] = $author;
        if(preg_match("/([12][0-9]{3})/", $actual_date, $arr)) $citation_order[] = $arr[1]; // year
        if($title) $citation_order[] = $title;
        if($publication) $citation_order[] = $publication;
        if($pages) $citation_order[] = $pages;
        
        $citation = implode(". ", $citation_order);
        $citation = str_replace("  ", " ", $citation);
        $citation = str_replace("..", ".", $citation);
        $citation = trim($citation);
        
        $GLOBALS['publications'][$id_prefix.$id] = $citation;
    }
}

function get_publication_links()
{
    //0     tsn integer not null ,
    //1     doc_id_prefix char(3) not null ,
    //2     documentation_id integer not null ,
    //3     original_desc_ind char(1),
    //4     init_itis_desc_ind char(1),
    //5     change_track_id integer,
    //6     vernacular_name varchar(80,5),
    //7     update_date date not null
    
    $GLOBALS['publication_links'] = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['reference_links'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data              = explode("|", $line);
        $tsn                    = trim($line_data[0]);
        $publication_id_prefix  = trim($line_data[1]);
        $publication_id         = trim($line_data[2]);
        
        // only get publications, not sources or experts
        if($publication_id_prefix == "PUB") $GLOBALS['publication_links'][$tsn][] = $publication_id_prefix.$publication_id;
    }
}

function get_locations()
{
    //1     tsn integer not null ,
    //2     geographic_value varchar(45,6) not null ,
    //3     update_date date not null 
    
    $GLOBALS['locations'] = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['geographic_div'];
    foreach(new FileIterator($path) as $line)
    {
        if(!$line) continue;
        $line_data  = explode("|", $line);
        $tsn        = trim($line_data[0]);
        $location   = trim($line_data[1]);
        $GLOBALS['locations'][$tsn] = $location;
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
        $comment    = trim(utf8_encode($line_data[2]));
        $GLOBALS['comments'][$id] = $comment;
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
        $GLOBALS['authors'][$id] = trim(utf8_encode($line_data[1]));
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

function get_names($resource_id)
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
    
    // $OUT = fopen(dirname(__FILE__)."/out.xml", "w+");
    // fwrite($OUT, DarwinCoreRecordSet::xml_header());
    
    if(!is_dir(CONTENT_RESOURCE_LOCAL_PATH . "$resource_id")) mkdir(CONTENT_RESOURCE_LOCAL_PATH . "$resource_id");
    $archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => CONTENT_RESOURCE_LOCAL_PATH . "$resource_id/"));
    $written_publication_ids = array();
    
    $path = $GLOBALS['itis_dump_dir']."/".$GLOBALS['filenames']['taxonomic_units'];
    $i = 0;
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
        if(@$GLOBALS['authors'][$author_id]) $name_string = utf8_encode($name_string)." ".$GLOBALS['authors'][$author_id];
        
        $canonical_form = $name_part_1;
        if($name_part_2)    $canonical_form.=" ".$name_part_2;
        if($name_part_3)    $canonical_form.=" ".$name_part_3;
        if($name_part_4)    $canonical_form.=" ".$name_part_4;
        
        $name_string    = trim($name_string);
        $canonical_form = utf8_encode(trim($canonical_form));
        
        $remarks = "";
        if($comp_rating && $comp_rating != "unknown") $remarks .= "Completeness: $comp_rating. ";
        if($cred_rating && $cred_rating != "unknown") $remarks .= "Credibility: $cred_rating. ";
        // if($curr_rating && $curr_rating != "unknown") $remarks .= "Currency: $curr_rating. ";
        
        if(isset($GLOBALS['comment_links'][$name_tsn]))
        {
            foreach($GLOBALS['comment_links'][$name_tsn] as $comment_id)
            {
                if(@$GLOBALS['comments'][$comment_id]) $remarks .= $GLOBALS['comments'][$comment_id].". ";
            }
        }
        $remarks = str_replace("..", ".", $remarks);
        $remarks = trim($remarks);
        
        
        
        if(isset($GLOBALS['synonym_of'][$name_tsn]))
        {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID = $name_tsn;
            $taxon->scientificName = $name_string;
            $taxon->parentNameUsageID = $GLOBALS['synonym_of'][$name_tsn];
            $taxon->taxonRank = $GLOBALS['ranks'][$rank_id];
            $taxon->taxonRemarks = $remarks;
            // $taxon->namePublishedIn = $publications;
            $taxon->taxonomicStatus = $reason;
            // if(isset($GLOBALS['locations'][$name_tsn])) $taxon->spatial = $GLOBALS['locations'][$name_tsn];
            
            if(!Functions::is_utf8($taxon->scientificName)) echo "NOT UTF8 SYN: $name_tsn : $taxon->scientificName\n";
            $archive_builder->write_object_to_file($taxon);
            @$GLOBALS['all_statuses']['synonyms'][$validity] += 1;
            @$GLOBALS['all_statuses']['synonym_reasons'][$reason] += 1;
        }else
        {
            // first loop and find all vernacular names
            $vernacular_names = array();
            if(isset($GLOBALS['vernaculars'][$name_tsn]))
            {
                foreach($GLOBALS['vernaculars'][$name_tsn] as $name_hash)
                {
                    $vernacular = new \eol_schema\VernacularName();
                    $vernacular->taxonID = $name_tsn;
                    $vernacular->vernacularName = $name_hash['name'];
                    $vernacular->language = get_iso_code_for_language($name_hash['language']);

                    if(!Functions::is_utf8($vernacular->vernacularName)) echo "NOT UTF8 VERN: $name_tsn : $vernacular->vernacularName\n";
                    $archive_builder->write_object_to_file($vernacular);
                    @$GLOBALS['all_statuses']['languages'][$name_hash['language']] += 1;
                }
            }
            
            $publication_ids = array();
            if(isset($GLOBALS['publication_links'][$name_tsn]))
            {
                foreach($GLOBALS['publication_links'][$name_tsn] as $pub_id)
                {
                    if(@$GLOBALS['publications'][$pub_id])
                    {
                        if(!isset($written_publication_ids[$pub_id]))
                        {
                            $reference = new \eol_schema\Reference();
                            $reference->identifier = $pub_id;
                            $reference->full_reference = $GLOBALS['publications'][$pub_id];

                            if(!Functions::is_utf8($reference->full_reference)) echo "NOT UTF8 REF: $name_tsn : $reference->full_reference\n";
                            $archive_builder->write_object_to_file($reference);
                            $written_publication_ids[$pub_id] = 1;
                        }
                        $publication_ids[] = $pub_id;
                    }
                }
            }
            
            if($i % 1000 == 0) echo "$i : $name_tsn : $name_string : ". time_elapsed() ." : ". memory_get_usage() ."\n";
            $i++;
            
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID = $name_tsn;
            $taxon->scientificName = $name_string;
            $taxon->parentNameUsageID = $parent_tsn;
            $taxon->taxonRank = $GLOBALS['ranks'][$rank_id];
            $taxon->taxonRemarks = $remarks;
            $taxon->referenceID = implode(";", $publication_ids);
            $taxon->taxonomicStatus = $validity;
            // if(isset($GLOBALS['locations'][$name_tsn])) $taxon->spatial = $GLOBALS['locations'][$name_tsn];
            
            if(!Functions::is_utf8($taxon->scientificName)) echo "NOT UTF8: $name_tsn : $taxon->scientificName\n";
            $archive_builder->write_object_to_file($taxon);
            @$GLOBALS['all_statuses']['valids'][$validity] += 1;
        }
    }
    
    $archive_builder->finalize();
}

function get_iso_code_for_language($language)
{
    if(!$language) return $language;
    
    static $lang = array();
    if(!$lang)
    {
        $lang['French'] = 'fr';
        $lang['English'] = 'en';
        $lang['Spanish'] = 'es';
        // $lang['Hawaiian'] = '';
        $lang['Native American'] = '';
        $lang['Portuguese'] = 'pt';
        $lang['Italian'] = 'it';
        $lang['German'] = 'de';
        $lang['Japanese'] = 'ja';
        $lang['Arabic'] = 'ar';
        $lang['Icelandic'] = 'is';
        $lang['Afrikaans'] = 'af';
        // $lang['Iglulik Inuit'] = '';
        $lang['Chinese'] = 'cn';
        $lang['Hindi'] = 'hi';
        $lang['Dutch'] = 'nl';
        $lang['Hausa'] = 'ha';
        $lang['Greek'] = 'el';
        // $lang['Djuka'] = '';
        $lang['Galibi'] = 'gl';
        $lang['Korean'] = 'ko';
        // $lang['Australian'] = '';
    }
    
    if(isset($lang[$language])) return $lang[$language];
    return $language;
    
    
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
