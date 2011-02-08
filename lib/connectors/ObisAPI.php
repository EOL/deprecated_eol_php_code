<?php

define("SPECIES_URL1", "http://www.iobis.org/OBISWEB/ObisControllerServlet?searchCategory=/AdvancedSearchServlet");
define("SPECIES_URL2", "http://www.iobis.org/OBISWEB/ObisControllerServlet?category=all&names=data&tableName=0&searchName=");
define("PATH_RANGES"    , DOC_ROOT . "/update_resources/connectors/files/OBIS/depthenv20100825_small.csv");
define("PATH_ANCESTRY"  , DOC_ROOT . "/update_resources/connectors/files/OBIS/tnames20100825_small.csv");
define("PATH_RANK"      , DOC_ROOT . "/update_resources/connectors/files/OBIS/rank.xls");

class ObisAPI
{
    public static function get_all_taxa()
    {    
        $all_taxa = array();
        $used_collection_ids = array();                  
        $path = PATH_RANGES;
        $urls = array($path);//just 1 path for now                   
        foreach($urls as $url)
        {
            $arr = self::get_obis_taxa($url,$used_collection_ids);                 
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];                        
            $all_taxa = array_merge($all_taxa,$page_taxa);                                    
        }
        return $all_taxa;
    }
    
    public static function get_obis_taxa($url,$used_collection_ids)
    {        
        $response = self::search_collections($url);//this will output the raw (but structured) output from the external service
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
    
    function search_collections($url)//this will output the raw (but structured) output from the external service
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $arr = $parser->convert_sheet_to_array($url);          
        
        $filename = PATH_ANCESTRY;
        $arr_taxon_detail = $parser->convert_sheet_to_array($filename);          
        
        //id	tname	tauthor	valid_id	rank_id	parent_id	worms_id        
        $arr_taxon_detail=self::prepare_taxon_detail($arr_taxon_detail,$parser);
        $response = self::prepare_species_page($arr,$arr_taxon_detail);        
        return $response;//structured array
    }           
    
    function prepare_species_page($arr_csv,$arr_taxon_detail)
    {       
        $arr_scraped=array();
        $arr_photos=array();
        $arr_sciname=array();                
        
        //=============================================================================================================
        
        $i=0;
        foreach($arr_csv['speciesname'] as $sci)
        {
            $agent=array();
            $agent[]=array("role" => "project" , "homepage" => "http://www.iobis.org/" , "name" => "OBIS");                
            $rights_holder = "Ocean Biogeographic Information System";    
            
            $sciname = self::remove_parenthesis_entry($sci);
            
            $desc = "Depth range (m): " . $arr_csv["min"][$i] . " - " . $arr_csv["max"][$i];            
            $desc .= "<br>" . $arr_csv["nrecords"][$i] . ": number of records on which the range was based";
            $desc .= "<br>" . $arr_csv["ntaxa"][$i] . ": number of taxa on which the range was determined (including subspecies and other infraspecific taxa)";
            
            $range1 = $arr_csv["max"][$i];
            $range2 = 0;
            
            $line_graph=self::generate_line_graph($range1,$range2,$arr_csv["max"][$i],$arr_csv["min"][$i]);
            $bar_graph=self::generate_bar_graph($arr_csv["max"][$i],$arr_csv["min"][$i]);
            
            $desc .= "<br>&nbsp;<br>$bar_graph";
            
            if(!$bar_graph)
            {
                if($line_graph)$desc .= "<br>$line_graph";
            }            
            
            $desc .= "<br>
            Note: this information has not been validated. 
            Check this *<a target='obis_gallery' href='http://www.eol.org/content_partner/content/Ocean%20Biogeographic%20Information%20System'>note</a>*. 
            Your feedback is most welcome.";            
            
            $pos=stripos($sciname," ");
            if(is_numeric($pos))    
            {
                $genus = trim(substr($sciname,0,$pos));
                $species = trim(substr($sciname,$pos+1,strlen($sciname)));
                $dc_source = SPECIES_URL1 . "&genus=$genus&species=$species";
            }
            else $dc_source = SPECIES_URL2 . $sciname;            
                    
            $species_id = $arr_csv["species_id"][$i];
            
            $arr_texts["$sci"][] = 
                         array(
                         "identifier"    =>$species_id . "-depth",
                         "mediaURL"      =>"",
                         "mimeType"      =>"text/html",                        
                         "date_created"  =>"",                        
                         "rights"        =>"",                        
                         "rights_holder" =>$rights_holder,
                         "dataType"      =>"http://purl.org/dc/dcmitype/Text",
                         "description"   =>$desc,
                         "title"         =>"Depth range",
                         "location"      =>"",
                         "dc_source"     =>$dc_source,
                         "agent"         =>$agent,
                         "subject"       =>"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Habitat",
                         );                    
            
            $ancestry=self::assign_ancestry(@$arr_taxon_detail[$species_id]['ancestry']);                   
        
            $arr_scraped[]=array("identifier"=>$species_id,
                                 "kingdom"=>@$ancestry["Kingdom"],
                                 "phylum"=>@$ancestry["Phylum"],
                                 "class"=>@$ancestry["Class"],
                                 "order"=>@$ancestry["Order"],
                                 "family"=>@$ancestry["Family"],
                                 "sciname"=>$sciname,
                                 "dc_source"=>$dc_source,   
                                 "texts"=>@$arr_texts["$sci"],
                                 "references"=>array()
                                );
            $i++;
        }        
        return $arr_scraped;        
    }
    
    function remove_parenthesis_entry($sciname)
    {
        $pos1 = stripos($sciname,"(");
        if(is_numeric($pos1))
        {
            $pos2 = stripos($sciname,")");    
            return self::clean_str(trim(substr($sciname,0,$pos1) . substr($sciname,$pos2+1,strlen($sciname))));
        }
        return $sciname;        
    }
    
    function generate_line_graph($range1,$range2,$max,$min)
    {
        $range1 = self::get_max_range($max,$min);   
        $title = "Depth range (m)";
        $comma_separated = $min . "," . $max;

        $chxt="y,x";
        $chxl="1:|Line graph";
        return"<img src='http://chart.apis.google.com/chart?chs=200x150&chxt=$chxt&chxl=$chxl&chxr=0,$range1,$range2&chtt=$title&cht=lc&chd=t:$comma_separated&chds=$range1,$range2'>";
    }
    function generate_bar_graph($max,$min)
    {
        $range=$max-$min;
        if($range==0)return;
        
        $max_range = self::get_max_range($max,$min);   
        $title = "Depth range (m)";
        $chm="N,FF0000,-1,,12|N,000000,0,,12,,c|N,000000,1,,12,,c|N,ffffff,2,,12,,c";        
        if($range==1)$chm="";
        $chxt="y";
        return"<img src='http://chart.apis.google.com/chart?cht=bvs&chs=350x150&chd=t:$min|$range&chxr=0,0,$max_range&chds=0,$max_range&chco=ffffff,389ced&chbh=50,20,15&chxt=$chxt&chm=$chm&chtt=$title&chma=20'>";            
    }
    function get_max_range($max,$min)
    {
        $digits = strlen(strval(intval($max)));
        $step = "1" . str_repeat("0", $digits-1);
        return $max + intval($step);                
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
    function prepare_taxon_detail($arr,$parser)
    {
        $rank_data = self::prepare_rank_data($parser);
        
        $arr_taxon_detail=array();
        $i=0;
        foreach($arr["id"] as $id)
        {
            //id	tname	tauthor	valid_id	rank_id	parent_id	worms_id
            $rank_id = $arr['rank_id'][$i];
            $arr_taxon_detail[$id]=array("tname"=>$arr['tname'][$i], "rank_id"=>$rank_id, "rank_name"=>@$rank_data[$rank_id], "parent_id"=>$arr['parent_id'][$i]);
            $i++;
        }
        $arr_ancestry=array();
        foreach($arr_taxon_detail as $id => $rec)
        {
            $ancestry=self::get_ancestry($id,$arr_taxon_detail,$rank_data);
            $arr_ancestry[$id]=$ancestry;
        }
        //final step
        $arr_taxon_detail=array();
        $i=0;
        foreach($arr["id"] as $id)
        {
            //id	tname	tauthor	valid_id	rank_id	parent_id	worms_id
            $rank_id = $arr['rank_id'][$i];
            $arr_taxon_detail[$id]=array("tname"=>$arr['tname'][$i], "rank_name"=>@$rank_data[$rank_id], "ancestry"=>$arr_ancestry[$id]);
            $i++;
        }
        return $arr_taxon_detail;
    }
    function get_ancestry($id,$arr_taxon_detail,$rank_data)
    {
        $arr=array();
        $continue=true; 
        $searched_id = $id;
        while($continue) 
        {
            if(@$arr_taxon_detail[$searched_id]['parent_id'] != $searched_id)
            {
                $temp_id        =@$arr_taxon_detail[$searched_id]['parent_id'];                
                $temp_rank_id   =@$arr_taxon_detail[$temp_id]['rank_id'];                
                $arr[]=array("id"=>$temp_id, "name"=>@$arr_taxon_detail[$temp_id]['tname'], "rank"=>@$rank_data[$temp_rank_id]);
                $searched_id = @$arr_taxon_detail[$searched_id]['parent_id'];
            }            
            else $continue=false;            
        }    
        return $arr;
    }
    function prepare_rank_data($parser)
    {
        $arr_rank=array();
        $filename = PATH_RANK;
        $arr = $parser->convert_sheet_to_array($filename);                              
        $i=0;
        foreach($arr['rank_id'] as $rank_id)
        {
            $arr_rank[$rank_id]=$arr['rank_name'][$i];
            $i++;
        }    
        return $arr_rank;
    }
        
    function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;                
        
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
                if($length == $i)$arr_ref = $references;
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
        $data_object_parameters["identifier"] = $rec["identifier"];        
        $data_object_parameters["source"] = $rec["dc_source"];        
        $data_object_parameters["dataType"] = $rec["dataType"];
        $data_object_parameters["mimeType"] = @$rec["mimeType"];
        $data_object_parameters["mediaURL"] = @$rec["mediaURL"];        
        $data_object_parameters["rights"] = @$rec["rights"];
        $data_object_parameters["rightsHolder"] = @$rec["rights_holder"];        
        $data_object_parameters["title"] = @$rec["title"];
        $data_object_parameters["description"] = utf8_encode($rec["description"]);
        $data_object_parameters["location"] = utf8_encode($rec["location"]);        
        $data_object_parameters["license"] = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';        
        
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
    
    function clean_str($str)
    {    
        $str = str_ireplace(array("\r", "\t", "\o"), '', $str);			
        $str = str_ireplace(array("  "), ' ', $str);			
        return $str;
    }
    
}
?>