<?php
namespace php_active_record;
/* [SDR.php] */
class SummaryDataResourcesAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        /*
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        */
        $this->download_options = array('resource_id' => 'SDR', 'timeout' => 60*5, 'expire_seconds' => 60*60*24, 'cache' => 1, 'download_wait_time' => 1000000);
        $this->debug = array();
        
        /* Terms relationships -> https://opendata.eol.org/dataset/terms-relationships */
        /* not used at the moment:
        $this->file['parent child']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/f8036c30-f4ab-4796-8705-f3ccd20eb7e9/download/parent-child-aug-16-2.csv";
        $this->file['parent child']['path'] = "http://localhost/cp/summary data resources/parent-child-aug-16-2.csv";
        */
        $this->file['parent child']['fields'] = array('parent', 'child'); //used more simple words instead of: array('parent_term_URI', 'subclass_term_URI');
        
        $this->file['preferred synonym']['path'] = "https://opendata.eol.org/dataset/237b69b7-8aba-4cc4-8223-c433d700a1cc/resource/41f7fed1-3dc1-44d7-bbe5-6104156d1c1e/download/preferredsynonym-aug-16-1-2.csv";
        $this->file['preferred synonym']['path'] = "http://localhost/cp/summary data resources/preferredsynonym-aug-16-1-2-3.csv";

        $this->file['preferred synonym']['fields'] = array('preferred', 'deprecated'); //used simple words instead of: array('preferred_term_URI', 'deprecated_term_URI');

        $this->file['parent child']['path_habitat'] = "http://localhost/cp/summary data resources/habitat-parent-child.csv";
        $this->file['parent child']['path_geoterms'] = "http://localhost/cp/summary data resources/geoterms-parent-child.csv";
        
        $this->dwca_file = "http://localhost/cp/summary data resources/carnivora_sample.tgz";
        $this->report_file = CONTENT_RESOURCE_LOCAL_PATH . '/sample.txt';
        $this->temp_file = CONTENT_RESOURCE_LOCAL_PATH . '/temp.txt';
        
        if(Functions::is_production())  $this->working_dir = "/extra/summary data resources/page_ids/";
        else                            $this->working_dir = "/Volumes/AKiTiO4/web/cp/summary data resources/page_ids/";
        $this->jen_isvat = "/Volumes/AKiTiO4/web/cp/summary data resources/2018 09 08/jen_isvat.txt";
        
        //for taxon summary
        /*
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/b534cd22-d904-45e4-b0e2-aaf06cc0e2d6/download/eoldynamichierarchyv1revised.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary data resources/eoldynamichierarchyv1.zip";
        */
        if(Functions::is_production())  $this->EOL_DH = "https://opendata.eol.org/dataset/b6bb0c9e-681f-4656-b6de-39aa3a82f2de/resource/bac4e11c-28ab-4038-9947-02d9f1b0329f/download/eoldynamichierarchywithlandmarks.zip";
        else                            $this->EOL_DH = "http://localhost/cp/summary%20data%20resources/DH/eoldynamichierarchywithlandmarks.zip";
        
        $this->EOL_DH = "http://localhost/cp/summary%20data%20resources/DH/eoldynamichierarchywithlandmarks.zip";
        $this->basal_values_resource_file = CONTENT_RESOURCE_LOCAL_PATH . '/basal_values_resource.txt';
    }
    function start()
    {
        /* print resource files (Basal values)
        //step 1: get all 'basal values' predicates:
        $predicates = self::get_summ_process_type_given_pred('opposite');
        $predicates = $predicates['basal values'];
        print_r($predicates);
        
        self::working_dir();
        $page_ids = self::get_page_ids_fromTraitsCSV_andInfo_fromDH();

        //--------initialize start
        self::initialize_basal_values();
        //write to DwCA
        $this->resource_id = 'basal_values';
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        //write to file
        if(!($WRITE = Functions::file_open($this->basal_values_resource_file, "w"))) return;
        $row = array("Page ID", 'eol_pk', "http://purl.obolibrary.org/obo/IAO_0000009", "Value URI");
        fwrite($WRITE, implode("\t", $row). "\n");
        //--------initialize end
        
        foreach($predicates as $predicate) {
            foreach($page_ids as $page_id => $taxon) {
                // [328684] => Array(
                //             [taxonRank] => species
                //             [Landmark] => 
                //         )
                if(!$page_id) continue;
                if(@$taxon['taxonRank'] == "species") {
                    if($ret = self::main_basal_values($page_id, $predicate)) {
                        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                        // print_r($ret);
                        self::write_resource_file($ret, $WRITE);
                    }
                }
            }
        }
        
        fclose($WRITE);
        $this->archive_builder->finalize(TRUE);
        if(file_exists($this->path_to_archive_directory."taxon.tab")) Functions::finalize_dwca_resource($this->resource_id);
        exit("\n-end print resource files (Basal values)-\n");
        */
        
        /* WORKING
        $ret = self::get_summ_process_type_given_pred('opposite');
        print_r($ret); exit("\n".count($ret)."\n");
        */
        
        /*
        self::initialize();
        self::investigate_traits_csv(); exit;
        */

        // /* METHOD: parents: basal values {still a work in progress. folder test case is [2018 09 28 basal values parent]}
        // self::parse_DH();
        self::initialize_basal_values();
        $page_id = 7662; $predicate = "http://eol.org/schema/terms/Habitat"; //habitat includes -> orig test case
        $ret = self::main_parents_basal_values($page_id, $predicate);
        exit("\n-- end method: parents: basal values --\n");
        // */

        // /* METHOD: basal values  ============================================================================================================
        self::initialize_basal_values();
        // /* orig write block
        //write to DwCA
        $this->resource_id = 'basal_values';
        // $this->resource_id = '46559217_single';
        $this->basal_values_resource_file = CONTENT_RESOURCE_LOCAL_PATH . "/".$this->resource_id."_resource.txt";
        
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        //write to file
        if(!($WRITE = Functions::file_open($this->basal_values_resource_file, "w"))) return;
        $row = array("Page ID", 'eol_pk', "http://purl.obolibrary.org/obo/IAO_0000009", "Value URI");
        fwrite($WRITE, implode("\t", $row). "\n");
        // */
        
        // $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328607, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328682, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328609, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 328598, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 4442159, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 46559197, 'predicate' => "http://eol.org/schema/terms/Present");
        // $input[] = array('page_id' => 46559217, 'predicate' => "http://eol.org/schema/terms/Present");
        
        // $input[] = array('page_id' => 7662, 'predicate' => "http://eol.org/schema/terms/Habitat"); //first test case     //test case with new 2nd deletion step
        // $input[] = array('page_id' => 328607, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 328682, 'predicate' => "http://eol.org/schema/terms/Habitat");
        $input[] = array('page_id' => 328609, 'predicate' => "http://eol.org/schema/terms/Habitat");                        //test case with new first & second deletion steps
        // $input[] = array('page_id' => 328598, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 4442159, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 46559197, 'predicate' => "http://eol.org/schema/terms/Habitat");
        // $input[] = array('page_id' => 46559217, 'predicate' => "http://eol.org/schema/terms/Habitat"); //test case for write resource

        foreach($input as $i) {
            /* temp block
            $this->taxon_ids = array(); $this->reference_ids = array(); $this->occurrence_ids = array();
            
            //write to DwCA
            $this->resource_id = $i['page_id']."_".pathinfo($i['predicate'], PATHINFO_BASENAME);
            $this->basal_values_resource_file = CONTENT_RESOURCE_LOCAL_PATH . "/".$this->resource_id."_resource.txt";

            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $this->resource_id . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

            //write to file
            if(!($WRITE = Functions::file_open($this->basal_values_resource_file, "w"))) return;
            $row = array("Page ID", 'eol_pk', "http://purl.obolibrary.org/obo/IAO_0000009", "Value URI");
            fwrite($WRITE, implode("\t", $row). "\n");
            */
            
            $page_id = $i['page_id']; $predicate = $i['predicate'];
            if($ret = self::main_basal_values($page_id, $predicate)) {
                $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
                print_r($ret);
                self::write_resource_file($ret, $WRITE);
            }
            
            /* temp block
            fclose($WRITE);
            $this->archive_builder->finalize(TRUE);
            if(file_exists($this->path_to_archive_directory."taxon.tab")) Functions::finalize_dwca_resource($this->resource_id);
            */
        }

        // /* orig write block
        fclose($WRITE);
        $this->archive_builder->finalize(TRUE);
        if(file_exists($this->path_to_archive_directory."taxon.tab")) Functions::finalize_dwca_resource($this->resource_id);
        // */
        
        exit("\n-- end method: basal values --\n");
        // */

        /* METHOD: parents: taxon summary
        self::parse_DH();
        $page_id = 7662; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats -> orig test case
        $ret = self::main_parents_taxon_summary($page_id, $predicate);
        print_r($ret);
        exit("\n-- end method: parents: taxon summary --\n");
        */

        // /* METHOD: taxon summary ============================================================================================================
        self::parse_DH();
        // $page_id = 328607; $predicate = "http://purl.obolibrary.org/obo/RO_0002439"; //preys on - no record
        // $page_id = 328682; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats -- additional test sample but no record for predicate 'eats'.
        
        $page_id = 7666; $page_id = 7662;
        $page_id = 7673; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        $page_id = 7662; $predicate = "http://purl.obolibrary.org/obo/RO_0002458"; //preyed upon by
        $page_id = 46559118; $predicate = "http://purl.obolibrary.org/obo/RO_0002439"; //preys on
        $page_id = 328607; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        $page_id = 46559162; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        $page_id = 46559217; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        $page_id = 328609; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        $page_id = 328598; $predicate = "http://purl.obolibrary.org/obo/RO_0002470"; //eats
        
        self::initialize();
        $ret = self::main_taxon_summary($page_id, $predicate);
        $ret['page_id'] = $page_id; $ret['predicate'] = $predicate;
        echo "\n\nFinal result:"; print_r($ret);
        exit("\n-- end method: 'taxon summary' --\n");
        // */

        /* METHOD: lifestage+statMeth ============================================================================================================
        self::initialize();
        $page_id = 347436; $predicate = "http://purl.obolibrary.org/obo/VT_0001259";
        // $page_id = 347438; 
        // $page_id = 46559130;
        $ret = self::main_lifestage_statMeth($page_id, $predicate);
        print_r($ret);
        exit("\n-- end method: lifestage_statMeth --\n");
        */
    }
    //############################################################################################ start write resource file - method = 'basal values'
    private function write_resource_file($info, $WRITE)
    {   /*when creating new records (non-tips), find and deduplicate all references and bibliographicCitations for each tip record below the node, and attach as references. MeasurementMethod= "summary of records available in EOL". Construct a source link to EOL, eg: https://beta.eol.org/pages/46559143/data */
        $page_id = $info['page_id']; $predicate = $info['predicate'];
        /*step 1: get all eol_pks */
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);

        $found = array();
        foreach($info['Selected'] as $id) {
            foreach($recs as $rec) {
                if($rec['value_uri'] == $id) {
                    $eol_pks[$rec['eol_pk']] = '';
                    $found[] = $id;
                    // /* write to file block
                    $row = array($page_id, $rec['eol_pk'], $id, $info['label']); //, $rec
                    /* fwrite($WRITE, implode("\t", $row). "\n"); //moved below, since we need to adjust selected values available in multiple records -> adjust_if_needed_and_write_existing_records() */
                    $existing_records_for_writing[] = $row;
                    // */
                }
            }
        }
        self::adjust_if_needed_and_write_existing_records($existing_records_for_writing, $WRITE);
        
        $eol_pks = array_keys($eol_pks);
        if($new_records = array_diff($info['Selected'], $found)) {
            // echo "\nNot found in traits.csv. Create new record(s): "; print_r($new_records); //good debug
            $refs = self::get_refs_from_metadata_csv($eol_pks); //get refs for new records, same refs for all new records
            self::create_archive($new_records, $refs, $info);
        }
        else echo "\nNo new records. Will not write to DwCA.\n";
    }
    private function adjust_if_needed_and_write_existing_records($rows, $WRITE)
    {   /*For selected values available in multiple records, let's do an order of precedence based on metadata, with an arbitrary tie-breaker (which you'll need in this case; sorry!). 
          Please count the number of references attached to each candidate record, add 1 if there is a bibliographicCitation for the record, and choose the record with the highest number. 
          In case of a tie, break it with any arbitrary method you like.
        */
        /* forced test data
        $rows = array();
        $rows[] = array(46559217, 'R96-PK42940163', 'http://eol.org/schema/terms/temperate_grasslands_savannas_and_shrublands', 'REP');
        $rows[] = array(46559217, 'R512-PK24322763', 'http://purl.obolibrary.org/obo/ENVO_00000078', 'REP');
        $rows[] = array(46559217, 'R512-PK24381251', 'http://purl.obolibrary.org/obo/ENVO_00000220', 'REP');
        $rows[] = array(46559217, 'R512-PK24428398', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        // $rows[] = array(46559217, 'R512-PK24244192', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        // $rows[] = array(46559217, 'R512-PK23617608', 'http://purl.obolibrary.org/obo/ENVO_00000572', 'REP');
        // $rows[] = array(46559217, 'R512-PK24249316', 'http://purl.obolibrary.org/obo/ENVO_00002033', 'REP');
        // $rows[] = array(46559217, 'R512-PK24569594', 'http://purl.obolibrary.org/obo/ENVO_00000446', 'REP');
        */
        // print_r($rows); //exit;
        //step 1: get counts
        foreach($rows as $row) {
            @$counts[$row[2]]++;
        }
        echo "\ncounts: "; print_r($counts);
        //step 2: get eol_pk if count > 1 -> meaning multiple records
        foreach($rows as $row) {
            $eol_pk = $row[1];
            $value_uri = $row[2];
            if($counts[$value_uri] > 1) @$study[$value_uri][] = $eol_pk;
        }
        if(!isset($study)) { echo "\nNo selected values available in multiple records.\n";
            foreach($rows as $row) fwrite($WRITE, implode("\t", $row). "\n");
            return;
        }
        //step 3: choose 1 among multiple eol_pks based on metadata (references + biblio). If same count just picked one.
        foreach($study as $value_uri => $eol_pks) {
            //get refs for each eol_pk
            foreach($eol_pks as $eol_pk) {
                $refs_of_eol_pk[$eol_pk][] = self::get_refs_from_metadata_csv(array($eol_pk));
            }
        }
        // echo "\n refs_of_eol_pk: "; print_r($refs_of_eol_pk);
        // echo "\n study: "; print_r($study);
        foreach($study as $value_uri => $eol_pks) {
            $ref_counts = array();
            foreach($eol_pks as $eol_pk) {
                $ref_counts[$eol_pk] = count($refs_of_eol_pk[$eol_pk]);
            }
            //compare counts and remove lesser, if equal just pick one
            // echo "\nref_counts: "; print_r($ref_counts);
            $remain[$value_uri][] = self::get_key_of_arr_with_biggest_value($ref_counts);
        }
        echo "\n remain: ";print_r($remain);
        foreach($study as $value_uri => $eol_pks) {
            $remove[$value_uri] = array_diff($eol_pks, $remain[$value_uri]);
        }
        echo "\n remove: ";print_r($remove);
        
        echo "\norig rows count: ".count($rows)."\n";
        //step 4: remove duplicate records
        $i = 0;
        foreach($rows as $row)
        {   /*Array(
            [0] => 46559217
            [1] => R512-PK24467582
            [2] => http://purl.obolibrary.org/obo/ENVO_00000447
            [3] => REP
            )*/
            $eol_pk = $row[1]; $value_uri = $row[2];
            if($eol_pk_2_remove = @$remove[$value_uri]) {
                if(in_array($eol_pk, $eol_pk_2_remove)) $rows[$i] = null;
            }
            $i++;
        }
        $rows = array_filter($rows);
        echo "\nnew rows count: ".count($rows)."\n";
        //step 5: finally writing the rows
        foreach($rows as $row) fwrite($WRITE, implode("\t", $row). "\n");
        return;
    }
    private function pick_one($arr)
    {
        echo "\npick one: \n"; print_r($arr); //exit;
        foreach($arr as $eol_pk) {
        }
    }
    private function create_archive($records, $refs, $info) //EXTENSION_URL: http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd
    {
        // print_r($records); exit;
        foreach($records as $value_uri) { //e.g. http://purl.obolibrary.org/obo/ENVO_01001125
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $info['page_id'];
            $taxon->EOL_taxonID     = $info['page_id'];
            // $taxon->scientificName  = '';
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
            $predicate = $info['predicate'];
            //start structured data
            $rec['label'] = $info['label'];
            $rec['taxon_id'] = $taxon->taxonID;
            $rec['measurementType'] = $predicate;
            $rec['measurementValue'] = $value_uri;
            if($reference_ids = self::create_references($refs)) $rec['referenceID'] = implode("; ", $reference_ids);
            $rec['catnum'] = $taxon->taxonID . "_" . pathinfo($predicate, PATHINFO_BASENAME) . "_" . pathinfo($value_uri, PATHINFO_BASENAME);
            $rec['source'] = "https://beta.eol.org/pages/$taxon->taxonID/data?predicate=$predicate"; //e.g. https://beta.eol.org/pages/46559217/data?predicate=http://eol.org/schema/terms/Habitat
            if($predicate == "http://eol.org/schema/terms/Habitat") self::add_string_types($rec);
            elseif($predicate == "xxx")                             self::add_string_types($rec);
        }
    }
    private function create_references($refs)
    {
        // print_r($refs); exit;
        if(!$refs) return array();
        $reference_ids = array();
        foreach($refs as $ref_no => $full_ref) {
            $r = new \eol_schema\Reference();
            $r->identifier = $ref_no;
            $r->full_reference = $full_ref;
            $reference_ids[$r->identifier] = '';
            if(!isset($this->reference_ids[$r->identifier])) {
               $this->reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return array_keys($reference_ids);
    }
    private function add_string_types($rec)
    {
        $taxon_id = $rec['taxon_id'];
        $catnum = $rec['catnum'];
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum, $rec);

        $m = new \eol_schema\MeasurementOrFact_specific(); //NOTE: used a new class MeasurementOrFact_specific() for non-standard fields like 'm->label'
        $m->label               = $rec['label'];
        $m->occurrenceID        = $occurrence_id;
        $m->measurementOfTaxon  = 'true';
        $m->measurementType     = $rec['measurementType'];
        $m->measurementValue    = $rec['measurementValue'];
        $m->source              = $rec['source'];
        $m->measurementMethod   = 'summary of records available in EOL';
        $m->measurementDeterminedDate = date("Y-M-d");
        $m->referenceID   = @$rec['referenceID']; //not all have refs
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);

        // $m->bibliographicCitation = "AmphibiaWeb: Information on amphibian biology and conservation. [web application]. 2015. Berkeley, California: AmphibiaWeb. Available: http://amphibiaweb.org/.";
        // $m->measurementRemarks  = '';
        // $m->contributor         = '';
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
    }
    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum; //can be just this, no need to add taxon_id
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID      = $taxon_id;
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
    private function get_refs_from_metadata_csv($eol_pks)
    {
        if(!$eol_pks) return array();
        $refs = array();
        $file = fopen($this->main_paths['archive_path'].'/metadata.csv', 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) { $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [eol_pk] => MetaTrait-19117935  [trait_eol_pk] => R261-PK22081478   [predicate] => http://rs.tdwg.org/dwc/terms/measurementMethod
                    [literal] => Activity cycle of each species measured for non-captive populations; adult or age unspecified individuals, male, female, or sex unspecified individuals; primary, secondary, or extrapolated sources; all measures of central tendency; in all localities. Species were defined as (1) nocturnal only, (2) nocturnal/crepuscular, cathemeral, crepuscular or diurnal/crepuscular and (3) diurnal only.  Based on information from primary and secondary literature sources.  See source for details. 
                    [measurement] => [value_uri] => [units] => [sex] => [lifestage] => [statistical_method] => [source] => 
                )*/
                if(in_array($rec['trait_eol_pk'], $eol_pks) && count($fields) == count($line) && $rec['predicate'] == "http://eol.org/schema/reference/referenceID") $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                if(in_array($rec['trait_eol_pk'], $eol_pks) && count($fields) == count($line) && $rec['predicate'] == "http://purl.org/dc/terms/bibliographicCitation") $refs[$rec['eol_pk']] = strip_tags($rec['literal']);
                // $debug[$rec['predicate']] = '';
            }
        }
        // print_r($refs); print_r($debug); exit;
        return $refs;
    }
    private function get_sought_field($recs, $field)
    {
        foreach($recs as $rec) $final[$rec[$field]] = '';
        return array_keys($final);
    }
    private function get_page_ids_fromTraitsCSV_andInfo_fromDH()
    {
        //step 1: get all page_ids from traits.csv
        $ret = self::get_fields_from_file(array('page_id'), 'traits.csv');
        $page_ids = $ret['page_id']; $ret = ''; //unset
        //step 2 get desired info from DH
        $info = self::prep_DH();
        $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )*/
                if(isset($page_ids[$rec['EOLid']])) $page_ids[$rec['EOLid']] = array('taxonRank' => $rec['taxonRank'], 'Landmark' => $rec['Landmark']);
            }
        }
        return $page_ids;
    }
    private function get_fields_from_file($headers, $filename)
    {
        $file = fopen($this->main_paths['archive_path'].'/'.$filename, 'r'); $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                foreach($headers as $head) $final[$head][$rec[$head]] = '';
                /*Array(
                    [eol_pk] => R96-PK42724728
                    [page_id] => 328673
                    [scientific_name] => <i>Panthera pardus</i>
                )*/
            }
        }
        return $final;
    }
    //############################################################################################ start method = 'parents basal values'
    private function main_parents_basal_values($main_page_id, $predicate)
    {
        /* 1. get all children of page_id with rank = species */
        $children = self::get_children_of_rank_species($main_page_id); //orig
        $children = array(328598, 328609, 46559217, 328682, 328607); //force assignment, development only
        
        /* 2. get all values for each child from method = 'basal values' */
        foreach($children as $page_id) {
            if($val = self::main_basal_values($page_id, $predicate)) $records[] = $val;
            // print_r($val); exit;
        }
        /* 3. get all selected values */
        $page_ids = array();
        foreach($records as $rec) {
            if($val = @$rec['Selected']) $page_ids = array_merge($page_ids, $val);
        }
        $original_records = $page_ids;
        asort($original_records); $original_records = array_values($original_records); //reindexes key
        
        
        $page_ids = array_unique($page_ids);
        asort($page_ids);
        $page_ids = array_values($page_ids); //reindexes key
        
        echo "\n==========================================================\nParent process for taxon ID $main_page_id, predicate $predicate\n";
        echo "\nChildren used for computation: "; print_r($children);

        echo "\n==========================================================\nCombined values from the original records (all REC records of children), raw:";
        print_r($original_records);

        echo "\n==========================================================\nDeduplicated:";
        print_r($page_ids);
    }
    //############################################################################################ start method = 'parents taxon summary'
    private function main_parents_taxon_summary($main_page_id, $predicate)
    {
        /* 1. get all children of page_id with rank = species */
        $children = self::get_children_of_rank_species($main_page_id);
        
        /* 2. get all values for each child from method = 'taxon summary' */
        // $children = array(328609); //debug
        foreach($children as $page_id) {
            if($val = self::main_taxon_summary($page_id, $predicate)) $records[] = $val;
            // print_r($val); exit;
        }
        /* 3. get all selected values */
        $page_ids = array();
        foreach($records as $rec) {
            if($val = @$rec['Selected']) $page_ids = array_merge($page_ids, $val);
        }
        $original_records = $page_ids;
        $page_ids = array_unique($page_ids);
        $page_ids = array_values($page_ids); //reindexes key
        
        echo "\n==========================================================\nParent process for taxon ID $main_page_id, predicate $predicate\n";
        echo "\nChildren used for computation: "; print_r($children);

        echo "\n==========================================================\nCombined values from the original records (all REC records of children), raw:";
        print_r($original_records);
        // asort($original_records); print_r($original_records);
        
        echo "\n==========================================================\nCombined values from the original records (all REC records of children), deduplicated:";
        print_r($page_ids);
        
        //now get similar report from 'taxon summary'
        echo "\n==========================================================\nHierarchies of taxon values:";
        $hierarchies_of_taxon_values = array();
        foreach($page_ids as $page_id) {
            $anc = self::get_ancestry_via_DH($page_id);
            $hierarchies_of_taxon_values[$page_id] = $anc;
        }

        // /* NEW STEP: If the common root of the dataset is anything else, you can leave it. Only remove it if it is in the magic 5 of deletable taxa. 
        // $hierarchies_of_taxon_values = self::adjust_2913056($hierarchies_of_taxon_values); MOVED BELOW...
        // */
        print_r($hierarchies_of_taxon_values);
        
        //start store counts 2:
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            $k = 0;
            foreach($anc as $id) {
                @$counts[$id]++;
                if($k > 0) $children_of[$id][] = $anc[$k-1];
                $k++;
            }
        }

        // print_r($counts); //print_r($children_of); //good debug
        $final = array();
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            foreach($anc as $id) {
                if($count = @$counts[$id]) {
                    if($count >= 2) { //meaning this ancestor exists in other recs
                        if($arr = @$children_of[$id]) {
                            $arr = array_unique($arr);
                            if(count($arr) > 1) $final[$page_id][] = $id; //meaning child is not the same for all recs
                        }
                    }
                }
            }
        }
        echo "\n==========================================================\nReduced hierarchies: \n";
        $hierarchies_of_taxon_values = array(); //to be used
        foreach($page_ids as $page_id) {
            echo "\n[$page_id] -> ";
            $hierarchies_of_taxon_values[$page_id] = '';
            if($val = @$final[$page_id]) {
                print_r($val);
                $hierarchies_of_taxon_values[$page_id] = $val;
            }
            else echo "no more ancestry";
        }

        // /* NEW STEP: If the common root of the dataset is anything else, you can leave it. Only remove it if it is in the magic 5 of deletable taxa. 
        $hierarchies_of_taxon_values = self::adjust_2913056($hierarchies_of_taxon_values);
        // */
        echo "\n==========================================================\nHierarchies after removal of the 5 deletable taxa:"; print_r($hierarchies_of_taxon_values);
        $final = $hierarchies_of_taxon_values; //needed assignment

        echo "\n==========================================================\nroots < 15% removal step:\n";
        /* ---------------------------------------------------------------------------------------------------------------------------------------
        "NEW STEP: IF there are multiple roots, discard those representing less than 15% of the original records",
        discard: yes, *in this step* discard means that whole hierarchy
        "original records" is a set just upstream of your second section in your result file: 
        "combined values from the original records (all REC records of children), deduplicated:Array". 
        The list I want is before deduplication, 
        i.e. if 207661 was a value for more than one of the child taxa, it should count more than once in the 15% calculation.
        */
        $ret_roots = self::get_all_roots($final); //get all roots of 'Reduced hierarchies'
        $all_roots = $ret_roots['roots'];
        $count_all_roots = count($all_roots);
        // echo "\nRoots info: "; print_r($ret_roots); //debug only

        // $all_roots = array(1, 42430800); //test force assignment -- debug only
        // if(true) {

        if($count_all_roots > 1) {
            echo "\nMultiple roots: "; print_r($all_roots);
            $temp_final = self::roots_lessthan_15percent_removal_step($original_records, $all_roots, $final);
            if($temp_final != $final) {
                $final = $temp_final;
                echo "\nHierarchies after discarding those representing less than 15% of the original records: "; print_r($final);
            }
            // else echo "\nfinal and temp_final are equal\n"; //just debug
            unset($temp_final);
        }
        else echo "\nJust one root ($all_roots[0]). Will skip this step.\n";

        echo "\n==========================================================\nFinal step:\n";
        /* ---------------------------------------------------------------------------------------------------------------------------------------
        IF >1 roots remain:,
        All the remaining roots are REP records,
        the one that appears in the most ancestries is the PRM,
        ,
        IF one root remains:,
        All direct children of the remaining root are REP records,
        the one that appears in the most ancestries is the PRM,
        (i.e. same behavior as taxon summary),
        ,
        "In this case, one root remains (taxon ID=1)",
        REP records:,
        2774383,
        166,
        10459935,
        ,
        PRM record:,
        2774383,
        */
        $ret_roots = self::get_all_roots($final); //get all roots of 'Reduced hierarchies'
        $all_roots = $ret_roots['roots'];
        $count_all_roots = count($all_roots);
        echo "\nList of root(s) and the corresponding no. of records it existed:"; print_r($ret_roots); //good debug
        if($count_all_roots == 1) {
            echo "\nAll direct children of the remaining root are REP records, the one that appears in the most ancestries is the PRM.\n";
            //from taxon summary:
            $ret = self::get_immediate_children_of_root_info($final);
            $immediate_children_of_root         = $ret['immediate_children_of_root'];
            $immediate_children_of_root_count   = $ret['immediate_children_of_root_count'];

            echo "\nImmediate children of root => and the no. of records it existed:";
            print_r($immediate_children_of_root_count); echo "\n";
            /* ver. 1 strategy
            $root_ancestor = array_unique($root_ancestor);
            */
            // /* ver. 2 strategy
            $root_ancestor = self::get_key_of_arr_with_biggest_value($immediate_children_of_root_count);
            // */
            $immediate_children_of_root = array_keys($immediate_children_of_root);

            echo "\nPRM record: $root_ancestor (the one that appears in the most ancestries)";
            echo "\nREP records: "; print_r($immediate_children_of_root);
            return array('tree' => $final, 'root' => $root_ancestor, 'root label' => 'PRM', 'Selected' => $immediate_children_of_root, 'Selected label' => 'REP');
            
        } //end IF one root remains ------------------------------------------------------------
        elseif($count_all_roots > 1) { //has not met this criteria yet in our test cases.
            echo "\nMore than 1 root remain. All the remaining roots are REP records, the one that appears in the most ancestries is the PRM.\n";
            /* IF >1 roots remain:,
            - All the remaining roots are REP records,
            - the one that appears in the most ancestries is the PRM,
            e.g. List of roots and the corresponding no. of records it existed:
            $ret_roots = Array(
                [roots] => Array(
                        [0] => 1
                        [1] => 173 
                        [2] => 143
                    )
                [count_of_roots] => Array(
                        [1] => 7
                        [173] => 2
                        [143] = 1
                    )
            )*/
            $root_ancestor = self::get_key_of_arr_with_biggest_value($ret_roots['count_of_roots']);
            echo "\nPRM record: $root_ancestor (the one that appears in the most ancestries)";
            echo "\nREP records: "; print_r($ret_roots['roots']);
            return array('tree' => $final, 'root' => $root_ancestor, 'root label' => 'PRM', 'Selected' => $ret_roots['roots'], 'Selected label' => 'REP');
        } //end if > 1 roots remain ------------------------------------------------------------
        exit("\nexit muna\n");
    }
    private function roots_lessthan_15percent_removal_step($original_records, $all_roots, $final_from_main)
    {
        /* compute how many records from the original_records does the root exists */
        foreach($original_records as $page_id) {
            $ancestries[] = self::get_ancestry_via_DH($page_id);
        }
        foreach($all_roots as $root) {
            if(!isset($final[$root])) $final[$root] = 0;
            foreach($ancestries as $anc) {
                if(in_array($root, $anc)) @$final[$root]++;
            }
        }
        // print_r($final); //good debug
        /* get those that are < 15% */
        $remove = array();
        foreach($final as $root => $count) {
            $percentage = ($count/count($original_records))*100;
            $final2['roots % in original records'][$root] = $percentage;
            if($percentage < 15) $remove[] = $root;
        }
        print_r($final2);
        // echo "\nremove: "; print_r($remove);
        
        if($remove) {
            /* remove from $final_from_main those with roots that are < 15% coverage in $original_records */
            foreach($final_from_main as $page_id => $ancestry) {
                if($ancestry) {
                    $orig_ancestry = $ancestry;
                    $root = array_pop($ancestry); //the last rec from an array
                    if(in_array($root, $remove)) {}
                    else $final3[$page_id] = $orig_ancestry;
                }
            }
            // echo "\nwent here 01\n";
            return $final3;
        }
        else  {
            // echo "\nwent here 02\n";
            return $final_from_main;
        }
    }
    private function get_all_roots($reduced_hierarchies)
    {
        foreach($reduced_hierarchies as $page_id => $anc) {
            if($anc) {
                $last = array_pop($anc);
                $final[$last] = '';
                @$count_of_roots[$last]++;
            }
            else { //case where both a root and a tip.
                $final[$page_id] = '';
                @$count_of_roots[$page_id]++;
            }
        }
        return array('roots' => array_keys($final), 'count_of_roots' => $count_of_roots);
    }
    private function adjust_2913056($hierarchies_of_taxon_values) //If the common root of the dataset is anything else, you can leave it. Only remove it if it is 2913056 
    {
        /* Rules:
        - If the root node is any of these five, and if it is common to all 'hierarchies_of_taxon_values', then I'll remove that root node from all hierarchies.
        - If there are multiple root nodes, but all are included in the magic five -> remove all
        - if there are multiple root nodes, some are outside of the magic five -> remove magic 5 roots, leave the others
        */
        $root_nodes_to_remove = array(46702381, 2910700, 6061725, 2908256, 2913056);
        $cont_for_more = false;
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            $orig_anc = $anc;
            $last = array_pop($anc);
            if(in_array($last, $root_nodes_to_remove)) {
                $final[$page_id] = $anc;
                $cont_for_more = true;
            }
            else $final[$page_id] = $orig_anc;
        }
        if($cont_for_more) {
            while(true) {
                $cont_for_more = false;
                foreach($final as $page_id => $anc) {
                    $orig_anc = $anc;
                    $last = array_pop($anc);
                    if(in_array($last, $root_nodes_to_remove)) {
                        $final2[$page_id] = $anc;
                        $cont_for_more = true;
                    }
                    else $final2[$page_id] = $orig_anc;
                }
                if($cont_for_more) {
                    $final = $final2;
                    $final2 = array();
                }
                else break; //break from while true
            }
            return $final2;
        }
        else return $final;
        
        /* version 1 obsolete
        $life = 2913056;
        $remove_last_rec = true;
        foreach($hierarchies_of_taxon_values as $page_id => $anc) {
            $last = array_pop($anc);
            if($last != $life) {
                $remove_last_rec = false; //if only if one is not $life then don't remove last rec.
                break; //end loop
            }
        }
        if($remove_last_rec) {
            echo "\nNOTE: Common root of hierarchies of taxon values (n=".count($hierarchies_of_taxon_values).") is 'Life:2913056'. Will remove this common root.\n";
            $final = array();
            foreach($hierarchies_of_taxon_values as $page_id => $anc) {
                array_pop($anc);
                $final[$page_id] = $anc;
            }
            return $final;
        }
        else return $hierarchies_of_taxon_values;
        */
    }
    private function get_children_of_rank_species($page_id) //TODO
    {
        // return array(328598, 328609, 46559217, 328682, 328607); wrong list
           return array(328598, 46559162, 328607, 46559217, 328609);
    }
    //############################################################################################ end method = 'parents'
    private function extract_DH()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->EOL_DH, "taxa.txt", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $tables['taxa'] = 'taxa.txt';
        $paths['tables'] = $tables;
        return $paths;
    }
    private function prep_DH()
    {
        if(Functions::is_production()) {
            if(!($info = self::extract_DH())) return;
            print_r($info);
            // $this->info_path = $info;
        }
        else { //local development only
            /*
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/EOL_dynamic_hierarchy/',   //for eoldynamichierarchyv1.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/',
                          'tables' => Array('taxa' => 'taxa.txt')); */
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',                         //for eoldynamichierarchywithlandmarks.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',
                          'tables' => Array('taxa' => 'taxa.txt'));
            // $this->info_path = $info;
        }
        return $info;
    }
    private function parse_DH()
    {
        $info = self::prep_DH();
        $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )
                Array(
                    [taxonID] => 93302
                    [acceptedNameUsageID] => 93302
                    [parentNameUsageID] => -1
                    [scientificName] => Cellular Organisms
                    [taxonRank] => clade
                    [source] => trunk:b72c3e8e-100e-4e47-82f6-76c3fd4d9d5f
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 6061725
                    [EOLidAnnotations] => manual;
                    [Landmark] => 
                )
                */
                /* debugging
                // if($rec['EOLid'] == 3014446) {print_r($rec); exit;}
                // if($rec['taxonID'] == 93302) {print_r($rec); exit;}
                // if($rec['Landmark']) print_r($rec);
                if(in_array($rec['EOLid'], Array(7687,3014522,42399419,32005829,3014446,2908256))) print_r($rec);
                */
                $this->EOL_2_DH[$rec['EOLid']] = $rec['taxonID'];
                $this->DH_2_EOL[$rec['taxonID']] = $rec['EOLid'];
                $this->parent_of_taxonID[$rec['taxonID']] = $rec['parentNameUsageID'];
                $this->landmark_value_of[$rec['EOLid']] = $rec['Landmark'];
                if($rec['taxonRank'] == 'family') $this->is_family[$rec['EOLid']] = '';
            }
        }
        /* may not want to force assign this:
        $this->DH_2_EOL[93302] = 6061725; //Biota - Cellular Organisms
        */
        
        // remove temp dir
        // recursive_rmdir($info['temp_dir']);
        // echo ("\n temporary directory removed: " . $info['temp_dir']);
    }
    private function get_ancestry_via_DH($page_id)
    {
        $final = array(); $final2 = array();
        $taxonID = @$this->EOL_2_DH[$page_id];
        if(!$taxonID) {
            echo "\nThis page_id [$page_id] is not found in DH.\n";
            return array();
        }
        while(true) {
            if($parent = @$this->parent_of_taxonID[$taxonID]) $final[] = $parent;
            else break;
            $taxonID = $parent;
        }
        $i = 0;
        foreach($final as $taxonID) {
            // echo "\n$i. [$taxonID] => ";
            if($EOLid = @$this->DH_2_EOL[$taxonID]) {
                /* orig strategy
                $final2[] = $EOLid; */
                /* new strategy: using Landmark value   ver 1
                if($this->landmark_value_of[$EOLid]) $final2[] = $EOLid; */
                // /* new strategy: using Landmark value   ver 2
                if($this->landmark_value_of[$EOLid] || isset($this->is_family[$EOLid])) $final2[] = $EOLid;
                // */
            }
            $i++;
        }
        return $final2;
    }
    private function main_taxon_summary($page_id, $predicate)
    {
        // /* working but seems not needed. Just bring it back when requested.
        $ancestry = self::get_ancestry_via_DH($page_id);
        echo "\n$page_id: (ancestors below, with {Landmark value} in curly brackets)";
        foreach($ancestry as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
        // */
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        $path = self::get_txt_path_by_page_id($page_id);
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        if(!$recs) { echo "\nNo records for [$page_id] [$predicate].\n"; return; }
        echo "\nrecs: ".count($recs)."\n";
        // print_r($recs);
        /* Jen's verbatim instruction: to get the reduced 'tree'
        For each ancestor, find all recs in which it appears (recs set 1)
        If the parent of that ancestor is the same in all the recs in rec set 1, remove the parent

        Eli's interpretation: which gets the same results:
        - get all ancestors that exist also in other recs.
        - among these ancestors, select those where it has > 1 children. Don't include those with the same child in its occurrence in other recs.
        */
        foreach($recs as $rec) {
            if($page_id = @$rec['object_page_id']) {
                $anc = self::get_ancestry_via_DH($page_id);
                // /* initial report for Jen
                // echo "\nAncestry [$page_id]: "; print_r($anc); //orig initial report
                if($anc) {
                    echo "\n$page_id: (ancestors below, with {Landmark value} in curly brackets)";
                    foreach($anc as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
                }
                // */
                //start store counts 1:
                $k = 0;
                foreach($anc as $id) {
                    @$counts[$id]++;
                    if($k > 0) $children_of[$id][] = $anc[$k-1];
                    $k++;
                }
            }
        }
        // print_r($counts); print_r($children_of); //good debug
        $final = array();
        foreach($recs as $rec) {
            if($page_id = @$rec['object_page_id']) {
                $anc = self::get_ancestry_via_DH($page_id);
                foreach($anc as $id) {
                    if($count = @$counts[$id]) {
                        if($count >= 2) { //meaning this ancestor exists in other recs
                            if($arr = @$children_of[$id]) {
                                $arr = array_unique($arr);
                                if(count($arr) > 1) $final[$page_id][] = $id; //meaning child is not the same for all recs
                            }
                        }
                    }
                }
            }
        }
        
        echo "\n==========================================\nTips on left. Ancestors on right.\n";
        // print_r($final);
        foreach($final as $tip => $ancestors) {
            echo "\n$tip: (reduced ancestors below, with {Landmark value} in curly brackets)";
            foreach($ancestors as $anc_id) echo "\n --- $anc_id {".$this->landmark_value_of[$anc_id]."}";
        }
        echo "\n";
        
        /* may not need this anymore: get tips
        $tips = array_keys($final); //next step is get all tips from $final; 
        echo "\n tips: ".count($tips)." - "; print_r($tips);
        */

        /* from Jen: After the tree is constructed:
        - Select all immediate children of the root and label REP.
        - Label the root PRM
        */
        echo "\n final array: ".count($final); print_r($final);
        if(!$final) return false;
        /* WORKING WELL but was made into a function -> get_immediate_children_of_root_info($final)
        foreach($final as $tip => $ancestors) {
            $root_ancestor[] = end($ancestors);
            $no_of_rows = count($ancestors);
            if($no_of_rows > 1) $idx = $ancestors[$no_of_rows-2]; // rows should be > 1 bec if only 1 then there is no child for that root.
            elseif($no_of_rows == 1) $idx = $tip; 
            else exit("\nInvestigate: won't go here...\n");
            $immediate_children_of_root[$idx] = '';
            @$immediate_children_of_root_count[$idx]++;
        }
        */
        $ret = self::get_immediate_children_of_root_info($final);
        $immediate_children_of_root         = $ret['immediate_children_of_root'];
        $immediate_children_of_root_count   = $ret['immediate_children_of_root_count'];
        
        echo "\nImmediate children of root => no. of records it existed:";
        print_r($immediate_children_of_root_count); echo "\n";
        /* ver. 1 strategy
        $root_ancestor = array_unique($root_ancestor);
        */
        // /* ver. 2 strategy
        $root_ancestor = self::get_key_of_arr_with_biggest_value($immediate_children_of_root_count);
        // */
        $immediate_children_of_root = array_keys($immediate_children_of_root);
        
        echo "\n root: "; print_r($root_ancestor);
        echo "\n immediate_children_of_root: "; print_r($immediate_children_of_root);
        //'tree' => $final,
        return array('root' => $root_ancestor, 'root label' => 'PRM', 'Selected' => $immediate_children_of_root, 'Selected label' => 'REP');
    }
    private function get_immediate_children_of_root_info($final)
    {
        foreach($final as $tip => $ancestors) {
            if($ancestors) {
                $root_ancestor[] = end($ancestors);
                $no_of_rows = count($ancestors);
                if($no_of_rows > 1) $idx = $ancestors[$no_of_rows-2]; // rows should be > 1 bec if only 1 then there is no child for that root.
                elseif($no_of_rows == 1) $idx = $tip; 
                else exit("\nInvestigate: won't go here...\n");
                $immediate_children_of_root[$idx] = '';
                @$immediate_children_of_root_count[$idx]++;
            }
        }
        return array('immediate_children_of_root' => $immediate_children_of_root, 'immediate_children_of_root_count' => $immediate_children_of_root_count);
    }
    private function get_key_of_arr_with_biggest_value($arr)
    {
        $val = 0;
        foreach($arr as $key => $value) {
            if($value > $val) $ret = $key;
            $val = $value;
        }
        return $ret;
    }
    
    private function main_lifestage_statMeth($page_id, $predicate)
    {
        $path = self::get_txt_path_by_page_id($page_id);
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate);
        if(!$recs) { echo "\nNo records for [$page_id] [$predicate].\n"; return; }
        echo "\nrecs: ".count($recs)."\n";
        // print_r($recs);
        if    ($ret = self::lifestage_statMeth_Step0($recs)) {}
        elseif($ret = self::lifestage_statMeth_Step1($recs)) {}
        elseif($ret = self::lifestage_statMeth_Step23456789($recs)) {}
        else exit("\nsingle simple answer (PRM) if still needed: put REP records in order of value and select one from the middle (arbitrary tie breaks OK)\n");
        if($val = @$ret['recs']) $ret['recs_total'] = count($val);
        return $ret;
    }
    private function lifestage_statMeth_Step0($recs)
    {
        if(count($recs) == 1) return array('label' => 'REP and PRM', 'recs' => $recs, 'step' => 0);
        else return false;
    }
    private function lifestage_statMeth_Step1($recs)
    {
        $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult");
        $final = array();
        foreach($recs as $rec) {
            /* print_r($rec); exit;
            Array(
                [eol_pk] => R143-PK39533097
                [page_id] => 46559130
                [scientific_name] => <i>Enhydra lutris</i>
                ...more fields below
            */
            if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                $statMethods = array("http://semanticscience.org/resource/SIO_001109", "http://semanticscience.org/resource/SIO_001110", "http://semanticscience.org/resource/SIO_001111");
                if(in_array($rec['statistical_method'], $statMethods)) $final[] = $rec;
            }
        }
        if(!$final) return false;
        else {
            if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => 1);
            elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => 1);
        }
    }
    private function lifestage_statMeth_Step23456789($recs) //steps 2,3,4,5 & 6 7 8 & 9
    {
        /* Step 2,3,4,5 */
        $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult");
        $statMethods = array("http://eol.org/schema/terms/average", "http://semanticscience.org/resource/SIO_001114", "http://www.ebi.ac.uk/efo/EFO_0001444", ""); //in specific order
        $step = 1;
        foreach($statMethods as $method) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                    if($rec['statistical_method'] == $method) $final[] = $rec;
                }
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        /* Step 6 , 7 , 8 */
        $stages = array("http://purl.obolibrary.org/obo/PO_0007134", "", "http://eol.org/schema/terms/subadult"); //in specific order
        $step = 5;
        foreach($stages as $stage) { $step++;
            $final = array();
            foreach($recs as $rec) {
                if($rec['lifestage'] == $stage) $final[] = $rec;
            }
            if($final) {
                if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => $step);
                elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => $step);
            }
        }
        /* Step 9 */
        $final = array();
        foreach($recs as $rec) {
            $possible_adult_lifestage = array("http://www.ebi.ac.uk/efo/EFO_0001272", "http://purl.obolibrary.org/obo/PATO_0001701", "http://eol.org/schema/terms/parasiticAdult", "http://eol.org/schema/terms/freelivingAdult", "http://eol.org/schema/terms/ovigerous", "http://purl.obolibrary.org/obo/UBERON_0007222", "http://eol.org/schema/terms/youngAdult", "adult");
            if(in_array($rec['lifestage'], $possible_adult_lifestage)) {
                $statMethods = array("http://semanticscience.org/resource/SIO_001113");
                if(in_array($rec['statistical_method'], $statMethods)) $final[] = $rec;
            }
        }
        if(!$final) return false;
        else {
            if    (count($final) == 1) return array('label' => 'PRM and REP', 'recs' => $final, 'step' => 9);
            elseif(count($final) > 1)  return array('label' => 'REP', 'recs' => $final, 'step' => 9);
        }
        return false;
    }
    private function get_txt_path_by_page_id($page_id)
    {
        $path = self::get_md5_path($this->working_dir, $page_id);
        return $path . $page_id . ".txt";
    }
    private function test() //basal values tests...
    {
        // self::utility_compare();
        /* IMPORTANT STEP: working OK - commented for now.
        self::working_dir(); self::generate_page_id_txt_files(); exit("\n\nText file generation DONE.\n\n");
        */
    }
    private function main_basal_values($page_id, $predicate) //for basal values
    {
        $this->original_nodes = array(); //IMPORTANT to initialize especially for multiple calls of this function main_basal_values()
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        $recs = self::assemble_recs_for_page_id_from_text_file($page_id, $predicate, array('value_uri')); //3rd param array is required_fields
        if(!$recs) {
            echo "\nNo records for [$page_id] [$predicate].\n";
            return false;
        }
        $uris = self::get_valueUris_from_recs($recs);
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $ISVAT = self::get_initial_shared_values_ancestry_tree($recs); //initial "shared values ancestry tree" ---> parent left, term right
        $ISVAT = self::sort_ISVAT($ISVAT);
        $info = self::add_new_nodes_for_NotRootParents($ISVAT);
        $new_nodes = $info['new_nodes'];    
        echo "\n\nnew nodes 0:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        
        $info['new_nodes'] = self::sort_ISVAT($new_nodes);
        $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        /* good debug
        echo "\n\nnew nodes 1:\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1];
        echo "\n\nRoots 1: ".count($roots)."\n"; print_r($roots);
        */
        
        // /* merge
        $info = self::merge_nodes($info, $ISVAT);
        $ISVAT     = $info['new_isvat'];
        $roots     = $info['new_roots'];
        $new_nodes = array();
        // */
        
        // /*
        //for jen: 
        echo "\n================================================================\npage_id: $page_id | predicate: [$predicate]\n";
        echo "\n\ninitial shared values ancestry tree: ".count($ISVAT)."\n";
        foreach($ISVAT as $a) echo "\n".$a[0]."\t".$a[1];
        // echo "\n\nnew nodes: ".count($new_nodes)."\n"; foreach($new_nodes as $a) echo "\n".$a[0]."\t".$a[1]; //good debug
        echo "\n\nRoots: ".count($roots)."\n"; print_r($roots);
        // exit("\n");
        // */
        
        //for step 1: So, first you must identify the tips- any values that don't appear in the left column. The parents, for step one, will be the values to the left of the tip values.
        $tips = self::get_tips($ISVAT);
        echo "\n tips: ".count($tips);
        foreach($tips as $tip) echo "\n$tip";
        echo "\n-end tips-\n"; //exit;
        
        if(count($tips) <= 5 ) $selected = $tips;
        else { // > 5
            
            // /* Two new steps from Jen & Katja
            $ret_from_2new_steps = self::two_new_steps($ISVAT, $roots, $tips);
            $roots = $ret_from_2new_steps['roots'];
            $tips = $ret_from_2new_steps['tips'];
            $ISVAT = $ret_from_2new_steps['ISVAT'];
            echo "\nnew tips: ".count($tips); foreach($tips as $tip) echo "\n".$tip;
            echo "\n";
            // */
            
            $step_1 = self::get_step_1($ISVAT, $roots, $tips, 1);
            if(count($step_1) <= 4) $selected = $step_1; //select set 1
            else {
                $step_2 = self::get_step_1($ISVAT, $roots, $step_1, 2);
                if(count($step_2) <= 4) $selected = $step_2; //select set 2
                else {
                    $step_3 = self::get_step_1($ISVAT, $roots, $step_2, 3);
                    if($step_2 == $step_3) {
                        echo "\nSteps 2 and 3 are identical.\n";
                        if(count($step_3) <= 4) $selected = $step_3; //select set 3
                        else {
                            echo "\nSelect root ancestors\n";
                            $selected = $roots;
                        }
                    }
                    else {
                        echo "\nStep 2 and Step 3 are different. Proceed with Step 4\n";
                        $step_4 = self::get_step_1($ISVAT, $roots, $step_3, 4);
                        if($step_3 == $step_4) {
                            echo "\nSteps 3 and 4 are identical.\n";
                            if(count($step_4) <= 4) $selected = $step_4; //select set 4
                            else {
                                echo "\nSelect root ancestors\n";
                                $selected = $roots;
                            }
                        }
                        else {
                            echo "\nStep 3 and Step 4 are different. Proceed with Step 5\n";
                            // exit("\nConstruct Step 5\n");
                            $step_5 = self::get_step_1($ISVAT, $roots, $step_4, 5);
                            if($step_4 == $step_5) {
                                echo "\nSteps 4 and 5 are identical.\n";
                                if(count($step_5) <= 4) $selected = $step_5; //select set 5
                                else {
                                    echo "\nSelect root ancestors\n";
                                    $selected = $roots;
                                }
                            }
                            else {
                                echo "\nStep 4 and Step 5 are different. Proceed with Step 6\n";
                                exit("\nConstruct Step 6\n");
                            }
                        }
                    }
                }
            }
        }

        //label PRM and REP if one record, REP if > 1
        if    (count($selected) == 1) $label = "PRM and REP";
        elseif(count($selected) > 1)  $label = "REP";
        echo "\n----- label as: [$label]\n";
        $selected = array_values($selected); //reindex array
        return array('Selected' => $selected, 'label' => $label);
        /*
        if tips <= 5 SELECT ALL TIPS 
        else
            GET SET_1
            if SET_1 <= 4 SELECT SET_1
            else 
                GET SET_2
                if SET_2 <= 4 SELECT SET_2
                else
                    GET SET_3
                    if SET_2 == SET_3
                        if SET_3 <= 4 SELECT SET_3
                        else SELECT ROOT_ANCESTORS
                    else CONTINUE PROCESS UNTIL all parents of the values in the set are roots, THEN IF <= 4 SELECT THAT SET else SELECT ROOT_ANCESTORS.

        if(WHAT IS SELECTED == 1) label as: "PRM and REP"
        elseif(WHAT IS SELECTED > 1) label as: "REP"

        So in our case: page_id: 7662 | predicate: [http://eol.org/schema/terms/Habitat]
        I will be creating new rocords based on 'ROOT_ANCESTORS'.
        */
    }
    private function two_new_steps($ISVAT, $roots, $tips)
    {
        echo "\nroots: ".count($roots); print_r($roots);
        /* DELETE ALONG WITH CHILDREN
            look for these nodes in the list of roots
            are there any other roots aside from the nodes in this list?
                if not, do nothing
                if so, keep the root nodes that are NOT on this list, and all their descendants. Discard all other nodes 
            the list:
        */
        echo "\n--------------------------------------------DELETE ALONG WITH CHILDREN step: -START-\n";
        $delete_list_1 = array('http://purl.obolibrary.org/obo/ENVO_00000094', 'http://purl.obolibrary.org/obo/ENVO_01000155', 'http://purl.obolibrary.org/obo/ENVO_00000002', 'http://purl.obolibrary.org/obo/ENVO_00000077');
        echo "\nDelete List: "; print_r($delete_list_1);
        if($roots_inside_the_list = self::get_roots_inside_the_list($roots, $delete_list_1)) {
            // exit("\ntest sample here...\n");
            echo "\nThere are root(s) in the 1st list: ".count($roots_inside_the_list)." "; print_r($roots_inside_the_list);
            echo "\norig 'shared values ancestry tree': ".count($ISVAT)."\n";
            foreach($ISVAT as $a) {
                if(              in_array($a[0], $roots_inside_the_list)) {}
                elseif(!$a[0] && in_array($a[1], $roots_inside_the_list)) {}
                else $new_isvat[] = $a;
            }
            echo "\ntrimmed shared ancestry tree: ".count($new_isvat); foreach($new_isvat as $a) echo "\n".$a[0]."\t".$a[1];
            $roots = array_diff($roots, $roots_inside_the_list);
            echo "\n\nnew roots: ".count($roots)."\n"; print_r($roots);
        }
        else {
            echo "\nAll root nodes are not on the list. Keeping all root nodes and all descendants. Do nothing.\n";
            $new_isvat = $ISVAT;
        }
        echo "\n-------------------------------------------- -END-\n";
        /*DELETE, BUT KEEP THE CHILDREN
            look for these nodes in the list of roots
            remove them. Their immediate children are now roots.
            the list:
        (it's OK if occasionally this leaves you with no records.)
        */
        echo "\n--------------------------------------------DELETE, BUT KEEP THE CHILDREN step: -START-\n";
        $delete_list_2 = array('http://purl.obolibrary.org/obo/ENVO_01001305', 'http://purl.obolibrary.org/obo/ENVO_00002030', 'http://purl.obolibrary.org/obo/ENVO_01000687');
        echo "\nDelete List: "; print_r($delete_list_2);
        echo "\n\nroots: ".count($roots)."\n"; print_r($roots);
        if($roots_inside_the_list = self::get_roots_inside_the_list($roots, $delete_list_2)) {
            echo "\nThere are root(s) found in the list: ".count($roots_inside_the_list)." "; print_r($roots_inside_the_list);
            $all_left_of_tree = self::get_all_left_of_tree($new_isvat);
            $add_2_roots = array();
            foreach($new_isvat as $a) {
                if(in_array($a[0], $roots_inside_the_list)) {
                    if(!in_array($a[1], $all_left_of_tree)) {
                        $new_isvat_2[] = array("", $a[1]);
                        $add_2_roots[$a[1]] = '';
                    }
                }
                else $new_isvat_2[] = $a;
            }
            echo "\ntrimmed shared ancestry tree: ".count($new_isvat_2); foreach($new_isvat_2 as $a) echo "\n".$a[0]."\t".$a[1];
            $roots = array_diff($roots, $roots_inside_the_list);
            if($add_2_roots) $roots = array_merge($roots, array_keys($add_2_roots));
            echo "\n\nnew roots: ".count($roots)."\n"; print_r($roots);
        }
        else {
            echo "\nNo roots inside the list. Do nothing.\n";
            $new_isvat_2 = $new_isvat;
        }
        echo "\n-------------------------------------------- -END-\n";
        return array('roots' => $roots, 'tips' => self::get_tips($new_isvat_2), 'ISVAT' => $new_isvat_2);
        // exit("\nend temp\n");
    }
    private function get_all_left_of_tree($tree)
    {
        foreach($tree as $a) $final[$a[0]] = '';
        return array_keys($final);
    }
    private function get_roots_inside_the_list($roots, $list)
    {
        $roots_inside_the_list = array();
        foreach($roots as $root) {
            if(in_array($root, $list)) $roots_inside_the_list[] = $root;
        }
        return $roots_inside_the_list;
    }
    private function get_tips($isvat)
    {
        foreach($isvat as $a) {
            $left[$a[0]] = '';
            $right[$a[1]] = '';
        }
        $right = array_keys($right);
        foreach($right as $node) {
            if(!isset($left[$node])) $final[$node] = '';
        }
        $final = array_keys($final);
        asort($final);
        return $final;
    }

    private function get_step_1($isvat, $roots, $tips, $step_no)
    {   /* 
        - find all tips
        - find all nodes that are parents of tips
        - in each case, check whether either the tip or the parent is a root
            -- if either the tip or the parent is a root, put the tip in set 1
            -- if neither the tip nor the parent is a root, put the parent in set 1
        - (deduplicate set 1) */
        foreach($isvat as $a) {
            $parent_of_right[$a[1]] = $a[0];
        }
        foreach($tips as $tip) {
            if($parent = @$parent_of_right[$tip]) {
                if(in_array($tip, $roots) || in_array($parent, $roots)) $final[$tip] = '';
                if(!in_array($tip, $roots) && !in_array($parent, $roots)) $final[$parent] = '';
            }
            else {
                if(in_array($tip, $roots)) $final[$tip] = '';
            }
        }
        $final = array_keys($final);
        asort($final);
        
        //optional display
        echo "\nStep $step_no:".count($final)."\n";
        foreach($final as $a) echo "\n".$a;
        echo "\n-end Step $step_no-\n";
        
        return $final;
    }
    private function utility_compare()
    {
        foreach(new FileIterator($this->jen_isvat) as $line_number => $line) {
            $arr[] = explode("\t", $line);
        }
        asort($arr); foreach($arr as $a) echo "\n".$a[0]."\t".$a[1];
        exit("\njen_isvat.txt\n");
    }
    private function merge_nodes($info, $ISVAT)
    {
        $new_nodes = $info['new_nodes'];
        $roots     = $info['roots'];
        
        $new_isvat = array_merge($ISVAT, $new_nodes);
        $new_isvat = self::sort_ISVAT($new_isvat);
        $new_isvat = self::remove_orphans_that_exist_elsewhere($new_isvat);
        
        $new_roots = $roots;
        foreach($new_isvat as $a) {
            if(!$a[0]) $new_roots[] = $a[1];
        }
        asort($new_roots);
        
        //scan new isvat for new roots
        foreach($new_isvat as $a) {
            if(!$a[0]) continue;
            if(@$this->parents_of[$a[0]]) {} // echo " - not root, has parents ".count($arr);
            else $new_roots[] = $a[0];
        }
        $new_roots = array_unique($new_roots);
        $new_roots = array_filter($new_roots); //remove null values
        asort($new_roots);
        
        return array('new_roots' => $new_roots, 'new_isvat' => $new_isvat);
    }
    private function remove_orphans_that_exist_elsewhere($isvat) //that is remove the orphan row
    {
        //first get all non-orphan rows
        foreach($isvat as $a) {
            if($a[0]) {
                $left[$a[0]] = '';
                $right[$a[1]] = '';
            }
        }
        //if orphan $a[1] exists elsewhere then remove that orphan row
        //The way I was thinking of documenting, it wouldn't need to be listed as an orphan if it also appears in any relationship pair.
        foreach($isvat as $a) {
            if(!$a[0] && (
                            isset($left[$a[1]]) || isset($right[$a[1]])
                         )
            ){
                echo "\n === $a[0] --- $a[1] === remove orphan coz it exists elsewhere \n"; //the orphan row ENVO_00000446 was removed here...
            }
            else $final[] = $a;
        }
        return $final;
    }
    private function sort_ISVAT($arr) //also remove parent nodes where there is only one child. Make child an orphan.
    {
        if(!$arr) return array();
        rsort($arr);
        foreach($arr as $a) {
            @$temp[$a[0]][$a[1]] = ''; //to be used in $totals
            $right_cols[$a[1]] = '';
            $temp2[$a[0]] = $a[1];
            $left_cols[$a[0]] = '';
        }
        asort($temp);
        foreach($temp as $key => $value) $totals[$key] = count($value);
        print_r($totals);

        $discard_parents = array(); echo "\n--------------------\n";
        foreach($totals as $key => $total_children) {
            if($total_children == 1) {
                echo "\n $key: with 1 child ";
                if(isset($right_cols[$key])) echo " -- appears in a relationship pair (right)";
                /* "Ancestors can be removed if they are parents of only one node BUT that node must NOT be an original node" THIS IS WRONG RULE!!!
                elseif(isset($this->original_nodes[$temp2[$key]])) {
                    echo "\nxxx $key --- ".@$temp2[$key]." parent of just 1 node BUT an original node\n";
                }
                */
                // /* THIS IS THE CORRECT RULE
                elseif(isset($this->original_nodes[$key])) {
                    echo "\nxxx $key --- parent of just 1 node BUT ancestor is an original node\n";
                }
                // */
                else $discard_parents[] = $key;
            }
        }
        echo "\n discarded_parents:"; print_r($discard_parents); echo "\n-----\n";
        
        $final = array();
        foreach($arr as $a) {
            if(in_array($a[0], $discard_parents)) $final[] = array("", $a[1]);
            else                                  $final[] = array($a[0], $a[1]);
        }
        asort($final);
        $final = array_unique($final, SORT_REGULAR);
        return $final;
    }
    private function generate_page_id_txt_files()
    {
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++; echo " $i";
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit;
                /*Array(
                    [eol_pk] => R96-PK42724728
                    [page_id] => 328673
                    [scientific_name] => <i>Panthera pardus</i>
                    [resource_pk] => M_00238837
                    [predicate] => http://eol.org/schema/terms/Present
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://www.worldwildlife.org/publications/wildfinder-database
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => http://eol.org/schema/terms/Southern_Zanzibar-Inhambane_coastal_forest_mosaic
                    [literal] => http://eol.org/schema/terms/Southern_Zanzibar-Inhambane_coastal_forest_mosaic
                    [measurement] => 
                    [units] => 
                    [normal_measurement] => 
                    [normal_units_uri] => 
                    [resource_id] => 20
                )*/

                $path = self::get_md5_path($this->working_dir, $rec['page_id']);
                $txt_file = $path . $rec['page_id'] . ".txt";
                if(file_exists($txt_file)) {
                    $WRITE = fopen($txt_file, 'a');
                    fwrite($WRITE, implode("\t", $line)."\n");
                    fclose($WRITE);
                }
                else {
                    $WRITE = fopen($txt_file, 'w');
                    fwrite($WRITE, implode("\t", $fields)."\n");
                    fwrite($WRITE, implode("\t", $line)."\n");
                    fclose($WRITE);
                }
            }
        }
        fclose($file);
    }
    private function get_md5_path($path, $taxonkey)
    {
        $md5 = md5($taxonkey);
        $cache1 = substr($md5, 0, 2);
        $cache2 = substr($md5, 2, 2);
        if(!file_exists($path . $cache1)) mkdir($path . $cache1);
        if(!file_exists($path . "$cache1/$cache2")) mkdir($path . "$cache1/$cache2");
        return $path . "$cache1/$cache2/";
    }
    private function assemble_recs_for_page_id_from_text_file($page_id, $predicate, $required_fields = array())
    {
        $recs = array();
        $txt_file = self::get_txt_path_by_page_id($page_id);
        echo "\n$txt_file\n";
        if(!file_exists($txt_file)) {
            echo "\nFile does not exist.\n";
            return false;
        }
        $i = 0;
        foreach(new FileIterator($txt_file) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /* Array( old during development with Jen
                    [page_id] => 46559197
                    [scientific_name] => <i>Arctocephalus tropicalis</i>
                    [predicate] => http://eol.org/schema/terms/Present
                    [value_uri] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
                )*/
                /*Array(
                    [eol_pk] => R143-PK39533505
                    [page_id] => 46559197
                    [scientific_name] => <i>Arctocephalus tropicalis</i>
                    [resource_pk] => 17255
                    [predicate] => http://eol.org/schema/terms/WeaningAge
                    [sex] => 
                    [lifestage] => 
                    [statistical_method] => 
                    [source] => http://genomics.senescence.info/species/entry.php?species=Arctocephalus_tropicalis
                    [object_page_id] => 
                    [target_scientific_name] => 
                    [value_uri] => 
                    [literal] => 
                    [measurement] => 239
                    [units] => http://purl.obolibrary.org/obo/UO_0000033
                    [normal_measurement] => 0.6543597746702533
                    [normal_units_uri] => http://purl.obolibrary.org/obo/UO_0000036
                    [resource_id] => 50
                )*/
                if($predicate == $rec['predicate']) {
                    if($required_fields) {
                        foreach($required_fields as $required_fld) {
                            if(!$rec[$required_fld]) continue; //e.g. value_uri
                            else $recs[] = $rec;
                        }
                    }
                    else $recs[] = $rec;
                }
                $this->original_nodes[$rec['value_uri']] = '';
            }
        }
        return $recs;
    }
    private function initialize()
    {
        self::working_dir();
    }
    private function initialize_basal_values()
    {
        self::working_dir();
        self::generate_terms_values_child_parent_list($this->file['parent child']['path_habitat']);
        self::generate_terms_values_child_parent_list($this->file['parent child']['path_geoterms']);
        self::generate_preferred_child_parent_list();
    }
    private function add_new_nodes_for_NotRootParents($list)
    {   //1st step: get unique parents
        foreach($list as $rec) {
            /*Array(
                [0] => http://www.geonames.org/6255151
                [1] => http://www.marineregions.org/gazetteer.php?p=details&id=australia
            )*/
            $unique[$rec[0]] = '';
        }
        //2nd step: check if parent is not root (meaning has parents), if yes: get parent and add the new node:
        $recs = array();
        foreach(array_keys($unique) as $child) {
            // echo "\n$child: ";
            if($arr = @$this->parents_of[$child]) { // echo " - not root, has parents ".count($arr);
                foreach($arr as $new_parent) {
                    if($new_parent) $recs[] = array($new_parent, $child);
                }
            }
            else $roots[] = $child; // echo " - already root";
        }
        return array('roots' => $roots, 'new_nodes' => $recs);
    }
    private function get_valueUris_from_recs($recs)
    {
        $uris = array();
        foreach($recs as $rec) $uris[] = $rec['value_uri'];
        return $uris;
    }
    private function get_initial_shared_values_ancestry_tree($recs)
    {
        $final = array();
        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($recs as $rec) {
            $term = $rec['value_uri'];
            $parent = self::get_parent_of_term($term);
            $final[] = array($parent, $term);
        }
        return $final;
    }
    function start_ok()
    {
        self::initialize();
        $uris = array(); //just during development --- assign uris here...
        self::set_ancestor_ranking_from_set_of_uris($uris);
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=australia";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4366";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4364";
        $terms[] = "http://www.geonames.org/2186224";
        $terms[] = "http://www.geonames.org/3370751";                               //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1914";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1904";  //error
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=1910";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4276";
        $terms[] = "http://www.marineregions.org/gazetteer.php?p=details&id=4365";
        $terms[] = "http://www.geonames.org/953987";
        $terms[] = "http://www.marineregions.org/mrgid/1914";
        $WRITE = fopen($this->temp_file, 'w'); fclose($WRITE);
        foreach($terms as $term) self::get_parent_of_term($term);
        exit("\nend 01\n");
    }
    private function set_ancestor_ranking_from_set_of_uris($uris)
    {
        $final = array(); $final_preferred = array();
        foreach($uris as $term) {
            if(!$term) continue;
            if($preferred_terms = @$this->preferred_names_of[$term]) {
                // echo "\nThere are preferred term(s):\n";
                // print_r($preferred_terms);
                foreach($preferred_terms as $pterm) {
                    @$final_preferred[$pterm]++;
                    // echo "\nparent(s) of $pterm:";
                    if($parents = @$this->parents_of[$pterm]) {
                        // print_r($parents);
                        foreach($parents as $parent) @$final[$parent]++;
                    }
                    // else echo " -- NO parent";
                }
            }
            else { //no preferred term
                if($parents = @$this->parents_of[$term]) {
                    foreach($parents as $parent) @$final[$parent]++;
                }
                // else exit("\n\nHmmm no preferred and no immediate parent for term: [$term]\n\n"); //seems acceptable
            }
        }//end main
        /*
        foreach($uris as $term) {
            if($parents = @$this->parents_of[$term]) {
                foreach($parents as $parent) @$final[$parent]++;
            }
        }//end main
        */
        arsort($final);
        $final = array_keys($final);
        $this->ancestor_ranking = $final;

        arsort($final_preferred);
        // print_r($final_preferred);
        $final_preferred = array_keys($final_preferred);
        // print_r($final_preferred);
        $this->ancestor_ranking_preferred = $final_preferred;
    }
    private function get_rank_most_parent($parents, $preferred_terms = array())
    {
        if(!$preferred_terms) {
            //1st option: if any is a preferred name then choose that
            foreach($this->ancestor_ranking_preferred as $parent) {
                if(in_array($parent, $parents)) {
                    $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                    return $parent;
                }
            }
        }
        else {
            // don't do THIS if preferred + parents are all inside $this->ancestor_ranking
            $all_inside = true;
            $temp = array_merge($parents, $preferred_terms);
            foreach($temp as $id) {
                if(!in_array($id, $this->ancestor_ranking)) $all_inside = false;
            }
            if(!$all_inside) {
                //THIS:
                foreach($this->ancestor_ranking as $parent) {
                    if(in_array($parent, $preferred_terms)) {
                        $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                        return $parent;
                    }
                }
            }
            if(count($preferred_terms) == 1 && in_array($preferred_terms[0], $this->ancestor_ranking) && in_array($preferred_terms[0], $this->ancestor_ranking_preferred)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $preferred_terms[0]."\n"); fclose($WRITE);
                return $preferred_terms[0];
            }
        }
        
        //2nd option
        $inclusive = array_merge($parents, $preferred_terms);
        foreach($this->ancestor_ranking as $parent) {
            if(in_array($parent, $inclusive)) {
                $WRITE = fopen($this->temp_file, 'a'); fwrite($WRITE, $parent."\n"); fclose($WRITE);
                return $parent;
            }
        }
        
        echo "\nInvestigate parents not included in ranking... weird...\n";
        print_r($inclusive);
        exit("\n===============\n");
    }
    private function get_parent_of_term($term)
    {
        echo "\n--------------------------------------------------------------------------------------------------------------------------------------- \n"."term in question: [$term]:\n";
        /*
        if($parents = @$this->parents_of[$term]) {
            echo "\nParents:\n"; print_r($parents);
        }
        else echo "\nNO PARENT\n";
        */
        if($preferred_terms = @$this->preferred_names_of[$term]) {
            echo "\nThere are preferred term(s):\n";
            print_r($preferred_terms);
            foreach($preferred_terms as $term) {
                echo "\nparent(s) of $term:\n";
                if($parents = @$this->parents_of[$term]) {
                    print_r($parents);
                    $chosen = self::get_rank_most_parent($parents, $preferred_terms);
                    echo "\nCHOSEN PARENT: ".$chosen."\n";
                    return $chosen;
                }
                else echo " -- NO parent";
            }
        }
        else {
            echo "\nThere is NO preferred term\n";
            if($immediate_parents = @$this->parents_of[$term]) {
                echo "\nThere are immediate parent(s) for term in question:\n";
                print_r($immediate_parents);
                $chosen = self::get_rank_most_parent($immediate_parents);
                echo "\nCHOSEN PARENT*: ".$chosen."\n";
                return $chosen;
                // foreach($immediate_parents as $immediate) {
                //     echo "\nparent(s) of $immediate:";
                //     if($parents = @$this->parents_of[$immediate]) {
                //         print_r($parents);
                //     }
                //     else echo " -- NO parent";
                // }
            }
        }
    }
    private function generate_preferred_child_parent_list()
    {
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym']['path'], $this->download_options);
        $file = fopen($temp_file, 'r'); $i = 0;
        $fields = $this->file['preferred synonym']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [preferred] => http://marineregions.org/mrgid/19161
                    [deprecated] => http://marineregions.org/gazetteer.php?p=details&id=19161
                )*/
                $this->preferred_names_of[$rec['deprecated']][] = $rec['preferred'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    private function get_ancestry_of_term($page_id)
    {
        $final = array(); $final2 = array();
        if($parent_ids = @$this->terms_values_child_parent_list[$page_id]) {
            foreach($parent_ids as $temp_id) {
                while(true) {
                    if($parent_ids2 = @$this->terms_values_child_parent_list[$temp_id]) {
                        foreach($parent_ids2 as $temp_id2) {
                            while(true) {
                                if($parent_ids3 = @$this->terms_values_child_parent_list[$temp_id2]) {
                                    foreach($parent_ids3 as $temp_id3) {
                                        $final['L3'][] = $temp_id3;
                                        $final2[$temp_id3] = '';
                                        $temp_id2 = $temp_id3;
                                    }
                                }
                                else break;
                            }
                            $final['L2'][] = $temp_id2;
                            $final2[$temp_id2] = '';
                            $temp_id = $temp_id2;
                        }
                    }
                    else break;
                }
                $final['L1'][] = $temp_id;
                $final2[$temp_id] = '';
                $page_id = $temp_id;
            }
        }
        return array($final, array_keys($final2));
        /*
        $final = array();
        $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->terms_values_child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
        */
    }
    private function generate_terms_values_child_parent_list($file = false)
    {
        if(!$file) exit("\nUndefined file: [$file]\n");
        $temp_file = Functions::save_remote_file_to_local($file, $this->download_options);
        $file = fopen($temp_file, 'r');
        $i = 0;
        $fields = $this->file['parent child']['fields'];
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($line) {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [parent] => ﻿http://purl.obolibrary.org/obo/ENVO_00000111
                    [child] => http://purl.obolibrary.org/obo/ENVO_01000196
                )*/
                $this->parents_of[$rec['child']][] = $rec['parent'];
                $this->children_of[$rec['parent']][] = $rec['child'];
            }
        }
        fclose($file); unlink($temp_file);
    }
    function start_v1()
    {
        self::working_dir();
        $this->child_parent_list = self::generate_child_parent_list();
        // /* tests...
        $predicate = "http://reeffish.org/occursIn";
        $predicate = "http://eol.org/schema/terms/Present";
        $similar_terms = self::given_predicate_get_similar_terms($predicate);
        // print_r($similar_terms); exit;
        
        self::print_taxon_and_ancestry($similar_terms);
        self::given_predicates_get_values_from_traits_csv($similar_terms);
        exit("\n-end tests-\n");
        // */
        if($this->debug) Functions::start_print_debug($this->debug, $this->resource_id);
        // remove temp dir
        /* un-comment in real operation
        recursive_rmdir($this->main_paths['temp_dir']);
        echo ("\n temporary directory removed: " . $this->main_paths['temp_dir']);
        */
    }
    private function generate_child_parent_list()
    {
        $file = fopen($this->main_paths['archive_path'].'/parents.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /* Array(
                    [child] => 47054812
                    [parent] => 7662
                )*/
                $final[$rec['child']] = $rec['parent'];
            }
        }
        fclose($file);
        return $final;
    }
    private function print_taxon_and_ancestry($preds)
    {
        $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Taxa (with ancestry) having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "ancestry"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    ...more fields below
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    $ancestry = self::get_ancestry_using_page_id($rec['page_id']);
                    if(!isset($printed_already[$rec['page_id']])) {
                        fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], implode("|", $ancestry)))."\n");
                        $printed_already[$rec['page_id']] = '';
                    }
                }
            }
        }
        fclose($file);
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
    }
    private function given_predicates_get_values_from_traits_csv($preds)
    {
        $WRITE = fopen($this->report_file, 'a');
        fwrite($WRITE, "Records from traits.csv having data for predicate in question and similar terms: \n\n");
        fwrite($WRITE, implode("\t", array("page_id", "scientific_name", "predicate", "value_uri OR literal"))."\n");
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++;
            if($i == 1) {
                $fields = $line;
                print_r($fields); //exit;
            }
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k];
                    $k++;
                }
                /*Array(
                    [eol_pk] => R96-PK42815719
                    [page_id] => 328076
                    [scientific_name] => <i>Tremarctos ornatus</i>
                    ...more fields below
                )*/
                if(in_array($rec['predicate'], $preds)) {
                    // echo "\n".self::get_value($rec);
                    // print_r($rec); //exit;
                    fwrite($WRITE, implode("\t", array($rec['page_id'], $rec['scientific_name'], $rec['predicate'], self::get_value($rec)))."\n");
                }
            }
        }
        fclose($file);
    }
    private function get_ancestry_using_page_id($page_id)
    {
        $final = array(); $temp_id = $page_id;
        while(true) {
            if($parent_id = @$this->child_parent_list[$temp_id]) {
                $final[] = $parent_id;
                $temp_id = $parent_id;
            }
            else break;
        }
        return $final;
    }
    private function get_value($rec)
    {
        if($val = @$rec['value_uri']) return $val;
        if($val = @$rec['literal']) return $val;
    }
    private function setup_working_dir()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "traits.csv", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        return $paths;
    }
    private function working_dir()
    {
        if(Functions::is_production()) {
            if(!($info = self::setup_working_dir())) return;
            $this->main_paths = $info;
        }
        else { //local development only
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/carnivora_sample',
                          'temp_dir'     => '/Library/WebServer/Documents/eol_php_code/tmp/dir_53125/');
            $this->main_paths = $info;
        }
    }
    private function given_predicate_get_similar_terms($pred) //used during initial report to Jen
    {
        $final = array();
        $final[$pred] = ''; //processed predicate is included
        //from 'parent child':
        $temp_file = Functions::save_remote_file_to_local($this->file['parent child'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[0] == $pred) $final[$line[1]] = '';
        }
        fclose($file); unlink($temp_file);
        //from 'preferred synonym':
        $temp_file = Functions::save_remote_file_to_local($this->file['preferred synonym'], $this->download_options);
        $file = fopen($temp_file, 'r');
        while(($line = fgetcsv($file)) !== FALSE) {
          if($line[1] == $pred) $final[$line[0]] = '';
        }
        fclose($file); unlink($temp_file);
        $final = array_keys($final);
        //start write
        $WRITE = fopen($this->report_file, 'w');
        fwrite($WRITE, "REPORT FOR PREDICATE: $pred\n\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fwrite($WRITE, "Similar terms from [terms relationship files]:\n\n");
        foreach($final as $url) fwrite($WRITE, $url . "\n");
        fwrite($WRITE, "==================================================================================================================================================================\n");
        fclose($WRITE);
        //end write
        return $final;
    }
    private function get_summ_process_type_given_pred($order = "normal") //sheet found here: https://docs.google.com/spreadsheets/u/1/d/1Er57xyxT_-EZud3mNkTBn0fZ9yZi_01qtbwwdDkEsA0/edit?usp=sharing
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1Er57xyxT_-EZud3mNkTBn0fZ9yZi_01qtbwwdDkEsA0';
        $params['range']         = 'predicates!A2:F1000'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) {
            if($uri = $item[0]) {
                if($order == "normal") $final[$uri] = @$item[5];
                elseif($order == "opposite") {
                    if($item5 = @$item[5]) $final[$item5][] = $uri;
                }
            }
        }
        return $final;
    }
    private function investigate_traits_csv()
    {
        $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
        $i = 0;
        while(($line = fgetcsv($file)) !== FALSE) {
            $i++; 
            if($i == 1) $fields = $line;
            else {
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); //exit;
                /*Array(
                    [eol_pk] => R96-PK42724728
                    [page_id] => 328673
                    [scientific_name] => <i>Panthera pardus</i>
                    ...more fields below
                )*/
                // if($rec['target_scientific_name']) print_r($rec);
                // if($rec['lifestage']) print_r($rec);
                if($rec['object_page_id']) print_r($rec);
            }
        }
    }
    
    
    /* not used at the moment
    private function choose_term_type($predicate)
    {
        switch ($predicate) {
            case "http://eol.org/schema/terms/Habitat":
                return 'path_habitat'; //break;
            case "http://eol.org/schema/terms/Present":
                return 'path_geoterms'; //break;
            default:
                exit("\nPredicate [$predicate] not yet assigned to what term_type.\n");
        }
    }
    */

    /* report for Jen
    self::parse_DH();
    $WRITE = fopen($this->report_file, 'w');
    $i = 0;
    foreach(new FileIterator($this->info_path['archive_path'].$this->info_path['tables']['taxa']) as $line_number => $line) {
        $line = explode("\t", $line); $i++;
        if($i == 1) $fields = $line;
        else {
            if(!$line[0]) break;
            $rec = array(); $k = 0;
            foreach($fields as $fld) {
                $rec[$fld] = $line[$k]; $k++;
            }
            // print_r($rec); exit;
            if($page_id = $rec['EOLid']) {
                $ancestry = self::get_ancestry_via_DH($page_id);
                fwrite($WRITE, $page_id . "\t" . implode(" | ", $ancestry) . "\n");
            }
        }
    }
    fclose($WRITE); exit(); exit("\n-end report-\n");
    */
    /* another report for Jen
    self::initialize();
    $i = 0;
    $file = fopen($this->main_paths['archive_path'].'/traits.csv', 'r');
    while(($line = fgetcsv($file)) !== FALSE) {
        $i++; if($i == 1) $fields = $line;
        else {
            $rec = array(); $k = 0;
            foreach($fields as $fld) {
                $rec[$fld] = $line[$k]; $k++;
            }
            // print_r($rec); exit;
            if($page_id = @$rec['object_page_id'])  $final[$page_id] = '';
            if($page_id = @$rec['page_id'])         $final[$page_id] = '';
        }
    }
    $WRITE = fopen($this->report_file, 'w');
    $final = array_keys($final);
    foreach($final as $page_id) {
        $ancestry = self::get_ancestry_via_DH($page_id);
        fwrite($WRITE, $page_id . "\t" . implode(" | ", $ancestry) . "\n");
    }
    fclose($WRITE); exit("\n-end report-\n");
    */

        /*
    Hi Jen, we are now just down to 1 discrepancy. But I think (hopefully) the last one is just something you've missed doing it manually.
    But more importantly, let me share my algorithm how I chose the parent. Please review closely and suggest improvement or even revise if needed.
    I came up with this using our case scenario for page_id 46559197 and your explanations why you chose your parents.
    Like what I said it came down to now just 1 discrepancy.

    I process each of the 12 terms, one by one.
        http://www.marineregions.org/gazetteer.php?p=details&id=australia
        http://www.marineregions.org/gazetteer.php?p=details&id=4366
        http://www.marineregions.org/gazetteer.php?p=details&id=4364
        http://www.geonames.org/2186224
        http://www.geonames.org/3370751
        http://www.marineregions.org/gazetteer.php?p=details&id=1914
        http://www.marineregions.org/gazetteer.php?p=details&id=1904
        http://www.marineregions.org/gazetteer.php?p=details&id=1910
        http://www.marineregions.org/gazetteer.php?p=details&id=4276
        http://www.marineregions.org/gazetteer.php?p=details&id=4365
        http://www.geonames.org/953987
        http://www.marineregions.org/mrgid/1914

    1.  First I get the preferred term(s) of the term in question. 
        Case A: If there are any: e.g. (pref1, pref2) Mostly only 1 preferred term.
            I get the immediate parent(s) each of the preferred terms. 
            e.g. pref1_parent1, pref1_parent2, pref1_parent3

        Case B: If there are NO preferred term(s)
            I get the immediate parent(s) of the term in question.
            e.g. term_parent1, term_parent2

    2.  Then whatever the Case be, I sent the collected items to the ranking selection.
            */
}
?>