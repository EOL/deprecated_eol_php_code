<?php

class TaxonConceptMetric extends MysqlBase
{
    function __construct($param)
    {
        $this->table_name = Functions::class_name(__FILE__);
        $result = $GLOBALS['db_connection']->query("SELECT * FROM ".$this->table_name." WHERE taxon_concept_id=$param");
        $row = $result->fetch_assoc();
        parent::initialize($row);
        if(@!$this->taxon_concept_id) return;
    }
    
    function videos()
    {
        return $this->video_total + $this->flash_total + $this->youtube_total;
    }
    
    function average_words()
    {
        if($this->text_total) return $this->text_total_words / $this->text_total;
        return 0;
    }
}

?>