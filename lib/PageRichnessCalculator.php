<?php

require_once(DOC_ROOT . "vendor/text_statistics/TextStatistics.php");

class PageRichnessCalculator
{
    // breadth
    static $IMAGE_BREADTH_MAX = 25;
    static $INFO_ITEM_BREADTH_MAX = 25;
    static $MAP_BREADTH_MAX = 1;
    static $VIDEO_BREADTH_MAX = 1;
    static $SOUND_BREADTH_MAX = 1;
    static $IUCN_BREADTH_MAX = 1;
    static $REFERENCE_BREADTH_MAX = 20;
    
    static $IMAGE_BREADTH_WEIGHT = .2;
    static $INFO_ITEM_BREADTH_WEIGHT = .45;
    static $MAP_BREADTH_WEIGHT = .08;
    static $VIDEO_BREADTH_WEIGHT = .13;
    static $SOUND_BREADTH_WEIGHT = 0;
    static $IUCN_BREADTH_WEIGHT = .08;
    static $REFERENCE_BREADTH_WEIGHT = .06;
    
    // depth
    static $TEXT_TOTAL_MAX = 6000;
    static $TEXT_AVERAGE_MAX = 1000;
    static $TEXT_TOTAL_WEIGHT = .3;
    static $TEXT_AVERAGE_WEIGHT = .7;
    
    // diversity
    static $PARTNERS_DIVERSITY_MAX = 25;
    static $PARTNERS_DIVERSITY_WEIGHT = 1;
    
    // category weights
    static $BREADTH_WEIGHT = .6;
    static $DEPTH_WEIGHT = .2;
    static $DIVERSITY_WEIGHT = .2;
    
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
            if($i==0) echo "QUERY IS DONE (".time_elapsed().")\n";
            $i++;
            if($i%10000==0) echo "$i: ". memory_get_usage() ."\n";
            $taxon_concept_id = $row[0];
            $this_scores = $this->calculate_score_from_row($row);
            if($this_scores['total'] >= .5) $all_scores[$taxon_concept_id] = $this_scores;
        }
        
        echo "CALCULATIONS ARE DONE (".time_elapsed().")\n";
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
        $image_total = $row[1];
        $text_total = $row[2];
        $text_total_words = $row[3];
        $video_total = $row[4];
        $sound_total = $row[5];
        $flash_total = $row[6];
        $youtube_total = $row[7];
        $iucn_total = $row[8];
        $data_object_references = $row[9];
        $info_items = $row[10];
        $content_partners = $row[11];
        $has_GBIF_map = $row[12];
        
        $video_total += $flash_total + $youtube_total;
        
        $words_per_text = 0;
        if($text_total) $words_per_text = $text_total_words / $text_total;
        
        $breadth_score = 0;
        $breadth_score += self::diminish($image_total, self::$IMAGE_BREADTH_MAX) * self::$IMAGE_BREADTH_WEIGHT;
        $breadth_score += self::diminish($info_items, self::$INFO_ITEM_BREADTH_MAX) * self::$INFO_ITEM_BREADTH_WEIGHT;
        $breadth_score += self::diminish($has_GBIF_map, self::$MAP_BREADTH_MAX) * self::$MAP_BREADTH_WEIGHT;
        $breadth_score += self::diminish($video_total, self::$VIDEO_BREADTH_MAX) * self::$VIDEO_BREADTH_WEIGHT;
        $breadth_score += self::diminish($sound_total, self::$SOUND_BREADTH_MAX) * self::$SOUND_BREADTH_WEIGHT;
        $breadth_score += self::diminish($iucn_total, self::$IUCN_BREADTH_MAX) * self::$IUCN_BREADTH_WEIGHT;
        $breadth_score += self::diminish($data_object_references, self::$REFERENCE_BREADTH_MAX) * self::$REFERENCE_BREADTH_WEIGHT;
        $scores['breadth'] = $breadth_score;
        
        $depth_score = 0;
        $depth_score += self::diminish($text_total_words, self::$TEXT_TOTAL_MAX) * self::$TEXT_TOTAL_WEIGHT;
        $depth_score += self::diminish($words_per_text, self::$TEXT_AVERAGE_MAX) * self::$TEXT_AVERAGE_WEIGHT;
        $scores['depth'] = $depth_score;
        
        $diversity_score = 0;
        $diversity_score += self::diminish($content_partners, self::$PARTNERS_DIVERSITY_MAX) * self::$PARTNERS_DIVERSITY_WEIGHT;
        $scores['diversity'] = $diversity_score;
        
        $total_score = 0;
        $total_score += $breadth_score * self::$BREADTH_WEIGHT;
        $total_score += $depth_score * self::$DEPTH_WEIGHT;
        $total_score += $diversity_score * self::$DIVERSITY_WEIGHT;
        $scores['total'] = $total_score;
        
        return $scores;
    }
    
    public static function diminish($value, $maximum)
    {
        // using log base 10
        // log(1,10) == 0; log(10,10) == 10
        // 0 becomes 0, 1 becomes 1, .1 becomes .28, .5 becomes .74
        $value = min($value, $maximum);
        return round(log(((($value / $maximum) * 9) + 1), 10), 4);
    }
    
    private static function sort_by_total_score($a, $b)
    {
        if ($a['total'] == $b['total']) return 0;
        return ($a['total'] < $b['total']) ? 1 : -1;
    }
}

?>