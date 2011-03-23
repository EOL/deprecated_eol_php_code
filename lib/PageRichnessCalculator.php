<?php

require_once(DOC_ROOT . "vendor/text_statistics/TextStatistics.php");

class PageRichnessCalculator
{
    static $VETTED_FACTOR = .75;
    
    // breadth
    static $IMAGE_BREADTH_MAX = 10;
    static $INFO_ITEM_BREADTH_MAX = 25;
    static $MAP_BREADTH_MAX = 1;
    static $VIDEO_BREADTH_MAX = 1;
    static $SOUND_BREADTH_MAX = 1;
    static $IUCN_BREADTH_MAX = 1;
    static $REFERENCE_BREADTH_MAX = 20;
    
    static $IMAGE_BREADTH_WEIGHT = .2;
    static $INFO_ITEM_BREADTH_WEIGHT = .4;
    static $MAP_BREADTH_WEIGHT = .15;
    static $VIDEO_BREADTH_WEIGHT = .12;
    static $SOUND_BREADTH_WEIGHT = .05;
    static $IUCN_BREADTH_WEIGHT = .02;
    static $REFERENCE_BREADTH_WEIGHT = .06;
    
    // depth
    static $TEXT_TOTAL_MAX = 6000;
    static $TEXT_AVERAGE_MAX = 500;
    static $TEXT_TOTAL_WEIGHT = .7;
    static $TEXT_AVERAGE_WEIGHT = .3;
    
    // diversity
    static $PARTNERS_DIVERSITY_MAX = 25;
    static $PARTNERS_DIVERSITY_WEIGHT = 1;
    
    // category weights
    static $BREADTH_WEIGHT = .6;
    static $DEPTH_WEIGHT = .3;
    static $DIVERSITY_WEIGHT = .1;
    
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
        $query = "SELECT taxon_concept_id, image_trusted, image_unreviewed, text_trusted, text_unreviewed, text_trusted_words, text_unreviewed_words, video_trusted, video_unreviewed, sound_trusted, sound_unreviewed, flash_trusted, flash_unreviewed, youtube_trusted, youtube_unreviewed, iucn_total, data_object_references, info_items, content_partners, has_GBIF_map FROM taxon_concept_metrics WHERE taxon_concept_id=$taxon_concept_id";
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
        $query = "SELECT taxon_concept_id, image_trusted, image_unreviewed, text_trusted, text_unreviewed, text_trusted_words, text_unreviewed_words, video_trusted, video_unreviewed, sound_trusted, sound_unreviewed, flash_trusted, flash_unreviewed, youtube_trusted, youtube_unreviewed, iucn_total, data_object_references, info_items, content_partners, has_GBIF_map FROM taxon_concept_metrics";
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
        static $num = 0;
        $OUT = fopen(DOC_ROOT . '/tmp/richness.txt', 'w+');
        fwrite($OUT, "RANK\tID\tNAME\tBREADTH\tDEPTH\tDIVERSITY\tTOTAL\n");
        foreach($all_scores as $id => $scores)
        {
            $num++;
            if($num >= 2500) break;
            $str = "$num\t$id\t" . TaxonConcept::get_name($id) ."\t";
            $str .= $scores['breadth']."\t";
            $str .= $scores['depth']."\t";
            $str .= $scores['diversity']."\t";
            $str .= $scores['total']."\n";
            fwrite($OUT, $str);
        }
        fclose($OUT);
        return $all_scores;
    }
    
    public function calculate_score_from_row($row)
    {
        $scores = array();
        $taxon_concept_id = $row[0];
        $image_trusted = $row[1];
        $image_unreviewed = $row[2];
        $text_trusted = $row[3];
        $text_unreviewed = $row[4];
        $text_words_trusted = $row[5];
        $text_words_unreviewed = $row[6];
        $video_trusted = $row[7];
        $video_unreviewed = $row[8];
        $sound_trusted = $row[9];
        $sound_unreviewed = $row[10];
        $flash_trusted = $row[11];
        $flash_unreviewed = $row[12];
        $youtube_trusted = $row[13];
        $youtube_unreviewed = $row[14];
        $iucn_total = $row[15];
        $data_object_references = $row[16];
        $info_items = $row[17];
        $content_partners = $row[18];
        $has_GBIF_map = $row[19];
        
        $image_total = $image_trusted + (self::$VETTED_FACTOR * $image_unreviewed);
        $text_total = $text_trusted + (self::$VETTED_FACTOR * $text_unreviewed);
        $text_total_words = $text_words_trusted + (self::$VETTED_FACTOR * $text_words_unreviewed);
        $sound_total = $sound_trusted + (self::$VETTED_FACTOR * $sound_unreviewed);
        $flash_total = $flash_trusted + (self::$VETTED_FACTOR * $flash_unreviewed);
        $youtube_total = $youtube_trusted + (self::$VETTED_FACTOR * $youtube_unreviewed);
        $video_total = $video_trusted + (self::$VETTED_FACTOR * $video_unreviewed);
        $video_total += $flash_total + $youtube_total;
        
        // we need to calculate the words per text using the weighted number of words and ACTUAL number of text objects
        // otherwise we could get a higher haverage words per text than before than before
        $words_per_text = 0;
        if($text_total) $words_per_text = $text_total_words / ($text_trusted + $text_unreviewed);
        
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