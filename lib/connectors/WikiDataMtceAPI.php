<?php
namespace php_active_record;
/* connector: [xxx.php]
---------------------------------------------------------------------------
sample of citations already in WikiData:    https://www.wikidata.org/wiki/Q56079384 ours
Manual check of citations/references:       https://apps.crossref.org/simpleTextQuery/

exit("\n".QUICKSTATEMENTS_EOLTRAITS_TOKEN."\n");
https://docs.google.com/spreadsheets/d/129IRvjoFLUs8kVzjdchT_ImlCGGXIdVKYkKwIv7ld0U/edit#gid=0
*/
class WikiDataMtceAPI
{
    function __construct($folder = null, $query = null)
    {
        $this->download_options = array(
            'resource_id'        => 'wikidata',  //resource_id here is just a folder name in cache
            'expire_seconds'     => false, //60*60*24*30, //maybe 1 month to expire
            'download_wait_time' => 1000000, 'timeout' => 60*3, 'download_attempts' => 1, 'delay_in_minutes' => 0.5);

        $this->anystyle_parse_prog = "ruby ".DOC_ROOT. "update_resources/connectors/helpers/anystyle/run.rb";
        $this->wikidata_api['search string'] = "https://www.wikidata.org/w/api.php?action=wbsearchentities&language=en&format=json&search=MY_TITLE";
        $this->wikidata_api['search entity ID'] = "https://www.wikidata.org/w/api.php?action=wbgetentities&format=json&ids=ENTITY_ID";
        $this->crossref_api['search citation'] = "http://api.crossref.org/works?query.bibliographic=MY_CITATION&rows=2";
        $this->debug = array();
                
        $this->tmp_batch_export = DOC_ROOT . "/tmp/temp_export.qs";

        // */
    }
    private function initialize_path($input)
    {
        $this->report_path = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/"; //generated from CypherQueryAPI.php
        if(!is_dir($this->report_path)) mkdir($this->report_path);
        $tmp = md5(json_encode($input));
        $this->report_path .= "$tmp/";
        if(!is_dir($this->report_path)) mkdir($this->report_path);

        /* the next are the 3 files to be generated: */

        // /* unique export file
        $last_digit = (string) rand();
        $last_digit = substr((string) rand(), -2);
        $this->temp_file = $this->report_path . date("Y_m_d_H_i_s_") . $last_digit . ".qs";
        $this->temp_file = $this->report_path."export_file.qs"; //nocturnal group
        if(file_exists($this->temp_file)) unlink($this->temp_file); //un-comment in real operation
        // */
        
        // /* 
        $this->report_not_taxon_or_no_wikidata = $this->report_path."unprocessed_taxa.tsv";
        if(file_exists($this->report_not_taxon_or_no_wikidata)) unlink($this->report_not_taxon_or_no_wikidata); //un-comment in real operation
        $final = array();
        $final[] = 'EOL name';
        $final[] = 'pageID';
        $WRITE = Functions::file_open($this->report_not_taxon_or_no_wikidata, "w");
        fwrite($WRITE, implode("\t", $final)."\n");
        fclose($WRITE);
        // */

        // /* report for Katja - taxonomic mappings for the trait statements we send to WikiData
        $this->taxonomic_mappings = $this->report_path."taxonomic_mappings_for_review.tsv";
        if(file_exists($this->taxonomic_mappings)) unlink($this->taxonomic_mappings); //un-comment in real operation
        $final = array();
        $final[] = 'EOL name';
        $final[] = 'pageID';
        $final[] = 'WikiData name';
        $final[] = 'ID';
        $final[] = 'Description';
        $final[] = 'Mapped';
        $this->WRITE = Functions::file_open($this->taxonomic_mappings, "w");
        fwrite($this->WRITE, implode("\t", $final)."\n");
    }

    function create_WD_traits($input)
    {   //print_r($input); exit("\nstop 2\n");
        self::initialize_path($input);
        // /* use identifier-map for taxon mapping - EOL page ID to WikiData entity ID
        require_library('connectors/IdentifierMapAPI');
        $func = new IdentifierMapAPI(); //get_declared_classes(); will give you how to access all available classes
        $this->taxonMap = $func->read_identifier_map_to_var(array("resource_id" => 1072));
        $this->taxonMap_all = $func->read_identifier_map_to_var(array("resource_id" => 'all'));
        echo "\ntaxonMap: ".count($this->taxonMap)."\n";
        // exit("\nelix1\n");
        // */

        // /* lookup spreadsheet for mapping
        $this->map = self::get_WD_entity_mappings();
        // */
        // /* report file to process
        // orig
        // $tmp = md5(json_encode($input));
        // $this->tsv_file = $this->report_path."/".$tmp."_".$input["trait kind"].".tsv"; //exit("\n".$this->tsv_file."\n");
        $this->tsv_file = $this->report_path."/".$input["trait kind"]."_qry.tsv"; //exit("\n".$this->tsv_file."\n");
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
                foreach($fields as $field) { $rec[$field] = $tmp[$k]; $k++; }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit("\nelix1\n");
                if($rec['pred.name'] && $rec['obj.name']) { //$rec['p.canonical'] && 
                    self::write_trait_2wikidata($rec, $input['trait kind']);
                }
                // if($i >= 20) break; //debug
            }
        }
        /* un-comment in real operation. Now I wanted to check first the big export file first before proceeding.
        self::divide_exportfile_send_2quickstatements();
        */
    }
    private function write_trait_2wikidata($rec, $trait_kind)
    {       /*Array(
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
        // print_r($rec); exit("\nstop 1\n");
        if($ret = self::get_wikidata_obj_using_EOL_pageID($rec['p.page_id'], $rec['p.canonical'])) { //1st option
            $entity_id = $ret[0];
            $wikidata_obj = $ret[1];
            $wikidata_obj = $wikidata_obj->entities->$entity_id;

            @$wikidata_obj->display->label->value        = $wikidata_obj->labels->en->value;
            @$wikidata_obj->display->description->value  = $wikidata_obj->descriptions->en->value;
            $rec['how'] = 'identifier-map';
            // print_r($wikidata_obj); exit("\nelix3\n");
        }
        else {
            $text_file = $this->report_not_taxon_or_no_wikidata;
            if($ret = @$this->taxonMap_all[$rec['p.page_id']]) {
                // print_r($rec); print_r($ret); exit("\nhuli ka\n");
                $rec['p.canonical'] = $ret['c'];
                if($wikidata_obj = self::is_instance_of_taxon($rec['p.canonical'])) $rec['how'] = 'name search thru identifier-map'; //2nd option
                else {
                    // print_r($rec); exit("\nCannot proceed with this record 22.\n");
                    $str = implode("\t", array($rec['p.canonical'], $rec['p.page_id'], "**"));
                    self::write_2text_file($text_file, $str); //."ignored record**"
                }
            }
            elseif($rec['p.canonical'] && $wikidata_obj = self::is_instance_of_taxon($rec['p.canonical'])) $rec['how'] = 'name search'; //prev. 2nd option. Now 3rd.
            else {
                // print_r($rec); exit("\nCannot proceed with this record 11.\n");
                $str = implode("\t", array($rec['p.canonical'], $rec['p.page_id'], "*"));
                self::write_2text_file($text_file, $str); //."ignored record*"
            }
        }

        if($wikidata_obj) {
            // print_r($wikidata_obj); exit("\nelix4\n");

            $taxon_entity_id = $wikidata_obj->id;
            self::write_taxonomic_mapping($rec, $wikidata_obj);

            $final = array();
            $final['taxon_entity'] = $taxon_entity_id;

            if($val = @$this->map['measurementTypes'][$rec["pred.name"]]["property"]) $final['predicate_entity'] = $val;
            else echo "\nUndefined pred.name: [".$rec["pred.name"]."] \n";
            
            if($val = @$this->map['measurementValues'][$rec["obj.name"]]["property"]) $final['object_entity'] = $val;
            else echo "\nUndefined obj.name: [".$rec["obj.name"]."] \n";
            
            $title = self::parse_citation_using_anystyle($rec['t.citation'], 'title');
            $title = self::manual_fix_title($title);

            /* when to use 'published in' ? TODO --> right now Eli did it manually e.g. 'published in' https://www.wikidata.org/wiki/Q116180473
            $final['P1433'] = self::get_WD_obj_using_string($title, 'entity_id'); //published in --- was not advised to use for now
            */

            /* The property to connect taxon to publication: If practical, "stated in" (P248) for regular records 
            and "inferred from" (P3452) for branch-painted records. 
            Eli, the corresponding part of your queries is [:trait|inferred_trait]. 
            If the queries were separated into a version using [:trait] and one using [:inferred_trait], 
            we could distinguish between regular records (P248) and branch painted records (P3452). */
            if    ($trait_kind == "trait")          $final['P248']  = self::get_WD_obj_using_string($title, 'entity_id'); //"stated in" (P248)
            elseif($trait_kind == "inferred_trait") $final['P3452'] = self::get_WD_obj_using_string($title, 'entity_id'); //"inferred from" (P3452) 
            else exit("\nUndefined trait kind.\n");

            if($final['taxon_entity'] && @$final['predicate_entity'] && @$final['object_entity']) self::create_WD_taxon_trait($final);
        }
        else echo "\nNot a taxon: [".$rec['p.canonical']."]\n";
        // print_r($final);
    }
    function create_citation_if_does_not_exist($citation, $t_source)
    {
        /* Crossref is not reliable. It always gets a DOI for most citations. https://apps.crossref.org/simpleTextQuery/
        $ret = self::crossref_citation($citation);
        print_r($ret);
        echo "\n DOI: ".$ret->message->items[0]->DOI."\n";
        echo "\n items: ".count($ret->message->items);
        exit("\n-end muna\n");
        */

        // /* lookup spreadsheet for mapping
        $this->map = self::get_WD_entity_mappings();
        // */
        
        /*
        Searching for an existing publication entity: if t.source is a doi, or a wikidata url, use that to identify the entity. 
        If not, proceed to parsing.
        */

        $citation_obj = self::parse_citation_using_anystyle($citation, 'all');
        if($ret = self::does_title_exist_in_wikidata($citation_obj, $citation)) { //orig un-comment in real operation
            print_r($ret);
            return $ret['wikidata_id'];
        }
        // if(false) {}
        else {
            $title = $citation_obj[0]->title[0];
            echo "\nTitle does not exist. [$title]\n";
            self::create_WD_reference_item($citation_obj, $citation);
        }
        
        echo ("\n-end muna-\n");
    }
    function is_instance_of_taxon($taxon)
    {
        $text_file = $this->report_not_taxon_or_no_wikidata;
        echo "\nSearching taxon... [$taxon]\n";
        $ret = self::get_WD_obj_using_string($taxon);

        // print_r($objs); exit;
        foreach($ret->search as $obj) {
            if($wikidata_id = @$obj->id) { # e.g. Q56079384
                echo "\npossible ID for '$taxon': [$wikidata_id]\n";
                if($taxon_obj = self::get_WD_obj_using_id($wikidata_id, 'all')) { //print_r($taxon_obj);
                    $instance_of = @$taxon_obj->entities->$wikidata_id->claims->P31[0]->mainsnak->datavalue->value->id; //exit("\n[$instance_of]\n");
                    if    ($instance_of == 'Q16521')  return $obj; //$wikidata_id; # instance_of -> taxon
                    elseif($instance_of == 'Q310890') return $obj; //$wikidata_id; # instance_of -> monotypic taxon
                }
            }    
        } //foreach
        echo "\nNot found in WikiData\n";
        // self::write_2text_file($text_file, $taxon."\t"."not in WikiData"); //moved
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
                $DOI = self::get_WD_obj_using_id($wikidata_id, 'DOI');
                return array("title" => $title, "wikidata_id" => $wikidata_id, "DOI" => $DOI);
            }
            else return false;
        }
    }
    private function manual_fix_title($str)
    {
        if(substr($str, -(strlen("(Orthoptera: Tettigoniidae"))) == "(Orthoptera: Tettigoniidae") return $str.")";
        elseif(substr($str, -(strlen("Lampyridae (Coleoptera"))) == "Lampyridae (Coleoptera") return $str.")";
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
        
        if($stated_in     = @$r['P248'])  $row .= "|S248|".$stated_in;
        if($inferred_from = @$r['P3452']) $row .= "|S3452|".$inferred_from;
        if($published_in  = @$r['P1433']) $row .= "|S1433|".$published_in; // seems not used, nor has implementation rules
        
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
        if($scholarly == "scholarly article") $rows[] = "LAST|P31|Q13442814 /* scholarly article */"; // instance of -> scholarly article
        else                                  $rows[] = "LAST|P31|Q55915575 /* scholarly work */"; // instance of -> scholarly work
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

        // /* Eli's initiative, but was given a go signal -> property 'type of reference' (P3865)
        if($type = @$obj->type)                      $rows = self::prep_for_adding(array($type), 'P3865', $rows); #ok
        // */

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
        foreach($rows as $row) fwrite($WRITE, $row."\n");
        fclose($WRITE);

        /* NEXT TODO: is the exec_shell command to trigger QuickStatements */
        exit("\nUnder construction...\n");
        

        /* Reminders:
        CREATE
        LAST	P31	Q13442814
                instance of -> scholarly article

        scholarly article   https://www.wikidata.org/wiki/Q13442814 for anything with a DOI and 
        scholarly work      https://www.wikidata.org/wiki/Q55915575 for all other items we create for sources.
        */
    }
    private function format_string($str)
    {   /* If submitting to the API, use 
            "%09" instead of the TAB symbol, 
            "%2B" instead of the "+" symbol, 
            "%3A" instead of the ":" symbol, and 
            "%2F" instead of the "/" symbol. */
        $str = str_replace("\t", "%09", $str);
        $str = str_replace("+", "%2B", $str);
        $str = str_replace(":", "%3A", $str);
        $str = str_replace("/", "%2F", $str);
        return $str;
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
        elseif($property == 'P3865') { #type - reference type
            foreach($recs as $val) {
                // $rows[] = "LAST|$property|".$this->map["other values"][$val]['entity']. " /* $val */";
                if($entity_id = @$this->map["other values"][$val]['entity']) {
                    if($property == @$this->map["other values"][$val]['field']) $rows[] = "LAST|$property|$entity_id". " /* $val */";
                    else exit("\nUndefined reference type [$val]\n");
                }
            }
        }
        // /* to do: Eli
        elseif($property == 'P1433') { # "published in" - value needs to be an entity. E.g. 'Ecotropica'
            foreach($recs as $val) {
                if($entity_id = @$this->map["other values"][$val]['entity']) {
                    if($property == @$this->map["other values"][$val]['field']) $rows[] = "LAST|$property|$entity_id". " /* $val */";
                    else exit("\nUndefined 'published in' [$val]\n");
                }
            }
        }
        // */        
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
        // /* group 1
        $sheets = array("measurementTypes", "measurementValues", "metadata", "other values");
        foreach($sheets as $sheet) {
            $params['range']         = $sheet.'!A1:C100'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
            $arr = $func->access_google_sheet($params); //2nd param false means cache expires
            $final[$sheet] = self::massage_google_sheet_results($arr);
        }
        // */
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
        ) */
        $i = 0;
        foreach($obj->author as $a) { $i++;
            $author[$i] = $a->family;
            /* working OK - if we want to add the given name
            if($given = @$a->given) $author[$i] .= ", " . $given;
            */
        }
        if(count($obj->author) == 1)        $str = $author[1];
        elseif(count($obj->author) == 2)    $str = $author[1]. " and ".$author[2];
        elseif(count($obj->author) > 2)     $str = $author[1]." et al.";

        return $scholarly . " by " . $str;
    }
    private function write_2text_file($text_file, $row)
    {
        $WRITE = Functions::file_open($text_file, "a");
        fwrite($WRITE, $row."\n");
        fclose($WRITE);
    }
    function utility_parse_refs($refs)
    {
        foreach($refs as $ref) {
            $obj = self::parse_citation_using_anystyle($ref, 'all');
            // print_r($obj); exit;
            $obj = $obj[0];
            echo "\n[".$obj->type."][$ref]";
        }
    }
    private function write_taxonomic_mapping($rec, $wikidata_obj)
    {   /*Array(
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
        )
        Katja needs:
        EOL resource ID (the source of the trait record), EOL name & pageID and the WikiData name & ID.
        */
        // print_r($rec);
        $canonical = "";
        if($canonical = $rec['p.canonical']) {}
        else {
            if($ret = @$this->taxonMap_all[$rec['p.page_id']]) {
                $canonical = $ret['c'];
            }
        }
        $final = array();
        $final[] = $canonical;
        $final[] = $rec['p.page_id'];
        $final[] = $wikidata_obj->display->label->value;
        $final[] = $wikidata_obj->id;
        $final[] = @$wikidata_obj->display->description->value;
        $final[] = $rec['how'];
        fwrite($this->WRITE, implode("\t", $final)."\n");
        // print_r($rec); print_r($wikidata_obj); print_r($final); exit;
    }
    private function get_wikidata_obj_using_EOL_pageID($page_id, $canonical)
    {
        // /* manual fix
        // Jimenezia 41766 Jimenezia Q14632906 genus of crustaceans Q116270045
        if($page_id == 41766 && $canonical == "Jimenezia") return self::fix_further($page_id, $canonical, "Q116270045");
        // Caroliniella 46941873 Caroliniella Q15869235 genus of insects Q116270111
        elseif($page_id == 46941873 && $canonical == "Caroliniella") return self::fix_further($page_id, $canonical, "Q116270111");
        // Ceraia 45959 Ceraia Q2393841 genus of plants Q13581136
        elseif($page_id == 45959 && $canonical == "Ceraia") return self::fix_further($page_id, $canonical, "Q13581136");
        /* next batch */
        elseif($page_id == 494881 && $canonical == "Ceraia dentata") return self::fix_further($page_id, $canonical, "Q10445580");
        elseif($page_id == 47177312 && $canonical == "Drepanophyllum") return self::fix_further($page_id, $canonical, "Q10476638");
        elseif($page_id == 47179232) return self::fix_further($page_id, $canonical, "Q10553675");
        // Lamprophyllum 47179232 Lamprophyllum Schimp. ex Broth. (1907) non Miers (1855) Q17294325 later homonym, use Schimperobryum identifier-map Q10553675
        elseif($page_id == 46207 && $canonical == "Montana") return self::fix_further($page_id, $canonical, "Q10588738");
        elseif($page_id == 45912 && $canonical == "Platyphyllum") return self::fix_further($page_id, $canonical, "Q10633517");
        elseif($page_id == 497268 && $canonical == "Psyrana sondaica") return self::fix_further($page_id, $canonical, "Q10645422");
        elseif($page_id == 46238 && $canonical == "Pterophylla") return self::fix_further($page_id, $canonical, "Q10645731");
        elseif($page_id == 857000 && $canonical == "Typhoptera unicolor") return self::fix_further($page_id, $canonical, "Q10707441");
        elseif($page_id == 59571 && $canonical == "Xiphophyllum") return self::fix_further($page_id, $canonical, "Q10722856");
        elseif($page_id == 45836 && $canonical == "Zichya") return self::fix_further($page_id, $canonical, "Q10724602");
        elseif($page_id == 52766840) return self::fix_further($page_id, $canonical, "Q116327677");
        // Dendrobia 52766840 Dendrobianthe Q98228381 name search thru identifier-map Q116327677
        elseif($page_id == 34781706 && $canonical == "Dicorypha") return self::fix_further($page_id, $canonical, "Q13572701");
        elseif($page_id == 856709 && $canonical == "Phyllophora speciosa") return self::fix_further($page_id, $canonical, "Q13582231");
        elseif($page_id == 87504 && $canonical == "Platenia") return self::fix_further($page_id, $canonical, "Q14113863");
        elseif($page_id == 63359 && $canonical == "Albertisiella") return self::fix_further($page_id, $canonical, "Q14589678");
        elseif($page_id == 74142 && $canonical == "Baetica") return self::fix_further($page_id, $canonical, "Q14594580");
        elseif($page_id == 87690 && $canonical == "Ceresia") return self::fix_further($page_id, $canonical, "Q14624705");
        elseif($page_id == 76249 && $canonical == "Ebneria") return self::fix_further($page_id, $canonical, "Q14626974");
        elseif($page_id == 19733 && $canonical == "Macrochiton") return self::fix_further($page_id, $canonical, "Q15262438");
        elseif($page_id == 46941515 && $canonical == "Odontura") return self::fix_further($page_id, $canonical, "Q2014752");
        // */
        else { //orig
            if($ret = @$this->taxonMap[$page_id]) {
                /* never use this since p.canonical in query sometimes really is blank. And identifier-map can do the connection.
                if($canonical == $ret['c']) {
                    if($obj = self::get_WD_obj_using_id($ret['i'], 'all')) return array($ret['i'], $obj);
                }
                else exit("\nInvestigate not equal: [$canonical] [".$ret['c']."]\n");
                */
                if($obj = self::get_WD_obj_using_id($ret['i'], 'all')) return array($ret['i'], $obj);
            }    
        }
    }
    private function fix_further($page_id, $canonical, $new_WD_id)
    {
        $ret = array("i" => $new_WD_id, "c" => $canonical);
        $this->download_options['expire_seconds'] = true;
        if($obj = self::get_WD_obj_using_id($ret['i'], 'all')) {
            $this->download_options['expire_seconds'] = false;
            return array($ret['i'], $obj);
        }
        $this->download_options['expire_seconds'] = false;
    }
    function get_WD_obj_using_string($string, $what = 'all')
    {
        $url = str_replace("MY_TITLE", urlencode($string), $this->wikidata_api['search string']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) { // print("\n$json\n");
            $obj = json_decode($json); //print_r($obj); exit;
            if($what == 'all') return $obj;
            elseif($what == 'entity_id') {
                // print_r($obj);
                echo "\nentity found for string: [$string]";
                echo "\nentity ID found is: [".$obj->search[0]->id."]\n";
                return $obj->search[0]->id;
            }
        }
        return false;
    }
    private function get_WD_obj_using_id($wikidata_id, $what = 'all')
    {
        // https://www.wikidata.org/w/api.php?action=help&modules=wbgetentities
        // https://www.wikidata.org/w/api.php?action=wbgetentities&format=xml&ids=Q56079384
        // searching using wikidata entity id
        $url = str_replace("ENTITY_ID", $wikidata_id, $this->wikidata_api['search entity ID']);
        $options = $this->download_options;
        // $options['expire_seconds'] = 60; //only used when wikidata.org is updated. And you cannot pinpoint which records are upated.
        if($json = Functions::lookup_with_cache($url, $options)) { // print("\n$json\n");
            $obj = json_decode($json); // print_r($obj);

            if($what == 'all') return $obj;
            elseif($what == 'DOI') return @$obj->entities->$wikidata_id->claims->P356[0]->mainsnak->datavalue->value;
            else exit("\nERROR: Specify return item.\n");
        }
        else echo "\nShould not go here.\n";
    }
    function divide_exportfile_send_2quickstatements($input)
    {
        /* last to process:
        */
        // exit;
        
        // /* set the paths
        $this->report_path = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/"; //generated from CypherQueryAPI.php
        $tmp = md5(json_encode($input));
        $this->report_path .= "$tmp/";
        $this->temp_file = $this->report_path."export_file.qs"; //unique export file
        // */

        $i = 0;
        $batch_name = date("Y_m_d");
        $batch_num = 0;
        $WRITE = Functions::file_open($this->tmp_batch_export, "w");
        foreach(new FileIterator($this->temp_file) as $line => $row) {
            if($row) $i++;
            
            // /* use this block to exclude already run: manually done
            if($i < 2250) continue;
            // */

            echo "\n".$row;
            fwrite($WRITE, $row."\n");
            if(($i % 3) == 0) { $batch_num++;
                echo "\n-----";
                fclose($WRITE);
                self::run_quickstatements_api($batch_name, $batch_num); 
                sleep(30);
                // self::run_quickstatements_api($batch_name, $batch_num); 
                // exit;

                // if($batch_num == 1200) exit;
                $WRITE = Functions::file_open($this->tmp_batch_export, "w"); //initialize again                
                // sleep(3);
            }
        }
        fclose($WRITE);
        self::run_quickstatements_api($batch_name, $batch_num);
        /*
        QUICKSTATEMENTS_EOLTRAITS_TOKEN
        curl https://quickstatements.toolforge.org/api.php \
        -d action=import \
        -d submit=1 \
        -d username=EOLTraits \
        -d "batchname=THE NAME OF THE BATCH" \
        --data-raw 'token=$2y$10$hz0sJt78sWQZavuLhlvNBev9ACNiUK3zFaF9Mu.WJFURYPXb6LmNy' \
        --data-urlencode data@test.qs
        */
    }
    private function run_quickstatements_api($batch_name, $batch_num)
    {
        $batchname = "$batch_name $batch_num";
        $cmd = "curl https://quickstatements.toolforge.org/api.php";
        $cmd .= " -d action=import ";
        $cmd .= " -d submit=1 ";
        $cmd .= " -d username=EOLTraits ";
        $cmd .= " -d 'batchname=".$batchname."' ";
        $cmd .= " --data-raw 'token=".QUICKSTATEMENTS_EOLTRAITS_TOKEN."' ";
        $cmd .= " --data-urlencode data@".$this->tmp_batch_export." ";
        echo "\n$cmd\n";
        $output = shell_exec($cmd);
        echo "\n[$output]\n";
    }

    /* the one used for citation, manually run in terminal:
    curl https://quickstatements.toolforge.org/api.php \
            -d action=import \
            -d submit=1 \
            -d username=EOLTraits \
            -d "batchname=BATCH 1" \
            --data-raw 'token=$2y$10$hz0sJt78sWQZavuLhlvNBev9ACNiUK3zFaF9Mu.WJFURYPXb6LmNy' \
            --data-urlencode data@test.qs        
    */

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