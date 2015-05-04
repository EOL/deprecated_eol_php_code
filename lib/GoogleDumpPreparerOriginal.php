<?php
namespace php_active_record;

class GoogleDumpPreparerOriginal
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
        $max_id = 1000000 - 1;
        
        if(!($this->NAMES_OUT = fopen(DOC_ROOT ."/temp/google_dump_names.txt", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT ."/temp/google_dump_names.txt");
          return;
        }
        fwrite($this->NAMES_OUT, "EOL PAGE ID\tNAME\tPARENT EOL PAGE ID\tRANK\n");
        if(!(!$this->LINKS_OUT = fopen(DOC_ROOT ."/temp/google_dump_links.txt", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . DOC_ROOT ."/temp/google_dump_links.txt");
          return;
        }
        fwrite($this->LINKS_OUT, "EOL PAGE ID\tPARTNER NAME\tPARTNER URL\n");
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->lookup_block($i, $limit);
            $this->lookup_block2($i, $limit);
        }
        fclose($this->LINKS_OUT);
        fclose($this->NAMES_OUT);
    }
    
    private function lookup_block($start, $limit)
    {
        $query = "SELECT tc.id, n.string, cf.string, he_parent.taxon_concept_id, tr.label FROM taxon_concepts tc JOIN taxon_concept_preferred_entries tcpe ON (tc.id=tcpe.taxon_concept_id) JOIN hierarchy_entries he ON (tcpe.hierarchy_entry_id=he.id) JOIN names n ON (he.name_id=n.id) LEFT JOIN canonical_forms cf ON (n.ranked_canonical_form_id=cf.id) LEFT JOIN hierarchy_entries he_parent ON (he.parent_id=he_parent.id) LEFT JOIN (ranks r JOIN translated_ranks tr ON (r.id = tr.rank_id AND tr.language_id=152)) ON (he.rank_id=r.id) WHERE tc.id BETWEEN $start AND ". ($start+$limit) ." AND he.published=1 AND he.visibility_id=1";
        
        static $j = 0;
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            if($j % 50000 == 0) echo "$start : $j : ". time_elapsed() ." : ". memory_get_usage() ."\n";
            $j++;
            $taxon_concept_id = $row[0];
            $string = trim($row[1]);
            $ranked_canonical_form = trim($row[2]);
            $parent_page_id = $row[3];
            $rank_label = $row[4];
            if(!$parent_page_id || $parent_page_id == "NULL") $parent_page_id = 0;
            if(!$rank_label || $rank_label == "NULL") $rank_label = '';
            
            $name = null;
            if($ranked_canonical_form != 'NULL' && !Name::is_surrogate($ranked_canonical_form)) $name = $ranked_canonical_form;
            elseif($string != 'NULL') $name = $string;
            if(!$name) continue;
            
            // echo "$taxon_concept_id : $name : $parent_page_id : $rank_label\n";
            fwrite($this->NAMES_OUT, "$taxon_concept_id\t$name\t$parent_page_id\t$rank_label\n");
        }
    }
    
    private function lookup_block2($start, $limit)
    {
        $query = "SELECT tc.id taxon_concept_id, he.id hierarchy_entry_id, he.identifier, he.source_url, h.label, h.outlink_uri, r.title, h.id FROM taxon_concepts tc JOIN hierarchy_entries he ON (tc.id=he.taxon_concept_id) JOIN hierarchies h ON (he.hierarchy_id=h.id) LEFT JOIN resources r ON (h.id=r.hierarchy_id) WHERE tc.id BETWEEN $start AND ". ($start+$limit) ." AND he.published=1 AND he.visibility_id=1";
        
        static $j = 0;
        $concept_links = array();
        $links_from_hierarchy = array();
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            if($j % 50000 == 0) echo "$start : $j : ". time_elapsed() ." : ". memory_get_usage() ."\n";
            $j++;
            $taxon_concept_id = $row[0];
            $hierarchy_id = $row[7];
            
            if(@!$concept_links[$taxon_concept_id]) $concept_links[$taxon_concept_id] = array();
            if(@$links_from_hierarchy[$taxon_concept_id][$hierarchy_id]) continue;
            
            if($link = $this->prepare_link($row))
            {
                fwrite($this->LINKS_OUT, "$taxon_concept_id\t". $link['title'] ."\t". $link['url'] ."\n");
                
                $concept_links[$taxon_concept_id][] = $link;
                $links_from_hierarchy[$taxon_concept_id][$hierarchy_id] = 1;
            }
            
            // $all_entries[$taxon_concept_id][$hierarchy_id] = array(
            //     'hierarchy_entry_id' => $hierarchy_entry_id,
            //     'visibility_id' => $visibility_id,
            //     'vetted_id' => $vetted_id,
            //     'vetted_view_order' => $vetted_view_order,
            //     'hierarchy_id' => $hierarchy_id,
            //     'browsable' => $browsable);
            
        }
        // foreach($concept_links as $taxon_concept_id => $links)
        // {
        //     if(!$links) continue;
        //     usort($links, array("php_active_record\GoogleDumpPreparer", "sort_by_title"));
        //     // echo "$taxon_concept_id\n";
        //     // print_r($links);
        //     
        // }
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
            }else
            {
                return array('title' => $title, 'url' => $o);
            }
            //   # .. and the ID exists
            //   unless he.identifier.blank?
            //     all_outlinks << {:hierarchy_entry => he, :hierarchy => he.hierarchy, :outlink_url => he.hierarchy.outlink_uri.gsub(/%%ID%%/, he.identifier) }
            //     used_hierarchies << he.hierarchy
            //   end
            // else
            //   # there was no %%ID%% pattern in the outlink_uri, but its not blank so its a generic URL for all entries
            //   all_outlinks << {:hierarchy_entry => he, :hierarchy => he.hierarchy, :outlink_url => he.hierarchy.outlink_uri }
            //   used_hierarchies << he.hierarchy
            // end
            // 
        }
    }
    
    private static function sort_by_title($a, $b)
    {
        return ($a['title'] < $b['title']) ? -1 : 1;  // ascending
    }
}

?>