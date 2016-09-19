<?php
namespace php_active_record;

class HierarchyEntry extends ActiveRecord
{
    public static $belongs_to = array(
            array('name'),
            array('taxon_concept'),
            array('hierarchy'),
            array('rank'),
            array('parent', 'class_name' => 'HierarchyEntry', 'foreign_key' => 'parent_id')
        );

    public static $has_many = array(
            array('hierarchy_entries_refs'),
            array('references', 'through' => 'hierarchy_entries_refs'),
            array('agents_hierarchy_entries'),
            array('agents', 'through' => 'agents_hierarchy_entries'),
            array('synonyms')
        );

    public function split_from_concept_static($hierarchy_entry_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];

        $entry = HierarchyEntry::find($hierarchy_entry_id);
		echo "after getting the entry \n";
        if(!$entry || @!$entry->id) return null;

        $result = $mysqli->query("SELECT he2.id, he2.taxon_concept_id FROM hierarchy_entries he JOIN hierarchy_entries he2 USING (taxon_concept_id) WHERE he.id=$hierarchy_entry_id");
        if($result && $row=$result->fetch_assoc())
        {
            $count = $result->num_rows;
            // if there is only one member in the entry's concept there is no need to split it
            if($count > 1)
            {
                $mysqli->begin_transaction();
                $old_taxon_concept_id = $entry->taxon_concept_id;
                $taxon_concept_id = TaxonConcept::create()->id;

                $mysqli->update("UPDATE taxon_concepts SET published=$entry->published, vetted_id=$entry->vetted_id WHERE id=$taxon_concept_id");
                $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$taxon_concept_id WHERE id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE taxon_concept_names SET taxon_concept_id=$taxon_concept_id WHERE source_hierarchy_entry_id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE hierarchy_entries he JOIN random_hierarchy_images rhi ON (he.id=rhi.hierarchy_entry_id) SET rhi.taxon_concept_id=he.taxon_concept_id WHERE he.taxon_concept_id=$hierarchy_entry_id");
				
				echo "before calling update data \n";
				HierarchyEntry::update_data($entry, $old_taxon_concept_id, $taxon_concept_id);
                Tasks::update_taxon_concept_names(array($taxon_concept_id));
                $mysqli->end_transaction();
                return $taxon_concept_id;
            }
        }
        return null;
    }

    public static function update_data($entry, $old_taxon_concept_id, $new_taxon_concept_id){
		$mysqli =& $GLOBALS['mysqli_connection'];
		echo "update data function \n";
		$resource_id = $mysqli->select_value("SELECT id from resources where hierarchy_id = $entry->hierarchy_id");
		$batch_size = 100;
		$sparql_client = SparqlClient::connection();
		$result = $sparql_client->get_traits_from_virtuoso($old_taxon_concept_id, $resource_id);
		$traits = array();
		$predicates = array();
		foreach ($result as $row) {
			$traits[] = $row['trait']['value'];
			$predicates[] = $row['predicate']['value'];
		}
		for ($i=0; $i < count($traits); $i = $i + $batch_size) { 
			HierarchyEntry::update_data_split_move($entry->id, $old_taxon_concept_id, $new_taxon_concept_id, array_slice($traits, $i), array_slice($predicates, $i));
		}
    }

	public static function update_data_split_move($hierarchy_id, $old_page, $new_page, $traits, $predicates){
		//DB update
		// echo ("trait is: " . $trait . " and predicate is: " . $predicate . "\n");
		$mysqli =& $GLOBALS['mysqli_connection'];
		$mysqli->update("UPDATE data_point_uris SET taxon_concept_id=$new_page where uri IN ('" . implode("', '", $traits) ."') and taxon_concept_id = $old_page");
		//virtuoso update new format
		$sparql_client = SparqlClient::connection();
		$sparql_client->update_taxon_given_trait($traits, $predicates, $new_page, $old_page);
	}

    public static function move_to_concept_static($hierarchy_entry_id, $taxon_concept_id, $force_move = false, $update_collection_items = false)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];

        if(!$force_move && self::entry_move_effects_other_hierarchies($hierarchy_entry_id, $taxon_concept_id))
        {
            echo "can't make this move\n";
            return false;
        }

        $result = $mysqli->query("SELECT he2.id, he2.taxon_concept_id FROM hierarchy_entries he JOIN hierarchy_entries he2 USING (taxon_concept_id) WHERE he.id=$hierarchy_entry_id");
        if($result && $row=$result->fetch_assoc())
        {
            // the entry is already in the new concept so return
            if($row['taxon_concept_id'] == $taxon_concept_id) return true;

            $count = $result->num_rows;
            if($count == 1)
            {
                //// if there is just one member of the group, then supercede the group with the new one
                HierarchyEntry::update_data($entry, $row['taxon_concept_id'], $taxon_concept_id);
                TaxonConcept::supercede_by_ids($taxon_concept_id, $row['taxon_concept_id'], $update_collection_items);
            }else
            {
                $mysqli->begin_transaction();
                $old_taxon_concept_id = $row['taxon_concept_id'];
                // if there is more than one member, just update the one entry
                $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$taxon_concept_id WHERE id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE taxon_concept_names SET taxon_concept_id=$taxon_concept_id WHERE source_hierarchy_entry_id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE hierarchy_entries he JOIN random_hierarchy_images rhi ON (he.id=rhi.hierarchy_entry_id) SET rhi.taxon_concept_id=he.taxon_concept_id WHERE he.taxon_concept_id=$hierarchy_entry_id");
                $mysqli->update("UPDATE IGNORE hierarchy_entries he JOIN data_objects_hierarchy_entries dohe ON (he.id=dohe.hierarchy_entry_id) JOIN data_objects_taxon_concepts dotc ON (dohe.data_object_id=dotc.data_object_id) SET dotc.taxon_concept_id=he.taxon_concept_id WHERE he.id=$hierarchy_entry_id AND dotc.taxon_concept_id=$old_taxon_concept_id");

                $mysqli->update("UPDATE taxon_concepts SET published=0, vetted_id=0 WHERE id IN ($taxon_concept_id, $old_taxon_concept_id)");

                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.published=he.published WHERE tc.id=$taxon_concept_id AND he.published!=0");
                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.vetted_id=he.vetted_id WHERE tc.id=$taxon_concept_id AND he.vetted_id!=0");

                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.published=he.published WHERE tc.id=$old_taxon_concept_id AND he.published!=0");
                $mysqli->update("UPDATE hierarchy_entries he JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET tc.vetted_id=he.vetted_id WHERE tc.id=$old_taxon_concept_id AND he.vetted_id!=0");
				
				$entry = HierarchyEntry::find($hierarchy_entry_id);
				HierarchyEntry::update_data($entry, $old_taxon_concept_id, $taxon_concept_id);
                $mysqli->end_transaction();
            }
        }
        return(true);
    }

    public static function entry_move_effects_other_hierarchies($hierarchy_entry_id, $new_taxon_concept_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];

        $counts = array();
        $result = $mysqli->query("SELECT SQL_NO_CACHE he.hierarchy_id, he.taxon_concept_id FROM hierarchy_entries he JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE he.id=$hierarchy_entry_id AND h.complete=1 AND he.visibility_id=".Visibility::visible()->id);
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_id = $row['hierarchy_id'];
            $counts[$hierarchy_id] = 1;
        }
        $result = $mysqli->query("SELECT SQL_NO_CACHE he.hierarchy_id, he.taxon_concept_id FROM hierarchy_entries he JOIN hierarchies h ON (he.hierarchy_id=h.id) WHERE he.taxon_concept_id=$new_taxon_concept_id AND h.complete=1 AND he.visibility_id=".Visibility::visible()->id);
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_id = $row['hierarchy_id'];
            if(isset($counts[$hierarchy_id])) return true;
        }

        return false;
    }

    // NOTE: this function looks as if it's never used (Aug 2015). That's fine, I'm not sure what it's intent was. :|
    function set_taxon_concept_id($taxon_concept_id)
    {
        if($this->taxon_concept_id)
        {
            if($this->taxon_concept_id!=$taxon_concept_id) $this->taxon_concept()->supercede($taxon_concept_id);
        }else
        {
            $mysqli->update("UPDATE hierarchy_entries SET taxon_concept_id=$taxon_concept_id WHERE id=$this->id");
            $this->taxon_concept_id = $taxon_concept_id;
        }
    }

    function ranked_ancestry()
    {
        $ancestry = "";

        if($this->parent_id)
        {
            $parent = $this->parent();
            $parent_ranked_ancestry = $parent->ranked_ancestry();

            if($parent_ranked_ancestry) $ancestry = $parent_ranked_ancestry."|";
            if(@$parent->rank->id) $ancestry .= $parent->rank->translation->label;
            $ancestry .= ":".$parent->name->string;
        }

        return $ancestry;
    }

    function ancestry_names()
    {
        $ancestry = "";

        if($this->parent_id)
        {
            $parent = $this->parent();
            $parent_ancestry_names = $parent->ancestry_names();

            if($parent_ancestry_names) $ancestry = $parent_ancestry_names."|";
            $ancestry .= $parent->name->string;
        }

        return $ancestry;
    }

    function parent()
    {
        if($this->parent_id) return HierarchyEntry::find($this->parent_id);
        return NULL;
    }

    function taxon_concept_parents()
    {
        $taxon_concept_ids = array();
        $parents = array();

        $result = $this->mysqli->query("SELECT he2.taxon_concept_id, he2.id FROM hierarchy_entries he1 JOIN hierarchy_entries he2 ON (he1.parent_id=h2.id) WHERE h1.taxon_concept_id=".$this->id);
        while($result && $row=$result->fetch_assoc())
        {
            if($row["taxon_concept_id"]) $taxon_concept_ids[$row["taxon_concept_id"]] = 1;
            else $parents[] = new HierarchyEntry($row["id"]);
        }
        $result->free();

        foreach($taxon_concept_ids as $k => $v)
        {
            $parents[] = new TaxonConcept($k);
        }

        return $parents;
    }

    function number_of_children()
    {
        $count = 0;

        $result = $this->mysqli->query("SELECT COUNT(id) as count FROM hierarchy_entries WHERE lft BETWEEN $this->lft AND $this->rgt AND hierarchy_id=$this->hierarchy_id");
        if($result && $row=$result->fetch_assoc()) $count = $row["count"] - 1;

        return $count;
    }

    function number_of_children_synonyms()
    {
        $count = 0;

        $result = $this->mysqli->query("SELECT COUNT(s.id) as count FROM hierarchy_entries he JOIN synonyms s ON (he.id=s.hierarchy_entry_id) WHERE he.lft BETWEEN $this->lft AND $this->rgt AND he.hierarchy_id=$this->hierarchy_id");
        if($result && $row=$result->fetch_assoc()) $count = $row["count"];

        return $count;
    }

    function children()
    {
        $children = array();

        $result = $this->mysqli->query("SELECT * FROM hierarchy_entries WHERE parent_id=".$this->id);
        while($result && $row=$result->fetch_assoc()) $children[] = new HierarchyEntry($row);
        $result->free();

        @usort($children, "Functions::cmp_hierarchy_entries");

        return $children;
    }

    function synonyms()
    {
        $synonyms = array();

        $result = $this->mysqli->query("SELECT * FROM synonyms WHERE hierarchy_entry_id=".$this->id);
        while($result && $row=$result->fetch_assoc()) $synonyms[] = new Synonym($row);
        $result->free();

        @usort($synonyms, "Functions::cmp_hierarchy_entries");

        return $synonyms;
    }

    function top_images()
    {
        $top_images = array();

        $result = $this->mysqli->query("SELECT data_object_id FROM top_images WHERE hierarchy_entry_id=".$this->id." ORDER BY view_order ASC");
        while($result && $row=$result->fetch_assoc())
        {
            $top_images[] = new DataObject($this->mysqli, $row["data_object_id"]);
        }
        $result->free();

        return $top_images;
    }

    function is_in_ancestry_of($hierarchy_entry)
    {
        $canonical_form_id = $this->name()->canonical_form_id;

        $ancestry = $hierarchy_entry->ancestry;
        $nodes = explode("|", $ancestry);
        foreach($nodes as $id)
        {
            //echo "IIAO: $id - $this->name_id<br>";
            if($id == $this->name_id) return true;
            $name = new Name($id);
            if($name->canonical_form_id == $canonical_form_id) return true;
        }

        return false;
    }

    public function delete_agents()
    {
        $this->mysqli->delete("DELETE FROM agents_hierarchy_entries WHERE hierarchy_entry_id=$this->id");
    }
    public function delete_common_names()
    {
        $this->mysqli->delete("DELETE FROM synonyms WHERE hierarchy_entry_id=$this->id AND hierarchy_id=$this->hierarchy_id AND language_id!=0 AND language_id!=". Language::find_or_create_for_parser('scientific name')->id);
    }
    public function delete_synonyms()
    {
        $this->mysqli->delete("DELETE FROM synonyms WHERE hierarchy_entry_id=$this->id AND hierarchy_id=$this->hierarchy_id AND (language_id=0 OR language_id=". Language::find_or_create_for_parser('scientific name')->id.")");
    }


    public function add_agent($agent_id, $agent_role_id, $view_order)
    {
        if(!$agent_id) return false;
        $this->mysqli->insert("INSERT IGNORE INTO agents_hierarchy_entries (hierarchy_entry_id, agent_id, agent_role_id, view_order) VALUES ($this->id, $agent_id, $agent_role_id, $view_order)");
    }

    public function add_synonym($name_id, $relation_id, $language_id, $preferred, $vetted_id = 0, $published = 0, $taxon_remarks = NULL)
    {
        if(!$name_id) return 0;
        if(!$relation_id) $relation_id = 0;
        if(!$language_id) $language_id = 0;
        if(!$preferred) $preferred = 0;
        Synonym::find_or_create(array('name_id'               => $name_id,
                                      'synonym_relation_id'   => $relation_id,
                                      'language_id'           => $language_id,
                                      'hierarchy_entry_id'    => $this->id,
                                      'preferred'             => $preferred,
                                      'hierarchy_id'          => $this->hierarchy_id,
                                      'vetted_id'             => $vetted_id,
                                      'published'             => $published,
                                      'taxon_remarks'         => $taxon_remarks));
    }

    public function add_data_object($data_object_id, $vetted_id = null, $visibility_id = null)
    {
        if(!$data_object_id) return 0;
        if($vetted_id === null) $vetted_id = Vetted::unknown()->id;
        if($visibility_id === null) $visibility_id = Visibility::preview()->id;
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_hierarchy_entries (hierarchy_entry_id, data_object_id, vetted_id, visibility_id) VALUES ($this->id, $data_object_id, $vetted_id, $visibility_id)");
        $this->mysqli->insert("INSERT IGNORE INTO data_objects_taxon_concepts (taxon_concept_id, data_object_id) VALUES ($this->taxon_concept_id, $data_object_id)");
    }

    public function delete_refs()
    {
        $this->mysqli->update("DELETE FROM hierarchy_entries_refs WHERE hierarchy_entry_id=$this->id");
    }

    public function add_reference($reference_id)
    {
        if(!$reference_id) return 0;
        $this->mysqli->insert("INSERT IGNORE INTO hierarchy_entries_refs (hierarchy_entry_id, ref_id) VALUES ($this->id, $reference_id)");
    }

    public function published_references()
    {
        $published_refs = array();
        foreach($this->references as $ref)
        {
            if($ref->published) $published_refs[] = $ref;
        }
        return $published_refs;
    }

    public static function move_to_child_of($node_id, $child_of_id)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];

        $node = new HierarchyEntry($node_id);
        $parent = new HierarchyEntry($child_of_id);

        // Make sure we have two valid nodes in the same hierarchy
        if(!$node->id) return false;
        if(!$parent->id) return false;
        if($parent->hierarchy_id != $node->hierarchy_id) return false;

        // No need to move, this branch is already a child of this parent
        if($parent->id == $node->parent_id) return false;

        $position = "left_of";
        if($parent->lft < $node->lft)
        {
            if($parent->rgt > $node->rgt) $position = "child_of";
            else $position = "right_of";
        }elseif($parent->rgt < $node->rgt)
        {
            // Parent is contained in this branch - cant move this to a child of itself
            return false;
        }

        $branch_span = $node->rgt - $node->lft + 1;

        if($position == "right_of") $branch_offset = $parent->rgt - $node->lft;
        else $branch_offset = $parent->rgt - $node->rgt - 1;

        $mysqli->begin_transaction();

        // Resest the branch parent
        $mysqli->query("UPDATE hierarchy_entries SET parent_id=$parent->id WHERE id=$node->id");
        $mysqli->commit();

        // Set the left and right values of the branch to their inverse so they can be queried for later
        $mysqli->query("UPDATE hierarchy_entries SET lft=-1*lft, rgt=-1*rgt WHERE lft between $node->lft AND $node->rgt AND hierarchy_id=$node->hierarchy_id");

        if($position == "right_of")
        {
            // Update the left and right values of the effected nodes
            $mysqli->query("UPDATE hierarchy_entries SET lft=lft+$branch_span WHERE lft>=$parent->rgt AND lft<$node->lft AND hierarchy_id=$node->hierarchy_id");
            $mysqli->query("UPDATE hierarchy_entries SET rgt=rgt+$branch_span WHERE rgt>=$parent->rgt AND rgt<$node->lft AND hierarchy_id=$node->hierarchy_id");
        }else
        {
            // Update the left and right values of the effected nodes
            $mysqli->query("UPDATE hierarchy_entries SET lft=lft-$branch_span WHERE lft>$node->rgt AND lft<$parent->rgt AND hierarchy_id=$node->hierarchy_id");
            $mysqli->query("UPDATE hierarchy_entries SET rgt=rgt-$branch_span WHERE rgt>$node->rgt AND rgt<$parent->rgt AND hierarchy_id=$node->hierarchy_id");
        }

        // Update the left and right values of the branch
        $mysqli->query("UPDATE hierarchy_entries SET depth=depth+(". ($parent->depth + 1 - $node->depth) ."), lft=(-1*lft)+($branch_offset), rgt=(-1*rgt)+($branch_offset) WHERE lft between ". (-1*$node->rgt) ." AND ". (-1*$node->lft) ." AND hierarchy_id=$node->hierarchy_id");

        $mysqli->end_transaction();
    }

      static function create_entries_for_taxon($taxon, $hierarchy_id)
    {
        $name_ids = array();
        if(@$string = $taxon['kingdom'])
        {
            $name = Name::find_or_create_by_string($string);
            $name_ids["kingdom"] = $name->id;
        }
        if(@$string = $taxon['phylum'])
        {
            $name = Name::find_or_create_by_string($string);
            $name_ids["phylum"] = $name->id;
        }
        if(@$string = $taxon['class'])
        {
            $name = Name::find_or_create_by_string($string);
            $name_ids["class"] = $name->id;
        }
        if(@$string = $taxon['order'])
        {
            $name = Name::find_or_create_by_string($string);
            $name_ids["order"] = $name->id;
        }
        if(@$string = $taxon['family'])
        {
            $name = Name::find_or_create_by_string($string);
            $name_ids["family"] = $name->id;
        }
        if(@$string = $taxon['genus'])
        {
            $name = Name::find_or_create_by_string($string);
            $name_ids["genus"] = $name->id;
        }
        if(@$taxon['family'] && !@$taxon['genus'] && @preg_match("/^([^ ]+) /", $taxon['scientific_name'], $arr))
        {
            $string = $arr[1];
            $name = Name::find_or_create_by_string($string);
            $name_ids["genus"] = $name->id;
        }

        // the base level scientific_name. Unsure of the rank at this point
        if(@$taxon['name']) $name_ids[] = $taxon['name']->id;

        $parent_hierarchy_entry = null;
        foreach($name_ids as $rank => $id)
        {
            $params = array();
            $params["name_id"] = $id;
            if($parent_hierarchy_entry)
            {
                if($parent_hierarchy_entry->ancestry) $params["ancestry"] = $parent_hierarchy_entry->ancestry. "|" .$parent_hierarchy_entry->name_id;
                else $params["ancestry"] = $parent_hierarchy_entry->name_id;
            }

            $params["hierarchy_id"] = $hierarchy_id;
            if($rank) $params["rank_id"] = Rank::find_or_create_by_translated_label($rank)->id;
            if($parent_hierarchy_entry) $params["parent_id"] = $parent_hierarchy_entry->id;

            // if there is no rank then we have the scientific_name
            if(!$rank)
            {
                $params["identifier"] = $taxon['identifier'];
                $params["source_url"] = $taxon['source_url'];
                if($taxon['rank'] && $rank_id = @$taxon['rank']->id)
                {
                    $params["rank_id"] = $rank_id;
                }
                if(isset($taxon['taxon_remarks'])) $params["taxon_remarks"] = $taxon['taxon_remarks'];
            }

            $params["visibility_id"] = Visibility::preview()->id;
            $hierarchy_entry = HierarchyEntry::find_or_create_by_array($params);
            $parent_hierarchy_entry = $hierarchy_entry;
        }

        // returns the entry object for the scientific_name
        if($parent_hierarchy_entry) return $parent_hierarchy_entry;
        return 0;
    }

    static function find_last_by_identifier($identifier)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        # can't find entries without an identifier
        if(!trim($identifier)) return null;

        $result = $mysqli->query("SELECT SQL_NO_CACHE id FROM hierarchy_entries WHERE identifier='".$mysqli->escape($identifier)."' ORDER BY id DESC LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            return $row['id'];
        }
        return null;
    }

    static function find_guid_by_hierarchy_and_identifier($parameters)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        if(@!$parameters['identifier']) return null;

        // look for entries with the SAME NAME and the SAME PARENT, in the SAME HIERARCHY
        $result = $mysqli->query("SELECT SQL_NO_CACHE id, guid
            FROM hierarchy_entries
            WHERE identifier='". $mysqli->escape($parameters['identifier']) ."'
            AND hierarchy_id=". $parameters['hierarchy_id'] ."
            ORDER BY id DESC");

        if($result && $row=$result->fetch_assoc())
        {
            return $row['guid'];
        }
        return false;
    }

    static function find_or_create_by_array($parameters, $force = false)
    {
        if(!$force && $object = self::find_by_array($parameters)) return $object;

        if(@!$parameters['taxon_concept_id']) $parameters['taxon_concept_id'] = TaxonConcept::create()->id;
        if(@!$parameters['guid'])
        {
            $previous_version_guid = self::find_guid_by_hierarchy_and_identifier($parameters);
            if($previous_version_guid)
            {
                $parameters['guid'] = $previous_version_guid;
            }else
            {
                $parameters['guid'] = Functions::generate_guid();
            }
        }

        return self::create($parameters);
    }

    static function find_by_array($parameters)
    {
        $mysqli =& $GLOBALS['mysqli_connection'];
        if(@!$parameters['name_id']) $parameters['name_id'] = 0;
        if(@!$parameters['parent_id']) $parameters['parent_id'] = 0;
        if(@!$parameters['identifier']) $parameters['identifier'] = '';
        if(@!$parameters['source_url']) $parameters['source_url'] = '';

        // look for entries with the SAME NAME and the SAME PARENT, in the SAME HIERARCHY
        $query = "SELECT SQL_NO_CACHE *
            FROM hierarchy_entries
            WHERE name_id=". $parameters['name_id'] ."
            AND parent_id=". $parameters['parent_id'] ."
            AND hierarchy_id=". $parameters['hierarchy_id'];
        if($parameters['identifier']) $query .= " AND (identifier='' OR identifier='". $mysqli->escape($parameters['identifier']) ."')";
        if($parameters['source_url']) $query .= " AND (source_url='' OR source_url='". $mysqli->escape($parameters['source_url']) ."')";
        $result = $mysqli->query($query);

        // check the results for duplicates
        while($result && $row=$result->fetch_assoc())
        {
            $hierarchy_entry = new HierarchyEntry($row);
            $changed = false;
            // existing entry has no identifier - so reuse it and update its identifier
            if(!$hierarchy_entry->identifier && $parameters['identifier'])
            {
                $hierarchy_entry->identifier = $parameters['identifier'];
                $changed = true;
            }
            // existing entry has no source_url - so reuse it and update its source_url
            if(!$hierarchy_entry->source_url && $parameters['source_url'])
            {
                $hierarchy_entry->source_url = $parameters['source_url'];
                $changed = true;
            }

            if($changed) $hierarchy_entry->save();
            return $hierarchy_entry;
        }
        return false;
    }
}

?>
