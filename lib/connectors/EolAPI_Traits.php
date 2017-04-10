<?php
namespace php_active_record;
/* connector: [eol_api_traits.php]
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
        $this->headers = "EOL page ID,Scientific Name,Common Name,Measurement,Value,Measurement URI,Value URI,Units (normalized),Units URI (normalized),Raw Value (direct from source),Raw Units (direct from source),Raw Units URI (normalized),Supplier,Content Partner Resource URL,source,citation,measurement method,statistical method,individual count,locality,event date,sampling protocol,size class,diameter,counting unit,cells per counting unit,scientific name,measurement remarks,height,Reference,measurement determined by,occurrence remarks,length,diameter 2,width,life stage,length 2,measurement determined date,sampling effort,standard deviation,number of available reports from the literature";
        /*
        %3A is :
        %2F is /
        */
    }
    
    function start()
    {
        $datasets = array();
        // /*
        // DATA-1648 derivative files: Cichlidae 
        // $datasets[] = array("name" => "Cichlidae - body mass", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=5344");
        // $datasets[] = array("name" => "Cichlidae - life span", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000050&q=&sort=desc&taxon_concept_id=5344");

        // DATA-1649 - derivative file: body mass, various groups
        // $datasets[] = array("name" => "Chondrichthyes - body mass", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=38541712");
        // $datasets[] = array("name" => "Amphibia - body mass", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1552");
        // $datasets[] = array("name" => "Reptilia - body mass", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1703");
        // $datasets[] = array("name" => "Mammalia - body mass", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=1642");
        // $datasets[] = array("name" => "Aves - body mass", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc&taxon_concept_id=695");

        // DATA-1650 lifespan of mammalia
        // $datasets[] = array("name" => "Mammalia - life span", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000050&q=&sort=desc&taxon_concept_id=1642");

        // DATA-1651 plant propagation method
        // $datasets[] = array("name" => "plant propagation method", "attribute" => "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPropagationMethod&q=&sort=desc");

        // DATA-1652 carbon per cell
        // $datasets[] = array("name" => "carbon per cell", "attribute" => "http%3A%2F%2Feol.org%2Fschema%2Fterms%2Fcarbon_per_cell&q=&sort=desc");

        // DATA-1653 life cycle habit
        // $datasets[] = array("name" => "life cycle habit", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0002725&q=&sort=desc");

        // DATA-1654 growth habit // 1091 pages!
        // $datasets[] = array("name" => "growth habit", "attribute" => "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FPlantHabit&q=&sort=desc");

        // DATA-1657 derivative file: All Body Mass
        // $datasets[] = array("name" => "body mass", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&q=&sort=desc");
        // */
        
        // DATA-1664: body length (CMO) + body length (VT)
        // $datasets[] = array("name" => "body length CMO VT", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FCMO_0000013&required_equivalent_attributes%5B%5D=2247&commit=Search&taxon_name=&q=");
        
        // DATA-1669: weight within Eutheria
        $datasets[] = array("name" => "weight within Eutheria", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FPATO_0000128&q=&sort=desc&taxon_concept_id=2844801");
        // $datasets[] = array("name" => "body mass within Eutheria", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FVT_0001259&commit=Search&taxon_name=Eutheria&q=&taxon_concept_id=2844801");

        //for archiving...
        // $datasets[] = array("name" => "active growth period", "attribute" => "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FActiveGrowthPeriod&commit=Search&taxon_name=&q="); DONE
        // $datasets[] = array("name" => "flower color", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FTO_0000537&commit=Search&taxon_name=&q=");                DONE
        // $datasets[] = array("name" => "abdomen length", "attribute" => "http%3A%2F%2Feol.org%2Fschema%2Fterms%2FAbdomenLength&commit=Search&taxon_name=&q=");    DONE
        // $datasets[] = array("name" => "egg diameter", "attribute" => "http%3A%2F%2Fpolytraits.lifewatchgreece.eu%2Fterms%2FEGG&commit=Search&taxon_name=&q=");   DONE
        // $datasets[] = array("name" => "body length (CMO)", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FCMO_0000013&commit=Search&taxon_name=&q=");  DONE

        //currently archiving...
        // $datasets[] = array("name" => "latitude", "attribute" => "http%3A%2F%2Frs.tdwg.org%2Fdwc%2Fterms%2FdecimalLatitude&commit=Search&taxon_name=&q=");

        // tests
        // $datasets[] = array("name" => "cell mass from Jen", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FOBA_1000036&commit=Search&taxon_name=Halosphaera&q=&taxon_concept_id=90645");
        // $datasets[] = array("name" => "cell mass from Jen2", "attribute" => "http%3A%2F%2Fpurl.obolibrary.org%2Fobo%2FOBA_1000036&commit=Search&q=");

        foreach($datasets as $dataset)
        {
            $filename = CONTENT_RESOURCE_LOCAL_PATH . "/" . str_replace(" ", "-", $dataset['name']) . ".txt";
            self::initialize_tsv($filename);
            self::get_data_search_results($dataset, $filename);
            
            if(filesize($filename) > 1)
            {
                $command_line = "gzip -c " . $filename . " > " . $filename . ".gz";
                $output = shell_exec($command_line);
                echo "\n$output\n";
            }
        }
        print_r($this->unique_index);
    }

    private function get_data_search_results($dataset, $filename)
    {
        $WRITE = fopen($filename, "a");
        $attrib = $dataset['attribute'];
        $result = self::get_html_info($attrib);
        $total = $result['total_records'];
        echo "\nTotal: $total";
        $pages = ceil($total/100);
        // $pages = 20; //debug - force limit 20 pages only
        echo "\nPages: $pages";
        for($page = 1; $page <= $pages; $page++)    //orig
        // for($page = 1; $page <= 196; $page++)
        // for($page = 196; $page <= 392; $page++)
        // for($page = 392; $page <= $pages; $page++)
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
                        echo "\n[[row: #$i $page of $pages]]";
                        // print($row); exit;
                        
                        $meta = self::get_additional_metadata($row);
                        
                        $rec = array();
                        $rec['predicate'] = $result['predicate'];
                        if(preg_match("/\/pages\/(.*?)\/data/ims", $row, $arr2)) $rec['taxon_id'] = trim($arr2[1]);
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
                            // print_r($arr2[1]); exit;
                            if($val = $arr2[1][0]) $rec['predicate2'] = self::parse_span_info($val);
                            if($val = $arr2[1][1]) $rec['term']       = self::parse_span_info($val);
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
                            
                            // print_r($rec);
                            // print_r($meta); //exit;
                            
                            // /*
                            //for stats only - works OK
                            $keys = array_keys($meta);
                            $this->unique_index = array_merge($this->unique_index, $keys);
                            $this->unique_index = array_unique($this->unique_index);
                            // */
                            
                            $save = array();
                            $save['EOL page ID']        = $rec['taxon_id'];
                            $save['Scientific Name']    = ($val = @$meta['scientific name']['value']) ? $val : $rec['sciname'];
                            $save['Common Name']        = @$rec['vernacular'];
                            
                            /* not perfect sol'n infact erroneous for like e.g. "13,846.17 mm adult"
                            //remove unit
                            if($unit = @$meta['measurement unit']['value']) $save['Value'] = str_replace(" ".$unit, "", $rec['term']['value']);
                            else                                            $save['Value'] = $rec['term']['value'];
                            //remove comma if numeric
                            $temp = str_replace(',', '', $save['Value']);
                            if(is_numeric($temp)) $save['Value'] = $temp;
                            */
                            $save['Value'] = self::get_float_from_string($rec['term']['value']); //better sol'n vs above
                            
                            $api_rec = self::get_actual_api_rec($rec, $save['Value']);
                            // print_r($api_rec);
                            if(!$api_rec) echo "\n-NO API RECORD-\n";

                            $save['Measurement']            = ($val = $rec['predicate2']['value'])          ? $val : $api_rec['predicate'];
                            $save['Measurement URI']        = ($val = $rec['predicate2']['uri'])            ? $val : $api_rec['dwc:measurementType'];
                            
                            $save['Value URI']              = ($val = @$rec['term']['uri'])                 ? $val : $api_rec['dwc:measurementValue'];
                            $save['Value URI'] = self::blank_if_not_uri($save['Value URI']);
                            
                            $save['Units (normalized)']     = ($val = @$meta['measurement unit']['value'])  ? $val : @$api_rec['units'];
                            $save['Units URI (normalized)'] = ($val = @$meta['measurement unit']['uri'])    ? $val : @$api_rec['dwc:measurementUnit'];
                            $save['Units URI (normalized)'] = self::blank_if_not_uri($save['Units URI (normalized)']);
                            
                            $save['Raw Value (direct from source)'] = $save['Value'];
                            $save['Raw Units (direct from source)'] = @$meta['measurement unit']['value'];
                            $save['Raw Units URI (normalized)']     = ($val = @$meta['measurement unit']['uri']) ? $val : @$api_rec['dwc:measurementUnit'];
                            $save['Raw Units URI (normalized)'] = self::blank_if_not_uri($save['Raw Units URI (normalized)']);
                            
                            $save['Supplier']                       = strip_tags($rec['source']);

                            $resource_url = self::get_resource_url_from_traitUri($api_rec['eol:traitUri']);
                            $save['Content Partner Resource URL'] = ($val = $resource_url) ? $val : ($val = @$api_rec['eolterms:resource']) ? $val : @$api_rec['source'];
                            
                            $save['source']                       = ($val = @$meta['source']['value'])      ? $val : @$api_rec['dc:source'];
                            $save['citation']               = ($val = @$meta['citation']['value'])               ? $val : @$api_rec['dc:bibliographicCitation'];
                            $save['measurement method']     = ($val = @$meta['measurement method']['value'])     ? $val : @$api_rec['dwc:measurementMethod'];
                            $save['statistical method']     = ($val = @$meta['statistical method']['value'])     ? $val : @$api_rec['eolterms:statisticalMethod']; //(get value for api_rec)
                            $save['individual count']       = ($val = @$meta['individual count']['value'])       ? $val : @$api_rec['dwc:individualCount'];
                            $save['locality']               = ($val = @$meta['locality']['value'])               ? $val : @$api_rec['dwc:locality'];
                            $save['event date']             = ($val = @$meta['event date']['value'])             ? $val : @$api_rec['dwc:eventDate'];
                            $save['sampling protocol']      = ($val = @$meta['sampling protocol']['value'])      ? $val : @$api_rec['dwc:samplingProtocol'];
                            $save['size class']             = ($val = @$meta['size class']['value'])             ? $val : @$api_rec['eolterms:SizeClass'];
                            $save['diameter']               = ($val = @$meta['diameter']['value'])               ? $val : "";
                            $save['counting unit']          = ($val = @$meta['counting unit']['value'])          ? $val : @$api_rec['eolterms:CountingUnit'];
                            $save['cells per counting unit'] = ($val = @$meta['cells per counting unit']['value']) ? $val : @$api_rec['eolterms:CellsPerCountingUnit'];
                            $save['scientific name']        = ($val = @$meta['scientific name']['value'])       ? $val : ($val = @$api_rec['dwc:scientificName']) ? $val : $rec['sciname'];
                            $save['measurement remarks'] = ($val = @$meta['measurement remarks']['value']) ? $val : @$api_rec['dwc:measurementRemarks'];
                            $save['height']              = ($val = @$meta['height']['value'])              ? $val : @$api_rec['http://semanticscience.org/resource/SIO_000040'];
                            $save['Reference']                  = ($val = @$meta['References']['value'])                 ? $val : @$api_rec['eol:reference/full_reference'];
                            $save['measurement determined by']  = ($val = @$meta['measurement determined by']['value'])  ? $val : @$api_rec['dwc:measurementDeterminedBy'];
                            $save['occurrence remarks']         = ($val = @$meta['occurrence remarks']['value'])         ? $val : @$api_rec['dwc:occurrenceRemarks'];
                            $save['length']                     = ($val = @$meta['length']['value'])                     ? $val : @$api_rec['http://semanticscience.org/resource/SIO_000041'];

                            //guessed meta field 1x
                            $save['diameter 2']                 = ($val = @$meta['diameter 2']['value'])                 ? $val : "";

                            $save['width']                      = ($val = @$meta['width']['value'])                      ? $val : @$api_rec['http://semanticscience.org/resource/SIO_000042'];
                            $save['life stage']                 = ($val = @$meta['life stage']['value'])                 ? $val : @$api_rec['dwc:lifeStage'];

                            //guessed meta field 1x
                            $save['length 2']                   = ($val = @$meta['length 2']['value'])                   ? $val : "";

                            $save['measurement determined date'] = ($val = @$meta['measurement determined date']['value'])   ? $val : @$api_rec['dwc:measurementDeterminedDate'];
                            $save['sampling effort']            = ($val = @$meta['sampling effort']['value'])                ? $val : @$api_rec['dwc:samplingEffort'];
                            $save['standard deviation']         = ($val = @$meta['standard deviation']['value'])             ? $val : @$api_rec['http://semanticscience.org/resource/SIO_000770'];
                            $save['number of available reports from the literature'] = ($val = @$meta['number of available reports from the literature']['value'])  ? $val : @$api_rec['eolterms:NLiteratureValues'];
                            print_r($save);
                            
                            //start saving
                            if($save['EOL page ID'])
                            {
                                $fields = explode(",", $this->headers);
                                $fields_total = count($fields);
                                $line = ""; $j = 0;
                                foreach($fields as $field)
                                {
                                    $j++;
                                    $line .= $save[$field];
                                    if($j != $fields_total) $line .= "\t";
                                }
                                fwrite($WRITE, $line . "\n");
                                // echo "\n[$line]";
                            }
                            // exit;
                            
                            /*
                            Array
                            (
                                [0] => source
                                [1] => statistical method
                                [2] => measurement unit
                                [3] => scientific name
                                [4] => contributor
                                [5] => citation
                                [6] => References
                                [7] => measurement method
                                [8] => sex
                                [9] => locality
                                [10] => life stage
                                [11] => measurement remarks
                                [12] => occurrence remarks
                                [13] => catalog number
                                [14] => individual count
                                [15] => institution code
                                [16] => body mass
                                [17] => longitude
                                [18] => sample size
                                [19] => latitude
                                [20] => event date
                                [21] => measurement determined date
                                [22] => water temperature
                                [23] => irradiance
                                [24] => field notes
                                [25] => collection code
                                [26] => 
                                [27] => diameter
                                [28] => size class
                                [29] => cells per counting unit
                                [30] => counting unit
                                [31] => sampling protocol
                                [32] => height
                                [33] => measurement determined by
                                [34] => length
                                [35] => width
                                [36] => sampling effort
                                [37] => standard deviation
                                [38] => number of available reports from the literature
                            )
                            */
                        }
                    }
                }
            }
        }
        fclose($WRITE);
    }
    
    private function get_float_from_string($string)
    {
        $float = filter_var($string, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $float = str_replace(array("-","+"), "", $float); //needed for like e.g. "Paratype- 1.5 mm"
        return $float;
    }
    
    private function blank_if_not_uri($val)
    {
        if($val)
        {
            if(strtolower(substr($val,0,4)) != "http") return "";
        }
        return $val;
    }
    private function get_resource_url_from_traitUri($traitUri)
    {
        //e.g. [eol:traitUri] => http://eol.org/resources/692/measurements/ab4a32a35c2d266976ba4f10879be6c4
        if(preg_match("/elix173(.*?)\/measurements\//ims", "elix173".$traitUri, $arr)) return $arr[1];
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
            if(preg_match("/<dt>(.*?)<\/dt>/ims", $temp, $arr))             $return['value'] = self::clean_value(trim($arr[1]));
            if(preg_match("/<dd class='uri'>(.*?)<\/dd>/ims", $temp, $arr)) $return['uri']   = trim($arr[1]);
        }
        else $return['value'] = self::clean_value(trim(strip_tags($temp)));
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
    
    private function clean_value($string)
    {
        return str_replace("\n", " ", $string);
    }
    
    private function get_actual_api_rec($rek, $valuex)
    {
        if($json = Functions::lookup_with_cache($this->trait_api.$rek['taxon_id'], $this->download_options))
        {
            $recs = json_decode($json, true);
            // print_r($recs);
            echo "\n"        .$recs['item']['scientificName']."\n";
            $traits         = $recs['item']['traits'];
            foreach($traits as $trait)
            {
                if($rek['predicate'] == $trait['predicate'])
                {
                    // print_r($trait); exit;
                    if($valuex == $trait['value'] &&
                       $rek['predicate2']['value'] == $trait['predicate']
                    ) return $trait;
                }
            }
        }
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

                /*
                //for stats only - works OK
                $keys = array_keys($trait);
                $this->unique_index = array_merge($this->unique_index, $keys);
                $this->unique_index = array_unique($this->unique_index);
                */
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

    private function initialize_tsv($filename)
    {
        $WRITE = fopen($filename, "w");
        $fields = explode(",", $this->headers);
        fwrite($WRITE, implode("\t", $fields) . "\n");
        fclose($WRITE);
    }

}
/*
Array
(
    [0] => @id
    [1] => eol:traitUri
    [2] => @type
    [3] => predicate
    [4] => dwc:measurementType
    [5] => value
    [6] => eol:dataPointId
    [7] => dc:source
    [8] => dwc:measurementValue
    [9] => dwc:scientificName
    [10] => eolterms:resource
    [11] => units
    [12] => dwc:individualCount
    [13] => eolterms:statisticalMethod
    [14] => dwc:measurementUnit
    [15] => dwc:eventDate
    [16] => eolterms:Reviewer
    [17] => eolterms:Assessor
    [18] => eolterms:Version
    [19] => dwc:measurementRemarks
    [20] => dwc:measurementDeterminedDate
    [21] => dwc:establishmentMeans
    [22] => http://purl.bioontology.org/ontology/CSP/5004-0024
    [23] => eolterms:SampleSize
    [24] => dwc:sex
    [25] => dwc:lifeStage
    [26] => dwc:measurementMethod
    [27] => dc:contributor
    [28] => dc:bibliographicCitation
    [29] => source
    [30] => eol:reference/full_reference
    [31] => eol:associationType
    [32] => eol:inverseAssociationType
    [33] => eol:subjectPage
    [34] => eol:objectPage
    [35] => eol:targetTaxonID
    [36] => dwc:measurementAccuracy
    [37] => eolterms:RedListCriteria
    [38] => dwc:verbatimEventDate
    [39] => dwc:year
    [40] => dwc:continent
    [41] => dwc:catalogNumber
    [42] => dwc:collectionCode
    [43] => dwc:institutionCode
    [44] => dwc:collectionID
    [45] => dwc:typeStatus
    [46] => dwc:county
    [47] => dwc:stateProvince
    [48] => dwc:country
    [49] => dwc:locality
    [50] => dwc:occurrenceRemarks
    [51] => dwc:recordedBy
    [52] => dwc:higherGeography
    [53] => dwc:waterBody
    [54] => dwc:associatedMedia
    [55] => dwc:island
    [56] => dwc:preparations
    [57] => dwc:fieldNumber
    [58] => dwc:startDayOfYear
    [59] => dwc:day
    [60] => dwc:month
    [61] => dwc:endDayOfYear
    [62] => dwc:islandGroup
    [63] => dwc:verbatimCoordinateSystem
    [64] => dwc:decimalLongitude
    [65] => dwc:decimalLatitude
    [66] => dwc:verbatimLongitude
    [67] => dwc:verbatimLatitude
    [68] => dwc:maximumDepthInMeters
    [69] => dwc:minimumDepthInMeters
    [70] => eolterms:Propagule
    [71] => eolterms:bodyPart
    [72] => http://purl.obolibrary.org/obo/VT_0001259
    [73] => http://semanticscience.org/resource/SIO_000770
    [74] => dwc:georeferenceProtocol
    [75] => dwc:geodeticDatum
    [76] => dwc:verbatimElevation
    [77] => dwc:minimumElevationInMeters
    [78] => dwc:maximumElevationInMeters
    [79] => http://edamontology.org/data_2140
    [80] => http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#Sample_Size
    [81] => http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#Standard_Deviation
    [82] => http://purl.obolibrary.org/obo/OBI_0000235
    [83] => dwc:georeferenceRemarks
    [84] => dwc:coordinateUncertaintyInMeters
    [85] => dwc:identifiedBy
    [86] => dwc:reproductiveCondition
    [87] => dwc:fieldNotes
    [88] => eolterms:TimeOfExtinction
    [89] => dwc:samplingProtocol
    [90] => http://purl.obolibrary.org/obo/VT_0001256
    [91] => eolterms:OriginOfToxin
    [92] => dwc:identificationQualifier
    [93] => dwc:recordNumber
    [94] => dwc:associatedSequences
    [95] => eolterms:WetlandIndicatorRegion
    [96] => http://tropicos.org/upper_name
    [97] => eolterms:ToxicEffect
    [98] => eolterms:HasToxin
    [99] => eolterms:SeawaterTemperature
    [100] => http://ecoinformatics.org/oboe/oboe.1.0/oboe-characteristics.owl#Irradiance
    [101] => eolterms:SizeClass
    [102] => http://ncicb.nci.nih.gov/xml/owl/EVS/Thesaurus.owl#C25285
    [103] => http://semanticscience.org/resource/SIO_000041
    [104] => eolterms:CellsPerCountingUnit
    [105] => eolterms:VolumeFormula
    [106] => eolterms:CountingUnit
    [107] => dwc:samplingEffort
    [108] => http://semanticscience.org/resource/SIO_000042         width
    [109] => http://semanticscience.org/resource/SIO_000040         height
    [110] => dwc:measurementDeterminedBy
    [111] => eolterms:LatentPeriod
    [112] => eolterms:Uses
    [113] => eolterms:ModeOfAction
    [114] => eolterms:NLiteratureValues
    [115] => eolterms:Salinity
    [116] => dwc:municipality
    [117] => dwc:verbatimDepth
    [118] => dwc:countryCode
    [119] => eolterms:GenbankAccessionNumber
    [120] => dwc:behavior
)
*/
?>
