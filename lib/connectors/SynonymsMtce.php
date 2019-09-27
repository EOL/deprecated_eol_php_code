<?php
namespace php_active_record;
// This is a lib for general maintenance of synonyms in EOL. https://eol-jira.bibalex.org/browse/DATA-1822
class SynonymsMtce
{
    function __construct()
    {
    }
    /*We have at least a couple of providers that have the weird practice of creating synonym relationships for invalid species or genus level taxa that point to taxa of much higher rank. 
    Examples are synonyms of Dinosauria in PBDB and synonyms of Bacteria in ITIS. Whenever possible, we should fix these issues in the connector. 

    To accomplish this, could you please establish a validation routine for all connectors with synonyms that enforces the following rank-based rules?

    1. A synonym with taxonRank (genus|subgenus) can only point to an acceptedName with taxonRank (genus|subgenus).

    2. A synonym with taxonRank (f.|form|forma|infraspecies|species|ssp|subform|subsp.|subspecies|subvariety|var.|varietas|variety) 
    can only point to an acceptedName with taxonRank (f.|form|forma|infraspecies|species|ssp|subform|subsp.|subspecies|subvariety|var.|varietas|variety)

    3. A taxon with taxonRank (genus|subgenus) can only have synonyms with taxonRank (genus|subgenus) or where taxonRank is empty.

    4.  A taxon with taxonRank (f.|form|forma|infraspecies|species|ssp|subform|subsp.|subspecies|subvariety|var.|varietas|variety) 
    can only have synonyms with taxonRank (f.|form|forma|infraspecies|species|ssp|subform|subsp.|subspecies|subvariety|var.|varietas|variety) or where taxonRank is empty.

    If there are synonyms that violate these rank-based rules, we should exclude them from the resource.
    */
    function build_taxonID_info()
    {
        
    }
    function synonym_maintenance($rec)
    {
        // print_r($rec); exit;
        /* ITIS first client
        Array(
            [taxonID] => 50
            [furtherInformationURL] => https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=50#null
            [taxonomicStatus] => valid
            [scientificName] => Bacteria
            [scientificNameAuthorship] => Cavalier-Smith, 2002
            [acceptedNameUsageID] => 
            [parentNameUsageID] => 
            [taxonRank] => kingdom
            [canonicalName] => Bacteria
            [kingdom] => 
            [taxonRemarks] => 
        )*/
        
    }
}
?>