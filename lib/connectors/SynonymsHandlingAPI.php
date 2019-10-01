<?php
namespace php_active_record;
/* connector: [called from DwCA_Utility.php, which is called from synonyms_handling.php for DATA-1824] */
class SynonymsHandlingAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        if($resource_id == 'itis_2019-08-28') {
            $this->valid_statuses = array('valid', 'accepted');
            $this->invalid_statuses = array('invalid', 'not accepted');
        }
        elseif($resource_id == '368_final') { //and so on
            $this->valid_statuses = array('accepted');
            $this->invalid_statuses = array('obsolete variant', 'subjective synonym', 'misspelling', 'replaced', 'nomen dubium', 'corrected',
                                            'objective synonym', 'nomen vanum', 'reassigned', 'nomen nudum', 'recombined', 'nomen oblitum');
        }
        else {
            $this->valid_statuses = array('valid', 'accepted');
            $this->invalid_statuses = array('invalid', 'not accepted', 'synonym');
        }
        $temp = 'f.|form|forma|infraspecies|species|ssp|subform|subsp.|subspecies|subvariety|var.|varietas|variety';
        $this->species_ranks = explode('|', $temp);
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function synonym_updates($info)
    {   $tables = $info['harvester']->tables;
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'build up taxonID_info');
        self::process_taxon($tables['http://rs.tdwg.org/dwc/terms/taxon'][0]);
        
        if($this->resource_id == '368_final') { //bec. it "Allowed memory size of xxx bytes exhausted in DwCA_Utility.php"
            self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'MoF');
        }
    }
    private function process_taxon($meta, $purpose = '')
    {   //print_r($meta);
        echo "\nprocess_taxon...$purpose\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /* e.g. for itis resource
            Array(
                [http://rs.tdwg.org/dwc/terms/taxonID] => 50
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => https://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=TSN&search_value=50#null
                [http://rs.tdwg.org/dwc/terms/acceptedNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 
                [http://rs.tdwg.org/dwc/terms/scientificName] => Bacteria Cavalier-Smith, 2002
                [http://rs.tdwg.org/dwc/terms/taxonRank] => kingdom
                [http://rs.tdwg.org/dwc/terms/scientificNameAuthorship] => Cavalier-Smith, 2002
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => valid
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
                [http://rs.gbif.org/terms/1.0/canonicalName] => Bacteria
            )*/
            if($purpose == 'build up taxonID_info') {
                $this->taxonID_info[$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = array('aID' => $rec['http://rs.tdwg.org/dwc/terms/acceptedNameUsageID'], 
                                                                                          'pID' => $rec['http://rs.tdwg.org/dwc/terms/parentNameUsageID'],
                                                                                          's' => $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'],
                                                                                          'r' => $rec['http://rs.tdwg.org/dwc/terms/taxonRank']);
                continue;
            }
            else { //main program to filter out bad synonyms
                $orig_rec = $rec;
                $uris = array_keys($rec);
                foreach($uris as $uri) {
                    $field = pathinfo($uri, PATHINFO_BASENAME);
                    $rec[$field] = $rec[$uri];
                }
                if(!($rec = self::is_valid_synonym_or_taxonYN($rec))) continue;
                $rec = $orig_rec;
            }
            //===================================================================================
            $o = new \eol_schema\Taxon();
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    function is_valid_synonym_or_taxonYN($rec)
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
        // print_r($this->taxonID_info); exit;
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
                if($info = @$this->taxonID_info[$rec['acceptedNameUsageID']]) {
                    if(in_array($info['r'], array('genus','subgenus'))) return $rec; //Ok
                    else return false;
                }
                else return false;
            }
            elseif(in_array($rec['taxonRank'], $this->species_ranks)) {
                if($info = @$this->taxonID_info[$rec['acceptedNameUsageID']]) {
                    if(in_array($info['r'], $this->species_ranks)) return $rec; //Ok
                    else return false;
                }
                else {
                    // print_r($rec);echo " -- 111";
                    return false;
                }
            }
            elseif($rec['taxonRank'] == '') { //for rules 3 & 4
                if($info = $this->taxonID_info[$rec['acceptedNameUsageID']]) {
                    $ranks = array_merge(array('genus','subgenus'), $this->species_ranks);
                    if(in_array($info['r'], $ranks)) return $rec; //Ok
                    else return false;
                }
                else {
                    // print_r($rec);echo " -- 222";
                    return false;
                }
            }
            
            /* Eli's general cleaning: if acceptedNameUsageID doesn't have an entry ignore that synonym START */
            if($info = @$this->taxonID_info[$rec['acceptedNameUsageID']]) return $rec; //Ok
            else return false;
            /* Eli's general cleaning: if acceptedNameUsageID doesn't have an entry ignore that synonym END */
        }
        else { //NOT a synonym
            /* 3 & 4 */
        }
        
        /* Eli's general cleaning: if parentNameUsageID doesn't have an entry ignore that taxon START */
        if($info = @$this->taxonID_info[$rec['parentNameUsageID']]) return $rec; //Ok
        else return false;
        /* Eli's general cleaning: if parentNameUsageID doesn't have an entry ignore that taxon END */
        
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
    private function process_generic_table($meta, $what)
    {   //print_r($meta);
        echo "\nprocess $what...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 100000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            // $row = Functions::conv_to_utf8($row); //possibly to fix special chars. but from copied template
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            /**/
            /* ----start customization---- */
            if($what == 'taxon') {
                $o = new \eol_schema\Taxon();
            }
            elseif($what == 'vernacular') {
                $o = new \eol_schema\VernacularName();
            }
            elseif($what == 'occurrence') {
                $o = new \eol_schema\Occurrence();
            }
            elseif($what == 'MoF') {
                $o = new \eol_schema\MeasurementOrFact_specific();
            }
            else exit("\nInvestigate [$what]\n");
            /* ----end customization---- */
            //===================================================================================
            $uris = array_keys($rec);
            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);
                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
            // if($i >= 10) break; //debug only
        }
    }
    /*================================================================= ENDS HERE ======================================================================*/
}
?>
