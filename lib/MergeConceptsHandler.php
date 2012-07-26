<?php
namespace php_active_record;
include_once(dirname(__FILE__) . "/../config/environment.php");

class MergeConceptsHandler
{

  public static function merge_concepts($args)
  {

    if(!$args['id1'] || !is_numeric($args['id1']) || !$args['id2'] || !is_numeric($args['id2']))
    {
        throw new \Exception("supercede_concepts.php [id1] [id2] [confirmed]");
    }

    if($args['confirmed'] == 'confirmed')
    {

        echo "++ Merging TC#" . $args['id1'] . " to " . $args['id2'] . "\n";

        TaxonConcept::supercede_by_ids($args['id1'], $args['id2'], true);
        echo "\nPages " . $args['id1'] . " and " . $args['id2'] . " have been merged to: " . min($args['id1'], $args['id2'])."\n\n";

        // Only applicable if run via resque, but safe *enough* otherwise:
        TaxonConcept::unlock_classifications_by_id($args['id1'], $args['notify']);
        TaxonConcept::unlock_classifications_by_id($args['id2'], $args['notify']);

        echo "++ Done.\n";

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
