<?php
namespace php_active_record;
/* This is a generic utility for DwCA post-processing. Dealing with Georgia cntry vs state. 
   https://eol-jira.bibalex.org/browse/DATA-1896?focusedCommentId=67771&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67771
*/
class GeorgiaCntry_vs_StateAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->resource_id = $resource_id;
        $this->archive_builder = $archive_builder;
        
        /* For task: add_canonical_in_taxa */
        $this->extracted_scinames = $GLOBALS['MAIN_TMP_PATH'] . $this->resource_id . "_scinames.txt";
        $this->gnparsed_scinames = $GLOBALS['MAIN_TMP_PATH'] . $this->resource_id . "_canonical.txt";
        
        /* For environments_names.tsv processing */
        $this->ontology['env_names'] = "https://github.com/eliagbayani/vangelis_tagger/raw/master/eol_tagger/environments_names.tsv";
    }
    /*============================================================ STARTS Georgia case =================================================*/
    function start($info)
    {
        /* Here's a more complex case: source text: "Georgia"

        It's the nation at least 90% of the time, I think, but the US state does creep in. If it's computationally practical, 
        I'd like to add a post-process, comparing other terms found in the same source record, and assigning the "Georgia" record accordingly. 
        (The other records would not be affected.)
        
        I think we could get away with just using the western cues, defaulting to http://www.geonames.org/614540 if they are absent and 
        using https://www.geonames.org/4197000 if any are present: America, United States, USA, Canada, Mexico, Carolina, Florida, Mississippi, Tennessee. 
        If it's practical to be fancy about it, we could also use a list of eastern cues, and if both sets are represented, discard the record- but I don't 
        think there will be that many such records. */

        $tables = $info['harvester']->tables; // print_r($tables); exit;
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'round 1');
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'round 2');
        self::process_generic_table($tables['http://rs.tdwg.org/dwc/terms/measurementorfact'][0], 'round 3');
        // remaining carry over extensions:
        self::carry_over_extension($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'occurrence');
        self::carry_over_extension($tables['http://rs.tdwg.org/dwc/terms/taxon'][0], 'taxon');
    }
    /*============================================================= ENDS Georgia case ==================================================*/
    private function process_generic_table($meta, $what)
    {   //print_r($meta);
        echo "\nResourceUtility...process_generic_table ($what) $meta->row_type ...\n"; $i = 0;
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
            // print_r($rec); exit("\ndebug...\n");
            /* Array(
                [http://rs.tdwg.org/dwc/terms/measurementID] => eeebc5e2a5e42968dc7ffc8e6f70101a_TreatmentB
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 3a55bcb7718b83f19dd125578c0063fc_TreatmentB
                [http://eol.org/schema/measurementOfTaxon] => true
                [http://rs.tdwg.org/dwc/terms/measurementType] => http://eol.org/schema/terms/Present
                [http://rs.tdwg.org/dwc/terms/measurementValue] => http://www.geonames.org/1269750
                [http://rs.tdwg.org/dwc/terms/measurementRemarks] => source text: "india"
                [http://purl.org/dc/terms/source] => https://treatment.plazi.org/id/1262A346E16192A8683A2CA3A3C28794
                [http://purl.org/dc/terms/bibliographicCitation] => Smith, F. (1859): Catalogue of hymenopterous insects collected by Mr. A. R. Wallace at the Islands of Aru and Key. Journal of the Proceedings of the Linnean Society of London, Zoology 3: 132-158, URL: http://antbase.org/ants/publications/10342/10342.pdf
            ) */
            $mremarks = $rec['http://rs.tdwg.org/dwc/terms/measurementRemarks'];
            $source = $rec['http://purl.org/dc/terms/source'];

            if($what == 'round 1') { // get a list of sources with "georgia".
                if($mremarks == 'source text: "georgia"') $this->sources_with_Georgia[$source] = '';
            }
            if($what == 'round 2') { // get all source texts for sources from round 1.
                if(isset($this->sources_with_Georgia[$source])) {
                    $this->source_texts_for_source[$source][$mremarks] = '';
                }
            }
            if($what == 'round 3') { // write
                if($mremarks == 'source text: "georgia"') {
                    $source_texts = array_keys($this->source_texts_for_source[$source]); // print_r($source_texts); //good debug
                    if($val = self::evaluate_entry($rec, $source_texts)) $rec['http://rs.tdwg.org/dwc/terms/measurementValue'] = $val;
                    else continue;
                }
                $o = new \eol_schema\MeasurementOrFact();
                self::loop_write($o, $rec);
            }
        } //end foreach()
    }
    private function has_Eastern_cues_YN($source_texts)
    {   
        $locations = array("Asia", "Europe", "Russia", "Middle East", "Caucasus", "Armenia", "Azerbaijan", "Turkey", "Iran");
        foreach($source_texts as $source_text) {
            foreach($locations as $location) {
                if(stripos($source_text, $location) !== false) { //string is found
                    return true;
                }
            }
        }
        return false;
    }
    private function evaluate_entry($rec, $source_texts)
    {
        // /* new: Eastern cues:
        $Eastern_cues_present_YN = self::has_Eastern_cues_YN($source_texts);
        // */

        $locations = array("America", "United States", "USA", "Canada", "Mexico", "Carolina", "Florida", "Mississippi", "Tennessee",        //Jen's list
        "massachusetts", "iowa", "wisconsin", "minnesota", "jersey", "kansas", "nebraska", "illinois", "delaware", "maryland", "virginia",  //Eli's addition
        "missouri", "oklahoma", "Dakota");                                                                                                  //Eli's addition
        foreach($source_texts as $source_text) {
            foreach($locations as $location) {
                if(stripos($source_text, $location) !== false) { //string is found
                    if($Eastern_cues_present_YN) return false;
                    else                         return "https://www.geonames.org/4197000"; //Georgia the US state.
                }
            }
        }
        return "http://www.geonames.org/614540"; //Georgia the country; as default
    }
    private function loop_write($o, $rec)
    {
        $uris = array_keys($rec); //print_r($uris); exit;
        foreach($uris as $uri) {
            $field = pathinfo($uri, PATHINFO_BASENAME);

            // /* some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
            $parts = explode("#", $field);
            if($parts[0]) $field = $parts[0];
            if(@$parts[1]) $field = $parts[1];
            // */

            $o->$field = $rec[$uri];
        }
        $this->archive_builder->write_object_to_file($o);        
    }
    private function carry_over_extension($meta, $class)
    {   //print_r($meta);
        echo "\nResourceUtility...carry_over_extension ($class)...\n"; $i = 0;
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
            // print_r($rec); exit("\ndebug1...\n");
            /**/
            $uris = array_keys($rec);
            if    ($class == "vernacular")          $o = new \eol_schema\VernacularName();
            elseif($class == "agent")               $o = new \eol_schema\Agent();
            elseif($class == "reference")           $o = new \eol_schema\Reference();
            elseif($class == "taxon")               $o = new \eol_schema\Taxon();
            elseif($class == "document")            $o = new \eol_schema\MediaResource();
            // elseif($class == "occurrence")          $o = new \eol_schema\Occurrence();
            elseif($class == "occurrence")          $o = new \eol_schema\Occurrence_specific();
            elseif($class == "measurementorfact")   $o = new \eol_schema\MeasurementOrFact();
            elseif($class == "association")         $o = new \eol_schema\Association();

            foreach($uris as $uri) {
                $field = pathinfo($uri, PATHINFO_BASENAME);

                // /* some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];
                // */

                // /* ignore certain fields for certain extensions: e.g. schema#localityName in Reference() schema
                if($class == "reference") {
                    if($field == "localityName") continue;
                }
                // */

                $o->$field = $rec[$uri];
            }
            $this->archive_builder->write_object_to_file($o);
        }
    }

}
?>