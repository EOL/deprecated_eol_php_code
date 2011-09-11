<?php
namespace php_active_record;
/* connector: [266]  */
/*
Steps before accepting a name from US Fish and Wildlife Services
- search the name through the API e.g. api/search/Gadus morhua
- if name == canonical_form(entry->title), proceed
- if there is multiple results, use the name with the most no. of data objects
*/

class USFishWildlifeAPI
{
    const API_URL           = "http://www.eol.org/api/search/";
    const TEXT_FILE_FOR_PARTNER  = "/update_resources/connectors/files/USFWS/names_without_pages_in_eol.txt"; //report back to DiscoverLife
    const TEMP_FILE_PATH    = "/update_resources/connectors/files/USFWS/";

    //const TAXA_LIST_FILE = "FWS complete18 May 2011 removed duplicate rows.xls";
    const TAXA_LIST_FILE = "FWS complete18 May 2011 removed duplicate rows_small.xls";

    const ANIMAL_LIST = "http://ecos.fws.gov/tess_public/pub/listedAnimals.jsp";
    const PLANT_LIST = "http://ecos.fws.gov/tess_public/pub/listedPlants.jsp";

    /* local HTML copies
    const ANIMAL_LIST = "http://localhost/~eolit/cp/USFWS/listedAnimals.jsp.html";
    const PLANT_LIST = "http://localhost/~eolit/cp/USFWS/listedPlants.jsp.html";
    */
    
    const SPECIES_PROFILE_PAGE = "http://ecos.fws.gov/speciesProfile/profile/speciesProfile.action?spcode=";

    public static function get_all_taxa_keys($resource_id)
    {
        require_library('CheckIfNameHasAnEOLPage');
        $func = new CheckIfNameHasAnEOLPage();

        $GLOBALS['animal_plant_list'] = self::prepare_animal_plant_list();
        $taxa_objects = self::prepare_taxa_list();
        $all_taxa = array();
        $used_collection_ids = array();

        //initialize text file for USFWS
        self::initialize_text_file(DOC_ROOT . self::TEXT_FILE_FOR_PARTNER);

        $i = 0;
        $save_count = 0;
        $no_eol_page = 0;
        
        foreach($taxa_objects as $name => $taxon)
        {
            $i++;
            //filter names. Process only those who already have a page in EOL. Report back to USFWS names not found in EOL
            $arr = $func->check_if_name_has_EOL_page($name);
            $if_name_has_page_in_EOL = $arr[0];
            if(!$if_name_has_page_in_EOL)
            {
                print "\n $i - no EOL page ($name)";
                $no_eol_page++;
                self::store_name_to_text_file($name);
                continue;
            }
            
            print "\n $i -- ";
            print $taxon['NAME'];
            
            //================================
            $arr = self::get_usfws_taxa($taxon, $used_collection_ids);
            $page_taxa              = $arr[0];
            $used_collection_ids    = $arr[1];
            if($page_taxa) $all_taxa = array_merge($all_taxa, $page_taxa);
            unset($page_taxa);
        }
        
        $xml = \SchemaDocument::get_taxon_xml($all_taxa);
        $resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . ".xml";
        $OUT = fopen($resource_path, "w");
        fwrite($OUT, $xml);
        fclose($OUT);
                
        $with_eol_page = $i - $no_eol_page;
        print "\n\n total = $i \n With EOL page = $with_eol_page \n No EOL page = $no_eol_page \n\n";
    }

    private function prepare_animal_plant_list()
    {
        $list = array();
        $urls[] = self::ANIMAL_LIST;
        $urls[] = self::PLANT_LIST;
        foreach($urls as $index => $url)
        {
            $html = Functions::get_remote_file($url);                                        
            $html = str_ireplace("displaytagEvenRow", "displaytagRow", $html);
            $html = str_ireplace("displaytagOddRow", "displaytagRow", $html);
            if(preg_match_all("/<tr class\=\"displaytagRow\">(.*?)<\/tr>/ims", $html, $matches)) 
            {
                $rows = $matches[1];
                foreach($rows as $row)
                {
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $matches)) 
                    {
                        $column = $matches[1];
                        if($index == 0) //for animals
                        {
                            //continue;
                            $common_name_column = $column[0];
                            $scientific_name_column = $column[1];
                            $family = "";
                            $where_listed = $column[4];
                        } 
                        else //for plants
                        {
                            $common_name_column = $column[1];
                            $scientific_name_column = $column[0];
                            $family = $column[4];
                            $where_listed = "";
                        }
                        if(preg_match("/spcode\=(.*)\"/ims", $scientific_name_column, $matches)) 
                        {
                            $species_code = $matches[1];
                            @$list[$species_code][] = array("common name" => $common_name_column, 
                                                            "scientific name url" => $scientific_name_column,
                                                            "scientific name" => strip_tags($scientific_name_column),
                                                            "species group" => $column[2],
                                                            "historic range" => $column[3],
                                                            "where listed" => $where_listed,
                                                            "family" => $family,
                                                            "listing status" => $column[5],
                                                            "critical habitat" => $column[6],
                                                            "special rules" => $column[7]
                                                           );
                        }
                    }
                }
            } //no preg_match
        }
        //print"<pre>"; print_r($list);exit;
        return $list;
    }
    
    function prepare_taxa_list()
    {
        $taxa = array();
        $filename = DOC_ROOT . self::TEMP_FILE_PATH . self::TAXA_LIST_FILE;
        require_library('XLSParser');
        $parser = new XLSParser();
        $taxa = $parser->prepare_data($parser->convert_sheet_to_array($filename), "single", "NAME", "NAME", "USFWS SPECIES PROFILE URL", "DISPLAYED TEXT", "SOURCE LIST");
        return $taxa;
    }

    public static function get_usfws_taxa($taxon, $used_collection_ids)
    {
        $response = self::prepare_object($taxon);//this will output the raw (but structured) array
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
    
    function store_name_to_text_file($name)
    {
        /* This text file will be given to USFWS so they can fix their names */
        if($fp = fopen(DOC_ROOT . self::TEXT_FILE_FOR_PARTNER, "a"))
        {
            fwrite($fp, $name . "\n");
            fclose($fp);
        }
    }

    function prepare_object($taxon_rec)
    {
        $taxa_data = array();
        print $taxon_rec["NAME"] . "\n";
        print $taxon_rec["USFWS SPECIES PROFILE URL"] . "\n";
        $url_info = parse_url($taxon_rec["USFWS SPECIES PROFILE URL"]);
        $taxon_id = str_ireplace("spcode=", "", $url_info["query"]);
        $taxon = $taxon_rec["NAME"];
        $source = $taxon_rec["USFWS SPECIES PROFILE URL"];
        $arr_objects = array();

        if(@$taxon_rec["USFWS SPECIES PROFILE URL"])
        {
            $description = "";
            $arr = self::get_population_detail($taxon_id);
            $population_detail = @$arr["population_detail"];
            $family = @$arr["family"];

            if($summary_listing_status = self::get_species_info_from_site($taxon_id))
            {
                $description .= $summary_listing_status;
            }

            if (in_array($taxon_rec["SOURCE LIST"], array("Threatened & Endangered Animals","Threatened & Endangered Plants")))
            {
                if($population_detail) $description .= "<b>Population detail:</b> <br><br>" . $population_detail;
            }            
            $description .= "For most current information and documents related to the conservation status and management of <i>$taxon</i>, see its ";
            $description .= "<a href='$source'>USFWS Species Profile</a>";
            $identifier = "$taxon_id" . "_ConservationStatus";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title = "";
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#ConservationStatus"; //debug orig ConservationStatus
            $agent = array();
            $mediaURL = ""; 
            $location = "";
            $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder = "";
            $refs = array();
            $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
        }

        if (in_array($taxon_rec["SOURCE LIST"], array("Threatened & Endangered Animals", "Threatened & Endangered Plants")))
        {
            if($description = self::get_historic_range($taxon_id))
            {
                $description = "<b>Historic Range:</b><br>" . $description . "<br>";
                $identifier = "$taxon_id" . "_Distribution";
                $mimeType   = "text/html";
                $dataType   = "http://purl.org/dc/dcmitype/Text";
                $title = "";
                $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
                $agent = array();
                $mediaURL = ""; 
                $location = "";
                $license = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
                $rightsHolder = "";
                $refs = array();
                $arr_objects[] = self::add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject);
            }
        }

        if(sizeof($arr_objects))
        {
            $taxa_data[] = array("identifier"   => $taxon_id,
                                 "source"       => $source,
                                 "kingdom"      => "",
                                 "phylum"       => "",
                                 "class"        => "",
                                 "order"        => "",
                                 "family"       => $family,
                                 "sciname"      => $taxon,
                                 "data_objects" => $arr_objects
                                );
        }
        return $taxa_data;
    }

    private function get_species_info_from_site($taxon_id)
    {
        /*
        <table id="pop">
        <caption>Current Listing Status Summary</caption>
        <thead><tr>
            <th>Status</th>
            <th>Date Listed</th>
            <th>Lead Region</th>
            <th>Where Listed</th>
        </tr></thead>
        
        <tbody>
        <tr class="displaytagOddRow">
            <td><script>displayListingStatus("Endangered")</script></td>
            <td>11/16/2005</td>
            <td><a href="http://www.nmfs.noaa.gov/pr/" target="regionWindow"/>National Marine Fisheries Service (Region 11)</td>
            <td>North America (West Coast from Point Conception, CA, U.S.A., to Punta Abreojos, Baja California, Mexico)</td>
        </tr></tbody>
        </table>
        */
        sleep(5);
        $html = Functions::get_remote_file(self::SPECIES_PROFILE_PAGE . $taxon_id);
        if(preg_match("/Current Listing Status Summary<\/caption>(.*?)<\/table>/ims", $html, $matches)) 
        {
            $html = trim($matches[1]);
            $html = str_ireplace("displaytagOddRow", "displaytagRow", $html);
            $html = str_ireplace("displaytagEvenRow", "displaytagRow", $html);
            if(preg_match_all("/<tr class\=\"displaytagRow\">(.*?)<\/tr>/ims", $html, $matches)) 
            {
                $rows = $matches[1];
                $desc = "";
                foreach($rows as $row)
                {
                    print "\n ============";
                    if(preg_match_all("/<td>(.*?)<\/td>/ims", $row, $matches)) 
                    {
                        $column = $matches[1];
                        $status = $column[0];
                        $date_listed = $column[1];
                        $lead_region = strip_tags($column[2]);
                        $where_listed = $column[3];
                        
                        if(preg_match("/displayListingStatus\(\"(.*?)\"/ims", $status, $matches)) $status = $matches[1];
                        
                        $desc .= "Status: " . $status . "<br>";
                        $desc .= "Date Listed: " . $date_listed . "<br>";
                        $desc .= "Lead Region: " . $lead_region . "<br>";
                        $desc .= "Where Listed: " . $where_listed . "<br><br>";                        
                    }
                }
                if($desc) return "<b>Current Listing Status Summary</b><br><br>" . $desc . "<br>";
            }
        }
        else print "\n No Listing Status Summary - $taxon_id \n";
    }
    
    private function get_population_detail($taxon_id)
    {
        $desc = "";
        $family = "";
        if(@$GLOBALS['animal_plant_list'][$taxon_id])
        {
            foreach($GLOBALS['animal_plant_list'][$taxon_id] as $rec) 
            {
                if($rec["where listed"]) $desc .= "Population location: " . $rec["where listed"] . "<br>";
                if($rec["listing status"]) $desc .= "Listing status: " . $rec["listing status"] . "<br>";
                $desc .= "<br>";
                if($rec["family"]) $family = $rec["family"];
                print "\n family: $family";
            }
        }
        return array("population_detail" => $desc, "family" => $family);
    }

    private function get_historic_range($taxon_id)
    {
        $ranges = array();
        foreach($GLOBALS['animal_plant_list'][$taxon_id] as $rec) $ranges[] = $rec["historic range"];
        $ranges = array_filter($ranges);
        $ranges = array_unique($ranges);
        $historic_range = "";
        foreach($ranges as $range) $historic_range .= $range . "<br>";      
        return $historic_range;
    }
    
    function add_objects($identifier, $dataType, $mimeType, $title, $source, $description, $mediaURL, $agent, $license, $location, $rightsHolder, $refs, $subject)
    {
        return array( "identifier"  => $identifier,
                      "dataType"    => $dataType,
                      "mimeType"    => $mimeType,
                      "source"      => $source,
                      "description" => $description,
                      "license"     => $license,
                      "subject"     => $subject
                    );
    }
    
    function initialize_text_file($filename)
    {
        $OUT = fopen($filename, "a");
        fwrite($OUT, "===================" . "\n");
        fwrite($OUT, date("F j, Y, g:i:s a") . "\n");
        fclose($OUT);
    }

}
?>