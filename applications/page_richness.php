<?php
namespace php_active_record;
  
  include_once(dirname(__FILE__) . "/../config/environment.php");
  require_library("PageRichnessCalculator");
  
  $taxon_concept_id = @$_REQUEST['taxon_concept_id'] ?: '';
  $taxon_concept_id2 = @$_REQUEST['taxon_concept_id2'] ?: '';
  
  $variable_names = array( 'VETTED_FACTOR', 'IMAGE_BREADTH_MAX', 'INFO_ITEM_BREADTH_MAX', 'MAP_BREADTH_MAX', 'VIDEO_BREADTH_MAX',
      'SOUND_BREADTH_MAX', 'IUCN_BREADTH_MAX', 'REFERENCE_BREADTH_MAX', 'IMAGE_BREADTH_WEIGHT',
      'INFO_ITEM_BREADTH_WEIGHT', 'MAP_BREADTH_WEIGHT', 'VIDEO_BREADTH_WEIGHT', 'SOUND_BREADTH_WEIGHT', 'IUCN_BREADTH_WEIGHT',
      'REFERENCE_BREADTH_WEIGHT', 'TEXT_TOTAL_MAX', 'TEXT_AVERAGE_MAX', 'TEXT_TOTAL_WEIGHT', 'TEXT_AVERAGE_WEIGHT',
      'PARTNERS_DIVERSITY_MAX', 'PARTNERS_DIVERSITY_WEIGHT', 'BREADTH_WEIGHT', 'DEPTH_WEIGHT', 'DIVERSITY_WEIGHT');
  
  foreach($variable_names as $variable_name)
  {
      $$variable_name = isset($_REQUEST[$variable_name]) ? $_REQUEST[$variable_name] : TaxonConceptMetric::$$variable_name;
  }
  
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
    td.category_weight { height: 30px; font-weight: bold; text-align:center; }
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
            <td><input type='text' size='5' name='IMAGE_BREADTH_MAX' value='<?php echo $IMAGE_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='IMAGE_BREADTH_WEIGHT' value='<?php echo $IMAGE_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>InfoItems:</td>
            <td><input type='text' size='5' name='INFO_ITEM_BREADTH_MAX' value='<?php echo $INFO_ITEM_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='INFO_ITEM_BREADTH_WEIGHT' value='<?php echo $INFO_ITEM_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>References:</td>
            <td><input type='text' size='5' name='REFERENCE_BREADTH_MAX' value='<?php echo $REFERENCE_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='REFERENCE_BREADTH_WEIGHT' value='<?php echo $REFERENCE_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>Maps:</td>
            <td><input type='text' size='5' name='MAP_BREADTH_MAX' value='<?php echo $MAP_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='MAP_BREADTH_WEIGHT' value='<?php echo $MAP_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>Videos:</td>
            <td><input type='text' size='5' name='VIDEO_BREADTH_MAX' value='<?php echo $VIDEO_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='VIDEO_BREADTH_WEIGHT' value='<?php echo $VIDEO_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>Sounds:</td>
            <td><input type='text' size='5' name='SOUND_BREADTH_MAX' value='<?php echo $SOUND_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='SOUND_BREADTH_WEIGHT' value='<?php echo $SOUND_BREADTH_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>IUCN:</td>
            <td><input type='text' size='5' name='IUCN_BREADTH_MAX' value='<?php echo $IUCN_BREADTH_MAX; ?>'/></td>
            <td><input type='text' size='5' name='IUCN_BREADTH_WEIGHT' value='<?php echo $IUCN_BREADTH_WEIGHT; ?>'/></td>
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
            <td><input type='text' size='5' name='TEXT_AVERAGE_MAX' value='<?php echo $TEXT_AVERAGE_MAX; ?>'/></td>
            <td><input type='text' size='5' name='TEXT_AVERAGE_WEIGHT' value='<?php echo $TEXT_AVERAGE_WEIGHT; ?>'/></td>
          </tr>
          <tr>
            <td>#Words total:</td>
            <td><input type='text' size='5' name='TEXT_TOTAL_MAX' value='<?php echo $TEXT_TOTAL_MAX; ?>'/></td>
            <td><input type='text' size='5' name='TEXT_TOTAL_WEIGHT' value='<?php echo $TEXT_TOTAL_WEIGHT; ?>'/></td>
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
            <td><input type='text' size='5' name='PARTNERS_DIVERSITY_MAX' value='<?php echo $PARTNERS_DIVERSITY_MAX; ?>'/></td>
            <td><input type='text' size='5' name='PARTNERS_DIVERSITY_WEIGHT' value='<?php echo $PARTNERS_DIVERSITY_WEIGHT; ?>'/></td>
          </tr>
        </table>
      </td>
    </tr>
    <tr>
      <td class='category_weight'>Category Weight: <input type='text' size='5' name='BREADTH_WEIGHT' value='<?php echo $BREADTH_WEIGHT; ?>'/></td>
      <td class='category_weight'>Category Weight: <input type='text' size='5' name='DEPTH_WEIGHT' value='<?php echo $DEPTH_WEIGHT; ?>'/></td>
      <td class='category_weight'>Category Weight: <input type='text' size='5' name='DIVERSITY_WEIGHT' value='<?php echo $DIVERSITY_WEIGHT; ?>'/></td>
    </tr>
    <tr>
      <td colspan='3' class='category_weight'>Vetted Factor: <input type='text' size='5' name='VETTED_FACTOR' value='<?php echo $VETTED_FACTOR; ?>'/></td>
    </tr>
  </table>
  <br/><br/>
  <table class='dual_input'><tr><td>
      PageID to evaluate: <input type='text' size='20' name='taxon_concept_id' value='<?php echo $taxon_concept_id ?>'/>
      <input type='submit' value='Calculate'/>
      <input type='hidden' name='ENV_NAME' value='<?php echo $GLOBALS['ENV_NAME']; ?>'/>
      <hr/>
      <?php show_results_for($taxon_concept_id); ?>
  </td><td class='second'>
      PageID to evaluate: <input type='text' size='20' name='taxon_concept_id2' value='<?php echo $taxon_concept_id2 ?>'/>
      <input type='submit' value='Calculate'/>
      <input type='hidden' name='ENV_NAME' value='<?php echo $GLOBALS['ENV_NAME']; ?>'/>
      </form>
      <hr/>
      <?php show_results_for($taxon_concept_id2); ?>
  </td></tr></table>
  </form>
</body>
</html>
<?php




function show_results_for($taxon_concept_id)
{
    if($taxon_concept_id)
    {
        $name = TaxonConcept::get_name($taxon_concept_id);
        $metric = TaxonConceptMetric::find_by_taxon_concept_id($taxon_concept_id);
        if(!isset($metric->image_total))
        {
            echo "Error or page not found";
            return;
        }
        $metric->set_weights($_REQUEST);
        $scores = $metric->scores();
        
        ?>
        <h3><a href='http://eol.org/pages/<?php echo $taxon_concept_id; ?>/overview' target='_blank'><?php echo $name; ?></a></h3>
        <table class='results'>
          <tr><td>Breadth:</td><td><?php echo round($scores['breadth'] / $metric->BREADTH_WEIGHT, 4); ?></td></tr>
          <tr><td>Depth:</td><td><?php echo round($scores['depth'] / $metric->DEPTH_WEIGHT, 4); ?></td></tr>
          <tr><td>Diversity:</td><td><?php echo round($scores['diversity'] / $metric->DIVERSITY_WEIGHT, 4); ?></td></tr>
          <tr><td>Total:</td><td><?php echo round($scores['total'], 4); ?></td></tr>
        </table>
        <hr/>
        <table class='results'>
          <tr><th>Stat</th><th>Value</th><th>Max</th><th>Impact on Score</th><th>Max</th></tr>
          <tr><td>Images:</td><td><?php echo $metric->weighted_images(); ?></td>
            <td class='max_score'><?php echo $metric->IMAGE_BREADTH_MAX; ?></td>
            <td><?php echo $metric->image_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->IMAGE_BREADTH_WEIGHT * $metric->BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>InfoItems:</td><td><?php echo $metric->info_items; ?></td>
            <td class='max_score'><?php echo $metric->INFO_ITEM_BREADTH_MAX; ?></td>
            <td><?php echo $metric->info_items_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->INFO_ITEM_BREADTH_WEIGHT * $metric->BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>References:</td><td><?php echo $metric->references(); ?></td>
            <td class='max_score'><?php echo $metric->REFERENCE_BREADTH_MAX; ?></td>
            <td><?php echo $metric->references_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->REFERENCE_BREADTH_WEIGHT * $metric->BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>Maps:</td><td><?php echo $metric->has_GBIF_map; ?></td>
            <td class='max_score'><?php echo $metric->MAP_BREADTH_MAX; ?></td>
            <td><?php echo $metric->maps_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->MAP_BREADTH_WEIGHT * $metric->BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>Videos:</td><td><?php echo $metric->weighted_videos(); ?></td>
            <td class='max_score'><?php echo $metric->VIDEO_BREADTH_MAX; ?></td>
            <td><?php echo $metric->videos_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->VIDEO_BREADTH_WEIGHT * $metric->BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>Sounds:</td><td><?php echo $metric->weighted_sounds(); ?></td>
            <td class='max_score'><?php echo $metric->SOUND_BREADTH_MAX; ?></td>
            <td><?php echo $metric->sounds_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->SOUND_BREADTH_WEIGHT * $metric->BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>IUCN:</td><td><?php echo $metric->iucn_total; ?></td>
            <td class='max_score'><?php echo $metric->IUCN_BREADTH_MAX; ?></td>
            <td><?php echo $metric->iucn_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->IUCN_BREADTH_WEIGHT * $metric->BREADTH_WEIGHT; ?></td></tr>
          
          <tr><td>Average #Words:</td><td><?php echo round($metric->average_words_weighted()); ?></td>
            <td class='max_score'><?php echo $metric->TEXT_AVERAGE_MAX; ?></td>
            <td><?php echo $metric->average_words_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->TEXT_AVERAGE_WEIGHT * $metric->DEPTH_WEIGHT; ?></td></tr>
          
          <tr><td>Total #Words:</td><td><?php echo round($metric->weighted_text_words()); ?></td>
            <td class='max_score'><?php echo $metric->TEXT_TOTAL_MAX; ?></td>
            <td><?php echo $metric->total_words_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->TEXT_TOTAL_WEIGHT * $metric->DEPTH_WEIGHT; ?></td></tr>
          
          <tr><td>Content Partners:</td><td><?php echo $metric->content_partners(); ?></td>
            <td class='max_score'><?php echo $metric->PARTNERS_DIVERSITY_MAX; ?></td>
            <td><?php echo $metric->content_partners_score(); ?></td>
            <td class='max_score'>/<?php echo $metric->PARTNERS_DIVERSITY_WEIGHT * $metric->DIVERSITY_WEIGHT; ?></td></tr>
        </table>
        <?php
    }
}



?>
