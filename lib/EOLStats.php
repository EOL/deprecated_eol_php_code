<?php
namespace php_active_record;
class EOLStats
{
    private $mysqli;
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        $this->published_id = TranslatedResourceStatus::find_or_create_by_label('Published')->id;
        $this->trusted_id   = Vetted::trusted()->id;
        $this->unknown_id   = Vetted::unknown()->id;
        $this->untrusted_id = Vetted::untrusted()->id;
        $this->visible_id   = Visibility::visible()->id;
        $this->invisible_id = Visibility::invisible()->id;
        $this->master_curator_id = CuratorLevel::master_curator()->id;
        $this->full_curator_id = CuratorLevel::full_curator()->id;
        $this->assistant_curator_id = CuratorLevel::assistant_curator()->id;
        $this->curator_ids = CuratorLevel::curator_ids();
        $this->data_object_scope = ChangeableObjectType::data_object_scope();
        $this->worms_content_partner_id = ContentPartner::find_or_create_by_full_name('World Register of Marine Species')->id;
        $this->col_hierarchy_id = Hierarchy::find_or_create_by_label('Species 2000 & ITIS Catalogue of Life: Annual Checklist 2011')->id;
        $this->latest_harvest_event_ids();
        $this->worms_latest_harvest_event_id();
    }

    public function save_eol_stats()
    {
        $stats = array();
        //Overall Statistics
        $time_start = time_elapsed();
        $stats['members_count'] = $this->members_count(); //Number of members
        $stats['communities_count'] = $this->communities_count(); //Number of communities
        $stats['collections_count'] = $this->collections_count(); //Number of collections
        $stats['pages_count'] = $this->pages_count(); //Total number of pages
        $stats['pages_with_content'] = $this->pages_with_content();//as currently reported on home page; assume this means pages with at least one data object
        $stats['pages_with_text'] = $this->pages_with_text();
        $stats['pages_with_image'] = $this->pages_with_image();
        $stats['pages_with_map'] = $this->pages_with_map();
        $stats['pages_with_video'] = $this->pages_with_video();
        $stats['pages_with_sound'] = $this->pages_with_sound();
        $stats['pages_without_text'] = $stats['pages_count'] - $this->pages_with_text();
        $stats['pages_without_image'] = $stats['pages_count'] - $stats['pages_with_image'];
        $stats['pages_with_image_no_text'] = $this->pages_with_image_no_text();
        $stats['pages_with_text_no_image'] = $this->pages_with_text_no_image();
        $stats['base_pages'] = $this->pages_without_content_with_other_info();//base pages - pages without any data objects; base pages may have references and BHL/content partner links
        print "\n Overall stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //Trusted Content Statistics - note change in terminology, phasing out vetted in favor of trusted
        $time_start = time_elapsed();
        $stats['pages_with_at_least_a_trusted_object'] = $this->pages_with_at_least_a_trusted_object(); // Number of pages with at least one trusted data object 
        $stats['pages_with_at_least_a_curatorial_action'] = $this->pages_curated(); //***
        print "\n Trusted content stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //BHL Statistics 
        $time_start = time_elapsed();
        $stats['pages_with_BHL_links'] = $this->pages_with_BHL_links();
        $stats['pages_with_BHL_links_no_text'] = $this->pages_with_BHL_links_no_text();
        $stats['pages_with_BHL_links_only'] = $this->pages_with_BHL_links_only();
        print "\n BHL stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //Content Partner Statistics 
        $time_start = time_elapsed();
        $stats['content_partners'] = $this->content_partners(); //Number of publicly listed partners - as shown on home page (This includes all published partners and a few partners that have been listed although they are not yet sharing content, e.g., some international partners)
        $stats['content_partners_with_published_resources'] = $this->content_partners_with_published_resources(); //Number of partners with published resources
        $stats['content_partners_with_published_trusted_resources'] = $this->content_partners_with_published_resources(1); //Number of partners with published trusted resources
        $stats['published_resources']            = $this->published_resources();  //Total number of published resources
        $stats['published_trusted_resources']    = $this->published_resources("1"); //Number of published trusted resources
        $stats['published_unreviewed_resources'] = $this->published_resources("0"); //Number of published unreviewed resources
        $stats['newly_published_resources_in_the_last_30_days'] = $this->published_resources_in_the_last_n_days(30); //Number of resources published for the first time in the last 30 days
        print "\n Content partner stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //Page Richness Statistics 
        $time_start = time_elapsed();
        $stats['rich_pages'] = $this->rich_pages(); // % of all pages (total number of taxon concepts) that are rich - with a score of 40 or more
        $hotlist = self::get_hotlist();
        $stats['hotlist_pages'] = count($hotlist);
        $stats['rich_hotlist_pages'] = $this->get_rich_pages($hotlist); // % pages on the hotlist that are rich - The official version of the hotlist (names & EOL ids) is now maintained here:
        $collection_ids = array(10675, 10667);
        $redhotlist = self::get_redhotlist($collection_ids);
        $stats['redhotlist_pages'] = count($redhotlist);
        $stats['rich_redhotlist_pages'] = $this->get_rich_pages($redhotlist); // % pages on the redhotlist that are rich - The official version of the redhotlist is the combined list of taxa of these two collections:
        $stats['pages_with_score_10_to_39'] = $this->not_so_rich_pages(); // % of all pages that are not rich but have at least some content (score 10-39)
        $stats['pages_with_score_less_than_10'] = $this->not_rich_pages(); // % of all pages that are base-like pages (score <10)        
        print "\n Page richness stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //Curatorial Stats
        $time_start = time_elapsed();
        $this->data_object_curation_activity_ids();
        $this->name_curation_activity_ids();
        $this->curation_activity_ids();
        $stats['curators']            = $this->curators();                            // Number of registered curators
        $stats['curators_assistant']  = $this->curators($this->assistant_curator_id); // Number of registered assistant curators
        $stats['curators_full']       = $this->curators($this->full_curator_id);      // Number of registered full curators
        $stats['curators_master']     = $this->curators($this->master_curator_id);    // Number of registered master curators
        $stats['active_curators']     = count($this->curators_active()); //COMPLETE
        $stats['pages_curated_by_active_curators']    = $this->pages_curated($this->curators_active); // number of pages curated by active curators 
        $stats['objects_curated_in_the_last_30_days'] = $this->objects_curated_in_the_last_n_days(30); //COMPLETE
        $stats['curator_actions_in_the_last_30_days'] = $this->curator_actions_in_the_last_n_days(30); //COMPLETE
        print "\n Curatorial Stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //LifeDesk stats
        $time_start = time_elapsed();
        $stats['lifedesk_taxa'] = $this->lifedesk_taxa();
        $stats['lifedesk_data_objects'] = $this->lifedesk_data_objects();
        print "\n LifeDesk stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //Marine stats
        $time_start = time_elapsed();
        if(self::get_marine_stats())
        {
            $stats['marine_pages'] = $this->marine_pages();
            $stats['marine_pages_in_col'] = $this->marine_pages_in_col();
            $stats['marine_pages_with_objects'] = $this->marine_pages_with_objects();
            $stats['marine_pages_with_objects_vetted'] = $this->marine_pages_with_objects($this->trusted_id);
        }
        else
        {
            print "\n No need to compute Marine stats now as there is no new WORMS harvest yet \n";
            $marine_stats = self::get_values_from_last_recorded_marine_stats();
            $stats['marine_pages'] = $marine_stats['marine_pages'];
            $stats['marine_pages_in_col'] = $marine_stats['marine_pages_in_col'];
            $stats['marine_pages_with_objects'] = $marine_stats['marine_pages_with_objects'];
            $stats['marine_pages_with_objects_vetted'] = $marine_stats['marine_pages_with_objects_vetted'];
        }
        print "\n Marine stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //User-submitted text
        $time_start = time_elapsed();
        $stats['udo_published'] = $this->udo_published(); // Number of user submitted text (published)
        $stats['udo_published_by_curators'] = $this->udo_published_by_curators(); // Number of text objects added by curators - assistant, full, or master curators
        $stats['udo_published_by_non_curators'] = $stats['udo_published'] - $stats['udo_published_by_curators']; // Number of text objects added by non-curators
        print "\n UDO stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        //Data Object Statistics 
        $time_start = time_elapsed();
        $stats['data_objects'] = $this->count_data_objects();
        $stats['data_objects_texts']  = $this->count_data_objects(array(DataType::text()->id));
        $stats['data_objects_images'] = $this->count_data_objects(array(DataType::image()->id));
        $stats['data_objects_videos'] = $this->count_data_objects(array(DataType::video()->id, DataType::flash()->id, DataType::youtube()->id));
        $stats['data_objects_sounds'] = $this->count_data_objects(array(DataType::sound()->id));
        $stats['data_objects_maps']   = $this->count_data_objects(array(DataType::map()->id));
        $stats['data_objects_trusted']     = count($this->count_data_objects_vettedness_list($this->trusted_id));
        $stats['data_objects_unreviewed']  = count($this->count_data_objects_vettedness_list($this->unknown_id));
        $stats['data_objects_untrusted']   = count($this->count_data_objects_vettedness_list($this->untrusted_id));
        $stats['data_objects_trusted_or_unreviewed_but_hidden'] = count($this->data_objects_trusted_or_unreviewed_but_hidden_list());
        print "\n Data object stats: " . (time_elapsed()-$time_start)/60 . " minutes";

        $stats['created_at'] = date('Y-m-d H:i:s');
        $this->mysqli_slave->insert("INSERT INTO eol_statistics (".implode(array_keys($stats), ",").") VALUES ('".implode($stats, "','")."')");
        print_r($stats);
    }

    public function get_marine_stats()
    {
        $result = $this->mysqli_slave->query("SELECT max(he.id) id, max(he.published_at) published_at FROM resources r JOIN harvest_events he ON r.id = he.resource_id JOIN content_partners cp ON r.content_partner_id = cp.id WHERE he.published_at IS NOT NULL AND cp.id = " . $this->worms_content_partner_id);
        if($result && $row=$result->fetch_assoc()) 
        {
            $latest_harvest_event_id = $row['id'];
            $latest_harvest = $row['published_at'];
        }
        $result = $this->mysqli_slave->query("SELECT e.created_at FROM eol_statistics e ORDER BY e.id DESC LIMIT 1");
        if($result && $row=$result->fetch_assoc()) $latest_stats = $row['created_at'];
        else return true;
        $date = new \DateTime($latest_harvest); $latest_harvest = $date->format('Y-m-d');
        $date = new \DateTime($latest_stats);   $latest_stats = $date->format('Y-m-d');
        print "\n\n $latest_stats -- $latest_harvest";
        if($latest_harvest >= $latest_stats) return true; // Calculate marine stats
        else return false; // No need to calculate marine stats
    }

    function get_values_from_last_recorded_marine_stats()
    {
        $marine_stats = array();
        $result = $this->mysqli_slave->query("SELECT e.marine_pages, marine_pages_in_col, marine_pages_with_objects, marine_pages_with_objects_vetted FROM eol_statistics e ORDER BY e.id DESC LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $marine_stats['marine_pages']                     = $row['marine_pages'];
            $marine_stats['marine_pages_in_col']              = $row['marine_pages_in_col'];
            $marine_stats['marine_pages_with_objects']        = $row['marine_pages_with_objects'];
            $marine_stats['marine_pages_with_objects_vetted'] = $row['marine_pages_with_objects_vetted'];
        }
        return $marine_stats;
    }

    public function marine_pages()
    {
        $result = $this->mysqli_slave->query("SELECT count(distinct he.taxon_concept_id) count FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he on (hehe.hierarchy_entry_id=he.id) WHERE hehe.harvest_event_id=" . $this->worms_latest_harvest_event_id);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function marine_pages_in_col()
    {
        $result = $this->mysqli_slave->query("SELECT count(distinct he.taxon_concept_id) count FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he on (hehe.hierarchy_entry_id=he.id) JOIN names n ON (he.name_id=n.id) JOIN taxon_concept_names tcn ON (n.id=tcn.name_id) JOIN taxon_concepts tc ON (tcn.taxon_concept_id=tc.id) JOIN hierarchy_entries he_col ON (tc.id=he_col.taxon_concept_id AND he_col.hierarchy_id=" . $this->col_hierarchy_id . ") WHERE hehe.harvest_event_id=" . $this->worms_latest_harvest_event_id . " AND tc.published=1 AND tc.supercedure_id=0 AND tc.vetted_id=" . $this->trusted_id);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function marine_pages_with_objects($vetted_id = null)
    {
        $sql = "SELECT count(distinct he.taxon_concept_id) count FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he on (hehe.hierarchy_entry_id=he.id) JOIN taxon_concept_metrics tcm on (he.taxon_concept_id=tcm.taxon_concept_id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) WHERE hehe.harvest_event_id=" . $this->worms_latest_harvest_event_id . " AND tc.published = 1 AND tc.supercedure_id = 0 AND tc.vetted_id=" . $this->trusted_id;
        if($vetted_id == $this->trusted_id) $sql .= " AND (text_trusted > 0 OR image_trusted > 0 OR video_trusted > 0 OR sound_trusted > 0 OR youtube_trusted > 0 OR flash_trusted > 0 OR map_trusted > 0)";
        else                                $sql .= " AND (text_total   > 0 OR image_total   > 0 OR video_total   > 0 OR sound_total   > 0 OR youtube_total   > 0 OR flash_total   > 0 OR map_total   > 0)";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function worms_latest_harvest_event_id()
    {
        if(isset($this->worms_latest_harvest_event_id)) return $this->worms_latest_harvest_event_id;
        $this->worms_latest_harvest_event_id = 0;
        $result = $this->mysqli_slave->query("SELECT max(he.id) id FROM resources r JOIN harvest_events he ON r.id = he.resource_id JOIN content_partners cp ON r.content_partner_id = cp.id WHERE he.published_at IS NOT NULL AND cp.id = " . $this->worms_content_partner_id);
        if($result && $row=$result->fetch_assoc()) $this->worms_latest_harvest_event_id = $row['id'];
        return $this->worms_latest_harvest_event_id;
    }

    public function lifedesk_taxa()
    {
        if($latest_published_lifedesk_resources = $this->latest_published_lifedesk_resources())
        {
            $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(he.taxon_concept_id)) count FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) WHERE hehe.harvest_event_id IN (".implode($latest_published_lifedesk_resources, ",").")");
            if($result && $row=$result->fetch_assoc()) $lifedesk_taxa = $row['count'];
            return $lifedesk_taxa;
        }
        else return 0;
    }

    public function lifedesk_data_objects()
    {
        if($latest_published_lifedesk_resources = $this->latest_published_lifedesk_resources())
        {
            $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(do.id)) count FROM data_objects_harvest_events dohe JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE dohe.harvest_event_id IN (".implode($latest_published_lifedesk_resources, ",").") AND do.published=1");
            if($result && $row=$result->fetch_assoc()) $lifedesk_data_objects = $row['count'];
            return $lifedesk_data_objects;
        }
        else return 0;
    }

    public function latest_published_lifedesk_resources()
    {
        if(isset($this->latest_published_lifedesk_resources)) return $this->latest_published_lifedesk_resources;
        $this->latest_published_lifedesk_resources = array();
        $resource_ids = array();
        $result = $this->mysqli_slave->query("SELECT r.id, max(he.id) max FROM resources r JOIN harvest_events he  ON (r.id=he.resource_id) WHERE r.accesspoint_url LIKE '%lifedesks.org%' AND he.published_at IS NOT NULL GROUP BY r.id");
        while($result && $row=$result->fetch_assoc()) $this->latest_published_lifedesk_resources[] = $row['max'];
        return $this->latest_published_lifedesk_resources;
    }

    public function count_data_objects_vettedness($vetted_id)
    {
        $filter = " WHERE do.published = 1 AND o.vetted_id = $vetted_id";
        $sql_cdohe = "SELECT COUNT(*) total FROM data_objects do JOIN curated_data_objects_hierarchy_entries o ON do.id = o.data_object_id $filter "; 
        $sql_dohe = "SELECT COUNT(*) total FROM data_objects do JOIN data_objects_hierarchy_entries          o ON do.id = o.data_object_id $filter ";
        $sql_udo = "SELECT COUNT(*) total FROM data_objects do JOIN users_data_objects                       o ON do.id = o.data_object_id $filter ";
        $sql = "SELECT sum(total) count FROM ( $sql_dohe UNION $sql_cdohe UNION $sql_udo ) sum ";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function count_data_objects_vettedness_list($vetted_id)
    {
        $data_object_ids = array();
        $filter = " WHERE do.published = 1 AND o.vetted_id = $vetted_id ";
        if($vetted_id != $this->untrusted_id) $filter .= " AND o.visibility_id = " . $this->visible_id;
        $sql_cdohe = "SELECT do.id  FROM data_objects do JOIN curated_data_objects_hierarchy_entries o ON do.id = o.data_object_id $filter "; 
        $sql_dohe = "SELECT do.id  FROM data_objects do JOIN data_objects_hierarchy_entries          o ON do.id = o.data_object_id $filter ";
        $sql_udo = "SELECT do.id  FROM data_objects do JOIN users_data_objects                       o ON do.id = o.data_object_id $filter ";
        $sql = "$sql_dohe UNION $sql_cdohe UNION $sql_udo";
        $result = $this->mysqli_slave->query($sql);
        while($result && $row=$result->fetch_assoc()) $data_object_ids[] = $row['id'];
        return array_unique($data_object_ids);
    }

    public function data_objects_trusted_or_unreviewed_but_hidden()
    {
        $trusted = $this->trusted_id;
        $unreviewed = $this->unknown_id;
        $hidden = $this->invisible_id;
        $filter = " WHERE do.published = 1 AND (o.vetted_id = $trusted OR o.vetted_id = $unreviewed) AND o.visibility_id = $hidden";
        $sql_cdohe = "SELECT COUNT(*) total  FROM data_objects do JOIN curated_data_objects_hierarchy_entries o ON do.id = o.data_object_id $filter ";
        $sql_dohe = "SELECT COUNT(*) total  FROM data_objects do JOIN data_objects_hierarchy_entries          o ON do.id = o.data_object_id $filter ";
        $sql_udo = "SELECT COUNT(*) total  FROM data_objects do JOIN users_data_objects                       o ON do.id = o.data_object_id $filter ";
        $sql = "SELECT sum(total) count FROM ( $sql_dohe UNION $sql_cdohe UNION $sql_udo ) sum ";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function data_objects_trusted_or_unreviewed_but_hidden_list()
    {
        $trusted = $this->trusted_id;
        $unreviewed = $this->unknown_id;
        $hidden = $this->invisible_id;
        $filter = " WHERE do.published = 1 AND (o.vetted_id = $trusted OR o.vetted_id = $unreviewed) AND o.visibility_id = $hidden";
        $sql_cdohe = "SELECT do.id  FROM data_objects do JOIN curated_data_objects_hierarchy_entries o ON do.id = o.data_object_id $filter ";
        $sql_dohe = "SELECT do.id  FROM data_objects do JOIN data_objects_hierarchy_entries          o ON do.id = o.data_object_id $filter ";
        $sql_udo = "SELECT do.id  FROM data_objects do JOIN users_data_objects                       o ON do.id = o.data_object_id $filter ";
        $sql = "$sql_dohe UNION $sql_cdohe UNION $sql_udo";
        $result = $this->mysqli_slave->query($sql);
        while($result && $row=$result->fetch_assoc()) $data_object_ids[] = $row['id'];
        return array_unique($data_object_ids);
    }

    public function count_data_objects($data_type_id = null)
    {
        //JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id) 
        $sql = "SELECT COUNT(distinct do.guid) count FROM data_objects do JOIN data_objects_hierarchy_entries dohe ON (do.id=dohe.data_object_id) WHERE do.published=1 AND dohe.visibility_id=" . $this->visible_id . " AND dohe.vetted_id!=" . $this->untrusted_id;
        if($data_type_id[0] != DataType::map()->id)
        {
            if($data_type_id) $sql .= " AND do.data_type_id IN (" . implode(",", $data_type_id) . ") ";
            if($data_type_id[0] == DataType::image()->id) $sql .= " AND do.data_subtype_id IS NULL";
        }
        else $sql .= " AND do.data_subtype_id = " . DataType::map()->id;
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function curation_activity_ids()
    {
        if(isset($this->curation_activity_ids)) return $this->curation_activity_ids;
        $this->curation_activity_ids = array();
        $this->curation_activity_ids = array_merge($this->name_curation_activity_ids, $this->data_object_curation_activity_ids);
        return $this->curation_activity_ids;
    }

    public function name_curation_activity_ids()
    {
        if(isset($this->name_curation_activity_ids)) return $this->name_curation_activity_ids;
        $this->name_curation_activity_ids = array();
        $result = $this->mysqli_slave->query("SELECT t.activity_id from eol_logging_version2.translated_activities t WHERE t.name in ('add_common_name', 'remove_common_name', 'trust_common_name', 'untrust_common_name', 'inappropriate_common_name', 'unreview_common_name', 'updated_common_names')");
        while($result && $row=$result->fetch_assoc()) $this->name_curation_activity_ids[] = $row['activity_id'];
        return $this->name_curation_activity_ids;
    }

    public function data_object_curation_activity_ids()
    {
        if(isset($this->data_object_curation_activity_ids)) return $this->data_object_curation_activity_ids;
        $this->data_object_curation_activity_ids = array();
        $result = $this->mysqli_slave->query("SELECT t.activity_id from eol_logging_version2.translated_activities t WHERE t.name in ('trusted', 'untrusted', 'unreviewed', 'show', 'hide', 'inappropriate', 'add_association', 'remove_association', 'choose_exemplar', 'rate')");
        while($result && $row=$result->fetch_assoc()) $this->data_object_curation_activity_ids[] = $row['activity_id'];
        return $this->data_object_curation_activity_ids;
    }

    public function pages_curated($curators = null)
    {
        return count(array_values(array_unique(array_merge($this->pages_curated_thru_data_objects($curators),
                                                           $this->pages_curated_thru_common_names($curators),
                                                           $this->pages_comments_curated_thru_TaxonConcept($curators),
                                                           $this->pages_comments_curated_thru_DataObject($curators)))));
    }

    public function pages_comments_curated_thru_TaxonConcept($curators)
    {
        $sql = "SELECT DISTINCT c.parent_id taxon_concept_id FROM eol_logging_version2.curator_activity_logs cal JOIN comments c ON cal.object_id = c.id WHERE cal.changeable_object_type_id = " . ChangeableObjectType::comment()->id . " AND c.parent_type = 'TaxonConcept' ";
        if($curators) $sql .= " AND cal.user_id IN (" . implode(",", $curators) . ")";
        $result = $this->mysqli_slave->query($sql);
        $pages_comments_curated_thru_TaxonConcept = array();
        while($result && $row=$result->fetch_assoc()) $pages_comments_curated_thru_TaxonConcept[] = $row['taxon_concept_id'];
        return $pages_comments_curated_thru_TaxonConcept;
    }

    public function pages_comments_curated_thru_DataObject($curators)
    {
        return array_values(array_unique(array_merge($this->pages_comments_curated_thru_data_objects('UDO', $curators),
                                                     $this->pages_comments_curated_thru_data_objects('DOHE', $curators),
                                                     $this->pages_comments_curated_thru_data_objects('CDOHE', $curators))));
    }

    public function pages_curated_thru_data_objects($curators)
    {
        $pages_curated_thru_data_objects = array();
        $sql = "SELECT DISTINCT dotc.taxon_concept_id FROM eol_logging_version2.curator_activity_logs cal JOIN eol_logging_version2.activities acts ON (cal.activity_id = acts.id) JOIN data_objects_taxon_concepts dotc ON (cal.object_id = dotc.data_object_id) WHERE cal.changeable_object_type_id IN (" . implode(",", $this->data_object_scope) . ")";
        if($curators) $sql .= " AND cal.user_id IN (" . implode(",", $curators) . ")";
        $result = $this->mysqli_slave->query($sql);
        while($result && $row=$result->fetch_assoc()) $pages_curated_thru_data_objects[] = $row['taxon_concept_id'];
        return $pages_curated_thru_data_objects;
    }

    public function pages_curated_thru_common_names($curators)
    {
        $pages_curated_thru_common_names = array();
        $sql = "SELECT DISTINCT cal.taxon_concept_id FROM eol_logging_version2.curator_activity_logs cal JOIN eol_logging_version2.activities acts ON (cal.activity_id = acts.id) WHERE cal.changeable_object_type_id = " . ChangeableObjectType::synonym()->id;
        if($curators) $sql .= " AND cal.user_id IN (" . implode(",", $curators) . ")";
        $result = $this->mysqli_slave->query($sql);
        while($result && $row=$result->fetch_assoc()) $pages_curated_thru_common_names[] = $row['taxon_concept_id'];
        return $pages_curated_thru_common_names;
    }

    public function curators_active()
    {
        if(isset($this->curators_active)) return $this->curators_active;
        $this->curators_active = array();        
        $sql = "SELECT DISTINCT(cal.user_id) FROM eol_logging_version2.curator_activity_logs cal WHERE cal.created_at > date_sub(now(), interval 1 year)
            AND
            ( cal.activity_id IN (" . implode(",", $this->curation_activity_ids) . ") OR
              cal.changeable_object_type_id = " . ChangeableObjectType::comment()->id . "
            )
            UNION
            SELECT DISTINCT udo.user_id FROM users_data_objects udo WHERE udo.created_at > date_sub(now(), interval 1 year)
            UNION
            SELECT DISTINCT w.user_id FROM wikipedia_queue w WHERE w.created_at > date_sub(now(), interval 1 year) ";
        $result = $this->mysqli_slave->query($sql);
        while($result && $row=$result->fetch_assoc()) $this->curators_active[] = $row['user_id'];
        return $this->curators_active;
    }

    public function objects_curated_in_the_last_n_days($days)
    {
        $sql = "SELECT COUNT(*) count FROM
          (
            SELECT DISTINCT(cal.object_id) FROM eol_logging_version2.curator_activity_logs cal WHERE cal.activity_id IN (" . implode(",", $this->data_object_curation_activity_ids) . ") AND cal.created_at > date_sub(now(), interval $days day)
            UNION
            SELECT DISTINCT w.revision_id object_id FROM wikipedia_queue w WHERE w.created_at > date_sub(now(), interval $days day)
          ) total";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
        return 0;
    }

    public function curator_actions_in_the_last_n_days($days)
    {
      $sql = "SELECT COUNT(*) count FROM
        (
          SELECT cal.object_id FROM eol_logging_version2.curator_activity_logs cal WHERE cal.created_at > date_sub(now(), interval $days day)
          AND 
          (
            cal.activity_id IN (" . implode(",", $this->curation_activity_ids) . ") OR
            cal.changeable_object_type_id = " . ChangeableObjectType::comment()->id . "
          )
          UNION ALL
          SELECT udo.data_object_id FROM users_data_objects udo WHERE udo.created_at > date_sub(now(), interval $days day)
          UNION ALL
          SELECT w.revision_id object_id FROM wikipedia_queue w WHERE w.created_at > date_sub(now(), interval $days day)
        ) total";
      $result = $this->mysqli_slave->query($sql);
      if($result && $row=$result->fetch_assoc()) return $row['count'];
      return 0;
    }

    public function curators($curator_level_id = null)
    {
        $add_sql = "";
        if($curator_level_id) $add_sql .= " AND u.curator_level_id = " . $curator_level_id;
        if($curator_level_id == $this->assistant_curator_id)
        {
            $sql = "SELECT u.id FROM users u WHERE u.curator_approved=1 AND u.active=1 $add_sql
                    UNION 
                    SELECT u.id FROM users u WHERE u.curator_approved=0 AND u.active=1 AND u.requested_curator_level_id > 0 ";
            $result = $this->mysqli_slave->query($sql);
            $user_ids = array();
            while($result && $row=$result->fetch_assoc()) $user_ids[] = $row['id'];
            return count($user_ids);
        } 
        $sql = "SELECT COUNT(*) count FROM users u WHERE u.curator_approved=1 AND u.active=1 $add_sql";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function not_rich_pages()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics tcm JOIN taxon_concepts tc ON (tcm.taxon_concept_id=tc.id) WHERE tc.published=1 AND tcm.richness_score < 0.10");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function not_so_rich_pages()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics tcm JOIN taxon_concepts tc ON (tcm.taxon_concept_id=tc.id) WHERE tc.published=1 AND tcm.richness_score >= 0.10 AND tcm.richness_score <= 0.39");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function get_redhotlist($collection_ids)
    {
        $result = $this->mysqli_slave->query("SELECT distinct(c.object_id) taxon_concept_id from collection_items c WHERE c.object_type = 'TaxonConcept' AND c.collection_id IN (" . implode(",", $collection_ids) . ")");
        $tc_ids = array();
        while($result && $row=$result->fetch_assoc()) $tc_ids[$row['taxon_concept_id']] = '';
        return array_keys($tc_ids);        
    }

    public function get_rich_pages($hotlist)
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics tcm JOIN taxon_concepts tc ON (tcm.taxon_concept_id=tc.id) WHERE tc.published=1 AND tcm.richness_score >= 0.40 AND tcm.taxon_concept_id IN (" . implode(",", $hotlist) . ")");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function get_hotlist()
    {
        $filename = "http://dl.dropbox.com/u/7597512/SPG/SPG%20Hotlist%20Official%20Version.tsv";
        // $filename = "https://spreadsheets.google.com/feeds/download/spreadsheets/Export?key=0AvGmk5nsoeBldE9aWEtIMlc1WjJUTzZzb21kUGhBb2c%20&exportFormat=tsv";
        // $filename = "https://docs.google.com/spreadsheet/ccc?key=0AvGmk5nsoeBldE9aWEtIMlc1WjJUTzZzb21kUGhBb2c";
        $data = array();
        $READ = fopen($filename, "r");
        while(!feof($READ))
        {
            if($line = fgets($READ))
            {
                $line = trim($line);
                $fields = explode("\t", $line);
                $data[$fields[1]] = '';
            }
        }
        fclose($READ);
        $data = array_keys($data);
        array_pop($data);
        return $data; // delete last element, it is not an ID but a label string 'ID'.
    }

    public function rich_pages()
    {
        $sql = "SELECT COUNT(*) count FROM taxon_concept_metrics tcm JOIN taxon_concepts tc ON (tcm.taxon_concept_id=tc.id) WHERE tc.published=1 AND tcm.richness_score >= 0.40";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function udo_published_by_curators()
    {
        $sql = "SELECT COUNT(distinct do.guid) count FROM users_data_objects udo JOIN data_objects do ON udo.data_object_id = do.id JOIN users u ON udo.user_id = u.id WHERE do.published = 1 AND udo.vetted_id != " . $this->untrusted_id . " AND udo.visibility_id = " . $this->visible_id . " AND u.curator_level_id IN (" . implode(",", $this->curator_ids) . ")";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function udo_published()
    {
        $sql = "SELECT COUNT(distinct do.guid) count FROM users_data_objects udo JOIN data_objects do ON udo.data_object_id = do.id WHERE do.published = 1 AND udo.vetted_id != " . $this->untrusted_id . " AND udo.visibility_id = " . $this->visible_id;
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function content_partners_with_published_resources($vetted = null)
    {
        $sql = "SELECT COUNT(distinct cp.id) count FROM harvest_events he JOIN resources r ON (he.resource_id=r.id) JOIN content_partners cp ON (r.content_partner_id=cp.id) WHERE he.published_at IS NOT NULL and cp.public=1";
        if($vetted) $sql .= " AND r.vetted = $vetted ";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function content_partners()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM content_partners WHERE public = 1");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function latest_harvest_event_ids()
    {
        if(isset($this->latest_harvest_event_ids)) return $this->latest_harvest_event_ids;
        $this->latest_harvest_event_ids = array();        
        $sql = "SELECT max(he.id) latest_harvest_event_id FROM resources r JOIN harvest_events he ON r.id = he.resource_id GROUP BY r.id";
        $result = $this->mysqli_slave->query($sql);
        while($result && $row=$result->fetch_assoc()) $this->latest_harvest_event_ids[] = $row['latest_harvest_event_id'];
        return $this->latest_harvest_event_ids;
    }

    public function published_resources($vetted = "x")
    {
        $sql = "SELECT COUNT(distinct r.id) count FROM harvest_events he 
        JOIN resources r ON (he.resource_id=r.id) JOIN content_partners cp ON (r.content_partner_id=cp.id) 
        WHERE he.published_at IS NOT NULL AND cp.public=1 ";
        if($vetted != "x") $sql .= " AND r.vetted = $vetted ";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function published_resources_in_the_last_n_days($days)
    {
        $sql = "SELECT COUNT(DISTINCT(r.id)) count FROM resources r JOIN harvest_events he ON r.id = he.resource_id 
        WHERE he.published_at IS NOT NULL AND he.published_at >= date_sub(now(), interval $days day) 
        AND r.id NOT IN 
        ( SELECT r.id FROM resources r JOIN harvest_events he ON r.id = he.resource_id 
          WHERE he.published_at IS NOT NULL AND he.published_at < date_sub(now(), interval $days day) )";
        $result = $this->mysqli_slave->query($sql);
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_BHL_links_only()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.BHL_publications > 0 AND 
            t.image_total = 0 AND t.text_total = 0 AND t.video_total = 0 AND t.sound_total = 0 AND t.flash_total = 0 AND t.youtube_total = 0 AND 
            t.map_total = 0 AND t.data_object_references = 0 AND t.outlinks = 0 AND t.has_biomedical_terms = 0 AND t.iucn_total = 0 "); //has_gbif_map = 0 AND
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_BHL_links_no_text()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.BHL_publications > 0 AND t.text_total = 0 AND t.user_submitted_text = 0");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_BHL_links()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.BHL_publications > 0");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_at_least_a_trusted_object()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND 
            t.image_trusted > 0 OR t.text_trusted > 0 OR t.video_trusted > 0 OR t.sound_trusted > 0 OR t.flash_trusted > 0 OR t.youtube_trusted > 0 OR t.map_trusted > 0 "); //has_gbif_map = 1
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_without_content_with_other_info()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND 
            t.image_total = 0 AND t.text_total = 0 AND t.video_total = 0 AND t.sound_total = 0 AND t.flash_total = 0 AND t.youtube_total = 0 AND t.map_total = 0 
            AND ( t.data_object_references > 0 OR t.BHL_publications > 0 OR t.outlinks > 0 OR t.has_biomedical_terms = 1 OR t.iucn_total > 0 )"); //has_gbif_map = 0 AND
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_text()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.text_total > 0 OR t.user_submitted_text > 0");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_image()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.image_total > 0");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_sound()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.sound_total > 0");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_map()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.map_total > 0 "); //OR has_gbif_map = 1
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_video()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.video_total > 0 OR t.flash_total > 0 OR t.youtube_total > 0");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_image_no_text()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.image_total > 0 AND t.text_total = 0 AND t.user_submitted_text = 0");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_text_no_image()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.image_total = 0 AND (t.text_total > 0 OR t.user_submitted_text > 0)");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_with_content()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concept_metrics t JOIN taxon_concepts tc ON (t.taxon_concept_id=tc.id) WHERE tc.published=1 AND t.image_total > 0 OR t.text_total > 0 OR t.video_total > 0 OR t.sound_total > 0 OR t.flash_total > 0 OR t.youtube_total > 0 OR t.map_total > 0"); //OR has_gbif_map = 1
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_count()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concepts tc WHERE tc.published = 1");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function collections_count()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM collections c WHERE c.special_collection_id IS NULL AND c.published = 1");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function communities_count()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM communities c WHERE c.published = 1");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function members_count()
    {
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM users s WHERE s.active = 1");
        if($result && $row=$result->fetch_assoc()) return $row['count'];
    }

    public function pages_comments_curated_thru_data_objects($type, $curators)
    {
        if    ($type == 'DOHE')  $sql = "SELECT DISTINCT he.taxon_concept_id FROM eol_logging_version2.curator_activity_logs cal JOIN comments c ON cal.object_id = c.id JOIN data_objects_hierarchy_entries dohe ON c.parent_id = dohe.data_object_id JOIN hierarchy_entries he ON dohe.hierarchy_entry_id = he.id ";
        elseif($type == 'CDOHE') $sql = "SELECT DISTINCT he.taxon_concept_id FROM eol_logging_version2.curator_activity_logs cal JOIN comments c ON cal.object_id = c.id JOIN curated_data_objects_hierarchy_entries cdohe ON c.parent_id = cdohe.data_object_id JOIN hierarchy_entries he ON cdohe.hierarchy_entry_id = he.id ";
        elseif($type == 'UDO')   $sql = "SELECT DISTINCT udo.taxon_concept_id FROM eol_logging_version2.curator_activity_logs cal JOIN comments c ON cal.object_id = c.id JOIN users_data_objects udo ON c.parent_id = udo.data_object_id ";
        $sql .= "WHERE cal.changeable_object_type_id = " . ChangeableObjectType::comment()->id . " AND c.parent_type = 'DataObject' ";
        if($curators) $sql .= " AND cal.user_id IN (" . implode(",", $curators) . ")";
        $result = $this->mysqli_slave->query($sql);
        $taxon_concept_ids = array();
        while($result && $row=$result->fetch_assoc()) $taxon_concept_ids[] = $row['taxon_concept_id'];
        return $taxon_concept_ids;
    }

}
?>