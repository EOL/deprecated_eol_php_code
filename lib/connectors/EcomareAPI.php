<?php
namespace php_active_record;
/* connector: 414 - Dutch Marine and Coastal Species Encyclopedia
Version 1:
Partner provided a zip file. This consists of individual customized taxon XML files.
The connector unzips the file, and processes each taxon XML to generate the final EOL XML.
The partner hasn't yet hosted the zip file. There might still be changes on how the raw files will be served.
This resource is still on preview mode.

Version 2:
Partner now provides/hosts an XML list of their taxa, and customized XML for each of their taxa.
http://www.zeeinzicht.nl/vleet/vleet_xml/_beknopt/encyclopedia_toc.xml
http://www.zeeinzicht.nl/vleet/vleet_xml/_beknopt/
*/

class EcomareAPI
{
    const ECOMARE_DOMAIN = "http://www.ecomare.nl/";
    const TAXON_SOURCE_URL = "http://www.ecomare.nl/index.php?id=";
    const DUTCH_DIR = 1;
    const ENGLISH_DIR = 3;
    const ECOMARE_SPECIES_LIST = "encyclopedia_toc.xml";
    const ECOMARE_SOURCE_DIR = "http://www.zeeinzicht.nl/vleet/vleet_xml/_beknopt/";

    function get_all_taxa()
    {
        $all_taxa = array();
        $used_collection_ids = array();
        $taxa = self::get_taxa_list();
        $i = 0;
        $total = sizeof($taxa);
        foreach($taxa as $id => $taxon)
        {
            $i++;
            print "\n $i of $total";
            $arr = self::get_Ecomare_taxa($taxon, $used_collection_ids);
            $page_taxa               = $arr[0];
            $used_collection_ids     = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa,$page_taxa);
        }
        return $all_taxa;
    }

    function get_taxa_list()
    {
        $taxa = array();
        $path_species_list = self::ECOMARE_SOURCE_DIR . self::ECOMARE_SPECIES_LIST;
        print "\n species list: " . $path_species_list;
        if($response = Functions::get_remote_file($path_species_list, DOWNLOAD_WAIT_TIME, 120))
        {
            $xml = simplexml_load_string($response);
            $i = 0;
            foreach($xml->subject_item as $item)
            {
                $id = trim($item->id);
                if($item->name_latin != "")
                {
                    $i++;
                    // if($i > 2) break; //debug
                    $sciname = self::get_sciname($item->name_latin);
                    $taxa[$id]['sciname'] = $sciname;
                    $taxa[$id]['id'] = $id;
                    print "\n\n $item->name_latin -- $id";
                    print "\n $sciname";
                    $objects = self::get_taxon_details($id);
                    foreach($objects as $object) $taxa[$id]['objects'][] = $object;
                }
            }
        }
        else print "\n Down: $path_species_list";
        return $taxa;
    }

    private function get_sciname($string)
    {
        $string = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis
        $temp = explode(",", trim($string));
        $temp = explode(";", trim($temp[0]));
        $temp = explode("/", trim($temp[0]));
        $temp = explode(" and ", trim($temp[0]));
        return trim($temp[0]);
    }

    function get_taxon_details($id)
    {
        $languages = array("en" => self::ENGLISH_DIR, "nl" => self::DUTCH_DIR);
        $description = "";
        $title = "";
        $identifier = "";
        $mediaURL = "";
        $mimeType = "";
        $rightsHolder = "";
        $source = "";
        $texts = array();
        $objects = array();
        $text_rightsHolder = "";
        foreach($languages as $language => $dir)
        {
            print "\n $language - $dir";
            if($language == "nl")
            {
                if(substr($description, strlen($description)-2, 2) == ". ") $description = substr($description, 0, strlen($description)-2). "; ";
                if(substr($title, strlen($title)-2, 2) == ". ") $title = substr($title, 0, strlen($title)-2) . "; ";
            }
            $xml_file = self::ECOMARE_SOURCE_DIR . "/$dir/" . $id . ".xml";
            sleep(3); // 3 seconds interval for every taxon XML access, so not to overwhelm the partner

            $trials = 1;
            while($trials <= 5)
            {
                print "\n Accessing: $xml_file";
                if($xml = Functions::get_hashed_response($xml_file))
                {
                    print " - OK ";
                    foreach($xml->content_item as $item)
                    {
                        if($item->type == 9) //image type
                        {
                            $source_url = str_ireplace("L=0", "L=2", $xml->subject_setup->source_url);
                            if(trim($item->file_name) != "")
                            {
                                $description .= $item->image_description != '' ? "" . $item->image_description . ". " : '';
                                $title .= $item->header != '' ? "" . $item->header . ". " : '';
                                $identifier     = "img_" . $item->uid;
                                $mediaURL       = self::ECOMARE_DOMAIN . $item->file_path . $item->file_name;
                                $mimeType       = Functions::get_mimetype($item->file_name);
                                $rightsHolder   = $item->image_copyright;
                                $source         = $source_url;
                            }
                            if(!in_array($item->bodytext, $texts))
                            {
                                $objects[] = array(
                                    "identifier"    => $item->uid,
                                    "mediaURL"      => '',
                                    "mimeType"      => 'text/html',
                                    "dataType"      => 'http://purl.org/dc/dcmitype/Text',
                                    "rightsHolder"  => '', //$text_rightsHolder
                                    "title"         => '',
                                    "description"   => strip_tags($item->bodytext),
                                    "source"        => $source_url,
                                    "license"       => "http://creativecommons.org/licenses/by-nc/3.0/",
                                    "subject"       => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology",
                                    "language"      => $language);
                                $texts[] = $item->bodytext;
                            }
                        }
                        elseif($item->type == 10) $text_rightsHolder = strip_tags($item->bodytext);
                    }
                    break; // gets out of the loop for a successful access
                }
                else
                {
                    $trials++;
                    print " Fail. Will wait for 30 seconds and will try again. Trial # " . $trials;
                    sleep(30);
                }
            }// access trial loop

        }// language loop

        $objects[] = array(
            "identifier"    => $identifier,
            "mediaURL"      => $mediaURL,
            "mimeType"      => $mimeType,
            "dataType"      => 'http://purl.org/dc/dcmitype/StillImage',
            "rightsHolder"  => $rightsHolder,
            "title"         => $title,
            "description"   => strip_tags($description),
            "source"        => $source,
            "license"       => "http://creativecommons.org/licenses/by-nc/3.0/",
            "subject"       => "",
            "language"      => "en");

        $i = 0;
        foreach($objects as $object)
        {
            if($object["mimeType"] == 'text/html') $objects[$i]["rightsHolder"] = $text_rightsHolder;
            $i++;
        }
        return $objects;
    }

    public static function get_Ecomare_taxa($taxon, $used_collection_ids)
    {
        $response = self::parse_xml($taxon);
        $page_taxa = array();
        foreach($response as $rec)
        {            
            if(@$used_collection_ids[$rec["identifier"]]) continue;
            $taxon = Functions::prepare_taxon_params($rec);
            if($taxon) $page_taxa[] = $taxon;
            @$used_collection_ids[$rec["identifier"]] = true;
        }
        return array($page_taxa, $used_collection_ids);
    }

    private function parse_xml($taxon)
    {
        $taxon_id = $taxon["id"];
        $arr_data = array();
        $arr_objects = array();
        $arr_objects = self::prepare_data_objects($taxon, $arr_objects);
        $refs = array();
        if(sizeof($arr_objects))
        {
            $sciname = trim(str_ireplace("spp.", "", $taxon['sciname']));
            $arr_data[] = array("identifier"   => $taxon_id,
                                "source"       => self::TAXON_SOURCE_URL . $taxon_id,
                                "kingdom"      => @$taxon["http://rs.tdwg.org/dwc/terms/kingdom"],
                                "phylum"       => @$taxon["http://rs.tdwg.org/dwc/terms/phylum"],
                                "class"        => @$taxon["http://rs.tdwg.org/dwc/terms/class"],
                                "order"        => @$taxon["http://rs.tdwg.org/dwc/terms/order"],
                                "family"       => @$taxon["http://rs.tdwg.org/dwc/terms/family"],
                                "genus"        => @$taxon["http://rs.tdwg.org/dwc/terms/genus"],
                                "sciname"      => $sciname,
                                "reference"    => $refs,
                                "synonyms"     => array(),
                                "commonNames"  => '',
                                "data_objects" => $arr_objects
                             );
        }
        return $arr_data;
    }

    private function prepare_data_objects($taxon, $arr_objects)
    {
        foreach($taxon['objects'] as $object)
        {
            $identifier     = $object['identifier'];
            $description    = @$object['description'];
            $mimeType       = $object['mimeType'];
            $dataType       = $object['dataType'];
            $title          = $object['title'];
            $subject        = $object['subject'];
            $mediaURL       = @$object['mediaURL'];
            $location       = "";
            $license        = $object['license'];
            $rightsHolder   = $object['rightsHolder'];
            $source         = $object['source'];
            $agent          = array();
            $refs           = array();
            $modified       = "";
            $created        = "";
            $language       = $object['language'];;
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language);
        }
        return $arr_objects;
    }

    private function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject, $modified, $created, $language)
    {
        return array( "identifier"   => $identifier,
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
                      "modified"     => $modified,
                      "created"      => $created,
                      "language"     => $language
                    );
    }

}
?>