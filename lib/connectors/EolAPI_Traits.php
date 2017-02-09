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
        
        // others
        $this->unique_index = array();
        // %3A :
        // %2F /
        
        
        $this->headers = "EOL page ID,Scientific Name,Common Name,Measurement,Value,Measurement URI,Value URI,Units (normalized),Units URI (normalized),Raw Value (direct from source),Raw Units (direct from source),Raw Units URI (normalized),Supplier,Content Partner Resource URL,source,citation,measurement method,statistical method,individual count,locality,event date,sampling protocol,size class,diameter,counting unit,cells per counting unit,scientific name,measurement remarks,height,Reference,measurement determined by,occurrence remarks,length,diameter 2,width,life stage,length 2,measurement determined date,sampling effort,standard deviation,number of available reports from the literature";
        // print_r($this->headers); exit;
    }
    
    function start()
    {
        /*
        // DATA-1648 derivative files: Cichlidae 
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=5344";
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000050&q=&sort=desc&taxon_concept_id=5344";
        
        // DATA-1649 - derivative file: body mass, various groups
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=38541712";   //done
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1552";       //done
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1703";
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1642";       //done
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=695";        //done

        // DATA-1650 lifespan of mammalia
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000050&q=&sort=desc&taxon_concept_id=1642";     //done
        
        // DATA-1651 plant propagation method
        $attribute = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPropagationMethod&q=&sort=desc";                       //done
        
        // DATA-1652 carbon per cell
        $attribute = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2Fcarbon_per_cell&q=&sort=desc";                         //done

        // DATA-1653 life cycle habit
        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0002725&q=&sort=desc";

        // DATA-1654 growth habit
        $attribute = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPlantHabit&q=&sort=desc";                              //done
        */

        $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FOBA_1000036&commit=Search&taxon_name=Halosphaera&q=&taxon_concept_id=90645"; //cell mass; csv sample from Jen
        // $attribute = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FOBA_1000036&commit=Search&q="; //cell mass; csv sample from Jen

        echo "\n" . urldecode($attribute) . "\n"; //exit;
        
        
        /* just for stats
        $attributes = array();
        $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=38541712";   //done
        $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1552";       //done
        $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1703";
        $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1642";       //done
        $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=695";        //done
        $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000050&q=&sort=desc&taxon_concept_id=1642";     //done
        $attributes[] = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPropagationMethod&q=&sort=desc";                       //done
        $attributes[] = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2Fcarbon_per_cell&q=&sort=desc";                         //done
        $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0002725&q=&sort=desc";
        $attributes[] = "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPlantHabit&q=&sort=desc";                              //done
        $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FOBA_1000036&commit=Search&q="; //cell mass; csv sample from Jen
        */

        $datasets = array();
        // $attributes[] = "";
        // $attributes[] = "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000050&q=&sort=desc&taxon_concept_id=5344";

        $datasets[] = array("name" => "cell mass from Jen", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FOBA_1000036&commit=Search&taxon_name=Halosphaera&q=&taxon_concept_id=90645");

        // $datasets[] = array("name" => "fishbase", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=5344");

        // $datasets[] = array("name" => "life span", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000050&q=&sort=desc&taxon_concept_id=1642");
        
        
        // $datasets[] = array("name" => "plant propagation method", "attribute" => "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPropagationMethod&q=&sort=desc");
        
        

        foreach($datasets as $dataset)
        {
            self::initialize_tsv($dataset['name']);
            self::get_data_search_results($dataset);
        }

        print_r($this->unique_index);
        exit("\n-eli stops-\n");
    }

    private function get_data_search_results($dataset)
    {
        $attrib = $dataset['attribute'];
        $result = self::get_html_info($attrib);
        $total = $result['total_records'];
        echo "\nTotal: $total";
        $pages = ceil($total/100);
        // $pages = 20;
        echo "\nPages: $pages";
        for($page = 1; $page <= $pages; $page++)
        {
            if($html = Functions::lookup_with_cache($this->data_search_url.$attrib."&page=$page", $this->download_options))
            {
                // if(preg_match_all("/<tr class='data' data-loaded (.*?)<\/tr>/ims", $html, $arr))
                if(preg_match_all("/<tr class='data' data-loaded (.*?)Link to this record/ims", $html, $arr))
                {
                    $i = 0;
                    foreach($arr[1] as $row)
                    {
                        $i++;
                        echo "\n[[row: $i]]";
                        // print($row); exit;
                        
                        $metadata = self::get_additional_metadata($row);
                        
                        $rec = array();
                        $rec['predicate'] = $result['predicate'];
                        if(preg_match("/\/pages\/(.*?)\/data/ims", $row, $arr2))                    $rec['taxon_id'] = trim($arr2[1]);

                        if(preg_match("/<h4>(.*?)<\/h4>/ims", $row, $arr2))
                        {
                            if(preg_match("/\/data\">(.*?)<\/a>/ims", $arr2[1], $arr3)) $rec['sciname'] = trim($arr3[1]);
                        }
                        // <h4>
                        // <a href="/pages/222402/data">Tylochromis mylodon</a>
                        // </h4>

                        if(preg_match("/<\/h4>(.*?)<\/div>/ims", $row, $arr2))
                        {
                            if(preg_match("/\/data\">(.*?)<\/a>/ims", $arr2[1], $arr3)) $rec['vernacular'] = trim($arr3[1]);
                        }
                        // </h4>
                        // <a href="/pages/222402/data">Mweru Hump-backed Bream</a>
                        // </div>
                        
                        
                        if(preg_match_all("/<div class='term'>(.*?)<\/div>/ims", $row, $arr2))
                        {
                            if($val = $arr2[1][1])
                            {
                                // $rec['term'] = trim($val);
                                $rec['term'] = self::parse_span_info($val);
                            }
                        }
                        if(preg_match("/<span class='stat'>(.*?)<\/span>/ims", $row, $arr2))        $rec['stat']     = trim($arr2[1]);
                        if(preg_match("/<span class='source'>(.*?)<\/span>/ims", $row, $arr2))      $rec['source']   = trim($arr2[1]);
                        if(preg_match("/<span class='comments'>(.*?)<\/span>/ims", $row, $arr2))    $rec['comments'] = trim($arr2[1]);
                        
                        $api_recs = array();
                        if($rec['taxon_id']) 
                        {
                            /* don't use JSON-LD
                            $api_recs = self::get_api_recs($rec, "#$i $page of $pages");
                            print_r($api_recs);
                            */
                            // $foo = $bar ? $a : $b;
                            
                            print_r($rec);
                            print_r($metadata); exit;
                            
                            $save['EOL page ID']        = $rec['taxon_id'];
                            $save['Scientific Name']    = ($val=$meta['scientific name']['value']) ? $val : $rec['sciname'];
                            $save['Common Name']        = @$rec['vernacular'];
                            
                            /*
                            	
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
                            height	                        http://semanticscience.org/resource/SIO_000040
                            Reference	                    eol:reference/full_reference
                            measurement determined by	    dwc:measurementDeterminedBy
                            occurrence remarks	            dwc:occurrenceRemarks
                            length	                        http://semanticscience.org/resource/SIO_000041
                            diameter 2	
                            width	                        http://semanticscience.org/resource/SIO_000042
                            life stage	                    dwc:lifeStage
                            length 2	
                            measurement determined date	    dwc:measurementDeterminedDate
                            sampling effort	                dwc:samplingEffort
                            standard deviation	            http://semanticscience.org/resource/SIO_000770
                            number of available reports from the literature     eolterms:NLiteratureValues
                            
                            */
                            
                            
                        }
                        // exit("\nxxx\n");
                    }
                }
            }
        }
    }
    
    private function get_additional_metadata($row)
    {
        $additional = array();
        if(preg_match("/<caption class='title'>Data about this record<\/caption>(.*?)elix173/ims", $row."elix173", $arr))
        {
            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1]."elix173", $arr2))
            {
                // print_r($arr2[1]);
                foreach($arr2[1] as $t)
                {
                    $field = ""; $details = array();
                    //gets the field e.g. 'statistical method'
                    if(preg_match("/<dt>(.*?)<\/dt>/ims", $t, $arr3))
                    {
                        $field = trim($arr3[1]);
                        // echo "\n"."[$field]";
                    }
                    //gets the value e.g.
                    // Array
                    // (
                    //     [value] => mean
                    //     [uri] => http://semanticscience.org/resource/SIO_001109
                    // )
                    if(preg_match("/<td id=(.*?)<\/td>/ims", $t, $arr4))
                    {
                        $temp = "<td id=".$arr4[1];
                        // echo "\n".$temp;
                        $details = self::parse_span_info($temp);
                        $additional[$field] = $details;
                    }
                    // echo "\n-------------------------";
                }
            }
        }
        return $additional;
    }
    
    private function parse_span_info($temp)
    {
        $return = array();
        if(stripos($temp, "<span class='info'>") !== false) //string is found
        {
            if(preg_match("/<dt>(.*?)<\/dt>/ims", $temp, $arr))             $return['value'] = trim($arr[1]);
            if(preg_match("/<dd class='uri'>(.*?)<\/dd>/ims", $temp, $arr)) $return['uri']   = trim($arr[1]);
        }
        else $return['value'] = trim(strip_tags($temp));
        /*
        <span class='info'>
            <ul class='glossary'>
                <li data-toc-id='' id='http___semanticscience_org_resource_SIO_001114'>
                <dt>
                    max
                </dt>
                <dd>
                    a maximal value is largest value of an attribute for the entities in the defined set.
                </dd>
                <dd class='uri'>http://semanticscience.org/resource/SIO_001114</dd>
                <ul class='helpers'>
                    <li><a href="http://eol.org/data_glossary#http___semanticscience_org_resource_SIO_001114" class="glossary" data-anchor="http___semanticscience_org_resource_SIO_001114" data-tab-link-message="see in glossary tab">explore full data glossary</a></li>
                </ul>
                </li>
            </ul>
        </span>
        */
        return $return;
    }
    
    private function get_api_recs($rek, $msg)
    {
        $records = array();
        echo "\n[$msg]";
        if($json = Functions::lookup_with_cache($this->trait_api.$rek['taxon_id'], $this->download_options))
        {
            $recs = json_decode($json, true);
            // print_r($recs);
            echo "\n"        .$recs['item']['scientificName']."\n";
            $common_names   = $recs['item']['vernacularNames'];
            $traits         = $recs['item']['traits'];
            foreach($traits as $trait)
            {
                if($rek['predicate'] == $trait['predicate'])
                {
                    // print_r($trait);
                    $records[] = $trait;
                }
                
                // exit;
                
                //for stats only
                $keys = array_keys($trait);
                $this->unique_index = array_merge($this->unique_index, $keys);
                $this->unique_index = array_unique($this->unique_index);
            }

            // exit;
            
            
            /*
            EOL page ID	                        $recs['item']['@id']
            Scientific Name	                    dwc:scientificName
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
            height	                        http://semanticscience.org/resource/SIO_000040
            Reference	                    eol:reference/full_reference
            measurement determined by	    dwc:measurementDeterminedBy
            occurrence remarks	            dwc:occurrenceRemarks
            length	                        http://semanticscience.org/resource/SIO_000041
            diameter 2	
            width	                        http://semanticscience.org/resource/SIO_000042
            life stage	                    dwc:lifeStage
            length 2	
            measurement determined date	    dwc:measurementDeterminedDate
            sampling effort	                dwc:samplingEffort
            standard deviation	            http://semanticscience.org/resource/SIO_000770
            number of available reports from the literature     eolterms:NLiteratureValues
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
            
            /*
            Array
            (
                [0] => @id
                [1] => eol:traitUri
                [2] => @type
                [3] => predicate
                [4] => dwc:measurementType
                [5] => value
                [6] => units
                [7] => eol:dataPointId
                [8] => eolterms:SizeClass
                [9] => http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#C25285
                [10] => eolterms:CellsPerCountingUnit
                [11] => dc:source
                [12] => dwc:individualCount
                [13] => dc:bibliographicCitation
                [14] => dwc:measurementMethod
                [15] => eolterms:statisticalMethod
                [16] => dwc:measurementValue
                [17] => dwc:measurementUnit
                [18] => dwc:scientificName
                [19] => eolterms:CountingUnit
                [20] => dwc:locality
                [21] => dwc:eventDate
                [22] => dwc:samplingProtocol
                [23] => eolterms:resource
                [24] => eolterms:VolumeFormula
                [25] => dwc:measurementRemarks
                [26] => http://semanticscience.org/resource/SIO_000770
                [27] => dc:contributor
                [28] => dwc:measurementDeterminedDate
                [29] => eolterms:SampleSize
                [30] => dwc:measurementDeterminedBy
                [31] => dwc:occurrenceRemarks
                [32] => source
                [33] => http://semanticscience.org/resource/SIO_000040
                [34] => eol:reference/full_reference
                [35] => eolterms:NLiteratureValues
                [36] => dwc:samplingEffort
                [37] => http://semanticscience.org/resource/SIO_000041
                [38] => eolterms:Salinity
                [39] => dwc:municipality
                [40] => dwc:month
                [41] => dwc:year
                [42] => dwc:island
                [43] => dwc:country
                [44] => eol:associationType
                [45] => eol:inverseAssociationType
                [46] => eol:subjectPage
                [47] => eol:objectPage
                [48] => eol:targetTaxonID
                [49] => dwc:lifeStage
                [50] => http://semanticscience.org/resource/SIO_000042
                [51] => eolterms:SeawaterTemperature
                [52] => dwc:verbatimEventDate
                [53] => dwc:day
                [54] => dwc:continent
                [55] => dwc:waterBody
                [56] => dwc:associatedMedia
                [57] => dwc:recordNumber
                [58] => dwc:startDayOfYear
                [59] => dwc:endDayOfYear
                [60] => dwc:catalogNumber
                [61] => dwc:collectionCode
                [62] => dwc:institutionCode
                [63] => dwc:collectionID
                [64] => dwc:typeStatus
                [65] => dwc:stateProvince
                [66] => dwc:recordedBy
                [67] => dwc:higherGeography
                [68] => eolterms:HasToxin
                [69] => eolterms:ToxicEffect
                [70] => dwc:verbatimDepth
                [71] => dwc:decimalLongitude
                [72] => dwc:countryCode
                [73] => eolterms:GenbankAccessionNumber
                [74] => dwc:identifiedBy
                [75] => dwc:preparations
                [76] => dwc:decimalLatitude
                [77] => dwc:islandGroup
                [78] => http://ecoinformatics.org/oboe/oboe.1.0/oboe-characteristics.owl#Irradiance
                [79] => dwc:fieldNotes
                [80] => dwc:measurementAccuracy
                [81] => dwc:county
                [82] => eolterms:Reviewer
                [83] => eolterms:Assessor
                [84] => eolterms:Version
                [85] => eolterms:RedListCriteria
                [86] => dwc:behavior
            )
            */
        }
        return $records;
    }
    
    private function get_html_info($attrib)
    {
        $result = array();
        if($html = Functions::lookup_with_cache($this->data_search_url.$attrib."&page=1", $this->download_options))
        {
            if(preg_match("/<h2>(.*?) results/ims", $html, $arr)) $result['total_records'] = $arr[1];
            // get predicate e.g. 'boday mass'
            // selected="selected" data-known_uri_id="1722">body mass</option>
            if(preg_match("/selected=\"selected\" data-known_uri_id=(.*?)<\/option>/ims", $html, $arr))
            {
                if(preg_match("/>(.*?)xxx/ims", $arr[1]."xxx", $arr2)) $result['predicate'] = $arr2[1];
            }
        }
        return $result;
    }

    private function initialize_tsv($name)
    {
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "/" . str_replace(" ", "_", $name) . ".txt";
        $WRITE = fopen($filename, "w");
        $fields = $this->headers = explode(",", $this->headers);
        fwrite($WRITE, implode("\t", $fields) . "\n");
        fclose($WRITE);
    }

}
?>
