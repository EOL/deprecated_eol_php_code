<?php

class HierarchiesContent
{
    private $mysqli;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    
    public function begin_process()
    {
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS hierarchies_content_tmp LIKE hierarchies_content");
        $this->mysqli->delete("TRUNCATE TABLE hierarchies_content_tmp");
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS taxon_concept_content_tmp LIKE taxon_concept_content");
        $this->mysqli->delete("TRUNCATE TABLE taxon_concept_content_tmp");
        
        // wait two minutes for the slave to catch up
        sleep(120);
        $this->lookup_content();
        
        // wait two minutes for the slave to catch up
        sleep(120);
        $this->gbif_maps();
        
        $this->mysqli->begin_transaction();
        $result = $this->mysqli->query("SELECT 1 FROM hierarchies_content_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("RENAME TABLE hierarchies_content TO hierarchies_content_swap,
                                                hierarchies_content_tmp TO hierarchies_content,
                                                hierarchies_content_swap TO hierarchies_content_tmp");
        }
        $result = $this->mysqli->query("SELECT 1 FROM taxon_concept_content_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("RENAME TABLE taxon_concept_content TO taxon_concept_content_swap,
                                                taxon_concept_content_tmp TO taxon_concept_content,
                                                taxon_concept_content_swap TO taxon_concept_content_tmp");
        }
        
        $this->mysqli->end_transaction();
    }
    
    public function lookup_content()
    {
        // make sure the TC content temp table has exactly the right number of rows and reset everything to 0
        $this->mysqli->query("DELETE tcc FROM taxon_concept_content_tmp tcc LEFT JOIN taxon_concepts tc ON (tcc.taxon_concept_id=tc.id) WHERE tc.id IS NULL");
        $this->mysqli->query("INSERT INTO taxon_concept_content_tmp (taxon_concept_id) (SELECT tc.id FROM taxon_concepts tc LEFT JOIN taxon_concept_content_tmp tcc ON (tc.id=tcc.taxon_concept_id) WHERE tcc.taxon_concept_id IS NULL)");
        sleep(60);
        $this->mysqli->query("UPDATE taxon_concept_content_tmp SET text=0, text_unpublished=0, image=0, image_unpublished=0, child_image=0, child_image_unpublished=0, flash=0, youtube=0, map=0, content_level=0, image_object_id=0");
        sleep(60);
        
        // make sure the HE content temp table has exactly the right number of rows and reset everything to 0
        $this->mysqli->query("DELETE hc FROM hierarchies_content_tmp hc LEFT JOIN hierarchy_entries he ON (hc.hierarchy_entry_id=he.id) WHERE he.id IS NULL");
        $this->mysqli->query("INSERT INTO hierarchies_content_tmp (hierarchy_entry_id) (SELECT he.id FROM hierarchy_entries he LEFT JOIN hierarchies_content_tmp hc ON (he.id=hc.hierarchy_entry_id) WHERE hc.hierarchy_entry_id IS NULL)");
        sleep(60);
        $this->mysqli->query("UPDATE hierarchies_content_tmp SET text=0, text_unpublished=0, image=0, image_unpublished=0, child_image=0, child_image_unpublished=0, flash=0, youtube=0, map=0, content_level=1, image_object_id=0");
        sleep(60);
        
        // update attributes for all the data types we care about
        $this->mysqli->query("UPDATE taxon_concept_content_tmp tcc JOIN data_types_taxon_concepts dttc USING (taxon_concept_id) SET tcc.text=1 WHERE dttc.data_type_id=".DataType::find_by_label('Text')." AND dttc.published=1 AND dttc.visibility_id=".Visibility::find('visible')."");
        sleep(60);
        $this->mysqli->query("UPDATE taxon_concept_content_tmp tcc JOIN data_types_taxon_concepts dttc USING (taxon_concept_id) SET tcc.image=1 WHERE dttc.data_type_id=".DataType::find_by_label('Image')." AND dttc.published=1 AND dttc.visibility_id=".Visibility::find('visible')."");
        sleep(60);
        $this->mysqli->query("UPDATE taxon_concept_content_tmp tcc JOIN data_types_taxon_concepts dttc USING (taxon_concept_id) SET tcc.flash=1 WHERE dttc.data_type_id=".DataType::find_by_label('Flash')." AND dttc.published=1 AND dttc.visibility_id=".Visibility::find('visible')."");
        sleep(60);
        $this->mysqli->query("UPDATE taxon_concept_content_tmp tcc JOIN data_types_taxon_concepts dttc USING (taxon_concept_id) SET tcc.youtube=1 WHERE dttc.data_type_id=".DataType::find_by_label('YouTube')." AND dttc.published=1 AND dttc.visibility_id=".Visibility::find('visible')."");
        sleep(60);
        
        // update attributes for all the UNPUBLISHED data types we care about
        $this->mysqli->query("UPDATE taxon_concept_content_tmp tcc JOIN data_types_taxon_concepts dttc USING (taxon_concept_id) SET tcc.text_unpublished=1 WHERE dttc.data_type_id=".DataType::find_by_label('Text')." AND (dttc.published=1 OR dttc.visibility_id!=".Visibility::find('visible').")");
        sleep(60);
        $this->mysqli->query("UPDATE taxon_concept_content_tmp tcc JOIN data_types_taxon_concepts dttc USING (taxon_concept_id) SET tcc.image_unpublished=1 WHERE dttc.data_type_id=".DataType::find_by_label('Image')." AND (dttc.published=1 OR dttc.visibility_id!=".Visibility::find('visible').")");
        sleep(60);
        
        // update the content_level attribute
        $this->mysqli->query("UPDATE taxon_concept_content_tmp tcc SET tcc.content_level=3 WHERE tcc.text=1 OR tcc.image=1");
        $this->mysqli->query("UPDATE taxon_concept_content_tmp tcc SET tcc.content_level=4 WHERE tcc.text=1 AND tcc.image=1");
        sleep(60);
        
        // update HE content with all the information from TC content
        $this->mysqli->query("UPDATE hierarchies_content_tmp hc JOIN hierarchy_entries he ON (hc.hierarchy_entry_id=he.id) JOIN taxon_concept_content_tmp tcc USING (taxon_concept_id) SET hc.text=tcc.text, hc.text_unpublished=tcc.text_unpublished, hc.image=tcc.image, hc.image_unpublished=tcc.image_unpublished, hc.flash=tcc.flash, hc.youtube=tcc.youtube, hc.content_level=tcc.content_level WHERE tcc.text=1 OR tcc.text_unpublished=1 OR tcc.image=1 OR tcc.image_unpublished=1 OR tcc.flash=1 OR tcc.youtube=1 OR tcc.content_level>1");
        sleep(60);
        
        // things with images also get the child_images flag
        $this->mysqli->query("UPDATE hierarchies_content_tmp SET child_image=1 WHERE image=1");
        
        // set ancestor child_images
        $continue = true;
        while($continue)
        {
            // update the child_image flag of all parents of things with images
            $this->mysqli->query("UPDATE hierarchy_entries he JOIN hierarchies_content_tmp hc_child ON (he.id=hc_child.hierarchy_entry_id) JOIN hierarchy_entries he_parents ON (he.parent_id=he_parents.id) JOIN hierarchies_content_tmp hc_parent ON (he_parents.id=hc_parent.hierarchy_entry_id) SET hc_parent.child_image=1 WHERE hc_child.image=1 OR hc_child.child_image=1 OR hc_child.image_unpublished=1");
            sleep(30);
            
            // continue doing this as long as we're effecting new rows
            $continue = $this->mysqli->affected_rows();
            if($continue) echo "Continuing with $continue parents\n";
        }
        
        // send the child_image flags to the taxon_concept_content table
        $this->mysqli->query("UPDATE hierarchy_entries he JOIN hierarchies_content_tmp hc ON (he.id=hc.hierarchy_entry_id) JOIN taxon_concept_content_tmp tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) SET tcc.child_image=1 WHERE hc.child_image=1");
        sleep(60);
        
        // set the content level to at least 2 where the child_image flag is set
        $this->mysqli->query("UPDATE hierarchies_content_tmp SET content_level=2 WHERE content_level=1 AND child_image=1");
        $this->mysqli->query("UPDATE taxon_concept_content_tmp SET content_level=2 WHERE content_level=1 AND child_image=1");
    }
    
    function gbif_maps()
    {
        $this->mysqli->begin_transaction();
        
        $mysqli_maps = load_mysql_environment("maps");
        if(!$mysqli_maps)
        {
            echo "skipping gbif maps\n";
            return false;
        }
        
        echo "starting gbif maps\n";
        $result = $mysqli_maps->query("SELECT DISTINCT taxon_id FROM tile_0_taxon");
        $ids = array();
        while($result && $row=$result->fetch_assoc())
        {
            $ids[] = $row["taxon_id"];
            if(count($ids) >= 50000)
            {
                echo "ADDING\n";
                $query = "UPDATE hierarchy_entries he JOIN hierarchies_content_tmp hc ON (he.id=hc.hierarchy_entry_id) SET hc.map=1 WHERE he.hierarchy_id=129 AND he.identifier IN ('". implode("','", $ids) ."')";
                $this->mysqli->update($query);
                $this->mysqli->commit();
                $ids = array();
                sleep(30);
            }
        }
        
        if($ids)
        {
            echo "ADDING\n";
            $query = "UPDATE hierarchy_entries he JOIN hierarchies_content_tmp hc ON (he.id=hcn.hierarchy_entry_id) SET hc.map=1 WHERE he.hierarchy_id=129 AND he.identifier IN (". implode(",", $ids) .")";
            $this->mysqli->update($query);
            $ids = array();
        }
        
        $this->mysqli->update("UPDATE hierarchies_content_tmp hc JOIN hierarchy_entries he ON (hc.hierarchy_entry_id=he.id) JOIN taxon_concept_content_tmp tcc ON (he.taxon_concept_id=tcc.taxon_concept_id) SET tcc.map=1 WHERE hc.map=1");
        
        $this->mysqli->end_transaction();
    }
}

?>