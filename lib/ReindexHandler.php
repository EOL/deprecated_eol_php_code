<?php
namespace php_active_record;

class ReindexHandler
{
    public static function reindex_concept($args)
    {
        $taxon_concept_id = $args['taxon_concept_id'];
        if(!$taxon_concept_id || !is_numeric($taxon_concept_id))
        {
            throw new \Exception("The TaxonConceptID was missing or was not a number");
            return;
        }

        Tasks::update_taxon_concept_names(array($taxon_concept_id));
        // I don't think we really want to do this here. :S
        // $he = new FlattenHierarchies();
        // $he->flatten_hierarchies_from_concept_id($taxon_concept_id);
        TaxonConcept::reindex_descendants_objects($taxon_concept_id);
        TaxonConcept::reindex_for_search($taxon_concept_id);
        TaxonConcept::unlock_classifications_by_id($taxon_concept_id);
    }
}

?>
