<?php
namespace php_active_record;
class ResourceDataObjectElementsSetting
{
    public function __construct($resource_id, $xml_path, $data_object_type = null, $rating = null)
    {
        $this->resource_id = $resource_id;
        $this->xml_path = $xml_path;
        $this->data_object_type = $data_object_type;
        $this->rating = $rating;
    }

    public function set_data_object_rating_on_xml_document()
    {
        $xml_string = self::load_xml_string();
        if($xml_string === false) return false;
        $xml = simplexml_load_string($xml_string);
        debug("set_data_object_rating_on_xml_document " . count($xml->taxon) . "-- please wait...");
        foreach($xml->taxon as $taxon)
        {
            foreach($taxon->dataObject as $dataObject)
            {
                $dataObject_dc = $dataObject->children("http://purl.org/dc/elements/1.1/");
                if(@$dataObject->dataType == $this->data_object_type)
                {
                    if ($dataObject->additionalInformation)
                    {
                        if ($dataObject->additionalInformation->rating) $dataObject->additionalInformation->rating = $this->rating;
                        else $dataObject->additionalInformation->addChild("rating", $this->rating);
                    }
                    else
                    {
                        $dataObject->addChild("additionalInformation", "");
                        $dataObject->additionalInformation->addChild("rating", $this->rating);
                    }
                }
            }
        }
        debug("set_data_object_rating_on_xml_document -- done.");
        return $xml->asXML();
    }

    function load_xml_string()
    {
        debug("Please wait, downloading resource document...");
        if(preg_match("/^(.*)\.(gz|gzip)$/", $this->xml_path, $arr))
        {
            $path_parts = pathinfo($this->xml_path);
            $filename = $path_parts['basename'];
            $this->TEMP_FILE_PATH = create_temp_dir() . "/";
            debug("temp file path: " . $this->TEMP_FILE_PATH);
            if($file_contents = Functions::get_remote_file($this->xml_path, array('timeout' => 172800)))
            {
                $temp_file_path = $this->TEMP_FILE_PATH . "/" . $filename;
                $TMP = fopen($temp_file_path, "w");
                fwrite($TMP, $file_contents);
                fclose($TMP);
                shell_exec("gunzip -f $temp_file_path");
                $this->xml_path = $this->TEMP_FILE_PATH . str_ireplace(".gz", "", $filename);
                debug("xml path: " . $this->xml_path);
            }
            else
            {
                debug("Connector terminated. Remote files are not ready.");
                return false;
            }
        }
        return Functions::get_remote_file($this->xml_path, array('timeout' => 172800));
    }

    public function remove_data_object_of_certain_element_value($field, $value, $xml_string)
    {
        /* e.g.
            remove_data_object_of_certain_element_value("mimeType", "audio/x-wav", $xml);
            remove_data_object_of_certain_element_value("dataType", "http://purl.org/dc/dcmitype/StillImage", $xml);
            remove_data_object_of_certain_element_value("subject", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology", $xml);
            valid elements to handle are those without namespace e.g. :
            [dataType], [mimeType], [license], [subject]
        */
        $xml = simplexml_load_string($xml_string);
        debug("remove_data_object_of_certain_element_value " . count($xml->taxon) . "-- please wait...");
        $t = -1;
        foreach($xml->taxon as $taxon)
        {
            $t++;
            $obj = -1;
            foreach($taxon->dataObject as $dataObject)
            {
                $obj++;
                $do = self::get_dataObject_namespace($field, $dataObject);
                $use_field = self::get_field_name($field);
                if(@$do->$use_field == $value) 
                {
                    debug("this <dataObject> will not be ingested -- $use_field = $value");
                    $xml->taxon[$t]->dataObject[$obj] = NULL;
                }
            }
        }
        debug("remove_data_object_of_certain_element_value -- done.");
        $xml = str_replace("<dataObject></dataObject>", "", $xml->asXML());
        return $xml;
    }

    public function replace_data_object_element_value($field, $old_value, $new_value, $xml_string, $compare = true)
    {
        /* e.g. 
            replace_data_object_element_value("mimeType", "audio/wav", "audio/x-wav", $xml);
            replace_data_object_element_value("dcterms:modified", "", "07/13/1972", $xml, false);
        */
        $xml = simplexml_load_string($xml_string);
        debug("replace_data_object_element_value " . count($xml->taxon) . "-- please wait...");
        foreach($xml->taxon as $taxon)
        {
            foreach($taxon->dataObject as $dataObject)
            {
                $do = self::get_dataObject_namespace($field, $dataObject);
                $use_field = self::get_field_name($field);
                if($compare) 
                {
                    if(@$do->$use_field == $old_value) $do->$use_field = $new_value;
                }
                else $do->$use_field = $new_value;
            }
        }
        debug("replace_data_object_element_value -- done.");
        return $xml->asXML();
    }

    public function replace_data_object_element_value_with_condition($field, $old_value, $new_value, $xml_string, $condition_field, $condition_value, $compare = true)
    {
        /* e.g. 
            This will replace all <mimeType> elements with original value of "image/gif" to "image/jpeg" only if <dc:title> is "JPEG Images"
            replace_data_object_element_value_with_condition("mimeType", "image/gif", "image/jpeg", $xml, "dc:title", "JPEG Images");
            
            This will replace all <subject> elements to "#Distribution" only if <dc:title> is "Distribution Information".
            replace_data_object_element_value_with_condition("subject", "", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", $xml, "dc:title", "Distribution Information", false);
        */
        $xml = simplexml_load_string($xml_string);
        debug("replace_data_object_element_value_with_condition " . count($xml->taxon) . "-- please wait...");
        foreach($xml->taxon as $taxon)
        {
            foreach($taxon->dataObject as $dataObject)
            {
                $do = self::get_dataObject_namespace($field, $dataObject);
                $use_field = self::get_field_name($field);
                if($compare) 
                {
                    if(@$do->$use_field == $old_value) 
                    {
                        $condition_do = self::get_dataObject_namespace($condition_field, $dataObject);
                        $use_condition_field = self::get_field_name($condition_field);
                        if(trim(@$condition_do->$use_condition_field) == $condition_value) $do->$use_field = $new_value;
                    }
                }
                else
                {
                    $condition_do = self::get_dataObject_namespace($condition_field, $dataObject);
                    $use_condition_field = self::get_field_name($condition_field);
                    if(trim(@$condition_do->$use_condition_field) == $condition_value) $do->$use_field = $new_value;
                }
            }
        }
        debug("replace_data_object_element_value_with_condition -- done.");
        return $xml->asXML();
    }

    function get_dataObject_namespace($field, $dataObject)
    {
        if(substr($field,0,3) == "dc:")             return $dataObject->children("http://purl.org/dc/elements/1.1/");
        elseif(substr($field,0,8) == "dcterms:")    return $dataObject->children("http://purl.org/dc/terms/");
        else                                        return $dataObject;
    }

    function get_field_name($field)
    {
        if(substr($field,0,3) == "dc:" || substr($field,0,8) == "dcterms:") return str_ireplace(array("dc:", "dcterms:"), "", $field);
        return $field;
    }

    public function save_resource_document($xml)
    {
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . ".xml";
        $OUT = fopen($resource_path, "w");
        fwrite($OUT, $xml);
        fclose($OUT);
    }

    public function delete_taxon_if_no_dataObject($xml_string)
    {
        if($xml = simplexml_load_string($xml_string))
        {
            $i = 0;
            foreach($xml->taxon as $taxon)
            {
                $i++;
                $dc = $taxon->children("http://purl.org/dc/elements/1.1/");
                $dwc = $taxon->children("http://rs.tdwg.org/dwc/dwcore/");
                $dcterms = $taxon->children("http://purl.org/dc/terms/");
                echo "\n " . $dc->identifier . " -- sciname: [" . $dwc->ScientificName ."]";
                if(!$taxon->dataObject)
                {
                    echo " --- deleted \n";
                    unset($dc->identifier);
                    unset($dc->source);
                    unset($dwc->Kingdom);
                    unset($dwc->Phylum);
                    unset($dwc->Class);
                    unset($dwc->Order);
                    unset($dwc->Family);
                    unset($dwc->Genus);
                    unset($dwc->ScientificName);
                    unset($xml->taxon[$i-1]->commonName);
                    unset($xml->taxon[$i-1]->synonym);
                    unset($dcterms->created);
                    unset($dcterms->modified);
                    unset($xml->taxon[$i-1]->reference);
                    unset($xml->taxon[$i-1]->dataObject);
                }
            }
            $xml_string = $xml->asXML();
            $xml_string = preg_replace('/\s*(<[^>]*>)\s*/', '$1', $xml_string); // remove whitespaces
            $xml_string = str_ireplace(array("<taxon></taxon>", "<taxon/>"), "", $xml_string);
            return $xml_string;
        }
    }

}
?>