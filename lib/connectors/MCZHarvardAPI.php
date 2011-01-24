<?php

define("SPECIMEN_DETAIL_URL", "http://mczbase.mcz.harvard.edu/SpecimenDetail.cfm?GUID=");
define("MCZ_TAXON_DETAIL_URL", "http://mczbase.mcz.harvard.edu/TaxonomyResults.cfm?scientific_name=");
//define("REMOTE_CSV", "http://pandanus.eol.org/public/EOL_resource/MCZ_Harvard/MCZimages_small.csv");
define("REMOTE_CSV", "http://digir.mcz.harvard.edu/forEOL/MCZimages.csv");
define("LOCAL_CSV", DOC_ROOT . "tmp/MCZ.csv");       

class MCZHarvardAPI
{
    public static function get_all_taxa()
    {    
        $all_taxa = array();
        $used_collection_ids = array();                          
        self::download_and_put_header_in_csv();
        $urls = array(LOCAL_CSV);
        $taxa_arr = self::compile_taxa($urls);        
                
        //start prepare CSV file
        require_library('XLSParser'); $parser = new XLSParser();                
        $images=self::prepare_table($parser->convert_sheet_to_array(LOCAL_CSV),"multiple","GUID","GUID","MEDIA_ID","MEDIA_URI","MIME_TYPE",        
        "SPEC_LOCALITY","HIGHER_GEOG","TYPESTATUS","PARTS","COLLECTING_METHOD","COLLECTORS","IDENTIFIEDBY","created","LAST_EDIT_DATE",
        "SPECIMENDETAILURL" );                
        print "images: " . sizeof($images) . "<br>\n";
        //end prepare CSV file          
        
        unlink(LOCAL_CSV);
        $i=1; $total=sizeof($taxa_arr);
        foreach($taxa_arr as $taxon)
        {
            print"\n $i of $total";$i++;            
            $taxon_id = $taxon['taxon_id'];                        
            $arr = self::get_MCZHarvard_taxa($taxon,@$images[$taxon_id],$used_collection_ids);
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];                        
            $all_taxa = array_merge($all_taxa,$page_taxa);                                                
        }
        return $all_taxa;
    }
    
    public static function get_MCZHarvard_taxa($taxon,$taxon_images,$used_collection_ids)
    {
        $response = self::search_collections($taxon,$taxon_images);//this will output the raw (but structured) output from the external service
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["sciname"]]) continue;            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;            
            $used_collection_ids[$rec["sciname"]] = true;
        }        
        return array($page_taxa,$used_collection_ids);
    }        
    
    function download_and_put_header_in_csv()
    {
        $first_row="MEDIA_ID,MEDIA_URI,MIME_TYPE,subject,created,CAT_NUM,INSTITUTION_ACRONYM,COLLECTION_CDE,COLLECTION,MINIMUM_ELEVATION,MAXIMUM_ELEVATION,ORIG_ELEV_UNITS,LAST_EDIT_DATE,INDIVIDUALCOUNT,COLL_OBJ_DISPOSITION,COLLECTORS,TYPESTATUS,SEX,PARTS,VERBATIM_DATE,HIGHER_GEOG,CONTINENT_OCEAN,COUNTRY,STATE_PROV,COUNTY,FEATURE,ISLAND,ISLAND_GROUP,QUAD,SEA,SPEC_LOCALITY,MIN_ELEV_IN_M,MAX_ELEV_IN_M,DEC_LAT,DEC_LONG,DATUM,ORIG_LAT_LONG_UNITS,VERBATIMLATITUDE,VERBATIMLONGITUDE,LAT_LONG_REF_SOURCE,COORDINATEUNCERTAINTYINMETERS,GEOREFMETHOD,LAT_LONG_REMARKS,LAT_LONG_DETERMINER,SCIENTIFIC_NAME,IDENTIFIEDBY,MADE_DATE,REMARKS,HABITAT,FULL_TAXON_NAME,PHYLCLASS,KINGDOM,PHYLUM,PHYLORDER,FAMILY,GENUS,SPECIES,SUBSPECIES,INFRASPECIFIC_RANK,AUTHOR_TEXT,IDENTIFICATIONMODIFIER,NOMENCLATURAL_CODE,GUID,BASISOFRECORD,DEPTH_UNITS,MIN_DEPTH,MAX_DEPTH,COLLECTING_METHOD,COLLECTING_SOURCE,DAYOFYEAR,AGE_CLASS,ATTRIBUTES,VERIFICATIONSTATUS,SPECIMENDETAILURL,COLLECTORNUMBER,VERBATIMELEVATION,YEAR,MONTH,DAY\n";
        $csv_body = Functions::get_remote_file_fake_browser(REMOTE_CSV);        
        $fp = fopen(LOCAL_CSV,"w+");        
        fwrite($fp,$first_row);        
        fwrite($fp,$csv_body);        
        fclose($fp);        
    }
    
    function compile_taxa($urls)
    {
        require_library('XLSParser');
        $parser = new XLSParser();
     
        $taxa_arr=array();
        foreach($urls as $url)   
        {            
            $arr=self::prepare_table($parser->convert_sheet_to_array($url),"single",
            "GUID","GUID","SCIENTIFIC_NAME","FULL_TAXON_NAME","PHYLCLASS","KINGDOM","PHYLUM","PHYLORDER","FAMILY","GENUS","SPECIES",
            "SUBSPECIES","INFRASPECIFIC_RANK","AUTHOR_TEXT");            
            foreach($arr as $taxon_id => $rec)
            {
                if(!@$taxa_arr[$taxon_id])
                {
                    $rec["taxon_id"]=$taxon_id;
                    $taxa_arr[$taxon_id]=$rec;                
                }                
            }            
            print"<br>\n";
        }        
        print "taxa: " . sizeof($taxa_arr) . "<br>\n";
        return $taxa_arr;    
    }
    
    function search_collections($taxon,$taxon_images)//this will output the raw (but structured) output from the external service
    {
        $arr=array();        
        $taxon_id = $taxon["taxon_id"];                    
        $response = self::prepare_species_page($taxon,$taxon_images);            
        return $response;//structured array
    }               
        
    function prepare_species_page($taxon,$taxon_images)
    {       
        $arr_scraped=array();
        $arr_photos=array();
        $arr_sciname=array();                        
        $taxon_id = $taxon["taxon_id"];        
        //=============================================================================================================
        $sciname = trim($taxon['SCIENTIFIC_NAME']);
        if(trim($sciname) == "<>") return array();
            
        $agent=array();
        $rights_holder = "";        
        $reference=array();
        $arr_texts=array();
        //======================================================================            
        $arr_photos=array();

        if($arr=$taxon_images)
        {
            foreach($arr as $r)
            {
                if  ( trim(@$r['MEDIA_URI']) != "" )
                { 
                    $mediaURL = str_ireplace(' ', '%20', trim(@$r['MEDIA_URI']));
                    $mimeType = @$r['MIME_TYPE'];                                                        
                    $dataType = "http://purl.org/dc/dcmitype/StillImage";                                                    
                    
                    $location = "";
                    if(trim($r['SPEC_LOCALITY']) != "[Exact locality unknown]") $location = $r['SPEC_LOCALITY'];
                    if($location!="") $location .= ", " . $r['HIGHER_GEOG'];
                    else              $location .= $r['HIGHER_GEOG'];
                    
                    $desc = "";
                    $typestatus = substr($r['TYPESTATUS'],0,stripos($r['TYPESTATUS']," "));
                    if($typestatus)             $desc.=$typestatus.", ";
                    if($r['PARTS'])             $desc.=$r['PARTS'].", ";
                    if($r['COLLECTING_METHOD']) $desc.=$r['COLLECTING_METHOD'].", ";
                    
                    if($r['COLLECTORS'] && trim($r['COLLECTORS']) != "no agent" 
                                        && trim($r['COLLECTORS']) != "Unknown collector"
                                        )
                    {
                        $desc.="collected by " . $r['COLLECTORS'].", ";
                    }
                    
                    if($r['IDENTIFIEDBY'])      $desc.="identified by " . $r['IDENTIFIEDBY'].", ";
                    if($r['GUID'])              $desc.="GUID: " . $r['GUID'].", ";                            

                    $date_created = $r['created'];
                    $date_modified = $r['LAST_EDIT_DATE'];

                    /*
                    For location, print: [SPEC_LOCALITY], [HIGHER_GEOG]
                    For the description, print: [first word of TYPESTATUS], [PARTS], [COLLECTING_METHOD], 
                    collected by [COLLECTORS], identified by [IDENTIFIEDBY], GUID: [GUID] 
                    */
                                                
                    //$dc_source     = MCZ_TAXON_DETAIL_URL . $taxon['SCIENTIFIC_NAME']; --working but replaced by Brendan
                    $dc_source     = $r['SPECIMENDETAILURL'];

                    $path_parts = pathinfo($mediaURL);
                    $dc_identifier = $path_parts['basename']; //$r['MEDIA_ID'];                            
                    
                    $arr_photos[$sciname][] = self::fill_data_object($dc_identifier,$desc,"","",$agent,$rights_holder,$mediaURL,$mimeType,$dataType,$dc_source,$date_created,$date_modified,$location);
                }                                    
            }                    
        }
                        
        if(@$arr_texts[$sciname] || @$arr_photos[$sciname])
        {
            //$dc_source = MCZ_TAXON_DETAIL_URL . $taxon['SCIENTIFIC_NAME'];
            $arr_scraped[]=array("identifier"=>$taxon['GUID'],
                                 "kingdom"=>$taxon['KINGDOM'],
                                 "phylum"=>$taxon['PHYLUM'],
                                 "class"=>$taxon['PHYLCLASS'],
                                 "order"=>$taxon['PHYLORDER'],
                                 "family"=>$taxon['FAMILY'],
                                 "genus"=>$taxon['GENUS'],
                                 "sciname"=>$sciname . " " . $taxon['AUTHOR_TEXT'],
                                 "dc_source"=>$dc_source,   
                                 "texts"=>@$arr_texts[$sciname],
                                 "photos"=>@$arr_photos[$sciname],
                                 "references"=>$reference
                                );                
        }                    
        return $arr_scraped;        
    }
    
    function fill_data_object($dc_identifier,$desc,$subject,$title,$agent,$rights_holder,$mediaURL,$mimeType,$dataType,$dc_source,$date_created,$date_modified,$location)
    {
        $desc = str_ireplace('<a href="/species_images/','<a target="_blank" href="http://www.cmarz.org/species_images/',$desc);
        $desc = str_ireplace('<a href="../species_images/','<a target="_blank" href="http://www.cmarz.org/species_images/',$desc);
        return
            array(
            "identifier"    =>$dc_identifier,
            "mediaURL"      =>$mediaURL,
            "mimeType"      =>$mimeType,                        
            "date_created"  =>$date_created,                        
            "date_modified" =>$date_modified,                        
            "rights"        =>"",                        
            "rights_holder" =>$rights_holder,
            "dataType"      =>$dataType,
            "description"   =>$desc,
            "title"         =>$title,
            "location"      =>$location,
            "dc_source"     =>$dc_source,            
            "subject"       =>$subject,
            "agent"         =>$agent
            );    
    }    
    
    function prepare_table($arr,$entry,$index_key,
        $attr1,$attr2=NULL,$attr3=NULL,$attr4=NULL,$attr5=NULL,$attr6=NULL,$attr7=NULL,$attr8=NULL,$attr9=NULL,$attr10=NULL,
        $attr11=NULL,$attr12=NULL,$attr13=NULL,$attr14=NULL,$attr15=NULL,$attr16=NULL,$attr17=NULL,$attr18=NULL,$attr19=NULL,$attr20=NULL,
        $attr21=NULL,$attr22=NULL,$attr23=NULL,$attr24=NULL,$attr25=NULL,$attr26=NULL        
        )
    {
        $arr_hash=array();
        $i=0;
        foreach(@$arr[$index_key] as $id)
        {
            $temp = array($attr1=>@$arr[$attr1][$i],
                                 $attr2=>@$arr[$attr2][$i],
                                 $attr3=>@$arr[$attr3][$i],
                                 $attr4=>@$arr[$attr4][$i],
                                 $attr5=>@$arr[$attr5][$i],
                                 $attr6=>@$arr[$attr6][$i],
                                 $attr7=>@$arr[$attr7][$i],                                 
                                 $attr8=>@$arr[$attr8][$i],
                                 $attr9=>@$arr[$attr9][$i],
                                 $attr10=>@$arr[$attr10][$i],
                                 $attr11=>@$arr[$attr11][$i],
                                 $attr12=>@$arr[$attr12][$i],
                                 $attr13=>@$arr[$attr13][$i],
                                 $attr14=>@$arr[$attr14][$i],
                                 $attr15=>@$arr[$attr15][$i],
                                 $attr16=>@$arr[$attr16][$i],
                                 $attr17=>@$arr[$attr17][$i],
                                 $attr18=>@$arr[$attr18][$i],
                                 $attr19=>@$arr[$attr19][$i],
                                 $attr20=>@$arr[$attr20][$i],
                                 $attr21=>@$arr[$attr21][$i],
                                 $attr22=>@$arr[$attr22][$i],
                                 $attr23=>@$arr[$attr23][$i],
                                 $attr24=>@$arr[$attr24][$i],
                                 $attr25=>@$arr[$attr25][$i],
                                 $attr26=>@$arr[$attr26][$i]                                 
                                 );
            if    ($entry=="single")    $arr_hash[$id]  =$temp;
            elseif($entry=="multiple")  $arr_hash[$id][]=$temp;
            $i++;
        }            
        return $arr_hash;
    }    

    function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $taxon["identifier"] = $rec["identifier"];                
        $taxon["source"] = $rec["dc_source"];                
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));        
        $taxon["kingdom"] = ucfirst(trim($rec["kingdom"]));
        $taxon["phylum"] = ucfirst(trim($rec["phylum"]));       
        $taxon["class"] = ucfirst(trim($rec["class"]));
        $taxon["order"] = ucfirst(trim($rec["order"]));
        $taxon["family"] = ucfirst(trim($rec["family"]));
        if(@$rec["photos"]) $taxon["dataObjects"] = self::prepare_objects($rec["photos"],@$taxon["dataObjects"],array());
        if(@$rec["texts"])  $taxon["dataObjects"] = self::prepare_objects($rec["texts"],@$taxon["dataObjects"],$rec["references"]);        
        $taxon_object = new SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    function prepare_objects($arr,$taxon_dataObjects,$references)
    {
        $arr_SchemaDataObject=array();        
        if($arr)
        {
            $arr_ref=array();
            $length = sizeof($arr);
            $i=0;
            foreach($arr as $rec)
            {
                $i++;
                /* if($length == $i)$arr_ref = $references;//to add the references to the last dataObject */
                $arr_ref = $references;//to add the reference to all dataObject's
                
                $data_object = self::get_data_object($rec,$arr_ref);
                if(!$data_object) return false;
                $taxon_dataObjects[]= new SchemaDataObject($data_object);                     
            }
        }        
        return $taxon_dataObjects;
    }
    
    function get_data_object($rec,$references)
    {
        $data_object_parameters = array();
        $data_object_parameters["identifier"]   = $rec["identifier"];        
        $data_object_parameters["source"]       = $rec["dc_source"];        
        $data_object_parameters["dataType"]     = $rec["dataType"];
        $data_object_parameters["created"]      = $rec["date_created"];
        $data_object_parameters["modified"]     = $rec["date_modified"];        
        $data_object_parameters["mimeType"]     = @$rec["mimeType"];
        $data_object_parameters["mediaURL"]     = @$rec["mediaURL"];        
        $data_object_parameters["rights"]       = @$rec["rights"];
        $data_object_parameters["rightsHolder"] = @$rec["rights_holder"];        
        $data_object_parameters["title"]        = @$rec["title"];
        $data_object_parameters["description"]  = utf8_encode($rec["description"]);
        $data_object_parameters["location"]     = utf8_encode($rec["location"]);        
        $data_object_parameters["license"]      = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';                

        //start reference
        $data_object_parameters["references"] = array();        
        $ref=array();
        foreach($references as $r)
        {
            $referenceParameters = array();
            $referenceParameters["fullReference"] = trim($r["ref"]);           
            $referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => trim($r["url"])));      
            $ref[] = new SchemaReference($referenceParameters);
        }        
        $data_object_parameters["references"] = $ref;
        //end reference
        
        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new SchemaSubject($subjectParameters);
        }
        
        if(@$rec["agent"])
        {               
            $agents = array();
            foreach($rec["agent"] as $a)
            {  
                $agentParameters = array();
                $agentParameters["role"]     = $a["role"];
                $agentParameters["homepage"] = $a["homepage"];
                $agentParameters["logoURL"]  = "";        
                $agentParameters["fullName"] = $a["name"];
                $agents[] = new SchemaAgent($agentParameters);
            }
            $data_object_parameters["agents"] = $agents;
        }
        return $data_object_parameters;
    }        
}
?>