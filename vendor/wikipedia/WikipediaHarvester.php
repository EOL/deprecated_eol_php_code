<?php
namespace php_active_record;

class WikipediaHarvester
{
    private $mysqli;
    private $taxa_pages;
    private $pageids_to_update;
    private $pageids_to_ignore;
    private $resource_file;
    private $resource;
    
    function __construct()
    {
        $this->mysqli =& $GLOBALS['mysqli_connection'];
        $this->taxa_pages = array();
        $this->pageids_to_update = array();
        $this->resource_file = null;
        $this->resource = Resource::find(80);
        $this->base_directory_path = DOC_ROOT ."update_resources/connectors/files/";
    }
    
    function begin_wikipedia_harvest()
    {
        // delete the downloaded files
        $this->cleanup_wikipedia_dump();
        
        $last_part = $this->download_wikipedia_dump();
        if(!$last_part)
        {
            echo "\n\nCouldn't determine the last file to process\n$last_line\n\n";
            exit;
        }
        
        // find out which titles have been updated, and what the latest revision IDs are
        $this->load_update_information();
        
        // create new _temp file
        if(!($this->resource_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_temp.xml", "w+")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_temp.xml");
          return;
        }
        
        // start the resource file with the XML header
        fwrite($this->resource_file, \SchemaDocument::xml_header());
        
        // iterate through the Wikipedia dump adding Taxa to the XML file
        $this->iterate_files($last_part, "get_scientific_pages");
        
        // write the resource footer
        fwrite($this->resource_file, \SchemaDocument::xml_footer());
        fclose($this->resource_file);
        
        @unlink(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_previous.xml");
        @rename(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id.".xml", CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_previous.xml");
        rename(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_temp.xml", CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id.".xml");
        
        // generate the _delete.xml file
        $this->create_delete_file();
        
        // set the resource to Harvest Requested
        if(filesize(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id.".xml"))
        {
            $this->mysqli->update("UPDATE resources SET resource_status_id=".ResourceStatus::find_or_create_by_translated_label('Harvest Requested')->id." WHERE id=".$this->resource->id);
        }
    }
    
    function download_wikipedia_dump()
    {
        // download latest Wikipedia export
        shell_exec("curl ".$this->resource->accesspoint_url." -o ". $this->base_directory_path ."wikipedia.xml.bz2");
        // unzip the download
        shell_exec("bunzip2 ". $this->base_directory_path ."wikipedia.xml.bz2");
        // split the huge file into 300M chunks
        shell_exec("split -b 300m ". $this->base_directory_path ."wikipedia.xml ". $this->base_directory_path ."wikipedia/part_");
        
        // determine the filename of the last chunk
        $last_part = NULL;
        $last_line = exec("ls -l ". $this->base_directory_path ."wikipedia");
        if(preg_match("/part_([a-z]{2})$/", trim($last_line), $arr)) $last_part = $arr[1];
        return $last_part;
    }
    
    private function cleanup_wikipedia_dump()
    {
        // cleaning up downloaded files
        shell_exec("rm -f ". $this->base_directory_path ."wikipedia/*");
        shell_exec("rm -f ". $this->base_directory_path ."wikipedia.xml");
        shell_exec("rm -f ". $this->base_directory_path ."wikipedia.xml.bz2");
    }
    
    function create_delete_file()
    {
        // _delete.xml should have the WIKIPEDIA identifiers, not EOL data_object_ids
        $ids = @file(DOC_ROOT . 'temp/wikipedia_deleted.txt');
        if($ids)
        {
            if(!($delete_file = fopen(CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_delete.xml", "w+")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .CONTENT_RESOURCE_LOCAL_PATH . $this->resource->id."_delete.xml");
              return;
            }
            $result = $this->mysqli->query("SELECT identifier FROM data_objects WHERE id IN (". implode(",", $ids).")");
            while($result && $row=$result->fetch_assoc())
            {
                fwrite($delete_file, $row['identifier']."\n");
            }
            fclose($delete_file);
        }
    }
    
    function load_update_information()
    {
        $this->pageids_to_update = array();
        $this->pageids_to_ignore = array();
        // run the update checker to generate files containing updated/delete/unchanged records
        shell_exec(PHP_BIN_PATH . DOC_ROOT ."update_resources/connectors/helpers/wikipedia_update_check.php ENV_NAME=". $GLOBALS['ENV_NAME']);
        $lines = file(DOC_ROOT . "temp/wikipedia_updated.txt");
        $i = 0;
        foreach($lines as $line)
        {
            $i++;
            if($i == 1) continue;
            $parts = explode("\t", trim($line));
            if(!isset($parts[2])) continue;  // checking for title, 3 columns
            
            $data_object_id = $parts[0];
            $new_revision_id = $parts[1];
            $pageid = $parts[2];
            $title = strtolower($parts[3]);
            $date = $parts[4];
            $this->pageids_to_update[$pageid] = array(  'rev_id' => $new_revision_id,
                                                        'title' => $title,
                                                        'data_object_id' => $data_object_id,
                                                        'date' => $date);
        }
        
        $lines = file(DOC_ROOT . "temp/wikipedia_unchanged.txt");
        $i = 0;
        foreach($lines as $line)
        {
            $i++;
            if($i == 1) continue;
            $parts = explode("\t", trim($line));
            if(!isset($parts[1])) continue;  // checking for title, 3 columns
            
            $data_object_id = $parts[0];
            $pageid = $parts[1];
            $date = $parts[2];
            $this->pageids_to_ignore[$pageid] = array(  'data_object_id' => $data_object_id,
                                                        'date' => $date);
        }
    }
    
    function iterate_files($last_part, $callback)
    {
        list($major, $minor) = str_split($last_part);
        $ord1 = ord("a");
        $ord2 = ord("a");
        
        $left_overs = "";
        while($ord1 <= ord($major))
        {
            while($ord2 <= ord("z"))
            {
                $left_overs = $this->process_file(chr($ord1).chr($ord2), $left_overs, $callback);
                
                if($ord1 == ord($major) && $ord2 == ord($minor))
                {
                    break;
                }
                $ord2++;
                //if($ord2 == ord("b")) break;
            }
            
            $ord1++;
            $ord2 = ord("a");
            //break;
        }
    }
    
    function process_file($part_suffix, $left_overs, $callback)
    {
        echo("Processing file $part_suffix with callback $callback\n");
        flush();
        if(!($FILE = fopen($this->base_directory_path ."wikipedia/part_".$part_suffix, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " .$this->base_directory_path ."wikipedia/part_".$part_suffix);
          return;
        }
        
        $current_page = $left_overs;
        static $page_number = 0;
        while(!feof($FILE))
        {
            if($line = fgets($FILE))
            {
                $current_page .= $line;
                $trim_line = trim($line);
                
                if($trim_line == "<page>")
                {
                    $current_page = $line;
                }
                if($trim_line == "</page>")
                {
                    if($page_number % 50000 == 0)
                    {
                        echo("page: $page_number\n");
                        echo("memory: ".memory_get_usage()."\n");
                        flush();
                    }
                    $page_number++;
                    
                    call_user_func(array(get_class($this), $callback), $current_page);
                    $current_page = "";
                }
            }
        }
        return $current_page;
    }
    
    
    function get_scientific_pages($xml)
    {
        if(preg_match("/\{\{\s*Taxobox/ims", $xml, $arr))
        {
            $count = count($this->taxa_pages);
            if($count && $count%1000==0) echo "taxon: $count\n";
            //if($count >= 10) return false;
            
            $page = new \WikiPage($xml);
            if(preg_match("/wikipedia/ims", $page->title)) return false;
            if(preg_match("/taxobox/ims", $page->title)) return false;
            if(preg_match("/template/ims", $page->title)) return false;
            
            // return if we don't need to update this page
            if(isset($this->pageids_to_ignore[$page->pageid]))
            {
                echo "Ignoring ".$page->title."\n";
                debug("Ignoring ".$page->title);
                return false;
            }
            
            if(isset($this->pageids_to_update[$page->pageid]))
            {
                $arr = $this->pageids_to_update[$page->pageid];
                $page->timestamp = $arr['date'];
                $page->revision = $arr['rev_id'];
            }
            
            $this->taxa_pages[$page->pageid] = true;
            
            echo $page->title."\n";
            if($taxon_params = $page->taxon_parameters())
            {
                if($data_object_params = $page->data_object_parameters())
                {
                    $taxon_params['dataObjects'][] = new \SchemaDataObject($data_object_params);
                }else echo "   no data object\n";
                
                $taxon = new \SchemaTaxon($taxon_params);
                fwrite($this->resource_file, $taxon->__toXML());
            }else
            {
                echo "   no taxon\n";
                debug("$page->title: no taxon\n");
                return false;
            }
        }
    }
    
    public static function force_import($revision_id)
    {
        $api_url = "http://en.wikipedia.org/w/api.php?action=query&format=xml&revids=$revision_id&export";
        $xml = Functions::get_hashed_response_fake_browser($api_url);
        $export_xml = simplexml_load_string($xml->query->export);
        
        $page = new \WikiPage($export_xml->page->asXML());
        if(preg_match("/wikipedia/ims", $page->title)) return false;
        if(preg_match("/taxobox/ims", $page->title)) return false;
        if(preg_match("/template/ims", $page->title)) return false;
        
        echo $page->title."\n";
        if($taxon_params = $page->taxon_parameters())
        {
            if($data_object_params = $page->data_object_parameters())
            {
                $taxon_params['dataObjects'][] = new \SchemaDataObject($data_object_params);
            }else echo "   no data object\n";
            
            $taxon = new \SchemaTaxon($taxon_params);
            $mini_doc = \SchemaDocument::get_taxon_xml(array($taxon));
            $mini_doc_xml = simplexml_load_string($mini_doc, null, LIBXML_NOCDATA);
            $taxon_xml = $mini_doc_xml->taxon[0];
            echo($mini_doc);
            print_r($taxon);
            
            $wikipedia_resource = Resource::wikipedia();
            $parsed_taxon = SchemaParser::read_taxon_xml($taxon_xml, $wikipedia_resource);
            list($hierarchy_entry, $new_data_object) = SchemaConnection::force_wikipedia_taxon($parsed_taxon);
            if(@!$new_data_object->id) return false;
            
            $GLOBALS['db_connection']->update("UPDATE data_objects SET published=0 WHERE guid='$new_data_object->guid' AND id!=$new_data_object->id");
            $GLOBALS['db_connection']->update("UPDATE data_objects SET published=1, vetted_id=". Vetted::trusted()->id .", visibility_id=". Visibility::visible()->id ." WHERE id=$new_data_object->id");
            $GLOBALS['db_connection']->update("UPDATE data_objects_hierarchy_entries dohe JOIN hierarchy_entries he ON (dohe.hierarchy_entry_id=he.id) JOIN taxon_concepts tc ON (he.taxon_concept_id=tc.id) SET he.published=1, tc.published=1 WHERE dohe.data_object_id=$new_data_object->id");
            $GLOBALS['db_connection']->insert("INSERT IGNORE INTO data_objects_taxon_concepts VALUES ($hierarchy_entry->taxon_concept_id, $new_data_object->id)");
            $GLOBALS['db_connection']->insert("INSERT IGNORE INTO data_objects_table_of_contents (SELECT doii.data_object_id, ii.toc_id FROM data_objects_info_items doii JOIN info_items ii ON (doii.info_item_id=ii.id) where doii.data_object_id=$new_data_object->id)");
            return true;
        }
    }
}

?>
