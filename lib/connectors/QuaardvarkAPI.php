<?php
namespace php_active_record;
/* connector:
*/
class QuaardvarkAPI
{
    function __construct($folder = null)
    {
        /*
        if($folder) {
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        */
        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 3000000, 'timeout' => 172800, 'download_attempts' => 2, 'delay_in_minutes' => 1);
        $this->download_options["expire_seconds"] = false; //60*60*24*25;
        $this->debug = array();
        $this->url['Habitat'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FDE-F3B2-0001-913C-B28812191D82/?start=';
        $this->url['Geographic Range'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E269055-F07D-0001-79AC-D4E055D018F4/?start=';
        $this->url['Physical Description'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E26905C-DEFA-0001-C0BD-C94B291C77C0/?start=';
        $this->url['Development'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E269098-5CC7-0001-2DE7-C83810947540/?start=';
        $this->url['Reproduction: Mating Systems'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690A5-331A-0001-C982-113D71801250/?start=';
        $this->url['Reproduction: General Behavior'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E26909A-D938-0001-B067-96FC19F012D8/?start=';
        $this->url['Reproduction: Parental Investment'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690A7-C348-0001-C87C-1A92B3001DBB/?start=';
        $this->url['Lifespan/Longevity'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690EB-D94C-0001-9425-1FC0D300FD80/?start=';
        $this->url['Behavior'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690EC-4A91-0001-4ECD-1683ECF087C0/?start=';
        $this->url['Communication and Perception'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690ED-B734-0001-3854-537018B0183F/?start=';
        $this->url['Food Habits'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E2690EF-01A6-0001-A675-17101E2016DF/?start=';

         
        $this->field_count['Habitat'] = 9;
        $this->field_count['Geographic Range'] = 6;
        $this->field_count['Physical Description'] = 18; //it varies - due to ranges that sometimes disappears if without value
        $this->field_count['Development'] = 5;
        $this->field_count['Reproduction: Mating Systems'] = 5;
        $this->field_count['Reproduction: General Behavior'] = 25; //it varies
        $this->field_count['Reproduction: Parental Investment'] = 5;
        $this->field_count['Lifespan/Longevity'] = 14;
        $this->field_count['Behavior'] = 8;
        $this->field_count['Communication and Perception'] = 7;
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
    }
    public function start()
    {
        $topics = array('Habitat', 'Geographic Range', 'Physical Description', 'Development', 'Reproduction: General Behavior',
                        'Reproduction: Mating Systems', 'Reproduction: Parental Investment', 'Lifespan/Longevity', 'Behavior',
                        'Communication and Perception', 'Food Habits');
        $topics = array('Food Habits'); //debug only

        foreach($topics as $data) self::main($data);
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
                if($i >= 2) break; //debug only
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
        if(preg_match("/".preg_quote($left, '/')."(.*?)<\/table>/ims", $html, $a)) {
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

            if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $main_block, $a2)) {
                $rows201 = $a2[1];
                foreach($rows201 as $row) {
                    $row = str_replace('<td type="sequence"/>', '<td type="sequence"></td>', $row);
                    $row = str_replace('<td type="text"/>', '<td type="text"></td>', $row);
                    $row = str_replace("<span/>", "<span></span>", $row);
                    $row = str_replace("<td/>", "<td></td>", $row);
                    
                    if(preg_match_all("/<td(.*?)<\/td>/ims", $row, $a3)) {
                        $cols = $a3[1];
                        // print_r($cols); exit; //good debug
                        $ret = array();
                        foreach($cols as $col) {
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
                                print_r($ret); exit;
                            }
                            $i++;
                        }
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
                        
                        self::for_stats($rek, $data); //for stats only
                        fwrite($f, implode("\t", $rek)."\n");
                    }
                }
            }
            fclose($f);
            // exit("\n-end-\n"); //if you want to investigate 1 html or 1 page
        }
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
}
?>