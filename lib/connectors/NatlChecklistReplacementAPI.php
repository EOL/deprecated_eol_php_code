<?php
namespace php_active_record;
// connector: [gbif_classification.php]
class NatlChecklistReplacementAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array(
            'resource_id'        => 'gbif',
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'cache' => 1);
        
        /* from copied template
        if(Functions::is_production()) {
            $this->service["backbone_dwca"] = "http://rs.gbif.org/datasets/backbone/backbone-current.zip";
            $this->service["gbif_classification"] = "https://editors.eol.org/eol_php_code/applications/content_server/resources/gbif_classification.tar.gz";
        }
        else {
            $this->service["backbone_dwca"] = "http://localhost/cp/GBIF_Backbone_Archive/backbone-current.zip";
            $this->service["gbif_classification"] = "/Volumes/MacMini_HD2/work_temp/gbif_classification.tar.gz";
        }
        $this->log_file = CONTENT_RESOURCE_LOCAL_PATH.'xxx.txt';
        */
        
        /* Generated respectively from gbif.org. Will receive email when download is complete. Will last in GBIF for 6 months starting now Oct 23, 2019
        https://www.gbif.org/occurrence/search?country=AI
        https://www.gbif.org/occurrence/search?country=AW
        https://www.gbif.org/occurrence/search?country=BH
        $this->c_service['c_BH'] = 'http://api.gbif.org/v1/occurrence/download/request/0027457-190918142434337.zip';
        $this->c_service['c_AI'] = 'http://api.gbif.org/v1/occurrence/download/request/0027458-190918142434337.zip';
        $this->c_service['c_AW'] = 'http://api.gbif.org/v1/occurrence/download/request/0027503-190918142434337.zip';
        */
        $this->c_service['c_BH'] = 'https://editors.eol.org/other_files/GBIF_DwCA/Bahrain_0027457-190918142434337.zip';
        $this->c_service['c_AI'] = 'https://editors.eol.org/other_files/GBIF_DwCA/Anguilla_0027458-190918142434337.zip';
        $this->c_service['c_AW'] = 'https://editors.eol.org/other_files/GBIF_DwCA/Aruba_0027503-190918142434337.zip';
        $this->c_citation['c_BH'] = 'GBIF.org (23 October 2019) GBIF Occurrence Download https://doi.org/10.15468/dl.tewqob';
        $this->c_citation['c_AI'] = 'GBIF.org (23 October 2019) GBIF Occurrence Download https://doi.org/10.15468/dl.psdkxm';
        $this->c_citation['c_AW'] = 'GBIF.org (23 October 2019) GBIF Occurrence Download https://doi.org/10.15468/dl.n3l3pq';

        $this->c_source['c_BH'] = 'https://doi.org/10.15468/dl.tewqob';
        $this->c_source['c_AI'] = 'https://doi.org/10.15468/dl.psdkxm';
        $this->c_source['c_AW'] = 'https://doi.org/10.15468/dl.n3l3pq';

        $this->c_mValue['c_BH'] = 'http://www.geonames.org/290291';
        $this->c_mValue['c_AI'] = 'http://www.geonames.org/3573512';
        $this->c_mValue['c_AW'] = 'http://www.geonames.org/3577279';
        $this->debug = array();
        $this->fields_4taxa = array('http://rs.tdwg.org/dwc/terms/taxonID', 'http://rs.tdwg.org/dwc/terms/scientificName', 'http://rs.tdwg.org/dwc/terms/kingdom', 
            'http://rs.tdwg.org/dwc/terms/phylum', 'http://rs.tdwg.org/dwc/terms/class', 'http://rs.tdwg.org/dwc/terms/order', 'http://rs.tdwg.org/dwc/terms/family', 
            'http://rs.tdwg.org/dwc/terms/genus', 'http://rs.tdwg.org/dwc/terms/taxonRank', 'http://rs.tdwg.org/dwc/terms/taxonomicStatus', 'http://rs.tdwg.org/dwc/terms/taxonRemarks');
        $this->dataset_page = 'https://www.gbif.org/dataset/';
    }
    function start()
    {   require_library('connectors/TraitGeneric');                             $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        require_library('connectors/GlobalRegister_IntroducedInvasiveSpecies'); $this->func_griis = new GlobalRegister_IntroducedInvasiveSpecies('griis');
        
        $paths = self::access_dwca($this->resource_id);
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/occurrence"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'taxa');
        self::process_occurrence($tables['http://rs.tdwg.org/dwc/terms/occurrence'][0], 'MoF');
        print_r($this->debug); //exit("\nexit muna\n");
        // print_r($this->info);
        $this->archive_builder->finalize(TRUE);

        // /* un-comment in real operation
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
    }
    private function access_dwca($dwca, $expire_seconds = false)
    {   
        $download_options = $this->download_options;
        if($expire_seconds) $download_options['expire_seconds'] = $expire_seconds;
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->c_service[$dwca], "meta.xml", $download_options);
        // print_r($paths); exit;
        // */
        /* local when developing
        $paths = Array(
            "archive_path" => "/Library/WebServer/Documents/eol_php_code/tmp/xxx/",
            "temp_dir" => "/Library/WebServer/Documents/eol_php_code/tmp/xxx/"
        );
        */
        return $paths;
    }
    private function process_occurrence($meta, $what)
    {   //print_r($meta);
        echo "\nprocess_occurrence...\n"; $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 1000) == 0) echo "\n".number_format($i);
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
            /*Array( bec. list is too long, many where removed
                [http://rs.gbif.org/terms/1.0/gbifID] => 2429025981
                [http://purl.org/dc/terms/audience] => 
                [http://purl.org/dc/terms/bibliographicCitation] => 
                [http://purl.org/dc/terms/contributor] => 
                [http://purl.org/dc/terms/created] => 
                [http://purl.org/dc/terms/creator] => 
                [http://purl.org/dc/terms/date] => 
                [http://purl.org/dc/terms/dateAccepted] => 
                [http://purl.org/dc/terms/dateSubmitted] => 
                [http://purl.org/dc/terms/description] => 
                [http://purl.org/dc/terms/format] => 
                [http://purl.org/dc/terms/identifier] => 79231
                [http://purl.org/dc/terms/instructionalMethod] => 
                [http://purl.org/dc/terms/license] => CC_BY_4_0
                [http://purl.org/dc/terms/modified] => 2017-11-23T11:52:23.683Z
                [http://purl.org/dc/terms/publisher] => 
                [http://purl.org/dc/terms/references] => 
                [http://purl.org/dc/terms/rights] => 
                [http://purl.org/dc/terms/rightsHolder] => 
                [http://purl.org/dc/terms/source] => 
                [http://purl.org/dc/terms/title] => 
                [http://purl.org/dc/terms/type] => 
                [http://rs.tdwg.org/dwc/terms/institutionID] => 
                [http://rs.tdwg.org/dwc/terms/collectionID] => 
                [http://rs.tdwg.org/dwc/terms/datasetID] => 
                [http://rs.tdwg.org/dwc/terms/institutionCode] => K
                [http://rs.tdwg.org/dwc/terms/collectionCode] => Economic Botany Collection
                [http://rs.tdwg.org/dwc/terms/datasetName] => 
                [http://rs.tdwg.org/dwc/terms/ownerInstitutionCode] => 
                [http://rs.tdwg.org/dwc/terms/basisOfRecord] => UNKNOWN
                [http://rs.tdwg.org/dwc/terms/occurrenceID] => 
                [http://rs.tdwg.org/dwc/terms/catalogNumber] => 79231
                [http://rs.tdwg.org/dwc/terms/recordNumber] => 
                [http://rs.tdwg.org/dwc/terms/recordedBy] => Nesbitt M
                [http://rs.tdwg.org/dwc/terms/individualCount] => 
                [http://rs.tdwg.org/dwc/terms/organismQuantity] => 
                [http://rs.tdwg.org/dwc/terms/organismQuantityType] => 
                [http://rs.tdwg.org/dwc/terms/sex] => 
                [http://rs.tdwg.org/dwc/terms/lifeStage] => 
                [http://rs.tdwg.org/dwc/terms/establishmentMeans] => 
                [http://rs.tdwg.org/dwc/terms/occurrenceStatus] => 
                [http://rs.tdwg.org/dwc/terms/preparations] => 
                [http://rs.tdwg.org/dwc/terms/disposition] => 
                [http://rs.tdwg.org/dwc/terms/occurrenceRemarks] => 
                [http://rs.tdwg.org/dwc/terms/organismID] => 
                [http://rs.tdwg.org/dwc/terms/organismName] => 
                [http://rs.tdwg.org/dwc/terms/organismScope] => 
                [http://rs.tdwg.org/dwc/terms/organismRemarks] => 
                [http://rs.tdwg.org/dwc/terms/eventDate] => 
                [http://rs.tdwg.org/dwc/terms/verbatimEventDate] => 
                [http://rs.tdwg.org/dwc/terms/habitat] => 
                [http://rs.tdwg.org/dwc/terms/samplingProtocol] => 
                [http://rs.tdwg.org/dwc/terms/samplingEffort] => 
                [http://rs.tdwg.org/dwc/terms/sampleSizeValue] => 
                [http://rs.tdwg.org/dwc/terms/sampleSizeUnit] => 
                [http://rs.tdwg.org/dwc/terms/fieldNotes] => 
                [http://rs.tdwg.org/dwc/terms/eventRemarks] => Purchased in market
                [http://rs.tdwg.org/dwc/terms/locationID] => 
                [http://rs.tdwg.org/dwc/terms/higherGeographyID] => 
                [http://rs.tdwg.org/dwc/terms/higherGeography] => Bahrain
                [http://rs.tdwg.org/dwc/terms/countryCode] => BH
                [http://rs.tdwg.org/dwc/terms/locality] => Bahrain, village of Saar.
                [http://rs.tdwg.org/dwc/terms/identificationID] => 
                [http://rs.tdwg.org/dwc/terms/identificationQualifier] => 
                [http://rs.tdwg.org/dwc/terms/typeStatus] => 
                [http://rs.tdwg.org/dwc/terms/identifiedBy] => 
                [http://rs.tdwg.org/dwc/terms/dateIdentified] => 

                [http://rs.gbif.org/terms/1.0/datasetKey] => 1d31211e-350e-492a-a597-34d24bbc1769
                [http://rs.gbif.org/terms/1.0/publishingCountry] => GB

                [http://rs.tdwg.org/dwc/terms/scientificName] => Phoenix dactylifera L.
                [http://rs.tdwg.org/dwc/terms/kingdom] => Plantae
                [http://rs.tdwg.org/dwc/terms/phylum] => Tracheophyta
                [http://rs.tdwg.org/dwc/terms/class] => Liliopsida
                [http://rs.tdwg.org/dwc/terms/order] => Arecales
                [http://rs.tdwg.org/dwc/terms/family] => Arecaceae
                [http://rs.tdwg.org/dwc/terms/genus] => Phoenix
                [http://rs.tdwg.org/dwc/terms/taxonRank] => SPECIES
                [http://rs.tdwg.org/dwc/terms/taxonomicStatus] => ACCEPTED
                [http://rs.tdwg.org/dwc/terms/taxonRemarks] => 
                
                [http://rs.gbif.org/terms/1.0/taxonKey] => 6109699
                [http://rs.gbif.org/terms/1.0/acceptedTaxonKey] => 6109699
                [http://rs.gbif.org/terms/1.0/speciesKey] => 6109699
            )*/
            // ------------------------------------------------------------------------------------------------- for taxa
            $rec['http://rs.tdwg.org/dwc/terms/taxonRank'] = strtolower($rec['http://rs.tdwg.org/dwc/terms/taxonRank']);
            $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'] = strtolower($rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus']);
            // ------------------------------------------------------------------------------------------------- for taxa
            $taxonomicStatus = $rec['http://rs.tdwg.org/dwc/terms/taxonomicStatus'];
            $this->debug[$taxonomicStatus] = ''; //stats only
            if(!self::valid_statusYN($taxonomicStatus)) continue;
            if($val = self::get_taxonID($rec)) $rec['http://rs.tdwg.org/dwc/terms/taxonID'] = $val;
            else continue;
            // ------------------------------------------------------------------------------------------------- for MoF
            // if($dataset_key = $rec['http://rs.gbif.org/terms/1.0/datasetKey']) {
            //     $ret = $this->func_griis->get_dataset_info($dataset_key);
            //     // print_r($ret); //exit;
            // }
            // -------------------------------------------------------------------------------------------------
            
            if($what == 'taxa') {
                self::write_taxon($rec);
                // if($i >= 20) break;
            }
            if($what == 'MoF') {
                self::write_MoF($rec);
                // if($i >= 5) break;
            }
        }
    }
    private function write_MoF($rec)
    {
        $mValue = $this->c_mValue[$this->resource_id];
        /* may not be ideal - let us wait for instruction
        $mType = $this->func_griis->get_mType_4distribution($rec['http://rs.tdwg.org/dwc/terms/occurrenceStatus'], $rec['http://rs.tdwg.org/dwc/terms/establishmentMeans']);
        if(!$mType) $mType = 'http://eol.org/schema/terms/Present';
        */
        $mType = 'http://eol.org/schema/terms/Present';
        
        // $occur_locality = '';
        // if($val = @$rec['http://rs.tdwg.org/dwc/terms/locationID']) $occur_locality = $val;
        // if($val = @$rec['http://rs.tdwg.org/dwc/terms/locality']) $occur_locality = $val;

        $taxon_id = $rec['http://rs.tdwg.org/dwc/terms/taxonID'];
        $save = array();
        $save['taxon_id'] = $taxon_id;
        $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
        // $save['measurementRemarks'] = $rec['http://rs.tdwg.org/dwc/terms/establishmentMeans']." (".$rec['http://rs.tdwg.org/dwc/terms/occurrenceStatus'].")"; copied template
        // $save['occur']['establishmentMeans'] = @$rec['http://rs.tdwg.org/dwc/terms/establishmentMeans'];
        // $save['occur']['locality'] = $occur_locality;
        // $save['occur']['eventDate'] = @$rec['http://rs.tdwg.org/dwc/terms/eventDate']; copied template
        // $save['occur']['recordedBy'] = @$rec['http://rs.tdwg.org/dwc/terms/recordedBy']; let us wait for instruction
        // $save['occur']['modified'] = @$rec['http://purl.org/dc/terms/modified']; let us wait for instruction
        // $save['occur']['occurrenceRemarks'] = @$rec['http://rs.tdwg.org/dwc/terms/occurrenceRemarks']; copied template
        $save['bibliographicCitation'] = $this->c_citation[$this->resource_id];
        $save['source'] = $this->c_source[$this->resource_id];
        $save['contributor'] = self::format_contributor($this->info['dataset_names'][$taxon_id]);
        $save['referenceID'] = self::format_referenceID($this->info['references'][$taxon_id]);
        if($mValue && $mType) $ret = $this->func->add_string_types($save, $mValue, $mType, "true");
        
        //for child http://eol.org/schema/terms/SampleSize
        $save = array();
        $mType = 'http://eol.org/schema/terms/SampleSize';
        $mValue = $this->info['samplesize'][$taxon_id];
        $save['taxon_id'] = $taxon_id;
        $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
        $save['parentMeasurementID'] = $ret['measurementID'];
        if($mValue && $mType) $this->func->add_string_types($save, $mValue, $mType, "child");
    }
    private function format_referenceID($arr)
    {   $reference_ids = array();
        $arr = array_keys($arr);
        // print_r($arr); exit;
        /*Array(
            [0] => Royal Botanic Gardens, Kew (2019). Royal Botanic Gardens, Kew - Economic Botany Collection Specimens. Occurrence dataset https://doi.org/10.15468/c3dx8a accessed via GBIF.org on 2019-10-24.
        )*/
        foreach($arr as $ref) {
            $r = new \eol_schema\Reference();
            $r->full_reference = $ref;
            $r->identifier = md5($r->full_reference);
            // $r->uri = ''; copied template
            $reference_ids[] = $r->identifier;
            if(!isset($this->reference_ids[$r->identifier])) {
                $this->reference_ids[$r->identifier] = '';
                $this->archive_builder->write_object_to_file($r);
            }
        }
        if($reference_ids) return implode(";", $reference_ids);
    }
    private function format_contributor($arr)
    {   $final = array();
        $arr = array_keys($arr);
        // print_r($arr); exit;
        /* Array(
            [0] => Royal Botanic Gardens, Kew - Economic Botany Collection Specimens\t|\t1d31211e-350e-492a-a597-34d24bbc1769
        )*/
        foreach($arr as $line) {
            $tmp = explode("\t|\t", $line);
            // print_r($tmp); exit;
            /*Array(
                [0] => Royal Botanic Gardens, Kew - Economic Botany Collection Specimens
                [1] => 1d31211e-350e-492a-a597-34d24bbc1769
            )*/
            $final[] = $tmp[0] . " | " . $this->dataset_page.$tmp[1];
        }
        if($final) return implode(";", $final);
    }
    private function valid_statusYN($status)
    {   /*Array(
            [ACCEPTED] => 
            [SYNONYM] => 
            [] => 
            [DOUBTFUL] => 
        )*/
        if(in_array($status, array('accepted', ''))) return true;
        return false;
    }
    private function get_taxonID($rec)
    {
        if($val = @$rec['http://rs.gbif.org/terms/1.0/taxonKey']) return $val;
        elseif($val = @$rec['http://rs.gbif.org/terms/1.0/acceptedTaxonKey']) return $val;
        elseif($val = @$rec['http://rs.gbif.org/terms/1.0/speciesKey']) return $val;
        return false;
    }
    private function write_taxon($rec)
    {
        $fields = $this->fields_4taxa;
        // print_r($fields); exit;
        $taxon = new \eol_schema\Taxon();
        foreach($fields as $field) {
            $var = pathinfo($field, PATHINFO_BASENAME);
            $taxon->$var = $rec[$field];
        }
        // /* Eli's initiative: if rank is 'genus' then $taxon->genus should be blank
        if($rank = @$rec['http://rs.tdwg.org/dwc/terms/taxonRank']) {
            if(in_array($rank, array('kingdom', 'phylum', 'class', 'order', 'family', 'genus'))) $taxon->$rank = '';
        }
        // */
        @$this->info['samplesize'][$taxon->taxonID]++;

        // /* for contributor in MoF
        if($dataset_key = $rec['http://rs.gbif.org/terms/1.0/datasetKey']) {
            $ret = $this->func_griis->get_dataset_info($dataset_key);
            // print_r($ret); //exit;
            $this->info['dataset_names'][$taxon->taxonID][$ret['dataset_name']."\t|\t".$ret['dataset_key']] = '';
            $this->info['references'][$taxon->taxonID][$ret['citation']] = '';
        }
        // */
        
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    /* copied template
    private function log_record($rec, $sciname = '', $flag = '')
    {
        if(!($file = Functions::file_open($this->log_file, "a"))) return;
        fwrite($file, implode("\t", array($rec['http://rs.tdwg.org/dwc/terms/taxonID'], $rec['http://rs.tdwg.org/dwc/terms/scientificName'], "[$sciname]", $flag))."\n");
        fclose($file);
    }
    */
}
?>