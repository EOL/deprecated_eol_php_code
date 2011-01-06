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
        
        $stats['data_objects_count_per_category'] =                 $this->data_object_count_per_subject();
        $stats['content_partners_count_per_category'] =             $this->content_partner_count_per_subject();

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
        
        $result = $this->mysqli_slave->query("SELECT COUNT(*) count FROM hierarchy_entries he  WHERE he.hierarchy_id=".Hierarchy::default_id());
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
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT(he.taxon_concept_id)) count FROM  hierarchy_entries he JOIN taxon_concept_content tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) WHERE he.hierarchy_id=".Hierarchy::default_id()." AND tcc.text=0 AND tcc.image=0");
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
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT dotc.taxon_concept_id) count FROM data_objects_taxon_concepts dotc  JOIN data_objects do ON (dotc.data_object_id=do.id) LEFT JOIN hierarchy_entries he ON (dotc.taxon_concept_id=he.taxon_concept_id AND he.hierarchy_id=".Hierarchy::default_id().") WHERE do.visibility_id=".Visibility::find("visible")." AND do.vetted_id=".Vetted::find('Unknown')." AND he.id IS NOT NULL");
        if($result && $row=$result->fetch_assoc()) $this->col_content_needs_curation = $row['count'];
        return $this->col_content_needs_curation;
    }
    
    public function non_col_content_needs_curation()
    {
        if(isset($this->non_col_content_needs_curation)) return $this->non_col_content_needs_curation;
        $this->non_col_content_needs_curation = 0;
        
        $result = $this->mysqli_slave->query("SELECT COUNT(DISTINCT dotc.taxon_concept_id) count FROM data_objects_taxon_concepts dotc  JOIN data_objects do ON (dotc.data_object_id=do.id) LEFT JOIN hierarchy_entries he ON (dotc.taxon_concept_id=he.taxon_concept_id AND he.hierarchy_id=".Hierarchy::default_id().") WHERE do.visibility_id=".Visibility::find("visible")." AND do.vetted_id=".Vetted::find('Unknown')." AND he.id IS NULL");
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
        
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, count(distinct do.data_type_id), count(distinct dotoc.toc_id), he_col.id IS NOT NULL FROM taxon_concepts tc JOIN data_objects_taxon_concepts dotc ON (tc.id=dotc.taxon_concept_id) JOIN data_objects do ON (dotc.data_object_id=do.id) LEFT JOIN data_objects_table_of_contents dotoc ON (do.id=dotoc.data_object_id) LEFT JOIN hierarchy_entries he_col ON (tc.id=he_col.taxon_concept_id AND he_col.hierarchy_id=".Hierarchy::default_id().") WHERE tc.published=1 AND tc.supercedure_id=0 AND do.published=1 AND do.visibility_id=".Visibility::find("visible")." AND do.vetted_id=".Vetted::find("trusted")." GROUP BY tc.id");
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
        
        $outfile = $GLOBALS['db_connection']->select_into_outfile("SELECT tc.id, 1, he_col.id IS NOT NULL FROM taxon_concepts tc JOIN data_objects_taxon_concepts dotc ON (tc.id=dotc.taxon_concept_id) JOIN data_objects do ON (dotc.data_object_id=do.id) LEFT JOIN hierarchy_entries he_col ON (tc.id=he_col.taxon_concept_id AND he_col.hierarchy_id=".Hierarchy::default_id().") WHERE tc.published=1 AND tc.supercedure_id=0 AND do.published=1 AND do.visibility_id=".Visibility::find("visible")." AND do.vetted_id=".Vetted::find('Unknown'));
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
        //$mysqli2 = load_mysql_environment('slave_eol');//eol_production database     
        $sql = "SELECT udo.data_object_id FROM users_data_objects udo join eol_data_production.data_objects do on do.id=udo.data_object_id WHERE do.published AND do.vetted_id != " . Vetted::find('Untrusted') . "";
        $result = $this->mysqli_eol->query($sql);            
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

    //##########################################################################################################################################################################
    ////////////////////////////////////
    ////////////////////////////////////  BHL - generate text files
    ////////////////////////////////////
    
    public function generate_taxon_concept_with_bhl_links_textfile() //execution time: 20 mins.
    {
        /*  This will generate the taxon_concept_with_bhl_links.txt. 
            Run once everytime BHL data is updated. 
            Lists all concepts with BHL links. */                            
        
        $timestart = microtime(1);        
        print"\n start - generate_taxon_concept_with_bhl_links_textfile";
        $result = $this->mysqli_slave->query("SELECT DISTINCT tc.id tc_id FROM taxon_concepts tc JOIN taxon_concept_names tcn on (tc.id=tcn.taxon_concept_id) JOIN page_names pn on (tcn.name_id=pn.name_id) WHERE tc.supercedure_id=0 AND tc.published=1");
        $str="";
        while($result && $row=$result->fetch_assoc())               
        {               
            $str .= $row['tc_id'] . "\n";                                                        
        }
        $filename = DOC_ROOT . "tmp/taxon_concept_with_bhl_links.txt.tmp"; 
        $fp = fopen($filename,"w"); print"\n writing..."; fwrite($fp,$str); fclose($fp); print"\n saved.";                
        
        //rename
        unlink(DOC_ROOT . "tmp/taxon_concept_with_bhl_links.txt");
        rename(DOC_ROOT . "tmp/taxon_concept_with_bhl_links.txt.tmp", DOC_ROOT . "tmp/taxon_concept_with_bhl_links.txt");                                        
        
        print"\n end - generate_taxon_concept_with_bhl_links_textfile";        
        $elapsed_time_sec = microtime(1)-$timestart;
        echo "\n elapsed time = $elapsed_time_sec secs              ";
        echo "\n elapsed time = " . $elapsed_time_sec/60 . " mins   ";
        echo "\n elapsed time = " . $elapsed_time_sec/60/60 . " hrs ";        
    }         
    
    public function generate_taxon_concept_with_bhl_publications_textfile() //single-query approach --- execution time: 10hrs
    {
        /*  This will generate the [taxon_concept_with_bhl_publications.txt]. 
            Run once everytime BHL data is updated. 
            Assigns # of publications for every concept. 
            Should be run locally and just copy to production.
        */                                
        
        $write_filename = DOC_ROOT . "tmp/taxon_concept_with_bhl_publications.txt.tmp";                         
        unlink($write_filename);
        $fp = fopen($write_filename,"a");                
        
        //start reading text file
        print"\n Start reading text file [taxon_concept_with_bhl_links] \n";                
        $filename = DOC_ROOT . "tmp/taxon_concept_with_bhl_links.txt"; 
        $FILE = fopen($filename, "r"); $i=0; $str=""; $save_count=0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $line = trim($line); $fields = explode("\t", $line);                    
                if($tc_id = trim($fields[0]))
                {
                    $sql = "Select ip.title_item_id From taxon_concept_names tcn Inner Join page_names pn ON tcn.name_id = pn.name_id Inner Join item_pages ip ON pn.item_page_id = ip.id Where tcn.taxon_concept_id = $tc_id ";
                    $result = $this->mysqli_slave->query($sql);
                    $arr=array();
                    while($result && $row=$result->fetch_assoc())               
                    {
                        $title_item_id = $row['title_item_id'];
                        $arr[$title_item_id]='';
                    }            
                    $publications = sizeof(array_keys($arr));                        
                    $str .= $tc_id . "\t" . $publications . "\n";                                                        
                    $i++; print "\n $i. [$tc_id][$publications] ";                      
                    
                    //saving
                    $save_count++;
                    if($save_count == 10000)
                    {
                        print"\n writing..."; fwrite($fp,$str);  print" saved.";                
                        $str=""; $save_count=0;
                    }                                              
                    if($i >= 15)break; //debug
                }
            }                
        }                    
        fclose($FILE);
        
        //last remaining writes
        print"\n writing..."; fwrite($fp,$str);  print" saved.";                
        fclose($fp);
        
        //rename
        unlink(DOC_ROOT . "tmp/taxon_concept_with_bhl_publications.txt");
        rename(DOC_ROOT . "tmp/taxon_concept_with_bhl_publications.txt.tmp", DOC_ROOT . "tmp/taxon_concept_with_bhl_publications.txt");                        
    }        
    
        
    //##########################################################################################################################################################################
    ////////////////////////////////////
    ////////////////////////////////////  Taxon Page Metrics: 
    ////////////////////////////////////  http://jira.eol.org/browse/WEB-1845
        
    public function create_page_metrics_table()/* prepare taxon concept totals for richness calculations */ 
    {           
        /*                        
        $tc_id=206692;             
        //$tc_id=218284; //with user-submitted-text                        
        self::get_BHL_publications($tc_id);                
        self::get_GBIF_map_availability($tc_id);                
        self::get_user_submitted_text_count($tc_id);        
        self::get_common_names_count($tc_id);
        self::get_synonyms_count($tc_id);                
        self::get_biomedical_terms_availability($tc_id);
        self::get_data_objects_count($tc_id);
        self::get_outlinks_count($tc_id);                
        self::get_content_partner_count($tc_id);        
        self::get_google_stats($tc_id); //page_views and unique_page_views                        
        */                                
        
        /*                
        self::get_BHL_publications();                 //1                                
        self::get_GBIF_map_availability();            //2        
        self::get_user_submitted_text_count();        //3    
        self::get_common_names_count();               //4
        self::get_synonyms_count();                   //5 
        self::get_biomedical_terms_availability();    //6    
        self::get_data_objects_count();               //7
        self::get_outlinks_count();                   //8     
        self::get_content_partner_count();            //9                    
        self::get_google_stats();                     //10
        */                
        
        self::save_to_text_file($arr_taxa);                      
        self::save_to_table();                        
    }       
    
    function get_google_stats($param_id=NULL)
    {        
        $arr_taxa=array();
        //get the 12th month - descending order        
        $sql="Select concat(gas.`year`,'_',substr(gas.`month` / 100,3,2)) as `year_month` From google_analytics_summaries gas Order By gas.`year` Desc, gas.`month` Desc limit 11,1";
        $result = $this->mysqli_slave->query($sql);                        
        if($result && $row=$result->fetch_assoc()) $year_month = $row['year_month'];                        

        $batch=500000; $start_limit=0;
        while(true)
        {       
            print"\n Google stats: page_views, unique_page_views [10 of 10] $start_limit \n";                        
            $sql="Select gaps.taxon_concept_id, gaps.page_views, gaps.unique_page_views From google_analytics_page_stats gaps Where concat(gaps.year,'_',substr(gaps.month/100,3,2)) >= '$year_month'";                    
            if($param_id)$sql .= " and gaps.taxon_concept_id = $param_id ";                
            $sql .= " limit $start_limit, $batch ";                        
            $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);                                        
                    $tc_id             = trim($fields[0]);
                    $page_views        = trim($fields[1]);
                    $unique_page_views = trim($fields[2]);                    
                    
                    @$arr_taxa[$tc_id]['pv'] +=$page_views;
                    @$arr_taxa[$tc_id]['upv']+=$unique_page_views;                                
                }
            }                
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";                        
            if($num_rows < $batch)break; 
        } 
        self::save_json_to_txt($arr_taxa,"tpm_google_stats"); unset($arr_taxa);        
    }
    
    function save_json_to_txt($arr,$filename)
    {        
        $json = json_encode($arr);        
        $fp = fopen(DOC_ROOT . "tmp/" . $filename . ".txt","w");            
        fwrite($fp,$json); fclose($fp);        
    }
    
    public function get_BHL_publications($param_id=NULL)
    {
        $arr_taxa=array();
        print"\n BHL publications [1 of 10]\n";                
        $filename = DOC_ROOT . "tmp/taxon_concept_with_bhl_publications.txt"; 
        $FILE = fopen($filename, "r");
        $num_rows=0; 
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++; $line = trim($line); $fields = explode("\t", $line);                    
                $tc_id        = trim(@$fields[0]);
                $publications = trim(@$fields[1]);                    
                $arr_taxa[$tc_id] = $publications;
            }                
        }            
        self::save_json_to_txt($arr_taxa,"tpm_BHL"); unset($arr_taxa);        
    }    
    
    public function get_biomedical_terms_availability($param_id=NULL)
    {        
        $arr_taxa=array();
        print"\n BOA_biomedical_terms [6 of 10]\n";                
        $BOA_agent_id = 9447;
        $result = $this->mysqli_slave->query("SELECT Max(harvest_events.id) latest_harvent_event_id FROM harvest_events JOIN agents_resources ON agents_resources.resource_id = harvest_events.resource_id WHERE agents_resources.agent_id = $BOA_agent_id AND harvest_events.published_at Is Not Null ");
        if($result && $row=$result->fetch_assoc()) $latest_harvent_event_id = $row['latest_harvent_event_id'];
               
        $sql="SELECT he.taxon_concept_id tc_id FROM harvest_events_hierarchy_entries hehe JOIN hierarchy_entries he ON hehe.hierarchy_entry_id = he.id WHERE hehe.harvest_event_id = $latest_harvent_event_id ";
        if($param_id)$sql .= " and he.taxon_concept_id = $param_id ";                
        $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
        $FILE = fopen($outfile, "r");
        $num_rows=0; 
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++; $line = trim($line); $fields = explode("\t", $line);                    
                $tc_id = trim($fields[0]);
                @$arr_taxa[$tc_id]=1;                                        
            }
        }        
        fclose($FILE);unlink($outfile);    
        print "\n num_rows: $num_rows";        
        self::save_json_to_txt($arr_taxa,"tpm_biomedical_terms"); unset($arr_taxa);        
    }
    
    public function get_GBIF_map_availability($param_id=NULL)
    {
        $arr_taxa=array();
        print"\n GBIF_map [2 of 10]\n";        
        $sql="SELECT tc.id tc_id FROM hierarchies_content hc JOIN hierarchy_entries he ON hc.hierarchy_entry_id = he.id JOIN taxon_concepts tc ON he.taxon_concept_id = tc.id WHERE hc.map > 0 AND tc.published = 1 AND tc.supercedure_id=0 ";
        if($param_id)$sql .= " and tc.id = $param_id ";                
        $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
        $FILE = fopen($outfile, "r");
        $num_rows=0; 
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++; $line = trim($line); $fields = explode("\t", $line);                    
                $tc_id = trim($fields[0]);
                @$arr_taxa[$tc_id]=1;            
            }
        }        
        fclose($FILE);unlink($outfile);
        print "\n num_rows: $num_rows";        
        
        self::save_json_to_txt($arr_taxa,"tpm_GBIF"); unset($arr_taxa);        
    }
    
    public function get_user_submitted_text_count($param_id=NULL)
    {   
        $arr_taxa=array();
        print"\n user_submitted_text, its providers [3 of 10]\n";        
        //debug
        $sql="SELECT udo.taxon_concept_id tc_id, udo.data_object_id do_id, udo.user_id FROM eol_production.users_data_objects udo JOIN data_objects do ON udo.data_object_id = do.id WHERE do.published=1 AND do.vetted_id != " . Vetted::find('Untrusted');
        if($param_id)$sql .= " and udo.taxon_concept_id = $param_id ";                
        $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
        $FILE = fopen($outfile, "r");
        $num_rows=0; $temp=array(); $temp2=array();        
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $num_rows++; $line = trim($line); $fields = explode("\t", $line);                    
                $tc_id   = trim($fields[0]);
                $do_id   = trim($fields[1]);
                $user_id = trim($fields[2]);
                $temp[$tc_id][$do_id]='';
                $temp2[$tc_id][$user_id]='';            
            }
        }                
        fclose($FILE); unlink($outfile); print "\n num_rows: $num_rows";
        
        foreach($temp as $id => $rec)   {@$arr_taxa[$id]['ust_cnt'] = sizeof($rec);}            
        foreach($temp2 as $id => $rec)  {@$arr_taxa[$id]['ust_prov'] = sizeof($rec);}                    
        unset($temp); unset($temp2); 
        
        self::save_json_to_txt($arr_taxa,"tpm_user_added_text"); unset($arr_taxa);        
    }
    
    public function get_content_partner_count($param_id=NULL)
    {
        $arr_taxa=array();
        $batch=500000; $start_limit=0;        
        $sql="Select Max(harvest_events.id) he_id FROM harvest_events Where harvest_events.published_at Is Not Null Group By harvest_events.resource_id";        
        $result = $this->mysqli_slave->query($sql);            
        $latest_harvest_event_ids=array();
        while($result && $row=$result->fetch_assoc()){$latest_harvest_event_ids[] = $row["he_id"];}               
        while(true)
        {       
            print"\n content_partners [9 of 10] $start_limit \n";
            $sql="SELECT tc.id tc_id, ar.agent_id FROM taxon_concepts tc JOIN data_objects_taxon_concepts dotc ON tc.id = dotc.taxon_concept_id JOIN data_objects_harvest_events dohe ON dotc.data_object_id = dohe.data_object_id JOIN harvest_events he ON dohe.harvest_event_id = he.id JOIN agents_resources ar ON he.resource_id = ar.resource_id WHERE tc.published=1 AND tc.supercedure_id=0 and he.id in (" . implode($latest_harvest_event_ids, ",") . ")";  
            if($param_id)$sql .= " and tc.id = $param_id ";
            $sql .= " limit $start_limit, $batch ";                        
            $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0; $temp=array();
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);                                        
                    $tc_id      = trim($fields[0]);
                    $agent_id   = trim($fields[1]);
                    $temp[$tc_id][$agent_id]='';
                }
            }                
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";            
            foreach($temp as $id => $rec){@$arr_taxa[$id] = sizeof($rec);} unset($temp); 
            if($num_rows < $batch)break; 
        }//while(true)           
        
        self::save_json_to_txt($arr_taxa,"tpm_content_partners"); unset($arr_taxa);        
    }    
    
    public function get_outlinks_count($param_id=NULL)
    {
        $arr_taxa=array();
        $batch=500000; $start_limit=0;
        while(true)
        {       
            print"\n outlinks [8 of 10] $start_limit \n";                        
            $sql="SELECT he.taxon_concept_id tc_id, h.agent_id FROM hierarchies h JOIN hierarchy_entries he ON h.id = he.hierarchy_id WHERE ( he.source_url != '' || ( h.outlink_uri != '' AND he.identifier != '' ) )";  
            if($param_id)$sql .= " AND he.taxon_concept_id = $param_id ";
            $sql .= " limit $start_limit, $batch ";                        
            $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0; $temp=array();
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);                                        
                    $tc_id      = trim($fields[0]);
                    $agent_id   = trim($fields[1]);
                    $temp[$tc_id][$agent_id]='';
                }
            }                
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";            
            foreach($temp as $id => $rec){@$arr_taxa[$id] = sizeof($rec);} unset($temp); 
            if($num_rows < $batch)break; 
        }//while(true)           
        
        self::save_json_to_txt($arr_taxa,"tpm_outlinks"); unset($arr_taxa);        
    }
    
    public function get_common_names_count($param_id=NULL)
    {
        $arr_taxa=array();
        $batch=500000; $start_limit=0;        
        while(true)
        {   
            print"\n common_names and its providers [4 of 10] $start_limit \n";            
            $sql="SELECT he.taxon_concept_id tc_id, s.name_id, s.hierarchy_id h_id FROM hierarchy_entries he JOIN synonyms s ON he.id = s.hierarchy_entry_id WHERE s.synonym_relation_id in (" . self::synonym_relations_id("common name") . "," . self::synonym_relations_id("genbank common name") . ")";
            if($param_id)$sql .= " and he.taxon_concept_id = $param_id ";
            $sql .= " limit $start_limit, $batch ";                        
            $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0; $temp=array(); $temp2=array();
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);                                        
                    $tc_id      = trim($fields[0]);
                    $name_id    = trim($fields[1]);
                    $h_id       = trim($fields[2]);
                    $temp[$tc_id][$name_id]='';
                    $temp2[$tc_id][$h_id]='';
                }
            }                
            fclose($FILE);unlink($outfile);            
            print "\n num_rows: $num_rows";            
            foreach($temp as $id => $rec)   {@$arr_taxa[$id]['cn_cnt'] = sizeof($rec);}            
            foreach($temp2 as $id => $rec)  {@$arr_taxa[$id]['cn_prov'] = sizeof($rec);}            
            unset($temp); unset($temp2); 
            if($num_rows < $batch)break;             
        }//while(true)           
        
        self::save_json_to_txt($arr_taxa,"tpm_common_names"); unset($arr_taxa);        
    }

    public function get_synonyms_count($param_id=NULL)
    {
        $arr_taxa=array();
        $batch=500000; $start_limit=0;        
        while(true)
        {                   
            print"\n synonyms and its providers [5 of 10] $start_limit \n";            
            $sql="SELECT he.taxon_concept_id tc_id, s.name_id, s.hierarchy_id h_id FROM hierarchy_entries he JOIN synonyms s ON he.id = s.hierarchy_entry_id JOIN hierarchies h ON s.hierarchy_id = h.id WHERE s.synonym_relation_id not in (" . self::synonym_relations_id("common name") . "," . self::synonym_relations_id("genbank common name") . ") and h.browsable=1";
            if($param_id)$sql .= " and he.taxon_concept_id = $param_id ";
            $sql .= " limit $start_limit, $batch ";                        
            $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0; $temp=array(); $temp2=array();
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);                                            
                    $tc_id      = trim($fields[0]);
                    $name_id    = trim($fields[1]);
                    $h_id       = trim($fields[2]);
                    $temp[$tc_id][$name_id]='';
                    $temp2[$tc_id][$h_id]='';                                
                }
            }                
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";                                    
            foreach($temp as $id => $rec) {@$arr_taxa[$id]['syn_cnt'] = sizeof($rec);}            
            foreach($temp2 as $id => $rec){@$arr_taxa[$id]['syn_prov'] = sizeof($rec);}                        
            unset($temp); unset($temp2); 
            if($num_rows < $batch)break; 
        }//while(true)                           
        
        self::save_json_to_txt($arr_taxa,"tpm_synonyms"); unset($arr_taxa);        
    }
        
    function synonym_relations_id($label)
    {
        $result = $this->mysqli_slave->query("SELECT id FROM synonym_relations WHERE label = '$label' ");
        $row=$result->fetch_assoc(); return $row['id'];                        
    }            

    public function get_data_objects_count($param_id=NULL)
    {        
        $arr_taxa=array();
        $image_id       = DataType::find_by_label('Image');
        $sound_id       = DataType::find_by_label('Sound');
        $text_id        = DataType::find_by_label('Text');
        $video_id       = DataType::find_by_label('Video');
        $gbif_image_id  = DataType::find_by_label('GBIF Image');
        $iucn_id        = DataType::find_by_label('IUCN');
        $flash_id       = DataType::find_by_label('Flash');
        $youtube_id     = DataType::find_by_label('YouTube');
        
        $data_type_label[$image_id]      ='image';
        $data_type_label[$sound_id]      ='sound';
        $data_type_label[$text_id]       ='text';
        $data_type_label[$video_id]      ='video';
        $data_type_label[$gbif_image_id] ='gbif';
        $data_type_label[$iucn_id]       ='iucn';
        $data_type_label[$flash_id]      ='flash';
        $data_type_label[$youtube_id]    ='youtube';                        
        
        $trusted_id     = Vetted::find("trusted");
        $untrusted_id   = Vetted::find("untrusted");
        $unreviewed_id  = Vetted::find("unknown");                
        
        $batch=500000; $start_limit=0;
        while(true)
        {       
            print"\n dataObjects, its infoItems, its references [7 of 10] $start_limit \n";            
            $sql="SELECT dotc.taxon_concept_id tc_id, do.data_type_id, doii.info_item_id, dor.ref_id, do.description, do.vetted_id FROM data_objects_taxon_concepts dotc JOIN data_objects do ON dotc.data_object_id = do.id Left Join data_objects_info_items doii ON do.id = doii.data_object_id Left Join data_objects_refs dor ON do.id = dor.data_object_id WHERE do.published=1 and do.visibility_id=".Visibility::find("visible");
            if($param_id)$sql .= " and (dotc.taxon_concept_id = $param_id) ";
            $sql .= " limit $start_limit, $batch ";                        
            $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0; $temp=array(); $temp2=array(); 
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);                                            
                    $tc_id          = trim($fields[0]);
                    $data_type_id   = trim($fields[1]);
                    $info_item_id   = trim($fields[2]);
                    $ref_id         = trim($fields[3]);
                    $description    = trim($fields[4]);
                    $vetted_id      = trim($fields[5]);
                    
                    $label = @$data_type_label[$data_type_id];
                    $words_count = str_word_count(strip_tags($description),0);            
                    
                    @$arr_taxa[$tc_id][$label]['total']++;
                    @$arr_taxa[$tc_id][$label]['total_w']+= $words_count;            
                    
                    if    ($vetted_id == $trusted_id)    
                    {
                        @$arr_taxa[$tc_id][$label]['t']++;
                        @$arr_taxa[$tc_id][$label]['t_w']+= $words_count;                
                    }
                    elseif($vetted_id == $untrusted_id)  
                    {
                        @$arr_taxa[$tc_id][$label]['ut']++;
                        @$arr_taxa[$tc_id][$label]['ut_w']+= $words_count;                
                    }
                    elseif($vetted_id == $unreviewed_id)    
                    {
                        @$arr_taxa[$tc_id][$label]['ur']++;
                        @$arr_taxa[$tc_id][$label]['ur_w']+= $words_count;
                    }                                    
                    
                    $temp[$tc_id][$info_item_id]='';           
                    $temp2[$tc_id][$ref_id]='';           
                }
            }                
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";
            foreach($temp as $id => $rec)   {@$arr_taxa[$id]['ii'] = sizeof($rec);}                       
            foreach($temp2 as $id => $rec)  {@$arr_taxa[$id]['ref'] = sizeof($rec);}                                   
            unset($temp); unset($temp2); 
            if($num_rows < $batch)break;             
        }//while(true)        
        
        self::save_json_to_txt($arr_taxa,"tpm_data_objects"); unset($arr_taxa);        
    }            

    function get_arr_from_json($filename)
    {
        $filename = DOC_ROOT . "tmp/" . $filename . ".txt";
        $fp = fopen($filename,"r");            
        $json = fread($fp, filesize($filename));
        fclose($fp); unlink($filename);
        return json_decode($json,true);                
    }
    
    public function save_to_text_file($arr_taxa)
    {
        $taxon_id_list = array();
        /* do this if u only want a list of concepts with some kind of data in it
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_data_objects);
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_bhl);
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_content_partners); 
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_outlinks);
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_common_names);
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_synonyms);
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_biomedical_terms); 
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_gbif);
        $taxon_id_list = self::get_taxon_id_list($taxon_id_list, $arr_user_submitted_text);                
        */
        
        $arr_google_stats   = self::get_arr_from_json("tpm_google_stats");
        $arr_bhl            = self::get_arr_from_json("tpm_BHL");
        $arr_biomed         = self::get_arr_from_json("tpm_biomedical_terms");
        $arr_gbif           = self::get_arr_from_json("tpm_GBIF");
        $arr_addedtext      = self::get_arr_from_json("tpm_user_added_text");
        $arr_partners       = self::get_arr_from_json("tpm_content_partners");
        $arr_outlinks       = self::get_arr_from_json("tpm_outlinks");
        $arr_comnames       = self::get_arr_from_json("tpm_common_names");
        $arr_synonyms       = self::get_arr_from_json("tpm_synonyms");
        $arr_objects        = self::get_arr_from_json("tpm_data_objects");                
        
        // do this if u want to get all concepts:
        $taxon_id_list = self::get_all_taxon_concepts($taxon_id_list);                       
        
        $taxon_id_list = array_keys($taxon_id_list);                
        $filename = DOC_ROOT . "tmp/taxon_concept_metrics.txt"; $fp = fopen($filename,"w");            
        $str=""; $i=0; $total=sizeof($taxon_id_list);
        foreach($taxon_id_list as $id)
        {            
            $i++; $objects=array("image","text","video","sound","flash","youtube","iucn");//"gbif",            
            $str .= $id . "\t";  
            foreach($objects as $object)
            {
                $str .=   @$arr_objects[$id]["$object"]['total']     . "\t" 
                        . @$arr_objects[$id]["$object"]['t']         . "\t"  /* trusted */
                        . @$arr_objects[$id]["$object"]['ut']        . "\t"  /* untrusted */
                        . @$arr_objects[$id]["$object"]['ur']        . "\t"  /* unreviewed */
                        . @$arr_objects[$id]["$object"]['total_w']   . "\t"  /* w = words */
                        . @$arr_objects[$id]["$object"]['t_w']       . "\t" 
                        . @$arr_objects[$id]["$object"]['ut_w']      . "\t" 
                        . @$arr_objects[$id]["$object"]['ur_w']      . "\t" ;
            }                        
            
            /*
            $str .= @$arr_bhl[$id]['bhl']          . "\t";            
            $str .= @$arr_partners[$id]['cp']           . "\t";
            $str .= @$arr_outlinks[$id]['outl']         . "\t";
            $str .= @$arr_gbif[$id]['gbif']         . "\t";
            $str .= @$arr_biomed[$id]['biomed']       . "\t";
            */
            
            $str .= @$arr_objects[$id]["ref"]           . "\t";                        
            $str .= @$arr_objects[$id]["ii"]            . "\t"; /* ii = info items */   
            $str .= @$arr_bhl[$id]                      . "\t";            
            $str .= @$arr_partners[$id]                 . "\t";
            $str .= @$arr_outlinks[$id]                 . "\t";
            $str .= @$arr_gbif[$id]                     . "\t";
            $str .= @$arr_biomed[$id]                   . "\t";
            $str .= @$arr_addedtext[$id]["ust_cnt"]     . "\t"; /* cnt = count */                       
            $str .= @$arr_addedtext[$id]["ust_prov"]    . "\t"; /* prov = providers */
            $str .= @$arr_comnames[$id]["cn_cnt"]       . "\t";                                    
            $str .= @$arr_comnames[$id]["cn_prov"]      . "\t";                                                
            $str .= @$arr_synonyms[$id]["syn_cnt"]      . "\t";                                                
            $str .= @$arr_synonyms[$id]["syn_prov"]     . "\t";             
            $str .= @$arr_google_stats[$id]["pv"]       . "\t"; /* pv = page_views */
            $str .= @$arr_google_stats[$id]["upv"]      . "\t"; /* upv = unique_page_views */            
            $str .= "\n";                                                
            if($i % 500000 == 0)
            {
                print"\n writing... $i of $total ";
                fwrite($fp,$str);
                $str="";                
            }            
        }
        print"\n writing... $i of $total "; fwrite($fp,$str); fclose($fp);
    }
    
    function save_to_table()
    {
        $filename = DOC_ROOT . "tmp/taxon_concept_metrics.txt";                
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS taxon_concept_metrics_tmp LIKE taxon_concept_metrics");
        $this->mysqli->delete("TRUNCATE TABLE taxon_concept_metrics_tmp");
        $this->mysqli->load_data_infile($filename, "taxon_concept_metrics_tmp");    
        $this->mysqli->update("RENAME TABLE taxon_concept_metrics TO taxon_concept_metrics_swap,
                                            taxon_concept_metrics_tmp TO taxon_concept_metrics,
                                            taxon_concept_metrics_swap TO taxon_concept_metrics_tmp");
    }
    
    function get_taxon_id_list($taxon_id_list, $arr)
    {
        if($arr)
        {
            foreach($arr as $id => $rec){$taxon_id_list[$id]='';}
        }
        return $taxon_id_list;        
    }        
    
    function get_all_taxon_concepts($taxon_id_list)
    {
        $batch=500000; $start_limit=0;        
        while(true)
        {                   
            print"\n get_all_taxon_concepts $start_limit \n";                        
            $sql="SELECT tc.id FROM taxon_concepts tc WHERE tc.published = 1 AND tc.supercedure_id = 0";
            //$sql.=" and tc.id in (1,2,3,4,5,6,206692,218284)";//debug
            $sql .= " limit $start_limit, $batch ";                        
            $outfile = $this->mysqli_slave->SELECT_into_outfile($sql);
            $start_limit += $batch;
            $FILE = fopen($outfile, "r");
            $num_rows=0;
            while(!feof($FILE))
            {
                if($line = fgets($FILE))
                {
                    $num_rows++; $line = trim($line); $fields = explode("\t", $line);                                            
                    $tc_id      = trim($fields[0]);
                    $taxon_id_list[$tc_id]='';                                
                }
            }                
            fclose($FILE);unlink($outfile);
            print "\n num_rows: $num_rows";                                    
            if($num_rows < $batch)break; 
        }//while(true)                           
        return $taxon_id_list;                
    }

}
?>