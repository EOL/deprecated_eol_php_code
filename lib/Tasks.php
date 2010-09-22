<?php

class Tasks extends MysqlBase
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
    
    public static function compare_hierarchies($hierarchy_id, $compare_to_hierarchy_id, $complete_hierarchy = true)
    {
        if(!$hierarchy_id) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        
        $result = $mysqli->query("SELECT id, taxon_concept_id, ancestry FROM hierarchy_entries WHERE hierarchy_id=$hierarchy_id AND taxon_concept_id>1299534");
        //$result = $mysqli->query("SELECT id, taxon_concept_id, ancestry FROM hierarchy_entries WHERE hierarchy_id=$hierarchy_id AND id=16098160");
        //$result = $mysqli->query("SELECT id, taxon_concept_id, ancestry FROM hierarchy_entries WHERE hierarchy_id=$hierarchy_id AND id=20192318");
        //$result = $mysqli->query("SELECT id, taxon_concept_id, ancestry FROM hierarchy_entries WHERE hierarchy_id=$hierarchy_id AND taxon_concept_id=2202401");
        
        
        //$result = $mysqli->query("SELECT id, taxon_concept_id, ancestry FROM hierarchy_entries WHERE hierarchy_id=$hierarchy_id AND id>=26000000 AND id<26400000");
        
        
        $i = 0;
        while($result && $row=$result->fetch_assoc())
        {
            if($i%1000==0) echo "$i: ".Functions::time_elapsed().": ".memory_get_usage()."\n";
            $i++;
            
            $id = $row["id"];
            $taxon_concept_id = $row["taxon_concept_id"];
            $ancestry = $row["ancestry"];
            if(preg_match("/^1769426(\||$)/", $ancestry)) continue;
            
            if($i%100==0) debug("NOW COMPARING: $id - ".Functions::time_elapsed());
            
            $concept1 = new TaxonConcept($taxon_concept_id);
            $entry1 = new HierarchyEntry($id);
            
            if(!array_diff($concept1->name_ids(), Name::unassigned_ids())) continue;
            
            $result2 = $mysqli->query("SELECT id FROM hierarchy_entries WHERE taxon_concept_id=$concept1->id AND he.hierarchy_id=$compare_to_hierarchy_id");
            if($result2 && $row2=$result2->fetch_assoc()) continue;
            
            if($canonical_form_id = $entry1->name()->canonical_form_id) $result2 = $mysqli->query("(SELECT DISTINCT he.taxon_concept_id FROM names n JOIN hierarchy_entries he ON n.id=he.name_id WHERE n.canonical_form_id=$canonical_form_id AND he.hierarchy_id=$compare_to_hierarchy_id AND he.id!=$id) UNION DISTINCT (SELECT DISTINCT he.taxon_concept_id FROM names n JOIN synonyms s ON (n.id=s.name_id) JOIN hierarchy_entries he ON (s.hierarchy_entry_id=he.id) WHERE n.canonical_form_id=$canonical_form_id AND he.hierarchy_id=$compare_to_hierarchy_id AND he.id!=$id)");
            else $result2 = $mysqli->query("(SELECT DISTINCT he.taxon_concept_id FROM hierarchy_entries he WHERE he.name_id=$entry1->name_id AND he.hierarchy_id=$compare_to_hierarchy_id AND he.id!=$id) UNION DISTINCT (SELECT DISTINCT he.taxon_concept_id FROM synonyms s JOIN hierarchy_entries he ON (s.hierarchy_entry_id=he.id) WHERE s.name_id=$entry1->name_id AND he.hierarchy_id=$compare_to_hierarchy_id AND he.id!=$id)");
            
            if(!($result2 && $result2->num_rows)) continue;
            
            
            $hierarchy_entries1 = $concept1->mock_hierarchy_entries();
            $all_names1 = $concept1->mock_all_names();
            $max_score = 0;
            $max_score_id = 0;
            
            while($result2 && $row2=$result2->fetch_assoc())
            {
                $concept2 = new TaxonConcept($row2["taxon_concept_id"]);
                
                if($concept1->id == $concept2->id) continue;
                if(!array_diff($concept2->name_ids(), Name::unassigned_ids())) continue;
                
                $hierarchy_entries2 = $concept2->mock_hierarchy_entries();
                $all_names2 = $concept2->mock_all_names();
                
                $score = NamesFunctions::compare_taxon_concepts($concept1, $hierarchy_entries1, $all_names1, $concept2, $hierarchy_entries2, $all_names2, $complete_hierarchy);
                
                $mysqli->insert("INSERT INTO taxon_concept_relationships VALUES ($concept1->id, $concept2->id, '', $score, '')");
                $mysqli->insert("INSERT INTO taxon_concept_relationships VALUES ($concept2->id, $concept1->id, '', $score, '')");
                
                if($score > $max_score)
                {
                    $max_score = $score;
                    $max_score_id = $concept2->id;
                }
                
                unset($concept2);
                unset($hierarchy_entries2);
                unset($all_names2);
            }
            if($result2 && $result2->num_rows) $result2->free();
            
            if($max_score >= MATCH_SCORE_THRESHOLD)
            {
                TaxonConcept::supercede_by_ids($concept1->id, $max_score_id);
                //self::update_taxon_concept_names(min($concept1->id, $concept2->id));
            }
            
            unset($entry1);
            unset($concept1);
            unset($hierarchy_entries1);
            unset($all_names1);
        }
        if($result && $result->num_rows) $result->free();
        $mysqli->end_transaction();
    }
    
    public static function update_taxon_concept_names($taxon_concept_id)
    {
        if(!$taxon_concept_id) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        //$result = $mysqli->query("SELECT id FROM taxon_concepts WHERE id=$taxon_concept_id");
        //if(!($result && $row=$result->fetch_assoc())) return false;
        
        $name_ids = array();
        $matching_ids = array();
        $hierarchy_entry_ids = array();
        
        $result = $mysqli->query("(SELECT id, name_id, 'preferred' as type FROM hierarchy_entries WHERE taxon_concept_id=$taxon_concept_id) UNION (SELECT s.hierarchy_entry_id, s.name_id, 'synonym' as type FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) WHERE he.taxon_concept_id=$taxon_concept_id AND s.language_id=0 AND s.synonym_relation_id!=".SynonymRelation::insert('genbank common name')." AND s.synonym_relation_id!=".SynonymRelation::insert('common name')." AND s.synonym_relation_id!=".SynonymRelation::insert('blast name')." AND s.synonym_relation_id!=".SynonymRelation::insert('genbank acronym')." AND s.synonym_relation_id!=".SynonymRelation::insert('acronym').")");
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];
            $name_id = $row["name_id"];
            $type = $row["type"];
            
            $name_ids[$name_id] = 1;
            $matching_ids[$name_id][$id] = $type;
            $hierarchy_entry_ids[$id] = 1;
        }
        $result->free();
        
        if(!$hierarchy_entry_ids) return false;
        
        
        
        
        // /* Lexical Group */
        // $result2 = $mysqli->query("SELECT l2.namebankID FROM lexicalGroups l1 JOIN lexicalGroups l2 ON (l1.lexicalGroupID=l2.lexicalGroupID) WHERE l1.namebankID IN (".implode(",",array_keys($namebankIDs)).") AND l1.lexicalGroupID!=0");
        // while($result2 && $row2=$result2->fetch_assoc())
        // {
        //     $matching_ids[$row2["name_id"]][0] = 1;
        //     $name_ids[$row2["name_id"]] = 1;
        // }
        // 
        // /* Basionym Group */
        // $result2 = $mysqli->query("SELECT b2.namebankID FROM basionymGroups b1 JOIN basionymGroups b2 ON (b1.basionymGroupID=b2.basionymGroupID) WHERE b1.namebankID IN (".implode(",",array_keys($namebankIDs)).") AND b1.basionymGroupID!=0");
        // while($result2 && $row2=$result2->fetch_assoc())
        // {
        //     $matching_ids[$row2["name_id"]][0] = 1;
        //     $name_ids[$row2["name_id"]] = 1;
        // }
        
        
        
        //This makes sure we have a scientific name, gets the canonicalFormID
        $result = $mysqli->query("SELECT n_match.id FROM names n JOIN canonical_forms cf ON (n.canonical_form_id=cf.id) JOIN names n_match ON (cf.id=n_match.canonical_form_id) WHERE n.id IN (".implode(",",array_keys($name_ids)).") AND n_match.string=cf.string");
        while($result && $row=$result->fetch_assoc())
        {
            //add the canonicalForm to the taxon_concept
            //only adding canonicalForm - not all names with the same canonicalForm - this might be changed in the future to be more inclusive
            $matching_ids[$row["id"]][0] = 1;
            $name_ids[$row["id"]] = 1;
        }
        $result->free();
        
        
        
        
        $mysqli->delete("DELETE FROM taxon_concept_names WHERE taxon_concept_id=$taxon_concept_id AND vern!=1");
        
        /* Insert the scientific names */
        foreach($matching_ids as $k => $v)
        {
            foreach($v as $k2 => $v2)
            {
                $preferred = 0;
                if($k2 && $v2=="preferred") $preferred = 1;
                $mysqli->insert("INSERT IGNORE INTO taxon_concept_names VALUES ($taxon_concept_id, $k, $k2, 0, 0, $preferred, NULL)");
            }
        }
        
        
        
        
        // /* Common Names */
        // $result = $mysqli->query("SELECT * FROM name_languages WHERE parent_name_id IN (".implode(",",array_keys($name_ids)).") AND language_id NOT IN (0,".Language::insert("Scientific Name").",".Language::insert("Operational Taxonomic Unit").")");
        // while($result && $row=$result->fetch_assoc())
        // {
        //     $name_id = $row["name_id"];
        //     $language_id = $row["language_id"];
        //     if($language_id==Language::insert("Common Name") || in_array($language_id, Language::unknown())) $language_id = Language::insert("Unknown");
        //     
        //     $preferred = 1;
        //     $result2 = $mysqli->query("SELECT * FROM taxon_concept_names WHERE taxon_concept_id=$taxon_concept_id AND source_hierarchy_entry_id=0 AND language_id=$language_id AND vern=1 AND preferred=1");
        //     if($result2 && $row2=$result2->fetch_assoc()) $preferred = 0;
        //     $mysqli->insert("INSERT IGNORE INTO taxon_concept_names VALUES ($taxon_concept_id, $name_id, 0, $language_id, 1, $preferred, NULL)");
        // }
        
        unset($matching_ids);
        unset($name_ids);
        unset($hierarchy_entry_ids);
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
        $result->free();
        
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
        $result->free();
        
        $mysqli->update("UPDATE hierarchy_entries SET rgt=$current_value WHERE id=$id");
        $current_value++;
        
        return $current_value;
    }
    
    
    
    
    
    /*
    public static function compare_hierarchies_old($hierarchy_id)
    {
        if(!$hierarchy_id) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        
        $result = $mysqli->query("SELECT id, hierarchy_id FROM hierarchy_entries WHERE hierarchy_id=$hierarchy_id");
        while($result && $row=$result->fetch_assoc())
        {
            $id = $row["id"];
            $mysqli->delete("DELETE FROM hierarchy_entry_relationships WHERE hierarchy_entry_id_1=$id OR hierarchy_entry_id_2=$id");
            $entry1 = new HierarchyEntry($id);
            
            if($canonical_form_id = $entry1->name()->canonical_form_id) $result2 = $mysqli->query("SELECT he.id, he.hierarchy_id FROM names n JOIN hierarchy_entries he ON n.id=he.name_id WHERE n.canonical_form_id=$canonical_form_id AND he.id!=$entry1->id");
            else $result2 = $mysqli->query("SELECT he.id, he.hierarchy_id FROM hierarchy_entries he WHERE he.name_id=$entry1->name_id AND he.id!=$entry1->id");
            
            while($result2 && $row2=$result2->fetch_assoc())
            {
                $entry2 = new HierarchyEntry($row2["id"]);
                
                $score = NamesFunctions::compare_hierarchy_entries($entry1, $entry2);
                
                if($score)
                {
                    $mysqli->insert("INSERT INTO hierarchy_entry_relationships VALUES ($entry1->id, $entry2->id, '', $score, '')");
                    $mysqli->insert("INSERT INTO hierarchy_entry_relationships VALUES ($entry2->id, $entry1->id, '', $score, '')");
                }
            }
            $result2->free();
        }
        
        $mysqli->end_transaction();
        
        self::merge_related_taxa($hierarchy_id);
    }
    
    public static function merge_related_taxa($hierarchy_id)
    {
        if(!$hierarchy_id) return false;
        $mysqli =& $GLOBALS['mysqli_connection'];
        
        $mysqli->begin_transaction();
        
        $query1 = "SELECT her.* FROM hierarchy_entries he JOIN hierarchy_entry_relationships her ON (he.id=her.hierarchy_entry_id_1) WHERE score>=".MATCH_SCORE_THRESHOLD;
        $query2 = "SELECT her.* FROM hierarchy_entries he JOIN hierarchy_entry_relationships her ON (he.id=her.hierarchy_entry_id_2) WHERE score>=".MATCH_SCORE_THRESHOLD;
        $result = $mysqli->query("($query1) UNION ($query2)");
        
        while($result && $row=$result->fetch_assoc())
        {
            $taxon_1 = new HierarchyEntry($row["hierarchy_entry_id_1"]);
            $taxon_2 = new HierarchyEntry($row["hierarchy_entry_id_2"]);
            
            
            if($taxon_1->taxon_concept_id != $taxon_2->taxon_concept_id)
            {
                $taxon_2->set_taxon_concept_id($taxon_1->taxon_concept_id);
                
                self::update_taxon_concept_names($taxon_1->taxon_concept_id);
            }
        }
        
        $mysqli->end_transaction();
    }
    */
}

?>