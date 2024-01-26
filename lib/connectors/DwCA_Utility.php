<?php
namespace php_active_record;
/* connector: [dwca_utility.php]
Processes any DwCA archive file.
Using the parentNameUsageID, generates a new DwCA with a new taxon column: http://rs.tdwg.org/dwc/terms/higherClassification
User Warning: Undefined property `rights` on eol_schema\Taxon as defined by `http://rs.tdwg.org/dwc/xsd/tdwg_dwcterms.xsd` in /opt/homebrew/var/www/eol_php_code/vendor/eol_content_schema_v2/DarwinCoreExtensionBase.php on line 168
*/
class DwCA_Utility
{
    function __construct($folder = NULL, $dwca_file = NULL, $params = array())
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array("directory_path" => $this->path_to_archive_directory));
        }
        $this->params = $params; //print_r($params); exit;
        $this->dwca_file = $dwca_file;
        /* un-comment if it will cause probs to other connectors
        $this->download_options = array("download_wait_time" => 2000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26);
        $this->download_options["expire_seconds"] = false; //debug - false means it will use cache
        */
        $this->debug = array();
        
        /* Please take note of some Meta XML entries have upper and lower case differences */
        $this->extensions = array("http://rs.gbif.org/terms/1.0/vernacularname"     => "vernacular",
                                  "http://rs.tdwg.org/dwc/terms/occurrence"         => "occurrence",
                                  "http://rs.tdwg.org/dwc/terms/measurementorfact"  => "measurementorfact",
                                  "http://rs.tdwg.org/dwc/terms/taxon"              => "taxon",
                                  "http://eol.org/schema/media/document"            => "document",
                                  "http://rs.gbif.org/terms/1.0/reference"          => "reference",
                                  "http://eol.org/schema/agent/agent"               => "agent",

                                  //start of other row_types: check for NOTICES or WARNINGS, add here those undefined URIs
                                  "http://rs.gbif.org/terms/1.0/description"        => "document",
                                  "http://rs.gbif.org/terms/1.0/multimedia"         => "document",
                                  "http://eol.org/schema/reference/reference"       => "reference"
                                  );

                                  /*
                                  [1] => http://rs.gbif.org/terms/1.0/speciesprofile
                                  [6] => http://rs.gbif.org/terms/1.0/typesandspecimen
                                  [7] => http://rs.gbif.org/terms/1.0/distribution
                                  "http://eol.org/schema/association"               => "association"  --> unsuccessful, ContentArchiveBuilder.php has to be updated.
                                  */
    
        if(@$this->resource_id == 24) {
            $this->taxon_ids = array();
        }
        
        $this->public_domains = array("http://creativecommons.org/licenses/publicdomain/", "https://creativecommons.org/share-your-work/public-domain/", "https://creativecommons.org/share-your-work/public-domain/cc0/");
    }

    private function start($dwca_file = false, $download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1)) //probably default expires in 1 day 60*60*24*1. Not false.
    {
        if($dwca_file) $this->dwca_file = $dwca_file; //used by /conncectors/lifedesk_eol_export.php
        
        // /* un-comment in real operation
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", $download_options); //true 'expire_seconds' means it will re-download, will NOT use cache. Set TRUE when developing
        // print_r($paths); exit("\n-exit muna-\n");
        // */

        /* development only
        $paths = Array(
            'archive_path' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_99493/',
            'temp_dir' => '/Volumes/AKiTiO4/eol_php_code_tmp/dir_99493/'
        );
        */
        
        $this->archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];
        $harvester = new ContentArchiveReader(NULL, $this->archive_path);
        $tables = $harvester->tables;
        $index = array_keys($tables);
        if(!($tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) { // take note the index key is all lower case
            debug("Invalid archive file. Program will terminate.");
            return false;
        }
        return array("harvester" => $harvester, "temp_dir" => $temp_dir, "tables" => $tables, "index" => $index);
    }    
    function convert_archive($preferred_rowtypes = false, $excluded_rowtypes = false) //same as convert_archive_by_adding_higherClassification(); just doesn't generate higherClassification
    {   /* param $preferred_rowtypes is the option to include-only those row_types you want on your final DwCA. 1st client was DATA-1770 */
        require_library('connectors/RemoveHTMLTagsAPI');
        echo "\nConverting archive to EOL DwCA...\n";
        //placeholder for customized resources with respective download_options
        
        // /* from Smithsonian Contribution to ???
        if(stripos($this->resource_id, "SCtZ-") !== false)      $annotateYes = true;
        elseif(stripos($this->resource_id, "scb-") !== false)   $annotateYes = true;
        elseif(stripos($this->resource_id, "scz-") !== false)   $annotateYes = true;
        else                                                    $annotateYes = false;
        // */
        
        if(Functions::is_production()) echo "\nProduction running...\n";
        else                           echo "\nLocal running...\n";
        
        if(in_array($this->resource_id, array("170_final", "BF", "cites_taxa", "727"))) $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 60*60*24*30)); //1 month expire
        elseif(in_array($this->resource_id, array("wikimedia_comnames", "71_new", "368_removed_aves", "itis_2019-08-28", "itis_2020-07-28", 
            "itis_2020-12-01", "itis_2022-02-28_all_nodes", "368_final"))) $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0)); //expires now
        elseif(in_array($this->resource_id, array("wiki_en_report"))) $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0)); //expires now
        elseif(in_array($this->resource_id, array("globi_associations", "globi_associations_final", "final_SC_unitedstates"))) $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0)); //expires now
        elseif(in_array($this->resource_id, array("xxx")))                                $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 60*60*24*1)); //expires 1 day
        elseif(in_array($this->resource_id, array("gbif_classification", "gbif_classification_without_ancestry", "gbif_classification_final", 
                                                  "26", "368_removed_aves", "617_ENV", "wikipedia_en_traits_FTG", "wikipedia_en_traits_tmp1", "wikipedia_en_traits_tmp2", "wikipedia_en_traits", 
                                                  "10088_5097_ENV", "10088_6943_ENV", 
                                                  "118935_ENV", "120081_ENV", "120082_ENV", "118986_ENV", "118920_ENV", "120083_ENV", "118237_ENV",
                                    "MoftheAES_ENV", "30355_ENV", "27822_ENV", "30354_ENV", "119035_ENV", "118946_ENV", "118936_ENV", "118950_ENV", 
                                    "120602_ENV", "119187_ENV", "118978_ENV", "118941_ENV", "119520_ENV", "119188_ENV",
                                    "15423_ENV", "91155_ENV")) || @$this->params['resource'] == 'all_BHL') {
            if(Functions::is_production()) $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0)); //expires now
            else                           $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 60*60*1)); //1 hour expire
        }
        elseif(substr($this->resource_id,0,3) == 'SC_' || substr($this->resource_id,0,2) == 'c_') $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 60*60*24*1)); //1 day expire
        elseif(stripos($this->resource_id, "_meta_recoded") !== false) $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0)); //0 orig expires now | during dev false
        elseif($annotateYes) $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0)); //expires now
        else {
            // $info = self::start(); //default doesn't expire. Your call. -- orig row
            if(Functions::is_production()) $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0)); //expires now
            else                           $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0)); //60*60*1 - 1 hour expire
        }

        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];
        /* e.g. $index -> these are the row_types
        Array
            [0] => http://rs.tdwg.org/dwc/terms/taxon
            [1] => http://rs.gbif.org/terms/1.0/vernacularname
            [2] => http://rs.tdwg.org/dwc/terms/occurrence
            [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
        */
        // print_r($index); exit; //good debug to see the all-lower case URIs
        foreach($index as $row_type) {
            /* ----------customized start------------ */
            /* used already - obsolete
            if(substr($this->resource_id,0,3) == 'SC_') {
                if($this->resource_id == 'SC_australia') {}
                else break; //all extensions will be processed elsewhere. Bec. meta.xml does not reflect actual extension details. DwCA seems hand-created.
            }
            */
            // if(stripos($this->resource_id, "_fxMoFchild") !== false) break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == 'wikipedia_en_traits_tmp4') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == '26_MoF_normalized') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == '26_delta') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == '26_delta_new') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if(stripos($this->resource_id, "_cleaned_MoF_habitat") !== false) break; //all extensions will be processed elsewhere. debug only, during dev only //string is found
            // if($this->resource_id == 'inat_images_3Mcap') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == 'inat_images_100cap') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == '368_cleaned_MoF') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->params['resource'] == "Deltas_4hashing") break; //all extensions will be processed elsewhere. debug only
            // if(in_array($this->resource_id, array('parent_BV_consolid8', 'TS_consolid8'))) break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == 'globi_associations') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if(stripos($this->resource_id, "_meta_recoded") !== false) break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == '26_ENV_final') break; //all extensions will be processed elsewhere. debug only, during dev only
            // if($this->resource_id == '20_ENV_final') break; //all extensions will be processed elsewhere. debug only, during dev only
            
            /* not used
            if($this->resource_id == 'globi_associations_refuted') break; //all extensions will be processed elsewhere IN real operation.
            */
                if(in_array($this->resource_id, array("368_removed_aves", "wiki_en_report"))) break; //all extensions will be processed elsewhere.
            elseif(in_array($this->resource_id, array("BF", "gbif_classification", "gbif_classification_without_ancestry", "gbif_classification_final", 
                                                      "708", "Brazilian_Flora_with_canonical"))) break; //all extensions will be processed elsewhere.
            /* ----------customized end-------------- */
            if($preferred_rowtypes) {
                if(!in_array($row_type, $preferred_rowtypes)) continue;
            }
            if($excluded_rowtypes) { //1st client is environments_2_eol.php -> apply_old_formats_filters()
                if(in_array($row_type, $excluded_rowtypes)) continue;
            }
            if(@$this->extensions[$row_type]) { //process only defined row_types
                // if(@$this->extensions[$row_type] == 'document') continue; //debug only
                echo "\nprocessing...DwCA_Utility...x: [$row_type]: ".@$this->extensions[$row_type]."...\n";
                
                // /* customized
                if((in_array($this->resource_id, array("10088_5097_ENV", "10088_6943_ENV", "118935_ENV", "120081_ENV", "120082_ENV", "118986_ENV", 
                    "118920_ENV", "120083_ENV", 
                    "118237_ENV", "MoftheAES_ENV", "30355_ENV", "27822_ENV", "30354_ENV", "119035_ENV", "118946_ENV", "118936_ENV", "118950_ENV", 
                    "120602_ENV", "119187_ENV", "118978_ENV", "118941_ENV", "119520_ENV", "119188_ENV",
                    "15423_ENV", "91155_ENV")) || @$this->params['resource'] == 'all_BHL') && $row_type == "http://rs.tdwg.org/dwc/terms/occurrence") {
                    self::process_fields($harvester->process_row_type($row_type), 'occurrence_specific');
                }
                elseif($annotateYes && $row_type == "http://rs.tdwg.org/dwc/terms/occurrence") {
                    self::process_fields($harvester->process_row_type($row_type), 'occurrence_specific');
                }
                else self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]); //original, the rest goes here
                // */
                
            }
            else echo "\nun-processed:A [$row_type]: ".@$this->extensions[$row_type]."\n";
        }
        
        // /* ================================= start of customization =================================
        if($this->resource_id == "24") {
            require_library('connectors/AntWebDataAPI');
            $func = new AntWebDataAPI($this->taxon_ids, $this->archive_builder, 24);
            $func->start($harvester, 'http://rs.tdwg.org/dwc/terms/taxon');
        }
        if($this->resource_id == 'globi_associations') {
            require_library('connectors/Globi_Refuted_Records');
            require_library('connectors/GloBIDataAPI');
            require_library('connectors/RemoveHTMLTagsAPI');
            $func = new GloBIDataAPI($this->archive_builder, 'globi');
            $func->start($info); //didn't use like above bec. memory can't handle 'occurrence' and 'association' TSV files
        }
        /* not used
        if($this->resource_id == 'globi_associations_refuted') {
            require_library('connectors/GloBIRefutedRecords');
            $func = new GloBIRefutedRecords($this->archive_builder, 'none');
            $func->start($info); //generates report for DATA-1854
        }
        */
        if($this->resource_id == 'globi_associations_final') {
            require_library('connectors/DWCA_Associations_Fix');
            $func = new DWCA_Associations_Fix($this->archive_builder, $this->resource_id);
            $func->start($info);
        }

        // if($this->resource_id == 'globi_associations_tmp1') { //working OK but was changed to below: more maintainable moving forward
        if(@$this->params['resource'] == 'remove_unused_references') { //1st client GloBI
            require_library('connectors/ResourceUtility');
            $func = new ResourceUtility($this->archive_builder, $this->resource_id);
            $func->remove_unused_references($info, $this->params['resource_name']);
        }
        if(@$this->params['resource'] == 'remove_unused_occurrences') { //1st client GloBI
            require_library('connectors/ResourceUtility');
            $func = new ResourceUtility($this->archive_builder, $this->resource_id);
            $func->remove_unused_occurrences($info, $this->params['resource_name']);
        }

        if(@$this->params['resource'] == 'remove_MoF_for_taxonID') { //1st client TRY database
            require_library('connectors/ResourceUtility');
            $func = new ResourceUtility($this->archive_builder, $this->resource_id);
            $func->remove_MoF_for_taxonID($info, $this->params['resource_name']);
        }

        if(@$this->params['resource'] == 'move_MoF_col_2childMoF') { //1st client TRY database
            require_library('connectors/Move_col_inMoF_2child_inMoF_API');
            $func = new Move_col_inMoF_2child_inMoF_API($this->archive_builder, $this->resource_id);
            $func->start($info, $this->params['resource_name']);
        }

        if(in_array($this->resource_id, array('final_SC_unitedstates')) || @$this->params['resource'] == 'MoF_normalized') {
            require_library('connectors/DWCA_Measurements_Fix');
            $func = new DWCA_Measurements_Fix($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        
        /* this has been run already. Other connector(s) are created for further adjustments on DwCA's. e.g. DATA-1841
        if(substr($this->resource_id,0,3) == 'SC_') {
            if($this->resource_id == 'SC_australia') { //customized for DATA-1833
                require_library('connectors/SC_Australia2019');
                $func = new SC_Australia2019($this->archive_builder, $this->resource_id);
                $func->start($info);
            }
            else { //regular func called from original task
                require_library('connectors/SpeciesChecklistAPI');
                $func = new SpeciesChecklistAPI($this->archive_builder, $this->resource_id);
                $func->start($info);
            }
        }
        */
        if(substr($this->resource_id,0,3) == 'SC_' || substr($this->resource_id,0,2) == 'c_') { //for DATA-1841 terms remapping. "c_" resources (3) came from DATA-1840.
            require_library('connectors/SpeciesChecklistAPI');
            $func = new SpeciesChecklistAPI($this->archive_builder, $this->resource_id);
            $func->start_terms_remap($info, $this->resource_id);
        }
        if($this->resource_id == '727') {
            require_library('connectors/USDAPlants2019');
            $func = new USDAPlants2019($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if($this->resource_id == '26') {
            echo "\nGoes here: [WoRMS_post_process]\n";
            require_library('connectors/WoRMS_post_process');
            $func = new WoRMS_post_process($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if($this->resource_id == '707') {
            require_library('connectors/BirdsADW_Data');
            $func = new BirdsADW_Data($this->archive_builder, $this->resource_id);
            $func->start($info);
        }

        if($this->resource_id == '20_ENV_final') { //1st client of AddTrait2EoLDwCA
            require_library('connectors/AddTrait2EoLDwCA');
            $func = new AddTrait2EoLDwCA($this->archive_builder, $this->resource_id);
            $func->start($info);
        }

        if(in_array($this->resource_id, array("parent_basal_values_Carnivora", "parent_basal_values"))) {
            require_library('connectors/SDRreportLib');
            $func = new SDRreportLib($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if($this->resource_id == '368_removed_aves') {
            require_library('connectors/RemoveAvesChildrenAPI');
            $func = new RemoveAvesChildrenAPI($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if(in_array($this->resource_id, array("itis_2019-08-28", "itis_2020-07-28", "itis_2020-12-01", "itis_2022-02-28_all_nodes", "368_final"))) { //all resources which undergo SynonymsHandling
            require_library('connectors/SynonymsHandlingAPI');
            $func = new SynonymsHandlingAPI($this->archive_builder, $this->resource_id);
            $func->synonym_updates($info);
        }
        if($this->resource_id == '233') {
            require_library('connectors/MediaConvertAPI');
            $func = new MediaConvertAPI($this->archive_builder, $this->resource_id);
            $func->start_233($info);
        }
        if($this->resource_id == '170_final') { //resource with .mp4 files
            require_library('connectors/MovieFilesAPI');
            $func = new MovieFilesAPI($this->archive_builder, $this->resource_id);
            $func->update_dwca($info);
        }
        if($this->resource_id == 'BF') {
            require_library('connectors/BrazilianFloraAPI');
            $func = new BrazilianFloraAPI($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if($this->resource_id == 'gbif_classification') {
            require_library('connectors/GBIF_classificationAPI_v2');
            $func = new GBIF_classificationAPI_v2($this->resource_id, $this->archive_builder);
            $func->fix_remaining_conflicts($info);
        }
        if($this->resource_id == 'gbif_classification_without_ancestry') {
            require_library('connectors/GBIF_classificationAPI_v2');
            $func = new GBIF_classificationAPI_v2($this->resource_id, $this->archive_builder);
            $func->create_dwca_without_ancestry($info);
        }
        if($this->resource_id == 'gbif_classification_final') {
            require_library('connectors/RemoveSurrogatesGBIF');
            $func = new RemoveSurrogatesGBIF($this->resource_id, $this->archive_builder);
            $func->remove_surrogates_from_GBIF($info);
        }
        
        // if(stripos($this->resource_id, "SCtZ-") !== false)      $annotateYes = true;
        // elseif(stripos($this->resource_id, "scb-") !== false)  $annotateYes = true;
        // elseif(stripos($this->resource_id, "scz-") !== false)  $annotateYes = true;
        // else                                                    $annotateYes = false;
        
        /* === Edit for new resources === */
        if(in_array($this->resource_id, array("21_ENV", "617_ENV", "26_ENV", "10088_5097_ENV", "10088_6943_ENV", "118935_ENV", "120081_ENV", 
            "120082_ENV", "118986_ENV", "118920_ENV", "120083_ENV", 
            "118237_ENV", "MoftheAES_ENV", "30355_ENV", "27822_ENV", "30354_ENV", "119035_ENV", "118946_ENV", "118936_ENV", "118950_ENV", 
            "120602_ENV", "119187_ENV", "118978_ENV", "118941_ENV", "119520_ENV", "119188_ENV",
            "15423_ENV", "91155_ENV", "20_ENV")) || $annotateYes || in_array(@$this->params['resource'], array('all_BHL', 'Pensoft_journals')))
        { //first 2 clients: Amphibiaweb, Wikipedia EN. Then all_BHL and Pensoft_journals...
            echo "\nGoes here really: [$this->resource_id]\n";
            require_library('connectors/ContributorsMapAPI');
            require_library('connectors/Environments2EOLfinal');
            $func = new Environments2EOLfinal($this->archive_builder, $this->resource_id, $this->params);
            $func->start($info);
        }
        /*
        if(in_array($this->resource_id, array("21_ENVO", "617_ENVO"))) { exit("\nOBSOLETE: Vangelis path\n");
            require_library('connectors/EnvironmentsFilters');
            $func = new EnvironmentsFilters($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        */
        if($this->resource_id == '26_ENV_final') {
            require_library('connectors/Change_measurementIDs');
            $func = new Change_measurementIDs($this->resource_id, $this->archive_builder);
            $func->start($info);
        }

        if(in_array($this->resource_id, array("Polytraits"))) {
            require_library('connectors/ContributorsMapAPI');
            require_library('connectors/PolytraitsAPI');
            $func = new PolytraitsAPI($this->archive_builder, $this->resource_id);
            $func->start($info);
        }

        if(in_array($this->resource_id, array("708", "21_final", "617_final"))) {
            require_library('connectors/New_EnvironmentsEOLDataConnector');
            $func = new New_EnvironmentsEOLDataConnector($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        
        // /* remove all records for taxon with habitat value(s) that are descendants of both marine and terrestrial
        if((stripos($this->resource_id, "_cleaned_MoF_habitat") !== false) ||                //string is found
           (in_array($this->resource_id, array('wikipedia_en_traits_tmp3', '26_delta_new')))
          ) {
            require_library('connectors/Clean_MoF_Habitat_API');
            $func = new Clean_MoF_Habitat_API($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        // */
        
        // /*
        if(in_array($this->resource_id, array('TreatmentBank_adjustment_02'))) {
            require_library('connectors/CladeSpecificFilters4Habitats_API');
            $func = new CladeSpecificFilters4Habitats_API($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if(in_array($this->resource_id, array('TreatmentBank_adjustment_03'))) {
            require_library('connectors/GeorgiaCntry_vs_StateAPI');
            $func = new GeorgiaCntry_vs_StateAPI($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        // */
        
        // /* ====================== parts of a whole: will run one after the other ======================
        if(in_array($this->resource_id, array("wikipedia_en_traits_FTG"))) { //calls FTG library
            require_library('connectors/FilterTermGroupByTaxa');
            $func = new FilterTermGroupByTaxa($this->archive_builder, $this->resource_id, $this->params);
            $func->start($info);
        }
        if(in_array($this->resource_id, array("wikipedia_en_traits_tmp1"))) { //calls a generic utility
            require_library('connectors/ResourceUtility');
            $func = new ResourceUtility($this->archive_builder, $this->resource_id);
            $func->remove_taxa_without_MoF($info);
        }
        // if(in_array($this->resource_id, array("wikipedia_en_traits"))) {     //old
        if(in_array($this->resource_id, array("wikipedia_en_traits_tmp2"))) {        //new
            require_library('connectors/ResourceUtility');
            $func = new ResourceUtility($this->archive_builder, $this->resource_id);
            $func->remove_contradicting_traits_fromMoF($info);
        }
        // ====================== end: parts of a whole ====================== */
        
        if(in_array($this->resource_id, array("WoRMS2EoL_zip"))) { //calls a generic utility
            require_library('connectors/ResourceUtility');
            $func = new ResourceUtility($this->archive_builder, $this->resource_id);
            $func->gen_canonical_list_from_taxa($info);
            $func->add_canonical_in_taxa($info);
        }
        if($this->resource_id == 'wiki_en_report') { //just a report, not creating a resource
            require_library('connectors/ResourceUtility');
            $func = new ResourceUtility($this->archive_builder, $this->resource_id);
            $func->report_4_Wikipedia_EN_traits($info);
            exit("\n-stop munax-\n");
        }
        if(in_array($this->resource_id, array("368_merged_MoF"))) {
            require_library('connectors/MergeMoFrecordsAPI');
            $func = new MergeMoFrecordsAPI($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if(in_array($this->resource_id, array("368_cleaned_MoF"))) {
            require_library('connectors/Remove_MoF_recordsAPI');
            $func = new Remove_MoF_recordsAPI($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if(stripos($this->resource_id, "_meta_recoded") !== false) {
            require_library('connectors/MetaRecodingAPI');
            $func = new MetaRecodingAPI($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if(stripos($this->resource_id, "_fxMoFchild") !== false) {
            require_library('connectors/FixMoFChildRecordsAPI');
            $func = new FixMoFChildRecordsAPI($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        
        if(in_array($this->resource_id, array("parent_BV_consolid8", "TS_consolid8", "parent_TS_consolid8"))) {
            require_library('connectors/SDR_Consolid8API');
            $func = new SDR_Consolid8API($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if($this->resource_id == "wikidata-hierarchy-final" || @$this->params['resource'] == "fillup_missing_parents") {
            require_library('connectors/FillUpMissingParentsAPI');
            $func = new FillUpMissingParentsAPI($this->archive_builder, $this->resource_id, $this->archive_path);
            $func->start($info);
            /* for testing
            $sciname = "Sarracenia flava 'Maxima'";
            //$sciname = "Adenanthos cuneatus ‘Flat Out’";
            $sciname = "Gadus morhua Linneaus 1972";
            // $canonical = $func->add_cannocial_using_gnparser($sciname, 'series');
            $canonical = $func->add_cannocial_using_gnparser($sciname, 'genus');
            exit("\n[$sciname] [$canonical]\n");
            */
        }
        
        if(@$this->params['resource'] == "Deltas_4hashing") {
            require_library('connectors/DeltasHashIDsAPI');
            $func = new DeltasHashIDsAPI($this->archive_builder, $this->resource_id, $this->archive_path);
            $func->start($info);
        }
        if(in_array($this->resource_id, array('inat_images_100cap', 'inat_images_3Mcap', 'inat_images_3Mcap_2', 'inat_images_3Mcap_3'))) {
            require_library('connectors/iNatImagesSelectAPI');
            $func = new iNatImagesSelectAPI($this->archive_builder, $this->resource_id, $this->archive_path, $this->params);
            $func->start($info);
        }
        if(in_array($this->resource_id, array("wikidata_hierarchy"))) { //1st client is: wikidata_hierarchy
            require_library('connectors/DwCA_AssignEOLidAPI');
            $func = new DwCA_AssignEOLidAPI($this->archive_builder, $this->resource_id, $this->archive_path);
            $func->start($info);
        }
        if(in_array($this->resource_id, array("Brazilian_Flora_with_canonical"))) { //1st client is: Brazilian_Flora_with_canonical
            require_library('connectors/DwCA_RunGNParser');
            $func = new DwCA_RunGNParser($this->archive_builder, $this->resource_id, $this->archive_path);
            $func->start($info);
        }
        
        if(in_array($this->resource_id, array("TreatmentBank_adjustment_01"))) { //calls a generic utility
            require_library('connectors/DwCA_Rem_Taxa_Adjust_MoF_API');
            $func = new DwCA_Rem_Taxa_Adjust_MoF_API($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        if(in_array($this->resource_id, array("Brazilian_Flora"))) { //calls a generic utility
            require_library('connectors/Mov_TaxaRef_2MOF_API');
            $func = new Mov_TaxaRef_2MOF_API($this->archive_builder, $this->resource_id);
            $func->start($info);
        }
        
        // ================================= end of customization ================================= */ 
        
        $this->archive_builder->finalize(TRUE);
        // /* un-comment in real operation -- remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        // */
        if($this->debug) print_r($this->debug);
    }
    function convert_archive_files($lifedesks) //used by: connectors/lifedesk_eol_export.php
    {
        require_library('connectors/RemoveHTMLTagsAPI');
        foreach($lifedesks as $ld) //e.g. $ld = "LD_afrotropicalbirds" or "LD_afrotropicalbirds_multimedia"
        {
            $dwca_file = CONTENT_RESOURCE_LOCAL_PATH.$ld.".tar.gz";
            // $dwca_file = "http://localhost/eol_php_code/applications/content_server/resources/".$ld.".tar.gz";
            echo "\nConverting multiple DwCA files [$ld] into one final DwCA...\n";
            $info = self::start($dwca_file);
            $temp_dir = $info['temp_dir'];
            $harvester = $info['harvester'];
            $tables = $info['tables'];
            $index = $info['index'];
            /*
            Array
                [0] => http://rs.tdwg.org/dwc/terms/taxon
                [1] => http://rs.gbif.org/terms/1.0/vernacularname
                [2] => http://rs.tdwg.org/dwc/terms/occurrence
                [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
            */
            foreach($index as $row_type) {
                if(@$this->extensions[$row_type]) { //process only defined row_types
                    // if(@$this->extensions[$row_type] == 'document') continue; //debug only
                    echo "\nprocessed: [$ld][$row_type]: ".@$this->extensions[$row_type]."\n";

                    /* good debug; debug only
                    if($ld == "LD_afrotropicalbirds") {
                        if($row_type == "http://rs.tdwg.org/dwc/terms/taxon") {
                            print_r($harvester->process_row_type($row_type));
                            // exit;
                        }
                    }
                    */
                    
                    self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
                }
                else echo "\nun-processed:B [$row_type]: ".@$this->extensions[$row_type]."\n";
            }
            // remove temp dir
            recursive_rmdir($temp_dir); echo ("\n temporary directory removed: " . $temp_dir);
        } //end foreach()

        $this->archive_builder->finalize(TRUE);
        // if($this->debug) print_r($this->debug); //to limit lines of output
    }
    
    function convert_archive_by_adding_higherClassification()
    {
        require_library('connectors/RemoveHTMLTagsAPI');
        echo "\ndoing this: convert_archive_by_adding_higherClassification()\n";
        $info = self::start();
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');
        if(self::can_compute_higherClassification($records)) {
            echo "\n1 of 3\n";  self::build_id_name_array($records);
            echo "\n2 of 3\n";  $records = self::generate_higherClassification_field($records);
            /*
            Array
                [0] => http://rs.tdwg.org/dwc/terms/taxon
                [1] => http://rs.gbif.org/terms/1.0/vernacularname
                [2] => http://rs.tdwg.org/dwc/terms/occurrence
                [3] => http://rs.tdwg.org/dwc/terms/measurementorfact
            */
            echo "\n3 of 3\n";
            foreach($index as $row_type) {
                if(@$this->extensions[$row_type]) { //process only defined row_types
                    if($this->extensions[$row_type] == "taxon") self::process_fields($records, $this->extensions[$row_type]);
                    else                                        self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
                }
            }
            $this->archive_builder->finalize(TRUE);
        }
        else echo "\nCannot compute higherClassification.\n";
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }

    function convert_archive_normalized() //this same as above two, but this removes taxa that don't have objects. Only taxa with objects will remain in taxon.tab.
    {
        require_library('connectors/RemoveHTMLTagsAPI');
        echo "\ndoing this: convert_archive_normalized()\n";
        $info = self::start(false, array("timeout" => 172800, 'expire_seconds' => 0));
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        if($records = $harvester->process_row_type("http://eol.org/schema/media/Document"))
        {
            $taxon_ids_with_objects = self::build_taxonIDs_with_objects_array($records);        echo "\n1 of 3\n";
            $records = $harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon');      echo "\n2 of 3\n";
            $records = self::remove_taxa_without_objects($records, $taxon_ids_with_objects);    echo "\n3 of 3\n";
            foreach($index as $row_type) {
                if(@$this->extensions[$row_type]) { //process only defined row_types
                    if($this->extensions[$row_type] == "taxon") self::process_fields($records, $this->extensions[$row_type]);
                    else                                        self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
                }
            }
            $this->archive_builder->finalize(TRUE);
        }
        else {
            echo "\nNo data objects for this resource [$this->resource_id].\n";
            recursive_rmdir($this->path_to_archive_directory);
        }
        
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        if($this->debug) print_r($this->debug);
    }
    
    //next 2 private functions are for convert_archive_customize_tab()
    private function can_customize($record, $fields)
    {
        foreach($fields as $field) {
            if(!isset($record[$field])) return false;
        }
        return true;
    }
    private function customize_tab($records, $jira, $rowtype = "")
    {
        //------------------------------------------------------------customization start
        if($jira == "DATA-1779") {}
        //------------------------------------------------------------customization end
        echo "\n start taxa count: ".count($records);
        $i = -1;
        foreach($records as $rec) {
            $i++;
            // print_r($rec); exit;
            /*Array( e.g. a media extension
                [http://purl.org/dc/terms/identifier] => 3603194
                [http://rs.tdwg.org/dwc/terms/taxonID] => dc35ea52861f3d5a5be14a4bdd2832c3
                [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/StillImage
                [http://rs.tdwg.org/audubon_core/subtype] => 
                [http://purl.org/dc/terms/format] => image/jpeg
                [http://purl.org/dc/terms/description] => This image revealed the presence of both the <i>human T-cell leukemia type-1 virus</i> (HTLV-1), (also known as the <i>human T lymphotropic virus type-1 virus</i>), and the <i>human immunodeficiency virus</i> (HIV).<br>Created:
                [http://rs.tdwg.org/ac/terms/accessURI] => https://editors.eol.org/other_files/EOL_media/94/3603194.jpg
                [http://rs.tdwg.org/ac/terms/furtherInformationURL] => http://phil.cdc.gov/phil/home.asp
                [http://purl.org/dc/terms/language] => en
                [http://ns.adobe.com/xap/1.0/Rating] => 2.5
                [http://purl.org/dc/terms/audience] => Expert users; General public
                [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/publicdomain/
                [http://purl.org/dc/terms/rights] => <B>None</b> - This image is in the public domain and thus free of any copyright restrictions. As a matter of courtesy we request that the content provider be credited and notified in any public or private usage of this image.
                [http://ns.adobe.com/xap/1.0/rights/Owner] => Public Health Image Library
                [http://eol.org/schema/agent/agentID] => 33b5e131211fb3858b3ddf9a6e1c605a
            )*/
            if($jira == "DATA-1779") { //if license is 'public domain', make 'Owner' field blank.
                $license = (string) $rec["http://ns.adobe.com/xap/1.0/rights/UsageTerms"];
                if(in_array($license, $this->public_domains)) {
                    // echo "\nfound criteria [".$records[$i]['http://ns.adobe.com/xap/1.0/rights/Owner']."]";
                    $records[$i]['http://ns.adobe.com/xap/1.0/rights/Owner'] = "";
                    // print_r($records[$i]); exit;
                }
            }
            if($jira == "DATA-1799") { //remove taxon entry when taxonID is missing
                                       //remove media entry when taxonID is missing
                $taxonID = (string) trim($rec["http://rs.tdwg.org/dwc/terms/taxonID"]);
                if(!$taxonID) {
                    /* a diff. case when you want to delete the entire record altogether
                    $records[$i] = NULL;
                    */
                    if($rowtype == "http://rs.tdwg.org/dwc/terms/Taxon") {
                        $this->taxonID_to_use = md5(json_encode($rec))."_eolx";
                        $records[$i]['http://rs.tdwg.org/dwc/terms/taxonID'] = $this->taxonID_to_use;
                    }
                    elseif($rowtype == "http://eol.org/schema/media/Document") {
                        $records[$i]['http://rs.tdwg.org/dwc/terms/taxonID'] = $this->taxonID_to_use;
                    }
                }
            }
            
        }
        $records = array_filter($records); //remove null arrays
        $records = array_values($records); //reindex key
        echo "\n end taxa count: ".count($records);
        return $records;
    }
    function convert_archive_customize_tab($options) //first clients are DATA-1779, DATA-1799. This will customize DwCA extension(s).
    {
        require_library('connectors/RemoveHTMLTagsAPI');
        echo "\ndoing this: convert_archive_customize_tab()\n";
        $info = self::start(); $temp_dir = $info['temp_dir']; $harvester = $info['harvester']; $tables = $info['tables']; $index = $info['index'];
        
        foreach($options['row_types'] as $rowtype) { //process here those extensions that need customization
            $records = $harvester->process_row_type($rowtype);
            $records = self::customize_tab($records, $options['Jira'], $rowtype);
            self::process_fields($records, $this->extensions[strtolower($rowtype)]);
        }

        $options['row_types'] = array_map('strtolower', $options['row_types']); //important step so to have similar lower-case strings
        /* print_r($index); print_r($options['row_types']); exit; */ //check if strings have same case, before to proceed with comparing.

        foreach($index as $row_type) { //process remaining row_types
            if(@$this->extensions[$row_type]) { //process only defined row_types
                if(!in_array($row_type, $options['row_types'])) self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type]);
            }
        }
        $this->archive_builder->finalize(TRUE);
        
        recursive_rmdir($temp_dir); echo ("\n temporary directory removed: " . $temp_dir); // remove temp dir
        if($this->debug) print_r($this->debug);
    }

    private function process_fields($records, $class, $generateArchive = true)
    {   //echo "\nProcessing: $class [".count($records)."]...\n"; //good debug - check if some extensions have records
        //start used in validation
        $do_ids = array();
        $taxon_ids = array();
        $ref_ids = array();
        //end used in validation
        $count = 0;
        foreach($records as $rec)
        {
            // if($count >= 10) break; //debug only
            $count++;
            if    ($class == "vernacular")  $c = new \eol_schema\VernacularName();
            elseif($class == "agent")       $c = new \eol_schema\Agent();
            elseif($class == "reference")   $c = new \eol_schema\Reference();
            elseif($class == "taxon")       $c = new \eol_schema\Taxon();
            elseif($class == "document")    $c = new \eol_schema\MediaResource();
            elseif($class == "occurrence")  $c = new \eol_schema\Occurrence();
            elseif($class == "occurrence_specific")  $c = new \eol_schema\Occurrence_specific(); //1st client is 10088_5097_ENV
            elseif($class == "measurementorfact")   $c = new \eol_schema\MeasurementOrFact();
            else exit("\nUndefined class [$class]. Will terminate.\n");
            
            if($this->resource_id == 'parent_basal_values_Carnivora') { //this actually works. But only goes here during dev. if needed, since MoF is customized in /lib/SDRreportLib.php in real operation
                if($class == "measurementorfact") $c = new \eol_schema\MeasurementOrFact_specific();
            }

            if($this->resource_id == '20_ENV_final') {
                if($class == "measurementorfact") $c = new \eol_schema\MeasurementOrFact_specific();
                if($class == "occurrence") $c = new \eol_schema\Occurrence_specific();
            }

            // if($class == "taxon") print_r($rec);
            
            $keys = array_keys($rec);
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];

                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                //#################### start some validations ---------------------------- put other validations in this block, as needed ################################################
                if($class == "reference") {
                    if($field == "full_reference" && !@$rec[$key] && $field == "title" && !@$rec[$key]) { //meaning full_reference AND title are blank or null
                        $c = false; break;
                    }
                }
                // some reference from DwCA: http://depot.globalbioticinteractions.org/release/org/eol/eol-globi-datasets/0.5/eol-globi-datasets-0.5-darwin-core-aggregated.tar.gz
                // <field index="0" term="http://purl.org/dc/terms/identifier"/>
                // <field index="1" term="http://eol.org/schema/reference/publicationType"/>
                // <field index="2" term="http://eol.org/schema/reference/full_reference"/>
                // <field index="3" term="http://eol.org/schema/reference/primaryTitle"/>
                // <field index="4" term="http://purl.org/dc/terms/title"/>
                
                if(in_array($field, array("accessURI","thumbnailURL","furtherInformationURL"))) {
                    if($val = @$rec[$key]) { //if not blank
                        if(!self::valid_uri_url($val)) { //then should be valid URI or URL
                            // $c = false; break; //you don't totally exclude the entire data_object but just set the field URI/URL to blank
                            $rec[$key] = "";
                            /* To do: investigate more
                            print_r($rec);
                            echo "\nURI/URL [$key] [$val] set to blank because it is invalid.\n";
                            */
                        }
                    }
                }
                
                // not been tested yet. Was working with dwca_utility.php _ 430 -> iNaturalist
                /* should work
                if($class == "document") { //meaning media objecs ---> filter out duplicate data_object identifiers
                    if($field == "identifier") {
                        $do_id = @$rec[$key];
                        if(isset($do_ids[$do_id])) {
                            $c = false; break; //exclude entire data_object entry if id already exists
                        }
                        else $do_ids[$do_id] = '';
                    }
                }
                */

                /* Need to have unique taxon ids. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have already unique taxon ids.
                Useful for e.g. DATA-1724 resource 'plant_forms_habitat_and_distribution'.
                */
                if(in_array($this->resource_id, array("plant_forms_habitat_and_distribution-adjusted", "1000_final", "fwater_marine_image_bank_meta_recoded")) || in_array(substr($this->resource_id,0,3), array("LD_", "EOL"))) {
                    if($class == "taxon") {
                        if($field == "taxonID") {
                            $taxon_id = @$rec[$key];
                            if(isset($this->taxon_ids[$taxon_id])) {
                                $this->debug['duplicate_taxon_ids'][$taxon_id] = '';
                                $c = false; break; //exclude entire taxon entry if id already exists
                            }
                            else $this->taxon_ids[$taxon_id] = '';
                        }
                    }
                }

                /* Need to have unique occurrenceIDs. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have already unique occurrenceIDs.
                Useful for e.g. DATA-1841 resource '1000_final'.
                */
                if(in_array($this->resource_id, array("1000_final"))) {
                    if($class == "occurrence") {
                        if($field == "occurrenceID") {
                            $occurrence_id = @$rec[$key];
                            if(isset($this->occurrence_ids[$occurrence_id])) {
                                $this->debug['duplicate_occurrence_ids'][$occurrence_id] = '';
                                $c = false; break; //exclude entire taxon entry if id already exists
                            }
                            else $this->occurrence_ids[$occurrence_id] = '';
                        }
                    }
                }

                /* Need to have unique reference ids. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have already unique ref ids.
                Useful for e.g. DATA-1724 resource 'plant_forms_habitat_and_distribution'.
                */
                /*
                Also used for: https://eol-jira.bibalex.org/browse/DATA-1733 --> Shelled_animal_body_mass, added this resource bec. it doesn't have unique ref ids.
                */
                if(in_array($this->resource_id, array("plant_forms_habitat_and_distribution-adjusted", "Shelled_animal_body_mass-adjusted"))) {
                    if($class == "reference") {
                        if($field == "identifier") {
                            $identifier = @$rec[$key];
                            if(isset($ref_ids[$identifier])) {
                                $this->debug['duplicate_ref_ids'][$identifier] = '';
                                $c = false; break; //exclude entire reference entry if id already exists
                            }
                            else $ref_ids[$identifier] = '';
                        }
                    }
                }
                
                /* measurementType must have value. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have non-null measurementType.
                Useful for e.g. https://eol-jira.bibalex.org/browse/DATA-1733 - 'Shelled_animal_body_mass'
                */
                if(in_array($this->resource_id, array("Shelled_animal_body_mass-adjusted"))) {
                    if($class == "measurementorfact") {
                        if($field == "measurementType" && !@$rec[$key]) { //meaning measurementType is blank or null, then exclude entire row.
                            $c = false; break;
                        }
                    }
                }
                
                /* used for resource 145 in lifedesk_combine.php --- taxonID in taxa extension cannot be blank
                if(stripos($this->resource_id, "145") !== false) { //string is found
                    if($class == "taxon") {
                        if($field == "taxonID" && !@$rec[$key]) { //meaning taxonID is blank or null, then compute for taxonID
                            $rec[$key] = str_replace(" ", "_", $rec['scientificName']);
                            echo "\n";
                            print_r($rec);
                            echo "\n taxonID is computed since it is blank \n";
                        }
                    }
                }
                */
                
                
                /* Need to have unique agent ids. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have already unique ref ids.
                First used for DATA-1569 resource 'lifedesks.tar.gz', connector [lifedesk_eol_export.php] */
                if(in_array($this->resource_id, array("lifedesks")) || in_array(substr($this->resource_id,0,3), array("LD_", "EOL"))) {
                    if($class == "agent") {
                        if($field == "identifier") {
                            $identifier = @$rec[$key];
                            if(isset($this->agent_ids[$identifier])) {
                                $this->debug['duplicate_agent_ids'][$identifier] = '';
                                $c = false; break; //exclude entire agent entry if id already exists
                            }
                            else $this->agent_ids[$identifier] = '';
                        }
                    }
                }

                /* Need to have unique taxon ids. It is confined to a pre-defined list of resources bec. it is memory intensive and most resources have already unique taxon ids.*/
                if(in_array($this->resource_id, array("Carrano_2006_meta_recoded"))) {
                    if($class == "taxon") {
                        if($field == "taxonID") {
                            $identifier = @$rec[$key];
                            if(isset($this->taxonIDs[$identifier])) {
                                $this->debug['duplicate taxonIDs'][$identifier] = '';
                                $c = false; break; //exclude entire agent entry if id already exists
                            }
                            else $this->taxonIDs[$identifier] = '';
                        }
                    }
                }
                
                //#################### end some validations ----------------------------  #########################################################################

                $c->$field = $rec[$key];

                // /* new: Oct 19, 2023
                if(in_array($field, array("full_reference", "primaryTitle", "title", "doi", "localityName", "description", "bibliographicCitation", "rights", "title", "namePublishedIn"))) {
                    if(in_array($this->resource_id, array("some_resource_id"))) $c->$field = strip_tags($c->$field);
                    else                                                        $c->$field = RemoveHTMLTagsAPI::remove_html_tags($c->$field);
                }
                // */
                
                // /* ----------------- customized: start to remove specific fields here -----------------
                
                // /* remove in MoF 'determinedBy' -> https://eol-jira.bibalex.org/browse/DATA-1881?focusedCommentId=65624&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65624
                if($this->resource_id == "cites_taxa") {
                    if($class == "measurementorfact") {
                        if(isset($c->measurementDeterminedBy)) unset($c->measurementDeterminedBy);
                    }
                }
                // */
                
                // ----------------- end ----------------- */

                // if($field == "taxonID") $c->$field = self::get_worms_taxon_id($c->$field); //not used here, only in WoRMS connector
            }//end loop foreach()
            if($generateArchive) {
                if($c) {
                    $this->archive_builder->write_object_to_file($c); //to facilitate validations
                    
                    //start customization here ========================================
                    if($this->resource_id == 24) {
                        if($class == "taxon") {
                            $this->taxon_ids[$c->taxonID] = '';
                            // print_r($c); exit;
                        }
                    }
                    //end customization here ========================================
                }
            }
        } //main loop
        return $count;
    }
    function build_id_name_array($records)
    {
        foreach($records as $rec) {
            // [http://rs.tdwg.org/dwc/terms/taxonID] => 6de0dc42e8f4fc2610cb4287a4505764
            // [http://rs.tdwg.org/dwc/terms/scientificName] => Accipiter cirrocephalus rosselianus Mayr, 1940
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $this->id_name[$taxon_id]['scientificName'] = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
            $this->id_name[$taxon_id]['parentNameUsageID'] = (string) $rec["http://rs.tdwg.org/dwc/terms/parentNameUsageID"];
        }
    }
    private function generate_higherClassification_field($records)
    {   /* e.g. $rec
        Array
            [http://rs.tdwg.org/dwc/terms/taxonID] => 5e2712849c197671c260f53809836273
            [http://rs.tdwg.org/dwc/terms/scientificName] => Passerina leclancherii leclancherii Lafresnaye, 1840
            [http://rs.tdwg.org/dwc/terms/parentNameUsageID] => 49fc924007e33cc43908fed677d5499a
        */
        $i = 0;
        foreach($records as $rec) {
            $higherClassification = self::get_higherClassification($rec);
            $records[$i]["higherClassification"] = $higherClassification; //assign value to main $records -> UNCOMMENT in real operation
            $i++;
        }
        return $records;
    }
    private function get_higherClassification($rek)
    {
        $parent_id = $rek['http://rs.tdwg.org/dwc/terms/parentNameUsageID'];
        $str = "";
        while($parent_id) {
            if($parent_id) {
                $str .= Functions::canonical_form(trim(@$this->id_name[$parent_id]['scientificName']))."|";
                $parent_id = @$this->id_name[$parent_id]['parentNameUsageID'];
            }
        }
        $str = substr($str, 0, strlen($str)-1);
        // echo "\norig: [$str]";
        $arr = explode("|", $str);
        $arr = array_reverse($arr);
        $str = implode("|", $arr);
        // echo "\n new: [$str]\n";
        return $str;
    }
    private function can_compute_higherClassification($records)
    {
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/taxonID"])) return false;
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/scientificName"])) return false;
        if(!isset($records[0]["http://rs.tdwg.org/dwc/terms/parentNameUsageID"])) return false;
        return true;
    }
    private function valid_uri_url($str)
    {
        $str = str_ireplace('http', 'http', $str); //bec some have something like Http://...
        if(substr($str,0,7) == "http://") return true;
        elseif(substr($str,0,8) == "https://") return true;
        return false;
    }
    //ends here 
    
    //=====================================================================================================================
    //start functions for the interface tool "genHigherClass"
    //=====================================================================================================================
    
    function tool_generate_higherClassification($file)
    {
        if($records = self::create_records_array($file))
        {
            self::build_id_name_array($records);                                //echo "\n1 of 3\n";
            $records = self::generate_higherClassification_field($records);     //echo "\n2 of 3\n";
            $fields = self::normalize_fields($records[0]);

            //start write to file
            // $file = str_replace(".", "_higherClassification.", $file);
            if(!($f = Functions::file_open($file, "w"))) return;
            fwrite($f, implode("\t", $fields)."\n");
            foreach($records as $rec) fwrite($f, implode("\t", $rec)."\n");
            fclose($f);
            // echo "\n3 of 3\n";
            return true;
        }
        else return false;
    }
    
    function create_records_array($file)
    {
        $records = array();
        $i = 0;
        foreach(new FileIterator($file) as $line => $row)
        {
            $i++;
            if($i == 1)
            {
                $fields = explode("\t", $row);
                $k = 0;
                foreach($fields as $field) //replace it with the long field URI
                {
                    if($field == "taxonID") $fields[$k] = "http://rs.tdwg.org/dwc/terms/taxonID";
                    elseif($field == "scientificName") $fields[$k] = "http://rs.tdwg.org/dwc/terms/scientificName";
                    elseif($field == "parentNameUsageID") $fields[$k] = "http://rs.tdwg.org/dwc/terms/parentNameUsageID";
                    $k++;
                }
            }
            else
            {
                $rec = array();
                $cols = explode("\t", $row);
                $k = 0;
                foreach($fields as $field)
                {
                    $rec[$field] = @$cols[$k];
                    $k++;
                }
                if($rec)
                {
                    if($i == 3) //can check this early if we can compute for higherClassification
                    {
                        if(!self::can_compute_higherClassification($records)) return false;
                    }
                    $records[] = $rec;
                }
            }
        }
        return $records;
    }
    
    private function normalize_fields($arr)
    {
        $fields = array_keys($arr);
        $k = 0;
        foreach($fields as $field)
        {
            $fields[$k] = pathinfo($field, PATHINFO_FILENAME);
            $k++;
        }
        return $fields;
    }
    //=====================================================================================================================
    //end functions for the interface tool "genHigherClass"
    //=====================================================================================================================

    // these 2 functions used in convert_archive_normalized()
    private function build_taxonIDs_with_objects_array($records)
    {
        $taxon_ids = array();
        foreach($records as $rec) {
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon_ids[$taxon_id] = '';
        }
        return array_keys($taxon_ids);
    }
    private function remove_taxa_without_objects($records, $taxon_ids_with_objects)
    {
        echo "\n start taxa count: ".count($records);
        $i = -1;
        foreach($records as $rec) {
            $i++;
            $taxon_id = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
            $taxon_status = (string) @$rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
            if(!in_array($taxon_id, $taxon_ids_with_objects) && !in_array($taxon_status, array("synonym"))) $records[$i] = null;
        }
        $records = array_filter($records); //remove null arrays
        $records = array_values($records); //reindex key
        echo "\n end taxa count: ".count($records);
        return $records;
    }

    //=====================================================================================================================
    //start OTHER functions
    //=====================================================================================================================
    function count_records_in_dwca($download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1))
    {
        if(!($info = self::start(false, $download_options))) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $totals = array();
        foreach($index as $row_type) {
            $count = self::process_fields($harvester->process_row_type($row_type), $this->extensions[$row_type], false); //3rd param = false means count only, no archive will be generated
            $totals[$row_type] = $count;
        }
        print_r($totals);
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
    }
    function lookup_values_in_dwca($download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1), $params)
    {
        if(!($info = self::start(false, $download_options))) return;
        $temp_dir = $info['temp_dir'];
        $harvester = $info['harvester'];
        $tables = $info['tables'];
        $index = $info['index'];

        $tables = $info['harvester']->tables; 
        // print_r($index); print_r($tables); exit;

        $row_type = $params['row_type'];
        $column = $params['column'];
        $meta = $tables[$row_type][0];

        $i = 0;
        foreach(new FileIterator($meta->file_uri) as $line => $row) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($meta->ignore_header_lines && $i == 1) continue;
            if(!$row) continue;
            $row = Functions::conv_to_utf8($row); //possibly to fix special chars
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta->fields as $field) {
                if(!$field['term']) continue;
                $rec[$field['term']] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit;
            $unique[$rec[$column]] = '';
        }
        // print_r($unique);
        return $unique;

        /* un-comment in real operation
        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        */
    }

    function get_uri_value($raw, $uri_values) //$raw e.g. "Philippines" ---- good func but not yet used, soon...
    {
        if($uri = @$uri_values[$raw]) return $uri;
        else {
            switch ($raw) { //put here customized mapping
                case "United States of America":    return "http://www.wikidata.org/entity/Q30";
                case "Port of Entry":               return false; //"DO NOT USE"
            }
        }
        return false;
    }
    
    //=====================================================================================================================
    //end OTHER functions
    //=====================================================================================================================

}
?>