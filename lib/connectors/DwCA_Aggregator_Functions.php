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
    function format_field($rec)
    {
        /* furtherInformationURL
        File: media_resource.tab
        Line: 338149
        URI: http://rs.tdwg.org/ac/terms/furtherInformationURL
        Message: Invalid URL
        Line Value: |https://treatment.plazi.org/id/E97287E44C427A0C1FEAFB6CFADACDC2        

        File: media_resource.tab
        Line: 1782706
        URI: http://rs.tdwg.org/ac/terms/furtherInformationURL
        Message: Invalid URL
        Line Value: Jonsell, B., Karlsson (2005): Chenopodiaceae - Fumariaceae (Chenopodium). Flora Nordica 2: 4-31, URL: http://antbase.org/ants/publications/FlNordica_chenop/FlNordica_chenop.pdf
        */
        $furtherInformationURL = str_replace("|", "", $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL']);
        if(substr($furtherInformationURL,0,4) != "http") $furtherInformationURL = ""; //invalid data, maybe due to erroneous tab count.
        $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL'] = $furtherInformationURL;

        /* http://purl.org/dc/terms/type
        File: media_resource.tab
        Line: 1782706
        URI: http://purl.org/dc/terms/type
        Message: Invalid DataType
        Line Value: https://treatment.plazi.org/id/01660DF93D09DB09C986CB2380FAB116
        */
        $type = $rec['http://purl.org/dc/terms/type'];
        if($type != "http://purl.org/dc/dcmitype/Text") $rec['http://purl.org/dc/terms/type'] = false;

        // [description] if last char is "|", should delete it. It messes with tab separators during validation tool.
        $desc = $rec['http://purl.org/dc/terms/description'];
        if(substr($desc, -1) == "|") $rec['http://purl.org/dc/terms/description'] = substr($desc, 0, strlen($desc)-1);
        
        return $rec;
    }
    function process_table_TreatmentBank($rec, $row_type)
    {
        $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
        if($row_type == 'http://eol.org/schema/media/document') { //not http://rs.gbif.org/terms/1.0/description
            // build-up an info list
            $this->info_taxonID_mediaRec[$taxon_id] = array('UsageTerms'    => $rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms'],
                                                            'rights'        => $rec['http://purl.org/dc/terms/rights'],
                                                            'Owner'         => $rec['http://ns.adobe.com/xap/1.0/rights/Owner'],
                                                            'contributor'   => $rec['http://purl.org/dc/terms/contributor'],
                                                            'creator'       => $rec['http://purl.org/dc/terms/creator'],
                                                            'bibliographicCitation' => $rec['http://purl.org/dc/terms/bibliographicCitation']);
        }
        elseif($row_type == 'http://rs.gbif.org/terms/1.0/description') { //not http://eol.org/schema/media/document
            /* Array( print_r($rec);
                [http://rs.tdwg.org/dwc/terms/taxonID] => 03C44153FFA9FFABFF77F9DFFADFFA97.taxon
                [http://purl.org/dc/terms/type] => description
                [http://purl.org/dc/terms/description] => Immature stages Egg. Eggs elongate oval to somewhat cylindrical, chorion with distinct microsculpture in Chilocorus (Figs 4 a, 5 a), Brumoides (Fig. 4 b), and Priscibrumus Kovář. Eggs laid singly or in small groups on or in the vicinity of prey. Chilocorus spp. have a characteristic and peculiar habit of laying eggs on sibling larvae, pupae, and exuviae besides the host colony (Fig. 4 c – e). Larva. Larvae of Chilocorini have a nearly cylindrical or broadly fusiform body with the dorsal and lateral surfaces covered with setose projections (“ senti ”) or prominent parascoli (Figs 4 f, g; 5 b – e). After completing their development, the mature larvae of Chilocorini, particularly armoured-scale feeders, pass 1 – 2 days in an immobile, prepupal stage (Fig. 5 f). Pupa. Pupae are exarate and enclosed in longitudinally and medially split open larval exuvium (Figs 4 h, i; 5 g). In many Chilocorus spp., larvae congregate in small or large clusters on the lower side of branches or on the tree trunk for pupation (Drea & Gordon 1990). It is common to see large congregations of pupae in Indian species such as Chilocorus circumdatus (Gyllenhal) (Fig. 6 a, b), C. nigrita (Fig. 6 c, d) and C. infernalis Mulsant on various host plants.
                [http://purl.org/dc/terms/language] => en
                [http://purl.org/dc/terms/source] => POORANI, J. (2023): An illustrated guide to the lady beetles (Coleoptera: Coccinellidae) of the Indian Subcontinent. Part II. Tribe Chilocorini. Zootaxa 5378 (1): 1-108, DOI: 10.11646/zootaxa.5378.1.1, URL: https://www.mapress.com/zt/article/download/zootaxa.5378.1.1/52353
            ) */
            if(!$rec['http://purl.org/dc/terms/description']) continue; //description cannot be blank
            $description_type = $rec['http://purl.org/dc/terms/type'];
            
            if(in_array($description_type, array('etymology', 'discussion', 'type_taxon'))) continue;
            // Additional text types:
            elseif(in_array($description_type, array("synonymic_list", "vernacular_names", ""))) continue;
            elseif(in_array($description_type, array("material", "conservation", "food_feeding", "breeding", "activity", "use", "ecology", "biology"))) continue;
            elseif(!$description_type) continue;
            // if($description_type == 'type_taxon') { print_r($rec); exit; } //debug only good debug

            $this->TreatmentBank_stats($rec, $description_type); // stat only purposes, good report.

            $json = json_encode($rec);
            $rec['http://purl.org/dc/terms/identifier'] = md5($json);
            $rec['http://rs.tdwg.org/ac/terms/additionalInformation'] = $rec['http://purl.org/dc/terms/type'];
            $rec['http://purl.org/dc/terms/type'] = "http://purl.org/dc/dcmitype/Text";
            $rec['http://purl.org/dc/terms/format'] = "text/html";
            $rec['http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm'] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Uses";
            $rec['http://purl.org/dc/terms/title'] = "";
            $rec['http://rs.tdwg.org/ac/terms/furtherInformationURL'] = "https://treatment.plazi.org/id/".str_replace(".taxon", "", $rec['http://rs.tdwg.org/dwc/terms/taxonID']);
            $rec['http://purl.org/dc/terms/bibliographicCitation'] = $rec['http://purl.org/dc/terms/source'];
            unset($rec['http://purl.org/dc/terms/source']);
            // /* supplement with data from media row_type
            if($val = $this->info_taxonID_mediaRec[$taxon_id]) {
                $rec['http://ns.adobe.com/xap/1.0/rights/UsageTerms']   = $val['UsageTerms']; //Public Domain
                $rec['http://purl.org/dc/terms/rights']                 = $val['rights']; //No known copyright restrictions apply. See Agosti, D., Egloff, W., 2009. Taxonomic information exchange and copyright: the Plazi approach. BMC Research Notes 2009, 2:53 for further explanation.
                $rec['http://ns.adobe.com/xap/1.0/rights/Owner']        = $val['Owner'];
                $rec['http://purl.org/dc/terms/contributor']            = $val['contributor']; //MagnoliaPress via Plazi
                $rec['http://purl.org/dc/terms/creator']                = $val['creator']; //POORANI, J.
                $rec['http://purl.org/dc/terms/bibliographicCitation']  = $val['bibliographicCitation']; //POORANI, J. (2023): An illustrated guide to the lady beetles (Coleoptera: Coccinellidae) of the Indian Subcontinent. Part II. Tribe Chilocorini. Zootaxa 5378 (1): 1-108, DOI: 10.11646/zootaxa.5378.1.1, URL: https://www.mapress.com/zt/article/download/zootaxa.5378.1.1/52353
            }
            // */                        
        }
    }
}
?>