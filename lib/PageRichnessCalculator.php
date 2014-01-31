<?php
namespace php_active_record;

require_once(DOC_ROOT . "vendor/text_statistics/TextStatistics.php");

class PageRichnessCalculator
{
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
        $query = "SELECT taxon_concept_id, image_trusted, image_unreviewed, text_trusted, text_unreviewed, text_trusted_words, text_unreviewed_words, video_trusted, video_unreviewed, sound_trusted, sound_unreviewed, flash_trusted, flash_unreviewed, youtube_trusted, youtube_unreviewed, iucn_total, data_object_references, info_items, content_partners, has_GBIF_map, BHL_publications, user_submitted_text FROM taxon_concept_metrics WHERE taxon_concept_id=$taxon_concept_id";
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
        $batches = array(1);
        if($taxon_concept_ids) $batches = array_chunk($taxon_concept_ids, 10000);
        
        foreach($batches as $batch)
        {
            $query = "SELECT taxon_concept_id, image_trusted, image_unreviewed, text_trusted, text_unreviewed, text_trusted_words, text_unreviewed_words, video_trusted, video_unreviewed, sound_trusted, sound_unreviewed, flash_trusted, flash_unreviewed, youtube_trusted, youtube_unreviewed, iucn_total, data_object_references, info_items, content_partners, has_GBIF_map, BHL_publications, user_submitted_text FROM taxon_concept_metrics";
            if($taxon_concept_ids) $query .= " WHERE taxon_concept_id IN (". implode($batch, ",") .")";
            elseif($range) $query .= " WHERE taxon_concept_id BETWEEN $range";
            foreach($this->mysqli_slave->iterate_file($query) as $row_num => $row)
            {
                static $i=0;
                if($i==0) echo "QUERY IS DONE (".time_elapsed().")\n";
                $i++;
                if($i%10000==0) echo "$i: ". memory_get_usage() ."\n";
                $taxon_concept_id = $row[0];
                $this_scores = $this->calculate_score_from_row($row);
                // if($this_scores['total'] >= .5) $all_scores[$taxon_concept_id] = $this_scores;
                $all_scores[$taxon_concept_id] = $this_scores['total'];
            }
        }
        
        echo "CALCULATIONS ARE DONE (".time_elapsed().") $range\n";
        // uasort($all_scores, array('self', 'sort_by_total_score'));
        static $num = 0;
        // $OUT = fopen(DOC_ROOT . '/tmp/richness.txt', 'w+');
        // fwrite($OUT, "RANK\tID\tNAME\tBREADTH\tDEPTH\tDIVERSITY\tTOTAL\n");
        // foreach($all_scores as $id => $scores)
        // {
        //     $num++;
        //     // if($num >= 2500) break;
        //     $str = "$num\t$id\t" . TaxonConcept::get_name($id) ."\t";
        //     $str .= $scores['breadth'] / TaxonConceptMetric::$BREADTH_WEIGHT ."\t";
        //     $str .= $scores['depth'] / TaxonConceptMetric::$DEPTH_WEIGHT ."\t";
        //     $str .= $scores['diversity'] / TaxonConceptMetric::$DIVERSITY_WEIGHT ."\t";
        //     $str .= $scores['total']."\n";
        //     fwrite($OUT, $str);
        // }
        // fclose($OUT);
        return $all_scores;
    }
    
    public function calculate_score_from_row($row)
    {
        // if(!$row[1]&&!$row[2]&&!$row[3]&&!$row[4]&&!$row[5]&&!$row[6]&&!$row[7]&&!$row[8]&&!$row[9]&&!$row[10]&&!$row[11]&&!$row[12]&&
        //     !$row[13]&&!$row[14]&&!$row[15]&&!$row[16]&&!$row[17]) return 0;
        
        $taxon_concept_metric = new TaxonConceptMetric();
        $taxon_concept_metric->taxon_concept_id = $row[0];
        $taxon_concept_metric->image_trusted = $row[1];
        $taxon_concept_metric->image_unreviewed = $row[2];
        $taxon_concept_metric->text_trusted = $row[3];
        $taxon_concept_metric->text_unreviewed = $row[4];
        $taxon_concept_metric->text_trusted_words = $row[5];
        $taxon_concept_metric->text_unreviewed_words = $row[6];
        $taxon_concept_metric->video_trusted = $row[7];
        $taxon_concept_metric->video_unreviewed = $row[8];
        $taxon_concept_metric->sound_trusted = $row[9];
        $taxon_concept_metric->sound_unreviewed = $row[10];
        $taxon_concept_metric->flash_trusted = $row[11];
        $taxon_concept_metric->flash_unreviewed = $row[12];
        $taxon_concept_metric->youtube_trusted = $row[13];
        $taxon_concept_metric->youtube_unreviewed = $row[14];
        $taxon_concept_metric->iucn_total = $row[15];
        $taxon_concept_metric->data_object_references = $row[16];
        $taxon_concept_metric->info_items = $row[17];
        $taxon_concept_metric->content_partners = $row[18];
        $taxon_concept_metric->has_GBIF_map = $row[19];
        $taxon_concept_metric->BHL_publications = $row[20];
        $taxon_concept_metric->user_submitted_text = $row[21];
        
        return $taxon_concept_metric->scores();
    }
    
    
    private static function sort_by_total_score($a, $b)
    {
        if ($a['total'] == $b['total']) return 0;
        return ($a['total'] < $b['total']) ? 1 : -1;
    }
}

?>