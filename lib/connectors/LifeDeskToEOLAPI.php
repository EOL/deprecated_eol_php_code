<?php
namespace php_active_record;
// connector: [lifedesk_eol_export]
class LifeDeskToEOLAPI
{
    function __construct()
    {
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 900, 'download_attempts' => 2); // 15mins timeout
        $this->text_path = array();
    }

    function export_lifedesk_to_eol($params)
    {
        $this->ancestry = @$params['ancestry'];
        
        if(Functions::url_exists($params["lifedesk"])) {
            require_library('connectors/LifeDeskToScratchpadAPI');
            $func = new LifeDeskToScratchpadAPI();
            if($this->text_path = $func->load_zip_contents($params["lifedesk"]))
            {
                self::update_eol_xml("LD_".$params["name"]);
            }
            // remove temp dir
            $parts = pathinfo($this->text_path["eol_xml"]);
            recursive_rmdir($parts["dirname"]);
            debug("\n temporary directory removed: " . $parts["dirname"]);
        }
        else debug("\n LifeDesk main XML not found: ".$params["lifedesk"]."\n");
    }
    
    private function update_eol_xml($lifedesk_name)
    {
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
        $xml = $func->replace_taxon_element_value("dc:source", "replace any existing value", "", $xml, false);
        $xml = $func->replace_data_object_element_value("dc:source", "replace any existing value", "", $xml, false);
        $xml = self::remove_tags_in_references($xml);

        $xml = self::add_some_ancestry($xml); //adds some ancestry info if available

        //remove non-text objects per: https://eol-jira.bibalex.org/browse/DATA-1569?focusedCommentId=62038&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62038
        $xml = $func->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/StillImage", $xml);
        $xml = $func->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/MovingImage", $xml);
        $xml = $func->remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/Sound", $xml);

        $func->save_resource_document($xml);
        // zip the xml
        $command_line = "gzip -c " . CONTENT_RESOURCE_LOCAL_PATH . $lifedesk_name . ".xml >" . CONTENT_RESOURCE_LOCAL_PATH . $lifedesk_name . ".xml.gz";
        $output = shell_exec($command_line);
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
        $xml = simplexml_load_string($xml_string);
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