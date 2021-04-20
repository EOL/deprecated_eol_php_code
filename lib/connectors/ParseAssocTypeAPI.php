<?php
namespace php_active_record;
/* */
class ParseAssocTypeAPI
{
    function __construct()
    {
        $this->download_options = array('resource_id' => 'unstructured_text', 'expire_seconds' => 60*60*24, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->prefixes = array("HOSTS", "HOST", "PARASITOIDS", "PARASITOID");
        $this->service['GNParser'] = "https://parser.globalnames.org/api/v1/";
        $this->service['GNRD text input'] = 'http://gnrd.globalnames.org/name_finder.json?text=';
    }
    /*#################################################################################################################################*/
    function parse_associations($html)
    {
        $arr = explode("<br>", $html); // print_r($arr);
        /*[35] => 
          [36] => HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens (Alt.) J.E. Smith also being mined (Braun, 1908a). Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
          [37] => 
          [38] => PARASITOIDS (Table 2).—Braconidae: Apanteles ornigus Weed, Apanteles sp., Pholetesor sp., probably salicifoliella (Mason); Eulophidae: Chrysocharis sp., Cirrospilus cinctithorax (Girault), Cirrospilus sp., Closterocerus tricinctus (Ashmead), Closterocerus sp., near trifasciatus, Horismenus fraternus (Fitch), Pediobius sp., Pnigalio flavipes (Ashmead), Pnigalio tischeriae (Ashmead) (regarded by some as a junior synonym of Pnigalio flavipes), Pnigalio near proximus (Ashmead), Pnigalio sp., Sympiesis conica (Provancher), Sympiesis sp., Tetrastichus sp.; Ichneumonidae: Alophosternum foliicola (Cushman), Diadeg-ma sp., stenosomus complex, Scambus decorus (Whalley); Pteromalidae: Pteromalus sp. (most records from Auerbach (1991), in which a few records may pertain only to Phyllonorycter nipigon).
        */
        $sciname = $arr[0];
        $arr = self::get_relevant_blocks($arr);
        $assoc = self::get_associations($arr);
        // exit("\n[$sciname]\n-end assoc-\n");
        return array('sciname' => $sciname, 'assoc' => $assoc);
    }
    private function get_associations($rows)
    {
        $scinames = array();
        foreach($rows as $prefix => $row) {
            // $row = str_replace(":", ",", $row);
            $row = Functions::conv_to_utf8($row);
            
            $parts = explode(",", $row); //exploded via a comma (","), since GNRD can't detect scinames from block of text sometimes.
            foreach($parts as $part) {
                $obj = self::run_GNRD_assoc($part); // print_r($obj); //exit;
                foreach($obj->names as $name) {
                    $tmp = $name->scientificName;
                    // if(self::is_one_word($tmp)) continue;
                    $scinames[$prefix][$tmp] = '';
                }
            }
        }
        // print_r($scinames);
        return $scinames;
    }
    private function get_relevant_blocks($arr)
    {
        $final = array();
        foreach($arr as $string) {
            foreach($this->prefixes as $prefix) {
                if(substr($string,0,strlen($prefix)+1) == "$prefix ") {
                    $final[$prefix] = $string;
                    continue;
                }
            }
        }
        // print_r($final); exit("\n-eli1-\n");
        /*Array(
            [HOSTS] => HOSTS (Table 1).—In North America, Populus tremuloides Michx., is the most frequently encountered host, with P. grandidentata Michx., and P. canescens (Alt.) J.E. Smith also being mined (Braun, 1908a). Populus balsamifera L., P. deltoides Marsh., and Salix sp. serve as hosts much less frequently. In the Palearctic region, Populus alba L., P. nigra L., P. tremula L., and Salix species have been reported as foodplants.
            [PARASITOIDS] => PARASITOIDS (Table 2).—Braconidae: Apanteles ornigus Weed, Apanteles sp., Pholetesor sp., probably salicifoliella (Mason); Eulophidae: Chrysocharis sp., Cirrospilus cinctithorax (Girault), Cirrospilus sp., Closterocerus tricinctus (Ashmead), Closterocerus sp., near trifasciatus, Horismenus fraternus (Fitch), Pediobius sp., Pnigalio flavipes (Ashmead), Pnigalio tischeriae (Ashmead) (regarded by some as a junior synonym of Pnigalio flavipes), Pnigalio near proximus (Ashmead), Pnigalio sp., Sympiesis conica (Provancher), Sympiesis sp., Tetrastichus sp.; Ichneumonidae: Alophosternum foliicola (Cushman), Diadeg-ma sp., stenosomus complex, Scambus decorus (Whalley); Pteromalidae: Pteromalus sp. (most records from Auerbach (1991), in which a few records may pertain only to Phyllonorycter nipigon).
        )
        */
        return $final;
    }
    private function run_GNRD_assoc($string)
    {
        $string = trim($string);
        $url = $this->service['GNRD text input'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json);
            return $obj;
        }
        return false;
    }
    private function is_one_word($str)
    {
        $arr = explode(" ", $str);
        if(count($arr) == 1) return true;
        return false;
    }
    /*
    private function run_gnparser_assoc($string)
    {
        $string = self::format_string_4gnparser($string);
        $url = $this->service['GNParser'].$string;
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($json = Functions::lookup_with_cache($url, $options)) {
            $obj = json_decode($json); // print_r($obj); //exit;
            return $obj;
        }
    }
    private function format_string_4gnparser($str)
    {
        // %26 - &
        // %2C - ,
        // %28 - (
        // %29 - )
        // %3B - ;
        // + - space

        $str = str_replace(",", "%2C", $str);
        $str = str_replace("(", "%28", $str);
        $str = str_replace(")", "%29", $str);
        $str = str_replace(";", "%3B", $str);
        $str = str_replace(" ", "+", $str);
        $str = str_replace("&", "%26", $str);
        return $str;
    }
    */
}
?>