<?php
namespace php_active_record;
/* connector: [329]
The partner provides a big tab-delimited text file for their images. The connector processes each row, assembles the data and generates the EOL XML.
Partner hasn't yet provided a permanent URL for the text file.
Just a one-time import.
*/

// define("BOLDS_IMAGE_EXPORT_FILE", "http://localhost/~eolit/eol_php_code/update_resources/connectors/files/BOLD_images/BOLD Images - CreativeCommons - Nov 2011 small.txt");
define("BOLDS_IMAGE_EXPORT_FILE", "http://opendata.eol.org/u/7597512/resources/BOLD Images - CreativeCommons - Nov 2011.txt");
define("BOLDS_SPECIES_URL", "http://www.boldsystems.org/views/taxbrowser.php?taxon=");

class BoldsImagesAPI
{
    public static function get_all_taxa($resource_id)
    {
        $data = self::prepare_data();
        $all_taxa = array();
        $used_collection_ids = array();
        $i = 0;
        $total = count(array_keys($data));
        foreach(array_keys($data) as $taxon)
        {
            $i++; print "\n$i of $total [$taxon]\n";
            $taxon_record = $data[$taxon];
            $taxon_record["name"] = $taxon;
            $arr = self::get_BoldsImages_taxa($taxon_record, $used_collection_ids);
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
        }
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        if(!($OUT = fopen($resource_path, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $resource_path);
          return;
        }
        fwrite($OUT, $xml);
        fclose($OUT);
    }

    function prepare_data()
    {
        $do_identifiers = array();
        $data = array();
        $filename = BOLDS_IMAGE_EXPORT_FILE;
        print "\nfilename: [$filename]";
        if(!($READ = fopen($filename, "r")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
          return;
        }
        $i = 0;
        //$limit = 10; //debug

        $ids_4fn = array();

        while(!feof($READ))
        {
            if($line = fgets($READ))
            {
                $i++; print "\n$i ";
                if($i == 1) continue;
                $line = trim($line);
                $fields = explode("\t", $line);
                $SampleID            = trim($fields[0]);
                $ProcessID           = trim($fields[1]);
                $Taxon               = trim($fields[2]);
                $Orientation         = trim($fields[3]);
                $Copyright_holder    = trim($fields[4]);
                $Copyright_year      = $fields[5];
                $Copyright           = trim($fields[6]);
                $Copyright_institute = trim($fields[7]);
                $Copyright_contact   = $fields[8];
                $Photographer        = trim($fields[9]);
                $URL                 = $fields[10];
                $rights_holder = $Copyright_holder;
                if($Copyright_institute)
                {
                    if($rights_holder) $rights_holder .= ", " . $Copyright_institute;
                    else $rights_holder = $Copyright_institute;
                }
                $description = "";
                if($SampleID) $description .= "Sample ID = " . $SampleID . "<br>";
                if($ProcessID) $description .= "Process ID = " . $ProcessID . "<br>";
                if($Orientation) $description .= "Orientation = " . $Orientation . "<br>";
                $agent = array();
                if($Photographer) $agent[] = array("role" => "photographer", "homepage" => "", "fullName" => $Photographer);
                if     (in_array($Copyright, array("CreativeCommons - Attribution Non-Commercial Share-Alike",
                                                   "CreativeCommons ? Attribution Non-Commercial Share-Alike",
                                                   "Creative Commons ? Attribution Non-Commercial Share-Alike"))) $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                elseif (in_array($Copyright, array("Creative Commons ? Attribution Non-Commercial",
                                                   "CreativeCommons ? Attribution Non-Commercial",
                                                   "creativecommons ? attribution + noncommercial",
                                                   "CreativeCommons - Attribution Non-Commercial",
                                                   "CreativeCommons ? Attribution Non-Commercial (by-nc)"))) $license = "http://creativecommons.org/licenses/by-nc/3.0/";
                elseif (in_array($Copyright, array("CreativeCommons - Attribution",
                                                   "Creative Commons ? Attribution"))) $license = "http://creativecommons.org/licenses/by/3.0/";
                elseif (in_array($Copyright, array("CreativeCommons - Attribution Share-Alike"))) $license = "http://creativecommons.org/licenses/by-sa/3.0/";
                else
                {
                    print "\n Alert: invalid license. [$Taxon]  \n";
                    continue;
                    /*
                    invalid:
                    CreativeCommons ? Attribution No Derivatives
                    CreativeCommons - Attribution Non-Commercial No Derivatives
                    CreativeCommons ? Attribution Non-Commercial No Derivatives
                    valid licenses:
                    http://creativecommons.org/licenses/publicdomain/
                    */
                }

                $do_id = $SampleID . "_" . $ProcessID . "_" . $Orientation;
                if (in_array($do_id, $do_identifiers)) 
                {
                    $path_parts = pathinfo($URL);
                    $do_id .= "_" . $path_parts['filename'];
                }
                else 
                {
                    $ids_4fn[$do_id] = 1;
                }

                $do_identifiers[] = $do_id;
                $data[$Taxon][] = array("identifier" => $do_id,
                                        "description" => $description,
                                        "rights_holder" => $rights_holder,
                                        "rights_statement" => "Copyright " . $Copyright_year,
                                        "license" => $license,
                                        "agent" => $agent,
                                        "mediaURL" => $URL);

            }
            //if($i >= $limit) break; //debug
        }

        // start utility
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "old_BOLDS_image_ids.txt";
        if(!($WRITE = fopen($filename, "w")))
        {
          debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
          return;
        }
        fwrite($WRITE, json_encode(array_keys($ids_4fn)));
        fclose($WRITE);
            // just testing - reading it back
            if(!($READ2 = fopen($filename, "r")))
            {
              debug(__CLASS__ .":". __LINE__ .": Couldn't open file: " . $filename);
              return;
            }
            $contents = fread($READ2, filesize($filename));
            fclose($READ2);
            $ids_4fn = json_decode($contents,true);
            echo "\n\n from text file: " . count($ids_4fn);
        // end utility
        
        
        fclose($READ);
        return $data;
    }

    public static function get_BoldsImages_taxa($taxon_record, $used_collection_ids)
    {
        $response = self::parse_xml($taxon_record);//this will output the raw (but structured) array
        $page_taxa = array();
        foreach($response as $rec)
        {
            if(@$used_collection_ids[$rec["source"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["source"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    function parse_xml($taxon_record)
    {
        $arr_data = array();
        $arr_objects = array();
        $arr_objects = self::get_images($taxon_record, $arr_objects);
        if(sizeof($arr_objects) == 0) return array();
        $arr_data[] = array("identifier"   => '',
                            "source"       => BOLDS_SPECIES_URL . urlencode($taxon_record['name']),
                            "kingdom"      => '',
                            "phylum"       => '',
                            "class"        => '',
                            "order"        => '',
                            "family"       => '',
                            "genus"        => '',
                            "sciname"      => $taxon_record['name'],
                            "taxon_refs"   => array(),
                            "synonyms"     => array(),
                            "commonNames"  => array(),
                            "data_objects" => $arr_objects
                           );
        return $arr_data;
    }

    function get_images($taxon_record, $arr_objects)
    {
        array_pop($taxon_record); //remove the last record because it is not dataObject
        $with_image = 0;
        foreach($taxon_record as $rec)
        {
            $with_image++;
            $identifier     = $rec['identifier'];
            $description    = $rec['description'];
            $license        = $rec['license'];
            $agent          = $rec['agent'];
            $rightsHolder   = $rec['rights_holder'];
            $location       = '';
            $dataType       = "http://purl.org/dc/dcmitype/StillImage";
            $mimeType       = "image/jpeg";
            $title          = "";
            $subject        = "";
            $source         = '';
            $mediaURL       = $rec['mediaURL'];
            $refs           = array();
            $arr_objects = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $arr_objects);
        }
        return $arr_objects;
    }

    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $arr_objects)
    {
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
                              "rightsHolder" => $rightsHolder,
                              "reference"    => $refs,
                              "subject"      => $subject,
                              "language"     => "en"
                            );
        return $arr_objects;
    }

}
?>