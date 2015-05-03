<?php
namespace php_active_record;

class GoogleDumpPreparer
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
        // $start = 1052070;
        // $max_id = 748878;
        // $limit = 1;
        
        $this->all_languages = array();
        if(!($this->LINKS_OUT = fopen(DOC_ROOT ."/temp/google_dump_links.txt", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .DOC_ROOT ."/temp/google_dump_links.txt");
          return;
        }
        fwrite($this->LINKS_OUT, "EOL PAGE ID\tHIERARCHY NODE ID\tNAME\tPARENT EOL PAGE ID\tPARENT HIERARCHY NODE ID\tEOL PAGE RICHNESS SCORE\tRANK\tPARTNER TAXON IDENTIFIER\tPARTNER NAME\tPARTNER URL\n");
        if(!($this->COMMON_NAMES_OUT = fopen(DOC_ROOT ."/temp/google_dump_common_names.txt", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT ."/temp/google_dump_common_names.txt");
          return;
        }
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
        
        if(!($this->BLURBS_OUT = fopen(DOC_ROOT ."/temp/google_dump_text_blurbs.txt", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT ."/temp/google_dump_text_blurbs.txt");
          return;
        }
        fwrite($this->BLURBS_OUT, "EOL PAGE ID\tBLURB\tATTRIBUTION\tLICENSE\n");
        $this->lookup_blurbs();
        fclose($this->BLURBS_OUT);
    }
    
    private function lookup_links($start, $limit)
    {
        $hierarchy_ids = array(
             771, 759, 431, 123, 903, 596, 410, 143, 860
        );
        // COL 2011, NCBI, WORMS, Wikipedia, ITIS, IF, Wikimedia Commons, fishbase, avibase
        $query = "
            SELECT tc.id, he.id hierarchy_entry_id, he.identifier, he.source_url, h.label, h.outlink_uri, res.title, h.id, n.string,
              cf.string, he_parent.id parent_hierarchy_entry_id, he_parent.taxon_concept_id, tr.label, tcm.richness_score
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
            AND he.published=1 AND he.visibility_id=".Visibility::visible()->id."
            AND he.hierarchy_id IN (". implode(",", $hierarchy_ids). ")";
        
        $links_from_hierarchy = array();
        static $j = 0;
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            if($j % 10000 == 0) echo "$start : $j : ". time_elapsed() ." : ". memory_get_usage() ."\n";
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
            $parent_hierarchy_entry_id = $row[10];
            $parent_page_id = $row[11];
            $rank_label = $row[12];
            $richness_score = $row[13];
            if(!$parent_hierarchy_entry_id || $parent_hierarchy_entry_id == "NULL") $parent_hierarchy_entry_id = 0;
            if(!$parent_page_id || $parent_page_id == "NULL") $parent_page_id = 0;
            if(!$richness_score || $richness_score == "NULL") $richness_score = 0;
            if(!$rank_label || $rank_label == "NULL") $rank_label = '';
            if($rank_label == "gen.") $rank_label = 'genus';
            if($rank_label == "sp.") $rank_label = 'species';
            if($rank_label == "subsp.") $rank_label = 'subspecies';
            if($rank_label == "var.") $rank_label = 'variety';
            $richness_score = round($richness_score * 100, 2);
            if($resource_title != 'NULL' && $t = trim($resource_title)) $title = $t;
            elseif($hierarchy_label != 'NULL' && $t = trim($hierarchy_label)) $title = $t;
            
            
            if(@$links_from_hierarchy[$taxon_concept_id][$hierarchy_id]) continue;
            
            $name = null;
            if($ranked_canonical_form != 'NULL' && !Name::is_surrogate($ranked_canonical_form)) $name = $ranked_canonical_form;
            elseif($string != 'NULL') $name = $string;
            if(!$name) continue;
            
            fwrite($this->LINKS_OUT, "$taxon_concept_id\t$hierarchy_entry_id\t$name\t$parent_page_id\t$parent_hierarchy_entry_id\t$richness_score\t$rank_label\t$identifier\t$title\t");
            if($link = $this->prepare_link($row)) fwrite($this->LINKS_OUT, $link['url']);
            fwrite($this->LINKS_OUT, "\n");
            
            $links_from_hierarchy[$taxon_concept_id][$hierarchy_id] = 1;
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
            if($j % 50000 == 0) echo "$start (*) : $j : ". time_elapsed() ." : ". memory_get_usage() ."\n";
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
    
    private function download_blurb_info()
    {
        $path = DOC_ROOT .'/tmp/google_blurbs.txt';
        $query = "
            SELECT do.id, do.data_rating, dotoc.toc_id, dohe.vetted_id, he.hierarchy_id, he.taxon_concept_id, l.source_url
            FROM data_objects do
            JOIN data_objects_table_of_contents dotoc ON (do.id=dotoc.data_object_id)
            JOIN licenses l ON (do.license_id=l.id)
            JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id)
            WHERE do.published = 1
            AND do.data_type_id = 3
            AND dotoc.toc_id IN (2, 308, 293, 267, 300)
            AND dohe.vetted_id IN (0, 5)
            AND dohe.visibility_id = 1
            AND (
                (he.hierarchy_id=120 AND do.object_title='Physical Description') OR
                (he.hierarchy_id=155 AND do.object_title='Description') OR
                (he.hierarchy_id=610 AND do.object_title='Introduction') OR
                he.hierarchy_id=116 OR
                (he.hierarchy_id=119 AND do.object_title='Description') OR
                (he.hierarchy_id=131 AND do.object_title='Description') OR
                (he.hierarchy_id=138 AND do.object_title='Description') OR
                (he.hierarchy_id=140 AND do.object_title='General Description') OR
                he.hierarchy_id=431)";
        $outfile = $this->mysqli->select_into_outfile($query);
        @unlink($path);
        if(filesize($outfile) > 1024) rename($outfile, $path);
        @unlink($outfile);
        
        $path = DOC_ROOT .'/tmp/google_blurbs_udo.txt';
        $query = "
            SELECT do.id, do.data_rating, dotoc.toc_id, udo.vetted_id, udo.taxon_concept_id, u.given_name, u.family_name, l.source_url
            FROM users_data_objects udo
            JOIN data_objects do ON (udo.data_object_id=do.id)
            JOIN data_objects_table_of_contents dotoc ON (do.id=dotoc.data_object_id)
            JOIN licenses l ON (do.license_id=l.id)
            JOIN users u ON (udo.user_id=u.id)
            WHERE dotoc.toc_id IN (2)
            AND do.published=1
            AND udo.vetted_id=5
            AND udo.visibility_id=1";
        $outfile = $this->mysqli->select_into_outfile($query);
        @unlink($path);
        if(filesize($outfile) > 1024) rename($outfile, $path);
        @unlink($outfile);
    }
    
    public function lookup_blurbs()
    {
        if(!file_exists(DOC_ROOT .'/tmp/google_blurbs.txt'))
        {
            $this->download_blurb_info();
        }
        if(!file_exists(DOC_ROOT .'/tmp/google_blurbs.txt')) return;
        
        $hierarchy_ids = array();
        $num_lines = 0;
        $taxon_concept_ids = array();
        $trusted_id = Vetted::trusted()->id;
        $this->all_blurb_info = array();
        $hierarchy_attribution = array(
            120 => 'Animal Diversity Web',
            155 => 'ARKive',
            610 => 'Tree of Life',
            116 => 'BioPedia',
            119 => 'AmphibiaWeb',
            131 => 'Illinois Wildflowers',
            138 => 'USDA',
            140 => 'University of Alberta',
            431 => 'Wikipedia');
        foreach(new FileIterator(DOC_ROOT .'/tmp/google_blurbs.txt') as $line_number => $line)
        {
            $row = explode("\t", $line);
            if(@!$row[1]) continue;
            $data_object_id = $row[0];
            $data_rating = $row[1];
            $toc_id = $row[2];
            $vetted_id = $row[3];
            $hierarchy_id = $row[4];
            $taxon_concept_id = $row[5];
            $license = $row[6];
            // Wikipedia can be unreviewed or trusted - everything else must be trusted
            if($vetted_id != $trusted_id) continue;
            if($hierarchy_id == 120 && $toc_id != 267) continue;
            if($hierarchy_id == 155 && $toc_id != 308) continue;
            if($hierarchy_id == 610 && $toc_id != 2) continue;
            if($hierarchy_id == 116 && $toc_id != 308) continue;
            if($hierarchy_id == 119 && $toc_id != 308) continue;
            if($hierarchy_id == 131 && $toc_id != 308) continue;
            if($hierarchy_id == 138 && $toc_id != 308) continue;
            if($hierarchy_id == 140 && $toc_id != 308) continue;
            if($hierarchy_id == 431 && $toc_id != 300) continue;
            if($hierarchy_id == 431) continue;
            $this->all_blurb_info[$data_object_id] = array(
                'data_object_id' => $data_object_id,
                'data_rating' => $data_rating,
                'toc_id' => $toc_id,
                'vetted_id' => $vetted_id,
                'attribution' => $hierarchy_attribution[$hierarchy_id],
                'taxon_concept_id' => $taxon_concept_id,
                'license' => $license);
        }
        foreach(new FileIterator(DOC_ROOT .'/tmp/google_blurbs_udo.txt') as $line_number => $line)
        {
            $row = explode("\t", $line);
            if(@!$row[1]) continue;
            $data_object_id = $row[0];
            $data_rating = $row[1];
            $toc_id = $row[2];
            $vetted_id = $row[3];
            $taxon_concept_id = $row[4];
            $given_name = $row[5];
            $family_name = $row[6];
            $license = $row[7];
            $this->all_blurb_info[$data_object_id] = array(
                'data_object_id' => $data_object_id,
                'data_rating' => $data_rating,
                'toc_id' => $toc_id,
                'vetted_id' => $vetted_id,
                'attribution' => trim($given_name ." ". $family_name),
                'taxon_concept_id' => $taxon_concept_id,
                'license' => $license);
        }
        $this->add_blurb_descriptions();
        
        if(!$this->taxon_concept_blurbs) return;
        ksort($this->taxon_concept_blurbs);
        foreach($this->taxon_concept_blurbs as $taxon_concept_id => $blurb_info)
        {
            fwrite($this->BLURBS_OUT, "$taxon_concept_id\t". $blurb_info['description'] ."\t". $blurb_info['attribution'] ."\t". $blurb_info['license']."\n");
        }
    }
    
    private function add_blurb_descriptions()
    {
        $chunks = array_chunk($this->all_blurb_info, 5000);
        foreach($chunks as $chunk)
        {
            $data_object_ids = array();
            foreach($chunk as $row) $data_object_ids[] = $row['data_object_id'];
            $query = "SELECT id, description FROM data_objects WHERE id IN (". implode(",", $data_object_ids) .")";
            $result = $this->mysqli->query($query);
            while($result && $row=$result->fetch_assoc())
            {
                $data_object_id = $row['id'];
                $description = $row['description'];
                $description = str_replace("&nbsp;", " ", $description);
                $description = strip_tags($description);
                //$description = html_entity_decode(html_entity_decode(strip_tags($description)));
                $description = str_replace("\t", " ", $description);
                $description = str_replace("\r", " ", $description);
                $description = str_replace("\n", " ", $description);
                while(preg_match("/  /", $description)) $description = str_replace("  ", " ", $description);
                if(!preg_match("/^[0-9]+$/", $data_object_id)) echo "$data_object_id\n";
                
                if(strlen($description) > 400)
                {
                    $description = substr($description, 0, 400);
                    if(preg_match("/^(.*\.) ?[A-Z\(\[]/", $description, $arr))
                    {
                        $description = $arr[1];
                    }
                }
                if(strlen($description) < 50)
                {
                    unset($this->all_blurb_info[$data_object_id]);
                    continue;
                }
                if(isset($this->all_blurb_info[$data_object_id])) $this->all_blurb_info[$data_object_id]['description'] = $description;
            }
        }
        uasort($this->all_blurb_info, array('self', 'cmp'));
        $this->taxon_concept_blurbs = array();
        foreach($this->all_blurb_info as $data_object_id => $blurb_info)
        {
            if(isset($blurb_info['description']) && !isset($this->taxon_concept_blurbs[$blurb_info['taxon_concept_id']]))
            {
                $this->taxon_concept_blurbs[$blurb_info['taxon_concept_id']] = $blurb_info;
            }
        }
    }
    
    static function cmp($a, $b)
    {
        if ($a['vetted_id'] == $b['vetted_id']) {
            if ($a['data_rating'] == $b['data_rating']) {
                if ($a['data_object_id'] == $b['data_object_id']) {
                    return 0;
                }
                return ($a['data_object_id'] > $b['data_object_id']) ? -1 : 1;
            }
            return ($a['data_rating'] > $b['data_rating']) ? -1 : 1;
        }
        return ($a['vetted_id'] > $b['vetted_id']) ? -1 : 1;
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
        
        if($title == "Wikipedia" && preg_match("/^(.*)&oldid=[0-9]*$/", $source_url, $arr))
        {
            $source_url = $arr[1];
        }
        
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

