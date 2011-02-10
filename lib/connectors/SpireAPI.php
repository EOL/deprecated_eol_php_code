<?php

define("SPIRE_SERVICE", "http://spire.umbc.edu/ontologies/foodwebs/webs_publisher.php?published_study=");
define("SPIRE_PATH_ANCESTRY", DOC_ROOT . "/update_resources/connectors/files/SPIRE/taxa_detail.xls");

class SpireAPI
{
    public static function get_all_taxa()
    {           
        $all_taxa = array();
        $used_collection_ids = array();        

        $arr = self::assemble_XML_files();
        $GLOBALS['arr_taxa']         = $arr[0];                              
        $GLOBALS['arr_ref']          = $arr[1];                              
        $GLOBALS['reference']        = $arr[2];                   
        
        $i=0;                
        foreach($GLOBALS['arr_taxa'] as $taxon => $temp)
        {
            print "\n [$taxon] --- ";
            $i++;             
            $arr = self::get_spire_taxa($taxon,$used_collection_ids);                                
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];                            
            if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);                                 
            //if($i > 1)break; //debug
        }                
        return $all_taxa;
    }
    
    public static function get_spire_taxa($taxon,$used_collection_ids)
    {
        $response = self::parse_xml($taxon);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["identifier"]]) continue;            
            $taxon = self::get_taxa_for_photo($rec);
            if($taxon) $page_taxa[] = $taxon;            
            @$used_collection_ids[$rec["identifier"]] = true;
        }        
        return array($page_taxa,$used_collection_ids);        
    }            
    
    function assemble_xml_files()
    {
        $arr_taxa       = array();
        $arr_predator   = array();
        $arr_prey       = array();
        
        $arr_ref       = array();
        
        //real count 259
        for ($i=1; $i<=259; $i++)//debug
        {            
            $str = Functions::get_remote_file(SPIRE_SERVICE . $i);             
            $str = str_replace('rdf:resource', 'rdf_resource', $str);  
            $xml = simplexml_load_string($str);                                        
            foreach($xml->ConfirmedFoodWebLink as $rec)
            {                                
                foreach($rec->predator[0]->attributes() as $attribute => $value) 
                {                                        
                    $arr = parse_url($value);
                    $predator = trim($arr['fragment']);                    
                    //print"\n counter[$i][$value]";
                    $predator = str_replace("_"," ",$predator);
                }       
                $pred_desc = trim($rec->predator_description);                                
                
                foreach($rec->prey[0]->attributes() as $attribute => $value) 
                {                                        
                    $arr = parse_url($value);
                    $prey = trim($arr['fragment']);            
                    $prey = str_replace("_"," ",$prey);        
                }       
                $prey_desc = trim($rec->prey_description);                                
                                
                foreach($rec->observedInStudy[0]->attributes() as $attribute => $value) 
                {                                        
                    $arr = parse_url($value);
                    $ref_num = trim($arr['fragment']);                    
                }                                                       
                
                $arr_taxa[$predator]['desc']        = $pred_desc;                
                $arr_taxa[$prey]['desc']            = $prey_desc;                
                
                
                if(!@$arr_predator[$predator]) $arr_predator[$predator][]  = $prey;
                if(!@$arr_prey[$prey])         $arr_prey[$prey][]          = $predator;                   

                if(!in_array($prey, $arr_predator[$predator]))  $arr_predator[$predator][]  = $prey;
                if(!in_array($predator, $arr_prey[$prey]))      $arr_prey[$prey][]          = $predator;                 
                
                if(!@$arr_ref[$ref_num]['predator']) $arr_ref[$ref_num]['predator'][] = $predator;                
                if(!@$arr_ref[$ref_num]['prey'])     $arr_ref[$ref_num]['prey'][] = $prey;
                
                if(!in_array($predator,$arr_ref[$ref_num]['predator'])) $arr_ref[$ref_num]['predator'][] = $predator;                
                if(!in_array($prey    ,$arr_ref[$ref_num]['prey']))     $arr_ref[$ref_num]['prey'][] = $prey;               
            }

            foreach($xml->Study as $rec)            
            {                
                $habitats=array();                
                foreach($rec->ofHabitat as $habitat) 
                {                                  
                    foreach($habitat->attributes() as $attribute => $value) 
                    {                            
                        $arr = parse_url($value);
                        $habitat = trim($arr['fragment']);                    
                        $habitats[] = str_replace("_"," ",$habitat);
                    }
                }                       
                $habitats =  implode(", ", $habitats);                                
                $reference[$ref_num]=array( "titleAndAuthors"=>trim($rec->titleAndAuthors), 
                                            "publicationYear"=>trim($rec->publicationYear),                                            
                                            "locality"=>trim($rec->locality),
                                            "habitat"=>$habitats,
                                          );
            }                        
        }//main loop 1-259
        
        
        require_library('XLSParser');
        $parser = new XLSParser();
        $names = $parser->convert_sheet_to_array(SPIRE_PATH_ANCESTRY);          
                
        $ancestry=array();
        foreach($arr_taxa as $taxon => $temp)                
        {
            $arr_taxa[$taxon]['objects']=array("predator"=>@$arr_predator[$taxon], "prey"=>@$arr_prey[$taxon]);

            //start ancestry
            $key = array_search(trim($taxon), $names['tname']);
            if(strval($key) != "")
            {
                $parent_id = $names['parent_id'][$key];        
                $ancestry=self::get_ancestry($key,$names);                
                $arr_taxa[$taxon]['ancestry'] = $ancestry;                                
            }                   
        }                                
        print"<pre>";print_r($arr_taxa);
        print"</pre>";
        return array($arr_taxa,$arr_ref,$reference);        
    }

    function get_ancestry($key,$names)
    {
        $arr=array();
        $continue=true;         
        while($continue) 
        {                                    
            $parent_id     = @$names['parent_id'][$key];                
            $key_parent_id = array_search($parent_id, $names['id']);            
            if(strval($key_parent_id) == "")$continue=false;                        
            $arr[]=array("name"=>@$names['tname'][$key_parent_id], "rank"=>@$names['rank'][$key_parent_id]);            
            $key = $key_parent_id;            
        }    
        return self::assign_ancestry($arr);
    }
    
    function assign_ancestry($arr)
    {
        $ancestry=array();
        if($arr)
        {
            $ranks = array("Kingdom", "Phylum", "Class", "Order", "Family", "Genus");
            foreach($arr as $r)
            {
                if(in_array($r['rank'], $ranks))
                {
                    $ancestry[$r['rank']] = $r['name'];
                }                
            }
        }
        return $ancestry;            
    }    
    
        
    function parse_xml($taxon)
    {                                                
        $arr_data=array();             
        $taxon_id = str_replace(" ","_",$taxon) . "_spire";        
        
        $arr_objects=array();
        
        if(@$GLOBALS['arr_taxa'][$taxon]['objects']['predator']) $arr_objects = self::get_predator_prey_associations($taxon,'predator',$arr_objects);
        if(@$GLOBALS['arr_taxa'][$taxon]['objects']['prey'])     $arr_objects = self::get_predator_prey_associations($taxon,'prey',$arr_objects);
        
        if(sizeof($arr_objects))
        {        
            $arr_data[]=array(  "identifier"    =>$taxon_id,
                                "source"        =>"http://spire.umbc.edu/fwc/",
                                "kingdom"       =>@$GLOBALS['arr_taxa'][$taxon]['ancestry']['Kingdom'],
                                "phylum"        =>@$GLOBALS['arr_taxa'][$taxon]['ancestry']['Phylum'],
                                "class"         =>@$GLOBALS['arr_taxa'][$taxon]['ancestry']['Class'],
                                "order"         =>@$GLOBALS['arr_taxa'][$taxon]['ancestry']['Order'],
                                "family"        =>@$GLOBALS['arr_taxa'][$taxon]['ancestry']['Family'],
                                "genus"         =>@$GLOBALS['arr_taxa'][$taxon]['ancestry']['Genus'],
                                "sciname"       =>$taxon,
                                "taxon_refs"    =>array(),
                                "synonyms"      =>array(),
                                "commonNames"   =>array(), 
                                "data_objects"  =>$arr_objects                                
                             );                                           
        }        
        return $arr_data;        
    }
    
    function get_predator_prey_associations($taxon,$type,$arr_objects)        
    {           
        $taxon_desc = $GLOBALS['arr_taxa'][$taxon]['desc'];
        if($type == 'predator')$verb = "preys on:";
        elseif($type == 'prey')$verb = "is a prey of:";
                
        $html="<b>$taxon ($taxon_desc) $verb</b>";
        
        $refs      = array(); $temp_citation=array();        
        $locations = array(); $temp_location=array();        
        $habitats  = array(); $temp_habitat=array();        
        
        foreach($GLOBALS['arr_taxa'][$taxon]['objects'][$type] as $rec)        
        {            
            $citation = "";
            $ref_url = "";            
            
            $html.="<br>&nbsp;<br> - " . $rec;            
            if($rec != $GLOBALS['arr_taxa'][$rec]['desc']) $html.=" {" . $GLOBALS['arr_taxa'][$rec]['desc'] . "}";                                                
            

            //check if the taxon is in the list of taxa for all references 1-259
            for ($i=1; $i<=259; $i++)
            {
                $index = "s_" . $i;                            
                if(!isset($GLOBALS['arr_ref'][$index]))continue;                    
                if(in_array(trim($rec),$GLOBALS['arr_ref'][$index][self::toggle_type($type)])) 
                {                    
                    $citation = $GLOBALS['reference'][$index]['titleAndAuthors'];                    
                    if(!in_array($citation,$temp_citation)) $refs[] = array("url"=>"", "ref"=>$citation);                                    
                    $temp_citation[]=$citation;                    
                    
                    $location = $GLOBALS['reference'][$index]['locality'];
                    if(@$GLOBALS['reference'][$index]['habitat']) $location .= "<br>Habitat: " . $GLOBALS['reference'][$index]['habitat'];                    
                    
                    if(!in_array($location,$temp_location)) $locations[] = $location;
                    $temp_location[]=$location;                                        
                }
            }                           
        }                   
        
        $description = "";
        if($locations)
        {
            $description .= "<b>Locations where studies were conducted:</b>";
            foreach($locations as $location)
            {
                $description .= "<br>&nbsp;<br>" . $location;
            }
        }
        
        $description .= "<br>&nbsp;<br>" . $html;
        $source = "http://spire.umbc.edu/fwc/";
        $identifier = str_replace(" ","_",$taxon) . "_" . $type;
        $mimeType   = "text/html";
        $dataType   = "http://purl.org/dc/dcmitype/Text";
        
        if($type=="predator") $title = "Predators";    
        else                  $title = "Prey";    
        $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations"; //debug
        
        $agent = array(); 
        $agent[] = array("role" => "compiler" , "homepage" => "http://spire.umbc.edu/fwc/" , "Cynthia Sims Parr");
        $agent[] = array("role" => "compiler" , "homepage" => "http://spire.umbc.edu/fwc/" , "Joel Sachs");        
        
        $mediaURL=""; $location="";
        $license = "http://creativecommons.org/licenses/by/3.0/";
        $rightsHolder = "SPIRE project";                    
                
        $arr_objects = self::add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$agent,$license,$location,$rightsHolder,$refs,$subject,$arr_objects);                        
        return $arr_objects;                                
    }
    
    function toggle_type($type)
    {
        if($type=="predator")return "prey";
        elseif($type=="prey")return "predator";
    }
    
    function add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$agent,$license,$location,$rightsHolder,$refs,$subject,$arr_objects)
    {
        $arr_objects[]=array( "identifier"=>$identifier,
                              "dataType"=>$dataType,
                              "mimeType"=>$mimeType,
                              "title"=>$title,
                              "source"=>$source,
                              "description"=>$description,
                              "mediaURL"=>$mediaURL,
                              "agent"=>$agent,
                              "license"=>$license,
                              "location"=>$location,
                              "rightsHolder"=>$rightsHolder,
                              "object_refs"=>$refs,
                              "subject"=>$subject
                            );                                    
        return $arr_objects;
    }    
    
    function get_taxa_for_photo($rec)
    {
        $taxon = array();                        
        $taxon["source"] = $rec["source"];
        $taxon["identifier"] = trim($rec["identifier"]);
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));
        $taxon["genus"] = ucfirst(trim(@$rec["genus"]));
        $taxon["family"] = ucfirst(trim(@$rec["family"]));
        $taxon["order"] = ucfirst(trim(@$rec["order"]));
        $taxon["class"] = ucfirst(trim(@$rec["class"]));        
        $taxon["phylum"] = ucfirst(trim(@$rec["phylum"]));
        $taxon["kingdom"] = ucfirst(trim(@$rec["kingdom"]));                
        
        //start taxon reference
        $taxon["references"] = array();        
        $refs=array();
        foreach($rec['taxon_refs'] as $ref)
        {
            $referenceParameters = array();
            $referenceParameters["fullReference"] = $ref['ref'];
            if($ref['url'])
            {
                $referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => $ref['url']));                                
            }
            $refs[] = new SchemaReference($referenceParameters);
        }
        $taxon["references"] = $refs;
        //end taxon reference        
                
        //start common names
        foreach($rec["commonNames"] as $comname)
        {            
            $taxon["commonNames"][] = new SchemaCommonName(array("name" => $comname, "language" => ""));
        }
        //end common names                        
                
        if($rec["data_objects"])
        {
            foreach($rec["data_objects"] as $object)
            {
                $data_object = self::get_data_object($object);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new SchemaDataObject($data_object);                     
            }
        }       
        
        //start synonyms
        $taxon["synonyms"] = array();
        foreach($rec["synonyms"] as $syn)
        {
            $taxon["synonyms"][] = new SchemaSynonym(array("synonym" => $syn['synonym'], "relationship" => $syn['relationship']));
        }                
        //end synonyms        
        
        $taxon_object = new SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    function get_data_object($rec)
    {
        $data_object_parameters = array();        
        $data_object_parameters["identifier"]   = trim(@$rec["identifier"]);        
        $data_object_parameters["source"]       = $rec["source"];        
        $data_object_parameters["dataType"]     = trim($rec["dataType"]);
        $data_object_parameters["mimeType"]     = trim($rec["mimeType"]);
        $data_object_parameters["mediaURL"]     = trim(@$rec["mediaURL"]);        
        $data_object_parameters["created"]      = trim(@$rec["created"]);                        
        $data_object_parameters["source"]       = $rec["source"];                
        $data_object_parameters["description"]  = Functions::import_decode($rec["description"]);                            
        $data_object_parameters["location"]     = Functions::import_decode($rec["location"]);                                                            
        $data_object_parameters["license"]      = $rec["license"];        
        $data_object_parameters["rightsHolder"] = trim($rec["rightsHolder"]);
        $data_object_parameters["title"]        = @trim($rec["title"]);
        $data_object_parameters["language"]     = "en";        
        //==========================================================================================
        if(trim($rec["subject"]))
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = trim($rec["subject"]);
            $data_object_parameters["subjects"][] = new SchemaSubject($subjectParameters);
        }                                    
        //==========================================================================================
        $agents = array();
        foreach(@$rec["agent"] as $agent)
        {  
            $agentParameters = array();
            $agentParameters["role"]     = $agent["role"];
            $agentParameters["homepage"] = $agent["homepage"];
            $agentParameters["logoURL"]  = "";        
            $agentParameters["fullName"] = $agent[0];
            $agents[] = new SchemaAgent($agentParameters);
        }
        $data_object_parameters["agents"] = $agents;    
        //==========================================================================================        
        $data_object_parameters["references"] = array();        
        $ref=array();
        foreach($rec["object_refs"] as $r)
        {
            if(!$r["ref"])continue;
            $referenceParameters = array();
            $referenceParameters["fullReference"] = Functions::import_decode($r["ref"]);                
            if($r["url"])$referenceParameters["referenceIdentifiers"][] = new SchemaReferenceIdentifier(array("label" => "url" , "value" => trim($r["url"])));      
            $ref[] = new SchemaReference($referenceParameters);
        }        
        $data_object_parameters["references"] = $ref;
        //==========================================================================================                        
        return $data_object_parameters;
    }                    
}
?>