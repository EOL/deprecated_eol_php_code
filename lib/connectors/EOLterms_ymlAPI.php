<?php
namespace php_active_record;
/* This lib is all about accesing EOL terms in .yml:
https://raw.githubusercontent.com/EOL/eol_terms/main/resources/terms.yml
-> actual yml file
https://github.com/EOL/eol_terms/blob/main/resources/terms.yml
-> Github entry

1st client: [USDAPlants2019.php] -> called by [727.php] -> [USDAPlants.tmproj]
2nd client: [Trait spreadsheet to DwC-A Tool] -> [http://localhost/eol_php_code/applications/trait_data_import/] - DID NOT MATERIALIZE
*/
class EOLterms_ymlAPI
{
    function __construct($archive_builder, $resource_id)
    {
        $this->download_options = array('cache' => 1, 'expire_seconds' => 60*60*24*1, 'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        // $this->download_options['expire_seconds'] = false; //comment after first harvest
        $this->EOL_terms_yml_url = "https://raw.githubusercontent.com/EOL/eol_terms/main/resources/terms.yml";
    }
    function get_terms_yml($sought_type = 'ALL') //possible values: 'measurement', 'value', 'ALL', 'WoRMS value'
    {                                            //output structure: $final[label] = URI;
        $final = array();
        if($yml = Functions::lookup_with_cache($this->EOL_terms_yml_url, $this->download_options)) { //orig 1 day cache
            $yml .= "alias: ";
            if(preg_match_all("/name\:(.*?)alias\:/ims", $yml, $a)) {
                $arr = array_map('trim', $a[1]);
                foreach($arr as $block) { // echo "\n$block\n"; exit;
                    /*
                     [10713] => verbatim coordinates
                    type: measurement
                    uri: http://rs.tdwg.org/dwc/terms/verbatimCoordinates
                    parent_uris:
                    synonym_of_uri: []
                    units_term_uri:
                    */
                    $rek = array();
                    if(preg_match("/elicha(.*?)\n/ims", "elicha".$block, $a)) $rek['name'] = trim($a[1]);
                    if(preg_match("/type\: (.*?)\n/ims", $block, $a)) $rek['type'] = trim($a[1]);
                    if(preg_match("/uri\: (.*?)\n/ims", $block, $a)) $rek['uri'] = trim($a[1]); //https://eol.org/schema/terms/thallus_length
                    $rek = array_map('trim', $rek);
                    // print_r($rek);
                    /*Array(
                        [name] => compound fruit
                        [type] => value
                        [uri] => https://www.wikidata.org/entity/Q747463
                    )*/
                    $name = self::remove_quote_delimiters($rek['name']);
                    if($sought_type == 'ALL')               $final[$name] = $rek['uri'];
                    elseif($sought_type == 'WoRMS value') {
                        if(@$rek['type'] == 'value') $final[$rek['uri']] = $name;
                    }
                    elseif(@$rek['type'] == $sought_type)   $final[$name] = $rek['uri'];
                    @$this->debug['EOL terms type'][@$rek['type']]++; //just for stats
                    /*
                    else {
                        echo "\n-----------------------\n";
                        echo "\n[$block]\n";
                        print_r($rek);
                        exit("\nUndefined sought type: [$sought_type]\n");
                        echo "\n-----------------------\n";
                    }
                    */
                }
            }
            else exit("\nInvestigate: EOL terms file structure had changed.\n");
        }
        else exit("Remote EOL terms (.yml) file not accessible.");
        print_r($this->debug); //just for stats
        return $final;
    } //end get_terms_yml()
    private function remove_quote_delimiters($str)
    {
        // $str = "'123456'"; // $str = '"123456"';
        $str = trim($str); // echo("\norig: [$str]\n");
        $first = substr($str,0,1);
        $last = substr($str, -1); // echo("\n[$first] [$last]\n");
        if($first == "'" && $last == "'") $str = substr($str, 1, strlen($str)-2);
        if($first == '"' && $last == '"') $str = substr($str, 1, strlen($str)-2);
        // exit("\nfinal: [$str]\n");
        return $str;
    }
}
?>