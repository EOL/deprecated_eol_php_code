<?php
include_once(dirname(__FILE__) . "/../config/environment.php");

class MergeConceptsHandler
{

  function merge_concepts($args)
  {

    $args['id1'] = @$argv[1];
    $args['id2'] = @$argv[2];
    $confirmed = @$argv[3];

    if(!$args['id1'] || !is_numeric($args['id1']) || !$args['id2'] || !is_numeric($args['id2']))
    {
        echo "\n\n\tsupercede_concepts.php [id1] [id2] [confirmed]\n\n";
        exit;
    }

    if($confirmed == 'confirmed')
    {
        TaxonConcept::supercede_by_ids($args['id1'], $args['id2'], true);
        echo "\nPages " . $args['id1'] . " and " . $args['id2'] . " have been merged to: " . min($args['id1'], $args['id2'])."\n\n";
    }else
    {
        $descendant_objects = TaxonConcept::count_descendants_objects($args['id1']);
        $descendants = TaxonConcept::count_descendants($args['id1']);
        echo "\n\nTaxonConcept1: " . $args['id1'] . "\n";
        echo "Descendant Objects:  $descendant_objects\n";
        echo "Descendant Concepts: $descendants\n";
        
        $descendant_objects = TaxonConcept::count_descendants_objects($args['id2']);
        $descendants = TaxonConcept::count_descendants($args['id2']);
        echo "\n\nTaxonConcept2: " . $args['id2'] . "\n";
        echo "Descendant Objects:  $descendant_objects\n";
        echo "Descendant Concepts: $descendants\n";
    }

  }

}

?>
