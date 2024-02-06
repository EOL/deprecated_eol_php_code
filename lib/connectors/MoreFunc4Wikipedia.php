<?php
namespace php_active_record;
/**/
class MoreFunc4Wikipedia
{
    function __construct($six_coverage = '1st')
    {
        echo "\n-----six_coverage is [$six_coverage]-----\n";
        if($six_coverage == '1st')      $this->all_wikipedias_tsv = DOC_ROOT. "update_resources/connectors/all_wikipedias_main.tsv";
        elseif($six_coverage == '2nd')  $this->all_wikipedias_tsv = DOC_ROOT. "update_resources/connectors/all_wikipedias_main_2ndpart.tsv";
        else                            $this->all_wikipedias_tsv = DOC_ROOT. "update_resources/connectors/all_wikipedias_main.tsv";
    }
    function is_this_wikipedia_lang_old_YN($lang)
    {
        $lang_date = self::get_date_of_this_wikipedia_lang($lang);
        echo "\ndate of $lang: $lang_date\n";
        // get date today minus 2 months
        $date = date("Y-m-d");
        $today = date_create($date);
        echo "\n-------new...\ntoday: ".date_format($today, 'Y-m-d')."\n";
        date_sub($today, date_interval_create_from_date_string('2 month')); //previously '2 months'
        $minus_2_months = date_format($today, 'Y-m-d');
        // compare
        echo "minus 1 month: " .$minus_2_months. "\n";
        echo "\n$lang_date < $minus_2_months \n";
        if($lang_date < $minus_2_months) return true;
        else return false;
    }
    private function get_date_of_this_wikipedia_lang($lang)
    {
        // /* NEW:
            if($lang == "en") $file = CONTENT_RESOURCE_LOCAL_PATH.'80.tar.gz';
        elseif($lang == "xx") $file = CONTENT_RESOURCE_LOCAL_PATH.'xx.tar.gz';
        else { //rest goes here
            $file = CONTENT_RESOURCE_LOCAL_PATH.'wikipedia-'.$lang.'.tar.gz';
        }
        if(file_exists($file)) return date("Y-m-d", filemtime($file));
        else                   return date("Y-m-d", false);
        // */
        /* OLD:
        $file = CONTENT_RESOURCE_LOCAL_PATH.'wikipedia-'.$lang.'.tar.gz';
        if(file_exists($file)) return date("Y-m-d", filemtime($file));
        else                   return date("Y-m-d", false);
        */
    }
    function get_language_info_from_TSV($needle)
    {
        $tsv = $this->all_wikipedias_tsv;
        $txt = file_get_contents($tsv);
        $rows = explode("\n", $txt);
        $final = array();
        foreach($rows as $row) {
            $arr = explode("\t", $row);
            $arr = array_map('trim', $arr); // print_r($arr);
            $lang = $arr[0]; $status = @$arr[1]; $six_conn = @$arr[2];
            if($needle == $lang) return $arr;
        }
        return false;
    }
    function get_next_lang_after($needle)
    {   // echo "\n". DOC_ROOT;
        // /opt/homebrew/var/www/eol_php_code/
        $tsv = $this->all_wikipedias_tsv;
        $txt = file_get_contents($tsv);
        $rows = explode("\n", $txt);
        /* step1: get all valid langs to process */
        $final = array();
        foreach($rows as $row) {
            $arr = explode("\t", $row);
            $arr = array_map('trim', $arr); // print_r($arr);
            $lang = $arr[0]; $status = @$arr[1];
            // if($lang == "-----") continue;
            // if($status == "N") continue;
            $final[] = $arr;
        } // print_r($final);
        /* step2: loop and search for needle in $final, get $i */
        $i = -1;
        $needle_found = false;
        foreach($final as $arr) { $i++; // print_r($rek); exit;
            /*Array(
                [0] => pl
                [1] => Y
            )*/
            $lang = $arr[0]; $status = $arr[1];
            if($needle == $lang) {
                $needle_found = true;
                break;
            }
        }
        if($needle_found) echo "\nneedle found OK [$needle]\n";
        else              exit("\nERROR: needle was not found [$needle] [$this->all_wikipedias_tsv]\n");
        /* step3: start with $i, then get the next valid lang */
        $start = $i+1; // echo "\nstart at: [$start]\n";
        $i = -1;
        foreach($final as $arr) { $i++; // print_r($rek); exit;
            /*Array(
                [0] => pl
                [1] => Y
                [2] => 6c
            )*/
            if($i >= $start) {
                $lang = $arr[0]; $status = @$arr[1]; $six_conn = @$arr[2];
                if($status == "Y" && $six_conn == "6c") {
                    if(self::is_this_wikipedia_lang_old_YN($lang)) return array($lang, $six_conn);
                }
            }
        }
        return false;
    }
    function get_all_6_connectors()
    {   $final = array();
        $tsv = $this->all_wikipedias_tsv;
        $txt = file_get_contents($tsv);
        $rows = explode("\n", $txt);
        /* step1: get all valid langs to process */
        $final = array();
        foreach($rows as $row) {
            $arr = explode("\t", $row);
            $arr = array_map('trim', $arr);
            // print_r($arr);
            $lang = $arr[0]; $status = @$arr[1]; $six_conn = @$arr[2];
            if($six_conn == '6c') $final[] = $lang;
        }
        return $final;
    }
}
?>