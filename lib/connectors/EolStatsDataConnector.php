<?php
namespace php_active_record;

class EolStatsDataConnector
{
    private static $all_ranks = array('superkingdom', 'kingdom', 'subkingdom', 'infrakingdom', 'superdivision', 'superphylum',
        'division', 'phylum', 'subdivision', 'subphylum', 'infraphylum', 'parvphylum', 'superclass', 'infraphylum',
        'class', 'subclass', 'infraclass', 'superorder', 'order', 'family');
    private static $column_ranks = array('kingdom', 'subkingdom', 'infrakingdom', 'superphylum',
            'phylum', 'subphylum', 'infraphylum', 'superclass',
            'class', 'subclass', 'infraclass', 'superorder', 'order', 'family');

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->source_file_path = DOC_ROOT . "temp/falo.txt";
    }

    public function begin()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->process_export();
        $this->archive_builder->finalize(true);
    }

    private function process_export()
    {
        foreach(new FileIterator($this->source_file_path) as $line_number => $line)
        {
            $line_text = trim($line);
            // if(!preg_match("/Gadiformes/", $line)) continue;
            if($line_number == 0 || !$line_text) continue;
            $all_names_in_row = explode("\t", $line_text);
            $ancestors = array();
            foreach($all_names_in_row as $key => $name)
            {
                $synonyms = array();
                $rank = self::$column_ranks[$key];
                if($name_info = $this->parse_name_information($name))
                {
                    $name = $name_info['name'];
                    $rank = isset($name_info['rank']) ? $name_info['rank'] : $rank;
                    $synonyms = isset($name_info['synonyms']) ? $name_info['synonyms'] : array();
                    // names should be one word with normal characters. Stop completely if this occurs
                    if(preg_match("/( |[^a-z])/i", $name))
                    {
                        echo "This is a line that isn't being handled properly:\n$line_number: $line :: |$name|\n\n\n";
                        return;
                    }
                    if($rank == 'family')
                    {
                        // $this->write_name_and_ancestray($line_text);
                        $this->write_stats($name, $synonyms, $ancestors);
                    }else
                    {
                        $ancestors[$key] = array('rank' => $rank, 'name' => $name, 'synonyms' => $synonyms);
                    }
                    
                }
            }
        }
    }

    private function parse_name_information($name_string)
    {
        $name_string = trim(preg_replace("/(\"|')/", "", $name_string));
        if(!$name_string) return null;
        if($name_string == "Incertae") return null;
        if(preg_match("/^(unspecified|unidentified) ([^ ]+)$/i", $name_string, $arr)) return null;
        // Order Bartramiales  ==>  Bartramiales
        if(preg_match("/^(". implode("|", self::$all_ranks).") (.+)$/i", $name_string, $arr))
        {
            return array('name' => trim($arr[2]),
                         'rank' => strtolower($arr[1]));
        }
        // Subclass Ophioglossidae [= Psilotidae]
        if(preg_match("/^([^ ]+) \[=? ?([^ ]+)\]$/i", $name_string, $arr))
        {
            return array('name' => trim($arr[1]),
                         'synonyms' => array($arr[2]));
        }
        // Superdivision Embryophyta [=Class Embryopsida = Class Equisetopsida]
        if(preg_match("/^([^ ]+) \[=Class ([^ ]+) = Class ([^ ]+)\]$/i", $name_string, $arr))
        {
            return array('name' => trim($arr[1]),
                         'synonyms' => array($arr[2], $arr[3]));
        }
        return array('name' => $name_string);
    }

    private function write_stats($name, $synonyms, $ancestors)
    {
        if($taxon_concept_id = $this->lookup_family($name, $synonyms, $ancestors))
        {
            $stats = $this->get_stats_for_family($taxon_concept_id);
            $occurrence = $this->write_occurrence($name, $ancestors);
            echo "$name :: $taxon_concept_id\n";
            foreach($stats as $type => $value)
            {
                $m = new \eol_schema\MeasurementOrFact();
                $m->occurrenceID = $occurrence->occurrenceID;
                $m->measurementType = 'http://eol.org/schema/terms/' . $type;
                $m->measurementValue = $value;
                $m->measurementOfTaxon = 'true';
                $m->source = "http://eol.org/pages/$taxon_concept_id/overview";
                $this->archive_builder->write_object_to_file($m);
            }
        }
    }

    private function write_occurrence($name, $ancestors)
    {
        $t = new \eol_schema\Taxon();
        $t->scientificName = $name;
        $t->taxonRank = 'family';
        $t->taxonID = md5($t->scientificName . 'eolstats');
        $this->archive_builder->write_object_to_file($t);

        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = md5($t->taxonID . 'eolstats');
        $o->taxonID = $t->taxonID;
        $this->archive_builder->write_object_to_file($o);
        return $o;
    }

    private function get_stats_for_family($taxon_concept_id)
    {
        $query = "SELECT COUNT(DISTINCT he_children.taxon_concept_id) as count
            FROM hierarchy_entries he
            JOIN hierarchy_entries_flattened hef on (he.id=hef.ancestor_id)
            JOIN hierarchy_entries he_children on (hef.hierarchy_entry_id=he_children.id)
            JOIN taxon_concepts tc on (he_children.taxon_concept_id=tc.id)
            JOIN hierarchies h on (he_children.hierarchy_id=h.id)
            LEFT JOIN taxon_concept_metrics tcm ON (he_children.taxon_concept_id=tcm.taxon_concept_id)
            WHERE he.taxon_concept_id=$taxon_concept_id
            AND he.published=1
            AND tc.published=1
            AND tc.supercedure_id=0
            AND he.visibility_id=". Visibility::visible()->id ."
            AND he_children.rank_id IN (". Rank::find_by_translated('label', 'sp.')->id .", ". Rank::find_by_translated('label', 'species')->id .")
            AND tcm.richness_score >= .4";
        $count_of_rich_species = $this->mysqli->select_value($query);

        $number_of_images = $this->mysqli->select_value("SELECT image_total FROM taxon_concept_metrics WHERE taxon_concept_id=$taxon_concept_id");
        $query = "
            SELECT DISTINCT tcm.taxon_concept_id, text_total, video_total, sound_total, flash_total, youtube_total, map_total, tcm.richness_score
            FROM hierarchy_entries he
            JOIN hierarchy_entries_flattened hef on (he.id=hef.ancestor_id)
            JOIN hierarchy_entries he_children on (hef.hierarchy_entry_id=he_children.id)
            JOIN taxon_concepts tc on (he_children.taxon_concept_id=tc.id)
            JOIN hierarchies h on (he_children.hierarchy_id=h.id)
            JOIN taxon_concept_metrics tcm ON (he_children.taxon_concept_id=tcm.taxon_concept_id)
            WHERE he.taxon_concept_id=$taxon_concept_id
            AND he.published=1
            AND tc.published=1
            AND tc.supercedure_id=0
            AND he.visibility_id=". Visibility::visible()->id;
        $text = 0;
        $media = 0;
        $this_richness = 0;
        foreach($this->mysqli->iterate($query) as $row)
        {
            $text += $row['text_total'];
            $media += $row['video_total'];
            $media += $row['sound_total'];
            $media += $row['flash_total'];
            $media += $row['youtube_total'];
            $this_richness = $row['richness_score'];
        }
        return array('NumberRichSpeciesPagesInEOL' => $count_of_rich_species, 'NumberImagesInEOL' => $number_of_images,
            'NumberArticlesInEOL' => $text, 'NumberMediaInEOL' => $media,
            'RichPageOnEOL' => (($this_richness >= .4) ? 'http://eol.org/schema/terms/yes' : 'http://eol.org/schema/terms/no'));
    }

    private function lookup_family($name, $synonyms, $ancestors)
    {
        $order = @$ancestors[12];
        $class = @$ancestors[8];
        $phylum = @$ancestors[4];
        if(!$order && !$class && !$phylum)
        {
            echo "This is a line that doesnt have a order, class or phylum:\n$line_number: $line :: $name\n\n\n";
            exit;
        }

        $synonyms[] = $name;
        $result = $this->mysqli->query("
            (SELECT n.id name_id, h.id hierarchy_id, h.browsable, he.taxon_concept_id, 'valid' match_type
                FROM canonical_forms cf
                JOIN names n ON (cf.id=n.canonical_form_id)
                JOIN hierarchy_entries he ON (n.id=he.name_id)
                JOIN hierarchies h ON (he.hierarchy_id=h.id)
                WHERE cf.string IN ('". implode("','", $synonyms) ."')
                AND he.published=1 AND he.visibility_id=". Visibility::visible()->id .")
            UNION
            (SELECT n.id name_id, h.id hierarchy_id, h.browsable, he.taxon_concept_id, 'synonym' match_type
                FROM canonical_forms cf
                JOIN names n ON (cf.id=n.canonical_form_id)
                JOIN synonyms s ON (n.id=s.name_id AND s.synonym_relation_id=". SynonymRelation::synonym()->id .")
                JOIN hierarchy_entries he ON (s.hierarchy_entry_id=he.id)
                JOIN hierarchies h ON (he.hierarchy_id=h.id)
                WHERE cf.string IN ('". implode("','", $synonyms) ."')
                AND he.published=1 AND he.visibility_id=". Visibility::visible()->id .")");
        if($result  && $result->num_rows)
        {
            return $this->get_best_concept_from_result($result, $name);
        }
    }

    private function get_best_concept_from_result($result, $name)
    {
        $rows = array();
        $counts = array('types' => array('valid' => array(), 'synonym' => array()),
                        'type_in_concepts' => array('valid' => array(), 'synonym' => array()));
        while($result && $row=$result->fetch_assoc())
        {
            $rows[] = $row;
            if(!isset($counts['type_in_concepts'][$row['match_type']][$row['taxon_concept_id']]))
            {
                $counts['type_in_concepts'][$row['match_type']][$row['taxon_concept_id']] = array();
            }
            $counts['type_in_concepts'][$row['match_type']][$row['taxon_concept_id']][] = $row;
            $counts['types'][$row['match_type']][] = $row;
        }
        // it only exists in one concept, and it is a valid name for that concept
        if(count($counts['type_in_concepts']['valid']) == 1 && count($counts['type_in_concepts']['synonym']) == 0)
        {
            $taxon_concept_id = key($counts['type_in_concepts']['valid']);
            return $taxon_concept_id;
        }
        // there are more valid occurences than synonym occurrences
        elseif(count($counts['types']['valid']) >= count($counts['types']['synonym']))
        {
            uasort($counts['type_in_concepts']['valid'], array($this, "sort_valid_matches"));
            $taxon_concept_id = key($counts['type_in_concepts']['valid']);
            return $taxon_concept_id;
        }
        // there are more synonyms matches than valid name matches
        elseif(count($counts['types']['valid']) < count($counts['types']['synonym']))
        {
            $taxon_concept_id = key($counts['type_in_concepts']['synonym']);
            return $taxon_concept_id;
        }
        else
        {
            echo "This name does not match in a way we know how to deal with: $name\n";
            print_r($counts);
            exit;
        }
    }

    private static function sort_valid_matches($a, $b)
    {
        // ascending
        return (count($a) < count($b)) ? 1 : -1;
    }
}

?>
