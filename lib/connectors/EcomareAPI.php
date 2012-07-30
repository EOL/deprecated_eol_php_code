<?php
namespace php_active_record;
/* connector: 414
Partner provided a zip file. This consists of individual customized taxon XML files.
The connector unzips the file, and processes each taxon XML to generate the final EOL XML.
The partner hasn't yet hosted the zip file. There might still be changes on how the raw files will be served.
This resource is still on preview mode.
*/
class EcomareAPI
{
    const ECOMARE_DOMAIN = "http://www.ecomare.nl/";
    const TAXON_SOURCE_URL = "http://www.ecomare.nl/index.php?id=";
    const ECOMARE_ZIP_PATH = "voorbeeldset kwallen"; //this will change when we get the final zip file from them
    const DUTCH_DIR = 1;
    const ENGLISH_DIR = 3;

    function get_all_taxa($dwca_file)
    {
        $all_taxa = array();
        $used_collection_ids = array();

        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($dwca_file, self::ECOMARE_ZIP_PATH);
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];

        $taxa = self::get_taxa_list($archive_path);
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

        // remove tmp dir
        if($temp_dir) shell_exec("rm -fr $temp_dir");
        
        return $all_taxa;
    }

    function get_taxa_list($archive_path)
    {
        $taxa = array();
        $languages = array("nl", "en");
        foreach($languages as $language)
        {
            if($language == "en") $dir = self::ENGLISH_DIR;
            elseif($language == "nl") $dir = self::DUTCH_DIR;
            $path = $archive_path . self::ECOMARE_ZIP_PATH . "/xml/" . $dir . "/";
            foreach(glob($path . "*.xml") as $filename) 
            {
                $path_parts = pathinfo($filename);
                $id = $path_parts['filename'];
                $objects = self::get_taxon_details($id, $path, $language);
                foreach($objects as $object) $taxa[$id]['objects'][] = $object;
            }
        }
        $taxa = self::get_sciname($taxa);
        return $taxa;
    }

    function get_sciname($taxa)
    {
        foreach($taxa as $id => $value)
        {
            $html = Functions::get_remote_file(self::TAXON_SOURCE_URL . $id);
            if(preg_match("/Lat:(.*?)</ims", $html, $match)) $sciname = trim($match[1]);
            else if($id == 3599) $sciname = 'Scyphozoa';
            $sciname = trim(preg_replace('/\s*\([^)]*\)/', '', $sciname)); // removes parenthesis
            $taxa[$id]['sciname'] = $sciname;
            $taxa[$id]['id'] = $id;
            print "\n $id - $sciname";
        }
        return $taxa;
    }

    function get_taxon_details($id, $path, $language)
    {
        $xml_file = $path . $id . ".xml";
        print "\n filename: $xml_file";
        $xml = Functions::get_hashed_response($xml_file);
        $texts = array();
        $objects = array();
        foreach($xml->content_item as $item)
        {
            if($item->type == 9) //image type
            {
                if($language == "nl") // Dutch
                {
                    $objects[] = array(
                        "identifier"    => $item->file_name,
                        "mediaURL"      => self::ECOMARE_DOMAIN . $item->file_path . $item->file_name,
                        "mimeType"      => Functions::get_mimetype($item->file_name),
                        "dataType"      => 'http://purl.org/dc/dcmitype/StillImage',
                        "rightsHolder"  => $item->image_copyright,
                        "title"         => $item->header,
                        "description"   => $item->image_description,
                        "source"        => $xml->subject_setup->source_url,
                        "license"       => "http://creativecommons.org/licenses/by-nc/3.0/",
                        "subject"       => "",
                        "language"      => $language);
                }
                
                if(!in_array($item->bodytext, $texts))
                {
                    $objects[] = array(
                        "identifier"    => "text_" . $language . "_" . $item->file_name,
                        "mediaURL"      => '',
                        "mimeType"      => 'text/html',
                        "dataType"      => 'http://purl.org/dc/dcmitype/Text',
                        "rightsHolder"  => '', //$text_rightsHolder
                        "title"         => '',
                        "description"   => $item->bodytext,
                        "source"        => $xml->subject_setup->source_url,
                        "license"       => "http://creativecommons.org/licenses/by-nc/3.0/",
                        "subject"       => "http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology",
                        "language"      => $language);
                    $texts[] = $item->bodytext;
                }
                
            }
            elseif($item->type == 10) $text_rightsHolder = $item->bodytext;
        }

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
            $arr_data[] = array("identifier"   => $taxon_id,
                                "source"       => self::TAXON_SOURCE_URL . $taxon_id,
                                "kingdom"      => @$taxon["http://rs.tdwg.org/dwc/terms/kingdom"],
                                "phylum"       => @$taxon["http://rs.tdwg.org/dwc/terms/phylum"],
                                "class"        => @$taxon["http://rs.tdwg.org/dwc/terms/class"],
                                "order"        => @$taxon["http://rs.tdwg.org/dwc/terms/order"],
                                "family"       => @$taxon["http://rs.tdwg.org/dwc/terms/family"],
                                "genus"        => @$taxon["http://rs.tdwg.org/dwc/terms/genus"],
                                "sciname"      => $taxon['sciname'],
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