<?php
namespace php_active_record;
/**/
class ContributorsMapAPI
{
    function __construct($resource_id = false)
    {
        $this->resource_id = $resource_id;
        $this->download_options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);
        $this->mappings_url['21_ENV'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/contributor_map/AmphibiaWeb-tab.tsv';
        $this->mappings_url['Polytraits'] = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/contributor_map/Polytraits_contributors.txt';
    }
    function get_contributor_mappings($resource_id = false)
    {
        if(!$resource_id) $resource_id = $this->resource_id;
        if($url = $this->mappings_url[$resource_id]) {}
        else exit("\nUndefined contributor mapping [$resource_id]\n");
        $local = Functions::save_remote_file_to_local($url, $this->download_options);
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
        unlink($local);
        // print_r($final);
        return $final;
    }
}
?>