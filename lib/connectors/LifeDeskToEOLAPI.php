<?php
namespace php_active_record;
// connector: [lifedesk_eol_export]
class LifeDeskToEOLAPI
{
    function __construct()
    {
        $this->download_options = array('expire_seconds' => false, 'download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 2); // 15mins timeout
        $this->text_path = array();
        $this->taxa_from_orig_LifeDesk_XML = array();
    }

    //=================================================================================================================================
    // start utility - not necessarily used in this library 'LifeDeskToEOLAPI'
    //=================================================================================================================================
    function get_taxa_from_EOL_XML($xml_path) // a new utility used elsewhere e.g. /connectors/collections_generic.php
    {
        $final = array();
        if(Functions::url_exists($xml_path)) {
            if($temp_path = self::load_zip_contents($xml_path)) {
                $xml = simplexml_load_file($temp_path["eol_xml"]);
                foreach($xml->taxon as $t) {
                    $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
                    $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
                    $identifier = Functions::import_decode($t_dc->identifier);
                    $sciname    = Functions::import_decode($t_dwc->ScientificName);
                    $final[$identifier] = $sciname;
                }
            }
        }
        else debug("\n EOL XML not found: ".$xml_path."\n");
        return array('taxa_from_EOL_XML' => $final, 'xml_path' => $temp_path["eol_xml"]);
    }
    private function load_zip_contents($zip_file) //used by get_taxa_from_EOL_XML()
    {
        if    ($zip_file == "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/LD2Scratchpad_EOL/apoidea/eol-partnership.xml.gz") $this->download_options['expire_seconds'] = false;
        elseif($zip_file == "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/LD2Scratchpad_EOL/avesamericanas/eol-partnership.xml.gz") $this->download_options['expire_seconds'] = false;
        
        $temp_dir = create_temp_dir() . "/";
        if($file_contents = Functions::lookup_with_cache($zip_file, $this->download_options)) {
            $parts = pathinfo($zip_file);
            $temp_file_path = $temp_dir . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            
            if(is_numeric(stripos($zip_file, ".tar.gz"))) $output = shell_exec("tar -xzf $temp_file_path -C $temp_dir");
            elseif(is_numeric(stripos($zip_file, ".xml.gz"))) $output = shell_exec("gzip -d $temp_file_path -q "); //$temp_dir
            
            $temp_path["eol_xml"] = $temp_dir . Functions::get_file_of_this_extension_in_this_folder($temp_dir, 'xml');
            return $temp_path;
        }
        else {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return false;
        }
    }
    //=================================================================================================================================
    // end utility
    //=================================================================================================================================

    function export_lifedesk_to_eol($params, $prefix)
    {
        $this->ancestry = @$params['ancestry'];
        
        if(Functions::url_exists($params["lifedesk"])) {
            /*
            if($prefix == "LD_") {
                require_library('connectors/LifeDeskToScratchpadAPI');
                $func = new LifeDeskToScratchpadAPI();
                if($this->text_path = $func->load_zip_contents($params["lifedesk"])) self::update_eol_xml("LD_".$params["name"]);
            }
            if($prefix == "EOL_") {
                if($this->text_path = self::load_zip_contents($params["lifedesk"])) self::update_eol_xml("EOL_".$params["name"]);
            }
            */
            // Just use the local load_zip_contents() rather than from LifeDeskToScratchpadAPI()
            if($this->text_path = self::load_zip_contents($params["lifedesk"])) self::update_eol_xml($prefix.$params["name"], $prefix);
            print_r($this->text_path);

            // remove temp dir
            $parts = pathinfo($this->text_path["eol_xml"]);
            recursive_rmdir($parts["dirname"]);
            debug("\n temporary directory removed: " . $parts["dirname"]);
        }
        else debug("\n LifeDesk ($prefix) main XML not found: ".$params["lifedesk"]."\n");
        return $this->taxa_from_orig_LifeDesk_XML;
    }
    
    private function update_eol_xml($lifedesk_name, $prefix)
    {
        $this->taxa_from_orig_LifeDesk_XML = self::get_taxa_from_orig_LifeDesk_XML(); //used for this purpose: https://eol-jira.bibalex.org/browse/DATA-1569?focusedCommentId=62079&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62079
        /*
        taxon = 434
        dwc:ScientificName = 434
        reference = 614
        synonym = 68
        commonName = 2
        dataObjects = 1705
        reference = 0
        texts = 1146
        images = 559
        videos = 0
        sounds = 0
        */
        require_library('ResourceDataObjectElementsSetting');
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $lifedesk_name . ".xml";
        $func = new ResourceDataObjectElementsSetting($lifedesk_name, $resource_path);
        $xml = file_get_contents($this->text_path["eol_xml"]);
        
        // if($prefix == "LD_") {
            $xml = $func->replace_taxon_element_value("dc:source", "replace any existing value", "", $xml, false);
            $xml = $func->replace_data_object_element_value("dc:source", "replace any existing value", "", $xml, false);
            $xml = self::remove_tags_in_references($xml);
        // }

        $xml = self::add_some_ancestry($xml); //adds some ancestry info if available. Working OK. Used initially in lifedesk_combine.php but changed strategy and wasn't used eventually. But this works OK.

        //remove non-text objects per: https://eol-jira.bibalex.org/browse/DATA-1569?focusedCommentId=62038&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62038
        $xml = $func->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/StillImage", $xml);
        $xml = $func->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/MovingImage", $xml);
        $xml = $func->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/Sound", $xml);

        $xml = str_replace("<dc:source></dc:source>", "", $xml);
        $xml = str_replace("<dc:source/>", "", $xml); //seems like the correct one to remove after above statements.

        $func->save_resource_document($xml);
        // zip the xml
        $command_line = "gzip -c " . CONTENT_RESOURCE_LOCAL_PATH . $lifedesk_name . ".xml >" . CONTENT_RESOURCE_LOCAL_PATH . $lifedesk_name . ".xml.gz";
        $output = shell_exec($command_line);
    }
    private function get_taxa_from_orig_LifeDesk_XML()
    {
        $final = array();
        $xml = simplexml_load_file($this->text_path["eol_xml"]);
        foreach($xml->taxon as $t) {
            $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
            $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
            $identifier = Functions::import_decode($t_dc->identifier);
            $sciname    = Functions::import_decode($t_dwc->ScientificName);
            $final[$identifier] = $sciname;
        }
        // print_r($final); exit;
        return $final;
    }
    private function add_some_ancestry($xml)
    {
        $ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus');
        foreach($ranks as $rank) {
            if($val = @$this->ancestry[$rank]) {
                if(strpos($xml, "<dwc:".ucfirst($rank).">") !== false) {} //string is found
                else $xml = str_replace("<dwc:ScientificName>", "<dwc:".ucfirst($rank).">$val</dwc:".ucfirst($rank)."><dwc:ScientificName>", $xml);
            }
        }
        return $xml;
        /*
        if($kingdom = @$this->ancestry['kingdom']) {
            if(strpos($xml, "<dwc:Kingdom>") !== false) {} //string is found
            else $xml = str_replace("<dwc:ScientificName>", "<dwc:Kingdom>$kingdom</dwc:Kingdom><dwc:ScientificName>", $xml);
        }
        */
    }
    private function remove_tags_in_references($xml_string)
    {
        $field = "reference";
        // $xml = simplexml_load_string($xml_string);
        $xml = simplexml_load_string($xml_string, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
        debug("remove_tags_in_references " . count($xml->taxon) . "-- please wait...");
        foreach($xml->taxon as $taxon)
        {
            $i = 0;
            foreach($taxon->reference as $ref)
            {
                $taxon->reference[$i] = strip_tags($ref);
                $i++;
            }
            // foreach($taxon->dataObject as $dataObject){}
        }
        debug("remove_tags_in_references -- done.");
        return $xml->asXML();
    }

}
?>