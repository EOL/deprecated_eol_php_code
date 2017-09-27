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

    public function set_data_object_rating_on_xml_document($expire_seconds = 60*60*24*25) //default expires in 25 days
    {
        $xml_string = self::load_xml_string($expire_seconds);
        if($xml_string === false) return false;
        if(!$xml = simplexml_load_string($xml_string)) return false;
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

    function load_xml_string($expire_seconds)
    {
        $file_contents = "";
        debug("Please wait, downloading resource document...");
        if(preg_match("/^(.*)\.(gz|gzip)$/", $this->xml_path, $arr))
        {
            $path_parts = pathinfo($this->xml_path);
            $filename = $path_parts['basename'];
            $temp_dir = create_temp_dir() . "/";
            debug("temp file path: " . $temp_dir);
            if($local_file = Functions::save_remote_file_to_local($this->xml_path, array('timeout' => 172800, 'cache' => 1, 'expire_seconds' => $expire_seconds)))
            {
                $file_contents = file_get_contents($local_file);

                $temp_file_path = $temp_dir . "/" . $filename;
                $TMP = fopen($temp_file_path, "w");
                fwrite($TMP, $file_contents);
                fclose($TMP);
                shell_exec("gunzip -f $temp_file_path");
                $this->xml_path = $temp_dir . str_ireplace(".gz", "", $filename);
                debug("xml path: " . $this->xml_path);
            }
            else
            {
                debug("Connector terminated. Remote files are not ready.");
                return false;
            }
            echo "\n $temp_dir \n";

            $file_contents = file_get_contents($this->xml_path);
            
            recursive_rmdir($temp_dir); // remove temp dir
            echo ("\n temporary directory removed: [$temp_dir]\n");
            unlink($local_file);
        }
        return $file_contents;
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
        if($xml = simplexml_load_string($xml_string))
        {
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
        else
        {
            echo "\nXML is invalid.\n";
            return $xml_string;
        }
    }

    public function replace_data_object_element_value($field, $old_value, $new_value, $xml_string, $compare = true)
    {
        /* e.g. 
            replace_data_object_element_value("mimeType", "audio/wav", "audio/x-wav", $xml);
            replace_data_object_element_value("dcterms:modified", "", "07/13/1972", $xml, false);
        */
        if($xml = simplexml_load_string($xml_string))
        {
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
        else
        {
            echo "\nXML is invalid.\n";
            return $xml_string;
        }
    }

    public function replace_data_object_element_value_with_condition($field, $old_value, $new_value, $xml_string, $condition_field, $condition_value, $compare = true)
    {
        /* e.g. 
            This will replace all <mimeType> elements with original value of "image/gif" to "image/jpeg" only if <dc:title> is "JPEG Images"
            replace_data_object_element_value_with_condition("mimeType", "image/gif", "image/jpeg", $xml, "dc:title", "JPEG Images");
            
            This will replace all <subject> elements to "#Distribution" only if <dc:title> is "Distribution Information".
            replace_data_object_element_value_with_condition("subject", "", "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", $xml, "dc:title", "Distribution Information", false);
        */
        if($xml = simplexml_load_string($xml_string))
        {
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
        else
        {
            echo "\nXML is invalid.\n";
            return $xml_string;
        }
    }

    function get_dataObject_namespace($field, $dataObject)
    {
        if(substr($field,0,3) == "dc:")             return $dataObject->children("http://purl.org/dc/elements/1.1/");
        elseif(substr($field,0,8) == "dcterms:")    return $dataObject->children("http://purl.org/dc/terms/");
        elseif(substr($field,0,4) == "dwc:")        return $dataObject->children("http://rs.tdwg.org/dwc/dwcore/");
        else                                        return $dataObject;
    }

    function get_field_name($field)
    {
        if(substr($field,0,3) == "dc:" || 
           substr($field,0,8) == "dcterms:" ||
           substr($field,0,4) == "dwc:"
           ) return str_ireplace(array("dc:", "dcterms:", "dwc:"), "", $field);
        return $field;
    }

    public function save_resource_document($xml)
    {
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$resource_path);
          return;
        }
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
        else
        {
            echo "\nXML is invalid.\n";
            return $xml_string;
        }
    }

    public function replace_taxon_element_value_with_condition($field, $old_value, $new_value, $xml_string, $condition_field, $condition_value, $compare = true)
    {
        /* e.g. working well e.g 106.php
            This will replace all <dwc:Class> elements with original value of "Insecta" to "Reptilia" only if <dwc:Order> is "Squamata"
            replace_taxon_element_value_with_condition("dwc:Class", "Insecta", "Reptilia", $xml, "dwc:Order", "Squamata");
        */
        if($xml = simplexml_load_string($xml_string))
        {
            debug("replace_taxon_element_value_with_condition " . count($xml->taxon) . "-- please wait...");
            foreach($xml->taxon as $taxon)
            {
                $t = self::get_dataObject_namespace($field, $taxon);
                $use_field = self::get_field_name($field);
                if($compare)
                {
                    if(@$t->$use_field == $old_value)
                    {
                        $condition_do = self::get_dataObject_namespace($condition_field, $taxon);
                        $use_condition_field = self::get_field_name($condition_field);
                        if(trim(@$condition_do->$use_condition_field) == $condition_value)
                        {
                            $t->$use_field = $new_value; // here is where the assignment operation takes place -- if $compare == true
                        }
                    }
                }
                else
                {
                    $condition_do = self::get_dataObject_namespace($condition_field, $taxon);
                    $use_condition_field = self::get_field_name($condition_field);
                    if(trim(@$condition_do->$use_condition_field) == $condition_value) $t->$use_field = $new_value;
                }
            }
            debug("replace_taxon_element_value_with_condition -- done.");
            return $xml->asXML();
        }
        else
        {
            echo "\nXML is invalid.\n";
            return $xml_string;
        }
    }

    public function replace_taxon_element_value($field, $old_value, $new_value, $xml_string, $compare = true)
    {
        /*
            replace_taxon_element_value("dc:source", "any value", "", $xml);
        */
        if($xml = simplexml_load_string($xml_string))
        {
            debug("replace_taxon_element_value_with_condition " . count($xml->taxon) . "-- please wait...");
            foreach($xml->taxon as $taxon)
            {
                $t = self::get_dataObject_namespace($field, $taxon);
                $use_field = self::get_field_name($field);
                if($compare)
                {
                    if(@$t->$use_field == $old_value) $t->$use_field = $new_value;
                }
                else $t->$use_field = $new_value;
            }
            debug("replace_taxon_element_value_with_condition -- done.");
            return $xml->asXML();
        }
        else
        {
            echo "\nXML is invalid.\n";
            return $xml_string;
        }
    }

}
?>