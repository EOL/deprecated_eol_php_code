<?php
namespace php_active_record;
/* connector: [usda_plant_images.php]

https://plants.usda.gov/assets/config.json
{
  "env": {
    "name": "prod" 
  },
  "serviceUrls": {
    "mapServerUrl": "https://nrcsgeoservices.sc.egov.usda.gov/arcgis/rest/services/land_use_land_cover/plants/MapServer",
    "plantsServicesUrl": "https://plantsservices.sc.egov.usda.gov/api/",        TO BE USED
    "plantsUrl": "https://plants.sc.egov.usda.gov",
    "imageLibraryUrl": "https://plants.sc.egov.usda.gov/ImageLibrary",          TO BE USED
    "plantGuideUrl": "https://plants.sc.egov.usda.gov/DocumentLibrary/plantguide/"
  }  
}

To get all plants: and get the plant symbol:
https://plants.usda.gov/assets/docs/CompletePLANTSList/plantlst.txt
A plant profile: to get the plantID
https://plantsservices.sc.egov.usda.gov/api/PlantProfile?symbol=ABBA
Images of a plant: using the plantID
https://plantsservices.sc.egov.usda.gov/api/PlantImages?plantId=15309

https://plants.sc.egov.usda.gov/ImageLibrary/thumbnail/abso_001_thp.jpg
https://plants.sc.egov.usda.gov/ImageLibrary/standard/abso_001_thp.jpg
https://plants.sc.egov.usda.gov/ImageLibrary/standard/abes_001_shp.jpg
*/
class USDAPlantNewAPI
{
    function __construct($folder = false)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->max_images_per_taxon = 10;
        $this->service['URLs'] = "https://plants.usda.gov/assets/config.json";
        $this->service['plant_list'] = "https://plants.usda.gov/assets/docs/CompletePLANTSList/plantlst.txt";

        $this->download_options = array('cache' => 1, 'resource_id' => 'usda_plants', 'expire_seconds' => 60*60*24*30*6, 
        'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); //6 months to expire
        // $this->download_options['expire_seconds'] = false;
        $this->debug = array();
        $this->github['US State list'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/USDA_Plants/US_State_list.tsv';
        /*
        GrowthHabits: http://purl.obolibrary.org/obo/FLOPO_0900032
            e.g.    http://purl.obolibrary.org/obo/FLOPO_0900034        Shrub
                    http://eol.org/schema/terms/subshrub                Subshrub
        

        NativeStatuses: 
        http://eol.org/schema/terms/NativeRange
            e.g.    http://www.geonames.org/5855797						Hawaii, USA
                    Pacific Basin excluding Hawaii						Pacific Basin excluding Hawaii
                    http://www.geonames.org/4566966						Puerto Rico

        http://eol.org/schema/terms/IntroducedRange
            e.g.    http://www.wikidata.org/entity/Q578170              Lower 48 United States of America
                    http://www.geonames.org/4566966						Puerto Rico

        http://eol.org/schema/terms/Present
                    http://www.geonames.org/4829764							Alabama
                    http://www.geonames.org/5332921							California

                    [StateAbbr] => AK

        - Present (http://eol.org/schema/terms/Present)
        - Native Statuses: (http://eol.org/schema/terms/NativeRange) or (http://eol.org/schema/terms/IntroducedRange)
        - Growth Habits: (http://purl.obolibrary.org/obo/FLOPO_0900032)
        - Durations (http://purl.obolibrary.org/obo/TO_0002725)
        */

        /* copied template
        $this->temp_path = CONTENT_RESOURCE_LOCAL_PATH . "BOLDS_temp/";
        if(Functions::is_production()) $this->BOLDS_new_path = "https://editors.eol.org/eol_connector_data_files/BOLDS_new/";
        else                           $this->BOLDS_new_path = "http://localhost/cp/BOLDS_new/";
        */
    }
    function initialize()
    {
        self::set_service_urls();
        self::get_US_states_list(); //working OK, but was never used
    }
    function start()
    {
        self::initialize();
        self::main();
        $this->archive_builder->finalize(true);
        // Functions::start_print_debug();
    }
    private function main()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30*3; //3 months
        $csv_file = Functions::save_remote_file_to_local($this->service['plant_list'], $options);
        $out = shell_exec("wc -l ".$csv_file); echo "$out";
        $i = 0;
        $file = Functions::file_open($csv_file, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            // print_r($row);
            $i++; if(($i % 2000) == 0) echo "\n$i";
            if($i == 1) {
                $fields = $row;
                // $fields = self::fill_up_blank_fieldnames($fields); // copied template
                $count = count($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n");
                    continue;
                }
                $k = 0; $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                // print_r($fields); print_r($rec); exit;
                if($rec['Synonym Symbol']) { //meaning it is a synonym
                    self::write_synonym($rec);
                    continue;
                }
                self::process_rec($rec);
            }
            // if($i > 10) break; //debug only
            // break;
        }
        unlink($csv_file);
    }
    function lookup_profile($symbol)
    {
        $url = $this->serviceUrls->plantsServicesUrl.'PlantProfile?symbol='.$symbol;
        // https://plantsservices.sc.egov.usda.gov/api/PlantProfile?symbol=ABBA
        if($json = Functions::lookup_with_cache($url, $this->download_options)) return $json;
    }
    private function process_rec($rec)
    {   /*Array(
        [Symbol] => ABAB
        [Synonym Symbol] => 
        [Scientific Name with Author] => Abutilon abutiloides (Jacq.) Garcke ex Hochr.
        [Common Name] => shrubby Indian mallow
        [Family] => Malvaceae
        )*/
        // $rec['Symbol'] = "ACOCC"; //"ABBA"; //force assign | ABES with copyrights | ACOCC subspecies
        if($json = self::lookup_profile($rec['Symbol'])) {
            $profile = json_decode($json); //print_r($profile); exit;
            /*Array( PlantProfile
                [Id] => 65791
                [Symbol] => ABAB
                [ScientificName] => <i>Abutilon abutiloides</i> (Jacq.) Garcke ex Hochr.
                [ScientificNameComponents] => 
                [CommonName] => shrubby Indian mallow
                [Group] => Dicot
                [RankId] => 180
                [Rank] => Species
                ...and many more...even trait data
            */

            // /*
            if(!isset($profile->Id)) {
                self::create_taxon_archive_using_rec($rec);
                return;
            }
            // */

            echo "\nSymbol: ".$rec['Symbol']." | Plant ID: $profile->Id | HasImages: $profile->HasImages"; //exit;
            $this->symbol_plantID_info[$rec['Symbol']] = $profile->Id;
            self::create_taxon_archive($rec, $profile);
            self::create_taxon_ancestry($profile);

            if($profile->HasImages > 0) {
                if($imgs = self::get_images($profile)) {
                    /* moved up, since we are now adding synonyms as well. Some taxa have synonyms but no images.
                    self::create_taxon_archive($rec, $profile);
                    self::create_taxon_ancestry($profile);
                    */
                    self::create_media_archive($profile->Id, $imgs);
                }
                // exit;
            }
            else {
                echo " | no images";
                // print_r($profile); exit("\nhas no images!\n");
            }
        }
        else exit("\ncannot lookup\n");
    }
    private function get_images($obj)
    {
        $url = $this->serviceUrls->plantsServicesUrl.'PlantImages?plantId='.$obj->Id;
        // https://plantsservices.sc.egov.usda.gov/api/PlantImages?plantId=15309
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $imgs = json_decode($json); //print_r($objs); exit("\n111\n");
            echo " | No. of images: ".count($imgs);
            return $imgs;
            /*[1] => stdClass Object(
                [ImageID] => 80
                [StandardSizeImageLibraryPath] => /ImageLibrary/standard/abes_002_shp.jpg
                [ThumbnailSizeImageLibraryPath] => /ImageLibrary/thumbnail/abes_002_thp.jpg
                [LargeSizeImageLibraryPath] => /ImageLibrary/large/abes_002_lhp.jpg
                [OriginalSizeImageLibraryPath] => /ImageLibrary/original/abes_002_php.jpg
                [Copyright] => 1            --- should not be 1
                [CommonName] => Pedro Acevedo-Rodriguez
                [Title] => 
                [ImageCreationDate] => 
                [Collection] => 
                [InstitutionName] => Smithsonian Institution, Department of Botany
                [ImageLocation] => United States, Virgin Islands, Saint John Co.
                [Comment] => 
                [EmailAddress] => RUSSELLR@si.edu
                [LiteratureTitle] => 
                [LiteratureYear] => 0
                [LiteraturePlace] => 
                [ProvidedBy] => Smithsonian Institution, Department of Botany
                [ScannedBy] => 
            )*/
        }

    }
    private function create_taxon_archive($rec, $profile)
    {   /*Array(
        [Symbol] => ABAB
        [Synonym Symbol] => 
        [Scientific Name with Author] => Abutilon abutiloides (Jacq.) Garcke ex Hochr.
        [Common Name] => shrubby Indian mallow
        [Family] => Malvaceae
        )*/
        /*Array( PlantProfile
        [Id] => 65791
        [Symbol] => ABAB
        [ScientificName] => <i>Abutilon abutiloides</i> (Jacq.) Garcke ex Hochr.
        [ScientificNameComponents] => 
        [CommonName] => shrubby Indian mallow
        [Group] => Dicot
        [RankId] => 180
        [Rank] => Species
        ...and many more...even trait data
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $profile->Id;
        $taxon->parentNameUsageID   = self::get_parent_id($profile);

        if($profile->ScientificName) {
            $ret = self::parse_sciname($profile->ScientificName);
            $taxon->scientificName              = $ret['sciname'];
            $taxon->scientificNameAuthorship    = $ret['author'];    
        }
        else { //should not go here...
            // print_r($profile); exit("\nno profile->sciname\n");
            $ret = self::parse_sciname($rec['Scientific Name with Author']);
            $taxon->scientificName              = $ret['sciname'];
            $taxon->scientificNameAuthorship    = $ret['author'];    
        }

        $taxon->taxonRank                   = strtolower($profile->Rank);
        if($profile->Rank != "Family") $taxon->family = $rec['Family'];
        $taxon->source = 'https://plants.usda.gov/home/plantProfile?symbol='.$profile->Symbol; //furtherInformationURL
        /* no data for:
        $taxon->taxonomicStatus          = '';
        $taxon->acceptedNameUsageID      = '';
        */
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
        /* start vernaculars */
        $rec['taxonID'] = $profile->Id;
        self::create_vernacular($rec);
    }
    private function write_synonym($rec)
    {   /*Array( e.g. synonym record
        [Symbol] => ABAB
        [Synonym Symbol] => ABAM5
        [Scientific Name with Author] => Abutilon americanum (L.) Sweet
        [Common Name] => 
        [Family] => 
        )*/
        if($acceptedNameUsageID = @$this->symbol_plantID_info[$rec['Symbol']]) {}
        else {
            return; //no acceptedNameUsageID for this synonym
            print_r($rec);
            exit("\nInvestigate: no acceptedNameUsageID\n");
        }
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec['Synonym Symbol'];
        /* copied template, but does not work here
        $ret = self::parse_sciname($profile->ScientificName);
        $taxon->scientificName              = $ret['sciname'];
        $taxon->scientificNameAuthorship    = $ret['author'];
        */
        $taxon->scientificName = $rec['Scientific Name with Author'];
        $taxon->taxonomicStatus          = 'synonym';
        $taxon->acceptedNameUsageID      = $acceptedNameUsageID;
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
    }
    private function create_vernacular($rec)
    {   //print_r($rec); exit;
        if(!$rec['Common Name']) return;
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec["taxonID"];
        $v->vernacularName  = $rec['Common Name'];
        $v->language        = "en";
        $vernacular_id = md5("$v->taxonID|$v->vernacularName|$v->language");
        if(!isset($this->vernacular_name_ids[$vernacular_id])) {
           $this->vernacular_name_ids[$vernacular_id] = '';
           $this->archive_builder->write_object_to_file($v);
        }
    }
    private function parse_sciname($name_str)
    {   // e.g. "<i>Abutilon abutiloides</i> (Jacq.) Garcke ex Hochr."
        // <i>Achnatherum occidentale</i> (Thurb.) Barkworth ssp. <i>californicum</i> (Merr. & Burtt Davy) Barkworth
        if(preg_match_all("/<i>(.*?)<\/i>/ims", $name_str, $arr)) {

            if(count($arr[1]) == 1) {
                $sciname = trim($arr[1][0]);
                // echo "\n[$name_str]\n[".$arr[1]."]\n"; exit; //good debug
                $author = strip_tags($name_str);
                $author = trim(str_replace($sciname, "", $author));
                return array('sciname' => $sciname, 'author' => $author);    
            }
            elseif(count($arr[1]) > 1) {
                /* ver 1
                $sciname = implode(" ", $arr[1]); echo (" >2 words -[$sciname]- ");
                $author = "";
                // ----- get author of subspecies or variety e.g. "<i>Achnatherum occidentale</i> (Thurb.) Barkworth ssp. <i>californicum</i> (Merr. & Burtt Davy) Barkworth"
                $last = end($arr[1]);
                if(preg_match("/<i>".$last."<\/i>(.*?)elix/ims", $name_str."elix", $arr2)) {
                    $author = trim($arr2[1]); // echo "\n[".$author."]\n";
                }
                // -----
                return array('sciname' => $sciname, 'author' => $author);    
                */
                // /* ver 2
                return array('sciname' => Functions::remove_whitespace(strip_tags($name_str)), 'author' => '');    
                // */
            }
            else exit("\ninvestigate here...\n");

        }
        else {
            // exit("\nNo italics\n$name_str\n");
            return array('sciname' => $name_str, 'author' => '');    
        }
    }
    private function get_available_image_path($img)
    {   /*
        [StandardSizeImageLibraryPath] => /ImageLibrary/standard/abli_001_shp.jpg
        [LargeSizeImageLibraryPath] => /ImageLibrary/large/abli_001_lhp.jpg
        [OriginalSizeImageLibraryPath] => /ImageLibrary/original/abli_001_php.jpg
        */
        if($val = trim($img->LargeSizeImageLibraryPath)) return $val;
        if($val = trim($img->StandardSizeImageLibraryPath)) return $val;
        if($val = trim($img->OriginalSizeImageLibraryPath)) return $val;
        return false;
    }
    private function create_media_archive($taxid, $imgs)
    {   //print_r($imgs); //exit;
        // return;
        foreach($imgs as $img) {
            if($img->Copyright) continue; //proceed only if not copyrighted
            // /*
            $image_file_path = self::get_available_image_path($img);
            if(!$image_file_path) continue; //blank path
            // */
            // print_r($img);
            $mr = new \eol_schema\MediaResource();
            // if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
            if($agent_ids = self::format_agents($img)) $mr->agentID = implode("; ", $agent_ids);
            $mr->taxonID                = $taxid;
            $mr->identifier             = $img->ImageID;
            $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
            $mr->format                 = Functions::get_mimetype($image_file_path);
            // $mr->furtherInformationURL  = '';
            $mr->description            = self::format_description($img);
            $mr->UsageTerms             = 'http://creativecommons.org/publicdomain/zero/1.0/'; //'http://creativecommons.org/licenses/publicdomain/'; //'http://creativecommons.org/licenses/by-nc-sa/3.0/';
            $mr->Owner                  = $img->ProvidedBy;
            // /*
            $rights = "<p>This image is not copyrighted and may be freely used for any purpose. Please credit the artist, original publication if applicable, and the USDA-NRCS PLANTS Database. The following format is suggested and will be appreciated:</p>";
            if($val = $img->CommonName) {
                $rights .= "<p>$val @ USDA-NRCS PLANTS Database</p>"; 
            }
            $rights .= "<p>If you cite PLANTS in a bibliography, please use the following: USDA, NRCS. [insert current year here]. PLANTS Database (https://plants.sc.egov.usda.gov/, [insert current date here]). National Plant Data Team, Greensboro, NC 27401-4901 USA.</p>";
            $mr->rights = Functions::remove_whitespace($rights);
            // */

            $tmp = $this->serviceUrls->imageLibraryUrl . $image_file_path;
            $mr->accessURI = str_replace("/ImageLibrary/ImageLibrary/", "/ImageLibrary/", $tmp);
            // https://plants.sc.egov.usda.gov/ImageLibrary/large/abam_016_lvp.jpg

            // $mr->Rating                 = '';
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';            
                @$this->image_cap[$mr->taxonID]++;
            }    
        }
    }
    private function format_description($img)
    {
        $tmp = "";
        if($val = $img->Title) $tmp .= $val.". ";
        if($val = $img->Comment) $tmp .= $val.". ";
        if($val = $img->CommonName) $tmp .= $val.". ";
        if($val = $img->ProvidedBy) $tmp .= "Provided by ".$val.". ";
        if($val = $img->ImageLocation) $tmp .= $val.". ";
        return trim($tmp);
    }
    private function format_agents($img)
    {   /*[0] => stdClass Object(
                    [ImageID] => 157
                    [StandardSizeImageLibraryPath] => /ImageLibrary/standard/abli_001_shp.jpg
                    [ThumbnailSizeImageLibraryPath] => /ImageLibrary/thumbnail/abli_001_thp.jpg
                    [LargeSizeImageLibraryPath] => /ImageLibrary/large/abli_001_lhp.jpg
                    [OriginalSizeImageLibraryPath] => /ImageLibrary/original/abli_001_php.jpg
                    [Copyright] => 
                    [CommonName] => Tracey Slotta
                    [Title] => 
                    [ImageCreationDate] => 
                    [Collection] => 
                    [InstitutionName] => ARS Systematic Botany and Mycology Laboratory
                    [ImageLocation] => United States, Texas
                    [Comment] => 
                    [EmailAddress] => 
                    [LiteratureTitle] => 
                    [LiteratureYear] => 0
                    [LiteraturePlace] => 
                    [ProvidedBy] => ARS Systematic Botany and Mycology Laboratory
                    [ScannedBy] => 
                )
        */
        $agent_ids = array();
        if($agent_name = $img->InstitutionName) {
            $tmp_ids = self::create_agent($agent_name, 'source');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        if($agent_name = $img->ProvidedBy) {
            $tmp_ids = self::create_agent($agent_name, 'source');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        if($agent_name = $img->CommonName) {
            $tmp_ids = self::create_agent($agent_name, 'photographer');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        $agent_ids = array_filter($agent_ids); //remove null arrays
        $agent_ids = array_unique($agent_ids); //make unique
        $agent_ids = array_values($agent_ids); //reindex key
        return $agent_ids;
    }
    private function create_agent($agent_name, $role)
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = $agent_name;
        $r->agentRole       = $role;
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = '';
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
    private function get_parent_id($profile)
    {   // print_r($profile->Ancestors); //exit;
        $index = count($profile->Ancestors) - 2; //to get index of immediate parent
        // print_r($profile->Ancestors[$index]); exit("\n".$profile->Ancestors[$index]->Id."\n");
        return $profile->Ancestors[$index]->Id;
    }
    private function create_taxon_ancestry($profile)
    {
        $i = -1;
        foreach($profile->Ancestors as $a) { $i++; //print_r($a);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = $a->Id;
            $taxon->parentNameUsageID   = @$profile->Ancestors[$i-1]->Id;
            // /* old
            if($a->ScientificName) {
                $ret = self::parse_sciname($a->ScientificName);
                $taxon->scientificName              = $ret['sciname'];
                $taxon->scientificNameAuthorship    = $ret['author'];    
            }
            else {
                print_r($profile); print_r($a);
                exit("\ninvestigate no sciname in ancestry list\n");
            }
            // */

            $taxon->taxonRank                   = strtolower($a->Rank);
            $taxon->source = 'https://plants.usda.gov/home/plantProfile?symbol='.$a->Symbol; //furtherInformationURL
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);    
            }
            /* start vernaculars */
            $rec = array();
            $rec['taxonID']     = $a->Id;
            $rec['Common Name'] = $a->CommonName;
            self::create_vernacular($rec);
        }
    }

    private function create_taxon_archive_using_rec($rec)
    {   /*Array(
        [Symbol] => ABAB
        [Synonym Symbol] => 
        [Scientific Name with Author] => Abutilon abutiloides (Jacq.) Garcke ex Hochr.
        [Common Name] => shrubby Indian mallow
        [Family] => Malvaceae
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec['Symbol'];
        $ret = self::parse_sciname($rec['Scientific Name with Author']);
        $taxon->scientificName              = $ret['sciname'];
        $taxon->scientificNameAuthorship    = $ret['author'];    
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
    }
    private function get_US_states_list()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30*12; //1 year
        $tsv_file = Functions::save_remote_file_to_local($this->github['US State list'], $options);
        $out = shell_exec("wc -l ".$tsv_file); echo "\nUS States/Territories: $out";
        $i = 0;
        foreach(new FileIterator($tsv_file) as $line_number => $line) { $i++;
            $row = explode("\t", $line);
            $abbrev = $row[1];
            $state_name = $row[0];
            $this->US_abbrev_state[$abbrev] = $state_name;
        }
        unlink($tsv_file);
    }
    private function set_service_urls()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //1 month
        if($json = Functions::lookup_with_cache($this->service['URLs'], $options)) {
            $obj = json_decode($json);
            $this->serviceUrls = $obj->serviceUrls;
            // print_r($obj); print_r($this->serviceUrls);
            // "plantsServicesUrl": "https://plantsservices.sc.egov.usda.gov/api/",        TO BE USED
            // "imageLibraryUrl": "https://plants.sc.egov.usda.gov/ImageLibrary",          TO BE USED

            // $this->serviceUrls->imageLibraryUrl --- for media accessURI
        }
    }
    // =========================================================================================
    // ========================================================================================= copied template below
    // =========================================================================================

    private function download_and_extract_remote_file($file = false, $use_cache = false)
    {
        if(!$file) $file = $this->data_dump_url; // used when this function is called elsewhere
        $download_options = $this->download_options;
        $download_options['timeout'] = 172800;
        $download_options['file_extension'] = 'txt.zip';
        $download_options['expire_seconds'] = 60*60*24*30;
        if($use_cache) $download_options['cache'] = 1;
        // $download_options['cache'] = 0; // 0 only when developing //debug - comment in real operation
        $temp_path = Functions::save_remote_file_to_local($file, $download_options);
        echo "\nunzipping this file [$temp_path]... \n";
        shell_exec("unzip -o " . $temp_path . " -d " . DOC_ROOT."tmp/"); //worked OK
        unlink($temp_path);
        if(is_dir(DOC_ROOT."tmp/"."__MACOSX")) recursive_rmdir(DOC_ROOT."tmp/"."__MACOSX");
    }
    //==================================================================================================================
    /* not being used as of Aug 6, 2018

        $download_options = $this->download_options;
        $download_options['expire_seconds'] = false;

        foreach($phylums as $phylum) {
            echo "\n$phylum ";
            $final = array();
            $temp_file = Functions::save_remote_file_to_local($this->service['phylum'].$phylum, $download_options);
            $reader = new \XMLReader();
            $reader->open($temp_file);
            while(@$reader->read()) {
                if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "record") {
                    $string = $reader->readOuterXML();
                    if($xml = simplexml_load_string($string)) {
                        // print_r($xml);
                        $ranks = array('phylum', 'class', 'order', 'family', 'genus', 'species');
                        $ranks = array('phylum', 'class', 'order', 'family', 'genus');
                        foreach($ranks as $rank) {
                            // echo "\n - $phylum ".@$xml->taxonomy->$rank->taxon->taxid."\n";
                            if($taxid = (string) @$xml->taxonomy->$rank->taxon->taxid) {
                                $final[$taxid] = '';
                            }
                        }
                    }
                }
            }
            unlink($temp_file);
            self::process_ids_for_this_phylum(array_keys($final), $phylum);
            // break; //debug
        }
    }
    */

    // private function process_record($taxid)
    // {
    //     self::create_trait_archive($a);
    // }
    private function create_trait_archive($a)
    {
        /*            */
        if($val = @$a['stats']['publicrecords']) {
            $rec = array();
            $rec["taxon_id"]            = $a['taxid'];
            $rec["catnum"]              = self::generate_id_from_array_record($a);
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/NumberPublicRecordsInBOLD";
            $rec['measurementValue']    = $val;
            $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
            self::add_string_types($rec);
        }
    }
    private function add_string_types($rec, $a = false) //$a is only for debugging
    {
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"], $rec);
        unset($rec['catnum']);
        unset($rec['taxon_id']);
        
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID = $occurrence_id;
        foreach($rec as $key => $value) $m->$key = $value;
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }

    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        if($val = @$rec['lifestage']) $o->lifeStage = $val;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
}
?>