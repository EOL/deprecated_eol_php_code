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
        $this->crossref_api['search citation'] = "http://api.crossref.org/works?query.bibliographic=MY_CITATION&rows=2";
        $this->debug = array();
    }

    function create_item_if_does_not_exist($citation)
    {
        $ret = self::crossref_citation($citation);
        exit("\n-end muna\n");
        $citation_obj = self::get_info_from_citation($citation, 'all');
        self::does_title_exist_in_wikidata($citation_obj, $citation);
        echo ("\n-end muna-\n");
    }
    private function crossref_citation($citation)
    {
        $url = str_replace("MY_CITATION", urlencode($citation), $this->crossref_api['search citation']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            // print("\n$json\n");
            $obj = json_decode($json); print_r($obj);
        }

    }
    private function does_title_exist_in_wikidata($citation_obj, $citation)
    {
        $title = $citation_obj[0]->title[0];
        echo "\n[$title]\n";
        $url = str_replace("MY_TITLE", urlencode($title), $this->wikidata_api['search title']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            print("\n$json\n");
            $obj = json_decode($json); // print_r($obj);
            if($wikidata_id = @$obj->search[0]->id) { # e.g. Q56079384
                echo "\nTitle exists: [$title]\n";
                echo "\nwikidata_id: [$wikidata_id]\n";
                if($DOI = self::get_wikidata_entity_info($wikidata_id, 'DOI')) {
                    echo "\nhas DOI: [$DOI]\n";
                }
            }
            else {
                echo "\nTitle does not exist. [$title]\n";
                self::create_WD_reference_item($citation_obj, $citation);
            }

        }
    }
    private function create_WD_reference_item($citation_obj, $citation)
    {
        /*
        author (P50)
        publisher (P123)
        place of publication (P291)
        page(s) (P304)
        issue (P433)
        volume (P478)
        publication date (P577)
        chapter (P792)
        title (P1476)
        */
        $obj = $citation_obj[0];
        print_r($obj); exit;
        if($authors = @$obj->author)                 self::prep_for_adding($authors, 'P50');
        if($publishers = @$obj->publisher)           self::prep_for_adding($publishers, 'P123');
        if($place_of_publications = @$obj->location) self::prep_for_adding($place_of_publications, 'P291');
        if($pages = @$obj->pages)                    self::prep_for_adding($pages, 'P304');
        if($issue = @$obj->issue)                    self::prep_for_adding($issues, 'P433');
        if($volumes = @$obj->volume)                 self::prep_for_adding($volumes, 'P478');
        if($publication_dates = @$obj->date)         self::prep_for_adding($publication_dates, 'P577');
        if($chapters = @$obj->chapter)               self::prep_for_adding($chapters, 'P792'); //No 'chapter' parsed by AnyStyle. Eli should do his own parsing.
        if($titles = @$obj->title)                   self::prep_for_adding($titles, 'P1476');
        // Others:
        if($dois = @$obj->doi)                       self::prep_for_adding($dois, 'P356');
        if($reference_URLs = @$obj->url)             self::prep_for_adding($reference_URLs, 'P854');

        // /* Eli's choice: will take Jen's approval first -> https://www.wikidata.org/wiki/Property:P1683
        self::prep_for_adding(array($citation), 'P1683');
        // */

        /* Reminders:
        CREATE
        LAST	P31	Q13442814
                instance of -> scholarly article

        scholarly article   https://www.wikidata.org/wiki/Q13442814 for anything with a DOI and 
        scholarly work      https://www.wikidata.org/wiki/Q55915575 for all other items we create for sources.
        */

    }
    private function prep_for_adding($x, $y)
    {

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