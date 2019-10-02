<?php
namespace php_active_record;
// This is a lib for general maintenance of synonyms in EOL. https://eol-jira.bibalex.org/browse/DATA-1822
class SynonymsMtce
{
    function __construct($resource_id)
    {
        exit("\nSynonymsMtce: is now obsolete. Will terminate.\n");
        $this->resource_id = $resource_id;
        if($resource_id == 'itis_2019-08-28') {
            /*[taxonomicStatus] => Array(
                        [invalid] => 
                        [valid] => 
                        [accepted] => 
                        [not accepted] => 
                    )
            */
            $this->valid_statuses = array('valid', 'accepted');
            $this->invalid_statuses = array('invalid', 'not accepted');
        }
        elseif($resource_id == 'xxx') { //and so on
        }
        else {
            $this->valid_statuses = array('valid', 'accepted');
            $this->invalid_statuses = array('invalid', 'not accepted');
        }
        
        $temp = 'f.|form|forma|infraspecies|species|ssp|subform|subsp.|subspecies|subvariety|var.|varietas|variety';
        $this->species_ranks = explode('|', $temp);
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
    function is_valid_synonym_or_taxonYN($rec, $taxonID_info)
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
        // print_r($taxonID_info); exit;
        /* [741821] => Array(
                    [aID] => 
                    [pID] => 734820
                    [s] => valid
                    [r] => species
                )

taxonID	furtherInformationURL	acceptedNameUsageID	parentNameUsageID	scientificName	taxonRank	scientificNameAuthorship	taxonomicStatus	taxonRemarks	canonicalName
50	https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=50#null			Bacteria Cavalier-Smith, 2002	kingdom	Cavalier-Smith, 2002	valid		Bacteria
52	https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=52#null	50		Archangiaceae	family		invalid	unavailable, database artifact	Archangiaceae
54	https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=54#null	50		Rhodobacteriineae	suborder		invalid	unavailable, database artifact	Rhodobacteriineae
55	https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=55#null	50		Pseudomonadineae	suborder		invalid	unavailable, database artifact	Pseudomonadineae
56	https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=56#null	50		Nitrobacteraceae	family		invalid	unavailable, database artifact	Nitrobacteraceae

        */
        if(self::is_record_a_synonymYN($rec)) {
            /* 1. A synonym with taxonRank (genus|subgenus) can only point to an acceptedName with taxonRank (genus|subgenus). */
            if(in_array($rec['taxonRank'], array('genus','subgenus'))) {
                if($info = $taxonID_info[$rec['acceptedNameUsageID']]) {
                    if(in_array($info['r'], array('genus','subgenus'))) return $rec; //Ok
                    else return false;
                }
                else return false;
            }
            elseif(in_array($rec['taxonRank'], $this->species_ranks)) {
                if($info = $taxonID_info[$rec['acceptedNameUsageID']]) {
                    if(in_array($info['r'], $this->species_ranks)) return $rec; //Ok
                    else return false;
                }
                else {
                    print_r($rec);echo " -- 111";
                    return false;
                }
            }
            elseif($rec['taxonRank'] == '') { //for rules 3 & 4
                if($info = $taxonID_info[$rec['acceptedNameUsageID']]) {
                    $ranks = array_merge(array('genus','subgenus'), $this->species_ranks);
                    if(in_array($info['r'], $ranks)) return $rec; //Ok
                    else return false;
                }
                else {
                    print_r($rec);echo " -- 222";
                    return false;
                }
            }
        }
        else { //NOT a synonym
            /* 3 & 4 */
        }
        return $rec;
    }
    private function is_record_a_synonymYN($rec)
    {
        if($rec['acceptedNameUsageID']) {
            if(!self::valid_statusYN($rec)) return true;
            else {
                echo "\nInvestigate: with aID but has a valid status\n"; print_r($rec); exit;
            }
        }
        return false;
    }
    private function valid_statusYN($rec)
    {
        if(in_array($rec['taxonomicStatus'], $this->valid_statuses)) return true;
        if(in_array($rec['taxonomicStatus'], $this->invalid_statuses)) return false;
    }
}
?>