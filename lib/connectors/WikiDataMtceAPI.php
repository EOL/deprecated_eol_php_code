<?php
namespace php_active_record;
/* connector: [xxx.php]
---------------------------------------------------------------------------
sample of citations already in WikiData:
https://www.wikidata.org/wiki/Q56079384 ours
https://www.wikidata.org/wiki/Q56079384 someones

*/
class WikiDataMtceAPI
{
    function __construct($folder = null, $query = null)
    {
        $this->download_options = array(
            'resource_id'        => 'wikidata',  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 750000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->anystyle_parse_prog = "ruby ".DOC_ROOT. "update_resources/connectors/helpers/anystyle/run.rb";
        $this->wikidata_api['search title'] = "https://www.wikidata.org/w/api.php?action=wbsearchentities&language=en&format=json&search=MY_TITLE";
        $this->wikidata_api['search entity ID'] = "https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=ENTITY_ID";

        $this->debug = array();
    }

    function create_item_if_does_not_exist($citation)
    {
        $title = self::get_info_from_citation($citation, 'title');
        echo "\n[$title]\n";
        self::does_title_exist_in_wikidata($title);
        echo ("\n-end muna-\n");
    }
    private function does_title_exist_in_wikidata($title)
    {
        $url = str_replace("MY_TITLE", urlencode($title), $this->wikidata_api['search title']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            print("\n$json\n");
            $obj = json_decode($json);
            print_r($obj);
            $wikidata_id = $obj->search[0]->id; # e.g. Q56079384
            echo "\nwikidata_id: [$wikidata_id]\n";
            $DOI = self::get_wikidata_entity_info($wikidata_id, 'DOI');
            echo "\nDOI: [$DOI]\n";

        }
    }
    private function get_wikidata_entity_info($wikidata_id, $what = 'all')
    {
        // https://www.wikidata.org/w/api.php?action=help&modules=wbgetentities
        // https://www.wikidata.org/w/api.php?action=wbgetentities&format=xml&ids=Q56079384
        // searching using wikidata entity id
        $url = str_replace("ENTITY_ID", $wikidata_id, $this->wikidata_api['search entity ID']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) { // print("\n$json\n");
            $obj = json_decode($json); // print_r($obj);

            if($what == 'all') return $obj;
            elseif($what == 'DOI') return $obj->entities->$wikidata_id->claims->P356[0]->mainsnak->datavalue->value;
            else exit("\nERROR: Specify return item.\n");
        }
        else echo "\nShould not go here.\n";

    }
    private function get_info_from_citation($citation, $what)
    {
        $json = shell_exec($this->anystyle_parse_prog . ' "'.$citation.'"');
        $json = substr(trim($json), 1, -1); # remove first and last char
        $json = str_replace("\\", "", $json); # remove "\" from converted json from Ruby
        $obj = json_decode($json); //print_r($obj);
        if($what == 'all') return $obj;
        elseif($what == 'title') return $obj[0]->title[0];
        echo ("\n-end muna-\n");
    }
}
?>