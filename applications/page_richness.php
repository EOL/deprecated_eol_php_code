<?php
  include_once(dirname(__FILE__) . "/../config/environment.php");
  require_library("PageRichnessCalculator");
  
  //print_pre($_REQUEST);
  $taxon_concept_id = @$_REQUEST['taxon_concept_id'] ?: '';
  $taxon_concept_id2 = @$_REQUEST['taxon_concept_id2'] ?: '';
  $IMAGE_BREADTH_MAX = @$_REQUEST['IMAGE_BREADTH_MAX'] ?: PageRichnessCalculator::$IMAGE_BREADTH_MAX;
  $INFO_ITEM_BREADTH_MAX = @$_REQUEST['INFO_ITEM_BREADTH_MAX'] ?: PageRichnessCalculator::$INFO_ITEM_BREADTH_MAX;
  $MAP_BREADTH_MAX = @$_REQUEST['MAP_BREADTH_MAX'] ?: PageRichnessCalculator::$MAP_BREADTH_MAX;
  $VIDEO_BREADTH_MAX = @$_REQUEST['VIDEO_BREADTH_MAX'] ?: PageRichnessCalculator::$VIDEO_BREADTH_MAX;
  $SOUND_BREADTH_MAX = @$_REQUEST['SOUND_BREADTH_MAX'] ?: PageRichnessCalculator::$SOUND_BREADTH_MAX;
  $IUCN_BREADTH_MAX = @$_REQUEST['IUCN_BREADTH_MAX'] ?: PageRichnessCalculator::$IUCN_BREADTH_MAX;
  $REFERENCE_BREADTH_MAX = @$_REQUEST['REFERENCE_BREADTH_MAX'] ?: PageRichnessCalculator::$REFERENCE_BREADTH_MAX;
  
  $IMAGE_BREADTH_WEIGHT = @$_REQUEST['IMAGE_BREADTH_WEIGHT'] ?: PageRichnessCalculator::$IMAGE_BREADTH_WEIGHT;
  $INFO_ITEM_BREADTH_WEIGHT = @$_REQUEST['INFO_ITEM_BREADTH_WEIGHT'] ?: PageRichnessCalculator::$INFO_ITEM_BREADTH_WEIGHT;
  $MAP_BREADTH_WEIGHT = @$_REQUEST['MAP_BREADTH_WEIGHT'] ?: PageRichnessCalculator::$MAP_BREADTH_WEIGHT;
  $VIDEO_BREADTH_WEIGHT = @$_REQUEST['VIDEO_BREADTH_WEIGHT'] ?: PageRichnessCalculator::$VIDEO_BREADTH_WEIGHT;
  $SOUND_BREADTH_WEIGHT = @$_REQUEST['SOUND_BREADTH_WEIGHT'] ?: PageRichnessCalculator::$SOUND_BREADTH_WEIGHT;
  $IUCN_BREADTH_WEIGHT = @$_REQUEST['IUCN_BREADTH_WEIGHT'] ?: PageRichnessCalculator::$IUCN_BREADTH_WEIGHT;
  $REFERENCE_BREADTH_WEIGHT = @$_REQUEST['REFERENCE_BREADTH_WEIGHT'] ?: PageRichnessCalculator::$REFERENCE_BREADTH_WEIGHT;
  
  $TEXT_DEPTH_MAX = @$_REQUEST['TEXT_DEPTH_MAX'] ?: PageRichnessCalculator::$TEXT_DEPTH_MAX;
  $TEXT_DEPTH_WEIGHT = @$_REQUEST['TEXT_DEPTH_WEIGHT'] ?: PageRichnessCalculator::$TEXT_DEPTH_WEIGHT;
  
  $PARTNERS_DIVERSITY_MAX = @$_REQUEST['PARTNERS_DIVERSITY_MAX'] ?: PageRichnessCalculator::$PARTNERS_DIVERSITY_MAX;
  $PARTNERS_DIVERSITY_WEIGHT = @$_REQUEST['PARTNERS_DIVERSITY_WEIGHT'] ?: PageRichnessCalculator::$PARTNERS_DIVERSITY_WEIGHT;
  
  $BREADTH_WEIGHT = @$_REQUEST['BREADTH_WEIGHT'] ?: PageRichnessCalculator::$BREADTH_WEIGHT;
  $DEPTH_WEIGHT = @$_REQUEST['DEPTH_WEIGHT'] ?: PageRichnessCalculator::$DEPTH_WEIGHT;
  $DIVERSITY_WEIGHT = @$_REQUEST['DIVERSITY_WEIGHT'] ?: PageRichnessCalculator::$DIVERSITY_WEIGHT;
?>
<html>
<head>
  <style type="text/css">
    table.main_table { border: 1px solid black; width: 750px; }
    table.sub_table { border: 1px solid black; }
    table.results { cellspacing:0; cellspacing:0; }
    table.results td { width: 120px; text-align: right; }
    table.results td.max_score { width: 40px; text-align: left; padding-left: 30px; }
    table.dual_input td { vertical-align: top; }
    table.dual_input td.second { padding-left: 10px; border-left: 2px black solid; }
    td.category_weight { height: 60px; font-weight: bold; text-align:center; }
  </style>
</head>
<body>
  <form action='page_richness.php' method='post'>
  <table class='main_table'>
    <tr valign='top'>
      <td><h3 align='center'>Breadth</h2>
        <table class='sub_table'>
          <tr>
            <th>Category</th>
            <th>Max Count</th>
            <th>Weight</th>
          </tr>
          <tr>
            <td>Images:</td>
            <td><input type='text' size='5' name='IMAGE_BREADTH_MAX' value='<?= $IMAGE_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='IMAGE_BREADTH_WEIGHT' value='<?= $IMAGE_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>InfoItems:</td>
            <td><input type='text' size='5' name='INFO_ITEM_BREADTH_MAX' value='<?= $INFO_ITEM_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='INFO_ITEM_BREADTH_WEIGHT' value='<?= $INFO_ITEM_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>References:</td>
            <td><input type='text' size='5' name='REFERENCE_BREADTH_MAX' value='<?= $REFERENCE_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='REFERENCE_BREADTH_WEIGHT' value='<?= $REFERENCE_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>Maps:</td>
            <td><input type='text' size='5' name='MAP_BREADTH_MAX' value='<?= $MAP_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='MAP_BREADTH_WEIGHT' value='<?= $MAP_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>Videos:</td>
            <td><input type='text' size='5' name='VIDEO_BREADTH_MAX' value='<?= $VIDEO_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='VIDEO_BREADTH_WEIGHT' value='<?= $VIDEO_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>Sounds:</td>
            <td><input type='text' size='5' name='SOUND_BREADTH_MAX' value='<?= $SOUND_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='SOUND_BREADTH_WEIGHT' value='<?= $SOUND_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>IUCN:</td>
            <td><input type='text' size='5' name='IUCN_BREADTH_MAX' value='<?= $IUCN_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='IUCN_BREADTH_WEIGHT' value='<?= $IUCN_BREADTH_WEIGHT; ?>'/></td>
          </tr>
        </table>
      </td>
      
      
      <td><h3 align='center'>Depth</h3>
        <table class='sub_table'>
          <tr>
            <th>Category</th>
            <th>Max Count</th>
            <th>Weight</th>
          </tr>
          <tr>
            <td>#Words per text:</td>
            <td><input type='text' size='5' name='TEXT_DEPTH_MAX' value='<?= $TEXT_DEPTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='TEXT_DEPTH_WEIGHT' value='<?= $TEXT_DEPTH_WEIGHT; ?>'/></td>
          </tr>
        </table>
      </td>
      
      
      <td><h3 align='center'>Diversity</h3>
        <table class='sub_table'>
          <tr>
            <th>Category</th>
            <th>Max Count</th>
            <th>Weight</th>
          </tr>
          <tr>
            <td>Partners:</td>
            <td><input type='text' size='5' name='PARTNERS_DIVERSITY_MAX' value='<?= $PARTNERS_DIVERSITY_MAX; ?>'/></td>
            <td><input type='text' size='5' name='PARTNERS_DIVERSITY_WEIGHT' value='<?= $PARTNERS_DIVERSITY_WEIGHT; ?>'/></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class='category_weight'>Category Weight: <input type='text' size='5' name='BREADTH_WEIGHT' value='<?= $BREADTH_WEIGHT; ?>'/></td>
      <td class='category_weight'>Category Weight: <input type='text' size='5' name='DEPTH_WEIGHT' value='<?= $DEPTH_WEIGHT; ?>'/></td>
      <td class='category_weight'>Category Weight: <input type='text' size='5' name='DIVERSITY_WEIGHT' value='<?= $DIVERSITY_WEIGHT; ?>'/></td>
    </tr>
  </table>
  <br/><br/>
  <table class='dual_input'><tr><td>
      PageID to evaluate: <input type='text' size='20' name='taxon_concept_id' value='<?= $taxon_concept_id ?>'/>
      <input type='submit' value='Calculate'/>
      <input type='hidden' name='ENV_NAME' value='<?= $GLOBALS['ENV_NAME']; ?>'/>
      <hr/>
      <? show_results_for($taxon_concept_id); ?>
  </td><td class='second'>
      PageID to evaluate: <input type='text' size='20' name='taxon_concept_id2' value='<?= $taxon_concept_id2 ?>'/>
      <input type='submit' value='Calculate'/>
      <input type='hidden' name='ENV_NAME' value='<?= $GLOBALS['ENV_NAME']; ?>'/>
      </form>
      <hr/>
      <? show_results_for($taxon_concept_id2); ?>
  </td></tr></table>
  </form>
</body>
</html>
<?php




function show_results_for($taxon_concept_id)
{
    global $IMAGE_BREADTH_MAX;
    global $INFO_ITEM_BREADTH_MAX;
    global $MAP_BREADTH_MAX;
    global $VIDEO_BREADTH_MAX;
    global $SOUND_BREADTH_MAX;
    global $IUCN_BREADTH_MAX;
    global $REFERENCE_BREADTH_MAX;
    
    global $IMAGE_BREADTH_WEIGHT;
    global $INFO_ITEM_BREADTH_WEIGHT;
    global $MAP_BREADTH_WEIGHT;
    global $VIDEO_BREADTH_WEIGHT;
    global $SOUND_BREADTH_WEIGHT;
    global $IUCN_BREADTH_WEIGHT;
    global $REFERENCE_BREADTH_WEIGHT;
    
    global $TEXT_DEPTH_MAX;
    global $TEXT_DEPTH_WEIGHT;
    
    global $PARTNERS_DIVERSITY_MAX;
    global $PARTNERS_DIVERSITY_WEIGHT;
    
    global $BREADTH_WEIGHT;
    global $DEPTH_WEIGHT;
    global $DIVERSITY_WEIGHT;
      
    if($taxon_concept_id)
    {
        $calc = new PageRichnessCalculator($_REQUEST);
        $scores = $calc->score_for_page($taxon_concept_id);
        $name = TaxonConcept::get_name($taxon_concept_id);
        $metric = new TaxonConceptMetric($taxon_concept_id);
        if(!isset($metric->image_total)) return;
        ?>
        <h3><a href='http://www.eol.org/pages/<?= $taxon_concept_id; ?>' target='_blank'><?= $name; ?></a></h3>
        <table class='results'>
          <tr><td>Breadth:</td><td><?= round($scores['breadth'], 4); ?></td></tr>
          <tr><td>Depth:</td><td><?= round($scores['depth'], 4); ?></td></tr>
          <tr><td>Diversity:</td><td><?= round($scores['diversity'], 4); ?></td></tr>
          <tr><td>Total:</td><td><?= round($scores['total'], 4); ?></td></tr>
        </table>
        <hr/>
        <table class='results'>
          <tr><th>Stat</th><th>Value</th><th>Max</th><th>Impact on Score</th><th>Max</th></tr>
          <tr><td>Images:</td><td><?= $metric->image_total; ?></td>
            <td class='max_score'><?= $IMAGE_BREADTH_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->image_total, $IMAGE_BREADTH_MAX) * $IMAGE_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td>
            <td class='max_score'>/<?= $IMAGE_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>InfoItems:</td><td><?= $metric->info_items; ?></td>
            <td class='max_score'><?= $INFO_ITEM_BREADTH_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->info_items, $INFO_ITEM_BREADTH_MAX) * $INFO_ITEM_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td>
            <td class='max_score'>/<?= $INFO_ITEM_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>References:</td><td><?= $metric->data_object_references; ?></td>
            <td class='max_score'><?= $REFERENCE_BREADTH_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->data_object_references, $REFERENCE_BREADTH_MAX) * $REFERENCE_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td>
            <td class='max_score'>/<?= $REFERENCE_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>Maps:</td><td><?= $metric->has_GBIF_map; ?></td>
            <td class='max_score'><?= $MAP_BREADTH_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->has_GBIF_map, $MAP_BREADTH_MAX) * $MAP_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td>
            <td class='max_score'>/<?= $MAP_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>Videos:</td><td><?= $metric->videos(); ?></td>
            <td class='max_score'><?= $VIDEO_BREADTH_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->videos(), $VIDEO_BREADTH_MAX) * $VIDEO_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td>
            <td class='max_score'>/<?= $VIDEO_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>Sounds:</td><td><?= $metric->sound_total; ?></td>
            <td class='max_score'><?= $SOUND_BREADTH_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->sound_total, $SOUND_BREADTH_MAX) * $SOUND_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td>
            <td class='max_score'>/<?= $SOUND_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>IUCN:</td><td><?= $metric->iucn_total; ?></td>
            <td class='max_score'><?= $IUCN_BREADTH_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->iucn_total, $IUCN_BREADTH_MAX) * $IUCN_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td>
            <td class='max_score'>/<?= $IUCN_BREADTH_WEIGHT * $BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>Average #Words:</td><td><?= round($metric->average_words()); ?></td>
            <td class='max_score'><?= $TEXT_DEPTH_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->average_words(), $TEXT_DEPTH_MAX) * $TEXT_DEPTH_WEIGHT * $DEPTH_WEIGHT; ?></td>
            <td class='max_score'>/<?= $TEXT_DEPTH_WEIGHT * $DEPTH_WEIGHT; ?></td></tr>
          
          <tr><td>Content Partners:</td><td><?= $metric->content_partners; ?></td>
            <td class='max_score'><?= $PARTNERS_DIVERSITY_MAX; ?></td>
            <td><?= PageRichnessCalculator::diminish($metric->content_partners, $PARTNERS_DIVERSITY_MAX) * $PARTNERS_DIVERSITY_WEIGHT * $DIVERSITY_WEIGHT; ?></td>
            <td class='max_score'>/<?= $PARTNERS_DIVERSITY_WEIGHT * $DIVERSITY_WEIGHT; ?></td></tr>
        </table>
        <?
    }
}



