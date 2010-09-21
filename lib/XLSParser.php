<?php
class XLSParser
{    
    public function convert_sheet_to_array($spreadsheet,$sheet=NULL,$startRow=NULL)
    {
        require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';        
        $ext = end(explode('.', $spreadsheet));
        if    ($ext == "xls") $objReader = PHPExcel_IOFactory::createReader('Excel5');
        elseif($ext == "xlsx")$objReader = PHPExcel_IOFactory::createReader('Excel2007'); //memory intensive, slow response        
        elseif($ext == "csv") $objReader = new PHPExcel_Reader_CSV();
        $objPHPExcel = $objReader->load($spreadsheet);        
        if($ext != "csv")$objReader->setReadDataOnly(true);                
        if(is_null($sheet)) $objWorksheet = $objPHPExcel->getActiveSheet();             
        else                $objWorksheet = $objPHPExcel->setActiveSheetIndex($sheet);         
        $highestRow = $objWorksheet->getHighestRow(); // e.g. 10
        $highestColumn = $objWorksheet->getHighestColumn(); // e.g 'F'
        $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn); // e.g. 5        
        $arr_label = array();
        $arr_value = array();     
        if(is_null($startRow))$startRow=1;        
        for ($row = $startRow; $row <= $highestRow; ++$row) 
        {
            for ($col = 0; $col <= $highestColumnIndex; ++$col) 
            {
                $cell = self::cell_value($objWorksheet, $col, $row);                       
                if($row==$startRow) $arr_label[]=$cell;
                else
                {
                    $index = $arr_label[$col];
                    if($index)$arr_value[$index][]=$cell;            
                }            
            }
        }
        return $arr_value;    
    }       
    
    public function create_eol_xml($parser,$file)
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
        $taxon_info = $parser->convert_sheet_to_array($file,5);                          
        
        $text_desc      = self::prepare_data($parser->convert_sheet_to_array($file,2),"multiple","Taxon Name","Reference Code","Attribution Code","Contributor Code",        
        "Audience","DateCreated","DateModified","Associations","Behaviour","Biology","Conservation",	
        "ConservationStatus","Cyclicity","Cytology","Description","DiagnosticDescription","Diseases","Dispersal","Distribution","Ecology","Evolution",
        "GeneralDescription","Genetics","Growth","Habitat","Key","Legislation","LifeCycle","LifeExpectancy","LookAlikes","Management","Migration",
        "MolecularBiology","Morphology","Physiology","PopulationBiology","Procedures","Reproduction","RiskStatement","Size","TaxonBiology","Threats","Trends",
        "TrophicStrategy","Uses");        
        
        $multimedia     = self::prepare_data($parser->convert_sheet_to_array($file,4,2),"multiple","Taxon Name",
        "DateCreated","DateModified","Data Type","MIME Type","Media URL","Thumbnail URL","Source URL","Caption","Language","Audience","Location","Latitude",	
        "Longitude","Altitude","Attribution Code","Contributor Code","Reference Code");        
        
        $references     = self::prepare_data($parser->convert_sheet_to_array($file,3),"single","Reference Code","Bibliographic Citation","URL","ISBN");
        $attributions   = self::prepare_data($parser->convert_sheet_to_array($file,1),"single","Code","License","RightsStatement","RightsHolder","BibliographicCitation");
        $contributors   = self::prepare_data($parser->convert_sheet_to_array($file,0),"single","Code","Display Name","Role","Logo URL","Homepage","Family Name","Given Name","Email","Telephone","Mailing Address");
        $common_names   = self::prepare_data($parser->convert_sheet_to_array($file,6),"multiple","Taxon Name","Common Name","Language");        
        $synonyms       = self::prepare_data($parser->convert_sheet_to_array($file,7),"multiple","Taxon Name","Synonym","Relationship");        
        
        $do_details=array("references"=>$references,
                          "attributions"=>$attributions,
                          "contributors"=>$contributors
                         );
        
        $eol_xml = self::create_specialist_project_xml($taxon_info,$text_desc,$multimedia,$common_names,$synonyms,$do_details);                
        return $eol_xml;
    }

    public function create_specialist_project_xml($taxon_info,$text_desc=NULL,$multimedia=NULL,$common_names=NULL,$synonyms=NULL,$do_details=NULL)
    {
        $schema_taxa = array();
        $used_taxa = array();
        $i=0;                
        $references = $do_details['references'];        
        foreach($taxon_info["Scientific Name"] as $sciname)
        {
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
                $refs=array();
                foreach(explode(",", self::format(@$taxon_info["Reference Code"][$i])) as $ref_code)
                {
                    $referenceParameters = array();
                    $referenceParameters["fullReference"] = self::format(@$references[$ref_code]['Bibliographic Citation']);
                    if(@$references[$ref_code]['URL'] || @$references[$ref_code]['ISBN'])
                    {
                        $referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => self::format(@$references[$ref_code]['URL'])));                                
                        $referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "isbn" , "value" => self::format(@$references[$ref_code]['ISBN'])));                                
                    }
                    $refs[] = new SchemaReference($referenceParameters);
                }
                $taxon_parameters["references"] = $refs;
                //end taxon reference
                
                //start preferred common names
                $taxon_parameters["commonNames"] = array();
                if(@$taxon_info["Preferred Common Name"][$i])
                {
                    $taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => self::format(@$taxon_info["Preferred Common Name"][$i]), "language" => self::format(@$taxon_info["Language of Common Name"][$i])));
                }                                
                //end preferred common names
                
                //start common names
                if(@$common_names[$taxon_identifier])
                {
                    foreach(@$common_names[$taxon_identifier] as $rec)
                    {            
                        if($rec)$taxon_parameters["commonNames"][] = new SchemaCommonName(array("name" => self::format($rec['Common Name']), "language" => self::format($rec['Language'])));
                    }
                }                                
                //end common names
                
                //start synonyms
                $taxon_params["synonyms"] = array();
                if(@$synonyms[$taxon_identifier])
                {
                    foreach(@$synonyms[$taxon_identifier] as $rec)
                    {
                        $taxon_parameters["synonyms"][] = new SchemaSynonym(array("synonym" => self::format($rec['Synonym']), "relationship" => self::format($rec['Relationship'])));
                    }                
                }
                //end synonyms

                
                // ==== start data objects
                $dataObjects = array();
                $temp = self::prepare_text_dataObject(@$text_desc[$taxon_identifier],$do_details);
                $dataObjects = array_merge($dataObjects,$temp);                                    
                $temp = self::prepare_media_dataObject(@$multimedia[$taxon_identifier],$do_details);
                $dataObjects = array_merge($dataObjects,$temp);                                    
                foreach($dataObjects as $v)
                {
                    $taxon_parameters["dataObjects"][] = new SchemaDataObject($v);
                    unset($v);
                }
                // ==== end data objects
                
                @$used_taxa[$taxon_identifier] = $taxon_parameters;
            }            
            @$used_taxa[$taxon_identifier] = $taxon_parameters;                                
            $i++;                    
        }                
        foreach($used_taxa as $taxon_parameters)
        {
            $schema_taxa[] = new SchemaTaxon($taxon_parameters);
        }
        //SchemaDocument::print_taxon_xml($schema_taxa);
        return SchemaDocument::get_taxon_xml($schema_taxa);        
    }
    
    private function prepare_text_dataObject($arr,$do_details)
    {          
        $dataObjects = array();    
        $subjects=array("Associations","Behaviour","Biology","Conservation",	
        "ConservationStatus","Cyclicity","Cytology","Description","DiagnosticDescription","Diseases","Dispersal","Distribution","Ecology","Evolution",
        "GeneralDescription","Genetics","Growth","Habitat","Key","Legislation","LifeCycle","LifeExpectancy","LookAlikes","Management","Migration",
        "MolecularBiology","Morphology","Physiology","PopulationBiology","Procedures","Reproduction","RiskStatement","Size","TaxonBiology","Threats","Trends",
        "TrophicStrategy","Uses");            
        foreach($subjects as $subject)
        {
            if($arr)
            {
                foreach($arr as $do)
                {
                    if(@$do[$subject])$dataObjects[] = self::get_data_object($do,$subject,$do_details); //e.g. $do['Associations'];
                }
            }
        }
        return $dataObjects;        
    }
    
    private function prepare_media_dataObject($arr,$do_details)
    {          
        $dataObjects = array();
        if($arr)
        {
            foreach($arr as $do)
            {
                if(@$do['Media URL'])
                {                
                    $dataObjects[] = self::get_data_object($do,NULL,$do_details);            
                }
            }
        }
        return $dataObjects;        
    }
    
    private function get_data_object($do,$subject,$do_details)
    {
        $references = $do_details['references'];
        $attributions = $do_details['attributions'];        
        $contributors = $do_details['contributors'];                
   
        $dataObjectParameters = array();
        $dataObjectParameters["identifier"] = "";
        $dataObjectParameters["dataType"]   = self::get_DataType(self::format(@$do['Data Type']));
        $dataObjectParameters["mimeType"]   = self::format(@$do['MIME Type']);

        if(is_null($subject))$desc = self::format(@$do['Caption']);   //multimedia
        else                 $desc = self::format($do[$subject]);   //text description
        
        $dataObjectParameters["description"] = $desc;
        
        //start subject
        if($subject)
        {
            $dataObjectParameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#" . $subject;            
            $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
        }
        //end subject
        
        //start reference
        $dataObjectParameters["references"] = array();        
        $refs=array();
        foreach(explode(",", self::format($do['Reference Code'])) as $ref_code)
        {
            $referenceParameters = array();
            $referenceParameters["fullReference"] = self::format(@$references[$ref_code]['Bibliographic Citation']);
            if(@$references[$ref_code]['URL'] || @$references[$ref_code]['ISBN'])
            {
                $referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => self::format(@$references[$ref_code]['URL'])));                                
                $referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "isbn" , "value" => self::format(@$references[$ref_code]['ISBN'])));                                
            }            
            $refs[] = new SchemaReference($referenceParameters);
        }
        $dataObjectParameters["references"] = $refs;
        //end reference
        
        //start contributors
        $agents = array();
        foreach(explode(",", self::format($do['Contributor Code'])) as $contributor_code)
        {  
            $agentParameters = array();
            $agentParameters["role"]     = self::format(@$contributors[$contributor_code]['Role']);
            $agentParameters["homepage"] = self::format(@$contributors[$contributor_code]['Homepage']);
            $agentParameters["logoURL"]  = self::format(@$contributors[$contributor_code]['Logo URL']);
            $agentParameters["fullName"] = self::format(@$contributors[$contributor_code]['Display Name']);
            $agents[] = new SchemaAgent($agentParameters);
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
            $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
        }
        //end audience
        
        $dataObjectParameters["source"]        = self::format(@$do['Source URL']);
        $dataObjectParameters["mediaURL"]      = self::format(@$do['Media URL']);
        $dataObjectParameters["thumbnailURL"]  = self::format(@$do['Thumbnail URL']);
        $dataObjectParameters["location"]      = self::format(@$do['Location']);
        

        /*
        $dataObjectParameters["created"]       = $do->created;
        $dataObjectParameters["modified"]      = $do->modified;
        */
    
        return $dataObjectParameters;
    }
        

    private function prepare_data($arr,$number,$index,
    $fld1=NULL,$fld2=NULL,$fld3=NULL,$fld4=NULL,$fld5=NULL,$fld6=NULL,$fld7=NULL,$fld8=NULL,$fld9=NULL,$fld10=NULL,
    $fld11=NULL,$fld12=NULL,$fld13=NULL,$fld14=NULL,$fld15=NULL,$fld16=NULL,$fld17=NULL,$fld18=NULL,$fld19=NULL,$fld20=NULL,
    $fld21=NULL,$fld22=NULL,$fld23=NULL,$fld24=NULL,$fld25=NULL,$fld26=NULL,$fld27=NULL,$fld28=NULL,$fld29=NULL,$fld30=NULL,
    $fld31=NULL,$fld32=NULL,$fld33=NULL,$fld34=NULL,$fld35=NULL,$fld36=NULL,$fld37=NULL,$fld38=NULL,$fld39=NULL,$fld40=NULL,
    $fld41=NULL,$fld42=NULL,$fld43=NULL,$fld44=NULL)
    {
        $data=array();
        $i=0;
     
        if(@$arr[$index])   
        {
            foreach(@$arr[$index] as $taxon_name)
            {
                $taxon_name=self::format($taxon_name);
                $temp=array( $fld1=>@$arr[$fld1][$i],$fld2=>@$arr[$fld2][$i],
                             $fld3=>@$arr[$fld3][$i],$fld4=>@$arr[$fld4][$i],
                             $fld5=>@$arr[$fld5][$i],$fld6=>@$arr[$fld6][$i],
                             $fld7=>@$arr[$fld7][$i],$fld8=>@$arr[$fld8][$i],
                             $fld9=>@$arr[$fld9][$i],
                             $fld10=>@$arr[$fld10][$i],$fld11=>@$arr[$fld11][$i],
                             $fld12=>@$arr[$fld12][$i],$fld13=>@$arr[$fld13][$i],
                             $fld14=>@$arr[$fld14][$i],$fld15=>@$arr[$fld15][$i],
                             $fld16=>@$arr[$fld16][$i],$fld17=>@$arr[$fld17][$i],
                             $fld18=>@$arr[$fld18][$i],$fld19=>@$arr[$fld19][$i],
                             $fld20=>@$arr[$fld20][$i],$fld21=>@$arr[$fld21][$i],
                             $fld22=>@$arr[$fld22][$i],$fld23=>@$arr[$fld23][$i],
                             $fld24=>@$arr[$fld24][$i],$fld25=>@$arr[$fld25][$i],
                             $fld26=>@$arr[$fld26][$i],$fld27=>@$arr[$fld27][$i],
                             $fld28=>@$arr[$fld28][$i],$fld29=>@$arr[$fld29][$i],
                             $fld30=>@$arr[$fld30][$i],$fld31=>@$arr[$fld31][$i],
                             $fld32=>@$arr[$fld32][$i],$fld33=>@$arr[$fld33][$i],
                             $fld34=>@$arr[$fld34][$i],$fld35=>@$arr[$fld35][$i],
                             $fld36=>@$arr[$fld36][$i],$fld37=>@$arr[$fld37][$i],
                             $fld38=>@$arr[$fld38][$i],$fld39=>@$arr[$fld39][$i],
                             $fld40=>@$arr[$fld40][$i],$fld41=>@$arr[$fld41][$i],
                             $fld42=>@$arr[$fld42][$i],$fld43=>@$arr[$fld43][$i],
                             $fld44=>@$arr[$fld44][$i]);
                if($number == "multiple")$data[$taxon_name][]=$temp;
                else                     $data[$taxon_name]=$temp;
                $i++;
            }        
        }
        return $data;
    }

    private function cell_value($obj, $col, $row)
    {
        $cell = $obj->getCellByColumnAndRow($col, $row)->getValue();
        if(self::is_formula($cell)) return $obj->getCellByColumnAndRow($col, $row)->getCalculatedValue();
        else return $cell;
    }    
    
    private function is_formula($cell)
    {
        if(substr($cell,0,1)=="=")return true;
        else return false;
    }
    
    private function format($str)
    {
        return utf8_encode(utf8_decode(trim($str)));
    }

    private function get_license($license)
    {   
        switch ($license) 
        {   case "Public Domain":   $license='http://creativecommons.org/licenses/publicdomain/'; break;
            case "CC-BY":           $license='http://creativecommons.org/licenses/by/3.0/'; break;
            case "CC-BY-NC":        $license='http://creativecommons.org/licenses/by-nc/3.0/'; break;
            case "CC-BY-SA":        $license='http://creativecommons.org/licenses/by-sa/3.0/'; break;
            case "CC-BY-NC-SA":     $license='http://creativecommons.org/licenses/by-nc-sa/3.0/'; break;
            default:                $license='';
        }        
        return $license;
    }
    function get_DataType($datatype)
    {   
        switch ($datatype) 
        {   case "Video":    $datatype='http://purl.org/dc/dcmitype/MovingImage'; break;
            case "Sound":    $datatype='http://purl.org/dc/dcmitype/Sound'; break;
            case "Image":    $datatype='http://purl.org/dc/dcmitype/StillImage'; break;
            case "Text":     $datatype='http://purl.org/dc/dcmitype/Text'; break;
            default:         $datatype='http://purl.org/dc/dcmitype/Text';
        }        
        return $datatype;
    }    
}
?>