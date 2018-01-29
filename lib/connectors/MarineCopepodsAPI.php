<?php
namespace php_active_record;
/* connector: copepods.php */
class MarineCopepodsAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        // $this->taxon_ids = array();
        // $this->occurrence_ids = array();
        // $this->media_ids = array();
        // $this->agent_ids = array();
        $this->debug = array();
        $this->download_options = array("timeout" => 60*60, "expire_seconds" => 60*60*24*25, "resource_id" => "MPC"); //marine planktonic copepods
        $this->page['species'] = "https://copepodes.obs-banyuls.fr/en/fichesp.php?sp=";
    }
    
    function start()
    {
        for($sp=1; $sp<=459; $sp++) {
            $rec = self::parse_species_page($sp);
        }

        // $sp = 111;
        // $rec = self::parse_species_page($sp); return;
        // print_r($this->debug);
    }
    private function parse_species_page($sp)
    {
        $rec = array();
        if($html = Functions::lookup_with_cache($this->page['species'].$sp, $this->download_options)) {
            // <div class="Style4"><b><em>Bradyidius armatus</em></b>&nbsp;&nbsp;Giesbrecht, 1897&nbsp;&nbsp;&nbsp;(F,M)</div>
            if(preg_match("/<div class=\"Style4\">(.*?)<\/div>/ims", $html, $a1)) {
                echo "\n". $a1[1]; //<b><em>Bradyidius armatus</em></b>&nbsp;&nbsp;Giesbrecht, 1897&nbsp;&nbsp;&nbsp;(F,M)
                if(preg_match("/<em>(.*?)<\/em>/ims", $a1[1], $a2)) $rec['species'] = $a2[1];
                else exit("\nInvestigate: no species 2 [$sp]\n");
                if(preg_match("/&nbsp;&nbsp;(.*?)&nbsp;&nbsp;/ims", $a1[1], $a2)) $rec['author'] = $a2[1];
            }
            else exit("\nInvestigate: no species 1 [$sp]\n");
            $rec['ancestry'] = self::parse_ancestry($html, $sp);
            $rec['NZ'] = self::get_NZ($html, $sp);
        }
        print_r($rec);
    }
    private function get_NZ($html)
    {   //<tr><td valign="top" width="30">NZ: </td><td>13 + 1 doubtful</td></tr>
        
    }
    private function parse_ancestry($html, $sp)
    {
        $ancestry = array();
        $ranks = array("Order", "Superfamily", "Family", "Genus");
        //<div align="left"><b>Calanoida</b> <em>( Order )</em></div>
        if(preg_match_all("/<div align=\"left\">(.*?)<\/div>/ims", $html, $a1)) {
            foreach($ranks as $rank) {
                foreach($a1[1] as $div) { //string is found
                    if(strpos($div, "( $rank )") !== false) { //&nbsp;&nbsp;&nbsp;&nbsp;<b>Clausocalanoidea</b> <em>( Superfamily )</em>
                        echo "\n$div";
                        if(preg_match("/<b>(.*?)<\/b>/ims", $div, $b)) $ancestry[$rank] = $b[1];
                    }
                }
            }
        }
        if(!$ancestry) exit("\nInvestigate: no ancestry [$sp]\n");
        return $ancestry;
    }
    
    /*
    if($country = @$rec['country']) {
        if($country_uri = self::get_country_uri($country)) {
            if(!isset($this->taxon_ids[$rec['taxon_id']])) self::add_taxon($rec);
            self::add_string_types($rec, $country_uri, "http://eol.org/schema/terms/Present", "true");
        }
        else $this->debug['undefined country'][$country] = '';
    }
    if($habitat = @$rec['habitat']) {
        if($habitat_uri = @$this->uri_values[$habitat]) {
            if(!isset($this->taxon_ids[$rec['taxon_id']])) self::add_taxon($rec);
            self::add_string_types($rec, $habitat_uri, "http://eol.org/schema/terms/Habitat", "true");
        }
        elseif($val = @$habitat_map[$habitat])
        {
            $habitat_uris = explode(";", $val);
            $habitat_uris = array_map('trim', $habitat_uris);
            foreach($habitat_uris as $habitat_uri)
            {
                if(!$habitat_uri) continue;
                if(!isset($this->taxon_ids[$rec['taxon_id']])) self::add_taxon($rec);
                $rec['measurementRemarks'] = $habitat;
                self::add_string_types($rec, $habitat_uri, "http://eol.org/schema/terms/Habitat", "true");
            }
        }
    }

    private function add_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['taxon_id'];
        $taxon->scientificName  = ucfirst($rec['scientific_name']);
        if($family = @$rec['family']) $taxon->family = ucfirst($family);
        if($taxon->family == "Formicidae") {
            $taxon->phylum  = 'Arthropoda';
            $taxon->class   = 'Insecta';
            $taxon->order   = 'Hymenoptera';
        }
        $taxon->furtherInformationURL = self::compute_furtherInformationURL($taxon->scientificName);
        // if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "")
    {
        $taxon_id = $rec["taxon_id"];
        $catnum   = $rec["catnum"];
        $occurrence_id = $catnum; // simply used catnum

        $unique_id = md5($taxon_id.$measurementType.$value);
        $occurrence_id = $unique_id; //becase one catalog no. can have 2 MeasurementOrFact entries. Each for country and habitat.

        $cont = $this->add_occurrence($taxon_id, $occurrence_id, $rec, $unique_id);

        if($cont) {
            $m = new \eol_schema\MeasurementOrFact();
            $m->occurrenceID       = $occurrence_id;
            $m->measurementOfTaxon = $measurementOfTaxon;
            if($measurementOfTaxon == "true") {
                $m->source      = @$rec["url"];
                $m->contributor = @$rec["contributor"];
                if($referenceID = @$rec["referenceID"]) $m->referenceID = $referenceID;
            }
            $m->measurementType  = $measurementType;
            $m->measurementValue = $value;
            // $m->bibliographicCitation = $this->bibliographic_citation;
            if($val = @$rec['measurementUnit'])     $m->measurementUnit = $val;
            if($val = @$rec['measurementMethod'])   $m->measurementMethod = $val;
            if($val = @$rec['statisticalMethod'])   $m->statisticalMethod = $val;
            if($val = @$rec['measurementRemarks'])  $m->measurementRemarks = $val;
            $this->archive_builder->write_object_to_file($m);
        }
    }
    private function add_occurrence($taxon_id, $occurrence_id, $rec, $unique_id)
    {
        if(isset($this->occurrence_ids[$unique_id])) return false;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $o->catalogNumber = @$rec['catalogNumber'];
        $o->dateIdentified = @$rec['dateIdentified'];
        $o->eventDate = @$rec['dateCollected'];
        $o->locality = '';
        if($val = @$rec['stateProvince']) {
            if($o->locality) $o->locality .= " stateProvince: $val.";
            else             $o->locality  = " stateProvince: $val.";
        }
        if($val = @$rec['country']) {
            if($o->locality) $o->locality .= " country: $val.";
            else             $o->locality  = " country: $val.";
        }
        if($val = @$rec['biogeographicregion']) {
            if($o->locality) $o->locality .= " biogeographicregion: $val.";
            else             $o->locality  = " biogeographicregion: $val.";
        }
        // $o->decimalLatitude
        // $o->decimalLongitude
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$unique_id] = '';
        return true;
    }

    private function get_country_uri($country)
    {
        if($country_uri = @$this->uri_values[$country]) return $country_uri;
        else {
            switch ($country) {
                case "United States of America":        return "http://www.wikidata.org/entity/Q30";
                case "Mariana Islands":                 return "http://www.wikidata.org/entity/Q153732";
            }
        }
    }
    
    */
}
?>

