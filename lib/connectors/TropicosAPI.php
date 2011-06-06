<?php
namespace php_active_record;

define("TROPICOS_NAME_EXPORT_FILE" , DOC_ROOT . "/update_resources/connectors/files/Tropicos/Tropicos_Name_Search_Reults_small.dat");
define("TROPICOS_DOMAIN" , "http://www.tropicos.org");

define("TROPICOS_IMAGE_DETAIL_PAGE" , "http://www.tropicos.org/Image/");
define("TROPICOS_TAXON_DETAIL_PAGE" , "http://www.tropicos.org/Name/");

define("TROPICOS_IMAGE_LOCATION_LOW_BANDWIDTH" , "http://www.tropicos.org/ImageScaled.aspx?imageid=");
define("TROPICOS_NAME_SERVICE" ,"http://services.tropicos.org/Name/Search?&format=xml&type=exact&name=");
define("TROPICOS_API_KEY" ,"2810ce68-f4cf-417c-b336-234bc8928390");
define("TROPICOS_API_SERVICE" ,"http://services.tropicos.org/Name/");

define("TROPICOS_NAME_FORM_SERVICE" , "http://www.tropicos.org/NameSearch.aspx/");
define("TROPICOS_IMAGE_LOCATION" , "http://www.tropicos.org/ImageDownload.aspx?imageid=");

define("TEMP_FILE_PATH" , DOC_ROOT . "/update_resources/connectors/files/Tropicos/");
define("WORK_LIST_FILENAME" , TEMP_FILE_PATH . "work_list.txt");
define("WORK_IN_PROGRESS_LIST_FILENAME" , TEMP_FILE_PATH . "work_in_progress_list.txt");
define("INITIAL_PROCESS_STATUS_FILENAME" , TEMP_FILE_PATH . "initial_process_status.txt");


class TropicosAPI
{
    function start_process($resource_id)
    {

        if(!trim(self::get_a_task(WORK_IN_PROGRESS_LIST_FILENAME)))//don't do this if there are harvesting task(s) in progress
        {
            if(!trim(self::get_a_task(INITIAL_PROCESS_STATUS_FILENAME)))//don't do this if initial process is still running
            {
                // step 1: divides the big Tropicos_Name_Search_Reults.dat into small files
                self::add_task_in_list("Initial process start",INITIAL_PROCESS_STATUS_FILENAME);
                self::divide_text_file(5);   
                self::delete_temp_xml_files(); //deletes the temp_Tropicos_batch_xx.xml files
                self::delete_a_task("Initial process start", INITIAL_PROCESS_STATUS_FILENAME);//remove a task from task list
            }
        }

        // step 2: run multiple instances, ideally 8 instances so it is over before their daily scheduled downtime
        while (true) //main process
        {
            $task = self::get_a_task(WORK_LIST_FILENAME);//get task to work on
            if($task)print"\n Process this: $task";
            if($task)
            {
                self::delete_a_task($task, WORK_LIST_FILENAME);//remove a task from task list
                self::add_task_in_list($task,WORK_IN_PROGRESS_LIST_FILENAME);
                print "$task \n";
                $task = str_ireplace("\n","",$task);//remove carriage return got from text file
                self::get_all_taxa($task);//main task
                print"\n Task $task is done. \n";
                self::delete_a_task("$task\n", WORK_IN_PROGRESS_LIST_FILENAME);//remove a task from task list
            }
            else
            {
                sleep(10);
                print"\n\n Work list done or list hasn't been created yet " . date('Y-m-d h:i:s a', time());
                break;
            }
        }

        if(!$task = trim(self::get_a_task(WORK_IN_PROGRESS_LIST_FILENAME)))//don't do this if there are task(s) in progress
        {
            // step 3: this should only run when all of instances of step 2 are done
            self::combine_all_xmls($resource_id);
        }
    }

    public static function get_all_taxa($task)
    {
        $all_taxa = array();
        $used_collection_ids = array();

        $filename = TEMP_FILE_PATH . $task . ".txt";
        print"[$filename]";
        $FILE = fopen($filename, "r");
        $i=0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $i++;
                $line = trim($line); $fields = explode("\t", $line);

                if(in_array(trim(@$fields[1]),array("*","**","***","!!")))continue;

                if(!isset($fields[2]))continue;
                $rec = array("taxon" => trim($fields[2]), "family" => trim($fields[0]), "author" => trim(@$fields[3]) );
                print"\n<br>$i. $rec[family] -- $rec[taxon] -- $rec[author]<br>";

                $arr = self::get_tropicos_taxa($rec,$used_collection_ids);
                $page_taxa              = $arr[0];
                $used_collection_ids    = $arr[1];
                if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);
                unset($page_taxa);
            }
            else print "\n invalid line";
            //if($i > 10)break; //debug
        }
        fclose($FILE);

        $xml = SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . "Tropicos/temp_TROPICOS_" . $task . ".xml";
        $OUT = fopen($resource_path, "w"); fwrite($OUT, $xml); fclose($OUT);
    }

    function get_a_task($filename)
    {
        $f = fopen($filename, "r");
        while ( $line = fgets($f, 1000) ){return $line;}
    }

    function delete_a_task($to_be_deleted,$filename)
    {
        $fh = fopen($filename, 'r');
        $theData = fread($fh, filesize($filename));
        fclose($fh);
        $theData = str_ireplace($to_be_deleted,"",$theData);

        //start saving
        $fh = fopen($filename, 'w') or die("can't open file");
        fwrite($fh, $theData);
        fclose($fh);
    }

    function format_number($num)
    {
        if($num < 10) return substr(strval($num/100),2,2);
        else          return strval($num);
    }

    function divide_text_file($divisor)
    {
        self::delete_temp_txtfiles();
        $FILE = fopen(TROPICOS_NAME_EXPORT_FILE, "r");
        $i=0; $file_ctr=0; $str="";
        print"<br>";
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $i++;
                $str.=$line;
                print"$i. $line<br>";
                if($i == $divisor)//no. of names per text file
                {
                    print"\n"; $file_ctr++;
                    $file_ctr_str = self::format_number($file_ctr);
                    $OUT = fopen(TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w");
                    fwrite($OUT, $str);fclose($OUT);
                    $str=""; $i=0;
                }
            }
        }
        //last writes
        if($str)
        {
            $file_ctr++;
            $file_ctr_str = self::format_number($file_ctr);
            $OUT = fopen(TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt", "w");
            fwrite($OUT, $str);fclose($OUT);
        }

        //create TROPICOS_work_list
        $str="";
        FOR($i = 1; $i <= $file_ctr; $i++)
        {
            $str .= "batch_".self::format_number($i)."\n";
        }
        $filename = WORK_LIST_FILENAME;
        if($fp = fopen($filename,"w+")){fwrite($fp,$str);fclose($fp);}
    }

    function add_task_in_list($task,$filename)
    {
        if($fp = fopen($filename,"a")){fwrite($fp,$task);fclose($fp);}
    }

    function delete_temp_txtfiles()
    {
        $file_ctr=0;
        while(true)
        {
            $file_ctr++;
            $file_ctr_str = self::format_number($file_ctr);
            $filename = TEMP_FILE_PATH . "batch_" . $file_ctr_str . ".txt";
            if(file_exists($filename)){print"\n unlink: $filename"; unlink($filename);}
            else {print"\n -nothing more to delete-"; return;}
        }
    }

    function delete_temp_xml_files()
    {
        $i=0;
        while(true)
        {
            $i++;
            $i_str = self::format_number($i);
            $filename = CONTENT_RESOURCE_LOCAL_PATH . "Tropicos/temp_TROPICOS_" . "batch_" . $i_str . ".xml";
            if(file_exists($filename)){print"\n unlink: $filename"; unlink($filename);}
            else {print"\n -nothing more to delete-"; return;}
        }
    }

    function combine_all_xmls($resource_id)
    {
        print"\n\n Start compiling all XML...\n";
        $old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id .".xml";
        $OUT = fopen($old_resource_path, "w+");
        $str = "<?xml version='1.0' encoding='utf-8' ?>\n";
        $str .= "<response\n";
        $str .= "  xmlns='http://www.eol.org/transfer/content/0.3'\n";
        $str .= "  xmlns:xsd='http://www.w3.org/2001/XMLSchema'\n";
        $str .= "  xmlns:dc='http://purl.org/dc/elements/1.1/'\n";
        $str .= "  xmlns:dcterms='http://purl.org/dc/terms/'\n";
        $str .= "  xmlns:geo='http://www.w3.org/2003/01/geo/wgs84_pos#'\n";
        $str .= "  xmlns:dwc='http://rs.tdwg.org/dwc/dwcore/'\n";
        $str .= "  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'\n";
        $str .= "  xsi:schemaLocation='http://www.eol.org/transfer/content/0.3 http://services.eol.org/schema/content_0_3.xsd'>\n";
        fwrite($OUT, $str);

        $i=0;
        while(true)
        {
            $i++;
            $i_str = self::format_number($i);
            $filename = CONTENT_RESOURCE_LOCAL_PATH . "Tropicos/temp_TROPICOS_" . "batch_" . $i_str . ".xml";
            if(!is_file($filename))
            {
                print" -end compiling XML's- ";
                break;
            }
            print " $i ";
            $READ = fopen($filename, "r");
            $contents = fread($READ,filesize($filename));
            fclose($READ);

            if($contents)
            {
                $pos1 = stripos($contents,"<taxon>");
                $pos2 = stripos($contents,"</response>");
                $str  = substr($contents,$pos1,$pos2-$pos1);
                fwrite($OUT, $str);
                //unlink($filename);
            }

        }
        fwrite($OUT, "</response>");fclose($OUT);
        print"\n All XML compiled\n -end-of-process- \n";
    }

    public static function get_tropicos_taxa($rec,$used_collection_ids)
    {
        $response = self::parse_xml($rec);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["source"]]) continue;
            $taxon = self::build_taxon($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["source"]] = true;
        }
        return array($page_taxa,$used_collection_ids);
    }

    function parse_xml($rec)
    {
        // Abutilon divaricatum var. divaricatum
        // Abies balsamea fo. phanerolepis

        $position_var = stripos($rec['taxon']," var.");
        $position_fo = stripos($rec['taxon']," fo.");
        $position_subsp = stripos($rec['taxon']," subsp.");
        if    (is_numeric($position_var))   $name = substr($rec['taxon'],0,$position_var);
        elseif(is_numeric($position_fo))    $name = substr($rec['taxon'],0,$position_fo);
        elseif(is_numeric($position_subsp)) $name = substr($rec['taxon'],0,$position_subsp);
        else                                $name = $rec['taxon'];

        $service = TROPICOS_NAME_SERVICE . $name . "&apikey=" . TROPICOS_API_KEY;
        if(is_numeric($position_var) || is_numeric($position_fo) || is_numeric($position_subsp))$service = str_replace("type=exact","type=wildcard",$service);

        if(!$xml = simplexml_load_file($service))
        {
            print"\n invalid name: " . $rec['taxon'];
            return array();
        }

        $arr_data=array();
        foreach($xml->Name as $name)
        {

            if(in_array(trim($name->Symbol),array("*","**","***","!!")))continue;

            print "\n<br>" . $name->NameId . " -- " . $name->ScientificName . "\n";
            $taxon_id = $name->NameId;

            $arr_objects=array();
            $arr_objects    = self::get_chromosome_count($taxon_id,$arr_objects);
            $arr_objects    = self::get_images($taxon_id,$arr_objects);
            $arr_objects    = self::get_distributions($taxon_id,$arr_objects);

            //if( sizeof($arr_objects)==0 && sizeof($arr_synonyms)==0 && sizeof($arr_taxon_ref)==0 )continue;
            if( sizeof($arr_objects)==0 )continue;

            $arr_synonyms   = self::get_synonyms($taxon_id);
            $arr_taxon_ref  = self::get_taxon_ref($taxon_id);

            $taxonomy       = self::get_taxonomy($taxon_id);

            $arr_data[]=array(  "identifier"    =>$taxon_id,
                                "source"        =>TROPICOS_TAXON_DETAIL_PAGE . $taxon_id,
                                "kingdom"       =>@$taxonomy['kingdom'],
                                "phylum"        =>@$taxonomy['phylum'],
                                "class"         =>@$taxonomy['class'],
                                "order"         =>@$taxonomy['order'],
                                "family"        =>@$taxonomy['family'],
                                "genus"         =>@$taxonomy['genus'],
                                "sciname"       =>$name->ScientificNameWithAuthors,
                                "taxon_refs"    =>$arr_taxon_ref,
                                "synonyms"      =>$arr_synonyms,
                                "commonNames"   =>array(), 
                                "data_objects"  =>$arr_objects
                             );
        }//foreach($xml->Name as $name)

        return $arr_data;
    }
    function get_taxonomy($taxon_id)
    {
        $taxonomy=array();
        $xml = simplexml_load_file(TROPICOS_API_SERVICE . $taxon_id . "/HigherTaxa?format=xml&apikey=" . TROPICOS_API_KEY);
        foreach($xml->Name as $rec)
        {
            if($rec->Rank == "kingdom") $taxonomy['kingdom']=$rec->ScientificNameWithAuthors;
            if($rec->Rank == "phylum")  $taxonomy['phylum']=$rec->ScientificNameWithAuthors;
            if($rec->Rank == "class")   $taxonomy['class']=$rec->ScientificNameWithAuthors;
            if($rec->Rank == "order")   $taxonomy['order']=$rec->ScientificNameWithAuthors;
            if($rec->Rank == "family")  $taxonomy['family']=$rec->ScientificNameWithAuthors;
            if($rec->Rank == "genus")   $taxonomy['genus']=$rec->ScientificNameWithAuthors;
        }
        return $taxonomy;
    }

    function get_taxon_ref($taxon_id)
    {
        $refs=array();
        $xml = simplexml_load_file(TROPICOS_API_SERVICE . $taxon_id . "/References?format=xml&apikey=" . TROPICOS_API_KEY);
        foreach($xml->NameReference as $rec)
        {
            if(!isset($rec->Reference->ReferenceId))continue;
            $ref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->Reference->ReferenceId);
            $citation = trim($rec->Reference->FullCitation);
            $refs[] = array("url"=>$ref_url, "ref"=>$citation);
        }
        return $refs;
    }

    function get_images($taxon_id,$arr_objects)
    {
        $xml = simplexml_load_file(TROPICOS_API_SERVICE . $taxon_id . "/Images?format=xml&apikey=" . TROPICOS_API_KEY);
        $with_image=0;
        foreach($xml->Image as $rec)
        {
            $with_image++;
            if($with_image > 15)break;//debug

            $description    = $rec->NameText . ". " . $rec->LongDescription;
            if($rec->PhotoLocation) $description .= " " . "Location: " . $rec->PhotoLocation . ".";
            if($rec->PhotoDate) $description .= " " . "Photo taken: " . $rec->PhotoDate . ".";
            if($rec->ImageKindText) $description .= " " . "Image kind: " . $rec->ImageKindText . ".";

            $valid_license = array("http://creativecommons.org/licenses/by/3.0/",
                                    "http://creativecommons.org/licenses/by-sa/3.0/",
                                    "http://creativecommons.org/licenses/by-nc/3.0/",
                                    "http://creativecommons.org/licenses/by-nc-sa/3.0/",
                                    "http://creativecommons.org/licenses/publicdomain/");
            if(!in_array(trim($rec->LicenseUrl),$valid_license))
            {
                print"\n invalid license - " . TROPICOS_IMAGE_DETAIL_PAGE . trim($rec->ImageId);
                continue;
            }
            $license = $rec->LicenseUrl;

            $agent=array();
            if($rec->Photographer) $agent[] = array("role" => "photographer" , "homepage" => "" , $rec->Photographer);
            //if($rec->Copyright) $agent[] = array("role" => "source" , "homepage" => "" , $rec->Copyright);

            $rightsHolder   = $rec->Copyright;
            $location   = $rec->PhotoLocation;
            $identifier = $rec->ImageId;
            $dataType   = "http://purl.org/dc/dcmitype/StillImage"; 
            $mimeType   = "image/jpeg";
            $title      = "";
            $subject    = "";
            $source     = TROPICOS_IMAGE_DETAIL_PAGE . $rec->ImageId;
            $mediaURL   = TROPICOS_IMAGE_LOCATION_LOW_BANDWIDTH . $rec->ImageId . "&maxwidth=600";

            $refs=array();
            $arr_objects = self::add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$agent,$license,$location,$rightsHolder,$refs,$subject,$arr_objects);
        }
        return $arr_objects;
    }

    function get_chromosome_count($taxon_id,$arr_objects)
    {
        $xml = simplexml_load_file(TROPICOS_API_SERVICE . $taxon_id . "/ChromosomeCounts?format=xml&apikey=" . TROPICOS_API_KEY);
        $html="";
        $refs = array(); $temp=array();
        $with_content=false;
        
        foreach($xml->ChromosomeCount as $rec)
        {
            if(!isset($rec->GametophyticCount) && !isset($rec->SporophyticCount))continue;
            $with_content=true;
            
            $citation = trim($rec->Reference->FullCitation);
            $short_citation = "";
            if($rec->Reference->ArticleTitle) $short_citation .= $rec->Reference->ArticleTitle . ". ";
            if($rec->Reference->AbbreviatedTitle) $short_citation .= $rec->Reference->AbbreviatedTitle . " ";
            if($rec->Reference->YearPublished) $short_citation .= $rec->Reference->YearPublished . ". ";
            if($rec->Reference->Collation) $short_citation .= $rec->Reference->Collation . ". ";
            $ref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->Reference->ReferenceId);
            
            if($rec->GametophyticCount) $html .= "Gametophytic count: " . $rec->GametophyticCount . "<br>";
            if($rec->SporophyticCount)  $html .= "Sporophytic count: " . $rec->SporophyticCount . "<br>";
            
            if(trim($rec->IPCNReferenceID))
            {
                $IPCNref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->IPCNReferenceID);
                $html .= "IPCN Ref.: " . "<a target='tropicos' href='" . $IPCNref_url . "'>" . $rec->IPCNAbbreviation . "</a><br>";
            }
            else $html.="IPCN Ref.: " . $rec->IPCNAbbreviation . "<br>";
            
            /*working but removed per CP
            $html.="<td valign='top'>" . "<a target='tropicos' href='" . $ref_url . "'>" . $short_citation . "</a></td>";
            */
            
            $html.="----- <br>";
            
            //this is to prevent getting duplicate references
            if(!in_array($citation,$temp)) $refs[] = array("url"=>$ref_url, "ref"=>$citation);                                                
            $temp[]=$citation;
        }
        
        if($with_content)
        {
            $description = $html;
            $source = TROPICOS_DOMAIN . "/Name/" . $taxon_id . "?tab=chromosomecounts";
            $identifier = $taxon_id . "_chromosome";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title      = "Chromosome Counts";    
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Genetics"; //debug
            //$subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            $agent = array(); $mediaURL=""; $location="";
            $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder = "Tropicos, botanical information system at the Missouri Botanical Garden - www.tropicos.org";
            $arr_objects = self::add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$agent,$license,$location,$rightsHolder,$refs,$subject,$arr_objects);
        }
        return $arr_objects;
    }
    
    function get_distributions($taxon_id,$arr_objects)
    {
        $xml = simplexml_load_file(TROPICOS_API_SERVICE . $taxon_id . "/Distributions?format=xml&apikey=" . TROPICOS_API_KEY);
        
        $refs = array(); $temp=array(); $temp2=array();
        $with_content=false;
        $html="";
        foreach($xml->Distribution as $rec)
        {
            if(!isset($rec->Location->CountryName))continue;
            $with_content=true;
            
            $citation = trim($rec->Reference->FullCitation);
            $short_citation = "";
            if($rec->Reference->ArticleTitle) $short_citation .= $rec->Reference->ArticleTitle . ". ";
            if($rec->Reference->AbbreviatedTitle) $short_citation .= $rec->Reference->AbbreviatedTitle . " ";
            if($rec->Reference->YearPublished) $short_citation .= $rec->Reference->YearPublished . ". ";
            if($rec->Reference->Collation) $short_citation .= $rec->Reference->Collation . ". ";
            $ref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->Reference->ReferenceId);
            
            //this is prevent getting duplicate distribution entry, even if API has duplicates.
            if(!in_array(trim($rec->Location->CountryName) . trim($rec->Location->RegionName),$temp2))
            {
                $html.= trim($rec->Location->CountryName) . " (" . trim($rec->Location->RegionName) . ")<br>";
                //$html.="<a target='tropicos' href='" . $ref_url . "'>" . $short_citation . "</a>";
            }
            $temp2[]=trim($rec->Location->CountryName) . trim($rec->Location->RegionName);
            
            //this is to prevent getting duplicate references
            if(!in_array($citation,$temp)) $refs[] = array("url"=>$ref_url, "ref"=>$citation);
            $temp[]=$citation;
        }
        
        if($with_content)
        {
            $description = $html;
            $source = TROPICOS_DOMAIN . "/Name/" . $taxon_id . "?tab=distribution";
            $identifier = $taxon_id . "_distribution";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title      = "";
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
            $agent = array(); $mediaURL=""; $location="";
            $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder = "Tropicos, botanical information system at the Missouri Botanical Garden - www.tropicos.org";
            $arr_objects = self::add_objects($identifier,$dataType,$mimeType,$title,$source,$description,$mediaURL,$agent,$license,$location,$rightsHolder,$refs,$subject,$arr_objects);
        }
        return $arr_objects;
    }
    
    function get_synonyms($taxon_id)
    {
        $arr_synonyms=array();
        $arr=array();
        $xml = simplexml_load_file(TROPICOS_API_SERVICE . $taxon_id . "/Synonyms?format=xml&apikey=" . TROPICOS_API_KEY);
        foreach($xml->Synonym as $syn)
        {
            $synonym = trim($syn->SynonymName->ScientificNameWithAuthors);
            $arr[$synonym]="";
        }
        foreach(array_keys($arr) as $synonym)
        {
            if($synonym) $arr_synonyms[] = array("synonym" => $synonym, "relationship" => "synonym");
        }                    
        return $arr_synonyms;
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
                              "references"=>$refs,
                              "subject"=>$subject
                            );
        return $arr_objects;
    }
    
    function build_taxon($rec)
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
                $referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url" , "value" => $ref['url']));
            }
            $refs[] = new \SchemaReference($referenceParameters);
        }
        $taxon["references"] = $refs;
        //end taxon reference

        //start common names
        foreach($rec["commonNames"] as $comname)
        {
            $taxon["commonNames"][] = new \SchemaCommonName(array("name" => $comname, "language" => ""));
        }
        //end common names

        if($rec["data_objects"])
        {
            foreach($rec["data_objects"] as $object)
            {
                $data_object = self::get_data_object($object);
                if(!$data_object) return false;
                $taxon["dataObjects"][] = new \SchemaDataObject($data_object);
            }
        }
        
        //start synonyms
        $taxon["synonyms"] = array();
        foreach($rec["synonyms"] as $syn)
        {
            $taxon["synonyms"][] = new \SchemaSynonym(array("synonym" => $syn['synonym'], "relationship" => $syn['relationship']));
        }
        //end synonyms
        
        $taxon_object = new \SchemaTaxon($taxon);
        return $taxon_object;
    }
    
    function get_data_object($rec)
    {
        $data_object_parameters = array();
        $data_object_parameters["identifier"]   = trim(@$rec["identifier"]);
        $data_object_parameters["source"]       = $rec["source"];
        $data_object_parameters["dataType"]     = $rec["dataType"];
        $data_object_parameters["mimeType"]     = $rec["mimeType"];
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
            $data_object_parameters["subjects"][] = new \SchemaSubject($subjectParameters);
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
            $agents[] = new \SchemaAgent($agentParameters);
        }
        $data_object_parameters["agents"] = $agents;
        //==========================================================================================
        $data_object_parameters["references"] = array();
        $ref=array();
        foreach($rec["references"] as $r)
        {
            if(!$r["ref"])continue;
            $referenceParameters = array();
            $referenceParameters["fullReference"] = Functions::import_decode($r["ref"]);
            if($r["url"])$referenceParameters["referenceIdentifiers"][] = new \SchemaReferenceIdentifier(array("label" => "url" , "value" => trim($r["url"])));
            $ref[] = new \SchemaReference($referenceParameters);
        }
        $data_object_parameters["references"] = $ref;
        //==========================================================================================
        if($rec["dataType"] == "http://purl.org/dc/dcmitype/Text")
        {
            $data_object_parameters["audiences"] = array();
            $audienceParameters = array();
            $audienceParameters["label"] = "Expert users";
            $data_object_parameters["audiences"][] = new \SchemaAudience($audienceParameters);
        }
        //==========================================================================================
        
        return $data_object_parameters;
    }
    
}
?>