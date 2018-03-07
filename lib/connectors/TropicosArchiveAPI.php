<?php
namespace php_active_record;
/* connector: [218] Missouri Botanical Garden:
Tropicos resource - this connector will generate the EOL archive with structured data (for Distribution text)
Partner provides a number of services to share their data to EOL. There is no scraping for this resource.
Partner provides a list of IDs: e.g. http://services.tropicos.org/Name/List?startid=0&PageSize=1000&apikey=2810ce68-f4cf-417c-b336-234bc8928390&format=json
The connector does some looping to get all the IDs.
And partner provides 7 different services for each type of information:
http://services.tropicos.org/Name/25510055?format=json&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/ChromosomeCounts?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/Images?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/Distributions?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/Synonyms?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/References?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390
http://services.tropicos.org/Name/25510055/HigherTaxa?format=xml&apikey=2810ce68-f4cf-417c-b336-234bc8928390

* Tropicos web service goes down daily between 7-8am Eastern. So the connector process sleeps for an hour during this downtime.
* Connector runs for a long time because the sheer number of server requests to get all data for all taxa.

1 - 49
*/

define("TROPICOS_DOMAIN", "http://www.tropicos.org");
define("TROPICOS_TAXON_DETAIL_PAGE", "http://www.tropicos.org/Name/");
define("TROPICOS_IMAGE_LOCATION_LOW_BANDWIDTH", "http://www.tropicos.org/ImageScaled.aspx?imageid=");
define("TROPICOS_API_KEY", "2810ce68-f4cf-417c-b336-234bc8928390");
define("TROPICOS_API_SERVICE", "http://services.tropicos.org/Name/");

class TropicosArchiveAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->occurrence_ids = array();
        $this->taxon_ids = array();
        $this->TEMP_DIR = create_temp_dir() . "/";
        $this->tropicos_ids_list_file = $this->TEMP_DIR . "tropicos_ids.txt";
        $this->download_options = array('resource_id' => 218, 'cache_path' => '/Volumes/AKiTiO4/eol_cache_tropicos/', 'expire_seconds' => false, 
        'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1); //timeout is 60 secs. * 3 = 3 mins.
                                                                                       //download_wait_time = 1000000 = 1 second; 300000 => .3 seconds
        //, 'delay_in_minutes' => 1
        $this->preview_mode = false; //false is orig value
    }
    function get_all_countries()
    {
        $this->uri_values = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($this->uri_values)."\n";
        self::add_additional_mappings(); //add more mappings specific only to this resource
        echo "\n".count($this->uri_values)."\n";

        $countries = array();
        $i = 0; $m = 129600; //$m = 259200 orig; //total seems 1,296,000
        foreach(new FileIterator(DOC_ROOT."/tmp/tropicos_ids/tropicos_ids.txt") as $line_number => $taxon_id) {
            if($taxon_id) {
                $i++;
                if(($i % 500) == 0) echo "\n" . number_format($i);
                /* breakdown when caching:
                $cont = false;
                // if($i >=  1    && $i < $m) $cont = true;
                // if($i >=  $m   && $i < $m*2) $cont = true;
                // if($i >=  $m*2 && $i < $m*3) $cont = true;
                // if($i >=  $m*3 && $i < $m*4) $cont = true;
                // if($i >=  $m*4 && $i < $m*5) $cont = true;
                if(!$cont) continue;
                */
                $xml = self::create_cache("distribution", $taxon_id);
                $xml = simplexml_load_string($xml);
                $lines = array();
                foreach($xml->Distribution as $rec) {
                    if(!isset($rec->Location->CountryName)) continue;
                    $ctry = trim((string) $rec->Location->CountryName);
                    if($val = @$this->uri_values[$ctry]) {} //$mappedOK[$ctry] = '';
                    else $countries[$ctry] = '';
                }
                // if($i > 10) break; //debug
            }
        }
        // print_r($countries);
        $OUT = Functions::file_open(DOC_ROOT."/tmp/tropicos_ids/countries.txt", "w");
        $countries = array_keys($countries);
        sort($countries);
        foreach($countries as $c) fwrite($OUT, $c."\n");
        fclose($OUT);
        // print_r($mappedOK);
    }
    function get_all_taxa($resource_id)
    {
        $this->uri_values = Functions::get_eol_defined_uris(false, true); //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        // echo "\n".count($this->uri_values)."\n";
        self::add_additional_mappings(); //add more mappings specific only to this resource
        // echo "\n".count($this->uri_values)."\n";
        // print_r($this->uri_values);

        /*
        $c = self::create_cache("taxon_name", "1800503");
        exit("\n[$c]\n");
        */
        
        /* 1800472  100001
        $id = "1800470";
        $arr = array("distribution", "chromosome", "taxon_name", "taxonomy", "synonyms", "taxon_ref", "images");
        $arr = array("taxon_name");
        
        foreach($arr as $type)
        {
            if($type == "taxon_name") $url = TROPICOS_API_SERVICE . $id . "?format=json&apikey=" . TROPICOS_API_KEY;
            elseif($type == "taxonomy") $url = TROPICOS_API_SERVICE . $id . "/HigherTaxa?format=xml&apikey=" . TROPICOS_API_KEY;
            elseif($type == "synonyms") $url = TROPICOS_API_SERVICE . $id . "/Synonyms?format=xml&apikey=" . TROPICOS_API_KEY;
            elseif($type == "taxon_ref") $url = TROPICOS_API_SERVICE . $id . "/References?format=xml&apikey=" . TROPICOS_API_KEY;
            elseif($type == "distribution") $url = TROPICOS_API_SERVICE . $id . "/Distributions?format=xml&apikey=" . TROPICOS_API_KEY;
            elseif($type == "images") $url = TROPICOS_API_SERVICE . $id . "/Images?format=xml&apikey=" . TROPICOS_API_KEY;
            elseif($type == "chromosome") $url = TROPICOS_API_SERVICE . $id . "/ChromosomeCounts?format=xml&apikey=" . TROPICOS_API_KEY;
            echo "\n $type: [$url]";
            if($type == 'taxon_name')
            {
                // $this->download_options['expire_seconds'] = 0;
                if($contents = Functions::lookup_with_cache($url, $this->download_options)) {
                    echo "\n $type OK \n";
                    echo "\n [$contents] \n";
                }
                else echo "\n $type failed \n"; //{"Error":"Exception occurred"}
            }
        }
        exit("\nexit muna\n");
        return;
        */

        if(!$this->preview_mode) {
            self::assemble_id_list();
            $batches = self::process_taxa($resource_id);
            // exit;
            self::combine_all_temp_archives($batches, $resource_id);
        }
        else {
            self::process_taxa($resource_id);
        }
        
        if(isset($this->debug)) print_r($this->debug);
        
        // remove temp dir
        recursive_rmdir($this->TEMP_DIR);
        echo ("\n temporary directory removed: " . $this->TEMP_DIR);
    }

    private function process_taxa($resource_id)
    {
        if(!$this->preview_mode) {
            // /* normal operation - orig block
            $temp_archive_batch_count = 20000; //debug orig is 10k //when testing use 200
            $k = 0;
            $i = 0;
            foreach(new FileIterator($this->tropicos_ids_list_file) as $line_number => $taxon_id)
            {
                // self::check_server_downtime();
                if($taxon_id) {
                    $i++;
                    if($i == 1) {
                        $this->taxon_ids = array();
                        $this->object_ids = array();
                        $k++;
                        echo "\n batch:[$k]\n";
                        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . "_$k" . '_working/';
                        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
                    }

                    if(($i % 500) == 0) echo "\n" . number_format($i) . " - [batch $k] ";
                    self::process_taxon($taxon_id); //orig - uncomment in real operation... when not caching...

                    // if($k >=  26   && $k < 28) self::process_taxon($taxon_id);
                    // if($k >=  28   && $k < 29) self::process_taxon($taxon_id);
                    // if($k >=  29   && $k < 30) self::process_taxon($taxon_id);

                    if(($i % $temp_archive_batch_count) == 0) {
                        $old_k = $k;
                        echo "\nfinalizing batch [$k]\n";
                        $this->archive_builder->finalize(TRUE);
                        $i = 0; //reset to 0
                    }

                }
            }
            if($old_k != $k) {
                echo "\nfinalizing batch [$k]\n";
                $this->archive_builder->finalize(TRUE);
            }
            return $k;
            // */
        }
        
        if($this->preview_mode) {
            // /* during preview mode -- debug
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
            $ids = array("100184", "100423", "21300106", "21300477", "21300646", "21300647", "21301287", "21301354", "21301544");
            foreach($ids as $taxon_id) self::process_taxon($taxon_id);
            $this->archive_builder->finalize(TRUE);
            // */
        }

    }

    function process_taxon($taxon_id)
    {
        self::get_images($taxon_id);
        $name = self::create_cache("taxon_name", $taxon_id);
        $name = json_decode($name, true);
        $sciname = @$name['ScientificName'];
        
        // "NomenclatureStatusName":"No opinion",
        // "Citation":"Tropicos.org. Missouri Botanical Garden. 11 Dec 2013 &lt;http:\/\/www.tropicos.org\/Name\/1&gt;",
        // "Copyright":"© 2013 Missouri Botanical Garden - 4344 Shaw Boulevard - Saint Louis, Missouri 63110",
        
        /* working but temporarily commented by Chris Freeland
        self::get_chromosome_count($taxon_id);
        */

        $taxonomy = self::get_taxonomy($taxon_id);
        $reference_ids = array();
        $reference_ids = self::get_taxon_ref($taxon_id);
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $taxon_id;
        $taxon->scientificName              = $sciname;
        $taxon->scientificNameAuthorship    = @$name["Author"];
        $taxon->taxonRank                   = @$name["Rank"];
        $taxon->furtherInformationURL       = str_replace('\\', "", @$name["Source"]);
        $taxon->namePublishedIn             = @$name["NamePublishedCitation"];
        $taxon->kingdom         = @$taxonomy['kingdom'];
        $taxon->phylum          = @$taxonomy['phylum'];
        $taxon->class           = @$taxonomy['class'];
        $taxon->order           = @$taxonomy['order'];
        $taxon->family          = @$taxonomy['family'];
        $taxon->genus           = @$taxonomy['genus'];
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        self::get_distributions($taxon_id, $sciname); //un-comment first Oct 22, 2017 - Traitbank data
        self::get_synonyms($taxon_id); //debug - uncomment in real operation
    }

    private function get_images($taxon_id)
    {
        $xml = self::create_cache("images", $taxon_id);
        $xml = simplexml_load_string($xml);
        $with_image = 0;
        foreach(@$xml->Image as $rec) {
            if($rec->Error) continue; // echo "\n no images - " . $rec->DetailUrl;
            $with_image++;
            if($with_image > 15) break; // max no. of images per taxon //debug orig 15
            $description = $rec->NameText . ". " . $rec->LongDescription;
            if($rec->SpecimenId)    $description .= "<br>" . "SpecimenId: " . $rec->SpecimenId;
            if($rec->SpecimenText)  $description .= "<br>" . "SpecimenText: " . $rec->SpecimenText;
            if($rec->Caption)       $description .= "<br>" . "Caption: " . $rec->Caption;
            if($rec->PhotoLocation) $description .= "<br>" . "Location: " . $rec->PhotoLocation;
            if($rec->PhotoDate)     $description .= "<br>" . "Photo taken: " . $rec->PhotoDate;
            if($rec->ImageKindText) $description .= "<br>" . "Image kind: " . $rec->ImageKindText;
            $valid_licenses = array("http://creativecommons.org/licenses/by/3.0/", "http://creativecommons.org/licenses/by-sa/3.0/", "http://creativecommons.org/licenses/by-nc/3.0/", "http://creativecommons.org/licenses/by-nc-sa/3.0/", "http://creativecommons.org/licenses/publicdomain/");
            if(!in_array(trim($rec->LicenseUrl), $valid_licenses)) continue; // echo "\n invalid image license - " . $rec->DetailUrl . "\n";
            $license = $rec->LicenseUrl;
            $agent_ids = array();
            if(trim($rec->Photographer) != "") {
                $agents = array();
                $agents[] = array("role" => "photographer", "homepage" => "", "fullName" => $rec->Photographer);
                $agent_ids = self::create_agents($agents);
            }
            $rightsHolder = $rec->Copyright;
            $identifier = $rec->ImageId;
            $dataType   = "http://purl.org/dc/dcmitype/StillImage";
            $mimeType   = "image/jpeg";
            $title      = "";
            $subject    = "";
            $source = $rec->DetailUrl;
            if($rec->DetailJpgUrl == 'http://www.tropicos.org/images/imageprotected.jpg') {
                $mediaURL = $rec->ThumbnailUrl;
                $rating = 1;
            }
            else {
                $mediaURL = $rec->DetailJpgUrl;
                $rating = 2;
            }
            $description .= "<br>Full sized images can be obtained by going to the <a href='$source'>original source page</a>.";

            // start create media
            $mr = new \eol_schema\MediaResource();
            if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
            $mr->taxonID        = $taxon_id;
            $mr->identifier     = (string) $identifier;
            $mr->type           = $dataType;
            $mr->language       = 'en';
            $mr->format         = $mimeType;
            $mr->furtherInformationURL = $source;
            $mr->Owner          = $rightsHolder;
            $mr->rights         = "";
            $mr->title          = $title;
            $mr->UsageTerms     = $license;
            $mr->description    = $description;
            $mr->accessURI      = $mediaURL;
            $mr->Rating         = $rating;
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }

    private function get_distributions($taxon_id, $sciname)
    {
        $this->occurrence_ids = array();
        $xml = self::create_cache("distribution", $taxon_id);
        $xml = simplexml_load_string($xml);
        $lines = array();
        foreach($xml->Distribution as $rec) {
            if(!isset($rec->Location->CountryName)) continue;

            $line = trim($rec->Location->CountryName) . trim($rec->Location->RegionName) . trim($rec->Location->UpperName);
            if(in_array($line, $lines)) continue;
            $lines[] = $line;

            $citation = trim($rec->Reference->FullCitation);
            $ref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->Reference->ReferenceId);
            
            // $agents = array();
            // $agents[] = array("role" => "source", "homepage" => "http://www.tropicos.org", "fullName" => "Tropicos");
            // $agent_ids = self::create_agents($agents);

            $text_id = (string) $rec->Location->LocationID;
            // if(isset($location_ids[$text_id])) continue;
            // $location_ids[$text_id] = '';
            
            $region = "";
            if($RegionName = trim($rec->Location->RegionName)) $region .= $RegionName;
            if($CountryName = trim($rec->Location->CountryName)) $region .= " - " . $CountryName;
            if($UpperName = trim($rec->Location->UpperName)) $region .= " - " . $UpperName;
            
            
            /* orig Eli's version
            self::add_string_types($taxon_id, $text_id, "Distribution", $region, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution", true);
            self::add_string_types($taxon_id, $text_id, "Taxon", $sciname, "http://rs.tdwg.org/dwc/terms/scientificName");
            self::add_string_types($taxon_id, $text_id, "Country", trim($rec->Location->CountryName), "http://rs.tdwg.org/dwc/terms/country");
            self::add_string_types($taxon_id, $text_id, "Continent", trim($rec->Location->RegionName), "http://rs.tdwg.org/dwc/terms/continent");
            if($upper_name = @$rec->Location->UpperName) {
                self::add_string_types($taxon_id, $text_id, "Upper name", $upper_name, "http://tropicos.org/". SparqlClient::to_underscore("Upper name"));
            }
            */
            //based from: https://eol-jira.bibalex.org/browse/DATA-1722?focusedCommentId=61829&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61829
            if($country_uri = self::get_country_uri($CountryName)) {
                /* strategy changed. better to just put original country string to remarks
                if(in_array($CountryName, $this->ctrys_with_diff_name)) $mremarks = $CountryName;
                else $mremarks = "";
                */
                $mremarks = $CountryName;
                
                self::add_string_types($taxon_id, $text_id, "Country", $country_uri, "http://eol.org/schema/terms/Present", true, $mremarks);
                // self::add_string_types($taxon_id, $text_id, "Taxon"  , $sciname    , "http://rs.tdwg.org/dwc/terms/scientificName", false); --not needed since redundant, sciname is same as in taxon extension
            }
            else $this->debug[$CountryName] = '';
            
        }

        //     $title      = "Localities documented in Tropicos sources";
        //     $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution";
        //     $license    = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
    }

    private function get_country_uri($country)
    {
        if($val = @$this->uri_values[$country]) return $val;
        else {
            /* working OK but too hard-coded, better to read the mapping from external file
            switch ($country) {
                case "United States of America":return "http://www.wikidata.org/entity/Q30";
                case "Myanmar":                 return "http://www.wikidata.org/entity/Q836";
            }
            */
        }
    }

    private function add_string_types($taxon_id, $catnum, $label, $value, $mtype, $mtaxon = false, $mremarks = '')
    {
        if(!trim($value)) return;
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence_id;
        if($mtaxon) {
            $m->measurementOfTaxon = 'true';
            $m->source = TROPICOS_DOMAIN . "/Name/" . $taxon_id . "?tab=distribution";
            /* temporarily excluded to reduce size of measurement tab
            $m->measurementRemarks = "Note: This information is based on publications available through <a href='http://tropicos.org/'>Tropicos</a> and may not represent the entire distribution. Tropicos does not categorize distributions as native or non-native.";
            */
            $m->measurementRemarks = $mremarks;
        }
        $m->measurementType = $mtype;
        $m->measurementValue = (string) $value;
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');

        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);

        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;

        /* old ways
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
        */
    }

    private function create_agents($agents)
    {
        $agent_ids = array();
        foreach($agents as $rec) {
            $agent = (string) trim($rec["fullName"]);
            if(!$agent) continue;
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|" . $rec["role"]);
            $r->agentRole = $rec["role"];
            $r->term_homepage = $rec["homepage"];
            $agent_ids[] = $r->identifier;
            if(!isset($this->resource_agent_ids[$r->identifier])) {
               $this->resource_agent_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }

    private function get_taxon_ref($taxon_id)
    {
        $xml = self::create_cache("taxon_ref", $taxon_id);
        $xml = simplexml_load_string($xml);
        $reference_ids = array();
        foreach(@$xml->NameReference as $rec) {
            if($ref_id = trim($rec->Reference->ReferenceId)) {
                $reference_ids[] = $ref_id;
                $ref_url = TROPICOS_DOMAIN . "/Reference/" . $ref_id;
                $citation = trim($rec->Reference->FullCitation);
                self::add_reference($citation, $ref_id, $ref_url);
            }
        }
        return $reference_ids;
    }

    private function get_synonyms($taxon_id)
    {
        $records = array();
        $xml = self::create_cache("synonyms", $taxon_id);
        $xml = simplexml_load_string($xml);
        foreach(@$xml->Synonym as $syn) {
            $synonym = trim($syn->SynonymName->ScientificNameWithAuthors);
            $NameId = trim($syn->SynonymName->NameId);
            $Family = trim($syn->SynonymName->Family);
            $reference_ids = array();
            if($ref_id = (string) $syn->Reference->ReferenceId) {
                $citation = $syn->Reference->AuthorString . ". " . $syn->Reference->ArticleTitle . ". " . $syn->Reference->AbbreviatedTitle . ". " . $syn->Reference->Collation . ".";
                $citation = str_replace("..", ".", $citation);
                $reference_ids[] = $ref_id;
                self::add_reference($citation, $ref_id);
            }
            if($NameId) $records[] = array("id" => $NameId, "synonym" => $synonym, "family" => $Family, "ref_ids" => $reference_ids);
        }
        if($records) self::add_synonyms($records, $taxon_id);
    }

    private function add_reference($citation, $ref_id, $ref_url = false)
    {
        if($citation) {
            $r = new \eol_schema\Reference();
            $r->full_reference = (string) $citation;
            $r->identifier = $ref_id;
            if($ref_url) $r->uri = $ref_url;
            if(!isset($this->resource_reference_ids[$r->identifier])) {
               $this->resource_reference_ids[$r->identifier] = '';
               $this->archive_builder->write_object_to_file($r);
            }
        }
    }

    private function add_synonyms($records, $acceptedNameUsageID)
    {
        foreach($records as $rec) {
            $synonym = new \eol_schema\Taxon();
            $synonym->taxonID               = $rec["id"];
            $synonym->scientificName        = $rec["synonym"];
            $synonym->acceptedNameUsageID   = $acceptedNameUsageID;
            $synonym->taxonomicStatus       = "synonym";
            $synonym->family                = @$rec["family"];
            $synonym->furtherInformationURL = TROPICOS_TAXON_DETAIL_PAGE . $rec["id"];
            if($rec["ref_ids"]) $synonym->referenceID = implode("; ", $rec["ref_ids"]);
            if(!isset($this->taxon_ids[$synonym->taxonID]))
            {
                $this->taxon_ids[$synonym->taxonID] = '';
                $this->archive_builder->write_object_to_file($synonym);
            }
            // else
            // {
            //     echo "\n investigate: synonym already entered \n";
            //     print_r($rec);
            // }
        }
    }
    
    function get_taxonomy($taxon_id)
    {
        $taxonomy = array();
        $xml = self::create_cache("taxonomy", $taxon_id);
        $xml = simplexml_load_string($xml);
        foreach(@$xml->Name as $rec) {
            if($rec->Rank == "kingdom") $taxonomy['kingdom'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "phylum")  $taxonomy['phylum'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "class")   $taxonomy['class'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "order")   $taxonomy['order'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "family")  $taxonomy['family'] = $rec->ScientificNameWithAuthors;
            if($rec->Rank == "genus")   $taxonomy['genus'] = $rec->ScientificNameWithAuthors;
        }
        return $taxonomy;
    }

    function get_chromosome_count($taxon_id)
    {
        $xml = self::create_cache("chromosome", $taxon_id);
        $xml = simplexml_load_string($xml);

        $refs = array();
        $temp_reference = array();
        $with_content = false;
        $GametophyticCount = array();
        $SporophyticCount = array();
        $IPCNReferenceID = array();
        foreach($xml->ChromosomeCount as $rec) {
            if(!isset($rec->GametophyticCount) && !isset($rec->SporophyticCount)) continue;
            $with_content = true;
            $citation = trim($rec->Reference->FullCitation);
            $ref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->Reference->ReferenceId);
            if($rec->GametophyticCount) $GametophyticCount["$rec->GametophyticCount"] = 1;
            if($rec->SporophyticCount) $SporophyticCount["$rec->SporophyticCount"] = 1;
            if(trim($rec->IPCNReferenceID)) {
                $IPCNref_url = TROPICOS_DOMAIN . "/Reference/" . trim($rec->IPCNReferenceID);
                $index = "<a target='tropicos' href='" . $IPCNref_url . "'>" . $rec->IPCNAbbreviation . "</a>";
                $IPCNReferenceID[$index] = 1;
            }
            //this is to prevent getting duplicate references
            if(!in_array($citation, $temp_reference)) $refs[] = array("url" => $ref_url, "fullReference" => $citation);
            $temp_reference[] = $citation;
        }
        $description = "";
        $GametophyticCount = array_keys($GametophyticCount);
        $SporophyticCount = array_keys($SporophyticCount);
        $IPCNReferenceID = array_keys($IPCNReferenceID);
        if($GametophyticCount) $description .= "Gametophyte chromosome count = " . implode("; ", $GametophyticCount) . "<br><br>";
        if($SporophyticCount) $description .= "Sporophyte chromosome count = " . implode("; ", $SporophyticCount) . "<br><br>";
        if($IPCNReferenceID) $description .= "IPCN Ref. = " . implode("; ", $IPCNReferenceID) . "<br><br>";
        if($with_content) {
            $source = TROPICOS_DOMAIN . "/Name/" . $taxon_id . "?tab=chromosomecounts";
            $identifier = $taxon_id . "_chromosome";
            $mimeType   = "text/html";
            $dataType   = "http://purl.org/dc/dcmitype/Text";
            $title      = "Chromosome Counts";
            $subject    = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Genetics";
            $agent      = array();
            $agent[]    = array("role" => "source", "homepage" => "http://www.tropicos.org", "fullName" => "Tropicos");
            $mediaURL   = "";
            $location   = "";
            $license    = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
            $rightsHolder   = "";
        }
    }

    private function assemble_id_list()
    {
        if(!($OUT = Functions::file_open($this->tropicos_ids_list_file, "w"))) return;
        $startid = 0; // debug orig value 0; 1600267 with mediaURL and <location>; 1201245 with thumbnail size images; 100391155 near the end
        $count = 0;
        while(true) {
            $count++;
            $contents = self::create_cache("id_list", $startid);
            if($contents) {
                $ids = json_decode($contents, true);
                if(($count % 100) == 0) echo "\n count:[$count] " . count($ids);
                $str = "";
                foreach($ids as $id) {
                    if(isset($id["Error"])) return; // no more ids --- [{"Error":"No names were found"}]
                    if($id["NameId"]) {
                        $str .= $id["NameId"] . "\n";
                        $startid = $id["NameId"];
                    }
                    else echo "\n nameid undefined";
                }
                $startid++; // to avoid duplicate ids, set next id to get
                if($str != "") fwrite($OUT, $str);
            }
            else {
                echo "\n --server not accessible-- \n";
                break;
            }
            if($count == 1300) break; // normal operation
            // break; //debug - when developing
        }
        fclose($OUT);
    }

    private function create_cache($type, $id)
    {
        if($type == "id_list") // $id here is the startid
        {
            $pagesize = 1000; // debug orig value max size is 1000; pagesize is the no. of records returned from Tropicos master list service
            // $pagesize = 100; //debug only
            $url = TROPICOS_API_SERVICE . "List?startid=$id&PageSize=$pagesize&apikey=" . TROPICOS_API_KEY . "&format=json";
        }
        // $id here is the taxon_id
        elseif($type == "taxon_name")   $url = TROPICOS_API_SERVICE . $id . "?format=json&apikey=" . TROPICOS_API_KEY;
        elseif($type == "taxonomy")     $url = TROPICOS_API_SERVICE . $id . "/HigherTaxa?format=xml&apikey=" . TROPICOS_API_KEY;
        elseif($type == "synonyms")     $url = TROPICOS_API_SERVICE . $id . "/Synonyms?format=xml&apikey=" . TROPICOS_API_KEY;
        elseif($type == "taxon_ref")    $url = TROPICOS_API_SERVICE . $id . "/References?format=xml&apikey=" . TROPICOS_API_KEY;
        elseif($type == "distribution") $url = TROPICOS_API_SERVICE . $id . "/Distributions?format=xml&apikey=" . TROPICOS_API_KEY;
        elseif($type == "images")       $url = TROPICOS_API_SERVICE . $id . "/Images?format=xml&apikey=" . TROPICOS_API_KEY;
        elseif($type == "chromosome")   $url = TROPICOS_API_SERVICE . $id . "/ChromosomeCounts?format=xml&apikey=" . TROPICOS_API_KEY;
        if($contents = Functions::lookup_with_cache($url, $this->download_options))
        {
            // sample error return -> {"Error":"Exception occurred"}
            if(stripos($contents, "Exception occurred") !== false) //string is found
            {   //let us try a fresh lookup
                $options = $this->download_options;
                $options['expire_seconds'] = 0;
                if($contents = Functions::lookup_with_cache($url, $options)) return $contents;
                else return false;
            }
            else return $contents;
        }
        else return false;
    }

    /*
    private function get_texts($description, $taxon_id, $title, $subject, $code, $reference_ids = null, $agent_ids = null)
    {
            $description = str_ireplace("&", "", $description);
            $mr = new \eol_schema\MediaResource();
            if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
            if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
            $mr->taxonID = $taxon_id;
            $mr->identifier = $mr->taxonID . "_" . $code;
            $mr->type = 'http://purl.org/dc/dcmitype/Text';
            $mr->language = 'en';
            $mr->format = 'text/html';
            $mr->furtherInformationURL = '';
            $mr->description = utf8_encode($description);
            $mr->CVterm = $this->SPM . $subject;
            $mr->title = $title;
            $mr->creator = '';
            $mr->CreateDate = '';
            $mr->modified = '';
            $mr->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
            $mr->Owner = '';
            $mr->publisher = '';
            $mr->audience = 'Everyone';
            $mr->bibliographicCitation = '';
            $this->archive_builder->write_object_to_file($mr);
    }
    */
    
    private function check_server_downtime()
    {
        $time = date('H:i:s', time());
        if($time >= "06:40:00" && $time <= "07:00:00") {
            echo "\n\n Process stopped at [$time], will resume in 1.5 hours...";
            sleep((60*60)+(60*30)); //sleep 1.5 hours
        }
    }
    
    private function combine_all_temp_archives($batches, $resource_id)
    {
        $this->unique_ids = array();
        
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $resource_id . '_working/';
        $this->archive_builder_final = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        for($i=1; $i <= $batches; $i++) {
            $dir = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_" . $i . "_working/";
            $harvester = new ContentArchiveReader(NULL, $dir);
            $tables = $harvester->tables;
            if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) return false; // take note the index key is all lower case
            foreach(array_keys($tables) as $table) self::process_fields($harvester->process_row_type($table), pathinfo($table, PATHINFO_BASENAME));
            print_r(array_keys($tables));

            // /* debug - uncomment in normal operation
            //delete temp dir and file.tar.gz
            if(is_dir($dir)) recursive_rmdir($dir);
            $file = CONTENT_RESOURCE_LOCAL_PATH . $resource_id . "_" . $i . "_working.tar.gz";
            if(is_file($file)) unlink($file);
            // */
        }
        $this->archive_builder_final->finalize(TRUE);
    }
    
    private function process_fields($records, $class)
    {
        /* you can add here a way to append only unique records */
        $unique_id_for['taxon']          = "taxonID";
        $unique_id_for['occurrence']     = "occurrenceID";
        $unique_id_for['reference']      = "identifier";
        $unique_id_for['document']       = "identifier";
        $unique_id_for['agent']          = "identifier";
        $unique_id_for['vernacularname'] = "vernacularName";
        
        $field_name_of_unique_id = @$unique_id_for[$class];
        $i = 0;
        foreach($records as $rec) {
            $i++;
            if    ($class == "vernacular")          $c = new \eol_schema\VernacularName();
            elseif($class == "agent")               $c = new \eol_schema\Agent();
            elseif($class == "reference")           $c = new \eol_schema\Reference();
            elseif($class == "taxon")               $c = new \eol_schema\Taxon();
            elseif($class == "document")            $c = new \eol_schema\MediaResource();
            elseif($class == "occurrence")          $c = new \eol_schema\Occurrence();
            elseif($class == "measurementorfact")   $c = new \eol_schema\MeasurementOrFact();
            else {
                echo "\nundefined class [$class]\n";
                return;
            }
            if(($i % 1000) == 0) debug("\nclass [$class] [$field_name_of_unique_id]\n");
            $keys = array_keys($rec);
            foreach($keys as $key) {
                $temp = pathinfo($key);
                $field = $temp["basename"];

                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                $c->$field = $rec[$key];
            }
            
            //this will allow only unique records to be appended to the final tab file
            if($field_name_of_unique_id) //for the rest of the tab files except measurementorfact
            {
                $var = (string) $c->{$field_name_of_unique_id};
                if(!in_array($var, $this->unique_ids[$class])) {
                    $this->unique_ids[$class][] = $var;
                    $this->archive_builder_final->write_object_to_file($c);
                    // debug("\n" . count($this->unique_ids[$class]) . " -> " . "[$var]"); //should remain commented in real operation, just debug.
                }
                // else debug("\nwill not add, will coz duplicates [$class]-[$field_name_of_unique_id]-[$var]\n"); //working
            }
            else $this->archive_builder_final->write_object_to_file($c); //for measurementorfact
        }
    }
    
    private function add_additional_mappings()
    {
        $url = "https://raw.githubusercontent.com/eliagbayani/EOL-connector-data-files/master/Tropicos/countries with added URIs.txt";
        $options = $this->download_options;
        $options['cache'] = 1;
        $options['expire_seconds'] = 60*60*24;
        $local = Functions::save_remote_file_to_local($url, $options);
        $handle = fopen($local, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $line = str_replace("\n", "", $line);
                $a = explode("\t", $line); $a = array_map('trim', $a);
                $this->uri_values[$a[0]] = $a[1];
                $this->ctrys_with_diff_name[] = $a[0]; //what goes here is e.g. 'Burma Rep.', if orig ctry name is 'Burma' and Tropicos calls it differently e.g. 'Burma Rep.'.
            }
            fclose($handle);
        } 
        else echo "\nCannot read!\n";
        unlink($local);
    }
}
?>