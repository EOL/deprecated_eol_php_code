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
        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 1000000, 'timeout' => 172800, 'download_attempts' => 2, 'delay_in_minutes' => 1);
        $this->download_options["expire_seconds"] = false; //60*60*24*25;
        $this->debug = array();
        $this->url['Habitat'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E268FDE-F3B2-0001-913C-B28812191D82/?start=';
        $this->url['Geographic Range'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E269055-F07D-0001-79AC-D4E055D018F4/?start=';
        $this->url['Physical Description'] = 'https://animaldiversity.ummz.umich.edu/quaardvark/search/1E26905C-DEFA-0001-C0BD-C94B291C77C0/?start=';

        $this->field_count['Habitat'] = 9;
        $this->field_count['Geographic Range'] = 0;
        $this->field_count['Physical Description'] = 18;


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
        if($total_pages = self::get_total_number_of_pages()) {
            $loops = ceil($total_pages/200);
            echo "\n total_pages: [$total_pages]\n loops: [$loops]\n";
            $sum = 1;
            for ($i = 1; $i <= $loops; $i++) {
                echo "\n$i. $sum";
                
                $url = $this->url['Habitat'].$sum;
                $url = $this->url['Geographic Range'].$sum;
                $data = 'Geographic Range';
                $data = 'Physical Description';
                $url = $this->url[$data].$sum;
                
                
                if($html = Functions::lookup_with_cache($url, $this->download_options)) { //hits="6369"
                    $recs = self::parse_page($html, $data);
                }
                $sum = $sum + 200;
                if($i >= 2) break; //debug only
            }
        }
        print_r($this->debug);
        exit("\n-end-\n");
        
    }
    private function parse_page($html, $data)
    {
        $left = '<table xmlns:media="urn:animaldiversity.org:templates:media"';
        if(preg_match("/".preg_quote($left, '/')."(.*?)<\/table>/ims", $html, $a)) {
            $main_block = $a[1];

            $fields = array();
            if(preg_match_all("/<th>(.*?)<\/th>/ims", $main_block, $a1)) $fields = $a1[1];
            print_r($fields);
            if(count($fields) != $this->field_count[$data]) exit("\nInvestigate fields <th> tags: ".count($fields)."\n");
            
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
                            
                            // $col = str_replace("<span/>", "<span></span>", $col);
                            // $col = str_replace("<td/>", "<td></td>", $col);
                            
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
                        print_r($rek); //exit("\nsample record\n"); //good debug
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
                        
                        self::for_stats($rek, $data);
                        
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
        //[Other Physical Features] => Endothermic | Homoiothermic | Bilateral symmetry
        $physical_desc = array('Other Physical Features', 'Sexual Dimorphism');
        $pipe_separated = array_merge($habitat, $physical_desc); // print_r($pipe_separated); exit;
        foreach($pipe_separated as $topic) {
            if($str = $rek[$topic]) {
                $arr = explode(' | ', $str);
                foreach($arr as $value) $this->debug[$data][$topic][$value] = '';
            }
        }
    }
    private function get_total_number_of_pages()
    {
        $url = $this->url['Habitat']."1";
        if($html = Functions::lookup_with_cache($url, $this->download_options)) { //hits="6369"
            if(preg_match("/hits=\"(.*?)\"/ims", $html, $a)) return $a[1];
            else exit("\nNo hits, investigate URL: [$url]\n");
        }
    }
}
?>