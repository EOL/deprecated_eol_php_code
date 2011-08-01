<?php
namespace php_active_record;

define("PAGE_URL", "http://www.bioimages.org.uk/html/");
define("IMAGE_URL", "http://www.bioimages.org.uk/");
class BioImagesAPI
{
    public static function get_all_taxa()
    {
        /* This creates a text file (connectors\files\BioImages\bioimages.txt) that'll list each taxon URL. */
        $urls = self::compile_taxon_urls();
        
        /* Partner provided ancestry information on a separate spreadsheet file. */
        $ancestry = self::prepare_ancestry_info();
        
        /* Reads bioimages.txt - normal operation */        
        $urls = self::get_from_txt();        

        $all_taxa = array();
        $used_collection_ids = array();        
        
        $i=1; $total=sizeof($urls);
        foreach($urls as $url)
        {
            print"\n $i of $total";$i++;
            if($url["active"])
            {
                $arr = self::get_BioImages_taxa($url["path"],$ancestry,$used_collection_ids); 
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];                
                $all_taxa = array_merge($all_taxa,$page_taxa);                                    
            }
        }
        return $all_taxa;
    }    
    
    public static function get_BioImages_taxa($url1,$ancestry,$used_collection_ids)
    {
        $response = self::search_collections($url1,$ancestry);//this will output the raw (but structured) output from the external service
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
    
    function prepare_ancestry_info()
    {
        $ancestry=array();        
        require_library('XLSParser');
        $parser = new XLSParser();        
        $arr_filename = array(DOC_ROOT . "update_resources/connectors/files/BioImages/Photographed Taxa.xls",
                              DOC_ROOT . "update_resources/connectors/files/BioImages/Taxa with trophisms.xls");        
        foreach($arr_filename as $filename)
        {
            $arr = $parser->convert_sheet_to_array($filename);                  
            $i=0;
            foreach($arr["Latin"] as $latin)
            {   
                $latin = str_ireplace(' & ',' and ', $latin);	                                        
                $latin_authority = $arr["Latin_authority"][$i];
                $latin_authority = str_ireplace(' & ',' and ', $latin_authority);	                                                        
                $ancestry[$latin]           =array("Phylum"=>$arr["Phylum"][$i], "Class"=>$arr["Class"][$i], "Order"=>$arr["Order"][$i], "Family"=>$arr["Family"][$i], "ComName"=>$arr["English"][$i]);
                $ancestry[$latin_authority] =array("Phylum"=>$arr["Phylum"][$i], "Class"=>$arr["Class"][$i], "Order"=>$arr["Order"][$i], "Family"=>$arr["Family"][$i], "ComName"=>$arr["English"][$i]);
                $i++;        
            }
        }
        return $ancestry;
    }
    
    function compile_taxon_urls()
    {
        $start_url = "http://www.bioimages.org.uk/html/shortcut.htm";
        
        //get top level URLs
        $taxon_urls = self::taxon_url_extractor($start_url);

        //start loop to all taxon URLs
        $i=0; $total=sizeof($taxon_urls);
        foreach($taxon_urls as $url)
        {
            $i++;
            print"\n $i of $total";            
            
            $arr_temp = self::taxon_url_extractor($url);    
            $taxon_urls = array_merge($taxon_urls,$arr_temp);            
            foreach($arr_temp as $url2)
            {
                $arr_temp2 = self::taxon_url_extractor($url2);    
                $taxon_urls = array_merge($taxon_urls,$arr_temp2);                                
                foreach($arr_temp2 as $url3)
                {
                    $arr_temp3 = self::taxon_url_extractor($url3);    
                    $taxon_urls = array_merge($taxon_urls,$arr_temp3);                                
                    foreach($arr_temp3 as $url4)                    
                    {
                        $arr_temp4 = self::taxon_url_extractor($url4);    
                        $taxon_urls = array_merge($taxon_urls,$arr_temp4);                                
                        foreach($arr_temp4 as $url5)                    
                        {
                            $arr_temp5 = self::taxon_url_extractor($url5);    
                            $taxon_urls = array_merge($taxon_urls,$arr_temp5);                                
                            foreach($arr_temp5 as $url6)                    
                            {
                                $arr_temp6 = self::taxon_url_extractor($url6);    
                                $taxon_urls = array_merge($taxon_urls,$arr_temp6);
                                foreach($arr_temp6 as $url7)                    
                                {
                                    $arr_temp7 = self::taxon_url_extractor($url7);    
                                    $taxon_urls = array_merge($taxon_urls,$arr_temp7);
                                    foreach($arr_temp7 as $url8)                    
                                    {
                                        $arr_temp8 = self::taxon_url_extractor($url8);    
                                        $taxon_urls = array_merge($taxon_urls,$arr_temp8);                                    
                                        foreach($arr_temp8 as $url9)                    
                                        {
                                            $arr_temp9 = self::taxon_url_extractor($url9);    
                                            $taxon_urls = array_merge($taxon_urls,$arr_temp9);
                                            foreach($arr_temp9 as $url10)                    
                                            {
                                                $arr_temp10 = self::taxon_url_extractor($url10);    
                                                $taxon_urls = array_merge($taxon_urls,$arr_temp10);
                                                foreach($arr_temp10 as $url11)                    
                                                {
                                                    $arr_temp11 = self::taxon_url_extractor($url11);    
                                                    $taxon_urls = array_merge($taxon_urls,$arr_temp11);
                                                    foreach($arr_temp11 as $url12)                    
                                                    {
                                                        $arr_temp12 = self::taxon_url_extractor($url12);    
                                                        $taxon_urls = array_merge($taxon_urls,$arr_temp12);
                                                        foreach($arr_temp12 as $url13)                    
                                                        {
                                                            $arr_temp13 = self::taxon_url_extractor($url13);    
                                                            $taxon_urls = array_merge($taxon_urls,$arr_temp13);
                                                        }
                                                    }                                                    
                                                }                                                
                                            }                                                           
                                        }
                                    }                                        
                                }
                            }
                        }                        
                    }   
                }                                
            }
            //use to limit to ANIMALIA -- if($i==1)break;
        }   
           
        $taxon_urls = array_unique($taxon_urls);
        self::save_to_txt($taxon_urls,"w+");//a - append, doesn't truncate | w+ - truncates
        return;        
    }
    
    function taxon_url_extractor($url)
    {
        $html = utf8_decode(Functions::get_remote_file_fake_browser($url));
        $html = strip_tags($html,"<a>");
        $html = str_ireplace('href="t' , "&arr[]=", $html);	
        $arr = array(); parse_str($html);	            
        $urls=array();
        foreach($arr as $r)
        {
            $url = PAGE_URL . "t" . substr($r,0,stripos($r,'"'));
            if($url != "http://www.bioimages.org.uk/html/t43377.htm")$urls[$url]=1;            
        }
        return array_keys($urls);
    }    

    function search_collections($url1,$ancestry)
    {
        $html = utf8_decode(Functions::get_remote_file_fake_browser($url1));
        $html1 = $html;
        $response = self::scrape_species_page($html1,$url1,$ancestry);        
        return $response;//structured array
    }           
    
    function scrape_species_page($html,$species_page_url,$ancestry)
    {        
        $arr_scraped=array();
        $arr_photos=array();
        $arr_sciname=array();                
        //=============================================================================================================         
        $species="";
        if(preg_match("/<title>(.*?)<\/title/ims", $html, $matches))
        {   
            $title = trim(strip_tags($matches[1]));            
            $piece = self::separate_sciname_from_vernacular($title);            
            $sciname = self::clean_sciname(@$piece[0]);            
            $vernacular = trim(@$piece[1]);           
        }
        //=============================================================================================================

        $agent=array();

        //photos start =================================================================
        $arr_photos=array();
        $arr_photo_url=self::get_photos($html,'<h2 class="Recs">'); 

        if($arr_photo_url)
        {
            $arr_photo_urls_per_taxon = self::get_all_photo_urls_per_taxon($arr_photo_url);
            if($arr_photo_urls_per_taxon)$arr_photos = self::get_photo_details($arr_photo_urls_per_taxon);               
        }        
                
        foreach($arr_photos as $rec)
        {
            $desc="";
            if(@$rec['Date:'])           $desc.="Date: " . @$rec['Date:'] . " <br>";
            if(@$rec['Location:'])       $desc.="Location: " . @$rec['Location:'] . " <br>";
            if(@$rec['State:'])          $desc.="State: " . @$rec['State:'] . " <br>";
            if(@$rec['Record Summary:']) $desc.="Record Summary: " . @$rec['Record Summary:'] . " <br>";
            if(@$rec['Image Reference:'])$desc.="Image Reference: " . @$rec['Image Reference:'];            
            $agent=array();
            $rights_holder="";            
            if($rec['Malcolm Storey'])
            {                
                $agent[]=array("role" => "photographer" , "homepage" => "http://www.bioimages.org.uk/index.htm" , "name" => "Malcolm Storey");
                $rights_holder = "Malcolm Storey";
            }
            else continue;            
            
            $rec["Copyright"] = str_ireplace('www.bioimages.org.uk.' ,'www.bioimages.org.uk',$rec["Copyright"]);
            $rec["Copyright"] = str_ireplace('Some rights reserved.' ,'',$rec["Copyright"]);
            
            $arr_photos["$sciname"][] = array(
                        "identifier"    =>@$rec["Image Reference:"],
                        "mediaURL"      =>@$rec["url"],
                        "mimeType"      =>"image/jpeg",                        
                        "date_created"  =>@$rec["Date:"],                        
                        "rights"        =>str_ireplace('©' , '', @$rec["Copyright"]),                        
                        "rights_holder" =>$rights_holder,
                        "dataType"      =>"http://purl.org/dc/dcmitype/StillImage",
                        "description"   =>$desc,
                        "title"         =>"",
                        "location"      =>@$rec["Location:"],
                        "dc_source"     =>@$rec["sourceURL"],
                        "agent"         =>$agent);            
        }                
        //photos end =================================================================        
        
        //text start Description DiagnosticDescription =================================================================
        $arr_texts = self::scrape_page($html,$species_page_url);            
        if(@$arr_texts['Notes (MWS)'])        $arr_texts["$sciname"][]=self::fill_text_array($arr_texts["sourceURL"],"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description",$arr_texts["Notes (MWS)"],"");
        if(@$arr_texts['Diagnostic features'])$arr_texts["$sciname"][]=self::fill_text_array($arr_texts["sourceURL"],"http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription",$arr_texts["Diagnostic features"],"");        
        //text end =================================================================        
        
        //text start associations =================================================================                
        $texts_asso = self::scrape_page_others($html,"is associated with:</p>","not reference");                    
        if($texts_asso) 
        {               
            $texts_asso = "In Great Britain and/or Ireland:<br>" . "$sciname is associated with:<br> <br> $texts_asso";
            $arr_texts["$sciname"][]=self::fill_text_array($species_page_url,"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations",$texts_asso,"Feeding and other inter-species relationships");
        }         
        $texts_asso = self::scrape_page_others($html,"<p>Associated with","not reference");            
        if($texts_asso) 
        {   
            $texts_asso = "In Great Britain and/or Ireland:<br>" . "Associated with $sciname:<br> <br> $texts_asso";
            $arr_texts["$sciname"][]=self::fill_text_array($species_page_url,"http://rs.tdwg.org/ontology/voc/SPMInfoItems#Associations",$texts_asso,"Feeding and other inter-species relationships");
        }                 
        //text end associations =================================================================                

        //text start references =================================================================                
        $arr_ref = self::scrape_page_others($html,'<h2 class="Lit">References</h2>',"reference");            
        if($arr_ref)$arr_ref = self::prepare_reference($arr_ref);
        else $arr_ref=array();
        //text end references =================================================================        
        
        $arr_sciname["$sciname"]=$species_page_url;                       
        foreach(array_keys($arr_sciname) as $sci)
        {
            if(@$arr_photos["$sci"] || @$arr_texts["$sci"])
            {
                $arr_scraped[]=array("id"=>"",
                                     "kingdom"=>"",   
                                     "phylum"=>@$ancestry[$sci]['Phylum'],   
                                     "class"=>@$ancestry[$sci]['Class'],   
                                     "order"=>@$ancestry[$sci]['Order'],   
                                     "family"=>@$ancestry[$sci]['Family'],   
                                     "comname"=>@$ancestry[$sci]['ComName'],
                                     "sciname"=>$sci,
                                     "dc_source"=>$species_page_url,   
                                     "photos"=>@$arr_photos["$sci"],
                                     "texts"=>@$arr_texts["$sci"],
                                     "references"=>$arr_ref
                                    );
            }
        }        
        return $arr_scraped;        
    }
    
    function clean_sciname($sciname)
    {
        $sciname = trim($sciname);
        $pos = stripos($sciname,' ');
        if(is_numeric($pos))
        {
            $part1 = trim(substr($sciname,0,$pos));
            $part2 = trim(substr($sciname,$pos,strlen($sciname)));
            $sciname = trim(ucfirst(strtolower($part1)) . " " . $part2);                
        }
        else $sciname = ucfirst(strtolower($sciname));    
        return $sciname;    
    }
    
    function prepare_reference($arr_ref)
    {
        $refs=array();
        foreach($arr_ref as $r)
        {        
            $url="";    
            if(preg_match("/href=(.*?)>/ims", $r, $matches)) $url = str_ireplace(" ","%20",$matches[1]);                        
            $ref = str_ireplace('</td>' , '. ', $r);	
            $ref = strip_tags($ref);
            $refs[]=array("url"=>$url, "ref"=>$ref);                        
        }          
        return $refs;
    }
    
    function fill_text_array($sourceURL,$subject,$desc,$title)
    {
        $agent[]=array("role" => "compiler" , "homepage" => "http://www.bioimages.org.uk/index.htm" , "name" => "Malcolm Storey");            
        $rights_holder = "Malcolm Storey";
        return          array(
                        "identifier"    =>$sourceURL,
                        "mediaURL"      =>"",
                        "mimeType"      =>"text/html",                        
                        "date_created"  =>"",                        
                        "rights"        =>"",                        
                        "rights_holder" =>$rights_holder,
                        "dataType"      =>"http://purl.org/dc/dcmitype/Text",
                        "description"   =>$desc,
                        "title"         =>$title,
                        "location"      =>"",
                        "dc_source"     =>$sourceURL,
                        "agent"         =>$agent,
                        "subject"       =>$subject,
                        );                    
    }

    function get_photos($string,$searched)
    {        
        $arr_photo_url=array();
        $pos = stripos($string,$searched);
        $str = substr($string,$pos,strlen($string));
        if(is_numeric($pos))
        {
            if(preg_match("/<table>(.*?)<\/table/ims", $str, $matches))
            {   
                $str = trim($matches[1]);
                $str = str_ireplace('<a href="' , "&arr[]=", $str);	
                $arr = array(); parse_str($str);	                            
                foreach($arr as $r)
                {                    
                    $arr_photo_url[] = PAGE_URL . substr($r,0,stripos($r,'"'));
                }
            }
        }
        return $arr_photo_url;
    }    
    
    function get_all_photo_urls_per_taxon($arr)
    {
        $arr_total_url=array();
        //URLs to access photos per species
        foreach($arr as $url)
        {                    
            $html = utf8_decode(Functions::get_remote_file_fake_browser($url));
            $arr_photo_url=self::get_photos($html,'<h2 class="Assets">');                        
            $arr_total_url = array_merge($arr_total_url,$arr_photo_url);
        }
        return $arr_total_url;
    }
    
    function get_photo_details($arr)
    {
        $arr_total=array();
        foreach($arr as $url)
        {                    
            $html = utf8_decode(Functions::get_remote_file_fake_browser($url));
            //special case
            $html = self::clean_str($html);
            $html = str_ireplace('<td class="FieldTitle">Date: </td><td></td>',"",$html);            
            $html = str_ireplace('<td class="FieldTitle">Location: </td><td></td>',"",$html);
            $html = str_ireplace('<td class="FieldTitle">State: </td><td></td>',"",$html);
            $html = str_ireplace('<td class="FieldTitle">Record Summary: </td><td></td>',"",$html);
            $html = str_ireplace('<td class="FieldTitle">Image Reference: </td><td></td>',"",$html);
            //end special case            
            $arr_scraped = self::scrape_page($html,$url);            
            if($arr_scraped)$arr_total[] = $arr_scraped;
        }   
        return $arr_total;
    }
    function scrape_page($html,$sourceURL)
    {
        //special case        
        $html = str_ireplace("&quot;","",$html);
        //end special case
        
        //for FieldTitle 
        $str = str_ireplace('<td class="FieldTitle">' , "&arr[]=", $html);	
        $arr = array(); parse_str($str);	                                    
        $arr_title=array();
        foreach($arr as $r)
        {
            $pos = stripos($r,'</td>');
            if(is_numeric($pos)) $arr_title[] = trim(substr($r,0,$pos));
        }
                
        //for FieldValue 
        $str = str_ireplace('<td class="FieldValue">' , "&arr[]=", $html);	
        $arr = array(); parse_str($str);	                                    
        $arr_value=array();
        foreach($arr as $r)
        {
            $pos = stripos($r,'</td>');
            if(is_numeric($pos)) $arr_value[] = trim(substr($r,0,$pos));
        }
        
        $arr=array(); $i=0;
        foreach($arr_title as $title)
        {
            $arr[$title]=$arr_value[$i];$i++;
        }        

        $substrr = substr($html,stripos($html,'FieldTitle'),strlen($html));
        if(preg_match("/src=\"(.*?)\"/ims", $substrr, $matches)) 
        {
            $arr["url"] = str_ireplace('../../' , IMAGE_URL, $matches[1]);	        
            $arr["url"] = str_ireplace(" ","%20", $arr["url"]);	        
            $arr["sourceURL"] = $sourceURL;
        }        
        return $arr;
    }
    
    function scrape_page_others($html,$searched,$return_value)
    {
        $pos = stripos($html,$searched);
        if(is_numeric($pos))
        {
            $html = trim(substr($html,$pos,strlen($html)));
            $pos = stripos($html,"</table>");   
            $html = trim(substr($html,0,$pos));            
            $pos = stripos($html,'<td class="FieldValue">');
            if(!is_numeric($pos))return;            
        }
        else return;

        $html = str_ireplace('<tr class="odd">' , '<tr>', $html);	
        $html = str_ireplace('<tr class="even">' , '<tr>', $html);	        
        
        //special case
        $html = str_ireplace('&amp;' , "and", $html);	                
        //end special case        

        $str = str_ireplace('<tr>' , "&arr[]=", $html);	
        $arr = array(); parse_str($str);	                                    
        
        $arr_value=array();
        foreach($arr as $r)
        {
            $pos = stripos($r,'</tr>');
            if(is_numeric($pos)) $arr_value[] = trim(substr($r,0,$pos));
        }
        
        //to exclude any images <img>
        $arr=array();
        foreach($arr_value as $r)
        {
            $r = str_ireplace('<td></td>' , "", $r);	                
            $r = str_ireplace('<a href=' , "<a target='bioimages' href=" . PAGE_URL, $r);	
            $arr[] = strip_tags(trim($r),"<a><td>");
        }            
        if($return_value=="reference")return $arr;

        //concatenate...
        $html="";
        foreach($arr as $r)
        {                
            $str = str_ireplace('<td class="FieldValue">' , "", $r);	
            $str = str_ireplace('<td class="FieldRef">' , "Ref. ", $str);	                
            $str = str_ireplace('</td>' , ". ", $str);	                
            $html .= $str;
            $html .= "<br> <br>";
        }                            
        return $html; 
    }    
    
    function separate_sciname_from_vernacular($string)
    {
        $string = strip_tags($string);
        $string = str_ireplace('&amp;' , "and", $string);	                
        $string = self::remove_parenthesis_if_first_char($string);
        $count = substr_count($string, '(');
        if($count == 0)
        {
            $arr = array($string,"");        
        }
        else
        {
            if($count == 1)    $pos = stripos($string,'(');    
            elseif($count > 1) $pos = strripos($string, '(');            
            $sciname = substr($string,0,$pos-1);
            $vernacular = substr($string,$pos+1,strlen($string));
            $vernacular = str_ireplace(')','',$vernacular); //remove the ending parenthesis
            $arr = array($sciname,$vernacular);                   
        }        
        return $arr;
    }
    function remove_parenthesis_if_first_char($string)
    {
        $string=trim($string);
        if(substr($string,0,1)=="(")
        {
            $pos = stripos($string,')');                
            $string = substr($string,1,strlen($string)-(strlen($string)-$pos)-1);
        }
        return $string;
    }

    function get_taxa_for_photo($rec)
    {
        $taxon = array();
        $taxon["commonNames"] = array();
        $license = null;                
        $taxon["identifier"] = "";
        $taxon["source"] = $rec["dc_source"];                
        $taxon["scientificName"] = ucfirst(trim($rec["sciname"]));        
        $taxon["kingdom"] = ucfirst(trim($rec["kingdom"]));
        $taxon["phylum"] = ucfirst(trim($rec["phylum"]));       
        $taxon["class"] = ucfirst(trim($rec["class"]));
        $taxon["order"] = ucfirst(trim($rec["order"]));
        $taxon["family"] = ucfirst(trim($rec["family"]));        
        if(@$rec["photos"]) $taxon["dataObjects"] = self::prepare_objects($rec["photos"],@$taxon["dataObjects"],array());
        if(@$rec["texts"])  $taxon["dataObjects"] = self::prepare_objects($rec["texts"],@$taxon["dataObjects"],$rec["references"]);        
        $taxon_object = new \SchemaTaxon($taxon);
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
                $taxon_dataObjects[]= new \SchemaDataObject($data_object);                     
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
            $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url" , "value" => trim($r["url"])));      
            $ref[] = new \SchemaReference($referenceParameters);
        }        
        $data_object_parameters["references"] = $ref;
        //end reference
        
        if(@$rec["subject"])
        {
            $data_object_parameters["subjects"] = array();
            $subjectParameters = array();
            $subjectParameters["label"] = @$rec["subject"];
            $data_object_parameters["subjects"][] = new \SchemaSubject($subjectParameters);
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
                 $agents[] = new \SchemaAgent($agentParameters);
             }
             $data_object_parameters["agents"] = $agents;
         }
        return $data_object_parameters;
    }    

    function clean_str($str)
    {    
        $str = str_ireplace(array("\n", "\r", "\t", "\o"), '', $str);			
        return $str;
    }

    function save_to_txt($arr,$mode)
    {
    	$str="";        
        foreach ($arr as $value)
    	{
    		$str .= $value . "\n";
    	}  
        $filename = DOC_ROOT . "update_resources/connectors/files/BioImages/bioimages.txt";
    	if($fp = fopen($filename,$mode))
        {   
            fwrite($fp,$str);fclose($fp);
        }	
        // else no text file	    
        return "";    
    }

    function get_from_txt()
    {        
        $filename = DOC_ROOT . "update_resources/connectors/files/BioImages/bioimages.txt";        
        $fd = fopen ($filename, "r");
        $contents = fread ($fd,filesize ($filename)); fclose ($fd);        
        $splitcontents = explode("\n", $contents);
        $counter = "";        
        $arr=array();
        foreach ( $splitcontents as $value )
        {    
            if($value)
            {
                $arr[]=array("path" => $value , "active" => 1);
            }        
        }            
        return $arr;
    }
}
?>