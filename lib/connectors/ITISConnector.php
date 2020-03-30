<?php
namespace php_active_record;
// connector: [383]
class ITISConnector
{
    // const DUMP_URL = "http://localhost/cp/ITIS/itisInformix.tar.gz";
    const DUMP_URL = "http://www.itis.gov/downloads/itisInformix.tar.gz";
    const ITIS_TAXON_PAGE = "http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
    }

    public function build_archive()
    {
        $local_temp_file = Functions::save_remote_file_to_local(self::DUMP_URL, array("cache" => 1, "timeout" => 60*60*5, "expire_seconds" => 60*60*24*25)); //5 hours timeout | expires in 25 days
        
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        if($download_directory = ContentManager::download_temp_file_and_assign_extension($local_temp_file, "")) //added 2nd blank param to suffice: "Warning: Missing argument 2"
        {
            echo "\ndownload_directory:[$download_directory]\n";
            // $download_directory = '/Library/WebServer/Webroot/eol_php_code/applications/content_server/tmp/9f508e44e8038fb56bbc0c9b34eb3ac7';
            if(is_dir($download_directory) && file_exists($download_directory ."/itis.sql"))
            {
                $this->download_directory = $download_directory;
                $this->all_statuses = array();

                echo "Getting file names...\n";
                $this->get_file_names();
                echo "Getting ranks...\n";
                $this->get_ranks();
                echo "Getting authors...\n";
                $this->get_authors();
                echo "Getting locations...\n";
                $this->get_locations();
                echo "Getting publications...\n";
                $this->get_publications();
                echo "Getting publication links...\n";
                $this->get_publication_links();
                echo "Getting comments...\n";
                $this->get_comments();
                echo "Getting comment links...\n";
                $this->get_comment_links();
                /* removed common names per: https://eol-jira.bibalex.org/browse/DATA-1849
                echo "Getting vernaculars...\n";
                $this->get_vernaculars();
                */
                echo "Getting synonyms...\n";
                $this->get_synonyms();

                $this->get_names();
                print_r($this->all_statuses);

                recursive_rmdir($download_directory);
            }
        }

        $this->archive_builder->finalize(true);
        unlink($local_temp_file);
    }

    private function get_file_names()
    {
        $this->filenames = array();
        $current_table_name = false;
        foreach(new FileIterator($this->download_directory . "/itis.sql") as $line)
        {
            if(!$line) continue;
            if(preg_match("/{ table \"itis\"\.([^ ]+) /i", $line, $arr))
            {
                $current_table_name = $arr[1];
                continue;
            }
            if($current_table_name && preg_match("/{ unload file name = ([^ ]+) /", $line,$arr))
            {
                $this->filenames[$current_table_name] = $arr[1];
                $current_table_name = false;
            }
        }
    }

    private function get_ranks()
    {
        //0    kingdom_id integer not null
        //1    rank_id smallint not null
        //2    rank_name char(15) not null
        //3    dir_parent_rank_id smallint not null
        //4    req_parent_rank_id smallint not null
        //5    update_date date not null
        $this->ranks = array();
        $this->rank_names = array();
        $path = $this->download_directory ."/". $this->filenames['taxon_unit_types'];
        foreach(new FileIterator($path) as $line)
        {
            if(!$line) continue;
            $line_data        = explode("|", $line);
            $id               = trim($line_data[1]);
            $this->ranks[$id] = trim($line_data[2]);
        }
    }

    private function get_authors()
    {
        //0    taxon_author_id serial not null
        //1    taxon_author varchar(100,30) not null
        //2    update_date date not null
        //3    kingdom_id smallint not null
        $this->authors = array();
        $path = $this->download_directory ."/". $this->filenames['taxon_authors_lkp'];
        foreach(new FileIterator($path) as $line)
        {
            if(!$line) continue;
            $line_data          = explode("|", $line);
            $id                 = trim($line_data[0]);
            $this->authors[$id] = trim(utf8_encode($line_data[1]));
        }
    }

    private function get_locations()
    {
        //1     tsn integer not null ,
        //2     geographic_value varchar(45,6) not null ,
        //3     update_date date not null
        $this->locations = array();
        $path = $this->download_directory ."/". $this->filenames['geographic_div'];
        foreach(new FileIterator($path) as $line)
        {
            if(!$line) continue;
            $line_data             = explode("|", $line);
            $tsn                   = trim($line_data[0]);
            $location              = trim($line_data[1]);
            $this->locations[$tsn] = $location;
        }
    }

    private function get_publications()
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
        $this->publications = array();
        $path = $this->download_directory ."/". $this->filenames['publications'];
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
            $this->publications[$id_prefix.$id] = $citation;
        }
    }

    private function get_publication_links()
    {
        //0     tsn integer not null ,
        //1     doc_id_prefix char(3) not null ,
        //2     documentation_id integer not null ,
        //3     original_desc_ind char(1),
        //4     init_itis_desc_ind char(1),
        //5     change_track_id integer,
        //6     vernacular_name varchar(80,5),
        //7     update_date date not null
        $this->publication_links = array();
        $path = $this->download_directory ."/". $this->filenames['reference_links'];
        foreach(new FileIterator($path) as $line)
        {
            if(!$line) continue;
            $line_data              = explode("|", $line);
            $tsn                    = trim($line_data[0]);
            $publication_id_prefix  = trim($line_data[1]);
            $publication_id         = trim($line_data[2]);
            // only get publications, not sources or experts
            if($publication_id_prefix == "PUB") $this->publication_links[$tsn][] = $publication_id_prefix . $publication_id;
        }
    }

    private function get_comments()
    {
        //0     comment_id serial not null ,
        //1     commentator varchar(100),
        //2     comment_detail char(2000) not null ,
        //3     comment_time_stamp datetime year to second not null ,
        //4     update_date date not null
        $this->comments = array();
        $path = $this->download_directory ."/". $this->filenames['comments'];
        foreach(new FileIterator($path) as $line)
        {
            if(!$line) continue;
            $line_data           = explode("|", $line);
            $id                  = trim($line_data[0]);
            $comment             = trim(utf8_encode($line_data[2]));
            $this->comments[$id] = $comment;
        }
    }

    private function get_comment_links()
    {
        //0     tsn integer not null ,
        //1     comment_id integer not null ,
        //2     update_date date not null
        $this->comment_links = array();
        $path = $this->download_directory ."/". $this->filenames['tu_comments_links'];
        foreach(new FileIterator($path) as $line)
        {
            if(!$line) continue;
            $line_data                   = explode("|", $line);
            $tsn                         = trim($line_data[0]);
            $comment_id                  = trim($line_data[1]);
            $this->comment_links[$tsn][] = $comment_id;
        }
    }

    private function get_vernaculars()
    {
        //0    tsn integer not null
        //1    vernacular_name varchar(80,5) not null
        //2    language varchar(15) not null
        //3    approved_ind char(1)
        //4    update_date date not null
        //5    primary key (tsn,vernacular_name,language)  constraint "itis".vernaculars_key
        $this->vernaculars = array();
        $path = $this->download_directory ."/". $this->filenames['vernaculars'];
        foreach(new FileIterator($path) as $line)
        {
            if(!$line) continue;
            $line_data                               = explode("|", $line);
            $name_tsn                                = trim($line_data[0]);
            $string                                  = trim(utf8_encode($line_data[1]));
            $language                                = trim($line_data[2]);
            if($language == "unspecified") $language = "";
            $this->vernaculars[$name_tsn][]          = array("name" => $string, "language" => $language);
        }
    }

    private function get_synonyms()
    {
        //0    tsn integer not null
        //1    tsn_accepted integer not null
        //2    update_date date not null
        $this->synonyms = array();
        $this->synonym_of = array();
        $path = $this->download_directory ."/". $this->filenames['synonym_links'];
        foreach(new FileIterator($path) as $line)
        {
            if(!$line) continue;
            $line_data          = explode("|", $line);
            $synonym_name_tsn   = trim($line_data[0]);
            $accepted_name_tsn  = trim($line_data[1]);
            $this->synonyms[$accepted_name_tsn][$synonym_name_tsn] = true;
            $this->synonym_of[$synonym_name_tsn] = $accepted_name_tsn;
        }
    }

    private function get_names()
    {
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
        $written_publication_ids = array();
        $path = $this->download_directory ."/". $this->filenames['taxonomic_units'];
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
            if(preg_match("/^[a-z]/", $name_part_1)) echo "???????? $name_tsn) $name_part_1\n";
            if($x1)             $name_string = $x1 ." ". $name_string;
            if($x2)             $name_string .= " ". $x2;
            if($name_part_2)    $name_string .= " ". $name_part_2;
            if($sp_marker_1)    $name_string .= " ". $sp_marker_1;
            if($name_part_3)    $name_string .= " ". $name_part_3;
            if($sp_marker_2)    $name_string .= " ". $sp_marker_2;
            if($name_part_4)    $name_string .= " ". $name_part_4;
            if($a = @$this->authors[$author_id]) $name_string = utf8_encode($name_string) ." ". $a;
            $name_string = trim($name_string);

            $remarks = "";
            if($comp_rating && $comp_rating != "unknown") $remarks .= "Completeness: $comp_rating. ";
            if($cred_rating && $cred_rating != "unknown") $remarks .= "Credibility: $cred_rating. ";
            // if($curr_rating && $curr_rating != "unknown") $remarks .= "Currency: $curr_rating. ";

            if(isset($this->comment_links[$name_tsn]))
            {
                foreach($this->comment_links[$name_tsn] as $comment_id)
                {
                    if($c = @$this->comments[$comment_id]) $remarks .= $c .". ";
                }
            }
            $remarks = str_replace("..", ".", $remarks);
            $remarks = trim($remarks);

            if(isset($this->synonym_of[$name_tsn]))
            {
                static $unavailable_reasons = array('database artifact', 'unavailable, database artifact', 'unavailable, incorrect orig. spelling',
                                                    'unavailable, literature misspelling', 'unavailable, suppressed by ruling', 'unavailable, other',
                                                    'unjustified emendation', 'nomen dubium', 'homonym (illegitimate)', 'superfluous renaming (illegitimate)');
                if(in_array($reason, $unavailable_reasons)) continue;
                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID = $name_tsn;
                $taxon->scientificName = $name_string;
                $taxon->parentNameUsageID = $this->synonym_of[$name_tsn];
                $taxon->taxonRank = $this->ranks[$rank_id];
                $taxon->taxonRemarks = $remarks;
                // $taxon->namePublishedIn = $publications;
                $taxon->taxonomicStatus = $reason;
                // if(isset($this->locations[$name_tsn])) $taxon->spatial = $this->locations[$name_tsn];

                //newly added
                if($val = @$this->locations[$name_tsn]) self::add_string_types($taxon->taxonID, md5($val), $val, "http://eol.org/schema/terms/Present", true);
                // http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution - possible value for ITIS geographic division

                if(!Functions::is_utf8($taxon->scientificName)) echo "NOT UTF8 SYN: $name_tsn : $taxon->scientificName\n";
                $this->archive_builder->write_object_to_file($taxon);
                @$this->all_statuses['synonyms'][$validity] += 1;
                @$this->all_statuses['synonym_reasons'][$reason] += 1;
            }else
            {
                // first loop and find all vernacular names
                $vernacular_names = array();
                if(isset($this->vernaculars[$name_tsn]))
                {
                    foreach($this->vernaculars[$name_tsn] as $name_hash)
                    {
                        $vernacular = new \eol_schema\VernacularName();
                        $vernacular->taxonID = $name_tsn;
                        $vernacular->vernacularName = $name_hash['name'];
                        $vernacular->language = self::get_iso_code_for_language($name_hash['language']);

                        if(!Functions::is_utf8($vernacular->vernacularName)) echo "NOT UTF8 VERN: $name_tsn : $vernacular->vernacularName\n";
                        $this->archive_builder->write_object_to_file($vernacular);
                        @$this->all_statuses['languages'][$name_hash['language']] += 1;
                    }
                }

                $publication_ids = array();
                if(isset($this->publication_links[$name_tsn]))
                {
                    foreach($this->publication_links[$name_tsn] as $pub_id)
                    {
                        if($p = @$this->publications[$pub_id])
                        {
                            if(!isset($written_publication_ids[$pub_id]))
                            {
                                $reference = new \eol_schema\Reference();
                                $reference->identifier = $pub_id;
                                $reference->full_reference = $p;

                                if(!Functions::is_utf8($reference->full_reference)) echo "NOT UTF8 REF: $name_tsn : $reference->full_reference\n";
                                $this->archive_builder->write_object_to_file($reference);
                                $written_publication_ids[$pub_id] = 1;
                            }
                            $publication_ids[] = $pub_id;
                        }
                    }
                }

                if($i % 5000 == 0) echo "$i : $name_tsn : $name_string : ". time_elapsed() ." : ". memory_get_usage() ."\n";
                $i++;

                $taxon = new \eol_schema\Taxon();
                $taxon->taxonID = $name_tsn;
                $taxon->scientificName = $name_string;
                $taxon->parentNameUsageID = $parent_tsn;
                $taxon->taxonRank = $this->ranks[$rank_id];
                $taxon->taxonRemarks = $remarks;
                $taxon->referenceID = implode(";", $publication_ids);
                $taxon->taxonomicStatus = $validity;
                // if(isset($this->locations[$name_tsn])) $taxon->spatial = $this->locations[$name_tsn];

                //newly added
                if($val = @$this->locations[$name_tsn]) self::add_string_types($taxon->taxonID, md5($val), $val, "http://eol.org/schema/terms/Present", true);

                if(!Functions::is_utf8($taxon->scientificName)) echo "NOT UTF8: $name_tsn : $taxon->scientificName\n";
                $this->archive_builder->write_object_to_file($taxon);
                @$this->all_statuses['valids'][$validity] += 1;
            }
        }
    }

    //newly added
    private function add_string_types($taxon_id, $catnum, $value, $mtype, $mtaxon = false)
    {
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence_id;
        if($mtaxon)
        {
            $m->measurementOfTaxon = 'true';
            $m->source = self::ITIS_TAXON_PAGE . $taxon_id;
            // $m->measurementRemarks = "";
        }
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $occurrence_id;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
    }

    private static function get_iso_code_for_language($language)
    {
        if(!$language) return $language;
        static $lang = array();
        if(!$lang)
        {
            $lang['French']             = 'fr';
            $lang['English']            = 'en';
            $lang['Spanish']            = 'es';
            $lang['Hawaiian']           = 'haw'; //newly added
            $lang['Native American']    = '';
            $lang['Portuguese']         = 'pt';
            $lang['Italian']            = 'it';
            $lang['German']             = 'de';
            $lang['Japanese']           = 'ja';
            $lang['Arabic']             = 'ar';
            $lang['Icelandic']          = 'is';
            $lang['Afrikaans']          = 'af';
            // $lang['Iglulik Inuit']      = '';
            $lang['Chinese']            = 'cn';
            $lang['Hindi']              = 'hi';
            $lang['Dutch']              = 'nl';
            $lang['Hausa']              = 'ha';
            $lang['Greek']              = 'el';
            // $lang['Djuka']              = '';
            $lang['Galibi']             = 'gl';
            $lang['Korean']             = 'ko';
            $lang['Australian']         = 'au';
            $lang['Fijan']              = 'fj';
        }
        if(isset($lang[$language])) return $lang[$language];
        return $language;
    }
}

?>