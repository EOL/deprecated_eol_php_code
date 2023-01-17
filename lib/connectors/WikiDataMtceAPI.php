<?php
namespace php_active_record;
/* connector: [xxx.php]
---------------------------------------------------------------------------
sample of citations already in WikiData:
https://www.wikidata.org/wiki/Q56079384 ours
https://www.wikidata.org/wiki/Q56079384 someones

Manual check of citations/references:
https://apps.crossref.org/simpleTextQuery/

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
        $this->wikidata_api['search string'] = "https://www.wikidata.org/w/api.php?action=wbsearchentities&language=en&format=json&search=MY_TITLE";
        $this->wikidata_api['search entity ID'] = "https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=ENTITY_ID";
        $this->crossref_api['search citation'] = "http://api.crossref.org/works?query.bibliographic=MY_CITATION&rows=2";
        $this->debug = array();
        
        // /* unique temp file
        $last_digit = (string) rand();
        $last_digit = substr((string) rand(), -2);
        $this->temp_file = DOC_ROOT . "/tmp/" . date("Y_m_d_H_i_s_") . $last_digit . ".qs";
        $this->temp_file = DOC_ROOT . "/tmp/test.qs";
        if(file_exists($this->temp_file)) unlink($this->temp_file);
        // */

        // exit("\n".QUICKSTATEMENTS_EOLTRAITS_TOKEN."\n");

        /* https://docs.google.com/spreadsheets/d/129IRvjoFLUs8kVzjdchT_ImlCGGXIdVKYkKwIv7ld0U/edit#gid=0 */

        // /* report filename - generated from CypherQueryAPI.php
        $this->report_path = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/";
        if(!is_dir($this->report_path)) mkdir($this->report_path);

        // */        

    }
    function create_WD_traits($input)
    {   
        // /* lookup spreadsheet for mapping
        $this->map = self::get_WD_entity_mappings();
        // */
        // /* report file to process
        $tmp = md5(json_encode($input));
        $this->tsv_file = $this->report_path."/".$tmp.".tsv"; // echo "\n$this->tsv_file\n";
        // */
        $i = 0;
        foreach(new FileIterator($this->tsv_file) as $line => $row) {
            // $row = Functions::conv_to_utf8($row);
            $i++;
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) {
                    $rec[$field] = $tmp[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec);
                // print_r($rec); //exit;
                self::write_trait_2wikidata($rec);
                if($i >= 7) break; //debug
            }
        }
    }
    private function write_trait_2wikidata($rec)
    {
        /*Array(
            [p.canonical] => Viadana semihamata
            [p.page_id] => 46941724
            [pred.name] => behavioral circadian rhythm
            [stage.name] => adult
            [sex.name] => 
            [stat.name] => 
            [obj.name] => nocturnal
            [t.measurement] => 
            [units.name] => 
            [t.source] => 
            [t.citation] => Fornoff, Felix; Dechmann, Dina; Wikelski, Martin. 2012. Observation of movement and activity via radio-telemetry reveals diurnal behavior of the neotropical katydid Philophyllia Ingens (Orthoptera: Tettigoniidae). Ecotropica, 18 (1):27-34
            [ref.literal] => 
        )*/
        print_r($rec); //exit;
        if($taxon_entity_id = self::is_instance_of_taxon($rec['p.canonical'])) {
            $final = array();
            $final['taxon_entity'] = $taxon_entity_id;
            $final['predicate_entity'] = $this->map['measurementTypes'][$rec["pred.name"]]["property"];
            $final['object_entity'] = $this->map['measurementValues'][$rec["obj.name"]]["property"];
            $title = self::parse_citation_using_anystyle($rec['t.citation'], 'title');
            $title = self::manual_fix_title($title);

            // /* when to use 'published in' and 'stated in' ? TODO
            // $final['P1433'] = self::get_WD_entity_object($title, 'entity_id'); //published in
            $final['P248'] = self::get_WD_entity_object($title, 'entity_id'); //stated in
            // */

            if($final['taxon_entity'] && $final['predicate_entity'] && $final['object_entity']) self::create_WD_taxon_trait($final);
        }
        else echo "\nNot a taxon: [".$rec['p.canonical']."]\n";
        // print_r($final);
    }
    function create_citation_if_does_not_exist($citation)
    {
        /* Crossref is not reliable. It always gets a DOI for most citations. https://apps.crossref.org/simpleTextQuery/
        $ret = self::crossref_citation($citation);
        print_r($ret);
        echo "\n DOI: ".$ret->message->items[0]->DOI."\n";
        echo "\n items: ".count($ret->message->items);
        exit("\n-end muna\n");
        */

        $citation_obj = self::parse_citation_using_anystyle($citation, 'all');
        // if($ret = self::does_title_exist_in_wikidata($citation_obj, $citation)) { //orig un-comment in real operation
        if(false) {
            print_r($ret);
        }
        else {
            $title = $citation_obj[0]->title[0];
            echo "\nTitle does not exist. [$title]\n";
            self::create_WD_reference_item($citation_obj, $citation);
        }
        ;
        echo ("\n-end muna-\n");
    }
    function get_WD_entity_object($string, $what = 'all')
    {
        $url = str_replace("MY_TITLE", urlencode($string), $this->wikidata_api['search string']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            // print("\n$json\n");
            $obj = json_decode($json); //print_r($obj); exit;
            if($what == 'all') return $obj;
            elseif($what == 'entity_id') return $obj->search[0]->id;
            // {
            //     return $obj->search[0]->id;
            //     print_r($obj); exit;
            // }
        }
        return false;
    }
    function is_instance_of_taxon($taxon)
    {
        echo "\ntaxon: [$taxon]\n";
        $obj = self::get_WD_entity_object($taxon);

        if($wikidata_id = @$obj->search[0]->id) { # e.g. Q56079384
            // echo "\nTitle exists: [$title]\n";
            echo "\nwikidata_id: [$wikidata_id]\n";
            if($taxon_obj = self::get_wikidata_entity_info($wikidata_id, 'all')) {
                // print_r($taxon_obj);
                $instance_of = @$taxon_obj->entities->$wikidata_id->claims->P31[0]->mainsnak->datavalue->value->id;
                // exit("\n[$instance_of]\n");
                if($instance_of == 'Q16521') # instance_of -> taxon
                {
                    return $wikidata_id;
                }
            }
        }
        return false;
    }
    private function does_title_exist_in_wikidata($citation_obj, $citation)
    {
        $title = $citation_obj[0]->title[0];
        $title = self::manual_fix_title($title); #important
        echo "\nsearch title: [$title]\n";
        $url = str_replace("MY_TITLE", urlencode($title), $this->wikidata_api['search string']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) { // print("\n$json\n");
            $obj = json_decode($json); // print_r($obj);
            if($wikidata_id = @$obj->search[0]->id) { # e.g. Q56079384
                echo "\nTitle exists: [$title]\n";
                echo "\nwikidata_id: [$wikidata_id]\n";
                $DOI = self::get_wikidata_entity_info($wikidata_id, 'DOI');
                return array("title" => $title, "wikidata_id" => $wikidata_id, "DOI" => $DOI);
            }
            else return false;
        }
    }
    private function manual_fix_title($str)
    {
        if(substr($str, -(strlen("(Orthoptera: Tettigoniidae"))) == "(Orthoptera: Tettigoniidae") return $str.")";
        return $str;
    }
    private function create_WD_taxon_trait($r)
    {
        print_r($r); //exit("\nxxx\n");
        /*Array(
            [taxon_entity] => Q10397859
            [predicate_entity] => https://www.wikidata.org/wiki/Property:P9566
            [object_entity] => https://www.wikidata.org/wiki/Q101029366
            [P1433] => Q116180473
        )*/
        $rows = array();
        $row = $r['taxon_entity']."|".self::get_property_from_uri($r['predicate_entity'])."|".self::get_property_from_uri($r['object_entity']);
        if($published_in = $r['P1433']) $row .= "|S1433|".$published_in;
        if($stated_in = $r['P248']) $row .= "|S248|".$stated_in;
        
        $rows[] = $row;

        print_r($rows);
        $WRITE = Functions::file_open($this->temp_file, "a");
        foreach($rows as $row) {
            fwrite($WRITE, $row."\n");
        }
        fclose($WRITE);
    }
    private function get_property_from_uri($uri)
    {
        $basename = pathinfo($uri, PATHINFO_BASENAME);
        $parts = explode(":", $basename);
        if($val = @$parts[1]) return $val;
        else return $basename;

    }
    private function create_WD_reference_item($citation_obj, $citation)
    {
        /*
        author (P2093)                  -> use P50 only if author is an entity
        publisher (P123)
        place of publication (P291)
        page(s) (P304)
        issue (P433)                    -> disregard, since an issue should have a statement. Required by Wikidata.
        volume (P478)
        publication date (P577)
        chapter (P792)
        title (P1476)
        */
        $obj = $citation_obj[0];

        // /* manual fixes
        if($val = @$obj->title[0]) $obj->title[0] = self::manual_fix_title($val);
        // */

        // print_r($obj); exit("\nstop muna\n");

        $rows = array();
        $rows[] = 'CREATE';

        // /* scholarly ?
        // The "instance of" classifications: To keep things simple, we could use "scholarly article" if the reference parsers tell us it's a journal article, 
        // and "scholarly work" for everything else.
        $publication_types = array("article-journal", "chapter", "book");
        if(in_array(@$obj->type, $publication_types)) {}
        else exit("\nUndefined publication type.\n");
        if(stripos(@$obj->type, 'journal') !== false) $scholarly = "scholarly article";
        else                                          $scholarly = "scholarly work";
        // */


        // /* first two entries: label and description
        # LAST TAB Lfr TAB "Le croissant magnifique!"
        if($title = @$obj->title[0]) {
            $rows[] = 'LAST|Len|' .'"'.$title.'"';
            $rows[] = 'LAST|Den|' .'"'.self::build_description($obj, $scholarly).'"';
        }
        // */

        // /* scholarly xxx
        // if($dois = @$obj->doi) $rows[] = "LAST|P31|Q13442814"; // instance of -> scholarly article //old
        if($scholarly == "scholarly article") $rows[] = "LAST|P31|Q13442814"; // instance of -> scholarly article
        else                                  $rows[] = "LAST|P31|Q55915575"; // instance of -> scholarly work
        // */

        if($authors = @$obj->author)                 $rows = self::prep_for_adding($authors, 'P2093', $rows); #ok use P50 if author is an entity
        if($publishers = @$obj->publisher)           $rows = self::prep_for_adding($publishers, 'P123', $rows);
        if($place_of_publications = @$obj->location) $rows = self::prep_for_adding($place_of_publications, 'P291', $rows);
        if($pages = @$obj->pages)                    $rows = self::prep_for_adding($pages, 'P304', $rows); #ok
        // if($issues = @$obj->issue)                   $rows = self::prep_for_adding($issues, 'P433', $rows); #ok
        if($volumes = @$obj->volume)                 $rows = self::prep_for_adding($volumes, 'P478', $rows); #ok
        if($publication_dates = @$obj->date)         $rows = self::prep_for_adding($publication_dates, 'P577', $rows); #ok
        if($chapters = @$obj->chapter)               $rows = self::prep_for_adding($chapters, 'P792', $rows); //No 'chapter' parsed by AnyStyle. Eli should do his own parsing.
        if($titles = @$obj->title)                   $rows = self::prep_for_adding($titles, 'P1476', $rows); #ok

        /* Eli's initiative atm.
        // So for starters, I added another property 'type of reference' (P3865)
        if($type = @$obj->type)                      $rows = self::prep_for_adding(array($type), 'P3865', $rows); #ok
        */

        // /* Eli's initiative but close to Jen's "published in" (P1433) proposal
        if($containers = @$obj->{"container-title"})      $rows = self::prep_for_adding($containers, 'P1433', $rows); #ok
        
        // */

        // Others:
        if($dois = @$obj->doi)                       $rows = self::prep_for_adding($dois, 'P356', $rows); #ok
        if($reference_URLs = @$obj->url)             $rows = self::prep_for_adding($reference_URLs, 'P854', $rows);

        /* Eli's choice: will take Jen's approval first -> https://www.wikidata.org/wiki/Property:P1683 -> WikiData says won't use it here.
        $rows = self::prep_for_adding(array($citation), 'P1683', $rows);
        */

        print_r($rows);
        $WRITE = Functions::file_open($this->temp_file, "w");
        foreach($rows as $row) {
            fwrite($WRITE, $row."\n");
        }
        fclose($WRITE);

        /* Reminders:
        CREATE
        LAST	P31	Q13442814
                instance of -> scholarly article

        scholarly article   https://www.wikidata.org/wiki/Q13442814 for anything with a DOI and 
        scholarly work      https://www.wikidata.org/wiki/Q55915575 for all other items we create for sources.
        */
        
    }
    private function format_string($str)
    {   /*
        If submitting to the API, use 
            "%09" instead of the TAB symbol, 
            "%2B" instead of the "+" symbol, 
            "%3A" instead of the ":" symbol, and 
            "%2F" instead of the "/" symbol.
        */
        $str = str_replace("\t", "%09", $str);
        $str = str_replace("+", "%2B", $str);
        $str = str_replace(":", "%3A", $str);
        $str = str_replace("/", "%2F", $str);
        return $str;
        // return urlencode($str);
    }
    private function format_publication_date($str)
    {   # +1839-00-00T00:00:00Z/9
        if(strlen($str) == 4) {
            return "+".$str."-00-00T00:00:00Z/9";
        }
        else {
            $vdate = strtotime($str);
            $p1 = date("Y-m-d", $vdate);
            $p2 = date("H:i:s", $vdate);
            $final = $p1."T".$p2."Z/9";
            return $final;
        }
    }
    private function prep_for_adding($recs, $property, $rows)
    {
        if($property == 'P2093') { # P50 is if author is an entity
            foreach($recs as $rec) {
                $name = "";
                if($family = @$rec->family) $name .= "$family, ";
                if($given = @$rec->given) $name .= "$given";
                $rows[] = "LAST|$property|" . '"'.self::format_string($name).'"';
            }
        }
        elseif($property == 'P577') {
            foreach($recs as $val) {
                $rows[] = "LAST|$property|" . self::format_publication_date($val);
            }
        }
        
        elseif($property == 'P1476') { #title
            foreach($recs as $val) {
                $rows[] = "LAST|$property|en:" .'"'.$val.'"';
            }
        }

        /* to do: Eli
        elseif($property == 'P1433') { # "published in" - value needs to be an entity. E.g. 'Ecotropica'
            foreach($recs as $val) {
                $rows[] = "LAST|$property|en:" .'"'.$val.'"';
            }
        }
        */
        

        else { // the rest goes here
            foreach($recs as $val) {

                if(in_array($property, array('P1476', 'P1683', 'P3865'))) {
                    $lang = "en:";
                    $val = self::format_string($val);
                }
                else $lang = "";

                $rows[] = "LAST|$property|$lang" . '"'.$val.'"';
            }
        }
        return $rows;
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
            elseif($what == 'DOI') return @$obj->entities->$wikidata_id->claims->P356[0]->mainsnak->datavalue->value;
            else exit("\nERROR: Specify return item.\n");
        }
        else echo "\nShould not go here.\n";

    }
    private function parse_citation_using_anystyle($citation, $what)
    {
        $json = shell_exec($this->anystyle_parse_prog . ' "'.$citation.'"');
        $json = substr(trim($json), 1, -1); # remove first and last char
        $json = str_replace("\\", "", $json); # remove "\" from converted json from Ruby
        $obj = json_decode($json); //print_r($obj);
        if($what == 'all') return $obj;
        elseif($what == 'title') return $obj[0]->title[0];
        echo ("\n-end muna-\n");
    }


    function get_WD_entity_mappings()
    {   
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '129IRvjoFLUs8kVzjdchT_ImlCGGXIdVKYkKwIv7ld0U';
        
        $sheets = array("measurementTypes", "measurementValues", "metadata");
        foreach($sheets as $sheet) {
            $params['range']         = $sheet.'!A1:C100'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
            $arr = $func->access_google_sheet($params);    
            $final[$sheet] = self::massage_google_sheet_results($arr);
        }
        // print_r($final); exit;
        return $final;
    }
    private function massage_google_sheet_results($arr)
    {   //start massage array
        $i = 0;
        foreach($arr as $item) { $i++;
            $item = array_map('trim', $item);
            if($i == 1) $labels = array("a" => $item[1], "b" => $item[2]);
            else        $final[$item[0]] = array($labels['a'] => $item[1], $labels['b'] => $item[2]);
        }
        // print_r($final);
        return $final;
        // */
    }
    private function build_description($obj, $scholarly)
    {   /* To construct the Description of a publication entity: use the "instance of" value for the item (scholarly article or scholarly work) 
        and the string "by [author]" if there is a single author, "by [author & author]" if there are 2 authors, and "by [author et al.]" 
        if there are more then 2 authors. eg: https://www.wikidata.org/wiki/Q116180473
        stdClass Object(
            [author] => Array(
                    [0] => stdClass Object(
                            [family] => Fornoff
                            [given] => Felix
                        )
                    [1] => stdClass Object(
                            [family] => Dechmann
                            [given] => Dina
                        )
                    [2] => stdClass Object(
                            [family] => Wikelski
                            [given] => Martin
                        )
                )
            [type] => article-journal
        )
        */
        $i = 0;
        foreach($obj->author as $a) { $i++;
            $author[$i] = $a->family;
            if($given = @$a->given) $author[$i] .= ", " . $given;
        }

        if(count($obj->author) == 1)        $str = $author[1];
        elseif(count($obj->author) == 2)    $str = $author[1]. " and ".$author[2];
        elseif(count($obj->author) > 2)     $str = $author[1]." et al.";
        
        return $scholarly . " by " . $str;




    }

    /* working func but not used, since Crossref is not used, unreliable.
    private function crossref_citation($citation)
    {
        $url = str_replace("MY_CITATION", urlencode($citation), $this->crossref_api['search citation']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) { // print("\n$json\n");
            $obj = json_decode($json);
            return $obj;
        }
    } */
}
?>