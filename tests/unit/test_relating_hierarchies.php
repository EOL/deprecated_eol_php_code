<?php
namespace php_active_record;

class test_relating_hierarchies extends SimpletestUnitBase
{
    function setUp()
    {
        parent::setUp();
        $this->solr = new SolrAPI(SOLR_SERVER, 'hierarchy_entries');
        $this->solr->delete_all_documents();
        
        $this->kingdom = Rank::find_or_create_by_translated_label('kingdom');
        $this->phylum = Rank::find_or_create_by_translated_label('phylum');
        $this->class = Rank::find_or_create_by_translated_label('class');
        $this->order = Rank::find_or_create_by_translated_label('order');
        $this->family = Rank::find_or_create_by_translated_label('family');
        $this->genus = Rank::find_or_create_by_translated_label('genus');
        $this->species = Rank::find_or_create_by_translated_label('species');
        
        $params = array('label' => 'Some Hierarchy', 'complete' => 0);
        $this->hierarchy = Hierarchy::find_or_create($params);
        
        $col_nodes = array(
            'rank' => 'kingdom', 'name' => 'Animalia',
            'children' => array(array(
                'rank' => 'phylum', 'name' => 'Chordata',
                'children' => array(array(
                    'rank' => 'class', 'name' => 'Actinopterygii',
                    'children' => array(array(
                        'rank' => 'order', 'name' => 'Gadiformes',
                        'children' => array(array(
                            'rank' => 'family', 'name' => 'Gadidae',
                            'children' => array(array(
                                'rank' => 'genus', 'name' => 'Gadus',
                                'children' => array(
                                    array('rank' => 'species', 'name' => 'Gadus morhua Linnaeus, 1800', 'identifier' => 'col_morhua1'),
                                    array('rank' => 'species', 'name' => 'Gadus morhua Linnaeus, 1758', 'identifier' => 'col_morhua2')
                                )
                            ))
                        ))
                    ))
                ))
            ))
        );
        $this->insert_node_data($col_nodes, $this->hierarchy);
        $this->relator = new RelateHierarchies(array('hierarchy_to_compare' => $this->hierarchy));
    }
    
    function testSetUpIsWorking()
    {
        $db_entry = HierarchyEntry::find_by_identifier('col_morhua2');
        $solr_entry = $this->fetch_solr_entry_by_id($db_entry->id);
        $this->assertEqual($solr_entry->canonical_form, $db_entry->name->canonical_form->string);
        $this->assertEqual($solr_entry->canonical_form_string, $db_entry->name->canonical_form->string);
        $this->assertEqual($solr_entry->class, "Actinopterygii");
        $this->assertEqual($solr_entry->family, "Gadidae");
        $this->assertEqual($solr_entry->genus, "Gadus");
        $this->assertEqual($solr_entry->hierarchy_id, $db_entry->hierarchy->id);
        $this->assertEqual($solr_entry->id, $db_entry->id);
        $this->assertEqual($solr_entry->kingdom, "Animalia");
        $this->assertEqual($solr_entry->name, $db_entry->name->string);
        $this->assertEqual($solr_entry->order, "Gadiformes");
        $this->assertEqual($solr_entry->parent_id, $db_entry->parent_id);
        $this->assertEqual($solr_entry->phylum, "Chordata");
        $this->assertEqual($solr_entry->published, 1);
        $this->assertEqual($solr_entry->rank_id, $db_entry->rank_id);
        $this->assertEqual($solr_entry->taxon_concept_id, $db_entry->taxon_concept_id);
        $this->assertEqual($solr_entry->vetted_id, $db_entry->vetted_id);
    }
    
    function testCompleteHierarchiesShouldNotMatch()
    {
        $solr_entry1 = $this->fetch_solr_entry_by_id(HierarchyEntry::find_by_identifier('col_morhua1')->id);
        $solr_entry2 = $this->fetch_solr_entry_by_id(HierarchyEntry::find_by_identifier('col_morhua2')->id);
        $score = $this->relator->compare_entries_from_solr($solr_entry1, $solr_entry2);
        $this->assertTrue($score != NULL, 'Incomplete hiearchies can compare against themselves');
        
        $this->hierarchy->complete = 1;
        $this->hierarchy->save();
        $this->relator = new RelateHierarchies(array('hierarchy_to_compare' => $this->hierarchy));
        $score = $this->relator->compare_entries_from_solr($solr_entry1, $solr_entry2);
        $this->assertTrue($score === NULL, 'complete hiearchies should not compare against themselves');
    }
    
    function testSameCanonicalForm()
    {
        $solr_entry1 = $this->fetch_solr_entry_by_id(HierarchyEntry::find_by_identifier('col_morhua1')->id);
        $solr_entry2 = $this->fetch_solr_entry_by_id(HierarchyEntry::find_by_identifier('col_morhua2')->id);
        $score = $this->relator->compare_entries_from_solr($solr_entry1, $solr_entry2);
        $this->assertTrue($score === 0.5, 'Nodes with exact ancestries, non-exact name strings, same canonical forms should score .5');
    }
    
    function testSameNameString()
    {
        $entry1 = HierarchyEntry::find_by_identifier('col_morhua1');
        $entry2 = HierarchyEntry::find_by_identifier('col_morhua2');
        $entry2->name_id = $entry1->name_id;
        $entry2->save();
        $this->index_hierarchy($this->hierarchy->id);
        
        $solr_entry1 = $this->fetch_solr_entry_by_id(HierarchyEntry::find_by_identifier('col_morhua1')->id);
        $solr_entry2 = $this->fetch_solr_entry_by_id(HierarchyEntry::find_by_identifier('col_morhua2')->id);
        $score = $this->relator->compare_entries_from_solr($solr_entry1, $solr_entry2);
        $this->assertTrue($score === 1, 'Nodes with exact ancestries, exact name strings should score 1');
    }
    
    function testDifferentNames()
    {
        $entry1 = HierarchyEntry::find_by_identifier('col_morhua1');
        $entry2 = HierarchyEntry::find_by_identifier('col_morhua2');
        $entry2->name_id = Name::find_or_create_by_string("Nonsense")->id;
        $entry2->save();
        $this->index_hierarchy($this->hierarchy->id);
        
        $solr_entry1 = $this->fetch_solr_entry_by_id(HierarchyEntry::find_by_identifier('col_morhua1')->id);
        $solr_entry2 = $this->fetch_solr_entry_by_id(HierarchyEntry::find_by_identifier('col_morhua2')->id);
        $score = $this->relator->compare_entries_from_solr($solr_entry1, $solr_entry2);
        $this->assertTrue($score === 0, 'Nodes different names should score 0');
    }
    
    
    
    
    
    
    
    
    
    
    
    
    
    function insert_node_data($node_data, $hierarchy, $parent_id = NULL)
    {
        $rank = $node_data['rank'];
        $name = $node_data['name'];
        $identifier = @$node_data['identifier'];
        $children = @$node_data['children'];
        
        $entry = HierarchyEntry::find_or_create(array(
            'hierarchy_id' => $hierarchy->id,
            'rank_id' => Rank::find_or_create_by_translated_label($rank)->id,
            'name_id' => Name::find_or_create_by_string($name)->id,
            'parent_id' => $parent_id,
            'taxon_concept_id' => TaxonConcept::create()->id,
            'published' => 1,
            'visibility_id' => Visibility::visible()->id,
            'vetted_id' => Vetted::trusted()->id,
            'identifier' => $identifier
        ));
        if($children)
        {
            foreach($children as $child_node_data)
            {
                $this->insert_node_data($child_node_data, $hierarchy, $entry->id);
            }
        }
        
        if($parent_id === NULL)
        {
            Tasks::rebuild_nested_set($hierarchy->id);
            $fh = new FlattenHierarchies();
            $fh->begin_process();
            $this->index_hierarchy($hierarchy->id);
        }
    }
    
    function index_hierarchy($hierarchy_id)
    {
        $indexer = new HierarchyEntryIndexer();
        $indexer->index($hierarchy_id);
    }
    
    function fetch_solr_entry_by_id($id)
    {
        $results = $this->solr->get_results("id:$id");
        return $results[0];
    }
}
