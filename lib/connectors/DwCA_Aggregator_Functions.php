<?php
namespace php_active_record;
/* */
class DwCA_Aggregator_Functions
{
    function __construct() {}
    function shorten_bibliographicCitation($meta, $bibliographicCitation)
    {   // exit("\n$meta->file_uri\n");
        // "/Volumes/AKiTiO4/eol_php_code_tmp/dir_07285//media.txt"
        $eml_file = str_ireplace("media.txt", "eml.xml", $meta->file_uri);      //for extension: http://eol.org/schema/media/Document
        $eml_file = str_ireplace("description.txt", "eml.xml", $eml_file);      //for extension: http://rs.gbif.org/terms/1.0/Description
        // exit("\n[$eml_file]\n");
        if(is_file($eml_file)) {
            if($xml = simplexml_load_file($eml_file)) { // print_r($xml);
                if($t = $xml->additionalMetadata->metadata->plaziMods) {
                    $mods = $t->children("http://www.loc.gov/mods/v3"); // xmlns:mods="http://www.loc.gov/mods/v3"
                    // echo "\n[".$mods->mods->typeOfResource."]\n"; //prints e.g. "text"
                    $subset = trim((string) @$mods->mods->relatedItem->part->detail->title);
                    if($subset) {
                        // echo "\nmay subset:\n[".$subset."]\n"; //prints the subset of the bibliographicCitation --- good debug
                        $shortened = str_ireplace("($subset)", "", $bibliographicCitation);
                        $shortened = Functions::remove_whitespace($shortened);
                        if($shortened) return $shortened;
                    }
                }
            }    
        }
        // exit("\nstop muna\n");
        return $bibliographicCitation;
    }
    /* Used by our original text object.
    function remove_taxon_lines_from_desc($html) // created for TreatmentBank - https://eol-jira.bibalex.org/browse/DATA-1916
    {
        if(preg_match_all("/<p>(.*?)<\/p>/ims", $html, $arr)) {
            $rows = $arr[1];
            $final = array();
            foreach($rows as $row) {
                $row = strip_tags($row);
                if(stripos($row, "locality:") !== false) {  //string is found
                    $final[] = $row;
                    continue;
                }
                if(strlen($row) <= 50) continue;
                if(stripos($row, "[not") !== false) continue; //string is found
                if(stripos($row, "(in part)") !== false) continue; //string is found
                if(stripos($row, "[? Not") !== false) continue; //string is found
                if(stripos($row, "Nomenclature") !== false) continue; //string is found
                if(stripos($row, "discarded]") !== false) continue; //string is found
                if(stripos($row, "♂") !== false) continue; //string is found
                if(stripos($row, "♀") !== false) continue; //string is found
                $final[] = $row;
            }
            if($final) {
                // print_r($final); // echo "\ntotal: [".count($final)."]\n";
                $ret = implode("\n", $final);
                return $ret;
            }
        }
        return $html;
    } */
    function let_media_document_go_first_over_description($index)
    {   /* Array(
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.tdwg.org/dwc/terms/occurrence
            [2] => http://rs.gbif.org/terms/1.0/description
            [3] => http://rs.gbif.org/terms/1.0/distribution
            [4] => http://eol.org/schema/media/document
            [5] => http://rs.gbif.org/terms/1.0/multimedia
            [6] => http://eol.org/schema/reference/reference
            [7] => http://rs.gbif.org/terms/1.0/vernacularname
        ) */
        $media_document = "http://eol.org/schema/media/document";;
        $description = "http://rs.gbif.org/terms/1.0/description";
        if(in_array($media_document, $index) && in_array($description, $index)) {
            $arr = array_diff($index, array($description));
            $arr[] = $description;
            $arr = array_values($arr); //reindex key
            return $arr;
        }
        else return $index;
    }
    function TreatmentBank_stats($rec, $description_type)
    {
        @$this->debug[$this->resource_id]['text type'][$rec['http://purl.org/dc/terms/type']]++;
        // save examples for Jen's investigation:
        $sought = array("synonymic_list", "vernacular_names", "conservation", "food_feeding", "breeding", "activity", "use", "ecology", "", "biology", "material");
        // $sought = array('distribution'); //debug only
        if(in_array($description_type, $sought)) {
            $count = count(@$this->debug[$this->resource_id]['type e.g.'][$description_type]);
            if($count <= 100) $this->debug[$this->resource_id]['type e.g.'][$description_type][$rec['http://rs.tdwg.org/dwc/terms/taxonID']] = '';
        }
    }
}
?>