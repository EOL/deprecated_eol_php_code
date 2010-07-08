<?php

class HierarchyEntryStats
{
    private $mysqli;
    
    public function __construct()
    {
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    
    public function begin_process()
    {
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS taxon_concept_stats_tmp LIKE taxon_concept_stats");
        $this->mysqli->delete("TRUNCATE TABLE taxon_concept_stats_tmp");
        
        $this->mysqli->insert("CREATE TABLE IF NOT EXISTS hierarchy_entry_stats_tmp LIKE hierarchy_entry_stats");
        $this->mysqli->delete("TRUNCATE TABLE hierarchy_entry_stats_tmp");
        
        $this->lookup_concept_content();
        $this->lookup_entry_content();
        $this->add_inherited_values();
        
        $this->mysqli->begin_transaction();
        $result = $this->mysqli->query("SELECT 1 FROM taxon_concept_stats_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("RENAME TABLE taxon_concept_stats TO taxon_concept_stats_swap,
                                                taxon_concept_stats_tmp TO taxon_concept_stats,
                                                taxon_concept_stats_swap TO taxon_concept_stats_tmp");
        }
        $result = $this->mysqli->query("SELECT 1 FROM hierarchy_entry_stats_tmp LIMIT 1");
        if($result && $row=$result->fetch_assoc())
        {
            $this->mysqli->update("RENAME TABLE hierarchy_entry_stats TO hierarchy_entry_stats_swap,
                                                hierarchy_entry_stats_tmp TO hierarchy_entry_stats,
                                                hierarchy_entry_stats_swap TO hierarchy_entry_stats_tmp");
        }
        $this->mysqli->end_transaction();
    }
    
    public function lookup_entry_content()
    {
        $outfile = $this->mysqli->select_into_outfile('SELECT id FROM hierarchy_entries');
        $this->mysqli->load_data_infile($outfile, 'hierarchy_entry_stats_tmp');
        unlink($outfile);
        
        $outfile = $this->mysqli->select_into_outfile("SELECT he.id, tcs.text_trusted, tcs.text_untrusted, tcs.image_trusted, tcs.image_untrusted, 0, 0, 0, 0, 0, 0 FROM hierarchy_entries he STRAIGHT_JOIN taxon_concept_stats tcs ON (he.taxon_concept_id=tcs.taxon_concept_id) WHERE he.published=1 AND he.visibility_id=". Visibility::find('visible'));
        $this->mysqli->load_data_infile($outfile, 'hierarchy_entry_stats_tmp', 'REPLACE');
        unlink($outfile);
    }
    
    public function add_inherited_values()
    {
        // update hierarchy_entry_stats set all_text_trusted=0, all_text_untrusted=0, all_image_trusted=0, all_image_untrusted=0;
        
        $outfile = $this->mysqli->select_into_outfile("SELECT id, lft, rgt, hierarchy_id FROM hierarchy_entries WHERE published=1 AND visibility_id=".Visibility::find('visible')." AND lft!=rgt AND lft!=rgt-1");
        $FILE = fopen($outfile, "r");
        $tmp_file_path = temp_filepath();
        $OUT = fopen($tmp_file_path, "w+");
        $i=0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $i++;
                $parts = explode("\t", trim($line));
                $id = $parts[0];
                $lft = $parts[1];
                $rgt = $parts[2];
                $hierarchy_id = $parts[3];
                $result = $this->mysqli->query("SELECT SUM(hes.text_trusted) all_text_trusted, SUM(hes.text_untrusted) all_text_untrusted, SUM(hes.image_trusted) all_image_trusted, SUM(hes.image_untrusted) all_image_untrusted FROM hierarchy_entries he JOIN hierarchy_entry_stats_tmp hes ON (he.id=hes.hierarchy_entry_id) WHERE he.lft BETWEEN $lft AND $rgt AND he.hierarchy_id=$hierarchy_id AND he.published=1 AND he.visibility_id=".Visibility::find('visible'));
                if($result && $row=$result->fetch_assoc())
                {
                    fwrite($OUT, "$id\t".$row["all_text_trusted"]."\t".$row["all_text_trusted"]."\t".$row["all_image_trusted"]."\t".$row["all_image_untrusted"]."\n");
                }
                echo "$i ($lft, $rgt): ".time_elapsed()."\n";
            }
        }
        fclose($OUT);
        unlink($outfile);
        
        
        $this->mysqli->begin_transaction();
        $FILE = fopen($tmp_file_path, "r");
        $i = 0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $i++;
                $parts = explode("\t", trim($line));
                $id = $parts[0];
                $text = $parts[1];
                $textU = $parts[2];
                $image = $parts[3];
                $imageU = $parts[4];
                $this->mysqli->update("UPDATE hierarchy_entry_stats_tmp SET all_text_trusted=$text, all_text_untrusted=$textU, all_image_trusted=$image, all_image_untrusted=$imageU WHERE hierarchy_entry_id=$id");
                
                if($i % 1000 == 0)
                {
                    echo "Committing $i\n";
                    $this->mysqli->commit();
                }
            }
        }
        $this->mysqli->end_transaction();
        fclose($FILE);
        unlink($tmp_file_path);
    }
    
    public function lookup_concept_content()
    {
        $outfile = $this->mysqli->select_into_outfile('SELECT id FROM taxon_concepts');
        $this->mysqli->load_data_infile($outfile, 'taxon_concept_stats_tmp');
        unlink($outfile);
        
        $visible_id = Visibility::find('visible');
        $unknown_id = Vetted::find('unknown');
        $trusted_id = Vetted::find('trusted');
        $image_id = DataType::find("http://purl.org/dc/dcmitype/StillImage");
        $text_id = DataType::find("http://purl.org/dc/dcmitype/Text");
        
        $outfile = $this->mysqli->select_into_outfile("SELECT dttc.taxon_concept_id, do.id data_object_id,  do.data_type_id, do.vetted_id FROM data_types_taxon_concepts dttc STRAIGHT_JOIN data_objects_taxon_concepts dotc ON (dttc.taxon_concept_id=dotc.taxon_concept_id) STRAIGHT_JOIN data_objects do ON (dotc.data_object_id=do.id) WHERE dttc.data_type_id IN ($image_id, $text_id) AND dttc.visibility_id=$visible_id AND dttc.published=1 AND do.published=1 AND dttc.visibility_id=$visible_id AND do.vetted_id IN ($unknown_id, $trusted_id) ORDER BY taxon_concept_id");
        $out_path = temp_filepath();
        $OUT = fopen($out_path, "w+");
        $trusted_text = 0;
        $unknown_text = 0;
        $trusted_image = 0;
        $unknown_image = 0;
        $used_do_id = array();
        $old_tc_id = 0;
        $FILE = fopen($outfile, "r");
        while(!feof($FILE))
        {
            if($line = fgets($FILE, 4096))
            {
                $parts = explode("\t", trim($line));
                $taxon_concept_id = $parts[0];
                $data_object_id = $parts[1];
                $data_type_id = $parts[2];
                $vetted_id = $parts[3];
                
                if($taxon_concept_id != $old_tc_id)
                {
                    if($old_tc_id != 0)
                    {
                        fwrite($OUT, "$old_tc_id\t$trusted_text\t$unknown_text\t$trusted_image\t$unknown_image\t0\n");
                    }
                    $trusted_text = 0;
                    $unknown_text = 0;
                    $trusted_image = 0;
                    $unknown_image = 0;
                    $used_do_id = array();
                    $old_tc_id = $taxon_concept_id;
                }
                
                if(isset($used_do_id[$data_object_id])) continue;
                $used_do_id[$data_object_id] = 1;
                
                if($data_type_id == $image_id)
                {
                    if($vetted_id == $trusted_id) $trusted_image++;
                    else $unknown_image++;
                }else
                {
                    if($vetted_id == $trusted_id) $trusted_text++;
                    else $unknown_text++;
                }
            }
        }
        fclose($OUT);
        
        $this->mysqli->load_data_infile($out_path, 'taxon_concept_stats_tmp', 'REPLACE');
        unlink($out_path);
        unlink($outfile);
    }
}

?>