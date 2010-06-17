<?php

class SiteStatistics
{
    private $mysqli;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->mysqli_slave = load_mysql_environment('slave');
        $this->mysqli_eol = load_mysql_environment('slave_eol');
    }
    
    public function insert_taxa_stats()
    {
        $stats = array();
        $stats['taxa_count'] =                                      $this->pages_with_content();
        $stats['taxa_text'] =                                       $this->pages_with_text();
        $stats['taxa_images'] =                                     $this->pages_with_images();
        $stats['taxa_text_images'] =                                $this->pages_with_text_and_images();
        $stats['taxa_BHL_no_text'] =                                $this->pages_with_bhl_no_text();
        $stats['taxa_links_no_text'] =                              $this->pages_with_links_no_text();
        $stats['taxa_images_no_text'] =                             $this->pages_with_images_no_text();
        $stats['taxa_text_no_images'] =                             $this->pages_with_text_no_images();
        $stats['vet_obj_only_1cat_inCOL'] =                         $this->pages_in_col_one_category();
        $stats['vet_obj_only_1cat_notinCOL'] =                      $this->pages_not_in_col_one_category();
        $stats['vet_obj_morethan_1cat_inCOL'] =                     $this->pages_in_col_more_categories();
        $stats['vet_obj_morethan_1cat_notinCOL'] =                  $this->pages_not_in_col_more_categories();
        $stats['vet_obj'] =                                         $this->pages_with_vetted_objects();
        $stats['no_vet_obj2'] =                                     $this->pages_in_col_no_content();
        $stats['with_BHL'] =                                        $this->pages_with_bhl();
        $stats['vetted_not_published'] =                            $this->pages_awaiting_publishing();
        $stats['vetted_unknown_published_visible_inCol'] =          $this->col_content_needs_curation();
        $stats['vetted_unknown_published_visible_notinCol'] =       $this->non_col_content_needs_curation();
        $stats['pages_incol'] =                                     $this->total_pages_in_col();
        $stats['pages_not_incol'] =                                 $this->total_pages_not_in_col();
        $stats['lifedesk_taxa'] =                                   $this->lifedesk_taxa();
        $stats['lifedesk_dataobject'] =                             $this->lifedesk_data_objects();
        //$stats['date_created'] =                                    date('Y-m-d');
        //$stats['time_created'] =                                    date('H:i:s');
        
        /* will enable once the migration to add these 2 fields in page_stats_taxa table is made.
        $stats['data_objects_count_per_category'] =                 $this->data_object_count_per_subject();
        $stats['content_partners_count_per_category'] =             $this->content_partner_count_per_subject();
        */

        $this->mysqli->insert("INSERT INTO page_stats_taxa (".implode(array_keys($stats), ",").") VALUES ('".implode($stats, "','")."')");
        //$this->delete_old_records_from('page_stats_taxa');
    }
    
    public function insert_data_object_stats()
    {
        $stats = array();
        $stats['taxa_count'] =                                          $this->total_data_objects();
        $stats['vetted_unknown_published_visible_uniqueGuid'] =         $this->unvetted_visible_data_objects();
        $stats['vetted_untrusted_published_visible_uniqueGuid'] =       $this->untrusted_visible_data_objects();
        $stats['vetted_unknown_published_notVisible_uniqueGuid'] =      $this->invisible_unvetted_data_objects();
        $stats['vetted_untrusted_published_notVisible_uniqueGuid'] =    $this->invisible_untrusted_data_objects();
        $stats['user_submitted_text'] =                                 $this->user_submitted_data_objects();
        $stats['date_created'] =                                        date('Y-m-d');
        $stats['time_created'] =                                        date('H:i:s');
        
        $this->mysqli->insert("INSERT INTO page_stats_dataobjects (".implode(array_keys($stats), ",").") VALUES ('".implode($stats, "','")."')");
        //$this->delete_old_records_from('page_stats_dataobjects');
    }
    
    
    ////////////////////////////////////
    ////////////////////////////////////  Main Stats
    ////////////////////////////////////
    
    public function total_pages()
    {
        if(isset($this->total_pages)) return $this->total_pages;
        $this->total_pages = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concepts tc  WHERE tc.published=1 AND tc.supercedure_id=0");
        if($result && $row=$result->fetch_assoc()) $this->total_pages = $row['count'];
        return $this->total_pages;
    }
    
    public function total_pages_in_col()
    {
        if(isset($this->total_pages_in_col)) return $this->total_pages_in_col;
        $this->total_pages_in_col = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM hierarchy_entries he  WHERE he.hierarchy_id=".Hierarchy::col_2009());
        if($result && $row=$result->fetch_assoc()) $this->total_pages_in_col = $row['count'];
        return $this->total_pages_in_col;
    }
    
    public function total_pages_not_in_col()
    {
        if(isset($this->total_pages_not_in_col)) return $this->total_pages_not_in_col;
        
        $this->total_pages_not_in_col = $this->total_pages() - $this->total_pages_in_col();
        return $this->total_pages_not_in_col;
    }
    
    public function pages_with_content()
    {
        if(isset($this->pages_with_content)) return $this->pages_with_content;
        $this->pages_with_content = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concepts tc  JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND (tcc.text=1 OR tcc.image=1 OR tcc.flash=1 OR tcc.youtube=1)");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_content = $row['count'];
        return $this->pages_with_content;
    }
    
    public function pages_with_text()
    {
        if(isset($this->pages_with_text)) return $this->pages_with_text;
        $this->pages_with_text = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concepts tc  JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=1");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_text = $row['count'];
        return $this->pages_with_text;
    }
    
    public function pages_with_images()
    {
        if(isset($this->pages_with_images)) return $this->pages_with_images;
        $this->pages_with_images = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concepts tc  JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.image=1");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_images = $row['count'];
        return $this->pages_with_images;
    }
    
    public function pages_with_text_and_images()
    {
        if(isset($this->pages_with_text_and_images)) return $this->pages_with_text_and_images;
        $this->pages_with_text_and_images = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concepts tc  JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=1 AND tcc.image=1");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_text_and_images = $row['count'];
        return $this->pages_with_text_and_images;
    }
    
    public function pages_with_images_no_text()
    {
        if(isset($this->pages_with_images_no_text)) return $this->pages_with_images_no_text;
        $this->pages_with_images_no_text = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concepts tc  JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=0 AND tcc.image=1");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_images_no_text = $row['count'];
        return $this->pages_with_images_no_text;
    }
    
    public function pages_with_text_no_images()
    {
        if(isset($this->pages_with_text_no_images)) return $this->pages_with_text_no_images;
        $this->pages_with_text_no_images = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM taxon_concepts tc  JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=1 AND tcc.image=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_text_no_images = $row['count'];
        return $this->pages_with_text_no_images;
    }
    
    public function pages_with_links_no_text()
    {
        if(isset($this->pages_with_links_no_text)) return $this->pages_with_links_no_text;
        $this->pages_with_links_no_text = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(tc.id)) count FROM taxon_concepts tc  JOIN taxon_concept_content tcc ON (tc.id=tcc.taxon_concept_id) JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) JOIN mappings m ON (tcn.name_id=m.name_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_links_no_text = $row['count'];
        return $this->pages_with_links_no_text;
    }
    
    
    ////////////////////////////////////
    ////////////////////////////////////  Content By Category
    ////////////////////////////////////
    
    public function pages_with_vetted_objects()
    {
        if(isset($this->pages_with_vetted_objects)) return $this->pages_with_vetted_objects;
        $this->pages_with_vetted_objects = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_with_vetted_objects = $content_by_category['total_with_objects'];
        return $this->pages_with_vetted_objects;
    }
    
    public function pages_in_col_no_content()
    {
        if(isset($this->pages_in_col_no_content)) return $this->pages_in_col_no_content;
        $this->pages_in_col_no_content = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(he.taxon_concept_id)) count FROM  hierarchy_entries he JOIN taxon_concept_content tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) WHERE he.hierarchy_id=".Hierarchy::col_2009()." AND tcc.text=0 AND tcc.image=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_in_col_no_content = $row['count'];
        return $this->pages_in_col_no_content;
    }
    
    public function pages_in_col_one_category()
    {
        if(isset($this->pages_in_col_one_category)) return $this->pages_in_col_one_category;
        $this->pages_in_col_one_category = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_in_col_one_category = $content_by_category['one_type_in_col'];
        return $this->pages_in_col_one_category;
    }
    
    public function pages_not_in_col_one_category()
    {
        if(isset($this->pages_not_in_col_one_category)) return $this->pages_not_in_col_one_category;
        $this->pages_not_in_col_one_category = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_not_in_col_one_category = $content_by_category['one_type_not_in_col'];
        return $this->pages_not_in_col_one_category;
    }
    
    public function pages_in_col_more_categories()
    {
        if(isset($this->pages_in_col_more_categories)) return $this->pages_in_col_more_categories;
        $this->pages_in_col_more_categories = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_in_col_more_categories = $content_by_category['more_types_in_col'];
        return $this->pages_in_col_more_categories;
    }
    
    public function pages_not_in_col_more_categories()
    {
        if(isset($this->pages_not_in_col_more_categories)) return $this->pages_not_in_col_more_categories;
        $this->pages_not_in_col_more_categories = 0;
        
        $content_by_category = $this->content_by_category();
        $this->pages_not_in_col_more_categories = $content_by_category['more_types_not_in_col'];
        return $this->pages_not_in_col_more_categories;
    }
    
    
    ////////////////////////////////////
    ////////////////////////////////////   BHL
    ////////////////////////////////////
    
    public function pages_with_bhl()
    {
        if(isset($this->pages_with_bhl)) return $this->pages_with_bhl;
        $this->pages_with_bhl = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(tc.id)) count  FROM taxon_concepts tc JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) JOIN page_names pn ON (tcn.name_id=pn.name_id) WHERE  tc.published=1 AND tc.supercedure_id=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_bhl = $row['count'];
        return $this->pages_with_bhl;
    }
    
    public function pages_with_bhl_no_text()
    {
        if(isset($this->pages_with_bhl_no_text)) return $this->pages_with_bhl_no_text;
        $this->pages_with_bhl_no_text = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(tc.id)) count  FROM taxon_concepts tc JOIN taxon_concept_names tcn ON (tc.id=tcn.taxon_concept_id) JOIN page_names pn ON (tcn.name_id=pn.name_id) JOIN taxon_concept_content tcc ON  (tc.id=tcc.taxon_concept_id) WHERE tc.published=1 AND tc.supercedure_id=0 AND tcc.text=0");
        if($result && $row=$result->fetch_assoc()) $this->pages_with_bhl_no_text = $row['count'];
        return $this->pages_with_bhl_no_text;
    }
    
    
    
    ////////////////////////////////////
    ////////////////////////////////////   Curators
    ////////////////////////////////////
    
    public function pages_awaiting_publishing()
    {
        if(isset($this->pages_awaiting_publishing)) return $this->pages_awaiting_publishing;
        $this->pages_awaiting_publishing = 0;
        
        $events_to_publish = array();
        $result = $this->mysqli_slave->query("SELECT he.resource_id, max(he.id) max FROM  harvest_events he GROUP BY he.resource_id");
        while($result && $row=$result->fetch_assoc())
        {
            $harvest_event = new HarvestEvent($row['max']);
            if(!$harvest_event->published_at) $events_to_publish[] = $harvest_event->id;
        }
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(tc.id)) count FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) WHERE hehe.harvest_event_id IN (".implode($events_to_publish, ",").") AND tc.published=0 AND tc.vetted_id=". Vetted::find("trusted"));
        if($result && $row=$result->fetch_assoc()) $this->pages_awaiting_publishing = $row['count'];
        return $this->pages_awaiting_publishing;
    }
    
    public function col_content_needs_curation()
    {
        if(isset($this->col_content_needs_curation)) return $this->col_content_needs_curation;
        $this->col_content_needs_curation = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT dotc.taxon_concept_id) count FROM data_objects_taxon_concepts dotc  JOIN data_objects do ON (dotc.data_object_id=do.id) LEFT JOIN hierarchy_entries he ON (dotc.taxon_concept_id=he.taxon_concept_id AND he.hierarchy_id=".Hierarchy::col_2009().") WHERE do.visibility_id=".Visibility::find("visible")." AND do.vetted_id=".Vetted::find('Unknown')." AND he.id IS NOT NULL");
        if($result && $row=$result->fetch_assoc()) $this->col_content_needs_curation = $row['count'];
        return $this->col_content_needs_curation;
    }
    
    public function non_col_content_needs_curation()
    {
        if(isset($this->non_col_content_needs_curation)) return $this->non_col_content_needs_curation;
        $this->non_col_content_needs_curation = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT dotc.taxon_concept_id) count FROM data_objects_taxon_concepts dotc  JOIN data_objects do ON (dotc.data_object_id=do.id) LEFT JOIN hierarchy_entries he ON (dotc.taxon_concept_id=he.taxon_concept_id AND he.hierarchy_id=".Hierarchy::col_2009().") WHERE do.visibility_id=".Visibility::find("visible")." AND do.vetted_id=".Vetted::find('Unknown')." AND he.id IS NULL");
        if($result && $row=$result->fetch_assoc()) $this->non_col_content_needs_curation = $row['count'];
        return $this->non_col_content_needs_curation;
    }
    
    
    
    ////////////////////////////////////
    ////////////////////////////////////   LifeDesk
    ////////////////////////////////////
    
    public function lifedesk_taxa()
    {
        if(isset($this->lifedesk_taxa)) return $this->lifedesk_taxa;
        $this->lifedesk_taxa = 0;
        
        $latest_published_lifedesk_resources = $this->latest_published_lifedesk_resources();
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(he.taxon_concept_id)) count FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON (hehe.hierarchy_entry_id=he.id) WHERE hehe.harvest_event_id IN (".implode($latest_published_lifedesk_resources, ",").")");
        if($result && $row=$result->fetch_assoc()) $this->lifedesk_taxa = $row['count'];
        return $this->lifedesk_taxa;
    }
    
    public function lifedesk_data_objects()
    {
        if(isset($this->lifedesk_data_objects)) return $this->lifedesk_data_objects;
        $this->lifedesk_data_objects = 0;
        
        $latest_published_lifedesk_resources = $this->latest_published_lifedesk_resources();
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(do.id)) count FROM data_objects_harvest_events dohe  JOIN data_objects do ON (dohe.data_object_id=do.id) WHERE dohe.harvest_event_id IN (".implode($latest_published_lifedesk_resources, ",").") AND do.published=1");
        if($result && $row=$result->fetch_assoc()) $this->lifedesk_data_objects = $row['count'];
        return $this->lifedesk_data_objects;
    }
    
    
    public function latest_published_lifedesk_resources()
    {
        $resource_ids = array();
        $result = $this->mysqli_slave->query("SELECT r.id, max(he.id) max FROM resources r JOIN harvest_events he  ON (r.id=he.resource_id) WHERE r.accesspoint_url LIKE '%lifedesks.org%' AND he.published_at IS NOT NULL GROUP BY r.id");
        while($result && $row=$result->fetch_assoc())
        {
            $resource_ids[] = $row['max'];
        }
        return $resource_ids;
    }
    
    
    
    ////////////////////////////////////
    ////////////////////////////////////   DataObjects
    ////////////////////////////////////
    
    public function total_data_objects()
    {
        if(isset($this->total_data_objects)) return $this->total_data_objects;
        $this->total_data_objects = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM data_objects  WHERE published=1 AND visibility_id=".Visibility::find('visible'));
        if($result && $row=$result->fetch_assoc()) $this->total_data_objects = $row['count'];
        return $this->total_data_objects;
    }
    
    public function unvetted_visible_data_objects()
    {
        if(isset($this->unvetted_visible_data_objects)) return $this->unvetted_visible_data_objects;
        $this->unvetted_visible_data_objects = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM data_objects  WHERE published=1 AND visibility_id=".Visibility::find('visible')." AND vetted_id=".Vetted::find('Unknown'));
        if($result && $row=$result->fetch_assoc()) $this->unvetted_visible_data_objects = $row['count'];
        return $this->unvetted_visible_data_objects;
    }
    
    public function untrusted_visible_data_objects()
    {
        if(isset($this->untrusted_visible_data_objects)) return $this->untrusted_visible_data_objects;
        $this->untrusted_visible_data_objects = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM data_objects  WHERE published=1 AND visibility_id=".Visibility::find('visible')." AND vetted_id=".Vetted::find('Untrusted'));
        if($result && $row=$result->fetch_assoc()) $this->untrusted_visible_data_objects = $row['count'];
        return $this->untrusted_visible_data_objects;
    }
    
    public function invisible_unvetted_data_objects()
    {
        if(isset($this->invisible_unvetted_data_objects)) return $this->invisible_unvetted_data_objects;
        $this->invisible_unvetted_data_objects = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM data_objects  WHERE published=1 AND visibility_id=".Visibility::find('invisible')." AND vetted_id=".Vetted::find('Unknown'));
        if($result && $row=$result->fetch_assoc()) $this->invisible_unvetted_data_objects = $row['count'];
        return $this->invisible_unvetted_data_objects;
    }
    
    public function invisible_untrusted_data_objects()
    {
        if(isset($this->invisible_untrusted_data_objects)) return $this->invisible_untrusted_data_objects;
        $this->invisible_untrusted_data_objects = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM data_objects  WHERE published=1 AND visibility_id=".Visibility::find('invisible')." AND vetted_id=".Vetted::find('Untrusted'));
        if($result && $row=$result->fetch_assoc()) $this->invisible_untrusted_data_objects = $row['count'];
        return $this->invisible_untrusted_data_objects;
    }
    
    public function user_submitted_data_objects()
    {
        if(isset($this->user_submitted_data_objects)) return $this->user_submitted_data_objects;
        $this->user_submitted_data_objects = 0;
        
        $data_object_ids = array();
        $result = $this->mysqli_eol->query("SELECT DISTINCT data_object_id  FROM users_data_objects");
        while($result && $row=$result->fetch_assoc()) $data_object_ids[] = $row['data_object_id'];
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM data_objects  WHERE published=1 AND visibility_id=".Visibility::find('visible')." AND id IN (".implode($data_object_ids, ",").")");
        if($result && $row=$result->fetch_assoc()) $this->user_submitted_data_objects = $row['count'];
        return $this->user_submitted_data_objects;
    }
    
    
    
    
    
    ////////////////////////////////////
    //////////////////////////////////// Methods
    ////////////////////////////////////
    
    public function content_by_category()
    {
        if(isset($this->content_by_category)) return $this->content_by_category;
        $this->content_by_category = array(
            'total_with_objects'    => 0,
            'one_type_in_col'       => 0,
            'one_type_not_in_col'   => 0,
            'more_types_in_col'     => 0,
            'more_types_not_in_col' => 0);
        
        $this->mysqli->query("DROP TABLE IF EXISTS `taxon_concepts_data_types`");
        $this->mysqli->query("CREATE TABLE `taxon_concepts_data_types` (
          `taxon_concept_id` int unsigned NOT NULL,
          `count_data_types` smallint unsigned NOT NULL,
          `count_toc` smallint unsigned NOT NULL,
          `in_col` tinyint unsigned NOT NULL,
          PRIMARY KEY  (`taxon_concept_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, count(distinct do.data_type_id), count(distinct dotoc.toc_id), he_col.id IS NOT NULL FROM taxon_concepts tc JOIN data_objects_taxon_concepts dotc ON (tc.id=dotc.taxon_concept_id) JOIN data_objects do ON (dotc.data_object_id=do.id) LEFT JOIN data_objects_table_of_contents dotoc ON (do.id=dotoc.data_object_id) LEFT JOIN hierarchy_entries he_col ON (tc.id=he_col.taxon_concept_id AND he_col.hierarchy_id=".Hierarchy::col_2009().") WHERE tc.published=1 AND tc.supercedure_id=0 AND do.published=1 AND do.visibility_id=".Visibility::find("visible")." AND do.vetted_id=".Vetted::find("trusted")." GROUP BY tc.id");
        $GLOBALS['db_connection']->load_data_infile($outfile, 'taxon_concepts_data_types');
        unlink($outfile);
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts_data_types");
        if($result && $row=$result->fetch_assoc()) $this->content_by_category['total_with_objects'] = $row['count'];
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts_data_types WHERE count_data_types=1 AND count_toc<2 AND in_col=1");
        if($result && $row=$result->fetch_assoc()) $this->content_by_category['one_type_in_col'] = $row['count'];
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts_data_types WHERE count_data_types=1 AND count_toc<2 AND in_col=0");
        if($result && $row=$result->fetch_assoc()) $this->content_by_category['one_type_not_in_col'] = $row['count'];
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts_data_types WHERE count_data_types>1 OR count_toc>1 AND in_col=1");
        if($result && $row=$result->fetch_assoc()) $this->content_by_category['more_types_in_col'] = $row['count'];
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts_data_types WHERE count_data_types>1 OR count_toc>1 AND in_col=0");
        if($result && $row=$result->fetch_assoc()) $this->content_by_category['more_types_not_in_col'] = $row['count'];
        
        $this->mysqli->query("DROP TABLE IF EXISTS `taxon_concepts_data_types`");
        
        return $this->content_by_category;
    }
    
    public function taxon_concept_curation()
    {
        if(isset($this->taxon_concept_curation)) return $this->taxon_concept_curation;
        $this->taxon_concept_curation = array(
            'needs_curation_in_col'     => 0,
            'needs_curation_not_in_col' => 0);
        
        $this->mysqli->query("DROP TABLE IF EXISTS `taxon_concepts_curation`");
        $this->mysqli->query("CREATE TABLE `taxon_concepts_curation` (
          `taxon_concept_id` int unsigned NOT NULL,
          `requires_curation` tinyint unsigned NOT NULL,
          `in_col` tinyint unsigned NOT NULL,
          PRIMARY KEY  (`taxon_concept_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, 1, he_col.id IS NOT NULL FROM taxon_concepts tc JOIN data_objects_taxon_concepts dotc ON (tc.id=dotc.taxon_concept_id) JOIN data_objects do ON (dotc.data_object_id=do.id) LEFT JOIN hierarchy_entries he_col ON (tc.id=he_col.taxon_concept_id AND he_col.hierarchy_id=".Hierarchy::col_2009().") WHERE tc.published=1 AND tc.supercedure_id=0 AND do.published=1 AND do.visibility_id=".Visibility::find("visible")." AND do.vetted_id=".Vetted::find('Unknown'));
        $GLOBALS['db_connection']->load_data_infile($outfile, 'taxon_concepts_curation');
        unlink($outfile);
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts_curation WHERE in_col=1");
        if($result && $row=$result->fetch_assoc()) $this->taxon_concept_curation['needs_curation_in_col'] = $row['count'];
        
        $result = $this->mysqli->query("SELECT COUNT(*) count FROM taxon_concepts_curation WHERE in_col=0");
        if($result && $row=$result->fetch_assoc()) $this->taxon_concept_curation['needs_curation_not_in_col'] = $row['count'];
        
        $this->mysqli->query("DROP TABLE IF EXISTS `taxon_concepts_curation`");
        
        return $this->taxon_concept_curation;
    }
    
    private function delete_old_records_from($table)
    {
        // if($table != "page_stats_taxa" && $table != "page_stats_dataobjects") return false;
        // $result = $this->mysqli->query("SELECT id FROM $table ORDER BY date_created DESC, time_created DESC limit 8,1");
        // if($result && $row=$result->fetch_assoc())
        // {
        //     $delete_from_id = $row['id'];
        //     $this->mysqli->delete("DELETE FROM $table WHERE id >= $delete_from_id");
        // }
    }
    


    ////////////////////////////////////
    ////////////////////////////////////  data objects count per SPM category
    ////////////////////////////////////
    
    public function data_object_count_per_subject()
    {    
        print"Start compute dataObject count per subject... \n";        
        if(isset($this->data_object_count_per_subject)) return $this->data_object_count_per_subject;
        $this->data_object_count_per_subject = "";
    
        $subjects = $this->get_info_items();
        $arr_count = array();
        while($subjects && $row=$subjects->fetch_assoc()) 
        {   
            $id = intval($row["id"]);
            print "$id of $subjects->num_rows" . "\n";
            $sql="SELECT   Count(do.id) total FROM data_objects do JOIN data_objects_info_items doii ON do.id = doii.data_object_id WHERE doii.info_item_id = $id AND do.published AND do.vetted_id != " . Vetted::find('Untrusted') . "";
            $rset = $this->mysqli->query($sql);
            while($rset && $row2=$rset->fetch_assoc()){$arr_count["$id"] = $row2["total"];}
        }                
        print_r($arr_count);        
        print"Start adding user-submitted-text count... \n";
        $user_data_object_ids = $this->get_user_data_object_ids();
        $arr_count = $this->add_user_data_objects_count($arr_count,$user_data_object_ids);        
        print_r($arr_count);        
        print json_encode($arr_count) . "\n";        
        $this->data_object_count_per_subject = json_encode($arr_count);        
        return $this->data_object_count_per_subject;        
    }
    public function get_info_items()
    {
        return $this->mysqli->query("SELECT   schema_value, id FROM info_items ORDER BY id  ");
    }
    public function add_user_data_objects_count($arr_count,$user_data_object_ids)
    {        
        if(count($user_data_object_ids) > 0)
        {
            $sql="SELECT   ii.id, Count(ii.id) total FROM data_objects_table_of_contents dotc JOIN info_items ii ON dotc.toc_id = ii.toc_id JOIN data_objects do ON dotc.data_object_id = do.id WHERE dotc.data_object_id IN (".implode($user_data_object_ids, ",").") Group By ii.id ";              
            $rset = $this->mysqli->query($sql);
            $i=0;
            while($rset && $row=$rset->fetch_assoc()) 
            {
                $i++; print "$i of " . $rset->num_rows . "\n";
                $id = $row["id"];
                if(@$arr_count["$id"]) $arr_count["$id"] += $row["total"];
                else                   $arr_count["$id"] = $row["total"];
            }
        }
        return $arr_count;
    }
    public function get_user_data_object_ids()
    {
        $mysqli2 = load_mysql_environment('slave_eol');//eol_production database     
        $query = "SELECT   udo.data_object_id FROM users_data_objects udo join eol_data_production.data_objects do on do.id=udo.data_object_id WHERE do.published AND do.vetted_id != " . Vetted::find('Untrusted') . "";
        $result = $mysqli2->query($query);            
        $arr=array();
        while($result && $row=$result->fetch_assoc()){$arr[] = $row["data_object_id"];}
        return $arr;
    }
    ////////////////////////////////////
    ////////////////////////////////////  content partners count per SPM category
    ////////////////////////////////////
    public function content_partner_count_per_subject()
    {
        print"Start compute contentPartner count per subject... \n";
        if(isset($this->content_partner_count_per_subject)) return $this->content_partner_count_per_subject;
        $this->content_partner_count_per_subject = "";
        $harvests = $this->get_agents_harvest_ids();
        $arr=array();
        $i=0;
        foreach ($harvests as $harvest) 
        {
            $i++;print "$i of " . count($harvests) . "\n";            
            $sql="Select   data_objects_info_items.info_item_id
            From data_objects_harvest_events
            JOIN data_objects_info_items ON data_objects_harvest_events.data_object_id = data_objects_info_items.data_object_id
            where data_objects_harvest_events.harvest_event_id = $harvest[harvest_id]
            Group By data_objects_info_items.info_item_id";            
            $rset = $this->mysqli->query($sql);            
            while($rset && $row=$rset->fetch_assoc())
            {
                @$arr["$row[info_item_id]"]++; 
            }                   
        }
        ksort($arr);
        print_r($arr);
        print json_encode($arr);        
        $this->content_partner_count_per_subject = json_encode($arr);        
        return $this->content_partner_count_per_subject;                
    }
    public function get_agents_harvest_ids()
    {
        $sql="Select   Max(harvest_events.id) AS latest_harvest_events_id, agents_resources.agent_id 
        From agents_resources JOIN harvest_events ON agents_resources.resource_id = harvest_events.resource_id
        Group By agents_resources.agent_id";
        $rset = $this->mysqli->query($sql);
        $arr=array();
        while($rset && $row=$rset->fetch_assoc())
        {
            $arr[] = array("agent_id" => $row["agent_id"], "harvest_id" => $row["latest_harvest_events_id"]);
        }        
        return $arr;
    }



}

?>