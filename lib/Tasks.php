<?php
namespace php_active_record;

class Tasks
{
    // public static function clean_names()
    // {
    //     $mysqli =& $GLOBALS['mysqli_connection'];
    //     $mysqli->begin_transaction();
    //     
    //     $mysqli->delete("DELETE FROM clean_names");
    //     
    //     $result = $mysqli->query("SELECT MAX(id) as max FROM names");
    //     $row = $result->fetch_assoc();
    //     $max = $row["max"];
    //     $start = 1;
    //     $interval = 50000;
    //     while($start < $max)
    //     {
    //         debug($start);
    //         $result = $mysqli->query("SELECT id, string FROM names WHERE id BETWEEN $start AND ".($start+$interval-1));
    //         while($result && $row=$result->fetch_assoc())
    //         {
    //             $id = $row["id"];
    //             $string = $row["string"];
    //             $clean_name = Functions::clean_name($string);
    //             
    //             //CleanName::insert($id, $clean_name);
    //             
    //             $query = "INSERT INTO clean_names VALUES ($id,'".$mysqli->escape($clean_name)."')";
    //             $mysqli->insert($query);
    //         }
    //         flush();
    //         $start += $interval;
    //     }
    //     
    //     
    //     $mysqli->end_transaction();
    // }
    
    function canonical_forms()
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        $mysqli->begin_transaction();
        $mysqli->delete("DELETE FROM canonical_forms");
        
        $mysqli->update("UPDATE names SET canonical_form_id=0");
        
        $result = $mysqli->query("SELECT MAX(id) as max FROM names");
        $row = $result->fetch_assoc();
        $max = $row["max"];
        $start = 1;
        $interval = 50000;
        while($start < $max)
        {
            debug($start);
            $result = $mysqli->query("SELECT id, string FROM names WHERE id BETWEEN $start AND ".($start+$interval-2)." AND canonical_form_verified!=0");
            while($result && $row=$result->fetch_assoc())
            {
                $id = $row["id"];
                $string = $row["string"];
                $canonical_form_id = $row["id"];
                $canonical_form_verified = $row["id"];
                
                $canonical_form = "";
                if($canonical_form_verified)
                {
                    $result2 = $mysqli->query("SELECT string FROM canonical_forms WHERE id=$canonical_form_id");
                    if($result2 && $row2=$result2->fetch_assoc())
                    {
                        $canonical_form = $row2["string"];
                    }
                }
                
                if(!$canonical_form) $canonical_form = Functions::canonical_form($string);
                
                if(@!$canonical_form_ids[$canonical_form])
                {
                    $result2 = $mysqli->query("SELECT id FROM canonical_forms WHERE string='".$mysqli->escape($canonical_form)."'");
                    if($result2 && $row2=$result2->fetch_assoc())
                    {
                        $canonical_form_ids[$canonical_form] = $row2["id"];
                    }else
                    {
                        $result2 = $mysqli->insert("INSERT INTO canonical_forms VALUES (NULL,'".$mysqli->escape($canonical_form)."')");
                        $canonical_form_ids[$canonical_form] = $mysqli->insert_id;
                    }
                }
                
                $query = "UPDATE names SET canonical_form_id ($id,'".$mysqli->escape($canonical_form)."')";
                $mysqli->update($query);
            }
            flush();
            $start += $interval;
        }
        
        $mysqli->end_transaction();
    }
    
    public static function update_taxon_concept_names($taxon_concept_id)
    {
        if(!$taxon_concept_id) return false;
        $mysqli =& $GLOBALS['db_connection'];
        
        //$result = $mysqli->query("SELECT id FROM taxon_concepts WHERE id=$taxon_concept_id");
        //if(!($result && $row=$result->fetch_assoc())) return false;
        
        $started_new_transaction = false;
        if(!$mysqli->in_transaction())
        {
            $mysqli->begin_transaction();
            $started_new_transaction = true;
        }
        
        $name_ids = array();
        $matching_ids = array();
        $hierarchy_entry_ids = array();
        
        $result = $mysqli->query("
        (SELECT id, name_id, 'preferred' as type FROM hierarchy_entries he WHERE taxon_concept_id=$taxon_concept_id AND ((he.published=1 AND he.visibility_id=". Visibility::visible()->id .") OR (he.published=0 AND he.visibility_id=". Visibility::preview()->id .")))
        UNION
        (SELECT s.hierarchy_entry_id, s.name_id, 'synonym' as type
        FROM hierarchy_entries he
        JOIN synonyms s ON (he.id=s.hierarchy_entry_id)
        WHERE he.taxon_concept_id=$taxon_concept_id
        AND s.language_id=0
        AND s.synonym_relation_id!=".SynonymRelation::find_or_create_by_translated_label('genbank common name')->id."
        AND s.synonym_relation_id!=".SynonymRelation::find_or_create_by_translated_label('common name')->id."
        AND s.synonym_relation_id!=".SynonymRelation::find_or_create_by_translated_label('blast name')->id."
        AND s.synonym_relation_id!=".SynonymRelation::find_or_create_by_translated_label('genbank acronym')->id."
        AND s.synonym_relation_id!=".SynonymRelation::find_or_create_by_translated_label('acronym')->id."
        AND ((he.published=1 AND he.visibility_id=". Visibility::visible()->id .") OR (he.published=0 AND he.visibility_id=". Visibility::preview()->id .")))");
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];
            $name_id = $row["name_id"];
            $type = $row["type"];
            
            $name_ids[$name_id] = 1;
            $matching_ids[$name_id][$id] = $type;
            $hierarchy_entry_ids[$id] = 1;
        }
        
        if($name_ids)
        {
            //This makes sure we have a scientific name, gets the canonicalFormID
            $result = $mysqli->query("SELECT n_match.id FROM names n JOIN canonical_forms cf ON (n.canonical_form_id=cf.id) JOIN names n_match ON (cf.id=n_match.canonical_form_id) WHERE n.id IN (".implode(",",array_keys($name_ids)).") AND n_match.string=cf.string");
            while($result && $row=$result->fetch_assoc())
            {
                //add the canonicalForm to the taxon_concept
                //only adding canonicalForm - not all names with the same canonicalForm - this might be changed in the future to be more inclusive
                $matching_ids[$row["id"]][0] = 1;
                $name_ids[$row["id"]] = 1;
            }
        }
        
        $mysqli->delete("DELETE FROM taxon_concept_names WHERE taxon_concept_id=$taxon_concept_id");
        
        /* Insert the scientific names */
        foreach($matching_ids as $name_id => $arr)
        {
            foreach($arr as $hierarchy_entry_id => $type)
            {
                $preferred = 0;
                if($hierarchy_entry_id && $type=="preferred") $preferred = 1;
                $mysqli->insert("INSERT IGNORE INTO taxon_concept_names (taxon_concept_id, name_id, source_hierarchy_entry_id, language_id, vern, preferred) VALUES ($taxon_concept_id, $name_id, $hierarchy_entry_id, 0, 0, $preferred)");
            }
        }
        
        
        
        
        
        
        /*
        SELECT he.published, he.visibility, s.id, s.hierarchy_entry_id, s.name_id, s.language_id, s.preferred, s.vetted_id FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) WHERE he.taxon_concept_id=206776 AND s.language_id!=0 AND (s.synonym_relation_id=9 OR s.synonym_relation_id=2)
        */
        
        $common_names = array();
        $preferred_in_language = array();
        $result = $mysqli->query("SELECT he.published, he.visibility_id, s.id, s.hierarchy_id, s.hierarchy_entry_id, s.name_id, s.language_id, s.preferred, s.vetted_id FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) JOIN vetted v ON (s.vetted_id=v.id) WHERE he.taxon_concept_id=$taxon_concept_id AND s.language_id!=0 AND (s.synonym_relation_id=".SynonymRelation::genbank_common_name()->id." OR s.synonym_relation_id=".SynonymRelation::common_name()->id.") ORDER BY s.language_id, (s.hierarchy_id=".Hierarchy::contributors()->id.") DESC, v.view_order ASC, s.preferred DESC, s.id DESC");
        while($result && $row=$result->fetch_assoc())
        {
            // skipping Wikipedia common names entirely
            if($row['hierarchy_id'] == @Hierarchy::wikipedia()->id) continue;
            $curator_name = ($row['hierarchy_id'] == @Hierarchy::contributors()->id);
            $ubio_name = ($row['hierarchy_id'] == @Hierarchy::ubio()->id);
            if($curator_name || $ubio_name || $row['hierarchy_id'] == Hierarchy::contributors()->id || ($row['published'] == 1 && $row['visibility_id'] == Visibility::visible()->id))
            {
                $synonym_id = $row["id"];
                $hierarchy_entry_id = $row["hierarchy_entry_id"];
                $name_id = $row["name_id"];
                $language_id = $row["language_id"];
                $preferred = $row["preferred"];
                $vetted_id = $row["vetted_id"];
                $hierarchy_id = $row["hierarchy_id"];
                if(isset($preferred_in_language[$language_id])) $preferred = 0;
                if($preferred && $curator_name && ($vetted_id == Vetted::trusted()->id || $vetted_id == Vetted::unknown()->id))
                {
                    $preferred_in_language[$language_id] = 1;
                }else $preferred = 0;
                $common_names[] = array(
                    'synonym_id' => $synonym_id,
                    'language_id' => $language_id,
                    'name_id' => $name_id,
                    'hierarchy_entry_id' => $hierarchy_entry_id,
                    'preferred' => $preferred,
                    'vetted_id' => $vetted_id,
                    'is_curator_name' => $curator_name);
            }
        }
        
        // if there was no preferred name
        foreach($common_names as $key => $arr)
        {
            if(@!$preferred_in_language[$arr['language_id']] &&
              ($arr['vetted_id'] == Vetted::trusted()->id || $arr['vetted_id'] == Vetted::unknown()->id))
            {
                $common_names[$key]['preferred'] = 1;
                $preferred_in_language[$arr['language_id']] = 1;
            }
        }
        
        
        /* Insert the common names */
        foreach($common_names as $key => $arr)
        {
            $synonym_id = $arr['synonym_id'];
            $language_id = $arr['language_id'];
            $name_id = $arr['name_id'];
            $hierarchy_entry_id = $arr['hierarchy_entry_id'];
            $preferred = $arr['preferred'];
            $vetted_id = $arr['vetted_id'];
            // echo "INSERT IGNORE INTO taxon_concept_names (taxon_concept_id, name_id, source_hierarchy_entry_id, language_id, vern, preferred, vetted_id, synonym_id) VALUES ($taxon_concept_id, $name_id, $hierarchy_entry_id, $language_id, 1, $preferred, $vetted_id, $synonym_id)\n";
            $mysqli->insert("INSERT IGNORE INTO taxon_concept_names (taxon_concept_id, name_id, source_hierarchy_entry_id, language_id, vern, preferred, vetted_id, synonym_id) VALUES ($taxon_concept_id, $name_id, $hierarchy_entry_id, $language_id, 1, $preferred, $vetted_id, $synonym_id)");
        }
        
        unset($matching_ids);
        unset($common_names);
        unset($name_ids);
        unset($hierarchy_entry_ids);
        
        if($started_new_transaction) $mysqli->end_transaction();
    }
    
    // public static function update_taxon_concept_names($taxon_concept_id)
    //     {
    //         if(!$taxon_concept_id) return false;
    //         $mysqli =& $GLOBALS['mysqli_connection'];
    //         
    //         //$eol_curator_hierarchy_id = Hierarchy::find_by_label('Encyclopedia of Life Curators');
    //         //$ubio_hierarchy_id = Hierarchy::find_by_label('uBio Namebank');
    //         
    //         $mysqli->delete("DELETE FROM taxon_concept_names WHERE taxon_concept_id=$taxon_concept_id");
    //         
    //         $result = $mysqli->query("SELECT id, name_id FROM hierarchy_entries WHERE taxon_concept_id=$taxon_concept_id");
    //         while($result && $row=$result->fetch_assoc())
    //         {
    //             $id = $row["id"];
    //             $name_id = $row["name_id"];
    //             $mysqli->insert("INSERT INTO taxon_concept_names VALUES ($taxon_concept_id, $name_id, $id, 0, 0, 1)");
    //         }
    //         
    //         $result = $mysqli->query("SELECT he.id, s.name_id, s.language_id, s.preferred FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) WHERE he.taxon_concept_id=$taxon_concept_id");
    //         while($result && $row=$result->fetch_assoc())
    //         {
    //             $id = $row["id"];
    //             $name_id = $row["name_id"];
    //             $language_id = $row["language_id"];
    //             $preferred = $row["preferred"];
    //             
    //             $vern = 0;
    //             if($language_id && $language_id != Language::insert("Scientific Name") && $language_id != Language::insert("Operational Taxonomic Unit")) $vern = 1;
    //             
    //             $mysqli->insert("INSERT INTO taxon_concept_names VALUES ($taxon_concept_id, $name_id, $id, $language_id, $vern, $preferred)");
    //         }
    //     }
    
    
    
    // this method will use its own transactions so commit any open transactions before using
    public static function rebuild_nested_set($hierarchy_id)
    {
        if(!$hierarchy_id) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        
        $mysqli->update("UPDATE hierarchy_entries SET lft=0, rgt=0, depth=0 WHERE hierarchy_id=$hierarchy_id");
        
        $result = $mysqli->query("SELECT id FROM hierarchy_entries WHERE parent_id=0 AND hierarchy_id=$hierarchy_id");
        $current_value = 0;
        while($result && $row=$result->fetch_assoc())
        {
            $current_value = self::nested_set_depth_first_assign($row["id"], 0, 0, $current_value);
        }
        
        $mysqli->end_transaction();
    }
    
    function nested_set_depth_first_assign($id, $parent_id, $depth, $current_value)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->update("UPDATE hierarchy_entries SET lft=$current_value, depth=$depth WHERE id=$id");
        $current_value++;
        
        $result = $mysqli->query("SELECT id FROM hierarchy_entries WHERE parent_id=$id");
        while($result && $row=$result->fetch_assoc())
        {
            $current_value = self::nested_set_depth_first_assign($row["id"], $id, $depth+1, $current_value);
        }
        
        $mysqli->update("UPDATE hierarchy_entries SET rgt=$current_value WHERE id=$id");
        $current_value++;
        
        return $current_value;
    }
    
    public static function get_descendant_objects($taxon_concept_id)
    {
        /*
        $solr = new SolrAPI(SOLR_SERVER, 'data_objects');
        $main_query = "ancestor_id:$taxon_concept_id&fl=data_object_id";
        $total_results = $solr->count_results($main_query);
        
        $data_object_ids = array();
        $solr_iteration_size = 10000;
        for($i=0 ; $i<$total_results ; $i += $solr_iteration_size)
        {
            $this_query = $main_query . "&rows=".$solr_iteration_size."&start=$i";
            $entries = $solr->get_results($this_query);
            foreach($entries as $entry)
            {
                $data_object_ids[$entry->data_object_id] = 1;
            }
        }
        */
        
        $data_object_ids = array();
        $result = $GLOBALS['mysqli_connection']->query("(SELECT dohe.data_object_id FROM hierarchy_entries he JOIN hierarchy_entries_flattened hef ON (he.id=hef.ancestor_id) JOIN hierarchy_entries he_descendants ON (hef.hierarchy_entry_id=he_descendants.id) JOIN hierarchy_entries he_concept ON (he_descendants.taxon_concept_id=he_concept.taxon_concept_id) JOIN data_objects_hierarchy_entries dohe ON (he_concept.id=dohe.hierarchy_entry_id) WHERE he.taxon_concept_id=$taxon_concept_id) UNION (SELECT dohe.data_object_id FROM hierarchy_entries he JOIN data_objects_hierarchy_entries dohe ON (he.id=dohe.hierarchy_entry_id) WHERE he.taxon_concept_id=$taxon_concept_id)");
        while($result && $row=$result->fetch_assoc())
        {
            $data_object_ids[$row['data_object_id']] = 1;
        }
        
        $result = $GLOBALS['mysqli_connection']->query("(SELECT dohe.data_object_id FROM hierarchy_entries he JOIN hierarchy_entries_flattened hef ON (he.id=hef.ancestor_id) JOIN hierarchy_entries he_descendants ON (hef.hierarchy_entry_id=he_descendants.id) JOIN hierarchy_entries he_concept ON (he_descendants.taxon_concept_id=he_concept.taxon_concept_id) JOIN curated_data_objects_hierarchy_entries dohe ON (he_concept.id=dohe.hierarchy_entry_id) WHERE he.taxon_concept_id=$taxon_concept_id) UNION (SELECT dohe.data_object_id FROM hierarchy_entries he JOIN curated_data_objects_hierarchy_entries dohe ON (he.id=dohe.hierarchy_entry_id) WHERE he.taxon_concept_id=$taxon_concept_id)");
        while($result && $row=$result->fetch_assoc())
        {
            $data_object_ids[$row['data_object_id']] = 1;
        }
        
        $result = $GLOBALS['mysqli_connection']->query("(SELECT udo.data_object_id FROM hierarchy_entries he JOIN hierarchy_entries_flattened hef ON (he.id=hef.ancestor_id) JOIN hierarchy_entries he_concept ON (hef.hierarchy_entry_id=he_concept.id) JOIN users_data_objects udo ON (he_concept.taxon_concept_id=udo.taxon_concept_id) WHERE he.taxon_concept_id=$taxon_concept_id) UNION (SELECT udo.data_object_id FROM users_data_objects udo WHERE udo.taxon_concept_id=$taxon_concept_id)");
        while($result && $row=$result->fetch_assoc())
        {
            $data_object_ids[$row['data_object_id']] = 1;
        }
        
        return array_keys($data_object_ids);
    }
}

?>
