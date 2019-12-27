<?php
namespace php_active_record;
/* connector: generic connector to convert EOL XML to EOL DWC-A
    412     EOL China
    306     Reptile DB
    21      AmphibiaWeb
    367     DC Birds video
    829     Zookeys
    
*/
class ConvertEOLtoDWCaAPI
{
    const SPM = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#";
    const EOL = "http://www.eol.org/voc/table_of_contents#";
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->occurrence_ids = array();
        $this->count = 0;
        // $this->download_options = array('download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1);
        
        //first to use is resource = '330_pre':
        $this->download_options = array('resource_id' => $this->resource_id, 'expire_seconds' => 60*60*24*30, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);
    }

    function export_xml_to_archive($params, $xml_file_YN = false, $expire_seconds = 60*60*24*25) //expires in 25 days
    {
        if(!$xml_file_YN) {
            require_library('connectors/INBioAPI');
            $func = new INBioAPI();
            $paths = $func->extract_archive_file($params["eol_xml_file"], $params["filename"], array("timeout" => 7200, "expire_seconds" => $expire_seconds));
            // "expire_seconds" -- false => won't expire; 0 => expires now //debug
            print_r($paths);
            $params["path"] = $paths["temp_dir"];
            self::convert_stream_xml($params);
            $this->archive_builder->finalize(TRUE);
            recursive_rmdir($paths["temp_dir"]); // remove temp dir
        }
        else { //is XML file
            // $params['path'] = DOC_ROOT . "tmp/"; //obsolete
            $params['path'] = $GLOBALS['MAIN_TMP_PATH'];
            $local_xml_file = Functions::save_remote_file_to_local($params['eol_xml_file'], array('file_extension' => "xml", "cache" => 1, "expire_seconds" => $expire_seconds, "timeout" => 7200, "download_attempts" => 2, "delay_in_minutes" => 2)); 
            /* expire_seconds is irrelevant if there is no cache => 1 in save_remote_file_to_local() */ 
            $params['filename'] = pathinfo($local_xml_file, PATHINFO_BASENAME);
            self::convert_stream_xml($params);
            $this->archive_builder->finalize(TRUE);
            if(unlink($local_xml_file)) echo "\nSuccesfully deleted [$local_xml_file]\n";
            else                        echo "\nERROR: not deleted [$local_xml_file]\n";
        }
        echo "\ntotal rows: $this->count\n";
    }
    private function convert_stream_xml($params)
    {
        $file = $params["path"] . $params["filename"];
        $reader = new \XMLReader();
        echo "\nReading file [$file]...\n";
        if(!file_exists($file)) { //new Dec 18, 2019
            echo "\nInvestigate: file not found: [$file]\n";
            return;
        }
        $reader->open($file);
        $i = 0;
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon") {
                if($page_xml = @$reader->readOuterXML()) {
                    if($this->resource_id == 346) {
                        require_library('ResourceDataObjectElementsSetting');
                        $nmnh = new ResourceDataObjectElementsSetting();
                        $page_xml = $nmnh->fix_NMNH_xml($page_xml);
                    }

                    $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);
                    
                    if($this->resource_id == 346) {
                        $t = self::assgn_eol_subjects($t);
                        if($t = self::replace_Indet_sp($t)) $i = self::process_t($t, $i, $params);
                    }
                    else $i = self::process_t($t, $i, $params);
                }
            }
            // if($i >= 5) break; //good debug --- if you want to limit the no. of taxa
        }
    }
    /* not used anymore...
    private function convert_xml($params)
    {
        $file = $params["path"] . $params["filename"];
        echo "\n[$file]\n";
        $contents = file_get_contents($file);
        $contents = str_replace("xml:lang", "xml_lang", $contents);
        // $xml = simplexml_load_string($contents);
        $xml = simplexml_load_string($contents, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $i = 0;
        foreach($xml->taxon as $t)
        {
            $i = self::process_t($t, $i, $params);
            // break; //debug
        }
    }
    */
    private function process_data_object($objects, $taxon_id, $params, $sciname) //$sciname here was added for AntWeb (24)
    {
        $records = array();
        foreach($objects as $o) {
            $o_dc       = $o->children("http://purl.org/dc/elements/1.1/");
            $o_dcterms  = $o->children("http://purl.org/dc/terms/");
            $rec = array();
            foreach(array_keys((array) $o) as $field) {
                if(in_array($field, array("agent", "reference"))) continue; //processed separately below
                else {
                    $rec[$field] = (string) $o->$field;
                    if($field == "additionalInformation") {
                        if($val = (string) $o->$field->rating) $rec['rating'] = $val;
                        if($val = (string) $o->$field->subtype) $rec['subtype'] = $val;
                        
                        //known client for these 3 is BHL Flickr (544) - https://eol-jira.bibalex.org/browse/DATA-1703
                        if($val = (string) $o->$field->spatial) $rec['spatial'] = $val;
                        if($val = (string) $o->$field->latitude) $rec['lat'] = $val;
                        if($val = (string) $o->$field->longitude) $rec['long'] = $val;
                        
                        // if($val = (string) $o->$field->subject) $rec['addl_subject'] = $val; --- don't know yet where to put it
                    }
                }
            }
            foreach(array_keys((array) $o_dc) as $field) $rec[$field] = (string) $o_dc->$field;
            foreach(array_keys((array) $o_dcterms) as $field) {
                /* if(in_array($field, array("some_field"))) continue; //how to exclude fields, not in schema */
                $rec[$field] = (string) $o_dcterms->$field;
            }

            // ================================================================start filters - for quality control ================================================================
            if(@$rec['language'] == "English") $rec['language'] = "En"; //used in resource_id = 120
            if(@$rec['dataType'] == 'http://purl.org/dc/dcmitype/Text' && !@$rec['description']) continue;  //Text objects must have descriptions


            /* for debugging - OK
            if(@$rec['subject'] == 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description') echo "\n ==> ". @$rec['description'] . " ==== ";
            */

            if(self::is_media_object($rec['dataType'])) { //for resource_id = 39
                if(!Functions::valid_uri_url($rec['mediaURL'])) continue; //Media objects must have accessURI
            }

            if($this->resource_id == 'TaiEOL') {
                if($rec['dataType'] == 'http://purl.org/dc/dcmitype/StillImage') continue; //images are already offline, so as its dc:source. So no way to get the image URL.
            }
            
            if($this->resource_id == 889) { //TaiEOL Insecta TRAM-703
                // print_r($o); print_r($rec);
                if($url = @$rec['source']) {
                    if($val = self::get_889_image_url($url)) $rec['mediaURL'] = $val;
                    else continue;
                }
                else continue;
                // print_r($rec); exit;
            }
            
            if($this->resource_id == 24) { //AntWeb per https://eol-jira.bibalex.org/browse/DATA-1713?focusedCommentId=61546&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61546
                if($val = @$rec['mediaURL']) $rec['source'] = self::compute_AntWeb_source_from_mediaURL($val); //source becomes furtherInformationURL in DwCA
                else                         $rec['source'] = self::compute_AntWeb_source_from_sciname($sciname); //for text objects
                if(stripos(@$rec['rightsHolder'], "California Academy of Sciences") !== false) $rec['rightsHolder'] = 'California Academy of Sciences'; //rightsHolder becomes Owner in DwCA. Removes extra ", xxx" string.
                if(!@$rec['rightsHolder'])                                                     $rec['rightsHolder'] = 'California Academy of Sciences'; //text objects need Owner as well, forced.
            }

            if($this->resource_id == 346) {
                if($rec['dataType'] == 'http://purl.org/dc/dcmitype/StillImage') $rec['rating'] = 2;
                if(@$rec['mimeType'] == "image/x-adobe-dng") continue; // remove_data_object_of_certain_element_value("mimeType", "image/x-adobe-dng", $xml);
                if(@$rec['dataType'] == "http://purl.org/dc/dcmitype/Text") continue; //no text objects for resource 346
            }
            
            if($this->resource_id == 367) { //DC Birds Video - https://eol-jira.bibalex.org/browse/DATA-1721
                $rec['obj_identifier'] = pathinfo($rec['mediaURL'], PATHINFO_BASENAME);
                $rec['mediaURL'] = "https://editors.eol.org/other_files/DCBirds_video/".$rec['obj_identifier'];
            }
            
            if($this->resource_id == 200) { //Bioimages Vanderbilt XML - https://eol-jira.bibalex.org/browse/DATA-1656?focusedCommentId=62673&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62673
                //1st get the agent(s), then save it in media tab as 'creator'. As in: http://eol.org/schema/media_extension.xml#creator
                //and if there are no <agent>'s, put Malcolm Storey as 'creator'.
                if($obj = @$o->agent) {
                    if($agents = self::process_agent($obj, $params)) {
                        /* Array(
                            [0] => Array(
                                    [term_name] => Steven J. Baskauf
                                    [agentRole] => photographer
                                    [agentID] => 42b508d6fda1e8199455c02a47d851fb
                                    [term_homepage] => 
                                )
                        )*/
                        $creator = array();
                        foreach($agents as $tmp) $creator[] = $tmp['term_name'];
                        $creator = implode("; ", $creator);
                        $rec['creator'] = $creator;
                    }
                }
                else $rec['creator'] = 'Malcolm Storey'; // print_r($obj); print_r($rec); print_r($o); //good debug
            }

            if($this->resource_id == '330_pre') { //Moorea Biocode - https://eol-jira.bibalex.org/browse/DATA-1810
                // print_r($rec); exit;
                $rec['derivedFrom'] = self::get_res330_contributorID($rec['source']);
            }
            // ================================================================end filters - for quality control ==================================================================


            //for references in data_object
            if($obj = @$o->reference)
            {
                if($references = self::process_reference($obj, $taxon_id, $params)) {
                    $reference_ids = array();
                    foreach($references as $reference) {
                        self::create_archive($reference, "reference");
                        $reference_ids[$reference["ref_identifier"]] = '';
                    }
                    $rec["referenceID"] = implode("; ", array_keys($reference_ids));
                }
            }
            
            //for agent
            if($obj = @$o->agent) {
                if($agents = self::process_agent($obj, $params)) {
                    $agent_ids = array();
                    foreach($agents as $agent) {
                        self::create_archive($agent, "agent");
                        $agent_ids[$agent["agentID"]] = '';
                    }
                    $rec["agentID"] = implode("; ", array_keys($agent_ids));
                }
            }
            
            //start customize --------------------------------------------------------------------------------------//Bioimages Vanderbilt XML - https://eol-jira.bibalex.org/browse/DATA-1656?focusedCommentId=62673&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62673
            if($this->resource_id == 200) { //for all records, add another agent - with role 'compiler' "Malcolm Storey"
                $additional_agent = array('term_name' => 'Malcolm Storey', 'agentRole' => 'compiler', 'agentID' => md5('Malcolm Storey'), 'term_homepage' => '');
                self::create_archive($additional_agent, "agent");
                $agent_ids[$additional_agent["agentID"]] = '';
                $rec["agentID"] = implode("; ", array_keys($agent_ids));
            }
            //end customize --------------------------------------------------------------------------------------
            

            /* obsolete but good reference to history
            if(in_array($params["dataset"], array("EOL China", "EOL XML")))
            {
                if($val = $o_dc->identifier) $identifier = (string) $val;
                else echo("\n -- find or create your own object identifier -- \n");
            }
            */
            if(!@$rec['obj_identifier']) { //e.g. resource_id = 367
                if($val = @$o_dc->identifier) $identifier = (string) $val;
                else {
                    /* from above
                    412     EOL China
                    306     Reptile DB
                    21      AmphibiaWeb
                    */
                    /* orig, but decided to make this automatic
                    if(in_array($this->resource_id, array(412,306,21,"EOL_180","EOL_374",181,'EOL_256','EOL_257','EOL_275'))) { //add here resource_ids
                        $json = json_encode($rec, true);
                        $identifier = md5($json);
                    }
                    else echo("\n -- find or create your own object identifier -- \n");
                    */
                    $json = json_encode($rec, true);
                    $identifier = md5($json);
                    // echo "\nSystem generated object identifier, since XML doesn't have it.\n";
                }
                $rec["obj_identifier"] = $identifier;
            }

            unset($rec["identifier"]);
            $rec["taxonID"] = $taxon_id;
            $records[] = $rec;
        }
        // print_r($records);
        return $records;
    }
    //=================================================== start customized functions [889] ===========================================
    private function get_889_image_url($url)
    {
        if(!$url) return;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($url, $options)) { 
            if(preg_match("/\"og\:image\" content=\"(.*?)\"/ims", $html, $arr)) {
                // <meta property="og:image" content="http://data.taieol.tw/files/eoldata/imagecache/taieol_img/images/39/calophya_mangiferae-2-001-007-g-2.jpg" />
                if($val = @$arr[1]) return $val;
            }
        }
    }
    //==================================================== end customized functions [889] ============================================
    //=================================================== start customized functions [330] ===========================================
    private function get_res330_contributorID($url)
    {
        if(!$url) return;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($url, $options)) { /* <b>contributor's ID #</b></small>&nbsp;&nbsp;BMOO-01716 <li> */
            if(preg_match("/contributor\'s ID \#(.*?)<li>/ims", $html, $arr)) {
                $id = strip_tags(trim($arr[1]));
                $id = str_replace("&nbsp;", "", $id);
                // echo "\n[$id]\n";
                return $id;
            }
        }
    }
    //=================================================== end customized functions [330] ===========================================
    private function is_media_object($data_type)
    {
        $media = array("http://purl.org/dc/dcmitype/MovingImage", "http://purl.org/dc/dcmitype/Sound", "http://purl.org/dc/dcmitype/StillImage");
        if(in_array($data_type, $media)) return true;
        else return false;
    }
    
    private function process_agent($objects, $params)
    {
        $records = array();
        foreach($objects as $o) {
            if($params["dataset"] == "EOL China") {}
            if(!(string) $o) continue;
            $records[] = array("term_name" => strip_tags((string) $o), "agentRole" => (string) $o{"role"}, "agentID" => md5((string) $o), "term_homepage" => (string) @$o{"homepage"});
        }
        // print_r($records);
        return $records;
    }

    private function process_reference($objects, $taxon_id, $params)
    {
        $records = array();
        foreach($objects as $o) {
            $full_reference = trim((string) $o);
            if(!$full_reference) continue;
            
            $identifier = ''; $uri = '';
            if($params["dataset"] == "EOL China") {
                $uri = (string) $o{"url"};
                if(preg_match("/\{(.*?)\}/ims", $uri, $arr)) $identifier = $arr[1];
                else echo("\n -- find or create your own ref identifier -- \n");
            }
            // elseif(in_array($params["dataset"], array("Pensoft XML files", "Amphibiaweb", "NMNH XML files"))) 
            else {
                if($val = $o{'doi'}) $identifier = (string) $val;
                if($val = $o{'uri'}) $uri = $val;
            }

            if(!$identifier) $identifier = md5($full_reference);
            
            if(!$identifier) echo "\nModule to create ref identifier and uri for this dataset has not yet been defined!\n";
            $records[] = array("full_reference" => $full_reference, "uri" => $uri, "ref_identifier" => $identifier);
        }
        // print_r($records);
        return $records;
    }
    private function process_synonym($objects, $taxon_id)
    {
        $records = array();
        foreach($objects as $o) {
            if(trim((string) $o)) { //needed validation for IUCN 211.php and NMNH XML resources
                // print_r($o); //debug
                $status = (string) @$o{"relationship"};
                if(!$status) $status = 'synonym';
                $records[] = array("scientificName" => (string) $o, "taxonomicStatus" => $status, 
                                   "taxonID" => str_replace(" ", "_", $o) ,"acceptedNameUsageID" => (string) $taxon_id);
            }
        } 
        // print_r($records);
        return $records;
    }
    private function process_vernacular($objects, $taxon_id)
    {
        $records = array();
        foreach($objects as $o) {
            $lang = trim((string) $o{"xml_lang"}); //not used anymore
            $lang = @$o->attributes('xml', TRUE)->lang; //works OK
            if($val = trim((string) $o)) $records[] = array("vernacularName" => $val, "language" => $lang, "taxonID" => (string) $taxon_id);
        }
        // print_r($records);
        return $records;
    }
    private function create_archive($rec, $type)
    {
        if    ($type == "taxon")       $t = new \eol_schema\Taxon();
        elseif($type == "vernacular")  $t = new \eol_schema\VernacularName();
        elseif($type == "reference")   $t = new \eol_schema\Reference();
        elseif($type == "data object") $t = new \eol_schema\MediaResource();
        elseif($type == "agent")       $t = new \eol_schema\Agent();
        foreach(array_keys($rec) as $orig_field) {
            $field = lcfirst($orig_field);
            if($field == 'additionalInformation') continue; //the actual field 'additionalInformation' is excluded in DwCA. Its contents (e.g. <rating>,<latitude>) are used elsewhere, not here.
            
            if    ($field == "identifier")      $tfield = "taxonID";
            elseif($field == "source")          $tfield = "furtherInformationURL";
            elseif($field == "ref_identifier")  $tfield = "identifier";
            elseif($field == "obj_identifier")  $tfield = "identifier";
            //for dataObject
            elseif($field == "dataType")        $tfield = "type";
            elseif($field == "mimeType")        $tfield = "format";
            elseif($field == "license")         $tfield = "UsageTerms";
            elseif($field == "rightsHolder")    $tfield = "Owner";
            elseif($field == "mediaURL")        $tfield = "accessURI";
            elseif($field == "created")         $tfield = "CreateDate";
            elseif($field == "subject")         $tfield = "CVterm";
            elseif($field == "agentID")
            {
                if($type == "data object")      $tfield = "agentID";
                elseif($type == "agent")        $tfield = "identifier";
            }
            elseif($field == "location")        $tfield = "LocationCreated";
            elseif($field == "rating")          $tfield = "Rating";
            else                                $tfield = $field;
            $t->$tfield = $rec[$orig_field];
        }
        
        if($type == "taxon") {
            if(!isset($this->taxon_ids[$t->taxonID])) {
                $this->taxon_ids[$t->taxonID] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }
        elseif($type == "data object") {
            if(!isset($this->media_ids[$t->identifier])) {
                $this->media_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }
        elseif(in_array($type, array("vernacular"))) {
            $this->archive_builder->write_object_to_file($t);
        }
        elseif($type == "reference") {
            if(!isset($this->reference_ids[$t->identifier])) {
                $this->reference_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }
        elseif($type == "agent") {
            if(!isset($this->agent_ids[$t->identifier])) {
                $this->agent_ids[$t->identifier] = '';
                $this->archive_builder->write_object_to_file($t);
            }
        }
    }
    private function process_t($t, $i, $params)
    {
        $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
        $t_dc       = $t->children("http://purl.org/dc/elements/1.1/");
        $t_dcterms  = $t->children("http://purl.org/dc/terms/");
        /*
        if($i <= 2) {
            print_r($t_dc);
            print_r($t_dwc);
        }
        else return; //exit;
        */
        $i++; if(($i % 5000) == 0) echo "\n $i ";
        $rec = array();
        foreach(array_keys((array) $t_dc) as $field)  $rec[$field] = (string) $t_dc->$field;
        foreach(array_keys((array) $t_dwc) as $field) $rec[$field] = (string) $t_dwc->$field;
        foreach(array_keys((array) $t_dcterms) as $field) {
            if(in_array($field, array("created"))) continue; //exclude these fields, not in schema - CreateDate not found in taxon extension
            $rec[$field] = (string) $t_dcterms->$field;
        }
        
        $taxon_id = false;
        if(isset($t_dc->identifier)) {
            if    ($val = trim($t_dc->identifier))      $taxon_id = $val;
            elseif($val = trim($t_dwc->ScientificName)) $taxon_id = md5($val);
            else //continue; is obsolete coz loop is gone here, use return; instead... //meaning if there is no taxon id and sciname then ignore record
            {
                return $i;
            }
        }
        else {
            // echo "\nwent here\n";
            if($val = $taxon_id) $rec["identifier"] = $val;
            else {
                if(in_array($params["dataset"], array("NMNH XML files"))) return $i; //meaning if there is no taxon id and sciname then ignore record
                else {
                    // echo "\n -- try to figure how to get taxon_id for this resource: $params[dataset] -- \n";
                    // print_r($t); print_r($t_dc); print_r($t_dwc); exit; //debug
                    return $i;
                }
            }
        }

        // ==================================start customize============================ was working OK, but decided to use the orig taxonID from LifeDesk XML
        // if(substr($this->resource_id,0,3) == "LD_") $taxon_id = md5(trim($t_dwc->ScientificName));
        /* Used md5(sciname) here so we can combine taxon.tab with LifeDesk multimedia resource (e.g. LD_afrotropicalbirds_multimedia.tar.gz). See CollectionsScrapeAPI.php */
        // ==================================end customize==============================

        if($obj = @$t->commonName) {
            if($vernaculars = self::process_vernacular($obj, $taxon_id)) {
                foreach($vernaculars as $vernacular) {
                    if($vernacular) self::create_archive($vernacular, "vernacular");
                }
            }
        }
        if($obj = @$t->synonym) {
            if($synonyms = self::process_synonym($obj, $taxon_id)) {
                foreach($synonyms as $synonym) self::create_archive($synonym, "taxon");
            }
        }
        if($obj = @$t->reference) {
            if($references = self::process_reference($obj, $taxon_id, $params)) {
                $reference_ids = array();
                foreach($references as $reference) {
                    self::create_archive($reference, "reference");
                    $reference_ids[$reference["ref_identifier"]] = '';
                }
                $rec["referenceID"] = implode("; ", array_keys($reference_ids));
            }
        }
        
        if($obj = @$t->dataObject) {
            if($data_objects = self::process_data_object($obj, $taxon_id, $params, $t_dwc->ScientificName)) {
                foreach($data_objects as $data_object) {
                    if($this->resource_id == 346 && $data_object['dataType'] == "http://purl.org/dc/dcmitype/Text") continue; //exclude text objects for resource (346) per DATA-1743
                    // print_r($rec); print_r($data_object); exit;
                    /*
                    $rec = Array (
                        [identifier] => afrotropicalbirds:tid:315
                        [source] => 
                        [ScientificName] => Ploceus ruweti Louette & Benson, 1982
                        [referenceID] => f4e71071e04dd117aec938ddb10a9031;5445e9a2b2148dfcf751fa7d5ea28aa9;9153811b0388946d329a780afc27c318;84eaad628f7cfe8e20c72ffb47a25c6e;a96732dcddd607e79d644e1730cdaba8;502078912fef71e6450ea603a7579531;3165367b7845d44e292cdc6bd5731b48;e4d83c107757ee2e6a14ce98da8259ab;447f18df3894ce7d754939861c42fa4e;2f1f8acd1ee764ae8c648166f7717d91;11460b4cfeedfc01b2677fddd3d480b6
                    )
                    $data_object = Array(
                        [dataType] => http://purl.org/dc/dcmitype/Text
                        [license] => http://creativecommons.org/licenses/by-nc-sa/3.0/
                        [subject] => http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description
                        [title] => Description
                        [source] => 
                        [description] => <p>The Lufira Masked Weaver is previously only known from the unique type specimen collected in 1960 at Lake Lufira (= Lake Tshangalele) in Katanga (Democratic Republic of Congo). For many years the status of <em>Ploceus ruweti </em>remained obscure, until it was rediscovered nesting at the same locality in February–March 2009.</p><p>The males of this sexually dimorphic ploceid are yellow and rufous coloured with a black mask differing from other members of the <em>P. velatus </em>complex by some detailed diagnostic characteristics.<strong><em> </em></strong></p>
                        [created] => 2011-04-18 15:15:15
                        [modified] => 2012-05-10 8:08:39
                        [rightsHolder] => Cooleman, Stijn
                        [agentID] => 6051bea39c0d43a6c245803166ad5ed2
                        [obj_identifier] => afrotropicalbirds:nid:1:tid_chapter:261
                        [taxonID] => afrotropicalbirds:tid:315
                    )
                    */
                    
                    self::create_archive($data_object, "data object");
                }
            }
        }
        
        $rec = array_map('trim', $rec);
        // echo "\nidentifier: ".$rec['identifier']. " ScientificName: " . $rec['ScientificName']; exit("\nelix\n");
        if($rec['identifier'] && $rec['ScientificName']) {
            // ==================================start customize============================ was working OK, but decided to use the orig taxonID from LifeDesk XML
            // if(substr($this->resource_id,0,3) == "LD_") $rec['identifier'] = md5($rec['ScientificName']);
            /* Used md5(sciname) here so we can combine taxon.tab with LifeDesk multimedia resource (e.g. LD_afrotropicalbirds_multimedia.tar.gz). See CollectionsScrapeAPI.php */
            // ==================================end customize==============================
            
            self::create_archive($rec, "taxon");
            $this->count++;
        }
        return $i;
    }
    
    // =============================================================== start customized functions [346]=============================================
    private function replace_Indet_sp($taxon) //resource_id 346 - NMNH Botany
    {
        $orig_taxon = $taxon;
        $dc = $taxon->children("http://purl.org/dc/elements/1.1/");
        $dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
        $dcterms = $taxon->children("http://purl.org/dc/terms/");
        // echo "\n " . $dc->identifier . " -- sciname: [" . $dwc->ScientificName."]";
        if(is_numeric(stripos($dwc->ScientificName, "Indet")) || is_numeric(stripos($dwc->Kingdom, "Indet")) || is_numeric(stripos($dwc->Phylum, "Indet")) ||
           is_numeric(stripos($dwc->Class, "Indet")) || is_numeric(stripos($dwc->Order, "Indet")) || is_numeric(stripos($dwc->Family, "Indet")) || is_numeric(stripos($dwc->Genus, "Indet")))
        {
            if(isset($dwc->Genus)) $ancestry['Genus'] = (string) $dwc->Genus;
            if(isset($dwc->Family)) $ancestry['Family'] = (string) $dwc->Family;
            if(isset($dwc->Order)) $ancestry['Order'] = (string) $dwc->Order;
            if(isset($dwc->Class)) $ancestry['Class'] = (string) $dwc->Class;
            if(isset($dwc->Phylum)) $ancestry['Phylum'] = (string) $dwc->Phylum;
            if(isset($dwc->Kingdom)) $ancestry['Kingdom'] = (string) $dwc->Kingdom;
            $ancestry['ScientificName'] = (string) $dwc->ScientificName;

            $ancestry = self::get_names($ancestry);
            // echo "\n old sciname: [$dwc->ScientificName] --- final sciname: [" . $ancestry['ScientificName'] . "]"; //good debug

            $dwc->ScientificName = $ancestry['ScientificName'];
            if(isset($dwc->Genus)) $dwc->Genus = $ancestry['Genus'];
            if(isset($dwc->Family)) $dwc->Family = $ancestry['Family'];
            if(isset($dwc->Order)) $dwc->Order = $ancestry['Order'];
            if(isset($dwc->Class)) $dwc->Class = $ancestry['Class'];
            if(isset($dwc->Phylum)) $dwc->Phylum = $ancestry['Phylum'];
            if(isset($dwc->Kingdom)) $dwc->Kingdom = $ancestry['Kingdom'];
            if(!$ancestry['ScientificName']) return false;
            else {
                $xml = $taxon->asXML();
                return simplexml_load_string($xml, null, LIBXML_NOCDATA);
            }
        }
        return $orig_taxon;
    }
    private function get_names($ancestry)
    {
        // first loop is to remove all Indet taxon entries
        foreach($ancestry as $rank => $name) {
            if(is_numeric(stripos($name, "Indet"))) {
                $ancestry[$rank] = "";
                // echo "\n $rank has [$name] now removed.";
            }
        }
        // if ScientificName is blank, then it will get the immediate higher taxon if it exists
        if($ancestry['ScientificName'] == "") {
            foreach($ancestry as $rank => $name) {
                if(trim($name) != "") {
                    // echo "\n This will be the new ScientificName: [$name] \n"; //good debug
                    $ancestry['ScientificName'] = $name;
                    $ancestry[$rank] = "";
                    return $ancestry;
                }
            }
        }
        return $ancestry;
    }
    private function assgn_eol_subjects($taxon)
    {
        foreach($taxon->dataObject as $dataObject) {
            $eol_subjects[] = self::EOL . "SystematicsOrPhylogenetics";
            $eol_subjects[] = self::EOL . "TypeInformation";
            $eol_subjects[] = self::EOL . "Notes";
            if(@$dataObject->subject) {
                if(in_array($dataObject->subject, $eol_subjects)) {
                    $dataObject->addChild("additionalInformation", "");
                    $dataObject->additionalInformation->addChild("subject", $dataObject->subject);
                    if    ($dataObject->subject == self::EOL . "SystematicsOrPhylogenetics") $dataObject->subject = self::SPM . "Evolution";
                    elseif($dataObject->subject == self::EOL . "TypeInformation")            $dataObject->subject = self::SPM . "DiagnosticDescription";
                    elseif($dataObject->subject == self::EOL . "Notes")                      $dataObject->subject = self::SPM . "Description";
                }
            }
        }
        $xml = $taxon->asXML();
        return simplexml_load_string($xml, null, LIBXML_NOCDATA);
    }
    //================================================================================ end [346]
    
    // =============================================================== start customized functions [24]=============================================
    private function compute_AntWeb_source_from_mediaURL($mediaURL)
    {
        // $mediaURL = "http://www.antweb.org/images/casent0103174/casent0103174_h_1_high.jpg";
        // $mediaURL = "http://www.antweb.org/images/casent0103174/casent0103174_l_1_high.jpg";
        /* Array (
            [dirname] => http://www.antweb.org/images/casent0103174
            [basename] => casent0103174_l_1_high.jpg
            [extension] => jpg
            [filename] => casent0103174_l_1_high
        ) */
        $parts = explode("_", pathinfo($mediaURL, PATHINFO_FILENAME));
        if(count($parts) >= 4) return "https://www.antweb.org/bigPicture.do?name=".$parts[0]."&shot=".$parts[1]."&number=".$parts[2];
        // https://www.antweb.org/bigPicture.do?name=casent0103174&shot=h&number=1
        // https://www.antweb.org/bigPicture.do?name=casent0103174&shot=l&number=1
    }
    private function compute_AntWeb_source_from_sciname($sciname)
    {   //e.g. https://www.antweb.org/description.do?genus=acanthognathus&species=teledectus
        $url = '';
        $sciname = trim($sciname);
        $parts = explode(" ", $sciname);
        if($val = $parts[0]) $genus = $val;
        $url = "https://www.antweb.org/description.do?genus=".$genus;
        if($species = $parts[1]) $url .= "&species=".$species;
        return $url;
    }
    //================================================================================ end [24]

}
?>