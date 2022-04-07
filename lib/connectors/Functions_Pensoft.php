<?php
namespace php_active_record;
/* */
class Functions_Pensoft
{
    function __construct() {}
    function initialize_new_patterns()
    {   // exit("\n[$this->new_patterns_4textmined_resources]\nelix1\n");
        $str = file_get_contents($this->new_patterns_4textmined_resources);
        $arr = explode("\n", $str);
        $arr = array_map('trim', $arr);
        // $arr = array_filter($arr); //remove null arrays
        // $arr = array_unique($arr); //make unique
        // $arr = array_values($arr); //reindex key
        // print_r($arr); //exit("\n".count($arr)."\n");
        $i = 0;
        foreach($arr as $row) { $i++;
            $cols = explode("\t", $row);
            if($i == 1) {
                $fields = $cols;
                continue;
            }
            else {
                $k = -1;
                foreach($fields as $fld) { $k++;
                    $rec[$fld] = $cols[$k];
                }
            }
            // print_r($rec); exit;
            /*Array(
                [string] => evergreen
                [measurementType] => http://purl.obolibrary.org/obo/FLOPO_0008548
                [measurementValue] => http://purl.obolibrary.org/obo/PATO_0001733
            )*/
            $this->new_patterns[$rec['string']] = array('mType' => $rec['measurementType'], 'mValue' => $rec['measurementValue']);
        }
    }
    function get_allowed_value_type_URIs_from_EOL_terms_file($download_options = false)
    {
        if($download_options) $options = $download_options;         //bec this func is also called from other libs
        else                  $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*1; //1 day expires
        if($yml = Functions::lookup_with_cache("https://raw.githubusercontent.com/EOL/eol_terms/main/resources/terms.yml", $options)) {
            /*  type: value
                uri: http://eol.org/schema/terms/neohapantotype
                parent_uris:        */
            if(preg_match_all("/type\: value(.*?)parent_uris\:/ims", $yml, $a)) {
                $arr = array_map('trim', $a[1]); // print_r($arr); exit;
                foreach($arr as $line) {
                    $uri = str_replace("uri: ", "", $line);
                    $final[$uri] = '';
                }
            }
            else exit("\nInvestigate: EOL terms file structure had changed.\n");
        }
        else exit("\nInvestigate: EOL terms file not accessible.\n");
        return $final;
    }
}
?>