<?php
namespace php_active_record;
/* connector:
http://content.eol.org/resources/640
http://content.eol.org/resources/30
*/
class QuaardvarkAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 1000000, 'timeout' => 172800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->download_options["expire_seconds"] = 60*60*24*30; //false orig
        $this->download_options["expire_seconds"] = false; //false orig

        $this->debug = array();
        $this->url['Habitat'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FDE-F3B2-0001-913C-B28812191D82/?start=';
        $this->url['Geographic Range'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E269055-F07D-0001-79AC-D4E055D018F4/?start=';
        $this->url['Physical Description'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E26905C-DEFA-0001-C0BD-C94B291C77C0/?start=';
        $this->url['Development'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E269098-5CC7-0001-2DE7-C83810947540/?start=';
        $this->url['Reproduction: Mating Systems'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690A5-331A-0001-C982-113D71801250/?start=';
        $this->url['Reproduction: General Behavior'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E26909A-D938-0001-B067-96FC19F012D8/?start=';
        $this->url['Reproduction: Parental Investment'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690A7-C348-0001-C87C-1A92B3001DBB/?start=';
        $this->url['Lifespan Longevity'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690EB-D94C-0001-9425-1FC0D300FD80/?start=';
        $this->url['Behavior'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690EC-4A91-0001-4ECD-1683ECF087C0/?start=';
        $this->url['Communication and Perception'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690ED-B734-0001-3854-537018B0183F/?start=';
        $this->url['Food Habits'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690EF-01A6-0001-A675-17101E2016DF/?start=';

        $this->field_count['Habitat'] = 9;
        $this->field_count['Geographic Range'] = 6;
        $this->field_count['Physical Description'] = 18; //it varies - due to ranges that sometimes disappears if without value
        // $this->field_count['Development'] = 5;                                          //   excluded
        $this->field_count['Reproduction: Mating Systems'] = 5;
        // $this->field_count['Reproduction: General Behavior'] = 25;   //it varies             excluded
        $this->field_count['Reproduction: Parental Investment'] = 5;
        // $this->field_count['Lifespan Longevity'] = 14;               //it varies             excluded
        $this->field_count['Behavior'] = 8;                             //it varies
        // $this->field_count['Communication and Perception'] = 7;                         //   excluded
        $this->field_count['Food Habits'] = 9;

        /*
        https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FDE-F3B2-0001-913C-B28812191D82/?start=1
        https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FF7-B855-0001-DDF1-12C01181A670/?start=201
        https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FF7-B855-0001-DDF1-12C01181A670/?start=401
        */
        $path = CONTENT_RESOURCE_LOCAL_PATH.'/reports/ADW_Quaardvark/';
        if(!is_dir($path)) mkdir($path);
        $this->report = $path;
        // exit("\n$this->report\n");
        
        $this->url['Media Assets: Subjects > Live Animal'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379B89-5DF7-0001-62C8-9A96CCF04A50/?start=';
            $this->field_count['Media Assets: Subjects > Live Animal'] = 5; //7119 matches
        $this->url['Media Assets: Subjects > Behaviors'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379B95-BFFC-0001-9DBF-374010D0F720/?start=';
            $this->field_count['Media Assets: Subjects > Behaviors'] = 5; //1808 matches
        $this->url['Media Assets: Subjects > Habitat'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379C11-DC75-0001-2777-1630CB40DCC0/?start=';
            $this->field_count['Media Assets: Subjects > Habitat'] = 5; //79 matches
        $this->url['Media Assets: Subjects > Anatomy'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379C4B-6C59-0001-3C1A-181617A08B00/?start=';
            $this->field_count['Media Assets: Subjects > Anatomy'] = 5; //4934 matches
        $this->url['Media Assets: Subjects > Life Stages and Gender'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379C4D-186B-0001-6A78-151C47601950/?start=';
            $this->field_count['Media Assets: Subjects > Life Stages and Gender'] = 5; //6976 matches
        
        $this->url['Media Assets: Specimens > Specimen: Foot'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379D9A-176D-0001-A9BE-13C01DA011A6/?start=';
            $this->field_count['Media Assets: Specimens > Specimen: Foot'] = 8; //36 matches
        $this->url['Media Assets: Specimens > Specimen: Forefoot'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379D9C-A848-0001-FACA-18101297D8D0/?start=';
            $this->field_count['Media Assets: Specimens > Specimen: Forefoot'] = 7; //27 matches
        $this->url['Media Assets: Specimens > Specimen: Forelimb'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379D9D-2F3D-0001-C77D-1A8CB6D015A7/?start=';
            $this->field_count['Media Assets: Specimens > Specimen: Forelimb'] = 8; //19 matches
        $this->url['Media Assets: Specimens > Specimen: Hindfoot'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379D9D-B055-0001-6082-14F013C0185C/?start=';
            $this->field_count['Media Assets: Specimens > Specimen: Hindfoot'] = 7; //53 matches
        $this->url['Media Assets: Specimens > Specimen: Lower Jaw'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379D9E-3338-0001-994F-118EA2D7D2C0/?start=';
            $this->field_count['Media Assets: Specimens > Specimen: Lower Jaw'] = 7; //584 matches
        $this->url['Media Assets: Specimens > Specimen: Skull'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379DA1-1DB4-0001-F442-184024E517D6/?start=';
            $this->field_count['Media Assets: Specimens > Specimen: Skull'] = 22; //762 matches --- CONNECTOR DIDN'T GET ANY RECORDS
        $this->url['Media Assets: Specimens > Specimen: Teeth'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E379DA0-1AC2-0001-D8FF-B6B561E0193B/?start=';
            $this->field_count['Media Assets: Specimens > Specimen: Teeth'] = 14; //709 matches
        $this->url['Media Assets: Specimens > Specimen: Vertebrae'] = '';
            $this->field_count['Media Assets: Specimens > Specimen: Vertebrae'] = 0; //0 matches

        $this->accepted_licenses = array('by-nc-sa', 'by-nc', 'by-sa', 'by', 'publicdomain');
        $this->license_lookup['publicdomain'] = 'http://creativecommons.org/licenses/publicdomain/';
        $this->license_lookup['by'] = 'http://creativecommons.org/licenses/by/3.0/';
        $this->license_lookup['by-nc'] = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $this->license_lookup['by-sa'] = 'http://creativecommons.org/licenses/by-sa/3.0/';
        $this->license_lookup['by-nc-sa'] = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
    }
    public function start()
    {   // /* copied template
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        // */
        self::initialize();
        $topics = array('Habitat', 'Geographic Range', 'Physical Description', 'Development', 'Reproduction: General Behavior',
                        'Reproduction: Mating Systems', 'Reproduction: Parental Investment', 'Lifespan Longevity', 'Behavior',
                        'Communication and Perception', 'Food Habits');
        /*
        x- Habitat
        x- Geographic Range
        x- Physical Description
        - Development
        x- Reproduction: Mating Systems
        - Reproduction: General Behavior
        x- Reproduction: Parental Investment
        - Lifespan Longevity
        x- Behavior
        - Communication and Perception
        x- Food Habits
        */

        // /* MoF records: un-comment in real operation
        $topics = array('Habitat', 'Geographic Range', 'Physical Description', 'Reproduction: Mating Systems', 'Reproduction: Parental Investment', 
                        'Behavior', 'Food Habits');
        // $topics = array('Reproduction: Parental Investment'); //debug only
        // $topics = array('Habitat'); //debug only
        // $topics = array('Geographic Range'); //debug only
        foreach($topics as $data) self::main($data);
        // */

        // /* Image objects: un-comment in real operation
        $topics = array('Media Assets: Subjects > Live Animal', 'Media Assets: Subjects > Behaviors', 'Media Assets: Subjects > Habitat', 
                        'Media Assets: Subjects > Life Stages and Gender', 'Media Assets: Subjects > Anatomy',
                        'Media Assets: Specimens > Specimen: Foot', 'Media Assets: Specimens > Specimen: Forefoot',
                        'Media Assets: Specimens > Specimen: Forelimb', 'Media Assets: Specimens > Specimen: Hindfoot',
                        'Media Assets: Specimens > Specimen: Lower Jaw', 'Media Assets: Specimens > Specimen: Skull',
                        'Media Assets: Specimens > Specimen: Teeth'); // for stillImage objects

        // $topics = array('Media Assets: Specimens > Specimen: Skull'); // for stillImage objects
        foreach($topics as $data) self::main($data);
        // exit("\ncaching only...\n");
        // */
        
        $this->archive_builder->finalize(true);
        echo "\n"; print_r($this->debug);
    }
    private function main($data)
    {
        $this->print_fields = false;
        if($total_pages = self::get_total_number_of_pages($data)) {
            $loops = ceil($total_pages/200);
            echo "\n $data\n total_pages: [$total_pages]\n loops: [$loops]\n";
            $sum = 1;
            for ($i = 1; $i <= $loops; $i++) {
                echo "\n$i. $sum";
                $url = $this->url[$data].$sum;
                if($html = Functions::lookup_with_cache($url, $this->download_options)) { //hits="6369"
                    $recs = self::parse_page($html, $data);
                }
                $sum = $sum + 200;
                // if($i >= 2) break; //debug only
            }
        }
        if(isset($this->debug['Habitat'])) {
            ksort($this->debug['Habitat']['Habitat Regions']);
            ksort($this->debug['Habitat']['Terrestrial Biomes']);
            ksort($this->debug['Habitat']['Aquatic Biomes']);
            ksort($this->debug['Habitat']['Wetlands']);
            ksort($this->debug['Habitat']['Other Habitat Features']);
        }
        if(isset($this->debug['Geographic Range'])) {
            ksort($this->debug['Geographic Range']['Biogeographic Regions']);
            ksort($this->debug['Geographic Range']['Other Geographic Terms']);
        }
        if(isset($this->debug['Physical Description'])) {
            ksort($this->debug['Physical Description']['Other Physical Features']);
            ksort($this->debug['Physical Description']['Sexual Dimorphism']);
        }
        if(isset($this->debug['Development'])) {
            ksort($this->debug['Development']['Development - Life Cycle']);
        }
        if(isset($this->debug['Reproduction: Mating Systems'])) {
            ksort($this->debug['Reproduction: Mating Systems']['Mating System']);
        }
        if(isset($this->debug['Reproduction: General Behavior'])) {
            ksort($this->debug['Reproduction: General Behavior']['Key Reproductive Features']);
        }
        if(isset($this->debug['Reproduction: Parental Investment'])) {
            ksort($this->debug['Reproduction: Parental Investment']['Parental Investment']);
        }
        if(isset($this->debug['Behavior'])) {
            ksort($this->debug['Behavior']['Key Behaviors']);
        }
        if(isset($this->debug['Communication and Perception'])) {
            ksort($this->debug['Communication and Perception']['Communication Channels']);
            ksort($this->debug['Communication and Perception']['Other Communication Modes']);
            ksort($this->debug['Communication and Perception']['Perception Channels']);
        }
        if(isset($this->debug['Food Habits'])) {
            ksort($this->debug['Food Habits']['Primary Diet']);
            ksort($this->debug['Food Habits']['Animal Foods']);
            ksort($this->debug['Food Habits']['Plant Foods']);
            ksort($this->debug['Food Habits']['Other Foods']);
            ksort($this->debug['Food Habits']['Foraging Behavior']);
        }
        // exit("\n-end-\n");
    }
    private function parse_page($html, $data)
    {
        $left = '<table xmlns:media="urn:animaldiversity.org:templates:media"';
        if(preg_match("/".preg_quote($left, '/')."(.*?)<\/table>/ims", $html, $a)) { //exit("\naaa\n");
            $main_block = $a[1];

            $fields = array();
            if(preg_match_all("/<th>(.*?)<\/th>/ims", $main_block, $a1)) $fields = $a1[1];
            if(!$this->print_fields) {
                if($GLOBALS['ENV_DEBUG']) {echo "\n"; print_r($fields);}
                $this->print_fields = true;
            }
            // /* during dev only
            if(count($fields) != $this->field_count[$data]) echo("\nInvestigate fields <th> tags: [$data] ".count($fields)."\n");
            // */
            
            $f = Functions::file_open($this->report.str_replace(' ','_',$data).'.txt', "w");
            fwrite($f, implode("\t", $fields)."\n");

            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $main_block, $a2)) { //exit("\nbbb\n");
                $rows201 = $a2[1];
                foreach($rows201 as $row) {
                    /*IMPORTANT MANUAL change...*/
                    $row = str_replace('<td type="sequence"/>', '<td type="sequence"></td>', $row);
                    $row = str_replace('<td type="text"/>', '<td type="text"></td>', $row);
                    $row = str_replace("<span/>", "<span></span>", $row);
                    $row = str_replace("<td/>", "<td></td>", $row);
                    $row = str_replace('<td type="gallery"/>', '<td type="gallery"></td>', $row);

                    if(preg_match_all("/<td(.*?)<\/td>/ims", $row, $a3)) {
                        $cols = $a3[1];
                        // print_r($cols); exit; //good debug
                        $ret = array();
                        foreach($cols as $col) {
                            // echo "\n---[$col]\n";
                            
                            if(substr(trim($col),0,15) == 'type="gallery">') {
                                // print_r($ret); exit("\n123\n");
                                if(preg_match_all("/href=\"(.*?)\"/ims", $col, $eli)) {
                                    // print_r($eli[1]); //exit;
                                    $ret[] = implode("|", $eli[1]);
                                }
                                else $ret[] = ''; //blank entry
                            }
                            else { //orig
                                $tmp = strip_tags("<td".$col, "<span>");
                                if(preg_match_all("/<span>(.*?)<\/span>/ims", $tmp, $a4)) {
                                    $tmp = $a4[1];
                                    $tmp = array_filter($tmp); //remove null arrays
                                    $tmp = array_unique($tmp); //make unique
                                    $tmp = array_values($tmp); //reindex key
                                    if($tmp) $tmp = implode(" | ", $tmp);
                                    else     $tmp = '';
                                }
                                $ret[] = $tmp;
                            }
                            
                            
                        }
                        $ret = array_map('trim', $ret);
                        // print_r($ret); exit; //good debug
                        /*Array( Habitat
                            [0] => Alpheus heterochaelis
                            [1] => Malacostraca
                            [2] => Decapoda
                            [3] => Alpheidae
                            [4] => Temperate | Tropical | Saltwater or marine
                            [5] => 
                            [6] => Benthic | Reef | Coastal | Brackish Water
                            [7] => Marsh
                            [8] => Estuarine
                        )*/
                        $rek = array(); $i = 0;
                        foreach($fields as $field) {
                            $rek[$field] = $ret[$i];
                            if(!isset($ret[$i])) {
                                print_r($ret); exit("\nwent here...\n");
                            }
                            $i++;
                        }
                        $rek = array_map('trim', $rek);
                        // print_r($rek); //exit("\nsample record\n"); //good debug
                        /*Array( Habitat
                            [Species] => Alpheus heterochaelis
                            [Class] => Malacostraca
                            [Order] => Decapoda
                            [Family] => Alpheidae
                            [Habitat Regions] => Temperate | Tropical | Saltwater or marine
                            [Terrestrial Biomes] => 
                            [Aquatic Biomes] => Benthic | Reef | Coastal | Brackish Water
                            [Wetlands] => Marsh
                            [Other Habitat Features] => Estuarine
                        )
                        Array(
                            [Species] => Aotus azarae
                            [Class] => Mammalia
                            [Order] => Primates
                            [Family] => Aotidae
                            [Other Physical Features] => Endothermic | Homoiothermic | Bilateral symmetry
                            [Sexual Dimorphism] => Sexes alike
                            [Length - average - mm] => 305
                            [Length - extreme low - mm] => 240
                            [Length - extreme high - mm] => 370
                            [Wingspan - average - mm] => 
                            [Wingspan - extreme low - mm] => 
                            [Wingspan - extreme high - mm] => 
                            [Mass - average - g] => 800
                            [Mass - extreme low - g] => 600
                            [Mass - extreme high - g] => 1000
                            [Basal Metabolic Rate - average - W] => 
                            [Basal Metabolic Rate - extreme low - W] => 
                            [Basal Metabolic Rate - extreme high - W] => 
                        )*/
                        $rek = self::write_taxon($rek);
                        if(in_array($data, array('Media Assets: Subjects > Live Animal', 'Media Assets: Subjects > Behaviors', 
                                                 'Media Assets: Subjects > Habitat', 'Media Assets: Subjects > Anatomy', 
                                                 'Media Assets: Subjects > Life Stages and Gender', 'Media Assets: Specimens > Specimen: Foot',
                                                 'Media Assets: Specimens > Specimen: Forefoot', 'Media Assets: Specimens > Specimen: Forelimb',
                                                 'Media Assets: Specimens > Specimen: Hindfoot', 'Media Assets: Specimens > Specimen: Lower Jaw',
                                                 'Media Assets: Specimens > Specimen: Skull', 'Media Assets: Specimens > Specimen: Teeth'))) {
                            self::main_proc_images($rek);
                        }
                        else {
                            self::for_stats($rek, $data); //for stats only
                            self::write_habitat_MoF($rek, $data);
                        }
                        fwrite($f, implode("\t", $rek)."\n");
                    }
                }
            }
            fclose($f);
            // exit("\n-end-\n"); //if you want to investigate 1 html or 1 page
        }
    }
    private function write_habitat_MoF($rek, $data)
    {   /*Array( Habitat
        [Species] => Alpheus heterochaelis
        [Class] => Malacostraca
        [Order] => Decapoda
        [Family] => Alpheidae
        [Habitat Regions] => Temperate | Tropical | Saltwater or marine
        [Terrestrial Biomes] => 
        [Aquatic Biomes] => Benthic | Reef | Coastal | Brackish Water
        [Wetlands] => Marsh
        [Other Habitat Features] => Estuarine
        )*/
        
        // $mType = 'http://purl.obolibrary.org/obo/RO_0002303';
        $mType = @$this->values[$data]['measurementType']; //exit("\n$mType\n");
        // print_r($rek);
        
        // $subtopics = array('Habitat Regions', 'Terrestrial Biomes', 'Aquatic Biomes', 'Wetlands', 'Other Habitat Features');
        $subtopics = array_keys($this->values[$data]); //print_r($subtopics); exit;
        foreach($subtopics as $subtopic) {
            if($str = @$rek[$subtopic]) {
                $ret = self::group_pipe_separated_items_if_needed($str, $data, $subtopic, $mType);
                $final = $ret['final']; //see group_pipe_separated_items_if_needed()
                $mType = $ret['mType'];
                foreach($final as $mValue => $sections) {
                    $mRemarks = implode(";", $sections['terms']);
                    $mType = $sections['mType'];
                    $save = array();
                    $save['taxon_id'] = $rek['taxonID'];
                    $save["catnum"] = $rek['taxonID'].'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                    $save['source'] = $rek['furtherInformationURL'];
                    $save['measurementRemarks'] = $mRemarks;
                    // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; exit; //just testing
                    // print_r($save); exit("\n[$mType] [$mValue]\n"); //good debug
                    $this->func->pre_add_string_types($save, $mValue, $mType, "true");
                }
            }
        }
    }
    private function write_taxon($rek)
    {   /*  [Species] => Aotus azarae
            [Class] => Mammalia
            [Order] => Primates
            [Family] => Aotidae*/
        $taxonID = str_replace(' ', '_', $rek['Species']);
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $taxonID;
        $taxon->scientificName  = $rek['Species'];
        $taxon->class           = $rek['Class'];
        $taxon->order           = $rek['Order'];
        $taxon->family          = $rek['Family'];
        $taxon->furtherInformationURL = 'https://animaldiversity.org/accounts/'.$taxonID.'/';
        // if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids); //copied template
        if(!isset($this->taxon_ids[$taxonID])) {
            $this->taxon_ids[$taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
        $rek['taxonID'] = $taxon->taxonID;
        $rek['furtherInformationURL'] = $taxon->furtherInformationURL;
        return $rek;
    }
    private function for_stats($rek, $data)
    {
        $habitat = array('Habitat Regions', 'Terrestrial Biomes', 'Aquatic Biomes', 'Wetlands', 'Other Habitat Features');
        $geographic_range = array('Biogeographic Regions', 'Other Geographic Terms');
        $physical_desc = array('Other Physical Features', 'Sexual Dimorphism');
        //e.g. [Other Physical Features] => Endothermic | Homoiothermic | Bilateral symmetry
        $development = array('Development - Life Cycle');
        $Reproduction_Mating_Systems = array('Mating System');
        $Reproduction_General_Behavior = array('Key Reproductive Features');
        $Reproduction_Parental_Investment = array('Parental Investment');
        $Behavior = array('Key Behaviors');
        $Communication_and_Perception = array('Communication Channels', 'Other Communication Modes', 'Perception Channels');
        $Food_Habits = array('Primary Diet', 'Animal Foods', 'Plant Foods', 'Other Foods', 'Foraging Behavior');
        
        $pipe_separated = array_merge($habitat, $geographic_range, $physical_desc, $development, $Reproduction_Mating_Systems,
                                      $Reproduction_General_Behavior, $Reproduction_Parental_Investment, $Behavior,
                                      $Communication_and_Perception, $Food_Habits); // print_r($pipe_separated); exit;
        foreach($pipe_separated as $topic) {
            if($str = @$rek[$topic]) {
                $arr = explode(' | ', $str);
                foreach($arr as $value) $this->debug[$data][$topic][$value] = '';
            }
        }
    }
    private function get_total_number_of_pages($data)
    {
        $url = $this->url[$data]."1";
        if($html = Functions::lookup_with_cache($url, $this->download_options)) { //hits="6369"
            if(preg_match("/hits=\"(.*?)\"/ims", $html, $a)) return $a[1];
            else exit("\nNo hits, investigate URL: [$url]\n");
        }
    }
    private function initialize()
    {   /*
        x- Habitat
        x- Geographic Range
        x- Physical Description
        - Development
        x- Reproduction: Mating Systems
        - Reproduction: General Behavior
        x- Reproduction: Parental Investment
        - Lifespan Longevity
        x- Behavior
        - Communication and Perception
        x- Food Habits
        */
        $this->values['Habitat'] = Array(
                'measurementType' => 'http://purl.obolibrary.org/obo/RO_0002303',
                'Habitat Regions' => Array(
                        'Freshwater' => 'http://purl.obolibrary.org/obo/ENVO_00000873',
                        'Polar' => 'http://purl.obolibrary.org/obo/ENVO_01000339',
                        'Saltwater or marine' => 'http://purl.obolibrary.org/obo/ENVO_00000447',
                        'Temperate' => 'http://purl.obolibrary.org/obo/ENVO_01000206',
                        'Terrestrial' => 'http://purl.obolibrary.org/obo/ENVO_00000446',
                        'Tropical' => 'http://purl.obolibrary.org/obo/ENVO_01000204'
                    ),
                'Terrestrial Biomes' => Array(
                        'Chaparral' => 'http://purl.obolibrary.org/obo/ENVO_00000301',
                        'Desert or dune' => 'http://purl.obolibrary.org/obo/ENVO_01000179',
                        'Forest' => 'http://purl.obolibrary.org/obo/ENVO_01000174',
                        'Icecap' => 'http://purl.obolibrary.org/obo/ENVO_00000145',
                        'Mountains' => 'http://purl.obolibrary.org/obo/ENVO_00000081',
                        'Rainforest' => 'http://purl.obolibrary.org/obo/ENVO_01000228',
                        'Savanna or grassland' => 'http://purl.obolibrary.org/obo/ENVO_01000177',
                        'Scrub forest' => 'http://purl.obolibrary.org/obo/ENVO_00000300',
                        'Taiga' => 'http://eol.org/schema/terms/boreal_forests_taiga',
                        'Tundra' => 'http://purl.obolibrary.org/obo/ENVO_01000180'
                    ),
                'Aquatic Biomes' => Array(
                        'Abyssal' => 'http://purl.obolibrary.org/obo/ENVO_01000027',
                        'Benthic' => 'http://eol.org/schema/terms/benthic',
                        'Brackish Water' => 'http://purl.obolibrary.org/obo/ENVO_00002019',
                        'Coastal' => 'http://purl.obolibrary.org/obo/ENVO_01000687',
                        'Lakes and Ponds' => 'http://purl.obolibrary.org/obo/ENVO_00000873',
                        'Oceanic vent' => 'http://purl.obolibrary.org/obo/ENVO_01000030',
                        'Pelagic' => 'http://purl.obolibrary.org/obo/ENVO_00000208',
                        'Reef' => 'http://purl.obolibrary.org/obo/ENVO_01000029',
                        'Rivers and Streams' => 'http://purl.obolibrary.org/obo/ENVO_00000873',
                        'Temporary Pools' => 'http://eol.org/schema/terms/temporaryAquatic'
                    ),
                'Other Habitat Features' => Array(
                        'Agricultural' => 'http://purl.obolibrary.org/obo/ENVO_00000077',
                        'Caves' => 'http://purl.obolibrary.org/obo/ENVO_00000067',
                        'Estuarine' => 'http://purl.obolibrary.org/obo/ENVO_01000020',
                        'Intertidal or littoral' => 'http://purl.obolibrary.org/obo/ENVO_00000316',
                        'Riparian' => 'http://eol.org/schema/terms/riparianWetland',
                        'Suburban' => 'http://purl.obolibrary.org/obo/ENVO_00000002',
                        'Urban' => 'http://purl.obolibrary.org/obo/ENVO_00000856'
                    ),
                'Wetlands' => Array(
                        'Bog' => 'http://eol.org/schema/terms/bogPond',
                        'Marsh' => 'http://purl.obolibrary.org/obo/ENVO_00000035',
                        'Swamp' => 'http://purl.obolibrary.org/obo/ENVO_00000233'
                    )
            );
    $this->values['Geographic Range'] = Array(
                'measurementType' => 'http://eol.org/schema/terms/Present',
                'Biogeographic Regions' => Array(
                        'Antarctica' => 'http://www.geonames.org/6255152',
                        'Antarctica :: Introduced' => 'http://www.geonames.org/6255152',
                        'Antarctica :: Native' => 'http://www.geonames.org/6255152',
                        'Arctic Ocean' => 'http://www.marineregions.org/mrgid/1906',
                        'Arctic Ocean :: Introduced' => 'http://www.marineregions.org/mrgid/1906',
                        'Arctic Ocean :: Native' => 'http://www.marineregions.org/mrgid/1906',
                        'Atlantic Ocean' => 'http://www.marineregions.org/mrgid/1902',
                        'Atlantic Ocean :: Introduced' => 'http://www.marineregions.org/mrgid/1902',
                        'Atlantic Ocean :: Native' => 'http://www.marineregions.org/mrgid/1902',
                        
                        /* replace by one below per: https://eol-jira.bibalex.org/browse/DATA-1918
                        'Australian' => 'http://www.geonames.org/2077456',
                        'Australian :: Introduced' => 'http://www.geonames.org/2077456',
                        'Australian :: Native' => 'http://www.geonames.org/2077456',
                        */
                        'Australian' => 'http://www.geonames.org/6255151',
                        'Australian :: Introduced' => 'http://www.geonames.org/6255151',
                        'Australian :: Native' => 'http://www.geonames.org/6255151',
                        
                        'Ethiopian' => 'http://www.geonames.org/337996',
                        'Ethiopian :: Introduced' => 'http://www.geonames.org/337996',
                        'Ethiopian :: Native' => 'http://www.geonames.org/337996',
                        'Indian Ocean' => 'http://www.marineregions.org/mrgid/1904',
                        'Indian Ocean :: Introduced' => 'http://www.marineregions.org/mrgid/1904',
                        'Indian Ocean :: Native' => 'http://www.marineregions.org/mrgid/1904',
                        'Mediterranean Sea' => 'http://www.marineregions.org/mrgid/1905',
                        'Mediterranean Sea :: Introduced' => 'http://www.marineregions.org/mrgid/1905',
                        'Mediterranean Sea :: Native' => 'http://www.marineregions.org/mrgid/1905',
                        'Nearctic' => 'https://www.wikidata.org/entity/Q737742',
                        'Nearctic :: Introduced' => 'https://www.wikidata.org/entity/Q737742',
                        'Nearctic :: Native' => 'https://www.wikidata.org/entity/Q737742',
                        'Neotropical' => 'https://www.wikidata.org/entity/Q217151',
                        'Neotropical :: Introduced' => 'https://www.wikidata.org/entity/Q217151',
                        'Neotropical :: Native' => 'https://www.wikidata.org/entity/Q217151',
                        'Oceanic Islands' => 'http://purl.obolibrary.org/obo/ENVO_00000222',
                        'Oceanic Islands :: Introduced' => 'DISCARD',
                        'Oceanic Islands :: Native' => 'http://purl.obolibrary.org/obo/ENVO_00000222',
                        'Oriental' => 'http://www.geonames.org/6255147',
                        'Oriental :: Introduced' => 'http://www.geonames.org/6255147',
                        'Oriental :: Native' => 'http://www.geonames.org/6255147',
                        'Pacific Ocean' => 'http://www.marineregions.org/mrgid/1903',
                        'Pacific Ocean :: Introduced' => 'http://www.marineregions.org/mrgid/1903',
                        'Pacific Ocean :: Native' => 'http://www.marineregions.org/mrgid/1903',
                        'Palearctic' => 'https://www.wikidata.org/entity/Q106447',
                        'Palearctic :: Introduced' => 'https://www.wikidata.org/entity/Q106447',
                        'Palearctic :: Native' => 'https://www.wikidata.org/entity/Q106447'
                    ),
                'Other Geographic Terms' => Array(
                        'Cosmopolitan' => 'http://eol.org/schema/terms/Cosmopolitan',
                        'Holarctic' => 'https://www.wikidata.org/entity/Q39061',
                        'Island endemic' => 'DISCARD',
                    )
            );
    $this->values['Physical Description'] = Array(
                'measurementType' => 'http://eol.org/schema/terms/BodyShape',
                'Other Physical Features' => Array(
                        'Bilateral symmetry' => 'http://purl.obolibrary.org/obo/PATO_0001324',
                        'Ectothermic' => 'DISCARD',
                        'Endothermic' => 'DISCARD',
                        'Heterothermic' => 'DISCARD',
                        'Homoiothermic' => 'DISCARD',
                        'Poisonous' => 'DISCARD',
                        'Polymorphic' => 'DISCARD',
                        'Radial symmetry' => 'http://purl.obolibrary.org/obo/PATO_0001325',
                        'Venomous' => 'DISCARD',
                    ),
                'Sexual Dimorphism' => Array(
                        'measurementType' => 'http://www.owl-ontologies.com/unnamed.owl#Dimorphism',
                        'Female larger' => 'http://eol.org/schema/terms/female_larger',
                        'Female more colorful' => 'http://eol.org/schema/terms/female_more_colorful',
                        'Male larger' => 'http://eol.org/schema/terms/male_larger',
                        'Male more colorful' => 'http://eol.org/schema/terms/male_more_colorful',
                        'Ornamentation' => 'DISCARD',
                        'Sexes alike' => 'DISCARD',
                        'Sexes colored or patterned differently' => 'DISCARD',
                        'Sexes shaped differently' => 'DISCARD',
                    )
            );
    //start mapping 2
    $this->values['Reproduction: Mating Systems'] = Array(
        'measurementType' => 'http://eol.org/schema/terms/MatingSystem',
        'Mating System' => Array(
                'Cooperative breeder' => 'http://purl.obolibrary.org/obo/GO_0060746,http://eol.org/schema/terms/CooperativeBreeding',
                'Eusocial' => 'http://eol.org/schema/terms/SocialSystem,https://www.wikidata.org/entity/Q753694',
                'Monogamous' => 'http://purl.obolibrary.org/obo/ECOCORE_00000063',
                'Polyandrous' => 'http://purl.obolibrary.org/obo/ECOCORE_00000064',
                'Polygynandrous (promiscuous)' => 'http://purl.obolibrary.org/obo/ECOCORE_00000067',
                'Polygynous' => 'http://purl.obolibrary.org/obo/ECOCORE_00000065'
            )
    );
    $this->values['Reproduction: Parental Investment'] = Array(
        'measurementType' => 'http://purl.obolibrary.org/obo/GO_0060746', //usually
        'Parental Investment' => Array(
                'Altricial' => 'http://eol.org/schema/terms/DevelopmentalMode,http://eol.org/schema/terms/altricial',
                'Female parental care' => 'http://eol.org/schema/terms/parentalCareFemale',
                'Male parental care' => 'http://eol.org/schema/terms/paternalCare',
                'No parental involvement' => 'http://polytraits.lifewatchgreece.eu/terms/BP_NO',
                'Post-independence association with parents' => 'http://polytraits.lifewatchgreece.eu/terms/BP_YES',
                'Pre-fertilization :: Protecting' => 'https://www.wikidata.org/entity/Q2251595',
                'Pre-fertilization :: Protecting :: Female' => 'http://eol.org/schema/terms/parentalCareFemale',
                'Pre-fertilization :: Protecting :: Male' => 'http://eol.org/schema/terms/paternalCare',
                'Pre-fertilization :: Provisioning' => 'https://www.wikidata.org/entity/Q2874419',
                'Pre-hatching/birth :: Protecting' => 'https://www.wikidata.org/entity/Q2251595',
                'Pre-hatching/birth :: Protecting :: Female' => 'http://eol.org/schema/terms/parentalCareFemale',
                'Pre-hatching/birth :: Protecting :: Male' => 'http://eol.org/schema/terms/paternalCare',
                'Pre-hatching/birth :: Provisioning' => 'https://www.wikidata.org/entity/Q2874419',
                'Pre-hatching/birth :: Provisioning :: Female' => 'http://eol.org/schema/terms/parentalCareFemale',
                'Pre-hatching/birth :: Provisioning :: Male' => 'http://eol.org/schema/terms/paternalCare',
                'Pre-independence :: Protecting' => 'https://www.wikidata.org/entity/Q2251595',
                'Pre-independence :: Protecting :: Female' => 'http://eol.org/schema/terms/parentalCareFemale',
                'Pre-independence :: Protecting :: Male' => 'http://eol.org/schema/terms/paternalCare',
                'Pre-independence :: Provisioning' => 'https://www.wikidata.org/entity/Q2874419',
                'Pre-independence :: Provisioning :: Female' => 'http://eol.org/schema/terms/parentalCareFemale',
                'Pre-independence :: Provisioning :: Male' => 'http://eol.org/schema/terms/paternalCare',
                'Pre-weaning/fledging :: Protecting' => 'https://www.wikidata.org/entity/Q2251595',
                'Pre-weaning/fledging :: Protecting :: Female' => 'http://eol.org/schema/terms/parentalCareFemale',
                'Pre-weaning/fledging :: Protecting :: Male' => 'http://eol.org/schema/terms/paternalCare',
                'Pre-weaning/fledging :: Provisioning' => 'https://www.wikidata.org/entity/Q2874419',
                'Pre-weaning/fledging :: Provisioning :: Female' => 'http://eol.org/schema/terms/parentalCareFemale',
                'Pre-weaning/fledging :: Provisioning :: Male' => 'http://eol.org/schema/terms/paternalCare',
                'Precocial' => 'http://eol.org/schema/terms/DevelopmentalMode,http://eol.org/schema/terms/precocial'
            )
    );
    //start mapping 3
    $this->values['Behavior'] = Array(
            'Key Behaviors' => Array(
                    'aestivation' => 'DISCARD',
                    
                    // 'arboreal' => 'http://purl.obolibrary.org/obo/RO_0002303,http://purl.obolibrary.org/obo/NBO_0000364',
                    'arboreal' => 'http://purl.obolibrary.org/obo/RO_0002303,http://purl.obolibrary.org/obo/ENVO_00000571',
                    // per: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=67779&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67779

                    'colonial' => 'http://eol.org/schema/terms/SocialSystem,http://eol.org/schema/terms/socialGroupLiving',
                    'crepuscular' => 'http://purl.obolibrary.org/obo/VT_0001502, http://purl.obolibrary.org/obo/ECOCORE_00000078',
                    'cursorial' => 'http://purl.obolibrary.org/obo/GO_0040011,http://purl.obolibrary.org/obo/NBO_0000055',
                    'daily torpor' => 'DISCARD',
                    'diurnal' => 'http://purl.obolibrary.org/obo/VT_0001502, http://www.wikidata.org/entity/Q906470',
                    'dominance hierarchies' => 'DISCARD',
                    'flies' => 'http://purl.obolibrary.org/obo/GO_0040011,http://purl.obolibrary.org/obo/NBO_0000367',
                    'fossorial' => 'http://eol.org/schema/terms/EcomorphologicalGuild, http://www.wikidata.org/entity/Q2850019',
                    'glides' => 'http://purl.obolibrary.org/obo/GO_0040011,http://purl.obolibrary.org/obo/NBO_0000369',
                    'hibernation' => 'DISCARD',
                    'migratory' => 'http://purl.obolibrary.org/obo/IDOMAL_0002084, http://eol.org/schema/terms/migratory',
                    'motile' => 'http://www.wikidata.org/entity/Q33596, http://eol.org/schema/terms/activelyMobile',
                    'natatorial' => 'http://purl.obolibrary.org/obo/GO_0040011,http://purl.obolibrary.org/obo/GO_0036268',
                    'nocturnal' => 'http://purl.obolibrary.org/obo/VT_0001502, http://www.wikidata.org/entity/Q309179',
                    'nomadic' => 'DISCARD',
                    'parasite' => 'http://eol.org/schema/terms/TrophicGuild, https://www.wikidata.org/entity/Q12806437',
                    'saltatorial' => 'http://purl.obolibrary.org/obo/GO_0040011,http://purl.obolibrary.org/obo/NBO_0000370',
                    'scansorial' => 'http://purl.obolibrary.org/obo/GO_0040011,http://purl.obolibrary.org/obo/NBO_0000368',
                    'sedentary' => 'http://purl.obolibrary.org/obo/IDOMAL_0002084, http://eol.org/schema/terms/nonmigratory',
                    'sessile' => 'http://www.wikidata.org/entity/Q33596, http://www.wikidata.org/entity/Q1759860',
                    'social' => 'http://eol.org/schema/terms/SocialSystem,http://eol.org/schema/terms/socialGroupLiving',
                    'solitary' => 'http://eol.org/schema/terms/SocialSystem,http://eol.org/schema/terms/solitary',
                    'terricolous' => 'http://purl.obolibrary.org/obo/RO_0002303,http://eol.org/schema/terms/groundDwelling',
                    'territorial' => 'DISCARD',
                    'troglophilic' => 'http://purl.obolibrary.org/obo/RO_0002303,http://purl.obolibrary.org/obo/ENVO_00000067'
                )
        );
    $this->values['Food Habits'] = Array(
            'Primary Diet' => Array(
                    'Carnivore' => 'http://www.wikidata.org/entity/Q1053008,https://www.wikidata.org/entity/Q81875',
                    'Carnivore :: Eats body fluids' => 'DISCARD',
                    'Carnivore :: Eats eggs' => 'http://eol.org/schema/terms/TrophicGuild,https://www.wikidata.org/entity/Q60743215',
                    'Carnivore :: Eats non-insect arthropods' => 'http://eol.org/schema/terms/TrophicGuild,http://eol.org/schema/terms/invertivore',
                    'Carnivore :: Eats other marine invertebrates' => 'http://eol.org/schema/terms/TrophicGuild,http://eol.org/schema/terms/invertivore',
                    'Carnivore :: Eats terrestrial vertebrates' => 'http://eol.org/schema/terms/TrophicGuild,http://eol.org/schema/terms/vertivore',
                    'Carnivore :: Insectivore' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q677088',
                    'Carnivore :: Molluscivore' => 'http://eol.org/schema/terms/TrophicGuild,https://www.wikidata.org/entity/Q3319613',
                    'Carnivore :: Piscivore' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q1420208',
                    'Carnivore :: Sanguivore' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q939099',
                    'Carnivore :: Scavenger' => 'http://eol.org/schema/terms/TrophicGuild,http://eol.org/schema/terms/carnivorous_scavenger',
                    'Carnivore :: Vermivore' => 'http://eol.org/schema/terms/TrophicGuild,http://eol.org/schema/terms/invertivore',
                    'Coprophage' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q320011',
                    'Detritivore' => 'http://www.wikidata.org/entity/Q1053008,http://wikidata.org/entity/Q2750657',
                    'Herbivore' => 'http://www.wikidata.org/entity/Q1053008,https://www.wikidata.org/entity/Q59099',
                    'Herbivore :: Algivore' => 'http://eol.org/schema/terms/TrophicGuild,https://www.wikidata.org/entity/Q7486201',
                    'Herbivore :: Eats sap or other plant foods' => 'http://eol.org/schema/terms/TrophicGuild',
                    'Herbivore :: Folivore' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q617573',
                    'Herbivore :: Frugivore' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q1470764',
                    'Herbivore :: Granivore' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q1974986',
                    'Herbivore :: Lignivore' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q45879481',
                    'Herbivore :: Nectarivore' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q120880',
                    'Mycophage' => 'http://eol.org/schema/terms/TrophicGuild,https://www.wikidata.org/entity/Q3331325',
                    'Omnivore' => 'http://www.wikidata.org/entity/Q1053008,http://www.wikidata.org/entity/Q164509',
                    'Planktivore' => 'http://eol.org/schema/terms/TrophicGuild,http://wikidata.org/entity/Q7201320'
                ),
            'Plant Foods' => Array(
                    'Algae' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q37868',
                    'Bryophytes' => 'http://eol.org/schema/terms/Diet,http://www.wikidata.org/entity/Q29993',
                    'Flowers' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/PO_0009046',
                    'Fruit' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/PO_0009001',
                    'Leaves' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/PO_0025034',
                    'Lichens' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q43142',
                    'Macroalgae' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/OMIT_0013523',
                    'Nectar' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/BTO_0000537',
                    'Phytoplankton' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q184755',
                    'Pollen' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/PO_0025281',
                    'Roots and tubers' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/PO_0009005',
                    'Sap or other plant fluids' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/PO_0025538',
                    'Seeds, grains, and nuts' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/PO_0009010',
                    'Wood, bark, or stems' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/ENVO_00002040'
                ),
            'Animal Foods' => Array(
                    'Amphibians' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q10908',
                    'Aquatic Crustaceans' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q25364',
                    'Aquatic or Marine Worms' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q47253',
                    'Birds' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q5113',
                    'Blood' => 'http://eol.org/schema/terms/Diet,http://purl.obolibrary.org/obo/NCIT_C12434',
                    'Body fluids' => 'DISCARD',
                    'Carrion' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q202994',
                    'Cnidarians' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q25441',
                    'Echinoderms' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q44631',
                    'Eggs' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q17147',
                    'Fish' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q152',
                    'Insects' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q1390',
                    'Mammals' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q7377',
                    'Mollusks' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q25326',
                    'Other Marine Invertebrates' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q3737872',
                    'Reptiles' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q10811',
                    'Terrestrial Non-insect Arthropods' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q1360',
                    'Terrestrial Worms' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q47253',
                    'Zooplankton' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q842627'
                ),
            'Other Foods' => Array(
                    'Detritus' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q736879',
                    'Dung' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q496',
                    'Fungus' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q764',
                    'Microbes' => 'http://eol.org/schema/terms/Diet,https://www.wikidata.org/entity/Q39833'
                ),
            'Foraging Behavior' => Array(
                    'Filter-feeding' => 'http://eol.org/schema/terms/TrophicGuild,http://www.wikidata.org/entity/Q1252491',
                    'Stores or caches food' => 'DISCARD'
                )
        );
        
        /* NOT NEEDED ANYMORE...
           under $data = [Reproduction: Parental Investment]
             $subtopic = [Parental Investment]
        $this->parent_child['https://www.wikidata.org/entity/Q2251595'] = array('Pre-fertilization :: Protecting', 'Pre-hatching/birth :: Protecting', 
                        'Pre-independence :: Protecting', 'Pre-weaning/fledging :: Protecting');
        $this->parent_child['https://www.wikidata.org/entity/Q2874419'] = array('Pre-fertilization :: Provisioning', 'Pre-hatching/birth :: Provisioning', 
                        'Pre-independence :: Provisioning', 'Pre-weaning/fledging :: Provisioning');
        $this->parent_child['http://eol.org/schema/terms/paternalCare'] = array('Male parental care', 'Pre-fertilization :: Protecting :: Male', 
                        'Pre-hatching/birth :: Protecting :: Male', 'Pre-hatching/birth :: Provisioning :: Male', 'Pre-independence :: Protecting :: Male', 
                        'Pre-independence :: Provisioning :: Male', 'Pre-weaning/fledging :: Protecting :: Male', 'Pre-weaning/fledging :: Provisioning :: Male');
        $this->parent_child['http://eol.org/schema/terms/parentalCareFemale'] = array('Female parental care', 'Pre-fertilization :: Protecting :: Female', 
                        'Pre-hatching/birth :: Protecting :: Female', 'Pre-hatching/birth :: Provisioning :: Female', 'Pre-independence :: Protecting :: Female', 
                        'Pre-independence :: Provisioning :: Female', 'Pre-weaning/fledging :: Protecting :: Female', 'Pre-weaning/fledging :: Provisioning :: Female');
        */
    }
    private function comma_separated_value($str)
    {   //e.g. [Eusocial] => http://eol.org/schema/terms/SocialSystem,https://www.wikidata.org/entity/Q753694
        $arr = explode(',', $str);
        $arr = array_map('trim', $arr);
        if(count($arr) == 1) return false;
        else return array('mType' => $arr[0], 'mValue' => $arr[1]);
    }
    private function group_pipe_separated_items_if_needed($str, $data, $subtopic, $mType)
    {   /*Array(
            [Species] => Abrocoma boliviensis
            [Class] => Mammalia
            [Order] => Rodentia
            [Family] => Abrocomidae
            $str = [Parental Investment] => Pre-fertilization | Pre-fertilization :: Provisioning | Pre-fertilization :: Protecting | 
                Pre-fertilization :: Protecting :: Female | Pre-hatching/birth | Pre-hatching/birth :: Provisioning | 
                Pre-hatching/birth :: Provisioning :: Female | Pre-hatching/birth :: Protecting | Pre-hatching/birth :: Protecting :: Female | 
                Pre-weaning/fledging | Pre-weaning/fledging :: Provisioning | Pre-weaning/fledging :: Provisioning :: Female
        )*/
        $final = array();
        // echo("\n[$str]\n");
        $arr = explode(' | ', $str);
        $arr = array_map('trim', $arr);
        foreach($arr as $string) {

            if($val = @$this->values[$data]['measurementType']) $mType = $val;
            if($val = @$this->values[$data][$subtopic]['measurementType']) $mType = $val;

            /*Caveats:
            For the [Biogeographic Regions] section, the measurementType should be http://eol.org/schema/terms/Present, 
            unless the value has Native or Introduced appended. If those are present, they should change the measurementType:
            Native => http://eol.org/schema/terms/NativeRange
            Introduced => http://eol.org/schema/terms/IntroducedRange*/
            if($subtopic == 'Biogeographic Regions') {
                $mType = 'http://eol.org/schema/terms/Present';
                if(stripos($string, "Native") !== false)         $mType = 'http://eol.org/schema/terms/NativeRange'; //string is found
                elseif(stripos($string, "Introduced") !== false) $mType = 'http://eol.org/schema/terms/IntroducedRange'; //string is found
                else                                             $mType = 'http://eol.org/schema/terms/Present';
                
                /*[Oceanic Islands] is an exception. This is a habitat, more than a distribution, 
                so it should have measurementType=http://purl.obolibrary.org/obo/RO_0002303, 
                regardless of whether "Native" or with nothing appended. If "Introduced" is present for this one, please discard the record.*/
                if(stripos($string, "Oceanic Islands") !== false) { //string is found
                    $mType = 'http://purl.obolibrary.org/obo/RO_0002303';
                    if(stripos($string, "Introduced") !== false) continue; //string is found
                }
            }

            /*For the [Other Physical Features] section, the measurementType specified works for the values we're using so far. 
            If we eventually start using some of the others, we'll probably assign them different ones. Don't say I didn't warn you */
            if($subtopic == 'Other Physical Features') {}
            elseif($subtopic == 'Sexual Dimorphism') $mType = 'http://www.owl-ontologies.com/unnamed.owl#Dimorphism';
            
            if($mValue = @$this->values[$data][$subtopic][$string]) {
                if($mValue == 'DISCARD') continue;
                
                /* e.g. [Eusocial] => http://eol.org/schema/terms/SocialSystem,https://www.wikidata.org/entity/Q753694
                then mType is http://eol.org/schema/terms/SocialSystem
                and mValue is https://www.wikidata.org/entity/Q753694
                */
                if($ret = self::comma_separated_value($mValue)) {
                    $mType = $ret['mType'];
                    $mValue = $ret['mValue'];
                }
                
                if(!$mType) exit("\nNo mType yet: [$data] [$subtopic] [$string]\n");
                
                if(in_array($subtopic, array('Parental Investment')) || 
                   in_array($data, array('Habitat', 'Food Habits'))) $final[$mValue]['terms'][] = $string;
                else                                                 $final[$mValue]['terms'] = array($string);
                $final[$mValue]['mType'] = $mType;
                
                // $save = array();
                // $save['taxon_id'] = $rek['taxonID'];
                // $save["catnum"] = $rek['taxonID'].'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                // $save['source'] = $rek['furtherInformationURL'];
                // // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; exit; //just testing
                // $this->func->pre_add_string_types($save, $mValue, $mType, "true");
                
            }
            // else exit("\nShould not go here, not yet initialized. [$data] [$subtopic] [$string]\n"); //acceptable, just ignore it.
        } //end foreach()
        
        /*Array( 'Parental Investment' sample
            [https://www.wikidata.org/entity/Q2874419] => Array(
                    [0] => Pre-fertilization :: Provisioning
                    [1] => Pre-hatching/birth :: Provisioning
                    [2] => Pre-weaning/fledging :: Provisioning)
            [https://www.wikidata.org/entity/Q2251595] => Array(
                    [0] => Pre-fertilization :: Protecting
                    [1] => Pre-hatching/birth :: Protecting)
            [http://eol.org/schema/terms/parentalCareFemale] => Array(
                    [0] => Pre-fertilization :: Protecting :: Female
                    [1] => Pre-hatching/birth :: Provisioning :: Female
                    [2] => Pre-hatching/birth :: Protecting :: Female
                    [3] => Pre-weaning/fledging :: Provisioning :: Female)
        )
        Array( 'Habitat' sample
            [http://purl.obolibrary.org/obo/ENVO_01000204] => Array(
                    [0] => Tropical
                )
            [http://purl.obolibrary.org/obo/ENVO_00000446] => Array(
                    [0] => Terrestrial
                )
        )*/
        
        // if(!$final) exit("\n[$data] [$subtopic] [$str] qwerty\n"); //debug only
        
        $ret = array('mType' => $mType, 'final' => $final);
        // print_r($ret); //exit("\n-111-\n");
        return $ret;
    }
    private function main_proc_images($rek)
    {   //print_r($rek); exit;
        /*Array(
            [Species] => Abaeis nicippe
            [Class] => Insecta
            [Order] => Lepidoptera
            [Family] => Pieridae
            [Live Animal :: Live Animal] => https://animaldiversity.org/collections/contributors/melody_lytle/abaeis_nicippe/medium.jpg|
                                            https://animaldiversity.org/collections/contributors/phil_myers/lepidoptera/Pieridae/Abaeis0791/medium.jpg|
                                            https://animaldiversity.org/collections/contributors/phil_myers/lepidoptera/Pieridae/Abaeis9231/medium.jpg|
                                            https://animaldiversity.org/collections/contributors/phil_myers/lepidoptera/Pieridae/butterfly0935/medium.jpg|
                                            https://animaldiversity.org/collections/contributors/phil_myers/lepidoptera/Pieridae/butterfly1088/medium.jpg|
                                            https://animaldiversity.org/collections/contributors/phil_myers/lepidoptera/Pieridae/Eurema7287/medium.jpg
            [taxonID] => Abaeis_nicippe
            [furtherInformationURL] => https://animaldiversity.org/accounts/Abaeis_nicippe/
        )*/
        
        if($val = @$rek['Live Animal :: Live Animal']) {}
        elseif($val = @$rek['Behaviors :: Behaviors']) {}
        elseif($val = @$rek['Habitat :: Habitat']) {}
        elseif($val = @$rek['Anatomy :: Anatomy']) {}
        elseif($val = @$rek['Life Stages and Gender :: Life Stages and Gender']) {}
        elseif($val = @$rek['Specimen: Foot :: Foot']) {}
        elseif($val = @$rek['Specimen: Forefoot :: Forefoot']) {}
        elseif($val = @$rek['Specimen: Forelimb :: Forelimb']) {}
        elseif($val = @$rek['Specimen: Hindfoot :: Hindfoot']) {}
        elseif($val = @$rek['Specimen: Lower Jaw :: Lower Jaw']) {}
        elseif($val = @$rek['Specimen: Teeth :: Teeth']) {}
        else exit("\nNot yet initialized.\n");
        $arr = explode("|", $val);
        
        // print_r($arr); exit("\n---\n");
        foreach($arr as $url) {
            $pathinfo = pathinfo($url);
            // print_r($pathinfo); exit;
            /*Array(
                [dirname] => https://animaldiversity.org/collections/contributors/melody_lytle/abaeis_nicippe
                [basename] => medium.jpg
                [extension] => jpg
                [filename] => medium
            )*/
            $img_rec = self::parse_image_summary($pathinfo['dirname']);
            $img_rec['taxonID'] = $rek['taxonID'];
            $img_rec['source'] = $pathinfo['dirname'];
            $img_rec['mimeType'] = Functions::get_mimetype($pathinfo['basename']);
            $img_rec['dataType'] = Functions::get_datatype_given_mimetype($img_rec['mimeType']);
            // print_r($img_rec); exit("\nsample record image\n");
            /*Array(
                [Caption] => Sleepy orange (Abaeis nicippe)
                [Agent long] => Melody Lytle (photographer; copyright holder; identification)
                [agent role] => photographer
                [Agent short] => Melody Lytle
                [license] => by-nc-sa
                [license long] => This work is licensed under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported License.
                [taxonID] => Abaeis_nicippe
                [source] => https://animaldiversity.org/collections/contributors/melody_lytle/abaeis_nicippe
                [mimeType] => image/jpeg
                [dataType] => http://purl.org/dc/dcmitype/StillImage
            )*/
            if(in_array(@$img_rec['license'], $this->accepted_licenses)) self::write_media_objects($img_rec);
        }
        // exit("\nstop munax\n");
    }
    private function parse_image_summary($url)
    {
        // $url = 'https://animaldiversity.org/collections/contributors/farhang_torki/Apannonicus3'; //debug only - forced value
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*365; //expires in a year
        if($html = Functions::lookup_with_cache($url, $options)) { // echo "\n$url\n"; exit("\n$html\n");
            $img = array();
            if(preg_match("/<h3>Date Taken<\/h3>(.*?)<\/p>/ims", $html, $a)) $img['Date Taken'] = strip_tags(trim($a[1]));
            if(preg_match("/<h3>Caption<\/h3>(.*?)<\/p>/ims", $html, $a)) $img['Caption'] = strip_tags(trim($a[1]));
            if(preg_match("/<h3>Location<\/h3>(.*?)<\/p>/ims", $html, $a)) $img['Location'] = strip_tags(trim($a[1]));
            /*<h3>Contributors</h3>
            <div class="block">
            <p><a href="http://www.karenmelody.com/" class="external-link">Melody Lytle</a> (photographer; copyright holder; identification)</p>
            </div>*/
            if(preg_match("/<h3>Contributors<\/h3>(.*?)<\/div>/ims", $html, $a)) {
                $img['Agent long'] = trim(strip_tags(trim($a[1])));
                if(stripos($img['Agent long'], "photographer") !== false) $img['agent role'] = 'photographer'; //string is found
                $img['Agent short'] = trim(preg_replace('/\s*\([^)]*\)/', '', $img['Agent long'])); //remove parenthesis
                
                if(stripos($img['Agent long'], "copyright holder") !== false) $img['copyright holder'] = $img['Agent short']; //string is found
                
                
            }
            if(preg_match("/<h3>Conditions of Use<\/h3>(.*?)<\/p>/ims", $html, $a)) {
                // http://creativecommons.org/licenses/by-nc-sa/3.0/
                if(preg_match("/\:\/\/creativecommons\.org\/licenses\/(.*?)\//ims", $a[1], $a2)) $img['license'] = $a2[1];
                $img['license long'] = trim(strip_tags(trim($a[1])));
            }
            
            /*<h3>Caption</h3>
              <p>sleepy orange</p>
              <ul class="keywords">
                <li class="keywords-header">Subject</li>
                <li>
                  <span>Live Animal</span>
                </li>
              </ul>
              <ul class="keywords">
                <li class="keywords-header">Type</li>
                <li>
                  <span>Photo</span>
                </li>
              </ul>
              <ul class="keywords last">
                <li class="keywords-header">Life Stages And Gender</li>
                <li>
                  <span>Adult/Sexually Mature</span>
                </li>
              </ul>
              <h3>Contributors</h3>*/
            if(preg_match("/<h3>Caption<\/h3>(.*?)<h3>/ims", $html, $a) ||
               preg_match("/<h3>Location<\/h3>(.*?)<h3>/ims", $html, $a) ||
               preg_match("/<h3>Identification<\/h3>(.*?)<h3>/ims", $html, $a)
            ) {
                if(preg_match_all("/<ul class=(.*?)<\/ul>/ims", $a[1], $a2)) {
                    // print_r($a2[1]); exit;
                    $arr = array();
                    foreach($a2[1] as $tmp) {
                        $tmp = strip_tags("<ul".$tmp, "<span>");
                        $tmp = str_replace("\n",'',$tmp);
                        $tmp = Functions::remove_whitespace(trim($tmp));
                        $tmp = strip_tags(str_replace(' <span>', ": ", $tmp));
                        $arr[] = $tmp;
                    }
                    // print_r($arr); exit;
                    $img['description'] = implode(" | ", $arr);
                }
            }
            // print_r($img); exit;
            return $img;
        }
        else $this->debug['url down'][$url] = '';
            
            /*
            <h3>Date Taken</h3>
          <p>7 September 2006</p>
          <h3>Location</h3>
          <p>Kalamani Nature Reserve, Xinjiang Province, China</p>
          <h3>Caption</h3>
          <p>Kulans, or Asiatic wild asses (Equus hemionus).</p>
          <ul class="keywords">
            <li class="keywords-header">Subject</li>
            <li>
              <span>Live Animal</span>
            </li>
          </ul>
          <ul class="keywords">
            <li class="keywords-header">Type</li>
            <li>
              <span>Photo</span>
            </li>
          </ul>
          <ul class="keywords">
            <li class="keywords-header">Life Stages And Gender</li>
            <li>
              <span>Adult/Sexually Mature</span>
            </li>
          </ul>
          <ul class="keywords last">
            <li class="keywords-header">Subject</li>
            <li>
              <span>Habitat</span>
            </li>
          </ul>
          <h3>Contributors</h3>
          <div class="block">
            <p>David Blank (photographer; copyright holder; identification)</p>
          </div>
          <div>
            <h3>Conditions of Use</h3>
            <p><a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/"><img alt="Creative Commons License" style="border-width:0" src="http://i.creativecommons.org/l/by-nc-sa/3.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by-nc-sa/3.0/">Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported License</a>.
                </p>            
            */
    }
    private function write_media_objects($o)
    {   /*Array(
            [Caption] => Sleepy orange (Abaeis nicippe)
            [Agent long] => Melody Lytle (photographer; copyright holder; identification)
            [agent role] => photographer
            [Agent short] => Melody Lytle
            [license] => by-nc-sa
            [license long] => This work is licensed under a Creative Commons Attribution-Noncommercial-Share Alike 3.0 Unported License.
            [taxonID] => Abaeis_nicippe
            [source] => https://animaldiversity.org/collections/contributors/melody_lytle/abaeis_nicippe
            [mimeType] => image/jpeg
            [dataType] => http://purl.org/dc/dcmitype/StillImage
        )*/
        
        // if(!@$o['Caption']) print_r($o);
        if(!@$o['description']) {
            print_r($o); echo " - no desc...";
        }
        // if(!@$o['agent role']) print_r($o);

        
        
        $o['identifier'] = md5($o['source']);
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $o['taxonID'];
        $mr->identifier     = $o['identifier'];
        $mr->type           = $o['dataType'];
        $mr->language       = 'en';
        $mr->format         = $o['mimeType'];
        $mr->furtherInformationURL = $o['source'];
        
        if($mr->accessURI = self::get_access_uri($o['source'])) {}
        else return; //can't access image
        // exit("\n[$mr->accessURI]\n");
        
        // $mr->CVterm         = '';
        // $mr->rights         = '';
        
        if($val = @$o['copyright holder']) $mr->Owner = $val;
        else                               $mr->Owner = $o['Agent long'];
        
        $mr->title          = @$o['Caption'];
        $mr->UsageTerms     = $this->license_lookup[$o['license']];
        // $mr->audience       = 'Everyone';
        $mr->description    = $o['description'];
        $mr->CreateDate     = @$o['Date taken'];
        $mr->LocationCreated     = @$o['Location'];
        
        // $mr->bibliographicCitation = '';
        // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids); //copied template
        if($agent_ids = self::create_agent($o))  $mr->agentID = implode("; ", $agent_ids);
        
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function get_access_uri($source)
    {
        $filenames = array('large.jpg', 'medium.jpg');
        foreach($filenames as $filename) {
            $remoteFile = $source."/".$filename;
            // Open file
            $handle = @fopen($remoteFile, 'r');
            // Check if file exists
            if($handle) return $remoteFile;
        }
        return false;
    }
    private function create_agent($o)
    {   /*Array(
            [Agent long] => Melody Lytle (photographer; copyright holder; identification)
            [agent role] => photographer
            [Agent short] => Melody Lytle
        */
        $name = false;
        if($name = $o['Agent short']) {}
        elseif($name = $o['Agent long']) {}
        else exit("\nInvestigate no agent...\n");

        $agent_ids = array();
        $r = new \eol_schema\Agent();
        $r->term_name       = $name;
        $r->agentRole       = (@$o['agent role']) ? $o['agent role'] : "creator";
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        // $r->term_homepage   = '';
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
}
?>