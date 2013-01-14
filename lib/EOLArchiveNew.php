<?php
namespace php_active_record;

class EOLArchive
{
    private $mysqli;
    private $mysqli_slave;
    private $content_archive_builder;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => DOC_ROOT . "/temp/eol_names_archive/"));
        $this->load_all_ranks();
    }
    
    public function create()
    {
        $start = 0;
        $max_id = 0;
        $limit = 100000;
        
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->lookup_ancestors();
            
            // $this->lookup_ranks($i, $limit);
            
            // $this->lookup_names($i, $limit);
        }
        $this->archive_builder->finalize();
    }
    
    function sort_ranks()
    {
        foreach($this->ranks_ids as $taxon_concept_id => $rank_ids)
        {
            arsort($rank_ids);
            $best_rank_id = key($rank_ids);
            if($label = $this->all_ranks[$best_rank_id])
            {
                $this->ranks[$taxon_concept_id] = $label;
            }
        }
    }
    
    function lookup_ranks($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying ranks");
        $query = "SELECT he.taxon_concept_id, he.rank_id
          FROM hierarchy_entries he USE INDEX (taxon_concept_id)
          WHERE he.visibility_id=".Visibility::visible()->id."
          AND he.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $this->ranks_ids = array();
        $this->ranks = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $taxon_concept_id = $row[0];
            $rank_id = $row[1];
            if($rank_id)
            {
                if(!isset($this->ranks_ids[$taxon_concept_id][$rank_id])) $this->ranks_ids[$taxon_concept_id][$rank_id] = 1;
                else $this->ranks_ids[$taxon_concept_id][$rank_id] += 1;
            }
        }
        $this->sort_ranks();
    }
    
    function ancestors($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying ancestors");
        $query = "SELECT he.taxon_concept_id, he.rank_id, n.string
          FROM hierarchy_entries he
          JOIN hierarchy_entries_flattened hef ON (he.id=hef.hierarchy_entry_id)
          JOIN hierarchy_entries he_ancestor ON (hef.ancestor_id=he_ancestor.id)
          JOIN ranks r ON (he_ancestor.rank_id=r.id)
          JOIN names n ON (he_ancestor.name_id=n.id)
          WHERE he.visibility_id=1
          AND r.id IN (183, 280)
          AND he.taxon_concept_id BETWEEN 1000000 AND 1001000;";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $this->ranks_ids = array();
        $this->ranks = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $taxon_concept_id = $row[0];
            $rank_id = $row[1];
            if($rank_id)
            {
                if(!isset($this->ranks_ids[$taxon_concept_id][$rank_id])) $this->ranks_ids[$taxon_concept_id][$rank_id] = 1;
                else $this->ranks_ids[$taxon_concept_id][$rank_id] += 1;
            }
        }
        $this->sort_ranks();
    }
    
    function lookup_names($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying names");
        $query = "SELECT tc.id, n.string, cf.string
            FROM taxon_concepts tc
            JOIN taxon_concept_preferred_entries pe ON (tc.id=pe.taxon_concept_id)
            JOIN hierarchy_entries he ON (pe.hierarchy_entry_id=he.id)
            JOIN names n ON (he.name_id=n.id)
            LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)
            WHERE tc.supercedure_id=0
            AND tc.published=1
            AND tc.id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        static $i = 0;
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            if($i % 1000 == 0) echo "$i :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $i++;
            $taxon_concept_id = $row[0];
            $name_string = $row[1];
            $canonical_form = $row[2];
            if($name_string == 'NULL') $name_string = NULL;
            if($canonical_form == 'NULL') $canonical_form = NULL;
            if(!$name_string) continue;
            
            $t = new \eol_schema\Taxon();
            $t->taxonID = $taxon_concept_id;
            $t->scientificName = $name_string;
            if($rank_label = @$this->ranks[$taxon_concept_id])
            {
                if($rank_label == "gen.") $rank_label = 'genus';
                elseif($rank_label == "sp.") $rank_label = 'species';
                elseif($rank_label == "subsp.") $rank_label = 'subspecies';
                elseif($rank_label == "var.") $rank_label = 'variety';
                $t->taxonRank = $rank_label;
                
                if($rank_label == 'species' && $canonical_form)
                {
                    $words = explode(" ", $canonical_form);
                    if(count($words) == 2)
                    {
                        $t->genus = $words[0];
                        $t->specificEpithet = $words[1];
                    }
                }elseif($rank_label == 'genus' && $canonical_form)
                {
                    $words = explode(" ", $canonical_form);
                    if(count($words) == 1)
                    {
                        $t->genus = $words[0];
                    }
                }elseif(in_array(array('subspecies', 'variety', 'infraspecies', 'infraspecificname', 'form')) && $canonical_form)
                {
                    $words = explode(" ", $canonical_form);
                    if(count($words) == 3)
                    {
                        $t->genus = $words[0];
                        $t->specificEpithet = $words[1];
                        $t->infraspecificEpithet = $words[2];
                    }
                }
            }
            $this->archive_builder->write_object_to_file($t);
            unset($t);
        }
    }
    
    function load_all_ranks()
    {
        $this->all_ranks = array();
        $result = $this->mysqli_slave->query("SELECT r.id, tr.label FROM ranks r JOIN translated_ranks tr ON (r.id=tr.rank_id) WHERE tr.language_id=". Language::english()->id);
        while($result && $row=$result->fetch_assoc())
        {
            $this->all_ranks[$row['id']] = strtolower($row['label']);
        }
        return $this->all_ranks;
    }
}

?>