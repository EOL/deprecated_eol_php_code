<?php
namespace php_active_record;
/* This is called from many places:
- contributor_map.php --> mostly utility
- Environments2EOLfinal.php --> class Environments2EOLfinal extends ContributorsMapAPI
*/
class ContributorsMapAPI
{
    function __construct($resource_id = false)
    {
        $this->resource_id = $resource_id;
        $this->download_options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);
        $this->FishBase_collaborators = 'https://www.fishbase.de/Collaborators/CollaboratorsTopicList.php';
        /*Reminder:
        In SC_unitedstates, please replace the MoF element http://purl.org/dc/terms/contributor with https://www.wikidata.org/entity/Q29514511 
        and the content, `Compiler: Anne E Thessen`, with https://orcid.org/0000-0002-2908-3327
        */
    }
    private function initialize()
    {
        $this->mappings_url['21_ENV'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/contributor_map/AmphibiaWeb-tab.tsv';
        $this->mappings_url['Polytraits'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/contributor_map/Polytraits_contributors.txt';
        $this->mappings_url['42'] = 'https://editors.eol.org/other_files/contributor_mappings/FishBase_contributors.tsv';
        $this->mappings_url['737'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/contributor_map/IUCN_mapping.tsv';
    }
    function get_contributor_mappings($resource_id = false, $download_options = array())
    {
        if(!$download_options) $download_options = $this->download_options;
        // $download_options['expire_seconds'] = 0;
        self::initialize();
        if(!$resource_id) $resource_id = $this->resource_id;
        if($url = $this->mappings_url[$resource_id]) {}
        else exit("\nUndefined contributor mapping [$resource_id]\n");
        $local = Functions::save_remote_file_to_local($url, $download_options);
        $i = 0;
        foreach(new FileIterator($local) as $line_number => $line) {
            $line = explode("\t", $line); $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit;
                /*Array(
                    [Label] => label
                    [URI] => http://eol.org/schema/terms/Amphibiaweb_URI
                )*/
                $final[$rec['Label']] = $rec['URI'];
            }
        }
        unlink($local); // print_r($final);
        return $final;
    }
    function get_collab_name_and_ID_from_FishBase()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //1 month
        $html = Functions::lookup_with_cache($this->FishBase_collaborators, $options);
        /* <a href="/collaborators/CollaboratorSummary.php?id=1713">Aarud, Thomas</a></td> */
        if(Functions::is_production()) $path = "/extra/other_files/contributor_mappings/";
        else                           $path = "/Volumes/AKiTiO4/other_files/contributor_mappings/";
        if(!is_dir($path)) mkdir($path);
        $file = $path.'FishBase_contributors.tsv';
        $handle = fopen($file, "w");
        fwrite($handle, implode("\t", array('Label','URI')) . "\n");
        if(preg_match_all("/CollaboratorSummary.php\?id\=(.*?)<\/a>/ims", $html, $a)) { // print_r($a[1]);
            foreach($a[1] as $tmp) { //2290">Chae, Byung-Soo
                $arr = explode('">', $tmp);
                $id = $arr[0];
                $label = $arr[1];
                $tmp = explode(",", $label);
                $tmp = array_map('trim', $tmp);
                $label = trim(@$tmp[1]." ".$tmp[0]); //to make it "Eli E. Agbayani"
                $label = Functions::remove_whitespace($label);
                $uri = "https://www.fishbase.de/collaborators/CollaboratorSummary.php?id=$id";
                if(!isset($final[$label])) {
                    fwrite($handle, implode("\t", array($label,$uri)) . "\n");
                    $final[$label] = '';
                }
            }
        }
        fclose($handle);
    }
    function get_WoRMS_contributor_id_name_info()
    {
        $final = array();
        $url = "http://www.marinespecies.org/imis.php?module=person&show=search&fulllist=1";
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //a month to expire
        if($html = Functions::lookup_with_cache($url, $options)) {
            /* <a href="imis.php?module=person&persid=19299">Abate, Nega Tassie</a> */
            if(preg_match_all("/module\=person\&persid\=(.*?)<\/a>/ims", $html, $arr)) {
                // print_r($arr[1]); exit;
                /* [961] => 15938">Zeidler, Wolfgang
                   [962] => 31719">Zhan, Aibin */
                foreach($arr[1] as $str) {
                    if(preg_match("/xxx(.*?)\"/ims", 'xxx'.$str, $arr2)) {
                        $persid = trim($arr2[1]);
                        if(preg_match("/\>(.*?)xxx/ims", $str.'xxx', $arr3)) {
                            $name = trim($arr3[1]);
                            $final[$name] = "https://www.marinespecies.org/imis.php?module=person&persid=".$persid;
                            
                            // /* generate another name for some un-matched names used in WoRMS
                            $new_name = self::format_remove_middle_initial($name);
                            $final[$new_name] = "https://www.marinespecies.org/imis.php?module=person&persid=".$persid;
                            // */

                            // /* generate another name: FROM: "de Voogd, Nicole" TO: "Nicole de Voogd"
                            $new_name = self::interchange_firstName_first($new_name);
                            $final[$new_name] = "https://www.marinespecies.org/imis.php?module=person&persid=".$persid;
                            // */
                            
                            // /* generate another name: FROM: "Cuvelier, Daphne" TO: "Daphne Cuvelier"
                            $new_name = self::interchange_firstName_first($name);
                            $final[$new_name] = "https://www.marinespecies.org/imis.php?module=person&persid=".$persid;
                            // */
                            
                        }
                    }
                }
            }
        }
        // print_r($final);
        echo("\nWoRMS contributors A: ".count($final)."\n");
        
        // /* additional contributors, manually looked-up
        $url = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/WoRMS/addtl_contributors.txt';
        $final = Functions::additional_mappings($final, 0, $url); //add a single mapping. 2nd param is expire_seconds
        echo("\nWoRMS contributors B: ".count($final)."\n");
        // */
        
        return $final; //http://www.marinespecies.org/imis.php?module=person&persid=19299
    }
    function format_remove_middle_initial($str) //FROM: "de Voogd, Nicole J." TO: "de Voogd, Nicole"
    {
        $parts = explode(" ", $str);
        $parts = array_map('trim', $parts);
        $last_part = $parts[count($parts)-1]; //print_r($parts); //echo "\nlast_part = [$last_part]\n";
        if(substr($last_part, -1) == "." && strlen($last_part) == 2) { //remove last part (middle initial)
            array_pop($parts);
            return implode(" ", $parts);
        }
        return $str;
    }
    private function interchange_firstName_first($name) //FROM: "Cuvelier, Daphne" TO: "Daphne Cuvelier"
    {                                                   //FROM: "de Voogd, Nicole" TO: "Nicole de Voogd"
        if(substr_count($name, ",") == 1) {
            $arr = explode(" ", $name);
            if(count($arr) == 2) {
                $new_name = $arr[1]." ".$arr[0];
                $new_name = str_replace(",", "", $new_name);
                return $new_name;
            }
            elseif(count($arr) == 3) {
                $new_name = $arr[2]." ".$arr[0]." ".$arr[1];
                $new_name = str_replace(",", "", $new_name);
                return $new_name;
            }
        }
        return $name;
    }
}
?>