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
}
?>