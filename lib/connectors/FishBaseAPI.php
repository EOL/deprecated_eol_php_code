<?php
namespace php_active_record;
/* connector: [42] - This is now scheduled as a cron task.
We created a script for FishBase and turned it over to them. This script is now installed in their system.
The script reads their MS Acccess database and creates tab-delimited text files.
They then zip these files and host it in their server.
The connector in this page then reads this zip file, extracts, assembles the information and generate the EOL XML.
FishBase contacts are: Stacy and Skit 
"Christian Stacy Militante" <fin.csd.militante@gmail.com>
"Josephine Skit Barile" <j.barile@fin.ph>
*/
class FishBaseAPI
{
    public function __construct($test_run = false)
    {
        $this->test_run = $test_run;
        // $this->fishbase_data = "http://localhost/~eolit/cp/FishBase/fishbase_in_folder.zip";
        // $this->fishbase_data = "http://localhost/~eolit/cp/FishBase/fishbase_not_in_folder.zip";
        // $this->fishbase_data = "http://localhost/~eolit/mtce2/GenerateEOLdata_from_stacy/GenerateEOLdata/txt/Archive.zip";
        // $this->fishbase_data = "http://localhost/~eolit/cp/FishBase/fishbase.zip";
        $this->fishbase_data = "http://www.fishbase.us/FB_data_for_EOL/fishbase.zip";
        if($this->test_run) $this->fishbase_data = "http://dl.dropbox.com/u/7597512/FishBase/fishbase_not_in_folder.zip";
        $this->TAXON_PATH                       = "";
        $this->TAXON_COMNAMES_PATH              = "";
        $this->TAXON_DATAOBJECT_PATH            = "";
        $this->TAXON_DATAOBJECT_AGENT_PATH      = "";
        $this->TAXON_DATAOBJECT_REFERENCE_PATH  = "";
        $this->TAXON_REFERENCES_PATH            = "";
        $this->TAXON_SYNONYMS_PATH              = "";
        $this->TEMP_FILE_PATH                   = "";
    }

    function get_all_taxa($resource_id)
    {
        $data = self::prepare_data();
        if($data === false) return false;
        $taxa                        = $data["taxon"]; 
        $taxon_comnames              = $data["taxon_comnames"];
        $taxon_references            = $data["taxon_references"];
        $taxon_synonyms              = $data["taxon_synonyms"];
        $taxon_dataobject            = $data["taxon_dataobject"];
        $GLOBALS['taxon_dataobject_agent'] = $data["taxon_dataobject_agent"];
        $GLOBALS['taxon_dataobject_reference']  = $data["taxon_dataobject_reference"];
        $all_taxa = array();
        $i = 0;
        $total = count(array_keys($taxa));
        $batch = 1000; //debug orig 1000
        $batch_count = 0;
        foreach($taxa as $taxon)
        {
            // if($taxon["dc_identifier"] != "FB-47873") continue; // debug
            $i++;
            debug("\n$i of $total " . $taxon["dwc_ScientificName"]);
            $taxon_record["taxon"] = $taxon;
            $taxon_id = $taxon["int_id"];
            $taxon_record["common_names"] = @$taxon_comnames[$taxon_id];
            $taxon_record["references"] = @$taxon_references[$taxon_id];
            $taxon_record["synonyms"] = @$taxon_synonyms[$taxon_id];
            $taxon_record["dataobjects"] = @$taxon_dataobject[$taxon_id];
            $arr = self::get_FishBase_taxa($taxon_record);
            $page_taxa = $arr[0];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
            if($i % $batch == 0)
            {
                $batch_count++;
                $xml = \SchemaDocument::get_taxon_xml($all_taxa);
                $resource_path = $this->TEMP_FILE_PATH . "FB_" . $batch_count . ".xml";
                if(!($OUT = fopen($resource_path, "w")))
                {
                  debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $resource_path);
                  return;
                }
                fwrite($OUT, $xml);
                fclose($OUT);
                $all_taxa = array();
            }
        }
        //last batch
        $batch_count++;
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = $this->TEMP_FILE_PATH . "FB_" . $batch_count . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $resource_path);
          return;
        }
        fwrite($OUT, $xml);
        fclose($OUT);
        Functions::combine_all_eol_resource_xmls($resource_id, $this->TEMP_FILE_PATH . "FB_*.xml");
        self::delete_temp_files($this->TEMP_FILE_PATH . "FB_*.xml");

        // remove tmp dir
        $this->TEMP_FILE_PATH = str_ireplace("/fishbase", "", $this->TEMP_FILE_PATH);
        if($this->TEMP_FILE_PATH) shell_exec("rm -fr $this->TEMP_FILE_PATH");

        if($this->test_run) return $all_taxa; //used in testing
    }

    function load_zip_contents()
    {
        $this->TEMP_FILE_PATH = create_temp_dir() . "/";
        if($file_contents = Functions::get_remote_file($this->fishbase_data, array('timeout' => 172800)))
        {
            $temp_file_path = $this->TEMP_FILE_PATH . "/fishbase.zip";
            if(!($TMP = fopen($temp_file_path, "w")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $temp_file_path);
              return;
            }
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip -o $temp_file_path -d $this->TEMP_FILE_PATH");

            if(!file_exists($this->TEMP_FILE_PATH . "/taxon.txt")) 
            {
                $this->TEMP_FILE_PATH = str_ireplace(".zip", "", $temp_file_path);
                if(!file_exists($this->TEMP_FILE_PATH . "/taxon.txt")) return;
            }

            $this->TAXON_PATH                       = $this->TEMP_FILE_PATH . "/taxon.txt";
            $this->TAXON_COMNAMES_PATH              = $this->TEMP_FILE_PATH . "/taxon_comnames.txt";
            $this->TAXON_DATAOBJECT_PATH            = $this->TEMP_FILE_PATH . "/taxon_dataobject.txt";
            $this->TAXON_DATAOBJECT_AGENT_PATH      = $this->TEMP_FILE_PATH . "/taxon_dataobject_agent.txt";
            $this->TAXON_DATAOBJECT_REFERENCE_PATH  = $this->TEMP_FILE_PATH . "/taxon_dataobject_reference.txt";
            $this->TAXON_REFERENCES_PATH            = $this->TEMP_FILE_PATH . "/taxon_references.txt";
            $this->TAXON_SYNONYMS_PATH              = $this->TEMP_FILE_PATH . "/taxon_synonyms.txt";
        }
        else
        {
            debug("\n\n Connector terminated. Remote files are not ready.\n\n");
            return;
        }
    }

    function prepare_data()
    {
        self::load_zip_contents();
        //taxon
        $fields = array("TaxonID", "dc_identifier", "dc_source", "dwc_Kingdom", "dwc_Phylum", "dwc_Class", "dwc_Order", "dwc_Family", "dwc_Genus", "dwc_ScientificName", "dcterms_created", "dcterms_modified", "int_id", "ProviderID");
        $taxon = self::make_array($this->TAXON_PATH, $fields, "", array(0,10,11,13));
        if($taxon === false) return false;
        //taxon_comnames
        $fields = array("commonName", "xml_lang", "int_id");
        $taxon_comnames = self::make_array($this->TAXON_COMNAMES_PATH, $fields, "int_id");
        //taxon_dataobject
        $fields = array("TaxonID", "dc_identifier", "dataType", "mimeType", "dcterms_created", "dcterms_modified", "dc_title", "dc_language", "license", "dc_rights", "dcterms_bibliographicCitation", "dc_source", "subject", "dc_description", "mediaURL", "thumbnailURL", "location", "xml_lang", "geo_point", "lat", "long", "alt", "timestamp", "int_id", "int_do_id", "dc_rightsHolder");
        $taxon_dataobject = self::make_array($this->TAXON_DATAOBJECT_PATH, $fields, "int_id", array(0,4,5,7,17,18,19,20,21,22));
        //taxon_dataobject_agent
        $fields = array("agent", "homepage", "logoURL", "role", "int_do_id", "timestamp");
        $taxon_dataobject_agent = self::make_array($this->TAXON_DATAOBJECT_AGENT_PATH, $fields, "int_do_id", array(5));
        //taxon_dataobject_reference
        $fields = array("reference", "bici", "coden", "doi", "eissn", "handle", "isbn", "issn", "lsid", "oclc", "sici", "url", "urn", "int_do_id");
        $taxon_dataobject_reference = self::make_array($this->TAXON_DATAOBJECT_REFERENCE_PATH, $fields, "int_do_id", array(1,2,3,4,5,7,8,9,10,12));
        //taxon_references
        $fields = array("reference", "bici", "coden", "doi", "eissn", "handle", "isbn", "issn", "lsid", "oclc", "sici", "url", "urn", "int_id", "timestamp", "autoctr");
        $taxon_references = self::make_array($this->TAXON_REFERENCES_PATH, $fields, "int_id", array(1,2,3,4,5,7,8,9,10,12,14,15));
        //taxon_synonyms
        $fields = array("synonym", "author", "relationship", "int_id", "timestamp", "autoctr");
        $taxon_synonyms = self::make_array($this->TAXON_SYNONYMS_PATH, $fields, "int_id", array(1,4,5));
        return array("taxon"                        => $taxon,
                     "taxon_comnames"               => $taxon_comnames,
                     "taxon_dataobject"             => $taxon_dataobject,
                     "taxon_dataobject_agent"       => $taxon_dataobject_agent,
                     "taxon_dataobject_reference"   => $taxon_dataobject_reference,
                     "taxon_references"             => $taxon_references,
                     "taxon_synonyms"               => $taxon_synonyms);
    }

    static function make_array($filename, $fields, $index_key="", $excluded_fields=array(), $separator="\t")
    {
        /*
        $excluded_fields can be array of fieldnames e.g. array("taxonID", "scientificName");
        or can be array of index values of the fields array e.g. array("0", "1", "3")
        */
        $data = array();
        $included_fields = array();
        foreach(new FileIterator($filename) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $values = explode($separator, $line);
                $i = 0;
                $temp = array();
                $continue_save = false;
                if(!$fields) $fields = array_map('trim', $values);
                foreach($fields as $field)
                {
                    if(is_int(@$excluded_fields[0])) $compare = $i;
                    else                             $compare = $field;
                    if(!in_array($compare, $excluded_fields))
                    {
                        $temp[$field] = trim(@$values[$i]);
                        $included_fields[$field] = 1;
                        if($temp[$field] != "") $continue_save = true; // as long as there is a single field with value then the row will be saved
                    }
                    $i++;
                }
                if($continue_save) $data[] = $temp;
            }
        }
        $included_fields = array_keys($included_fields);
        if($index_key)
        {
            $included_fields = array_unique($included_fields);
            return self::assign_key_to_table($data, $index_key, $included_fields);
        }
        else return $data;
    }

    function assign_key_to_table($table, $index_key, $included_fields)
    {
        $data = array();
        $included_fields = array_diff($included_fields, array($index_key));
        foreach($table as $record)
        {
            $index_value = $record["$index_key"];
            $temp = array();
            foreach($included_fields as $field) $temp[$field] = $record[$field];
            $data[$index_value][] = $temp;
        }
        return $data;
    }

    public static function get_FishBase_taxa($taxon_record)
    {
        $response = self::parse_xml($taxon_record);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
        }
        return array($page_taxa);
    }

    function parse_xml($taxon_record)
    {
        $arr_data = array();
        $arr_objects = array();
        $arr_objects = self::get_images($taxon_record['dataobjects'], $arr_objects);
        $refs = self::get_references($taxon_record['references']);
        $synonyms = self::get_synonyms($taxon_record['synonyms']);
        $common_names = self::get_common_names($taxon_record['common_names']);
        $arr_data[] = array("identifier"   => $taxon_record['taxon']['dc_identifier'],
                            "source"       => $taxon_record['taxon']['dc_source'],
                            "kingdom"      => $taxon_record['taxon']['dwc_Kingdom'],
                            "phylum"       => $taxon_record['taxon']['dwc_Phylum'],
                            "class"        => $taxon_record['taxon']['dwc_Class'],
                            "order"        => $taxon_record['taxon']['dwc_Order'],
                            "family"       => utf8_encode($taxon_record['taxon']['dwc_Family']),
                            "genus"        => utf8_encode($taxon_record['taxon']['dwc_Genus']),
                            "sciname"      => utf8_encode($taxon_record['taxon']['dwc_ScientificName']),
                            "reference"    => $refs, // formerly taxon_refs
                            "synonyms"     => $synonyms,
                            "commonNames"  => $common_names,
                            "data_objects" => $arr_objects
                           );
        return $arr_data;
    }

    function get_images($dataobjects, $arr_objects)
    {
        foreach($dataobjects as $rec)
        {
            $agent = array();
            foreach($GLOBALS['taxon_dataobject_agent'][$rec['int_do_id']] as $person)
            {
                if(trim($person['agent']) != "") $agent[] = array("role" => $person['role'], "homepage" => $person['homepage'], "fullName" => $person['agent']);
            }
            $refs = self::get_references(@$GLOBALS['taxon_dataobject_reference'][$rec['int_do_id']]);
            $identifier     = $rec['dc_identifier'];
            $description    = utf8_encode($rec['dc_description']);
            $license        = $rec['license'];
            $agent          = $agent;
            $rightsHolder   = $rec['dc_rightsHolder'];
            $rights         = $rec['dc_rights'];
            $location       = utf8_encode($rec['location']);
            $dataType       = $rec['dataType'];
            $mimeType       = $rec['mimeType'];
            $title          = $rec['dc_title'];
            $subject        = $rec['subject'];
            $source         = $rec['dc_source'];
            $mediaURL       = $rec['mediaURL'];
            $refs           = $refs;
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $arr_objects);
        }
        return $arr_objects;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rights, $rightsHolder, $refs, $subject, $arr_objects)
    {
        if(!Functions::is_utf8($description)) 
        {
            echo "\n not utf8 desc: [" . $description . "]\n";
            return $arr_objects;
        }
        $arr_objects[]=array( "identifier"   => $identifier,
                              "dataType"     => $dataType,
                              "mimeType"     => $mimeType,
                              "title"        => $title,
                              "source"       => $source,
                              "description"  => $description,
                              "mediaURL"     => $mediaURL,
                              "agent"        => $agent,
                              "license"      => $license,
                              "location"     => $location,
                              "rights"       => $rights,
                              "rightsHolder" => $rightsHolder,
                              "reference"    => $refs,
                              "subject"      => $subject,
                              "language"     => "en"
                            );
        return $arr_objects;
    }

    function get_references($references)
    {
        $refs = array();
        if($references)
        {
            foreach($references as $reference) 
            {
                $ref = utf8_encode($reference['reference']);
                if(Functions::is_utf8($ref)) $refs[] = array("url" => $reference['url'], "fullReference" => Functions::import_decode($ref));
                else echo "\n not utf8 ref: [" . $ref . "]\n";
            }
        }
        return $refs;
    }

    function get_synonyms($synonyms)
    {
        $arr_synonyms = array();
        if($synonyms) 
        {
            foreach($synonyms as $name) 
            {
                $relationship = '';
                if(strtolower($name['relationship']) != 'xxx') $relationship = $name['relationship'];
                $arr_synonyms[] = array("synonym" => utf8_encode($name['synonym']), "relationship" => $relationship);
            }
        }
        return $arr_synonyms;
    }

    function get_common_names($names)
    {
        $arr_names = array();
        if($names) 
        {
            foreach($names as $name) 
            {
                $common = utf8_encode($name['commonName']);
                if(Functions::is_utf8($common)) $arr_names[] = array("name" => Functions::import_decode($common), "language" => $name['xml_lang']);
                else echo "\n not utf8 common name: [" . $common . "]\n";
            }
        }
        return $arr_names;
    }

    function delete_temp_files($files)
    {
        foreach (glob($files) as $filename) unlink($filename);
    }

}
?>
