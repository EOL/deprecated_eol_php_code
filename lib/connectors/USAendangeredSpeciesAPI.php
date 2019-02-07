<?php
namespace php_active_record;
/* connector: [usa_endangered.php] 
*/
class USAendangeredSpeciesAPI
{
    function __construct($folder = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->debug = array();
        $this->download_options = array(
            'expire_seconds'     => 60*60*24*30, //expires in 1 month
            'download_wait_time' => 1000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        $this->page['animals'] = 'https://ecos.fws.gov/ecp0/reports/ad-hoc-species-report?kingdom=V&kingdom=I&status=E&status=T&status=EmE&status=EmT&status=EXPE&status=EXPN&status=SAE&status=SAT&mapstatus=3&fcrithab=on&fstatus=on&fspecrule=on&finvpop=on&fgroup=on&header=Listed+Animals';
        $this->page['plants'] = 'https://ecos.fws.gov/ecp0/reports/ad-hoc-species-report?kingdom=P&status=E&status=T&status=EmE&status=EmT&status=EXPE&status=EXPN&status=SAE&status=SAT&mapstatus=3&fcrithab=on&fstatus=on&fspecrule=on&finvpop=on&fgroup=on&ffamily=on&header=Listed+Plants';
    }
    function start()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);

        $groups = array('animals', 'plants');
        $groups = array('animals');
        foreach($groups as $group) self::process_group($group);

        exit;
        $this->archive_builder->finalize(true);
        Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function process_group($group)
    {
        if($html = Functions::lookup_with_cache($this->page[$group], $this->download_options)) {
            if(preg_match("/\"resultTable\">(.*?)<\/table>/ims", $html, $arr)) {
                $html = $arr[1];
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $html, $arr)) {
                    $fields = self::get_fields_from_tr($arr[1][0]);
                    $rows = $arr[1];
                    array_shift($rows);
                    echo "\n".count($rows)."\n";
                    // echo "\n".$rows[0]; echo "\n".$rows[1466];
                    foreach($rows as $row) {
                        // echo "\n".$row;
                        if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $arr)) {
                            $tds = $arr[1];
                            $rec = array(); $i = -1;
                            foreach($fields as $field) {
                                $i++;
                                $rec[$field] = $tds[$i];
                            }
                            if($rec) self::process_rec($rec);
                        }
                    }
                }
            }
        }
    }
    private function process_rec($rec)
    {   /*Array(
            [scientific_name] => <a href="/ecp/species/615"><i>Zyzomys pedunculatus</i></a>
            [common_name] => Australian native mouse
            [critical_habitat] => N/A
            [species_group] => Mammals
            [federal_listing_status] => <i class='fa fa-info-circle' title='Endangered! E = endangered. A species in danger of extinction throughout all or a significant portion of its range.'></i>  Endangered
            [special_rules] => N/A
            [where_listed] => Wherever found
        )*/
        if(preg_match("/\/species\/(.*?)\"/ims", $rec['scientific_name'], $arr)) $rec['taxon_id'] = $arr[1];
        $rec['taxon_name'] = strip_tags($rec['scientific_name']);
        print_r($rec);
    }
    private function get_fields_from_tr($str)
    {
        if(preg_match_all("/class=\"(.*?)\"/ims", $str, $arr)) {
            return $arr[1];
        }
    }
    //********************************************************************************************************************************************************
    //********************************************************************************************************************************************************
    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }
    private function create_vernaculars($rec)
    {
        $this->taxa_with_trait[$rec['REF|Plant|theplant']] = ''; //to be used when creating taxon.tab
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec['REF|Plant|theplant'];
        $v->vernacularName  = $rec['common'];
        $v->language        = $rec['Language to Change ISO 639-3'];
        $v->countryCode     = $rec['country'];
        $this->archive_builder->write_object_to_file($v);
    }
    private function create_reference($rec)
    {
        // print_r($rec); exit;
        /*Array(
            [DEF_id] => 1
            [author] => Lovett; J.C. and Sorensen; L. and Lovett; J.
            [year] => 2006
            [title] => Field guide to the moist forest trees of Tanzania
            [journal] => 
            [volume] => 
            [number] => 
            [pages] => 
        )*/
        $r = new \eol_schema\Reference();
        $r->identifier = $rec['DEF_id'];
        $r->full_reference = $rec['author']." ".$rec['year'].". ".$rec['title'].".";
        $r->authorList = $rec['author'];
        $r->title = $rec['title'];
        // $r->uri = '';
        if(!isset($this->reference_ids[$r->identifier])) {
            $this->reference_ids[$r->identifier] = '';
            $this->archive_builder->write_object_to_file($r);
        }
    }
    private function create_text_object($rec)
    {
        // print_r($rec); //exit;
        /*Array(
            [DEF_id] => desc_1
            [type] => http://purl.org/dc/dcmitype/Text
            [Subject] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription
            [REF|Plant|theplant] => 1
            [description] => <b>Bole:</b>  Small/medium. To 24 m.  <b>Bark:</b>  Grey/pale green. Smooth.  <b>Slash:</b>  Yellow with white or yellow lines.  <b>Leaf:</b>  Simple. Alternate.  <b>Petiole:</b>  0.5 - 2.5 cm.  <b>Lamina:</b>  Medium. 4 - 19 × 2.5 - 10 cm (Juvenile up to 25 × 27 cm). Ovate/elliptic. Cuneate/cordate. Asymmetric. 5 - 7 nerved from base. Acuminate. Entire. Hairy/glabrous. Simple.  <b>Domatia:</b>  Present/absent. Small tufts of hairs.  <b>Glands:</b>   Absent.  <b>Stipules:</b>  Absent.  <b>Thorns & Spines:</b>  Absent.  <b>Flower:</b>  White/pale yellow. Fragrant. Infloresence 3 - 23 flowered axillary cymes. Hermaphrodite.  <b>Fruit:</b>  Globose 0.8 - 1.0 × 0.4 - 0.9 cm.
            [REF|Reference|ref] => 1
            [blank_1] => http://creativecommons.org/licenses/by-sa/3.0/
            [Title] => Botanical Description
        )
        Array(
            [DEF_id] => desc_659
            [type] => http://purl.org/dc/dcmitype/Text
            [Subject] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription
            [REF|Plant|theplant] => 654
            [description] => <b>Bole:</b>  Small. To 10 m.  <b>Bark:</b>  NR.  <b>Slash:</b>  NR.  <b>Leaf:</b>  Simple. Alternate.  <b>Petiole:</b>  0.5 - 3 cm. Bristly pubescent.  <b>Lamina:</b>  Medium. 7 - 18 × 3 - 7 cm. Ovate/oblong/oblong-lanceolate. Cuneate. Acuminate. Serrate. Glabrous above; slightly hairy beneath.  <b>Domatia:</b>  Absent.  <b>Glands:</b>   Brown dots underneath leaves.  <b>Stipules:</b>  Present.  <b>Thorns & Spines:</b>  Absent.  <b>Flower:</b>  Slender terminal thyrse.  <b>Fruit:</b>  Capsule 3-lobed. 0.1 - 1.7 cm long.
            [REF|Reference|ref] => 1
            [blank_1] => http://creativecommons.org/licenses/by-sa/3.0/
            [Title] => Botanical Description
        )*/
        $this->taxa_with_trait[$rec['REF|Plant|theplant']] = ''; //to be used when creating taxon.tab
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $rec['REF|Plant|theplant'];
        $mr->identifier     = $rec['DEF_id'];
        $mr->type           = $rec['type'];
        $mr->language       = 'en';
        $mr->format         = "text/html";
        $mr->CVterm         = $rec['Subject'];
        // $mr->Owner          = '';
        // $mr->rights         = '';
        $mr->title          = $rec['Title'];
        $mr->UsageTerms     = $rec['blank_1'];
        $mr->description    = $rec['description'];
        // $mr->LocationCreated = '';
        $mr->bibliographicCitation = $this->partner_bibliographicCitation;
        $mr->furtherInformationURL = $this->partner_source_url;
        $mr->referenceID = $rec['REF|Reference|ref'];
        if(!@$rec['REF|Reference|ref']) {
            print_r($rec);
            exit("\nNo reference!\n");
        }
        // if($agent_ids = )  $mr->agentID = implode("; ", $agent_ids);
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function create_taxon($rec)
    {
        if(!isset($this->taxa_with_trait[$rec['DEF_id']])) return;
        // print_r($rec); exit;
        /*Array(
            [DEF_id] => 1
            [family] => Alangiaceae 
            [genus] => Alangium
            [scientific name] => Alangium chinense 
            [species] => chinense
            [subspecies] => 
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['DEF_id'];
        $taxon->scientificName  = $rec['scientific name'];
        $taxon->family          = $rec['family'];
        $taxon->genus           = $rec['genus'];
        // $taxon->taxonRank             = '';
        // $taxon->furtherInformationURL = '';
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function create_trait($rek, $group)
    {
        if($group == "distribution.csv") {
            $arr = explode(";", $rek['Region']);
            $taxon_id = $rek['Plant No'];
            $mtype = "http://eol.org/schema/terms/Present";
        }
        elseif($group == "use.csv") {
            $arr = explode(";", $rek['Use']);
            $taxon_id = $rek['Plant'];
            $mtype = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Use";
        }
        $arr = array_map('trim', $arr);
        // print_r($arr); exit;
        foreach($arr as $string_val) {
            if($string_val) {
                $string_val = Functions::conv_to_utf8($string_val);
                $rec = array();
                $rec["taxon_id"] = $taxon_id;
                $rec["catnum"] = $taxon_id.'_'.$rek['id'];
                if($string_uri = self::get_string_uri($string_val)) {
                    $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
                    $rec['measurementRemarks'] = $string_val;
                    $rec['bibliographicCitation'] = $this->partner_bibliographicCitation;
                    $rec['source'] = $this->partner_source_url;
                    $rec['referenceID'] = 1;
                    $this->func->add_string_types($rec, $string_uri, $mtype, "true");
                }
                elseif($val = @$this->addtl_mappings[strtoupper(str_replace('"', "", $string_val))]) {
                    $this->taxa_with_trait[$taxon_id] = ''; //to be used when creating taxon.tab
                    $rec['measurementRemarks'] = $string_val;
                    self::write_addtl_mappings($val, $rec);
                }
                else $this->debug[$group][$string_val] = '';
            }
        }
    }
    private function write_addtl_mappings($rek, $rec)
    {
        // print_r($rek); exit;
        /*Array(
            [distribution.csv] => Central Africa
            [measurementType] => http://eol.org/schema/terms/Present
            [measurementValue] => http://www.geonames.org/7729886
            [measurementRemarks] => 
        )*/
        if($rek['measurementType'] == "DISCARD") return;
        $rec['measurementRemarks'] = $rek['measurementRemarks'];
        // print_r($rec); exit;
        /*Array(
            [taxon_id] => 1
            [catnum] => 1_dist_1
            [measurementRemarks] => 
        )*/
        $tmp = str_replace('"', "", $rek['measurementValue']);
        $tmp = explode(",", $tmp);
        $tmp = array_map('trim', $tmp);
        // print_r($tmp); exit;
        /*Array(
            [0] => http://www.geonames.org/7729886
        )*/
        foreach($tmp as $string_uri) {
            $rec['bibliographicCitation'] = $this->partner_bibliographicCitation;
            $rec['source'] = $this->partner_source_url;
            $rec['referenceID'] = 1;
            $this->func->add_string_types($rec, $string_uri, $rek['measurementType'], "true");
        }
    }
    private function get_string_uri($string)
    {
        switch ($string) { //put here customized mapping
            case "NR":                return false; //"DO NOT USE";
            // case "United States of America":    return "http://www.wikidata.org/entity/Q30";
        }
        if($string_uri = @$this->uris[$string]) return $string_uri;
    }
    private function separate_strings($str, $ret, $group)
    {
        $arr = explode(";", $str);
        $arr = array_map('trim', $arr);
        foreach($arr as $item) {
            if(!isset($this->uris[$item])) $ret[$group][$item] = '';
                                        // $ret[$group][$item] = '';
        }
        return $ret;
    }
    private function fill_up_blank_fieldnames($fields)
    {
        $i = 0;
        foreach($fields as $field) {
            if($field) $final[$field] = '';
            else {
                $i++;
                $final['blank_'.$i] = '';
            } 
        }
        return array_keys($final);
    }
    private function initialize_mapping()
    {
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        self::use_mapping_from_jen();
        // print_r($this->uris);
    }
    private function use_mapping_from_jen()
    {
        $csv_file = Functions::save_remote_file_to_local($this->use_mapping_from_jen, $this->download_options);
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row);
            // print_r($row);
            $i++; if(($i % 2000) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                // print_r($fields); print_r($rec); exit;
                /*Array(
                    [Use string] => timber
                    [URI] => http://purl.obolibrary.org/obo/EUPATH_0000001
                    [blank_1] => 
                    [blank_2] => 
                    [blank_3] => 
                    [blank_4] => 
                )*/
                $this->uris[$rec['Use string']] = $rec['URI'];
            } //main records
        } //main loop
        fclose($file);
        unlink($csv_file);
    }
}
?>
