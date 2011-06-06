<?php
namespace php_active_record;

class TaxonConceptMetric extends ActiveRecord
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
    
    function __construct($param = null)
    {
        parent::__construct($param);
        $this->set_weights();
    }
    
    function set_weights($override_weights = array())
    {
        $variable_names = array( 'VETTED_FACTOR', 'IMAGE_BREADTH_MAX', 'INFO_ITEM_BREADTH_MAX', 'MAP_BREADTH_MAX', 'VIDEO_BREADTH_MAX',
            'SOUND_BREADTH_MAX', 'IUCN_BREADTH_MAX', 'REFERENCE_BREADTH_MAX', 'IMAGE_BREADTH_WEIGHT',
            'INFO_ITEM_BREADTH_WEIGHT', 'MAP_BREADTH_WEIGHT', 'VIDEO_BREADTH_WEIGHT', 'SOUND_BREADTH_WEIGHT', 'IUCN_BREADTH_WEIGHT',
            'REFERENCE_BREADTH_WEIGHT', 'TEXT_TOTAL_MAX', 'TEXT_AVERAGE_MAX', 'TEXT_TOTAL_WEIGHT', 'TEXT_AVERAGE_WEIGHT',
            'PARTNERS_DIVERSITY_MAX', 'PARTNERS_DIVERSITY_WEIGHT', 'BREADTH_WEIGHT', 'DEPTH_WEIGHT', 'DIVERSITY_WEIGHT');
        foreach($variable_names as $variable_name)
        {
            if(isset($override_weights[$variable_name]))
            {
                $this->$variable_name = $override_weights[$variable_name];
            }else
            {
                $this->$variable_name = self::$$variable_name;
            }
        }
    }
    
    function weighted_images()
    {
        return $this->image_trusted + ($this->VETTED_FACTOR * $this->image_unreviewed);
    }
    
    function weighted_text()
    {
        return $this->text_trusted + ($this->VETTED_FACTOR * $this->text_unreviewed);
    }
    
    function weighted_text_words()
    {
        return $this->text_trusted_words + ($this->VETTED_FACTOR * $this->text_unreviewed_words);
    }
    
    function weighted_videos()
    {
        return ($this->video_trusted + $this->flash_trusted + $this->youtube_trusted) + ($this->VETTED_FACTOR * ($this->video_unreviewed + $this->flash_unreviewed + $this->youtube_unreviewed));
    }
    
    function weighted_sounds()
    {
        return $this->sound_trusted + ($this->VETTED_FACTOR * $this->sound_unreviewed);
    }
    
    function videos()
    {
        return $this->video_total + $this->flash_total + $this->youtube_total;
    }
    
    function references()
    {
        $number_of_references = $this->data_object_references;
        if($this->BHL_publications) $number_of_references += $this->BHL_publications;
        return $number_of_references;
    }
    
    function content_partners()
    {
        $number_of_content_partners = $this->content_partners;
        if($this->BHL_publications) $number_of_content_partners++;
        if($this->has_GBIF_map) $number_of_content_partners++;
        if($this->user_submitted_text) $number_of_content_partners++;
        return $number_of_content_partners;
    }
    
    function average_words_weighted()
    {
        // we need to calculate the words per text using the weighted number of words and ACTUAL number of text objects
        // otherwise we could get a higher haverage words per text than before than before
        if($this->weighted_text_words($this->VETTED_FACTOR))
        {
            return $this->weighted_text_words($this->VETTED_FACTOR) / ($this->text_trusted + $this->text_unreviewed);
        }
        return 0;
    }
    
    
    
    function image_score()
    {
        return self::diminish($this->weighted_images(), $this->IMAGE_BREADTH_MAX) * $this->IMAGE_BREADTH_WEIGHT * $this->BREADTH_WEIGHT;
    }
    function info_items_score()
    {
        return self::diminish($this->info_items, $this->INFO_ITEM_BREADTH_MAX) * $this->INFO_ITEM_BREADTH_WEIGHT * $this->BREADTH_WEIGHT;
    }
    function maps_score()
    {
        return self::diminish($this->has_GBIF_map, $this->MAP_BREADTH_MAX) * $this->MAP_BREADTH_WEIGHT * $this->BREADTH_WEIGHT;
    }
    function videos_score()
    {
        return self::diminish($this->weighted_videos(), $this->VIDEO_BREADTH_MAX) * $this->VIDEO_BREADTH_WEIGHT * $this->BREADTH_WEIGHT;
    }
    function sounds_score()
    {
        return self::diminish($this->weighted_sounds(), $this->SOUND_BREADTH_MAX) * $this->SOUND_BREADTH_WEIGHT * $this->BREADTH_WEIGHT;
    }
    function iucn_score()
    {
        return self::diminish($this->iucn_total, $this->IUCN_BREADTH_MAX) * $this->IUCN_BREADTH_WEIGHT * $this->BREADTH_WEIGHT;
    }
    function references_score()
    {
        return self::diminish($this->references(), $this->REFERENCE_BREADTH_MAX) * $this->REFERENCE_BREADTH_WEIGHT * $this->BREADTH_WEIGHT;
    }
    function breadth_score()
    {
        $breadth_score = 0;
        $breadth_score += $this->image_score();
        $breadth_score += $this->info_items_score();
        $breadth_score += $this->maps_score();
        $breadth_score += $this->videos_score();
        $breadth_score += $this->sounds_score();
        $breadth_score += $this->iucn_score();
        $breadth_score += $this->references_score();
        return $breadth_score;
    }
    
    
    function total_words_score()
    {
        return self::diminish($this->weighted_text_words(), $this->TEXT_TOTAL_MAX) * $this->TEXT_TOTAL_WEIGHT * $this->DEPTH_WEIGHT;
    }
    function average_words_score()
    {
        return self::diminish($this->average_words_weighted(), $this->TEXT_AVERAGE_MAX) * $this->TEXT_AVERAGE_WEIGHT * $this->DEPTH_WEIGHT;
    }
    function depth_score()
    {
        $depth_score = 0;
        $depth_score += $this->total_words_score();
        $depth_score += $this->average_words_score();
        return $depth_score;
    }
    
    function content_partners_score()
    {
        return self::diminish($this->content_partners(), $this->PARTNERS_DIVERSITY_MAX) * $this->PARTNERS_DIVERSITY_WEIGHT * $this->DIVERSITY_WEIGHT;
    }
    function diversity_score()
    {
        $diversity_score = 0;
        $diversity_score += $this->content_partners_score();
        return $diversity_score;
    }
    
    function scores()
    {
        $scores = array();
        $scores['breadth'] = $this->breadth_score();
        $scores['depth'] = $this->depth_score();
        $scores['diversity'] = $this->diversity_score();
        
        $total_score = 0;
        $total_score += $scores['breadth'];
        $total_score += $scores['depth'];
        $total_score += $scores['diversity'];
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
}

?>