<?php
namespace php_active_record;

class EOLArchiveNamesToFamily
{
    private $mysqli;
    private $mysqli_slave;
    private $content_archive_builder;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->output_directory = DOC_ROOT . "temp/eol_names_and_ranks_to_family_archive/";
        recursive_rmdir($this->output_directory);
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->output_directory));
        $this->load_all_ranks();
        $this->load_all_hierarchies();
    }
    
    public function create()
    {
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts tc");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->lookup_ranks($i, $limit);
            $this->lookup_hierarchies($i, $limit);
            $this->lookup_names($i, $limit);
        }
        $this->archive_builder->finalize(true);
    }
    
    function sort_ranks()
    {
        foreach($this->all_rank_ids as $taxon_concept_id => $rank_ids)
        {
            arsort($rank_ids);
            $best_rank_id = key($rank_ids);
            if($label = $this->all_ranks[$best_rank_id])
            {
                $this->ranks[$taxon_concept_id] = $label;
                $this->best_rank_ids[$taxon_concept_id] = $best_rank_id;
            }
        }
    }
    
    function lookup_ranks($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying ranks");
        $query = "SELECT he.taxon_concept_id, he.rank_id
          FROM hierarchy_entries he
          JOIN ranks r ON (he.rank_id=r.id)
          AND r.id IN (". implode(',', $this->linnaean_rank_ids) .")
          AND he.published = 1
          AND he.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $this->best_rank_ids = array();
        $this->all_rank_ids = array();
        $this->ranks = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $taxon_concept_id = $row[0];
            $rank_id = $row[1];
            if($rank_id)
            {
                if(!isset($this->all_rank_ids[$taxon_concept_id][$rank_id])) $this->all_rank_ids[$taxon_concept_id][$rank_id] = 1;
                else $this->all_rank_ids[$taxon_concept_id][$rank_id] += 1;
            }
        }
        $this->sort_ranks();
    }
    
    function lookup_hierarchies($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying ranks");
        $query = "SELECT he.taxon_concept_id, he.rank_id, h.id, h.label
          FROM hierarchy_entries he
          JOIN hierarchies h ON (he.hierarchy_id=h.id)
          JOIN ranks r ON (he.rank_id=r.id)
          AND r.id IN (". implode(',', $this->linnaean_rank_ids) .")
          AND he.published = 1
          AND he.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit);
        
        $this->hierarchies = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            $taxon_concept_id = $row[0];
            $rank_id = $row[1];
            $hierarchy_id = $row[2];
            $hierarchy_label = $row[3];
            if($hierarchy_label)
            {
                if($rank_id = @$this->best_rank_ids[$taxon_concept_id])
                {
                    if(!isset($this->hierarchies[$taxon_concept_id][$hierarchy_label])) $this->hierarchies[$taxon_concept_id][$hierarchy_label] = 1;
                }
            }
        }
        $this->sort_ranks();
    }
    
    function lookup_names($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying names");
        $query = "SELECT tc.id, n.string, cf.string, he.rank_id, h.label
            FROM taxon_concepts tc
            JOIN taxon_concept_preferred_entries pe ON (tc.id=pe.taxon_concept_id)
            JOIN hierarchy_entries he ON (pe.hierarchy_entry_id=he.id)
            JOIN names n ON (he.name_id=n.id)
            JOIN hierarchies h ON (he.hierarchy_id=h.id)
            LEFT JOIN canonical_forms cf ON (n.canonical_form_id=cf.id)
            WHERE tc.supercedure_id = 0
            AND tc.published = 1
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
            $rank_id = $row[3];
            if($name_string == 'NULL') $name_string = NULL;
            if($canonical_form == 'NULL') $canonical_form = NULL;
            if(!$name_string) continue;
            
            $t = new \eol_schema\Taxon();
            $t->taxonID = $taxon_concept_id;
            $t->scientificName = $name_string;
            if(@!$this->ranks[$taxon_concept_id]) continue;
            if($hierarchies = @$this->hierarchies[$taxon_concept_id])
            {
                $t->nameAccordingTo = implode("; ", array_keys($hierarchies));
            }
            if($canonical_form && $rank_label = @$this->ranks[$taxon_concept_id])
            {
                if($rank_label == "gen.") $rank_label = 'genus';
                elseif($rank_label == "sp.") $rank_label = 'species';
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
        
        $this->linnaean_rank_ids = array();
        $result = $this->mysqli_slave->query("SELECT r.id, tr.label FROM ranks r JOIN translated_ranks tr ON (r.id=tr.rank_id) WHERE tr.language_id=". Language::english()->id ."
            AND tr.label IN ('kingdom', 'phylum', 'class', 'order', 'family')");
        while($result && $row=$result->fetch_assoc())
        {
            $this->linnaean_rank_ids[] = $row['id'];
        }
        return $this->linnaean_rank_ids;
    }
    
    function load_all_hierarchies()
    {
        $this->all_hierarchies = array();
        $result = $this->mysqli_slave->query("SELECT id, label FROM hierarchies");
        while($result && $row=$result->fetch_assoc())
        {
            $this->all_hierarchies[$row['id']] = strtolower($row['label']);
        }
    }
}

?>
