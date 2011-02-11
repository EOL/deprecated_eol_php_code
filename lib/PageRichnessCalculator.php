<?php

class PageRichnessCalculator
{
    // breadth
    static $IMAGE_BREADTH_MAX = 8;
    static $INFO_ITEM_BREADTH_MAX = 14;
    static $MAP_BREADTH_MAX = 1;
    static $VIDEO_BREADTH_MAX = 1;
    static $SOUND_BREADTH_MAX = 1;
    static $IUCN_BREADTH_MAX = 1;
    static $REFERENCE_BREADTH_MAX = 10;
    
    static $IMAGE_BREADTH_WEIGHT = .2;
    static $INFO_ITEM_BREADTH_WEIGHT = .5;
    static $MAP_BREADTH_WEIGHT = .08;
    static $VIDEO_BREADTH_WEIGHT = .08;
    static $SOUND_BREADTH_WEIGHT = 0;
    static $IUCN_BREADTH_WEIGHT = .08;
    static $REFERENCE_BREADTH_WEIGHT = .06;
    
    // depth
    static $TEXT_DEPTH_MAX = 400;
    static $TEXT_DEPTH_WEIGHT = 1;
    
    // diversity
    static $PARTNERS_DIVERSITY_MAX = 12;
    static $PARTNERS_DIVERSITY_WEIGHT = 1;
    
    // category weights
    static $BREADTH_WEIGHT = .5;
    static $DEPTH_WEIGHT = .2;
    static $DIVERSITY_WEIGHT = .3;
    
    public function __construct($parameters = null)
    {
        $this->mysqli =& $GLOBALS['db_connection'];
        if($GLOBALS['ENV_NAME'] == 'production' && environment_defined('slave')) $this->mysqli_slave = load_mysql_environment('slave');
        else $this->mysqli_slave =& $this->mysqli;
        
        // override the default weights
        if($parameters)
        {
            foreach($parameters as $p => $v)
            {
                if(isset(self::$$p)) self::$$p = $v;
            }
        }
    }
    
    // process a single page and just return the results
    public function score_for_page($taxon_concept_id)
    {
        $query = "SELECT taxon_concept_id, image_total, text_total, text_total_words, video_total, sound_total, flash_total, youtube_total, iucn_total, data_object_references, info_items, content_partners, has_GBIF_map FROM taxon_concept_metrics WHERE taxon_concept_id=$taxon_concept_id";
        $result = $this->mysqli_slave->query($query);
        if($result && $row=$result->fetch_row())
        {
            return $this->calculate_score_from_row($row);
        }
    }
    
    // these are the exemplar taxa that SPG rated manually
    public function score_for_exemplars()
    {
        $exemplar_ids = array(328593, 1177464, 335326, 2866150, 1151804, 347254, 1006877, 451984, 490953, 2556370, 996711, 2873424, 1013446, 131350, 972688, 585382, 149877, 131865, 324316, 902035);
        return $this->begin_calculating($exemplar_ids);
    }
    
    public function score_for_range($min, $max)
    {
        return $this->begin_calculating(null, "$min and $max");
    }
    
    public function begin_calculating($taxon_concept_ids = null, $range = null)
    {
        $GLOBALS['top_taxa'] = array();
        $all_scores = array();
        $query = "SELECT taxon_concept_id, image_total, text_total, text_total_words, video_total, sound_total, flash_total, youtube_total, iucn_total, data_object_references, info_items, content_partners, has_GBIF_map FROM taxon_concept_metrics";
        if($taxon_concept_ids) $query .= " WHERE taxon_concept_id IN (". implode($taxon_concept_ids, ",") .")";
        elseif($range) $query .= " WHERE taxon_concept_id BETWEEN $range";
        foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
        {
            static $i=0;
            if($i==0) echo "QUERY IS DONE\n";
            $i++;
            if($i%10000==0) echo "$i: ". memory_get_usage() ."\n";
            $taxon_concept_id = $row[0];
            $this_scores = $this->calculate_score_from_row($row);
            if($this_scores['total'] >= .5) $all_scores[$taxon_concept_id] = $this_scores;
        }
        
        echo "CALCULATIONS ARE DONE\n";
        uasort($all_scores, array('self', 'sort_by_total_score'));
        echo "RANK\tID\tNAME\tBREADTH\tDEPTH\tDIVERSITY\tTOTAL\n";
        static $num = 0;
        foreach($all_scores as $id => $scores)
        {
            $num++;
            echo "$num\t$id\t" . TaxonConcept::get_name($id) ."\t";
            echo $scores['breadth']."\t";
            echo $scores['depth']."\t";
            echo $scores['diversity']."\t";
            echo $scores['total']."\n";
        }
        return $all_scores;
    }
    
    public function calculate_score_from_row($row)
    {
        $scores = array();
        $taxon_concept_id = $row[0];
        $image_total = min($row[1], self::$IMAGE_BREADTH_MAX);
        $text_total = $row[2];
        $text_total_words = $row[3];
        $video_total = $row[4];
        $sound_total = min($row[5], self::$SOUND_BREADTH_MAX);
        $flash_total = $row[6];
        $youtube_total = $row[7];
        $iucn_total = min($row[8], self::$IUCN_BREADTH_MAX);
        $data_object_references = min($row[9], self::$REFERENCE_BREADTH_MAX);
        $info_items = min($row[10], self::$INFO_ITEM_BREADTH_MAX);
        $content_partners = min($row[11], self::$PARTNERS_DIVERSITY_MAX);
        $has_GBIF_map = min($row[12], self::$MAP_BREADTH_MAX);
        
        $video_total += $flash_total + $youtube_total;
        $video_total = min($video_total, self::$VIDEO_BREADTH_MAX);
        
        $words_per_text = 0;
        if($text_total) $words_per_text = min(($text_total_words / $text_total), self::$TEXT_DEPTH_MAX);
        
        $breadth_score = 0;
        $breadth_score += ($image_total / self::$IMAGE_BREADTH_MAX) * self::$IMAGE_BREADTH_WEIGHT;
        $breadth_score += ($info_items / self::$INFO_ITEM_BREADTH_MAX) * self::$INFO_ITEM_BREADTH_WEIGHT;
        $breadth_score += ($has_GBIF_map / self::$MAP_BREADTH_MAX) * self::$MAP_BREADTH_WEIGHT;
        $breadth_score += ($video_total / self::$VIDEO_BREADTH_MAX) * self::$VIDEO_BREADTH_WEIGHT;
        $breadth_score += ($sound_total / self::$SOUND_BREADTH_MAX) * self::$SOUND_BREADTH_WEIGHT;
        $breadth_score += ($iucn_total / self::$IUCN_BREADTH_MAX) * self::$IUCN_BREADTH_WEIGHT;
        $breadth_score += ($data_object_references / self::$REFERENCE_BREADTH_MAX) * self::$REFERENCE_BREADTH_WEIGHT;
        $scores['breadth'] = $breadth_score;
        
        $depth_score = 0;
        $depth_score += ($words_per_text / self::$TEXT_DEPTH_MAX) * self::$TEXT_DEPTH_WEIGHT;
        $scores['depth'] = $depth_score;
        
        $diversity_score = 0;
        $diversity_score += ($content_partners / self::$PARTNERS_DIVERSITY_MAX) * self::$PARTNERS_DIVERSITY_WEIGHT;
        $scores['diversity'] = $diversity_score;
        
        $total_score = 0;
        $total_score += $breadth_score * self::$BREADTH_WEIGHT;
        $total_score += $depth_score * self::$DEPTH_WEIGHT;
        $total_score += $diversity_score * self::$DIVERSITY_WEIGHT;
        $scores['total'] = $total_score;
        
        return $scores;
    }
    
    private static function sort_by_total_score($a, $b)
    {
        if ($a['total'] == $b['total']) return 0;
        return ($a['total'] < $b['total']) ? 1 : -1;
    }
}

?>