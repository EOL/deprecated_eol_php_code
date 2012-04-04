<?php
namespace php_active_record;

class GoogleDumpPreparerSecond
{
    private $mysqli;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    
    public function begin_processing()
    {
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        // $limit = 500;
        
        $result = $this->mysqli->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        // $start = 748877;
        // $max_id = 748878;
        // $limit = 1;
        
        $this->all_languages = array();
        $this->LINKS_OUT = fopen(DOC_ROOT ."/temp/google_dump_links.txt", "w+");
        fwrite($this->LINKS_OUT, "EOL PAGE ID\tNAME\tPARENT EOL PAGE ID\tEOL PAGE RICHNESS SCORE\tRANK\tPARTNER NAME\tPARTNER URL\n");
        $this->COMMON_NAMES_OUT = fopen(DOC_ROOT ."/temp/google_dump_common_names.txt", "w+");
        fwrite($this->COMMON_NAMES_OUT, "EOL PAGE ID\tCOMMON NAME\tLANGUAGE ISO CODE OR LABEL\tPREFERRED NAME IN LANGUAGE\n");
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->lookup_links($i, $limit);
            $this->lookup_common_names($i, $limit);
        }
        fclose($this->COMMON_NAMES_OUT);
        fclose($this->LINKS_OUT);
        
        arsort($this->all_languages);
        print_r($this->all_languages);
    }
    
    private function lookup_links($start, $limit)
    {
        $query = "
            SELECT tc.id, he.id hierarchy_entry_id, he.identifier, he.source_url, h.label, h.outlink_uri, res.title, h.id, n.string,
              cf.string, he_parent.taxon_concept_id, tr.label, tcm.richness_score
            FROM taxon_concepts tc
            JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id)
            JOIN names n ON (he.name_id=n.id)
            JOIN hierarchies h ON (he.hierarchy_id=h.id)
            LEFT JOIN taxon_concept_metrics tcm ON (tc.id=tcm.taxon_concept_id)
            LEFT JOIN resources res ON (h.id=res.hierarchy_id)
            LEFT JOIN canonical_forms cf ON (n.ranked_canonical_form_id=cf.id)
            LEFT JOIN hierarchy_entries he_parent ON (he.parent_id=he_parent.id)
            LEFT JOIN (ranks r JOIN translated_ranks tr ON (r.id = tr.rank_id AND tr.language_id=152)) ON (he.rank_id=r.id)
            WHERE tc.id BETWEEN $start AND ". ($start+$limit) ."
            AND he.published=1 AND he.visibility_id=1";
        
        $links_from_hierarchy = array();
        static $j = 0;
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            if($j % 50000 == 0) echo "$start : $j : ". time_elapsed() ." : ". memory_get_usage() ."\n";
            $j++;
            $taxon_concept_id = $row[0];
            $hierarchy_entry_id = $row[1];
            $identifier = $row[2];
            $source_url = $row[3];
            $hierarchy_label = $row[4];
            $outlink_uri = $row[5];
            $resource_title = $row[6];
            $hierarchy_id = $row[7];
            $string = trim($row[8]);
            $ranked_canonical_form = trim($row[9]);
            $parent_page_id = $row[10];
            $rank_label = $row[11];
            $richness_score = $row[12];
            if(!$parent_page_id || $parent_page_id == "NULL") $parent_page_id = 0;
            if(!$richness_score || $richness_score == "NULL") $richness_score = 0;
            if(!$rank_label || $rank_label == "NULL") $rank_label = '';
            if($rank_label == "gen.") $rank_label = 'genus';
            if($rank_label == "sp.") $rank_label = 'species';
            if($rank_label == "subsp.") $rank_label = 'subspecies';
            if($rank_label == "var.") $rank_label = 'variety';
            $richness_score = round($richness_score * 100, 2);
            
            if(@$links_from_hierarchy[$taxon_concept_id][$hierarchy_id]) continue;
            
            $name = null;
            if($ranked_canonical_form != 'NULL' && !Name::is_surrogate($ranked_canonical_form)) $name = $ranked_canonical_form;
            elseif($string != 'NULL') $name = $string;
            if(!$name) continue;
            
            if($link = $this->prepare_link($row))
            {
                fwrite($this->LINKS_OUT, "$taxon_concept_id\t$name\t$parent_page_id\t$richness_score\t$rank_label\t". $link['title'] ."\t". $link['url'] ."\n");
                
                $links_from_hierarchy[$taxon_concept_id][$hierarchy_id] = 1;
            }
        }
    }
    
    private function lookup_common_names($start, $limit)
    {
        $query = "
            SELECT tc.id, n.string, l.iso_639_1, l.iso_639_2, l.iso_639_3, tl.label, tcn.preferred
            FROM taxon_concepts tc
            JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id)
            JOIN names n ON (tcn.name_id=n.id)
            JOIN languages l ON (tcn.language_id=l.id)
            LEFT JOIN translated_languages tl ON (l.id=tl.original_language_id and tl.language_id=152)
            WHERE tc.id BETWEEN $start AND ". ($start+$limit)."
            ORDER BY tc.id ASC, (iso_639_1 != '') DESC, preferred DESC";
        
        static $j = 0;
        $names_from_concept_and_language = array();
        
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            if($j % 50000 == 0) echo "$start : $j : ". time_elapsed() ." : ". memory_get_usage() ."\n";
            $j++;
            $taxon_concept_id = $row[0];
            $common_name = trim($row[1]);
            $iso_639_1 = $row[2];
            $iso_639_2 = $row[3];
            $iso_639_3 = $row[4];
            $language_label = $row[5];
            $preferred = $row[6];
            if(!$common_name || $common_name == 'NULL') continue;
            $common_name = str_replace("\n", " ", $common_name);
            
            $language = $iso_639_1;
            if(!$language || $language == 'NULL') $language = $iso_639_2;
            if(!$language || $language == 'NULL') $language = $iso_639_3;
            if(!$language || $language == 'NULL') $language = $language_label;
            if(!$language || $language == 'NULL' || preg_match("/[0-9]/", $language)) continue;
            
            if(in_array(strtolower($language), array('unspecified', 'unknown', 'common name', 'other', 'miscellaneous languages', '?')))
            {
                // this name was associated with a different language
                if(@$names_from_concept_and_language[$taxon_concept_id][$common_name]) continue;
                else $language = 'unknown';
            }
            if(in_array(strtolower($language), array('informal latinized name (vernacular concept only)'))) continue;
            
            if(@!$this->all_languages[$language]) $this->all_languages[$language] = 1;
            else $this->all_languages[$language]++;
            
            if(@$names_from_concept_and_language[$taxon_concept_id][$common_name][$language]) continue;
            $names_from_concept_and_language[$taxon_concept_id][$common_name][$language] = 1;
            
            fwrite($this->COMMON_NAMES_OUT, "$taxon_concept_id\t$common_name\t$language\t$preferred\n");
        }
    }
    
    private function prepare_link($row)
    {
        $taxon_concept_id = $row[0];
        $hierarchy_entry_id = $row[1];
        $identifier = $row[2];
        $source_url = $row[3];
        $hierarchy_label = $row[4];
        $outlink_uri = $row[5];
        $resource_title = $row[6];
        $hierarchy_id = $row[7];
        
        if($resource_title != 'NULL' && $t = trim($resource_title)) $title = $t;
        elseif($hierarchy_label != 'NULL' && $t = trim($hierarchy_label)) $title = $t;
        
        if($source_url != 'NULL' && $s = trim($source_url)) return array('title' => $title, 'url' => $source_url);
        if($outlink_uri != 'NULL' && $o = trim($outlink_uri))
        {
            if(preg_match("/%%ID%%/", $o, $arr))
            {
                if($i = trim($identifier))
                {
                    return array('title' => $title, 'url' => str_replace("%%ID%%", $i, $o));
                }
            }
        }
    }
}

?>
