<?php
namespace php_active_record;

class COLImporter
{
    public function __construct(&$mysqli)
    {
        $this->mysqli =& $mysqli;
        $this->ranks = array();
        $this->rank_markers = array();
        $this->author_strings = array();
        $this->taxon_ranks = array();
        $this->ranks_below_genus = array();
        $this->synonyms = array();
        $this->common_names = array();
        
        echo "Memory: " . self::get_memory_in_mb() ."\n";
        $this->lookup_ranks();
        echo "Memory: " . self::get_memory_in_mb() ."\n";
        $this->lookup_parents();
        echo "Memory: " . self::get_memory_in_mb() ."\n";
        $this->lookup_synonyms();
        echo "Memory: " . self::get_memory_in_mb() ."\n";
        $this->lookup_common_names();
        echo "Memory: " . self::get_memory_in_mb() ."\n";
        
        
        $this->add_hierarchy();
        echo "Memory: " . self::get_memory_in_mb() ."\n";
        $this->ranks_below_genus();
        echo "Memory: " . self::get_memory_in_mb() ."\n";
        
        $this->mysqli->begin_transaction();
        $this->insert_children_recursively(NULL, 0, '', 0);
        echo "Memory: " . self::get_memory_in_mb() ."\n";
        $this->mysqli->end_transaction();
    }
    
    public function add_hierarchy()
    {
        // $agent_params = array(      "full_name"     => "Catalogue of Life",
        //                             "acronym"       => "CoLP",
        //                             "homepage"      => "http://www.catalogueoflife.org/");
        // 
        // $agent_id = Agent::insert(Functions::mock_object("php_active_record/Agent", $agent_params));
        // $hierarchy_id = Hierarchy::find_by_agent_id($agent_id);
        // if($hierarchy_id)
        // {
        //     $hierarchy = new Hierarchy($hierarchy_id);
        //     $hierarchy_group_id = $hierarchy->hierarchy_group_id;
        //     $hierarchy_group_version = $hierarchy->latest_group_version()+1;
        // }else
        // {
        //     $hierarchy_group_id = Hierarchy::next_group_id();
        //     $hierarchy_group_version = 1;
        // }
        // 
        // $hierarchy_params = array(  "label"                     => "Species 2000 & ITIS Catalogue of Life: Annual Checklist 2011",
        //                             "description"               => "2011 edition",
        //                             "agent_id"                  => $agent_id,
        //                             "hierarchy_group_id"        => $hierarchy_group_id,
        //                             "hierarchy_group_version"   => $hierarchy_group_version);
        // 
        // $this->hierarchy = new Hierarchy(Hierarchy::insert($hierarchy_params));
    }
    /*
    All ranks and their parent ranks
    SELECT t_child.taxonomic_rank_id, tr_child.rank, t_parent.taxonomic_rank_id, tr_parent.rank
    FROM (taxon_name_element tne_child
        JOIN taxon t_child ON ( tne_child.taxon_id = t_child.id )
        LEFT JOIN taxonomic_rank tr_child ON ( t_child.taxonomic_rank_id = tr_child.id ))
    LEFT JOIN (taxon_name_element tne_parent
        JOIN taxon t_parent ON ( tne_parent.taxon_id = t_parent.id )
        LEFT JOIN taxonomic_rank tr_parent ON ( t_parent.taxonomic_rank_id = tr_parent.id ))
    ON ( tne_child.parent_id = tne_parent.taxon_id )
    GROUP BY t_child.taxonomic_rank_id, t_parent.taxonomic_rank_id
    LIMIT 0 , 30
    
    6 	class 	76 	phylum
    9 	convar 	83 	species
    10 	cultivar 	20 	genus
    10 	cultivar 	83 	species
    17 	family 	72 	order
    17 	family 	112 	superfamily
    19 	form 	83 	species
    20 	genus 	17 	family
    54 	kingdom 	NULL 	NULL
    66 	monster 	83 	species
    69 	mutant 	83 	species
    72 	order 	6 	class
    76 	phylum 	54 	kingdom
    77 	prole 	83 	species
    78 	race 	83 	species
    83 	species 	20 	genus
    88 	sub-variety 	83 	species
    95 	subform 	83 	species
    104 	subspecies 	20 	genus
    104 	subspecies 	83 	species
    112 	superfamily 	72 	order
    129 	variety 	83 	species
    130 	not assigned 	83 	species
    132 	subtaxon 	83 	species
    133 	staxon 	83 	species
    134 	microgene 	83 	species
    135 	nm. 	83 	species
    136 	nothovar. 	83 	species
    141 	nothosubsp. 	83 	species
    142 	nothosp. 	83 	species
    */
        
    public function ranks_below_genus()
    {
        $this->ranks_below_genus = array(
            9 => true,
            10 => true,
            19 => true,
            66 => true,
            69 => true,
            77 => true,
            78 => true,
            83 => true,
            88 => true,
            95 => true,
            104 => true,
            129 => true,
            130 => true,
            132 => true,
            133 => true,
            134 => true,
            135 => true,
            136 => true,
            141 => true,
            142 => true);
    }
    
    public function lookup_ranks()
    {
        $query = "SELECT id, rank, marker_displayed FROM taxonomic_rank";
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            if($row[1] == 'not assigned') $row[1] = 'infraspecies';
            //$this->ranks[$row[0]] = Rank::insert($row[1]);
            $this->ranks[$row[0]] = $row[0];
        }
    }
    
    public function lookup_synonyms()
    {
        $query = "SELECT DISTINCT(taxon_id) FROM synonym";
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            $this->synonyms[$row[0]] = 1;
        }
    }
    
    public function lookup_common_names()
    {
        $query = "SELECT DISTINCT(taxon_id) FROM common_name";
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            $this->common_names[$row[0]] = 1;
        }
    }
    
    public function lookup_parents()
    {
        $this->children = array();
        $query = "SELECT taxon_id, parent_id FROM taxon_name_element";
        foreach($this->mysqli->iterate_file($query) as $row_num => $row)
        {
            $this->children[$row[1]][] = $row[0];
        }
    }
    
    public function insert_children_recursively($parent_id, $parent_hierarchy_entry_id, $ancestry, $depth)
    {
        static $counter = 0;
        if($counter % 1000 == 0) echo "counter: $counter; memory: ".memory_get_usage()."; time: ".Functions::time_elapsed()."\n";
        $counter++;
        
        //if($depth==5) return;
        if($counter % 200 == 0)
        {
            echo "COMMITING\n";
            $this->mysqli->commit();
        }
        
        if(!$parent_id) $condition = "IS NULL";
        else $condition = "= $parent_id";
        $query = "
            SELECT t.id taxon_id, sne.name_element, tr.id, tr.rank rank_string, tr.marker_displayed, auth.string author_string,
                sp.name specialist_name, t_parent.id parent_id, sne_parent.name_element, tr_parent.id, tr_parent.rank rank_string,
                tr_parent.marker_displayed, sd.name source_database,sd.version source_database_version, sc.original_scrutiny_date,
                tne_parent.parent_id
            FROM taxon t
            JOIN taxonomic_rank tr ON (t.taxonomic_rank_id=tr.id)
            JOIN taxon_name_element tne ON (t.id=tne.taxon_id)
            JOIN scientific_name_element sne ON (tne.scientific_name_element_id=sne.id)
            LEFT JOIN (
                taxon_detail td
                LEFT JOIN author_string auth ON (td.author_string_id=auth.id)
                LEFT JOIN (
                    scrutiny sc
                    JOIN specialist sp ON (sc.specialist_id=sp.id)
                ) ON (td.scrutiny_id=sc.id)
            ) ON (t.id=td.taxon_id)
            LEFT JOIN (
              taxon t_parent
              JOIN taxonomic_rank tr_parent ON (t_parent.taxonomic_rank_id=tr_parent.id)
              JOIN taxon_name_element tne_parent ON (t_parent.id=tne_parent.taxon_id)
              JOIN scientific_name_element sne_parent ON (tne_parent.scientific_name_element_id=sne_parent.id)
            ) ON (tne.parent_id=t_parent.id)
            LEFT JOIN source_database sd ON (t.source_database_id=sd.id)
            WHERE tne.parent_id $condition";
        $result = $this->mysqli->query($query);
        while($result && $row=$result->fetch_row())
        {
            foreach($row as $key => $val)
            {
                if($val == 'NULL') $row[$key] = '';
            }
            $taxon_id = trim($row[0]);
            $name_element = trim($row[1]);
            $rank_id = trim($row[2]);
            $rank = trim($row[3]);
            $marker_displayed = trim($row[4]);
            $author = trim($row[5]);
            $specialist_name = trim($row[6]);
            $parent_taxon_id = trim($row[7]);
            $parent_name_element = trim($row[8]);
            $parent_rank_id = trim($row[9]);
            $parent_rank = trim($row[10]);
            $parent_marker_displayed = trim($row[11]);
            $source_database = trim($row[12]);
            $source_database_version = trim($row[13]);
            $original_scrutiny_date = trim($row[14]);
            $parents_parent_id = trim($row[15]);
            
            
            $name = $name_element;
            $canonical_form = $name_element;
            if($author) $name = $name ." ". $author;
            if($marker_displayed) $name = $marker_displayed ." ". $name;
            if(isset($this->ranks_below_genus[$rank_id]))
            {
                $name = $parent_name_element ." ". $name;
                $canonical_form = $parent_name_element ." ". $name;
                if($parent_marker_displayed) $name = $parent_marker_displayed ." ". $name;
            }
            
            if(isset($this->ranks_below_genus[$parent_rank_id]))
            {
                list($name, $canonical_form) = $this->finish_name_recursively($name, $canonical_form, $parents_parent_id);
            }
            $name = ucfirst($name);
            
            // ADD NAME
            // $name_id = Name::insert($name);
            // if(!$name_id)
            // {
            //     echo "$id: no name_id\n";
            //     return;
            // }
            
            // ADD ENTRY
            if(isset($this->ranks[$rank_id])) $entry_rank_id = $this->ranks[$rank_id];
            else $entry_rank_id = 0;
            // echo "rank: $entry_rank_id\n";
            
            
            
            // echo "$taxon_id\t$ancestry|$name\t$rank\n";
            
            
            
            // $params = array("identifier"    => $id,
            //                 "name_id"       => $name_id,
            //                 "parent_id"     => $parent_hierarchy_entry_id,
            //                 "hierarchy_id"  => $this->hierarchy->id,
            //                 "rank_id"       => $entry_rank_id,
            //                 "ancestry"      => $ancestry);
            // $hierarchy_entry_id = HierarchyEntry::insert($params, true);
            
            
            // ADD AGENT
            $attributions = array();
            if($specialist_name) $attributions[] = $specialist_name;
            if($original_scrutiny_date) $attributions[] = $original_scrutiny_date;
            if($source_database) $attributions[] = $source_database;
            if($source_database_version) $attributions[] = $source_database_version;
            $attribution = implode(", ", $attributions);
            // $agent_id = Agent::insert(Functions::mock_object("Agent", array( "full_name" => $attribution)));
            // $hierarchy_entry->add_agent($agent_id, AgentRole::insert('Source'), 0);
            // echo "$attribution\n";
            
            // // ADD SYNONYMS AND COMMON NAMES
            // if(isset($this->synonynms[$taxon_id])) insert_synonyms($hierarchy_entry_id, $taxon_id);
            // if(isset($this->common_names[$taxon_id])) insert_common_names($hierarchy_entry_id, $taxon_id);
            // 
            
            // if($ancestry) $ancestry .= "|".$name_id;
            $this_ancestry = $ancestry;
            if($this_ancestry) $this_ancestry .= "|".$name;
            else $this_ancestry = $name;
            
            if(isset($this->children[$taxon_id]))
            {
                // $this->insert_children_recursively($taxon_id, $hierarchy_entry_id, $ancestry, $depth+1);
                $this->insert_children_recursively($taxon_id, 0, $this_ancestry, $depth+1);
            }
        }
    }
    
    public function finish_name_recursively($current_name, $current_canonical_form, $parent_id)
    {
        if(!$parent_id) return NULL;
        $query = "
            SELECT sne.name_element, tr.id rank_id, tr.rank rank_string, tr.marker_displayed, tne.parent_id
            FROM taxon t
            JOIN taxonomic_rank tr ON (t.taxonomic_rank_id=tr.id)
            JOIN taxon_name_element tne ON (t.id=tne.taxon_id)
            JOIN scientific_name_element sne ON (tne.scientific_name_element_id=sne.id)
            WHERE t.id = $parent_id";
        $result = $this->mysqli->query($query);
        if($result && $row=$result->fetch_assoc())
        {
            $name_element = trim($row['name_element']);
            $rank_id = trim($row['rank_id']);
            $rank = trim($row['rank_string']);
            $marker_displayed = trim($row['marker_displayed']);
            $parent_id = trim($row['parent_id']);
            
            $current_name = $name_element ." ". $current_name;
            $current_canonical_form = $name_element ." ". $current_canonical_form;
            if($marker_displayed) $current_name = $marker_displayed ." ". $current_name;
            
            if(isset($this->ranks_below_genus[$rank_id]))
            {
                echo "======================\n";
                list($current_name, $current_canonical_form) = $this->finish_name_recursively($current_name, $parent_id);
            }
        }
        return array($current_name, $current_canonical_form);
    }
    
    function insert_synonyms($hierarchy_entry_id, $taxon_id)
    {
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
        }
        if($result && $result->num_rows) $result->free();
    }

    function insert_common_names($hierarchy_entry_id, $taxon_id)
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
    
    public static function get_memory_in_mb()
    {
        return self::convert_bytes(memory_get_usage(true));
    }
    
    public static function convert_bytes($size)
    {
        $unit=array('b','kb','mb','gb','tb','pb');
        return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
    }
}

?>