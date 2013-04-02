<?php
namespace php_active_record;

class MergeConceptsHandler
{
    public static function merge_concepts($args)
    {
        $taxon_concept_id_1 = @$args['id1'];
        $taxon_concept_id_2 = @$args['id2'];
        $confirmation = @$args['confirmed'];
        if(!$taxon_concept_id_1 || !is_numeric($taxon_concept_id_1) ||
           !$taxon_concept_id_2 || !is_numeric($taxon_concept_id_2))
        {
            throw new \Exception("supercede_concepts.php [id1] [id2] [confirmed]");
        }
        
        if($confirmation == 'confirmed')
        {
            \CodeBridge::print_message("Merging TC# $taxon_concept_id_1 to $taxon_concept_id_2");
            TaxonConcept::supercede_by_ids($taxon_concept_id_1, $taxon_concept_id_2, true);
            
            // Only applicable if run via resque, but safe *enough* otherwise:
            TaxonConcept::unlock_classifications_by_id($taxon_concept_id_1);
            TaxonConcept::unlock_classifications_by_id($taxon_concept_id_2);
            \CodeBridge::print_message("Done. Pages $taxon_concept_id_1 and $taxon_concept_id_2 have been merged to: ". min($taxon_concept_id_1, $taxon_concept_id_2));
        }else
        {
            $descendant_objects = TaxonConcept::count_descendants_objects($taxon_concept_id_1);
            $descendants = TaxonConcept::count_descendants($taxon_concept_id_1);
            echo "\n\nTaxonConcept1: " . $taxon_concept_id_1 . "\n";
            echo "Descendant Objects:  $descendant_objects\n";
            echo "Descendant Concepts: $descendants\n";
            
            $descendant_objects = TaxonConcept::count_descendants_objects($taxon_concept_id_2);
            $descendants = TaxonConcept::count_descendants($taxon_concept_id_2);
            echo "\n\nTaxonConcept2: " . $taxon_concept_id_2 . "\n";
            echo "Descendant Objects:  $descendant_objects\n";
            echo "Descendant Concepts: $descendants\n";
        }
    }
}

?>
