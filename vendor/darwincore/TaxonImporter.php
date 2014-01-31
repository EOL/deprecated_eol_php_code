<?php
namespace php_active_record;

class TaxonImporter
{
    private $mysqli;
    private $valid_taxonomic_statuses;
    private $children;
    private $synonyms;
    private $common_names;
    private $hierarchy;
    private $hierarchy_vetted_id;
    private $hierarchy_published;
    private $taxon_ids_inserted;
    
    public function __construct(&$hierarchy, $vetted_id = 0, $visibility_id = 0, $published = 0)
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->valid_taxonomic_statuses = array("valid", "accepted");
        $this->hierarchy = $hierarchy;
        $this->hierarchy_vetted_id = $vetted_id;
        $this->hierarchy_visibility_id = $visibility_id;
        $this->hierarchy_published = $published;
    }
    
    public function import_taxa($taxa)
    {
        if(!$taxa) return false;
        $this->children = array();
        $this->synonyms = array();
        $this->common_names = array();
        
        foreach($taxa as $key => $taxon)
        {
            $is_valid = true;
            if(isset($taxon->taxonomicStatus) && trim($taxon->taxonomicStatus)!='')
            {
                $is_valid = Functions::array_searchi($taxon->taxonomicStatus, $this->valid_taxonomic_statuses);
                // $is_valid might be zero at this point so we need to check
                if($is_valid === null) $is_valid = false;
            }
            if($is_valid && isset($taxon->acceptedNameUsageID) && trim($taxon->acceptedNameUsageID)!='') $is_valid = false;
            
            $taxon_id = $taxon->taxonID;
            $parent_taxon_id = @$taxon->parentNameUsageID;
            $accepted_taxon_id = @$taxon->acceptedNameUsageID;
            
            if($taxon_id && $is_valid && @$taxon->scientificName)
            {
                if(!$parent_taxon_id) $parent_taxon_id = 0;
                $this->children[$parent_taxon_id][] = $taxon;
            }elseif($taxon_id && @$taxon->vernacularName)
            {
                $this->vernacular_names[$taxon_id][] = $taxon;
            }elseif(!$is_valid && $parent_taxon_id)
            {
                $this->synonyms[$parent_taxon_id][] = $taxon;
            }elseif(!$is_valid && $accepted_taxon_id)
            {
                $this->synonyms[$accepted_taxon_id][] = $taxon;
            }
            // remove the taxon from the big array to free some memory
            unset($taxa[$key]);
        }
        $this->begin_adding_nodes();
        
        // set the nested set values
        Tasks::rebuild_nested_set($this->hierarchy->id);
        
        // // Rebuild the Solr index for this hierarchy
        // $indexer = new HierarchyEntryIndexer();
        // $indexer->index($this->hierarchy->id);
        // 
        // // Compare this hierarchy to all others and store the results in the hierarchy_entry_relationships table
        // CompareHierarchies::process_hierarchy($this->hierarchy, null, true);
        // 
        // // Use the entry relationships to assign the proper concept IDs
        // CompareHierarchies::begin_concept_assignment($this->hierarchy->id);
    }
    
    private function begin_adding_nodes()
    {
        $this->taxon_ids_inserted = array();
        if(isset($this->children[0]))
        {
            foreach($this->children[0] as $child_taxon)
            {
                $parent_hierarchy_entry_id = 0;
                $ancestry = "";
                $this->mysqli->begin_transaction();
                $this->add_hierarchy_entry($child_taxon, $parent_hierarchy_entry_id, $ancestry);
                $this->mysqli->end_transaction();
            }
        }else
        {
            echo "THERE ARE NO ROOT TAXA\nAborting import\n";
        }
    }
    
    function add_hierarchy_entry(&$taxon, $parent_hierarchy_entry_id, $ancestry)
    {
        static $i=0;
        $i++;
        if($i%500 == 0 && $GLOBALS['ENV_DEBUG'])
        {
            echo "Memory: ".memory_get_usage()." : $i\n";
            print_r($taxon);
        }
        
        // make sure this taxon has a name, otherwise skip this branch
        if(!isset($taxon->scientificName)) return false;
        // this taxon_id has already been inserted meaning this tree has a loop in it - so stop
        if(isset($this->taxon_ids_inserted[$taxon->taxonID])) return false;
        
        if(isset($taxon->scientificNameAuthorship))
        {
            $taxon->scientificName = trim($taxon->scientificName)." ".$taxon->scientificNameAuthorship;
        }
        
        $name_id = Name::find_or_create_by_string($taxon->scientificName)->id;
        $params = array("identifier"    => $taxon->taxonID,
                        "name_id"       => $name_id,
                        "parent_id"     => $parent_hierarchy_entry_id,
                        "hierarchy_id"  => $this->hierarchy->id,
                        "rank"          => Rank::find_or_create_by_translated_label(@$taxon->taxonRank),
                        "ancestry"      => $ancestry,
                        "vetted_id"     => $this->hierarchy_vetted_id,
                        "visibility_id" => $this->hierarchy_visibility_id,
                        "published"     => $this->hierarchy_published);
        
        $hierarchy_entry = HierarchyEntry::find_or_create_by_array($params);
        $this->taxon_ids_inserted[$taxon->taxonID] = $hierarchy_entry->id;
        unset($params);
        
        if(isset($this->synonyms[$taxon->taxonID]))
        {
            foreach($this->synonyms[$taxon->taxonID] as $synonym_taxon)
            {
                if(!isset($synonym_taxon->scientificName)) continue;
                if(isset($synonym_taxon->taxonID) && isset($this->taxon_ids_inserted[$synonym_taxon->taxonID])) continue;
                
                $name_id = Name::find_or_create_by_string($synonym_taxon->scientificName)->id;
                $synonym_relation = SynonymRelation::find_or_create_by_translated_label(@$synonym_taxon->taxonomicStatus);
                $hierarchy_entry->add_synonym($name_id, @$synonym_relation->id ?: 0, 0, 0, $this->hierarchy_vetted_id, $this->hierarchy_published);
                if(isset($synonym_taxon->taxonID)) $this->taxon_ids_inserted[$synonym_taxon->taxonID] = 1;
            }
            unset($this->synonyms[$taxon->taxonID]);
        }
        
        if(isset($this->vernacular_names[$taxon->taxonID]))
        {
            foreach($this->vernacular_names[$taxon->taxonID] as $vernacular)
            {
                if(!isset($vernacular->vernacularName)) continue;
                
                $name_id = Name::find_or_create_by_string($vernacular->vernacularName)->id;
                $language = Language::find_or_create_for_parser(@$vernacular->dcterms->language);
                $synonym_relation = SynonymRelation::find_or_create_by_translated_label('common name');
                $hierarchy_entry->add_synonym($name_id, @$synonym_relation->id ?: 0, @$language->id ?: 0, 0, $this->hierarchy_vetted_id, $this->hierarchy_published);
            }
            unset($this->vernacular_names[$taxon->taxonID]);
        }
        
        if(isset($this->children[$taxon->taxonID]))
        {
            // set the ancestry for its children
            if($ancestry) $this_ancestry = $ancestry."|".$name_id;
            else $this_ancestry = $name_id;
            
            foreach($this->children[$taxon->taxonID] as $child_taxon)
            {
                $this->add_hierarchy_entry($child_taxon, $hierarchy_entry->id, $this_ancestry);
            }
            unset($this->children[$taxon->taxonID]);
        }
        unset($hierarchy_entry);
    }
}

?>