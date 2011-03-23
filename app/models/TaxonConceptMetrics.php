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
    
    function weighted_images($weight = .75)
    {
        return $this->image_trusted + ($weight * $this->image_unreviewed);
    }
    
    function weighted_text($weight = .75)
    {
        return $this->text_trusted + ($weight * $this->text_unreviewed);
    }
    
    function weighted_text_words($weight = .75)
    {
        return $this->text_trusted_words + ($weight * $this->text_unreviewed_words);
    }
    
    function weighted_videos($weight = .75)
    {
        return $this->videos() + ($weight * ($this->video_unreviewed + $this->flash_unreviewed + $this->sound_unreviewed));
    }
    
    function weighted_sounds($weight = .75)
    {
        return $this->sound_trusted + ($weight * $this->sound_unreviewed);
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
    
    function average_words_weighted($weight = .75)
    {
        if($this->weighted_text_words($weight)) return $this->weighted_text_words($weight) / ($this->text_trusted + $this->text_unreviewed);
        return 0;
    }
    
}

?>