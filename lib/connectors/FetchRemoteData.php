<?php
namespace php_active_record;
/* A collection of ways to gather remote codes, values, etc. e.g. country codes. */
class FetchRemoteData
{
    function __construct($download_options = false)
    {
        if($val = $download_options) $this->download_options = $val;
        else {
            $this->download_options = array(
                'resource_id'        => 'remote_fetch',
                'expire_seconds'     => 60*60*24, //expires in 1 day
                'download_wait_time' => 500000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
        }
        $this->website['country_codes'] = "https://countrycode.org/";
    }
    public function get_country_codes()
    {
        if($html = Functions::lookup_with_cache($this->website['country_codes'], $this->download_options)) {
            $html = Functions::conv_to_utf8($html);
            // echo "\n$html\n"; exit;
            if(preg_match("/<!--CONTENT TABLE-->(.*?)<\/div>/ims", $html, $arr)) {
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr2)) {
                    // print_r($arr2[1]);
                    foreach($arr2[1] as $rec) {
                        if(preg_match_all("/<td>(.*?)<\/td>/ims", $rec, $arr3)) {
                            // print_r($arr3[1]);
                            $r = $arr3[1];
                            $country = strip_tags($r[0]);
                            $tmp = explode("/", $r[2]);
                            $tmp = array_map('trim', $tmp);
                            $tmp = array_map('strtolower', $tmp);
                            $final[strtoupper($country)] = $tmp;
                        }
                    }
                }
            }
        }
        /* manual addition used for FAOSpeciesAPI.php DATA-1787
        [ZIMBABWE] => Array(
                    [0] => zw
                    [1] => zwe
                )
        */
        $final["ADEN (GULF OF)"][] = "ara";
        $final["ARABIAN PENINSULA"][] = "ara";
        $final["AFRIKAANS"][] = "afr";
        $final["ARABIC"][] = "ara";
        $final["BULGARIAN"][] = "bul";
        $final["BURMESE"][] = "bur";
        $final["CATALAN"][] = "cat";
        $final["CREE"][] = "cre";
        $final["DANISH"][] = "dan";
        $final["ENGLAND"][] = "eng";
        $final["ENGLAND AND USA);"][] = "eng";
        $final["ENGLISH"][] = "eng";
        $final["JAPANESE"][] = "jpn";
        $final["MARMOTAPORTUGAL"][] = "por";
        $final["SPANISH"][] = "spa";
        $final["U.S.A."][] = "eng";
        $final["USA"][] = "eng";
        $final["ZANZIBAR"][] = "swa";
        $final["CZECZUGA .ROMANIA"][] = "rum";
        
        // print_r($final);
        return $final;
    }
}
?>