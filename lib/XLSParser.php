<?php
namespace php_active_record;

class XLSParser
{
    const SUBJECTS = "Associations,Behaviour,Biology,Conservation,ConservationStatus,Cyclicity,Cytology,Description,DiagnosticDescription,
                      Diseases,Dispersal,Distribution,Ecology,Evolution,GeneralDescription,Genetics,Growth,Habitat,Key,Legislation,LifeCycle,LifeExpectancy,
                      LookAlikes,Management,Migration,MolecularBiology,Morphology,Physiology,PopulationBiology,Procedures,Reproduction,RiskStatement,
                      Size,TaxonBiology,Threats,Trends,TrophicStrategy,Uses";
    
    public function convert_sheet_to_array($spreadsheet, $sheet_index_number = NULL, $startRow = NULL, $save_params = false, $sheet_index_name = NULL)
    {
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';
        
        if(!isset($this->open_spreadsheets)) $this->open_spreadsheets = array();
        $temp = explode('.', $spreadsheet); //to avoid E_STRICT warning - only variables can be passed by reference
        $ext = strtolower(end($temp));
        if(isset($this->open_spreadsheets['spreadsheet']))
        {
            $objPHPExcel = $this->open_spreadsheets['spreadsheet'];
        }else
        {
            if    ($ext == "xls") $objReader = \PHPExcel_IOFactory::createReader('Excel5');
            elseif($ext == "xlsx")$objReader = \PHPExcel_IOFactory::createReader('Excel2007'); //memory intensive, slow response
            elseif($ext == "zip") $objReader = \PHPExcel_IOFactory::createReader('Excel2007'); //memory intensive, slow response
            elseif($ext == "csv") $objReader = new \PHPExcel_Reader_CSV();
            if($ext != "csv") $objReader->setReadDataOnly(true);
            $objPHPExcel = $objReader->load($spreadsheet);
            $this->open_spreadsheets['spreadsheet'] = $objPHPExcel;
        }
        if(is_null($sheet_index_number)) {
            if(is_null($sheet_index_name)) $objWorksheet = $objPHPExcel->getActiveSheet();
            else                           $objWorksheet = $objPHPExcel->setActiveSheetIndexByName($sheet_index_name);
        }
        else
        {
            if($sheet_index_number+1 > $objPHPExcel->getSheetCount()) return false;
            $objWorksheet = $objPHPExcel->setActiveSheetIndex($sheet_index_number);
        }
        $highestRow         = $objWorksheet->getHighestRow(); // e.g. 10
        $highestColumn      = $objWorksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = \PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5
        $sheet_label = array();
        $sheet_value = array();
        if(is_null($startRow)) $startRow = 1;
        if($save_params) $FILE = Functions::file_open($save_params['path']."/".$save_params['worksheet_title'].".txt", 'w');
        for ($row = $startRow; $row <= $highestRow; ++$row)
        {
            if($save_params) $saved_row = array();
            for ($col = 0; $col <= $highestColumnIndex; ++$col)
            {
                $cell = self::cell_value($objWorksheet, $col, $row, $ext);
                if($row == $startRow)
                {
                    $sheet_label[] = $cell;
                    if($save_params) $saved_row[] = $cell;
                }
                else
                {
                    $index = trim($sheet_label[$col]);
                    if($index)
                    {
                        if($save_params) $saved_row[] = $cell;
                        else $sheet_value[$index][] = $cell;
                    }
                }
            }
            if($save_params) fwrite($FILE, implode("\t", $saved_row)."\n");
        }
        if($save_params) fclose($FILE);
        return $sheet_value;
    }
    
    public function create_eol_xml($file)
    {
        /* EOL spreadsheet template worksheets
        0 = Contributors
        1 = Attributions
        2 = Text descriptions
        3 = References
        4 = Multimedia
        5 = Taxon Information
        6 = More common names (optional)
        7 = Synonyms
        */
        
        $taxon_info = $this->convert_sheet_to_array($file, 5);
        $GLOBALS['subjects_with_identical_texts'] = self::check_subjects_with_identical_texts($this->convert_sheet_to_array($file, 2));
        
        $text_desc = self::prepare_data($this->convert_sheet_to_array($file, 2), "multiple", "Taxon Name", "Taxon Name", "Reference Code", "Attribution Code", "Contributor Code",
        "Audience", "DateCreated", "DateModified", "Source URL",
        "Associations", "Behaviour", "Biology", "Conservation",
        "ConservationStatus", "Cyclicity", "Cytology", "Description", "DiagnosticDescription", "Diseases", "Dispersal", "Distribution", "Ecology", "Evolution",
        "GeneralDescription", "Genetics", "Growth", "Habitat", "Key", "Legislation", "LifeCycle", "LifeExpectancy", "LookAlikes", "Management", "Migration",
        "MolecularBiology", "Morphology", "Physiology", "PopulationBiology", "Procedures", "Reproduction", "RiskStatement", "Size", "TaxonBiology", "Threats", "Trends",
        "TrophicStrategy", "Uses");
        
        $multimedia     = self::prepare_data($this->convert_sheet_to_array($file, 4, 2), "multiple", "Taxon Name",
        "DateCreated", "DateModified", "Data Type", "MIME Type", "Media URL", "Thumbnail URL", "Source URL", "Caption", "Language", "Audience", "Location", "Latitude",
        "Longitude", "Altitude", "Attribution Code", "Contributor Code", "Reference Code");
        
        $references     = self::prepare_data($this->convert_sheet_to_array($file, 3), "single", "Reference Code", "Bibliographic Citation", "URL", "ISBN");
        $attributions   = self::prepare_data($this->convert_sheet_to_array($file, 1), "single", "Code", "License", "RightsStatement", "RightsHolder", "BibliographicCitation");
        $contributors   = self::prepare_data($this->convert_sheet_to_array($file, 0), "single", "Code", "Display Name", "Role", "Logo URL", "Homepage", "Family Name", "Given Name", "Email", "Telephone", "Mailing Address");
        $common_names   = self::prepare_data($this->convert_sheet_to_array($file, 6), "multiple", "Taxon Name", "Common Name", "Language");
        $synonyms       = self::prepare_data($this->convert_sheet_to_array($file, 7), "multiple", "Taxon Name", "Synonym", "Relationship");
        
        $do_details = array("references" => $references,
                            "attributions" => $attributions,
                            "contributors" => $contributors);
        
        $eol_xml = self::create_specialist_project_xml($taxon_info, $text_desc, $multimedia, $common_names, $synonyms, $do_details);
        return $eol_xml;
    }

    function check_subjects_with_identical_texts($text_descriptions_sheet)
    {
        $subjects_with_identical_texts = array();
        $subjects = explode(",",self::SUBJECTS);
        foreach($subjects as $subject)
        {
            $arr_text = @array_filter($text_descriptions_sheet[$subject]);
            if(!$arr_text) continue;
            $arr_text_unique = array_unique($arr_text);
            if(sizeof($arr_text) != sizeof($arr_text_unique)) $subjects_with_identical_texts[] = $subject;
        }
        return $subjects_with_identical_texts;
    }

    public function create_specialist_project_xml($taxon_info, $text_desc = NULL, $multimedia = NULL, $common_names = NULL, $synonyms = NULL, $do_details = NULL)
    {
        $schema_taxa = array();
        $used_taxa = array();
        $i = 0;
        $references = $do_details['references'];
        
        //formerly Scientific Name
        foreach($taxon_info["Scientific Name"] as $sciname)
        {
            if(!trim($sciname))
            {
                $i++;
                continue;
            }
            
            $taxon_identifier = self::format($sciname);
            if(@$used_taxa[$taxon_identifier]) $taxon_parameters = $used_taxa[$taxon_identifier];
            else
            {
                $taxon_parameters = array();
                $taxon_parameters["identifier"]     = self::format(@$taxon_info["ID"][$i]);
                $taxon_parameters["kingdom"]        = ucfirst(self::format(@$taxon_info["Kingdom"][$i]));
                $taxon_parameters["phylum"]         = ucfirst(self::format(@$taxon_info["Phylum"][$i]));
                $taxon_parameters["class"]          = ucfirst(self::format(@$taxon_info["Class"][$i]));
                $taxon_parameters["order"]          = ucfirst(self::format(@$taxon_info["Order"][$i]));
                $taxon_parameters["family"]         = ucfirst(self::format(@$taxon_info["Family"][$i]));
                $taxon_parameters["genus"]          = ucfirst(self::format(@$taxon_info["Genus"][$i]));
                $taxon_parameters["scientificName"] = ucfirst(self::format(@$taxon_info["Scientific Name"][$i]));
                $taxon_parameters["source"]         = trim(self::format(@$taxon_info["Source URL"][$i]));
        
                //start taxon reference
                $taxon_parameters["references"] = array();
                $refs = array();
                $reference_code = self::format(@$taxon_info["Reference Code"][$i]);
                $reference_code = str_ireplace(" ", "", $reference_code);
                foreach(explode(",", $reference_code) as $ref_code)
                {
                    $referenceParameters = array();
                    $referenceParameters["fullReference"] = self::format(@$references[$ref_code]['Bibliographic Citation']);
                    if(@$references[$ref_code]['URL'] || @$references[$ref_code]['ISBN'])
                    {
                        $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url" , "value" => self::format(@$references[$ref_code]['URL'])));
                        $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "isbn" , "value" => self::format(@$references[$ref_code]['ISBN'])));
                    }
                    $refs[] = new \SchemaReference($referenceParameters);
                }
                $taxon_parameters["references"] = $refs;
                //end taxon reference
                
                //start preferred common names
                $taxon_parameters["commonNames"] = array();
                if(@$taxon_info["Preferred Common Name"][$i])
                {
                    $taxon_parameters["commonNames"][] = new \SchemaCommonName(array("name" => self::format(@$taxon_info["Preferred Common Name"][$i]), "language" => self::format(@$taxon_info["Language of Common Name"][$i])));
                }
                //end preferred common names
                
                //start common names
                if(@$common_names[$taxon_identifier])
                {
                    foreach(@$common_names[$taxon_identifier] as $rec)
                    {
                        if($rec)$taxon_parameters["commonNames"][] = new \SchemaCommonName(array("name" => self::format($rec['Common Name']), "language" => self::format($rec['Language'])));
                    }
                }
                //end common names
                
                //start synonyms
                $taxon_params["synonyms"] = array();
                if(@$synonyms[$taxon_identifier])
                {
                    foreach(@$synonyms[$taxon_identifier] as $rec)
                    {
                        $taxon_parameters["synonyms"][] = new \SchemaSynonym(array("synonym" => self::format($rec['Synonym']), "relationship" => self::format($rec['Relationship'])));
                    }
                }
                //end synonyms
                
                //start data objects
                $dataObjects = array();
                $text_desc_title = $text_desc[''];
                /* to get the title e.g. 'Associations': $text_desc_title[0]['Associations'] */
                $temp = self::prepare_text_dataObject(@$text_desc[$taxon_identifier], $do_details, $text_desc_title);
                $dataObjects = array_merge($dataObjects, $temp);
                $temp = self::prepare_media_dataObject(@$multimedia[$taxon_identifier], $do_details);
                $dataObjects = array_merge($dataObjects, $temp);
                foreach($dataObjects as $object)
                {
                    $taxon_parameters["dataObjects"][] = new \SchemaDataObject($object);
                    unset($object);
                }
                //end data objects
                
                @$used_taxa[$taxon_identifier] = $taxon_parameters;
            }
            @$used_taxa[$taxon_identifier] = $taxon_parameters;
            $i++;
        }
        foreach($used_taxa as $taxon_parameters)
        {
            $schema_taxa[] = new \SchemaTaxon($taxon_parameters);
        }
        return \SchemaDocument::get_taxon_xml($schema_taxa);
    }
    
    function prepare_text_dataObject($text_desc, $do_details, $text_desc_title)
    {
        if(!$text_desc) return array();
        $dataObjects = array();
        $subjects = explode(",", self::SUBJECTS);
        foreach($subjects as $subject)
        {
            foreach($text_desc as $do)
            {
                if(@$do[$subject]) $dataObjects[] = self::get_data_object($do, $subject, $do_details, $text_desc_title); //e.g. $do['Associations'];
            }
        }
        return $dataObjects;
    }
    
    function prepare_media_dataObject($multimedia, $do_details)
    {
        $dataObjects = array();
        if($multimedia)
        {
            foreach($multimedia as $do)
            {
                if(@$do['Media URL']) $dataObjects[] = self::get_data_object($do, NULL, $do_details);
            }
        }
        return $dataObjects;
    }
    
    function get_data_object($do, $subject, $do_details, $text_desc_title = NULL)
    {
        $references = $do_details['references'];
        $attributions = $do_details['attributions'];
        $contributors = $do_details['contributors'];

        $dataObjectParameters = array();
        $dataObjectParameters["identifier"] = "";
        $dataObjectParameters["dataType"] = self::get_DataType(self::format(@$do['Data Type']));
        $dataObjectParameters["mimeType"] = self::format(@$do['MIME Type']);

        if(is_null($subject)) $desc = self::format(@$do['Caption']); //multimedia
        else                  $desc = self::format($do[$subject]);   //text description        
        $dataObjectParameters["description"] = $desc;
        
        //start title
        $title = self::format(@$text_desc_title[0][$subject]);
        if($title != "Title if different") $dataObjectParameters["title"] = $title;        
        //end title
        
        //start subject
        if($subject)
        {
            $dataObjectParameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#" . $subject;
            $dataObjectParameters["subjects"][] = new \SchemaSubject($subjectParameters);
        }
        //end subject
        
        //start reference
        $dataObjectParameters["references"] = array();
        $refs = array();
        $reference_code = self::format($do['Reference Code']);
        $reference_code = str_ireplace(" ", "", $reference_code);
        foreach(explode(",", $reference_code) as $ref_code)
        {
            $referenceParameters = array();
            $referenceParameters["fullReference"] = self::format(@$references[$ref_code]['Bibliographic Citation']);
            if(@$references[$ref_code]['URL'] || @$references[$ref_code]['ISBN'])
            {
                $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url", "value" => self::format(@$references[$ref_code]['URL'])));
                $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "isbn" , "value" => self::format(@$references[$ref_code]['ISBN'])));
            }
            $refs[] = new \SchemaReference($referenceParameters);
        }
        $dataObjectParameters["references"] = $refs;
        //end reference
        
        //start contributors
        $agents = array();        
        $contributor_code = self::format($do['Contributor Code']);
        $contributor_code = str_ireplace(" ", "", $contributor_code);        
        foreach(explode(",", $contributor_code) as $code)
        {
            $agentParameters = array();
            $agentParameters["role"]     = self::format(@$contributors[$code]['Role']);
            $agentParameters["homepage"] = self::format(@$contributors[$code]['Homepage']);
            $agentParameters["logoURL"]  = self::format(@$contributors[$code]['Logo URL']);
            $agentParameters["fullName"] = self::format(@$contributors[$code]['Display Name']);
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $dataObjectParameters["agents"] = $agents;
        //end contributors
        
        //start attributions
        $attribution_code = self::format($do['Attribution Code']);
        $dataObjectParameters["license"]                = self::get_license(self::format(@$attributions[$attribution_code]['License']));
        $dataObjectParameters["rightsHolder"]           = self::format(@$attributions[$attribution_code]['RightsHolder']);
        $dataObjectParameters["rights"]                 = self::format(@$attributions[$attribution_code]['RightsStatement']);
        $dataObjectParameters["bibliographicCitation"]  = self::format(@$attributions[$attribution_code]['BibliographicCitation']);
        //end attributions

        //start audience
        $dataObjectParameters["audiences"] = array();
        $audienceParameters = array();
        if($do['Audience'])
        {
            $audienceParameters["label"] = self::format($do['Audience']);
            $dataObjectParameters["audiences"][] = new \SchemaAudience($audienceParameters);
        }
        //end audience
        
        $dataObjectParameters["source"]        = self::format(@$do['Source URL']);
        $dataObjectParameters["mediaURL"]      = str_replace(" ", "%20", self::format(@$do['Media URL']));
        $dataObjectParameters["thumbnailURL"]  = str_replace(" ", "%20", self::format(@$do['Thumbnail URL']));
        $dataObjectParameters["location"]      = self::format(@$do['Location']);
        return $dataObjectParameters;
    }

    function prepare_data($sheet, $number, $index,
    $fld1 = NULL, $fld2 = NULL, $fld3 = NULL, $fld4 = NULL, $fld5 = NULL, $fld6 = NULL, $fld7 = NULL, $fld8 = NULL, $fld9 = NULL, $fld10 = NULL,
    $fld11 = NULL, $fld12 = NULL, $fld13 = NULL, $fld14 = NULL, $fld15 = NULL, $fld16 = NULL, $fld17 = NULL, $fld18 = NULL, $fld19 = NULL, $fld20 = NULL,
    $fld21 = NULL, $fld22 = NULL, $fld23 = NULL, $fld24 = NULL, $fld25 = NULL, $fld26 = NULL, $fld27 = NULL, $fld28 = NULL, $fld29 = NULL, $fld30 = NULL,
    $fld31 = NULL, $fld32 = NULL, $fld33 = NULL, $fld34 = NULL, $fld35 = NULL, $fld36 = NULL, $fld37 = NULL, $fld38 = NULL, $fld39 = NULL, $fld40 = NULL,
    $fld41 = NULL, $fld42 = NULL, $fld43 = NULL, $fld44 = NULL, $fld45 = NULL)
    {
        $data = array();
        $i = 0;
        if(@$sheet[$index])
        {
            foreach(@$sheet[$index] as $taxon_name)
            {
                $taxon_name = self::format($taxon_name);
                $temp = array($fld1 => @$sheet[$fld1][$i], $fld2 => @$sheet[$fld2][$i],
                              $fld3 => @$sheet[$fld3][$i], $fld4 => @$sheet[$fld4][$i],
                              $fld5 => @$sheet[$fld5][$i], $fld6 => @$sheet[$fld6][$i],
                              $fld7 => @$sheet[$fld7][$i], $fld8 => @$sheet[$fld8][$i],
                              $fld9 => @$sheet[$fld9][$i],
                              $fld10 => @$sheet[$fld10][$i], $fld11 => @$sheet[$fld11][$i],
                              $fld12 => @$sheet[$fld12][$i], $fld13 => @$sheet[$fld13][$i],
                              $fld14 => @$sheet[$fld14][$i], $fld15 => @$sheet[$fld15][$i],
                              $fld16 => @$sheet[$fld16][$i], $fld17 => @$sheet[$fld17][$i],
                              $fld18 => @$sheet[$fld18][$i], $fld19 => @$sheet[$fld19][$i],
                              $fld20 => @$sheet[$fld20][$i], $fld21 => @$sheet[$fld21][$i],
                              $fld22 => @$sheet[$fld22][$i], $fld23 => @$sheet[$fld23][$i],
                              $fld24 => @$sheet[$fld24][$i], $fld25 => @$sheet[$fld25][$i],
                              $fld26 => @$sheet[$fld26][$i], $fld27 => @$sheet[$fld27][$i],
                              $fld28 => @$sheet[$fld28][$i], $fld29 => @$sheet[$fld29][$i],
                              $fld30 => @$sheet[$fld30][$i], $fld31 => @$sheet[$fld31][$i],
                              $fld32 => @$sheet[$fld32][$i], $fld33 => @$sheet[$fld33][$i],
                              $fld34 => @$sheet[$fld34][$i], $fld35 => @$sheet[$fld35][$i],
                              $fld36 => @$sheet[$fld36][$i], $fld37 => @$sheet[$fld37][$i],
                              $fld38 => @$sheet[$fld38][$i], $fld39 => @$sheet[$fld39][$i],
                              $fld40 => @$sheet[$fld40][$i], $fld41 => @$sheet[$fld41][$i],
                              $fld42 => @$sheet[$fld42][$i], $fld43 => @$sheet[$fld43][$i],
                              $fld44 => @$sheet[$fld44][$i], $fld45 => @$sheet[$fld45][$i]);
                if($number == "multiple") $data[$taxon_name][] = $temp;
                else                      $data[$taxon_name] = $temp;
                $i++;
            }
        }
        return $data;
    }

    function cell_value($obj, $col, $row, $ext)
    {
        $cell = $obj->getCellByColumnAndRow($col, $row)->getValue();
        if($ext == "csv") return $cell;
        if(self::is_formula($cell)) return $obj->getCellByColumnAndRow($col, $row)->getCalculatedValue();
        else return $cell;
    }
    
    function is_formula($cell)
    {
        if(substr($cell, 0, 1) == "=")
        {
            /* to trap problems in a cell, display $cell here then exit */
            return true;
        }
        else return false;
    }
    
    function format($str)
    {
        if(!$str) return $str;
        $str = trim($str);
        if(!$str) return $str;
        $str = utf8_encode(utf8_decode($str));
        $str = self::fix_chars($str);
        $str = utf8_encode(utf8_decode($str));
        return $str;
    }

    function fix_chars($s)
    {
        /*
        $s = str_ireplace(utf8_decode('“'), "'", $s);
        $s = str_ireplace(utf8_decode('”'), "'", $s);
        $s = str_ireplace(utf8_decode('–'), "-", $s);
        $s = str_ireplace(utf8_decode('’'), "'", $s);
        $s = str_ireplace(utf8_decode('µ'), utf8_encode("&#181;"), $s);
        */
        
        static $entities_to_decode = array("&nbsp;", "&iexcl;", "&cent;", "&pound;", "&curren;", "&yen;", "&brvbar;", "&sect;",
                    "&uml;", "&copy;", "&ordf;", "&laquo;", "&not;", "&shy;", "&reg;", "&hibar;",
                    "&deg;", "&plusmn;", "&sup2;", "&sup3;", "&acute;", "&micro;", "&para;", "&middot;",
                    "&cedil;", "&sup1;", "&ordm;", "&raquo;", "&frac14;", "&frac12;", "&frac34;", "&iquest;",
                    "&agrave;", "&aacute;", "&acirc;", "&atilde;", "&auml;", "&aring;", "&aelig;", "&ccedil;",
                    "&egrave;", "&eacute;", "&ecirc;", "&euml;", "&igrave;", "&iacute;", "&icirc;", "&iuml;",
                    "&eth;", "&ntilde;", "&ograve;", "&oacute;", "&ocirc;", "&otilde;", "&ouml;", "&times;",
                    "&oslash;", "&igrave;", "&uacute;", "&ucirc;", "&uuml;", "&yacute;", "&thorn;", "&szlig;",
                    "&agrave;", "&aacute;", "&acirc;", "&atilde;", "&auml;", "&aring;", "&aelig;", "&ccedil;",
                    "&egrave;", "&eacute;", "&ecirc;", "&euml;", "&igrave;", "&iacute;", "&icirc;", "&iuml;",
                    "&eth;", "&ntilde;", "&ograve;", "&oacute;", "&ocirc;", "&otilde;", "&ouml;", "&divide;",
                    "&oslash;", "&ugrave;", "&uacute;", "&ucirc;", "&uuml;", "&yacute;", "&thorn;", "&yuml;");
        $entities_not_replaced = array();
        while(preg_match("/(&[a-z0-9]{3,7};)/ims", $s, $arr))
        {
            if(in_array($arr[1], $entities_to_decode)) $s = str_replace($arr[1], html_entity_decode($arr[1]), $s);
            else
            {
                $s = str_replace($arr[1], "|REPLACED_". count($entities_not_replaced) ."|", $s);
                $entities_not_replaced[] = $arr[1];
            }
        }
        while(preg_match("/(\|REPLACED_([0-9]+)\|)/ims", $s, $arr))
        {
            $s = str_replace($arr[1], $entities_not_replaced[$arr[2]], $s);
        }
        return $s;
    }

    function get_license($license)
    {
        switch ($license)
        {   case "Public Domain":   $license = 'http://creativecommons.org/licenses/publicdomain/'; break;
            case "CC-BY":           $license = 'http://creativecommons.org/licenses/by/3.0/'; break;
            case "CC-BY-NC":        $license = 'http://creativecommons.org/licenses/by-nc/3.0/'; break;
            case "CC-BY-SA":        $license = 'http://creativecommons.org/licenses/by-sa/3.0/'; break;
            case "CC-BY-NC-SA":     $license = 'http://creativecommons.org/licenses/by-nc-sa/3.0/'; break;
            default:                $license = '';
        }
        return $license;
    }
    
    function get_DataType($datatype)
    {
        switch ($datatype)
        {   case "Video":   $datatype = 'http://purl.org/dc/dcmitype/MovingImage'; break;
            case "Sound":   $datatype = 'http://purl.org/dc/dcmitype/Sound'; break;
            case "Image":   $datatype = 'http://purl.org/dc/dcmitype/StillImage'; break;
            case "Text":    $datatype = 'http://purl.org/dc/dcmitype/Text'; break;
            default:        $datatype = 'http://purl.org/dc/dcmitype/Text';
        }
        return $datatype;
    }
}
?>