<?php
namespace php_active_record;

class EOLArchiveObjects
{
    private $mysqli;
    private $mysqli_slave;
    private $content_archive_builder;

    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->output_directory = DOC_ROOT . "temp/eol_archive_objects/";
        recursive_rmdir($this->output_directory);
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->output_directory));
        $this->load_all_ranks();
        $this->load_all_hierarchies();
        $this->load_all_data_types();
        $this->load_all_mime_types();
        $this->load_all_languages();
        $this->load_all_licenses();
        $this->load_all_curated_object_associations();
        $this->load_all_user_object_associations();
        $this->load_all_resources();
        $this->initital_object_associations = array_merge($this->curated_data_objects_taxa, $this->user_data_objects_taxa);
    }

    public function create()
    {
        $this->lookup_taxa();
        $this->lookup_objects();
        $this->archive_builder->finalize(true);
    }

    private function lookup_taxa()
    {
        $start = 0;
        $max_id = 0;
        $limit = 50000;
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM taxon_concepts");
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
    }

    private function lookup_objects()
    {
        $start = 0;
        $max_id = 0;
        $limit = 10000;
        $result = $this->mysqli_slave->query("SELECT MIN(id) as min, MAX(id) as max FROM data_objects");
        if($result && $row=$result->fetch_assoc())
        {
            $start = $row["min"];
            $max_id = $row["max"];
        }
        for($i=$start ; $i<$max_id ; $i+=$limit)
        {
            $this->lookup_resources($i, $limit);
            $this->lookup_object_associations($i, $limit);
            $this->lookup_data_objects($i, $limit);
        }
    }

    private function lookup_resources($start, $limit)
    {
        static $latest_harvest_event_ids = array();
        if(!$latest_harvest_event_ids)
        {
            $query = "SELECT resource_id, MAX(id) FROM harvest_events he GROUP BY resource_id";
            foreach($this->mysqli_slave->iterate_file($query) as $row)
            {
                $latest_harvest_event_ids[] = $row[1];
            }
        }
        $query = "SELECT dohe.data_object_id, he.resource_id
            FROM data_objects_harvest_events dohe
            JOIN harvest_events he ON (dohe.harvest_event_id=he.id)
            WHERE dohe.harvest_event_id IN (". implode(",", $latest_harvest_event_ids) .")
            AND dohe.data_object_id BETWEEN $start AND ". ($start+$limit-1);
        $this->data_objects_resources = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $this->data_objects_resources[$row[0]] = $row[1];
        }
    }

    private function lookup_object_associations($start, $limit)
    {
        debug("querying lookup_data_objects_taxa");
        $query = "SELECT do.id, he.taxon_concept_id
            FROM data_objects do
            JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id)
            WHERE do.published = 1
            AND dohe.vetted_id IN (". implode(",", array(Vetted::trusted()->id, Vetted::unknown()->id)) .")
            AND dohe.visibility_id = ".Visibility::visible()->id ."
            AND do.id BETWEEN $start AND ". ($start+$limit-1);
        $this->data_objects_taxa = $this->initital_object_associations;
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $this->data_objects_taxa[$row[0]][$row[1]] = true;
        }
    }

    private function lookup_data_objects($start, $limit)
    {
        debug("querying lookup_data_objects");
        $query = "SELECT do.id, do.guid, do.identifier, do.data_type_id, do.data_subtype_id, do.mime_type_id, do.object_title, do.language_id,
            do.license_id, do.rights_statement, do.rights_holder, do.bibliographic_citation, do.source_url, do.description, do.object_url,
            do.thumbnail_url, do.location, do.latitude, do.longitude, do.altitude, do.derived_from, do.spatial_location, ii.schema_value,
            do.data_rating
            FROM data_objects do
            LEFT JOIN
                (data_objects_info_items doii JOIN info_items ii ON (doii.info_item_id=ii.id))
                ON (do.id=doii.data_object_id)
            WHERE do.published = 1
            AND do.id BETWEEN $start AND ". ($start+$limit-1);
        $this->data_object_ids_seen = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            if(count($row) != 24)
            {
                print_r($row);
                echo "this result was incomplete\n";
                continue;
            }

            $m = new \eol_schema\MediaResource();
            $data_object_id = self::clean_mysql_result($row[0]);
            if(isset($this->data_object_ids_seen[$data_object_id])) continue;
            $this->data_object_ids_seen[$data_object_id] = true;

            if($data_object_id) $m->identifier = $data_object_id;
            else continue;

            if(isset($this->data_objects_taxa[$data_object_id]))
            {
                $m->taxonID = implode(";", array_keys($this->data_objects_taxa[$data_object_id]));
            }else continue;
            if(isset($this->data_objects_resources[$data_object_id]) && isset($this->all_resources[$this->data_objects_resources[$data_object_id]]))
            {
                $m->contributor = $this->all_resources[$this->data_objects_resources[$data_object_id]];
            }else continue;
            
            if($v = self::clean_mysql_result($row[3])) $m->type = @$this->all_data_types[$v];
            if(!$m->type || $m->type == "Link") continue;
            if($m->type == "http://purl.org/dc/dcmitype/Text")
            {
                if($v = self::clean_mysql_result($row[22])) $m->CVterm = $v;
                else continue;
            }
            if($v = self::clean_mysql_result($row[4])) $m->subtype = @$this->all_data_types[$v];
            if($v = self::clean_mysql_result($row[5])) $m->format = @$this->all_mime_types[$v];
            if($v = self::clean_mysql_result($row[6])) $m->title = $v;
            if($v = self::clean_mysql_result($row[7])) $m->language = @$this->all_languages[$v];
            if($v = self::clean_mysql_result($row[8])) $m->UsageTerms = @$this->all_licenses[$v];
            if($v = self::clean_mysql_result($row[9])) $m->rights = $v;
            if($v = self::clean_mysql_result($row[10])) $m->Owner = $v;
            if($v = self::clean_mysql_result($row[11])) $m->bibliographicCitation = $v;
            if($v = self::clean_mysql_result($row[12])) $m->furtherInformationURL = $v;
            if($v = self::clean_mysql_result($row[13])) $m->description = $v;
            if($v = self::clean_mysql_result($row[14])) $m->accessURI = $v;
            if($v = self::clean_mysql_result($row[15])) $m->thumbnailURL = $v;
            if($v = self::clean_mysql_result($row[16])) $m->LocationCreated = $v;
            if($v = self::clean_mysql_result($row[17])) $m->lat = $v;
            if($v = self::clean_mysql_result($row[18])) $m->long = $v;
            if($v = self::clean_mysql_result($row[19])) $m->alt = $v;
            if($v = self::clean_mysql_result($row[20])) $m->derivedFrom = $v;
            if($v = self::clean_mysql_result($row[21])) $m->spatial = $v;
            if($v = self::clean_mysql_result($row[23])) $m->Rating = $v;
            $this->archive_builder->write_object_to_file($m);
        }
    }

    private function lookup_ranks($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying lookup_ranks");
        $query = "SELECT he.taxon_concept_id, he.rank_id
            FROM hierarchy_entries he
            WHERE he.published = 1
            AND he.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit-1);

        $this->best_rank_ids = array();
        $this->all_rank_ids = array();
        $this->ranks = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $taxon_concept_id = $row[0];
            $rank_id = $row[1];
            if($rank_id == 'NULL') $rank_id = NULL;
            if($rank_id)
            {
                if(!isset($this->all_rank_ids[$taxon_concept_id][$rank_id])) $this->all_rank_ids[$taxon_concept_id][$rank_id] = 1;
                else $this->all_rank_ids[$taxon_concept_id][$rank_id] += 1;
            }
        }
        $this->sort_ranks();
    }

    private function lookup_hierarchies($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying lookup_hierarchies");
        $query = "SELECT he.taxon_concept_id, h.id, h.label, he_parent.taxon_concept_id
            FROM hierarchy_entries he
            JOIN hierarchies h ON (he.hierarchy_id=h.id)
            LEFT JOIN hierarchy_entries he_parent ON (he.parent_id=he_parent.id)
            WHERE he.published = 1
            AND he.taxon_concept_id ";
        if($taxon_concept_ids) $query .= "IN (". implode(",", $taxon_concept_ids) .")";
        else $query .= "BETWEEN $start AND ". ($start+$limit-1);

        $this->hierarchies = array();
        $this->parents = array();
        foreach($this->mysqli_slave->iterate_file($query) as $row)
        {
            $taxon_concept_id = $row[0];
            $hierarchy_id = $row[1];
            $hierarchy_label = $row[2];
            $parent_taxon_concept_id = $row[3];
            if($parent_taxon_concept_id == 'NULL') $parent_taxon_concept_id = NULL;
            if($hierarchy_label)
            {
                $this->hierarchies[$taxon_concept_id][$hierarchy_label] = true;
            }
            if($parent_taxon_concept_id && $parent_taxon_concept_id != $taxon_concept_id)
            {
                $this->parents[$taxon_concept_id][$parent_taxon_concept_id] = true;
            }
        }
    }

    private function lookup_names($start, $limit, &$taxon_concept_ids = array())
    {
        debug("querying lookup_names");
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
        else $query .= "BETWEEN $start AND ". ($start+$limit-1);

        static $i = 0;
        foreach($this->mysqli_slave->iterate_file($query) as $row)
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
            // if(@!$this->ranks[$taxon_concept_id]) continue;
            // if($hierarchies = @$this->hierarchies[$taxon_concept_id])
            // {
            //     $t->nameAccordingTo = implode("; ", array_keys($hierarchies));
            // }
            if($parents = @$this->parents[$taxon_concept_id])
            {
                $t->parentNameUsageID = implode("; ", array_keys($parents));
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

    private function load_all_ranks()
    {
        $this->all_ranks = array();
        $query = "SELECT r.id, tr.label
            FROM ranks r
            JOIN translated_ranks tr ON (r.id=tr.rank_id)
            WHERE tr.language_id=". Language::english()->id;
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            $this->all_ranks[$row['id']] = strtolower($row['label']);
        }

        $this->linnaean_rank_ids = array();
        $query = "SELECT r.id, tr.label
            FROM ranks r
            JOIN translated_ranks tr ON (r.id=tr.rank_id)
            WHERE tr.language_id=". Language::english()->id ."
            AND tr.label IN ('kingdom', 'phylum', 'class', 'order', 'family', 'genus', 'gen', 'gen.', 'species', 'sp', 'sp.')";
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            $this->linnaean_rank_ids[] = $row['id'];
        }
    }

    private function load_all_hierarchies()
    {
        $this->all_hierarchies = array();
        $query = "SELECT id, label FROM hierarchies";
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            if($label = trim($row['label']))
            {
                $this->all_hierarchies[$row['id']] = $label;
            }
        }
    }

    private function load_all_data_types()
    {
        $this->all_data_types = array();
        $query = "SELECT id, schema_value FROM data_types";
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            if($data_type = trim($row['schema_value']))
            {
                if($data_type == "Flash") $data_type = "http://purl.org/dc/dcmitype/MovingImage";
                elseif($data_type == "YouTube") $data_type = "http://purl.org/dc/dcmitype/MovingImage";
                elseif($data_type == "IUCN") $data_type = "http://purl.org/dc/dcmitype/Text";
                $this->all_data_types[$row['id']] = $data_type;
            }
        }
    }

    private function load_all_mime_types()
    {
        $this->all_mime_types = array();
        $query = "SELECT mt.id, tr.label FROM mime_types mt JOIN translated_mime_types tr ON (mt.id=tr.mime_type_id) WHERE tr.language_id=". Language::english()->id;
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            if($mime_type = trim($row['label']))
            {
                $this->all_mime_types[$row['id']] = $mime_type;
            }
        }
    }

    private function load_all_languages()
    {
        $this->all_languages = array();
        $query = "SELECT l.id, l.iso_639_1, tr.label FROM languages l JOIN translated_languages tr ON (l.id=tr.original_language_id) WHERE tr.language_id=". Language::english()->id;
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            if($iso = trim($row['iso_639_1']))
            {
                $this->all_languages[$row['id']] = $iso;
            }elseif($label = trim($row['label']))
            {
                $this->all_languages[$row['id']] = $label;
            }
        }
    }

    private function load_all_licenses()
    {
        $this->all_licenses = array();
        $query = "SELECT id, title, source_url FROM licenses";
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            if($source_url = trim($row['source_url']))
            {
                $this->all_licenses[$row['id']] = $source_url;
            }elseif($title = trim($row['title']))
            {
                $this->all_licenses[$row['id']] = $title;
            }
        }
    }

    private function load_all_curated_object_associations()
    {
        $this->curated_data_objects_taxa = array();
        $query = "SELECT do.id, he.taxon_concept_id
            FROM data_objects do
            JOIN curated_data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id)
            JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id)
            WHERE do.published = 1
            AND dohe.vetted_id IN (". implode(",", array(Vetted::trusted()->id, Vetted::unknown()->id)) .")
            AND dohe.visibility_id = ".Visibility::visible()->id;
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            $this->curated_data_objects_taxa[$row['id']][$row['taxon_concept_id']] = true;
        }
    }

    private function load_all_user_object_associations()
    {
        $this->user_data_objects_taxa = array();
        $query = "SELECT do.id, udo.taxon_concept_id
            FROM data_objects do
            JOIN users_data_objects udo ON (do.id=udo.data_object_id)
            WHERE do.published = 1
            AND udo.vetted_id IN (". implode(",", array(Vetted::trusted()->id, Vetted::unknown()->id)) .")
            AND udo.visibility_id = ".Visibility::visible()->id;
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            $this->user_data_objects_taxa[$row['id']][$row['taxon_concept_id']] = true;
        }
    }

    private function load_all_resources()
    {
        $this->all_resources = array();
        $query = "SELECT r.id, r.title, r.content_partner_id, cp.full_name
            FROM resources r
            JOIN content_partners cp ON (r.content_partner_id = cp.id)
            WHERE r.content_partner_id NOT IN (2, 6, 13, 14, 23, 165, 366, 166, 67, 133, 82, 30)";
        foreach($this->mysqli_slave->iterate($query) as $row)
        {
            $label = $row['full_name'];
            if(strtolower($row['full_name']) != strtolower($row['title'])) $label .= " in ". $row['title'];
            $this->all_resources[$row['id']] = $label;
        }
    }

    private function sort_ranks()
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

    private static function clean_mysql_result($text)
    {
        if($text == 'NULL') return NULL;
        return $text;
    }
}

?>
