<?php
namespace php_active_record;
/* connector: [eol_api.php]
This script uses the different means to access the EOL API.
Can be used for OpenData's customized subsets.
*/

class EolAPI_Traits
{
    function __construct($folder = null, $query = null)
    {
        /* add: 'resource_id' => "gbif" ;if you want to add cache inside a folder [gbif] inside [eol_cache_gbif] */
        $this->download_options = array(
            'cache_path'         => '/Volumes/Thunderbolt4/eol_cache/',     //used in Functions.php for all general cache
            'resource_id'        => 'eol_api_traits',                       //resource_id here is just a folder name in cache
            'expire_seconds'     => false,                                  //another option is 1 year to expire
            'download_wait_time' => 3000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0);

        $this->download_options2 = array(
            'cache_path'         => '/Volumes/Thunderbolt4/eol_cache/',     //used in Functions.php for all general cache
            'resource_id'        => 'eol_api',                              //resource_id here is just a folder name in cache
            'expire_seconds'     => false,                                  //another option is 1 year to expire
            'download_wait_time' => 3000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 1);

        $this->trait_api = "http://eol.org/api/traits/";
        $this->data_search_url = "http://eol.org/data_search?attribute=";

        // for creating archives
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->do = array();
        $this->agent_ids = array();
    }
    
    function start()
    {
        /*
        %3A :
        %2F /
        */


        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1703";
        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1703";

        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1642";
        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1642";

        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=695";

        
        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0002725&q=&sort=desc";                             //last 7th problematic
        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0002725&q=&sort=desc";                             //last 7th problematic
        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0002725&q=&sort=desc";                             //last 7th problematic
        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0002725&q=&sort=desc";                             //last 7th problematic

        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FOBA_1000036&commit=Search&taxon_name=Halosphaera&q=&taxon_concept_id=90645"; //cell mass; csv sample from Jen
        
        
        
        /*
        https://eol-jira.bibalex.org/browse/DATA-1649
        derivative file: body mass, various groups
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=38541712";   //done
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1552";       //done
        http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1703
        http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1642
        http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=695
        
        DATA-1650 lifespan of mammalia
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000050&q=&sort=desc&taxon_concept_id=1642";     //done
        
        DATA-1651 plant propagation method
        $attribute = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPropagationMethod&q=&sort=desc";                       //done
        
        DATA-1652 carbon per cell
        $attribute = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2Fcarbon_per_cell&q=&sort=desc";                         //done

        DATA-1653 life cycle habit
        http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0002725&q=&sort=desc

        DATA-1654 growth habit
        $attribute = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPlantHabit&q=&sort=desc";                              //done
        */
        
        echo "\n" . urldecode($attribute) . "\n"; //exit;
        
        self::get_data_search_results($attribute);
        exit("\n-eli stops-\n");
    }
    
    private function get_data_search_results($attrib)
    {
        $total = self::get_total_records($attrib);
        echo "\nTotal: $total";
        $pages = ceil($total/100);
        echo "\nPages: $pages";
        for($page = 1; $page <= $pages; $page++)
        {
            if($html = Functions::lookup_with_cache($this->data_search_url.$attrib."&page=$page", $this->download_options))
            {
                if(preg_match_all("/<tr class='data' data-loaded (.*?)<\/tr>/ims", $html, $arr))
                {
                    $i = 0;
                    foreach($arr[1] as $row)
                    {
                        $i++;
                        print($row); //exit;
                        $rec = array();
                        if(preg_match("/\/pages\/(.*?)\/data/ims", $row, $arr2))                    $rec['taxon_id'] = trim($arr2[1]);
                        if(preg_match_all("/\/data\">(.*?)<\/a>/ims", $row, $arr2))
                        {
                            if($val = $arr2[1][2])  $rec['sciname'] = trim($val);
                            if($val = @$arr2[1][3]) $rec['vernacular'] = trim($val);
                        }
                        if(preg_match_all("/<div class='term'>(.*?)<\/div>/ims", $row, $arr2))
                        {
                            if($val = $arr2[1][1]) $rec['term'] = trim($val);
                        }
                        if(preg_match("/<span class='stat'>(.*?)<\/span>/ims", $row, $arr2))        $rec['stat']     = trim($arr2[1]);
                        if(preg_match("/<span class='source'>(.*?)<\/span>/ims", $row, $arr2))      $rec['source']   = trim($arr2[1]);
                        if(preg_match("/<span class='comments'>(.*?)<\/span>/ims", $row, $arr2))    $rec['comments'] = trim($arr2[1]);
                        // print_r($rec); //exit;
                        
                        $api_recs = array();
                        if($rec['taxon_id']) $api_recs = self::get_api_recs($rec, "#$i $page of $pages");
                        
                        // exit;
                    }
                }
            }
        }
    }
    
    private function get_api_recs($rec, $msg)
    {
        echo "\n[$msg]";
        if($json = Functions::lookup_with_cache($this->trait_api.$rec['taxon_id'], $this->download_options))
        {
            $arr = json_decode($json, true);
            print_r($arr); //exit;
            /*
            EOL page ID	
            Scientific Name	        dwc:scientificName
            Common Name	
            Measurement	                        predicate
            Value	                            value
            Measurement URI	                    dwc:measurementType
            Value URI	                        dwc:measurementValue
            Units (normalized)	                units
            Units URI (normalized)	            dwc:measurementUnit
            Raw Value (direct from source)	    
            Raw Units (direct from source)	    
            Raw Units URI (normalized)	        dwc:measurementUnit
            Supplier	
            Content Partner Resource URL	    eolterms:resource
            source	                        dc:source
            citation	                    dc:bibliographicCitation
            measurement method	            dwc:measurementMethod
            statistical method	            eolterms:statisticalMethod (get value)
            individual count	            dwc:individualCount
            locality	                    dwc:locality
            event date	                    dwc:eventDate
            sampling protocol	            dwc:samplingProtocol
            size class	                    eolterms:SizeClass
            diameter	
            counting unit	                eolterms:CountingUnit
            cells per counting unit	        eolterms:CellsPerCountingUnit
            scientific name	                dwc:scientificName
            measurement remarks	            dwc:measurementRemarks
            height	
            Reference	                    eol:reference/full_reference
            measurement determined by	    dwc:measurementDeterminedBy
            occurrence remarks	
            length	
            diameter 2	
            width	
            life stage	
            length 2	
            measurement determined date	    dwc:measurementDeterminedDate
            sampling effort	                dwc:samplingEffort
            standard deviation	
            number of available reports from the literature
            */
            
            /*
            dwc:fieldNotes
            eolterms:SeawaterTemperature
            
            "dwc:municipality": "9",
            "dwc:month": "1967",
            "dwc:year": "R090",
            "dwc:island": "Europe",
            "dwc:country": "Belgium",
            "dc:contributor": "Compiler: Anne E Thessen",
            */
            
        }
        
    }
    
    private function get_total_records($attrib)
    {
        if($html = Functions::lookup_with_cache($this->data_search_url.$attrib."&page=1", $this->download_options))
        {
            if(preg_match("/<h2>(.*?) results/ims", $html, $arr)) return $arr[1];
        }
        return 0;
    }

}
?>
