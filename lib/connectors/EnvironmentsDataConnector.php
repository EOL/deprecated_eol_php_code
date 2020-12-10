<?php
namespace php_active_record;
require_library('PreferredEntriesCalculator');

class EnvironmentsDataConnector
{
    const DUMP_URL = "/Users/pleary/Downloads/eol_section_matches_extracted.txt";
    const ENVO_OWL_URL = "/Users/pleary/Downloads/envo.owl.txt";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->taxon_ids = array();
    }

    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->occurrence_ids = array();
        $this->taxon_ids_terms = array();
        $this->taxon_subjects = array();
        $this->taxon_names = array();
        $this->prepare_envo_schema();
        $subjects = array();

        echo "Reading file...\n";
        foreach(new FileIterator(self::DUMP_URL) as $line_number => $line)
        {
            if($line_number % 50000 == 0) echo "line $line_number\n";
            // if($line_number >= 5000) break;
            $line_data = explode("\t", $line);
            $taxon_concept_id = str_replace('EOL:', '', $line_data[0]);
            $subject = str_replace(' ', '_', strtolower(trim($line_data[1])));
            if($subject == 'taxon_biology') $subject = 'brief_summary';
            if($subject == 'biology') $subject = 'comprehensive_description';
            if($subject == 'general_description') $subject = 'comprehensive_description';
            if($subject == 'description') $subject = 'comprehensive_description';
            @$subjects[$subject]++;
            $text = trim($line_data[2]);
            $uri = trim($line_data[3]);
            if(!trim($uri)) continue;
            $uri = "http://purl.obolibrary.org/obo/" . str_replace(":", "_", $uri);
            if(!isset($this->envo_term_uris[$uri]))
            {
                echo "$uri is not valid\n";
                continue;
            }
            $label = $this->envo_term_uris[$uri];
            if(preg_match("/ (feature|region|entity|material|physical)/", $label)) continue;
            static $terms_to_skip = array('habitat', 'environmental condition');
            if(in_array($label, $terms_to_skip)) continue;

            $this->taxon_ids_terms[$taxon_concept_id][$uri] = $text;
            $this->taxon_subjects[$taxon_concept_id] = $subject;
        }
        arsort($subjects);
        print_r($subjects);
        $this->filter_out_parent_classes();
        $this->lookup_taxon_names();
        foreach($this->taxon_ids_terms as $taxon_concept_id => $uris)
        {
            static $i = 0;
            if($i % 1000 == 0) echo "Inserting taxon $i\n";
            $i++;
            $taxon = $this->add_taxon($taxon_concept_id);
            $occurrence = $this->add_occurrence($taxon);
            $this->add_measurements($taxon_concept_id, $occurrence, $uris);
        }
        $this->archive_builder->finalize(true);
    }

    private function add_taxon($taxon_concept_id)
    {
        $taxon_id = 'EOL:' . $taxon_concept_id;
        if(isset($this->taxon_ids[$taxon_id])) return $this->taxon_ids[$taxon_id];
        $t = new \eol_schema\Taxon();
        $t->taxonID = $taxon_id;
        $names = @$this->taxon_concept_names[$taxon_concept_id];
        if(!$names) return false;
        if(!$names['scientificName']) return false;
        $t->scientificName = $names['scientificName'];
        $t->kingdom = @$names['kingdom'];
        $t->phylum = @$names['phylum'];
        // $t->class = @$names['class'];
        // $t->order = @$names['order'];
        $t->family = @$names['family'];
        $this->archive_builder->write_object_to_file($t);
        $this->taxon_ids[$taxon_id] = $t;
        return $t;
    }

    private function lookup_taxon_names()
    {
        $batches = array_chunk(array_keys($this->taxon_ids_terms), 10000);
        foreach($batches as $batch)
        {
            $this->lookup_taxon_name_batch($batch);
        }
    }

    private function lookup_taxon_name_batch($taxon_concept_ids)
    {
        $entry_taxon_concept_ids = array();
        foreach($GLOBALS['db_connection']->iterate("
            SELECT pref.taxon_concept_id, he.id, n.string
            FROM taxon_concept_preferred_entries pref
            JOIN hierarchy_entries he ON (pref.hierarchy_entry_id=he.id)
            LEFT JOIN names n ON (he.name_id=n.id)
            WHERE pref.taxon_concept_id IN (". implode(",", $taxon_concept_ids) .")") as $row)
        {
            $entry_taxon_concept_ids[$row['id']] = $row['taxon_concept_id'];
            $this->taxon_concept_names[$row['taxon_concept_id']]['scientificName'] = $row['string'];
        }

        $kingdom_ids = Rank::kingdom_rank_ids();
        $phylum_ids = Rank::phylum_rank_ids();
        // $class_ids = Rank::class_rank_ids();
        // $order_ids = Rank::order_rank_ids();
        $family_ids = Rank::family_rank_ids();
        // $all_rank_ids = array_merge($kingdom_ids, $phylum_ids, $class_ids, $order_ids, $family_ids);
        $all_rank_ids = array_merge($kingdom_ids, $phylum_ids, $family_ids);
        foreach($GLOBALS['db_connection']->iterate("
            SELECT hef.hierarchy_entry_id, he.id, he.rank_id, n.string
            FROM hierarchy_entries_flattened hef
            JOIN hierarchy_entries he ON (hef.ancestor_id=he.id)
            LEFT JOIN names n ON (he.name_id=n.id)
            WHERE hef.hierarchy_entry_id IN (". implode(",", array_keys($entry_taxon_concept_ids)) .")
            AND he.rank_id IN (". implode(",", $all_rank_ids) .")") as $row)
        {
            $taxon_concept_id = $entry_taxon_concept_ids[$row['hierarchy_entry_id']];
            $name_string = $row['string'];
            if(Name::is_surrogate($name_string)) continue;
            if(in_array($row['rank_id'], $kingdom_ids)) $this->taxon_concept_names[$taxon_concept_id]['kingdom'] = $name_string;
            elseif(in_array($row['rank_id'], $phylum_ids)) $this->taxon_concept_names[$taxon_concept_id]['phylum'] = $name_string;
            // elseif(in_array($row['rank_id'], $class_ids)) $this->taxon_concept_names[$taxon_concept_id]['class'] = $name_string;
            // elseif(in_array($row['rank_id'], $order_ids)) $this->taxon_concept_names[$taxon_concept_id]['order'] = $name_string;
            elseif(in_array($row['rank_id'], $family_ids)) $this->taxon_concept_names[$taxon_concept_id]['family'] = $name_string;
        }
    }

    private function add_occurrence($taxon)
    {
        $occurrence_id = md5($taxon->taxonID . 'occurrence');
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon->taxonID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    private function add_measurements($taxon_id, $occurrence, $uris)
    {
        foreach($uris as $uri => $source_text)
        {
            $m = new \eol_schema\MeasurementOrFact();
            $m->occurrenceID = $occurrence->occurrenceID;
            $m->measurementOfTaxon = 'true';
            $m->measurementType = 'http://purl.obolibrary.org/obo/RO_0002303';
            $m->measurementValue = $uri;
            $m->measurementMethod = 'text mining';
            $m->contributor = '<a href="http://environments-eol.blogspot.com/2013/03/welcome-to-environments-eol-few-words.html">Environments-EOL</a>';
            $m->source = "http://eol.org/pages/$taxon_id/details#". $this->taxon_subjects[$taxon_id];
            $m->measurementRemarks = "source text: \"$source_text\"";
            $this->archive_builder->write_object_to_file($m);
        }
    }

    private function filter_out_parent_classes()
    {
        foreach($this->taxon_ids_terms as $taxon_id => $uris)
        {
            if(count($uris) <= 1) continue;
            $filtered_uris = $uris;
            foreach($uris as $uri => $junk)
            {
                if($this->envo_term_parents[$uri])
                {
                    foreach($this->envo_term_parents[$uri] as $parent_uri)
                    {
                        unset($filtered_uris[$parent_uri]);
                    }
                }
            }
            $this->taxon_ids_terms[$taxon_id] = $filtered_uris;
        }
    }

    private function prepare_envo_schema()
    {
        $envo_schema = file_get_contents(self::ENVO_OWL_URL);
        $this->envo_term_uris = array();
        $this->envo_term_parents = array();
        if(preg_match_all("/\n    <owl:Class rdf:about=\"(.*?)\"(.*?)\n    <\/owl:Class>/ims", $envo_schema, $matches, PREG_SET_ORDER))
        {
            foreach($matches as $match)
            {
                $class_uri = $match[1];
                $class_xml = $match[2];
                if(preg_match("/<rdfs:label.*?>(.*?)<\/rdfs:label>/", $class_xml, $arr)) $this->envo_term_uris[$class_uri] = $arr[1];
                // subclass
                if(preg_match_all("/<rdfs:subClassOf rdf:resource=\"(.*?)\"\/>/ims", $class_xml, $arrs, PREG_SET_ORDER))
                {
                    foreach($arrs as $arr) $this->envo_term_parents[$class_uri][] = $arr[1];
                }
                // part_of
                if(preg_match_all("/<owl:onProperty rdf:resource=\"http:\/\/purl.obolibrary.org\/obo\/BFO_0000050\"\/>\s+<owl:someValuesFrom rdf:resource=\"(.*?)\"\/>/ims", $class_xml, $arrs, PREG_SET_ORDER))
                {
                    foreach($arrs as $arr) $this->envo_term_parents[$class_uri][] = $arr[1];
                }
            }
        }
        $this->processed_uri_parents = array();
        foreach($this->envo_term_parents as $uri => $parent_uri) $this->add_parents_recursively($uri);
    }

    private function add_parents_recursively($uri)
    {
        if(isset($this->processed_uri_parents[$uri])) return $this->processed_uri_parents[$uri];
        if(isset($this->envo_term_parents[$uri]))
        {
            foreach($this->envo_term_parents[$uri] as $parent_uri)
            {
                $parent_uris = $this->add_parents_recursively($parent_uri);
                $this->envo_term_parents[$uri] = array_unique(array_merge($this->envo_term_parents[$uri], $parent_uris));
            }
            $this->processed_uri_parents[$uri] = $this->envo_term_parents[$uri];
        }else $this->processed_uri_parents[$uri] = array();
        return $this->processed_uri_parents[$uri];
    }
}

?>