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
        $this->sourcemd_api['search DOI'] = "https://sourcemd.toolforge.org/index_old.php?id=MY_DOI&doit=Check+source";
        $this->debug = array();
        // $this->tmp_batch_export = DOC_ROOT . "/tmp/temp_export.qs"; //moved

        
        /* export file for new citation in WikiData */
        $this->citation_export_file = DOC_ROOT. "temp/citation_export_file.qs";

    }
    function get_WD_entityID_for_DOI($doi)
    {
        $doi = str_ireplace("https://doi.org/", "", $doi);
        $doi = str_ireplace("http://doi.org/", "", $doi);
        $options = $this->download_options;
        $options['expire_seconds'] = false; //never expires. But set this to zero (0) if you've created a new WD item for a DOI.
        $url = str_replace("MY_DOI", urlencode($doi), $this->sourcemd_api['search DOI']);
        if($html = Functions::lookup_with_cache($url, $options)) {
            /*
                <p>Other sources with these identifiers exist:
                <a href='//www.wikidata.org/wiki/Q90856597' target='_blank'>Q90856597</a>
                </p>
                <p>Trying to update values of Q90856597; this will not change existing ones.</p>
            */
            if(preg_match("/www.wikidata.org\/wiki\/(.*?)\'/ims", $html, $arr)) {
                $id = $arr[1];
                return $id;
                exit("\n[$id\n");
            }
        }
    }
    private function generate_report_path($input)
    {
        $path = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/"; //generated from CypherQueryAPI.php
        if(!is_dir($path)) mkdir($path);
        $tmp = md5(json_encode($input));
        $path .= "$tmp/";
        if(!is_dir($path)) mkdir($path);
        return $path;
    }
    private function initialize_path($input)
    {
        $this->report_path = self::generate_report_path($input);

        /* Next are the 3 files to be generated: */

        $this->tmp_batch_export = $this->report_path . "/temp_export.qs";

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
        $final[] = 'Ancestry';
        $this->WRITE = Functions::file_open($this->taxonomic_mappings, "w");
        fwrite($this->WRITE, implode("\t", $final)."\n");

        // /* 
        $this->discarded_rows = $this->report_path."discarded_rows.tsv";
        if(file_exists($this->discarded_rows)) unlink($this->discarded_rows); //un-comment in real operation
        $final = array();
        $final[] = 'p.canonical';
        $final[] = 'p.page_id';
        $final[] = 't.eol_pk';
        $final[] = 'p.rank';
        $final[] = 'pred.name';
        $final[] = 'stage.name';
        $final[] = 'sex.name'; 
        $final[] = 'stat.name';
        $final[] = 'obj.name';
        $final[] = 't.measurement';
        $final[] = 'units.name';
        $final[] = 't.source';
        $final[] = 't.citation';
        $final[] = 'ref.literal';
        $WRITE = Functions::file_open($this->discarded_rows, "w");
        fwrite($WRITE, implode("\t", $final)."\n");
        fclose($WRITE);
        // */
    }

    function create_WD_traits($input)
    {   //print_r($input); exit("\nstop 2\n");
        self::initialize_path($input);

        // /* use identifier-map for taxon mapping - EOL page ID to WikiData entity ID
        if(!isset($this->taxonMap) && !isset($this->taxonMap_all)) {
            require_library('connectors/IdentifierMapAPI');
            $func = new IdentifierMapAPI(); //get_declared_classes(); will give you how to access all available classes
            $this->taxonMap = $func->read_identifier_map_to_var(array("resource_id" => 1072));
            $this->taxonMap_all = $func->read_identifier_map_to_var(array("resource_id" => 'all'));
            echo "\ntaxonMap: ".count($this->taxonMap)."\n";
        }
        // */

        // /* lookup spreadsheet for mapping
        $this->map = self::get_WD_entity_mappings();
        // */
        
        // /* report file to process
        $this->tsv_file = $this->report_path."/".$input["trait kind"]."_qry.tsv"; //exit("\n".$this->tsv_file."\n");
        // */

        $total = shell_exec("wc -l < ".escapeshellarg($this->tsv_file)); $total = trim($total);

        $i = 0;
        foreach(new FileIterator($this->tsv_file) as $line => $row) { $i++; echo $this->progress; echo "\n$i of $total traits\n";
            // $row = Functions::conv_to_utf8($row);
            if($i == 1) $fields = explode("\t", $row);
            else {

                // if($i >= 168104 && $i <= 168104+5000) {}      //403648 running... caching...
                // else continue;
                // if($i >= 168104+5000 && $i <= 168104+5000+5000) {}      //403648 running... caching...
                // else continue;
                // if($i >= 168104+5000+5000 && $i <= 168104+5000+5000+5000) {}      //403648 running... caching...
                // else continue;
                // if($i >= 168104+5000+5000+5000 && $i <= 168104+5000+5000+5000+5000) {}      //403648 running... caching...
                // else continue;
                // if($i >= 168104+5000+5000+5000+5000) {}      //403648 running... caching...
                // else continue;


                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = $tmp[$k]; $k++; }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit("\nelix1\n");
                if($rec['pred.name'] && $rec['obj.name']) { //$rec['p.canonical'] && 
                    self::write_trait_2wikidata($rec, $input['trait kind']);
                }
                else {
                    //discarded rows
                    $WRITE = Functions::file_open($this->discarded_rows, "a");
                    fwrite($WRITE, implode("\t", $rec)."\tdiscard obj."."\n"); fclose($WRITE);
                }
                // if($i >= 20) break; //debug
            }
        }
        echo "\nreport path: ".$input["trait kind"].":"."$this->report_path\n";
        self::show_totals();

        /* un-comment in real operation. Now I commented bec. I want to check first the big export file first before proceeding.
        self::divide_exportfile_send_2quickstatements();
        */
    }
    private function write_trait_2wikidata($rec, $trait_kind)
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
        Array(
            [p.canonical] => Acantoluzarida
            [p.page_id] => 73906
            [pred.name] => habitat
            [stage.name] => adult
            [sex.name] => 
            [stat.name] => 
            [obj.name] => litter layer
            [t.measurement] => 
            [units.name] => 
            [t.source] => https://doi.org/10.2307/3503472
            [t.citation] => L. Desutter-Grandcolas. 1995. Toward the Knowledge of the Evolutionary Biology of Phalangopsid Crickets (Orthoptera: Grylloidea: Phalangopsidae): Data, Questions and Evolutionary Scenarios. Journal of Orthoptera Research, No. 4 (Aug., 1995), pp. 163-175
            [ref.literal] => 
        )*/
        // print_r($rec); exit("\nstop 1\n");

        /* Generally just exclude these taxa:
        Also, please remove the records for the following taxon. It is mismapped on EOL, and the taxon we want is not in WikiData. We could create it, but I can't find a good external reference for it, so I think it's best to skip it for now.
        Angolania mira 46777128 Rudebeckia mira Q2095315 species of insect identifier-map

        Also, please discard the data records for the following taxa. These WikiData nodes are mismapped on EOL, and the correct taxa are not in WikiData.
        Pacifica 44068029 Tarenna subg. Pacifica Q93182914 identifier-map
        Vesta 4220900 Vesta Q95742196 identifier-map 

        Also, please discard the data records for this one. There is no good match on wikidata:
        Macroglossinae 51482088 Macroglossinae Q134858 subfamily of insects name search thru identifier-map

        Also, please remove the records associated with these taxa:
        47071476, 69177,...
        */
        $text_file = $this->report_not_taxon_or_no_wikidata;
        if(in_array($rec['p.page_id'], array(46777128, 44068029, 4220900, 51482088, 47071476, 69177, 94325, 53448, 46793443, 58457, 
            4774732, 19758, 250532, 60994119, 60992489))) {
            $str = implode("\t", array($rec['p.canonical'], $rec['p.page_id'], "***"));
            self::write_2text_file($text_file, $str); //."excluded record***"
            return;
        }
        // /* stop node query
        if($trait_kind == "inferred_trait") {
            if($t_eol_pk = $rec['t.eol_pk']) {}
            else {
                print_r($rec);
                exit("\n[$trait_kind]\nNo t.eol_pk\n");
            }
            if(isset($this->stop_node_query[$t_eol_pk]) && self::rank_is_above_species($rec['p.rank'])) {
                $WRITE = Functions::file_open($this->discarded_rows, "a");
                fwrite($WRITE, implode("\t", $rec)."\tstop_node"."\n"); fclose($WRITE);
                return;
            }    
        }
        // */
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

            $final = array();
            $final['taxon_entity'] = $taxon_entity_id;

            $discarded_already_YN = false;
            if($val = @$this->map['measurementTypes'][$rec["pred.name"]]["property"]) {
                if($val != "DISCARD") $final['predicate_entity'] = $val;
                else {
                    $WRITE = Functions::file_open($this->discarded_rows, "a");
                    fwrite($WRITE, implode("\t", $rec)."\tdiscard pred."."\n"); fclose($WRITE);
                    $discarded_already_YN = true;
                }                
            }
            else {
                echo "\nUndefined pred.name: [".$rec["pred.name"]."] \n";
                $this->debug['undefined measurementTypes pred.name'][$rec["pred.name"]] = '';

                $WRITE = Functions::file_open($this->discarded_rows, "a");
                fwrite($WRITE, implode("\t", $rec)."\tundef. pred."."\n"); fclose($WRITE);
                $discarded_already_YN = true;
            }
            
            if($val = @$this->map['measurementValues'][$rec["obj.name"]]["wikiData term"]) {
                if($val != "DISCARD") $final['object_entity'] = $val;
                else {
                    if(!$discarded_already_YN) {
                        $WRITE = Functions::file_open($this->discarded_rows, "a");
                        fwrite($WRITE, implode("\t", $rec)."\tdiscard obj."."\n"); fclose($WRITE);
                    }
                }
            }
            else {
                echo "\nUndefined obj.name: [".$rec["obj.name"]."] \n";
                $this->debug['undefined measurementValues obj.name'][$rec["obj.name"]] = '';
                if(!$discarded_already_YN) {
                    $WRITE = Functions::file_open($this->discarded_rows, "a");
                    fwrite($WRITE, implode("\t", $rec)."\tundef obj."."\n"); fclose($WRITE);                
                }
            }
            
            // /* this block prevents from running ruby all the time.
            if($title = $this->is_the_title) {}
            else {
                $title = self::parse_citation_using_anystyle($rec['t.citation'], 'title'); //dito ginamit
                $title = self::manual_fix_title($title);
                $this->is_the_title = $title;
            }
            if(!$title) exit("\nShould not go here...\n");
            // */

            /* when to use 'published in' ? TODO --> right now Eli did it manually e.g. 'published in' https://www.wikidata.org/wiki/Q116180473
            $final['P1433'] = self::get_WD_obj_using_string($title, 'entity_id'); //published in --- was not advised to use for now
            */

            /* The property to connect taxon to publication: If practical, "stated in" (P248) for regular records 
            and "inferred from" (P3452) for branch-painted records. 
            Eli, the corresponding part of your queries is [:trait|inferred_trait]. 
            If the queries were separated into a version using [:trait] and one using [:inferred_trait], 
            we could distinguish between regular records (P248) and branch painted records (P3452). */
            /* orig
            if    ($trait_kind == "trait")          $final['P248']  = self::get_WD_obj_using_string($title, 'entity_id'); //"stated in" (P248)
            elseif($trait_kind == "inferred_trait") $final['P3452'] = self::get_WD_obj_using_string($title, 'entity_id'); //"inferred from" (P3452)
            else exit("\nUndefined trait_kind.\n");
            */

            // /* new scheme
            $citation_WD_id = self::get_WD_id_of_citation($rec);
            if    ($trait_kind == "trait")          $final['P248']  = $citation_WD_id; //"stated in" (P248)
            elseif($trait_kind == "inferred_trait") $final['P3452'] = $citation_WD_id; //"inferred from" (P3452)
            else exit("\nUndefined trait_kind.\n");
            // */

            if($final['taxon_entity'] && @$final['predicate_entity'] && @$final['object_entity']) {
                self::create_WD_taxon_trait($final);                    //writes export_file.qs
                self::write_taxonomic_mapping($rec, $wikidata_obj);     //writes taxonomic_mappings_for_review.tsv
            }
        }
        else echo "\nNot a taxon: [".$rec['p.canonical']."]\n";
        // print_r($final); return;
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
        
        $citation_obj = self::parse_citation_using_anystyle($citation, 'all');
        if($ret = self::does_title_exist_in_wikidata($citation_obj, $citation)) { //orig un-comment in real operation
            print_r($ret);
            return $ret['wikidata_id'];
        }
        else {
            $title = $citation_obj[0]->title[0];
            $title = self::manual_fix_title($title);
            echo "\nTitle does not exist (A). [$title]\n";
            self::create_WD_reference_item($citation_obj, $citation);
        }        
    }
    function is_instance_of_taxon($taxon)
    {
        echo "\nSearching taxon... [$taxon]\n";
        $ret = self::get_WD_obj_using_string($taxon); //print_r($ret); exit;
        foreach($ret->search as $obj) {
            if($wikidata_id = @$obj->id) { # e.g. Q56079384
                // print_r($obj); exit;
                $found = $obj->display->label->value;
                if($found != $taxon) continue; //string should match to proceed
                echo "\npossible ID for '$taxon' '$found': [$wikidata_id]\n";
                if($taxon_obj = self::get_WD_obj_using_id($wikidata_id, 'all')) { //print_r($taxon_obj->entities->$wikidata_id->labels); exit;
                    $instance_of = @$taxon_obj->entities->$wikidata_id->claims->P31[0]->mainsnak->datavalue->value->id; //exit("\n[$instance_of]\n");
                    if    ($instance_of == 'Q16521')  return $obj; //$wikidata_id; # instance_of -> taxon
                    elseif($instance_of == 'Q310890') return $obj; //$wikidata_id; # instance_of -> monotypic taxon
                    elseif($instance_of) { //meaning not blank
                        $label = self::get_WD_obj_using_id($instance_of, 'label');
                        echo("\nIs instance of what?: [$label]\n");
                        if(stripos($label, "taxon") !== false) return $obj; //string is found
                        // else return false; --- don't stop here, let the loop finish.
                    }
                }
            }    
        } //foreach
        echo "\nNot found in WikiData\n";
        return false;
    }
    private function does_title_exist_in_wikidata($citation_obj, $citation)
    {
        $title = $citation_obj[0]->title[0];
        $title = self::manual_fix_title($title); #important
        echo "\nsearch title: [$title]\n";
        $url = str_replace("MY_TITLE", urlencode($title), $this->wikidata_api['search string']);
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24;
        if($json = Functions::lookup_with_cache($url, $options)) { // print("\n$json\n");
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
        elseif(substr($str, -(strlen("Oriental Swallowtail Moths (Lepidoptera: Epicopeiidae"))) == "Oriental Swallowtail Moths (Lepidoptera: Epicopeiidae") return $str.")";
        elseif(substr($str, -(strlen("Valdivian Archaic Moths (Lepidoptera: Heterobathmiidae"))) == "Valdivian Archaic Moths (Lepidoptera: Heterobathmiidae") return $str.")";
        elseif(substr($str, -(strlen("xxxx"))) == "xxxx") return $str.")";
        elseif(substr($str, -(strlen("xxxx"))) == "xxxx") return $str.")";        
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
        if($publishers = @$obj->publisher)           $rows = self::prep_for_adding($publishers, 'P123', $rows, "publisher");
        if($place_of_publications = @$obj->location) $rows = self::prep_for_adding($place_of_publications, 'P291', $rows, "place of publication");
        if($pages = @$obj->pages)                    $rows = self::prep_for_adding($pages, 'P304', $rows, "page(s)"); #ok
        // if($issues = @$obj->issue)                   $rows = self::prep_for_adding($issues, 'P433', $rows); #ok
        if($volumes = @$obj->volume)                 $rows = self::prep_for_adding($volumes, 'P478', $rows, "volume"); #ok
        if($publication_dates = @$obj->date)         $rows = self::prep_for_adding($publication_dates, 'P577', $rows, "publication date"); #ok
        if($chapters = @$obj->chapter)               $rows = self::prep_for_adding($chapters, 'P792', $rows, "chapter"); //No 'chapter' parsed by AnyStyle. Eli should do his own parsing.
        if($titles = @$obj->title)                   $rows = self::prep_for_adding($titles, 'P1476', $rows, "title"); #ok

        // /* Eli's initiative, but was given a go signal -> property 'type of reference' (P3865)
        if($type = @$obj->type)                      $rows = self::prep_for_adding(array($type), 'P3865', $rows); #ok
        // */

        // /* Eli's initiative but close to Jen's "published in" (P1433) proposal
        if($containers = @$obj->{"container-title"})      $rows = self::prep_for_adding($containers, 'P1433', $rows, "published in"); #ok
        // */

        // Others:
        if($dois = @$obj->doi)                       $rows = self::prep_for_adding($dois, 'P356', $rows, "DOI"); #ok
        /* seems no instructions to use this yet:
        if($reference_URLs = @$obj->url)             $rows = self::prep_for_adding($reference_URLs, 'P854', $rows);
        */

        /* Eli's choice: will take Jen's approval first -> https://www.wikidata.org/wiki/Property:P1683 -> WikiData says won't use it here.
        $rows = self::prep_for_adding(array($citation), 'P1683', $rows);
        */

        print_r($rows);
        $WRITE = Functions::file_open($this->citation_export_file, "w");
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
    private function prep_for_adding($recs, $property, $rows, $comment = "")
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

                $rows[] = "LAST|$property|$lang" . '"'.$val.'"' . " /* $comment */";
            }
        }
        return $rows;
    }
    private function parse_citation_using_anystyle($citation, $what)
    {
        echo("\n----------\nthis runs ruby...[$what]\n----------\n"); //comment in real operation
        $json = shell_exec($this->anystyle_parse_prog . ' "'.$citation.'"');
        $json = substr(trim($json), 1, -1); # remove first and last char
        $json = str_replace("\\", "", $json); # remove "\" from converted json from Ruby
        $obj = json_decode($json); //print_r($obj);
        if($what == 'all') return $obj;
        elseif($what == 'title') {
            if($val = @$obj[0]->title[0]) return $val;
            else {
                echo "\n---------- no title -------------\n";
                // print_r($obj); 
                echo "\ncitation:[$citation]\ntitle:[$what]\n";
                echo "\n---------- end -------------\n";
                return "-no title-";
            }
        }
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
            else {
                if($property = @$item[2]) $final[$item[0]] = array($labels['a'] => $item[1], $labels['b'] => $property);
            }
        }
        // print_r($final); exit;
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
        // /*
        // $final[] = self::get_pipe_delimited_ancestry($wikidata_obj->id); //working OK
        $final[] = "-ancestry-";
        // */
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
        /* start 2nd */
        // Callopisma	46719434
        elseif($page_id == 46719434) return self::fix_further($page_id, $canonical, "Q18582462");

        /* start 3rd
        Hi Eli,
        Just a couple of taxonomic mapping corrections for this one:
        EOL name pageID WikiData name oldID Description Mapped newID
        Also, for the following, your mappings are fine, but I had to fix the wikidata parent relationships. 
        Although the descriptions say "species of orthopterans," they were actually attached to a crustacean parent:
        Arachnomimus nietneri 605124 Arachnopsis nietneri Q63249464 species of orthopterans identifier-map 
        Arachnopsita cavicola 605061 Arachnopsis cavicola Q63249466 species of orthopterans identifier-map */
        elseif($page_id == 36073 && $canonical == "Rumea") return self::fix_further($page_id, $canonical, "Q13985115");
        elseif($page_id == 605108 && $canonical == "Laranda annulata") return self::fix_further($page_id, $canonical, "Q116520562");
        
        /* start 4th
        Hi Eli, Here are the corrections for this batch:
        EOL name pageID WikiData name ID Description Mapped newID */
        elseif($page_id == 8110438 && $canonical == "Anchieta") return self::fix_further($page_id, $canonical, "Q17378097");
        elseif($page_id == 4129498 && $canonical == "Acmonotus") return self::fix_further($page_id, $canonical, "Q116590983");
        elseif($page_id == 610427 && $canonical == "Conocephalus borneensis") return self::fix_further($page_id, $canonical, "Q10458337");
        elseif($page_id == 46941980 && $canonical == "Conocephalus dubius") return self::fix_further($page_id, $canonical, "Q10458358");
        elseif($page_id == 8110538 && $canonical == "Nivella") return self::fix_further($page_id, $canonical, "Q18116894");
        
        // /* start 5th
        // Here are the corrections for this sample:
        // EOL name pageID WikiData name ID Description Mapped new ID
        elseif($page_id == 63424 && $canonical == "Aptera") return self::fix_further($page_id, $canonical, "Q10416319");
        elseif($page_id == 106546 && $canonical == "Moluchia") return self::fix_further($page_id, $canonical, "Q10587867");
        elseif($page_id == 13289391 && $canonical == "Cladodes") return self::fix_further($page_id, $canonical, "Q10748947");
        elseif($page_id == 63535 && $canonical == "Chorisia") return self::fix_further($page_id, $canonical, "Q116653554");
        elseif($page_id == 613523) return self::fix_further($page_id, $canonical, "Q11842489");
        elseif($page_id == 106616) return self::fix_further($page_id, $canonical, "Q18117252");
        elseif($page_id == 47113898) return self::fix_further($page_id, $canonical, "Q2899669");
        elseif($page_id == 63415) return self::fix_further($page_id, $canonical, "Q3014241");        
        // */

        // /* start 6th
        // Hi Eli, here are the corrections for the latest batch: 
        // EOL name pageID WikiData name ID Description Mapped newID
        elseif($page_id == 33347) return self::fix_further($page_id, $canonical, "Q1500089");        
        elseif($page_id == 42152) return self::fix_further($page_id, $canonical, "Q577045");        
        elseif($page_id == 42159) return self::fix_further($page_id, $canonical, "Q15402210");        
        elseif($page_id == 46324029) return self::fix_further($page_id, $canonical, "Q28929960");        
        elseif($page_id == 1019571) return self::fix_further($page_id, $canonical, "Q22718");        
        elseif($page_id == 85361) return self::fix_further($page_id, $canonical, "Q14340053");                
        elseif($page_id == 80873) return self::fix_further($page_id, $canonical, "Q2746790");        
        // */

        // /* start 7th
        // EOL name pageID WikiData name ID Description Mapped newID
        elseif($page_id == 70351) return self::fix_further($page_id, $canonical, "Q10295328");        
        elseif($page_id == 95899) return self::fix_further($page_id, $canonical, "Q10328362");        
        elseif($page_id == 59667) return self::fix_further($page_id, $canonical, "Q10349191");        
        elseif($page_id == 96115) return self::fix_further($page_id, $canonical, "Q10355327");        
        elseif($page_id == 47125964) return self::fix_further($page_id, $canonical, "Q10382280");        
        elseif($page_id == 78228) return self::fix_further($page_id, $canonical, "Q10382761");        
        elseif($page_id == 93022) return self::fix_further($page_id, $canonical, "Q10390097");        
        elseif($page_id == 52973) return self::fix_further($page_id, $canonical, "Q10554573");        
        elseif($page_id == 38415) return self::fix_further($page_id, $canonical, "Q10638147");        
        elseif($page_id == 66129) return self::fix_further($page_id, $canonical, "Q10766916");        
        elseif($page_id == 48941) return self::fix_further($page_id, $canonical, "Q10794248");        
        elseif($page_id == 67836) return self::fix_further($page_id, $canonical, "Q10794751");        
        elseif($page_id == 47112260) return self::fix_further($page_id, $canonical, "Q10795249");        
        elseif($page_id == 96435) return self::fix_further($page_id, $canonical, "Q113474262");        
        elseif($page_id == 49082) return self::fix_further($page_id, $canonical, "Q116741523");        
        elseif($page_id == 504250) return self::fix_further($page_id, $canonical, "Q116741907");        
        elseif($page_id == 505096) return self::fix_further($page_id, $canonical, "Q116742047");        
        elseif($page_id == 96366) return self::fix_further($page_id, $canonical, "Q116754894");        
        elseif($page_id == 20127) return self::fix_further($page_id, $canonical, "Q116755186");        
        elseif($page_id == 75073) return self::fix_further($page_id, $canonical, "Q116765826");        
        elseif($page_id == 59544) return self::fix_further($page_id, $canonical, "Q116765894");        
        elseif($page_id == 19348) return self::fix_further($page_id, $canonical, "Q11841399");        
        elseif($page_id == 20474) return self::fix_further($page_id, $canonical, "Q1310909");        
        elseif($page_id == 69434) return self::fix_further($page_id, $canonical, "Q1312949");        
        elseif($page_id == 48480) return self::fix_further($page_id, $canonical, "Q1314183");        
        elseif($page_id == 504065) return self::fix_further($page_id, $canonical, "Q13380125");        
        elseif($page_id == 526546) return self::fix_further($page_id, $canonical, "Q13433763");        
        elseif($page_id == 938537) return self::fix_further($page_id, $canonical, "Q13471348");        
        elseif($page_id == 51531833) return self::fix_further($page_id, $canonical, "Q13528408");        
        elseif($page_id == 48901) return self::fix_further($page_id, $canonical, "Q13614984");        
        elseif($page_id == 47173024) return self::fix_further($page_id, $canonical, "Q13635467");        
        elseif($page_id == 60211) return self::fix_further($page_id, $canonical, "Q1366893");        
        elseif($page_id == 60199) return self::fix_further($page_id, $canonical, "Q1367031");        
        elseif($page_id == 46790277) return self::fix_further($page_id, $canonical, "Q1368348");        
        elseif($page_id == 34000) return self::fix_further($page_id, $canonical, "Q1375382");        
        elseif($page_id == 47750) return self::fix_further($page_id, $canonical, "Q13845641");        
        elseif($page_id == 48437) return self::fix_further($page_id, $canonical, "Q13845696");        
        elseif($page_id == 47108535) return self::fix_further($page_id, $canonical, "Q13845838");        
        elseif($page_id == 19185) return self::fix_further($page_id, $canonical, "Q13846252");        
        elseif($page_id == 17444) return self::fix_further($page_id, $canonical, "Q13846329");        
        elseif($page_id == 33654) return self::fix_further($page_id, $canonical, "Q13846373");        
        elseif($page_id == 46794143) return self::fix_further($page_id, $canonical, "Q13846399");        
        elseif($page_id == 48019) return self::fix_further($page_id, $canonical, "Q13847367");        
        elseif($page_id == 47914) return self::fix_further($page_id, $canonical, "Q13847396");        
        elseif($page_id == 94758) return self::fix_further($page_id, $canonical, "Q13860318");        
        elseif($page_id == 51542262) return self::fix_further($page_id, $canonical, "Q13861512");        
        elseif($page_id == 46794281) return self::fix_further($page_id, $canonical, "Q13862024");        
        elseif($page_id == 47173020) return self::fix_further($page_id, $canonical, "Q13863677");        
        elseif($page_id == 48045) return self::fix_further($page_id, $canonical, "Q13863767");        
        elseif($page_id == 17332) return self::fix_further($page_id, $canonical, "Q13891892");        
        elseif($page_id == 94841) return self::fix_further($page_id, $canonical, "Q13900127");        
        elseif($page_id == 47286) return self::fix_further($page_id, $canonical, "Q14072134");        
        elseif($page_id == 53106) return self::fix_further($page_id, $canonical, "Q140725");        
        elseif($page_id == 64389) return self::fix_further($page_id, $canonical, "Q142172");        
        elseif($page_id == 186119) return self::fix_further($page_id, $canonical, "Q14515049");        
        elseif($page_id == 33183) return self::fix_further($page_id, $canonical, "Q14839546");        
        elseif($page_id == 88845) return self::fix_further($page_id, $canonical, "Q15911567");        
        elseif($page_id == 20288) return self::fix_further($page_id, $canonical, "Q1592101");        
        elseif($page_id == 47141435) return self::fix_further($page_id, $canonical, "Q15958275");        
        elseif($page_id == 47114988) return self::fix_further($page_id, $canonical, "Q16460123");        
        elseif($page_id == 96397) return self::fix_further($page_id, $canonical, "Q1755645");        
        elseif($page_id == 50683) return self::fix_further($page_id, $canonical, "Q1755652");        
        elseif($page_id == 64427) return self::fix_further($page_id, $canonical, "Q1756618");        
        elseif($page_id == 18973) return self::fix_further($page_id, $canonical, "Q1758756");        
        elseif($page_id == 20669) return self::fix_further($page_id, $canonical, "Q1762176");        
        elseif($page_id == 32854) return self::fix_further($page_id, $canonical, "Q1762462");        
        elseif($page_id == 19072) return self::fix_further($page_id, $canonical, "Q1762543");        
        elseif($page_id == 32696) return self::fix_further($page_id, $canonical, "Q1765121");        
        elseif($page_id == 46795449) return self::fix_further($page_id, $canonical, "Q1766048");        
        elseif($page_id == 48294) return self::fix_further($page_id, $canonical, "Q18095508");        
        elseif($page_id == 46728) return self::fix_further($page_id, $canonical, "Q18095596");        
        elseif($page_id == 1101) return self::fix_further($page_id, $canonical, "Q184616");        
        elseif($page_id == 96058) return self::fix_further($page_id, $canonical, "Q18521654");        
        elseif($page_id == 48398) return self::fix_further($page_id, $canonical, "Q18522086");        
        elseif($page_id == 68900) return self::fix_further($page_id, $canonical, "Q18522872");        
        elseif($page_id == 53489) return self::fix_further($page_id, $canonical, "Q19587087");        
        elseif($page_id == 52755861) return self::fix_further($page_id, $canonical, "Q21225997");        
        elseif($page_id == 49833917) return self::fix_further($page_id, $canonical, "Q21441018");        
        elseif($page_id == 35726) return self::fix_further($page_id, $canonical, "Q2296986");        
        elseif($page_id == 47141346) return self::fix_further($page_id, $canonical, "Q2338910");        
        elseif($page_id == 19925) return self::fix_further($page_id, $canonical, "Q2375613");        
        elseif($page_id == 66918) return self::fix_further($page_id, $canonical, "Q255793");        
        elseif($page_id == 58409) return self::fix_further($page_id, $canonical, "Q261238");        
        elseif($page_id == 48732) return self::fix_further($page_id, $canonical, "Q27487");        
        elseif($page_id == 48709) return self::fix_further($page_id, $canonical, "Q290858");        
        elseif($page_id == 34507) return self::fix_further($page_id, $canonical, "Q3012860");        
        elseif($page_id == 52757390) return self::fix_further($page_id, $canonical, "Q3026314");        
        elseif($page_id == 19053) return self::fix_further($page_id, $canonical, "Q3054035");        
        elseif($page_id == 19088) return self::fix_further($page_id, $canonical, "Q311218");        
        elseif($page_id == 52755763) return self::fix_further($page_id, $canonical, "Q33193672");        
        elseif($page_id == 34351) return self::fix_further($page_id, $canonical, "Q3528716");        
        elseif($page_id == 50695) return self::fix_further($page_id, $canonical, "Q3546350");        
        elseif($page_id == 48432) return self::fix_further($page_id, $canonical, "Q3552124");        
        elseif($page_id == 19187) return self::fix_further($page_id, $canonical, "Q3841274");        
        elseif($page_id == 17454) return self::fix_further($page_id, $canonical, "Q420732");        
        elseif($page_id == 72340) return self::fix_further($page_id, $canonical, "Q45083329");        
        elseif($page_id == 57050) return self::fix_further($page_id, $canonical, "Q4735447");        
        elseif($page_id == 46659) return self::fix_further($page_id, $canonical, "Q4748282");        
        elseif($page_id == 59196) return self::fix_further($page_id, $canonical, "Q4754294");        
        elseif($page_id == 47459) return self::fix_further($page_id, $canonical, "Q4861441");        
        elseif($page_id == 51532738) return self::fix_further($page_id, $canonical, "Q5019528");        
        elseif($page_id == 74194) return self::fix_further($page_id, $canonical, "Q5099175");        
        elseif($page_id == 68213) return self::fix_further($page_id, $canonical, "Q5184177");        
        elseif($page_id == 94571) return self::fix_further($page_id, $canonical, "Q5188748");        
        elseif($page_id == 20342) return self::fix_further($page_id, $canonical, "Q522255");        
        elseif($page_id == 47136755) return self::fix_further($page_id, $canonical, "Q5227610");        
        elseif($page_id == 52756989) return self::fix_further($page_id, $canonical, "Q5315942");        
        elseif($page_id == 93658) return self::fix_further($page_id, $canonical, "Q5376348");        
        elseif($page_id == 47119522) return self::fix_further($page_id, $canonical, "Q5377547");        
        elseif($page_id == 34289) return self::fix_further($page_id, $canonical, "Q5383575");        
        elseif($page_id == 37672) return self::fix_further($page_id, $canonical, "Q5405867");        
        elseif($page_id == 51533327) return self::fix_further($page_id, $canonical, "Q5406528");        
        elseif($page_id == 33895) return self::fix_further($page_id, $canonical, "Q5414644");        
        elseif($page_id == 93636) return self::fix_further($page_id, $canonical, "Q5558933");        
        elseif($page_id == 40369) return self::fix_further($page_id, $canonical, "Q5567274");        
        elseif($page_id == 35099) return self::fix_further($page_id, $canonical, "Q5573498");        
        elseif($page_id == 48495) return self::fix_further($page_id, $canonical, "Q591966");        
        elseif($page_id == 48488) return self::fix_further($page_id, $canonical, "Q6521398");        
        elseif($page_id == 20463) return self::fix_further($page_id, $canonical, "Q6696928");        
        elseif($page_id == 60622) return self::fix_further($page_id, $canonical, "Q6725468");        
        elseif($page_id == 58500) return self::fix_further($page_id, $canonical, "Q6823514");        
        elseif($page_id == 35014) return self::fix_further($page_id, $canonical, "Q6868917");        
        elseif($page_id == 58116) return self::fix_further($page_id, $canonical, "Q6901524");        
        elseif($page_id == 60994095) return self::fix_further($page_id, $canonical, "Q6963796");        
        elseif($page_id == 57093) return self::fix_further($page_id, $canonical, "Q6992942");        
        elseif($page_id == 48850) return self::fix_further($page_id, $canonical, "Q7075694");        
        elseif($page_id == 93786) return self::fix_further($page_id, $canonical, "Q7116123");        
        elseif($page_id == 34052) return self::fix_further($page_id, $canonical, "Q7131966");        
        elseif($page_id == 60994373) return self::fix_further($page_id, $canonical, "Q7135170");        
        elseif($page_id == 35679) return self::fix_further($page_id, $canonical, "Q7189540");        
        elseif($page_id == 47136024) return self::fix_further($page_id, $canonical, "Q7402621");        
        elseif($page_id == 94016) return self::fix_further($page_id, $canonical, "Q7563144");        
        elseif($page_id == 68287) return self::fix_further($page_id, $canonical, "Q7660579");        
        elseif($page_id == 48746) return self::fix_further($page_id, $canonical, "Q7901166");        
        elseif($page_id == 68512) return self::fix_further($page_id, $canonical, "Q8045501");        
        // */


        else { //orig
            if($ret = @$this->taxonMap[$page_id]) {
                /* never use this since p.canonical in query sometimes really is blank. And identifier-map can do the connection.
                if($canonical == $ret['c']) {
                    if($obj = self::get_WD_obj_using_id($ret['i'], 'all')) return array($ret['i'], $obj);
                }
                else exit("\nInvestigate not equal: [$canonical] [".$ret['c']."]\n");
                */

                /* use to refresh WikiData desc, etc.
                --- Also, for the following, your mappings are fine, but I had to fix the wikidata parent relationships. Although the descriptions say "species of orthopterans," they were actually attached to a crustacean parent:
                Arachnomimus nietneri 605124 Arachnopsis nietneri Q63249464 species of orthopterans identifier-map 
                Arachnopsita cavicola 605061 Arachnopsis cavicola Q63249466 species of orthopterans identifier-map

                --- The following mappings are fine now, but I had to fix things on WikiData:
                Acmonotus incusifer 8110730 Acmonotus incusifer Q2780863 species of insect identifier-map fixed parent on WikiData
                Nigrimacula xizangensis 46942518 Nigrimacula xizangensis Q98515360 species of fungus name search thru identifier-map fixed description on WikiData

                --- Furthermore, I fixed the WikiData parent relationships and/or descriptions for the following taxa. Their mappings are now fine:
                Biolleya alaris 1075434 Biolleya alaris Q10429764 species of mantises identifier-map
                Chorisia reducta 1074475 Chorisia reducta Q10450998 species of insect identifier-map
                Pseudoplatia 106725 Pseudoplatia Q14192681 genus of insects identifier-map
                Pseudoplatia atra 1076363 Pseudoplatia atra Q10644039 species of insect identifier-map
                Glyptotermes satsumensis 12027215 Glyptotermes satsumensis Q114661130 species of insect name search thru identifier-map
                Reticulitermes flaviceps 12027027 Reticulitermes flaviceps Q114661162 species of insect name search thru identifier-map
                Periaciculitermes 46938881 Periaciculitermes Q113480781 genus of insects name search thru identifier-map
                Acanthotermes 3837089 Acanthotermes Q114797700 genus of insect name search thru identifier-map
                Nasutitermes parvonasutus 12027274 Nasutitermes parvonasutus Q114661268 species of insect name search thru identifier-map
                Biolleya 106650 Biolleya Q18117214 genus of mantises identifier-map 
                
                --- For the following taxa, I had to fix the parent mappings on wikidata. They are fine now:
                750237, 750234,...
                
                And I had to fix parent relationships for the following taxa on wikiData, so their mappings are now fine:
                
                */
                if(in_array($page_id, array(605124,605061,8110730,46942518,1075434,1074475,106725,1076363,12027215,12027027,
                    46938881,3837089,12027274,106650, 750237, 750234, 750235, 752307, 750236, 751037, 752306, 751036, 750233, 
                    748552, 751034, 752305, 752304, 750232, 750231, 748910, 752302 
                    /* processed already - row 3
                    , 358896, 360755, 358900, 358899, 358898, 30590798, 377869, 556910, 556911, 556912, 551240, 551241, 551242, 551248, 551249, 
                    551250, 551251, 555189, 934145, 46780822, 46780821, 564333, 30561671, 392421, 412763, 412764, 412768, 412770, 48888452, 
                    504950, 477553, 954445, 953190, 959429, 60993017, 49826113, 49825866, 49825865, 52755932, 60776369, 618515, 481992, 618514, 
                    615798, 615797, 618161, 608210, 618158, 481310, 618159, 618157, 617959, 617961, 481590, 616020, 615796, 615786, 615785, 615775, 
                    615774, 615776, 615816, 615815, 615791, 615790, 615792, 615825, 615824, 615821, 615820, 615819, 618516, 618630, 618519, 618632, 
                    618631, 618634, 481991, 618633, 618636, 618635, 618638, 618637, 618640, 618639, 618641, 618644, 618643, 618648, 618647, 618646, 
                    618645, 618651, 618649, 618650, 618652, 608212, 618654, 618653, 618656, 618655, 618657, 618659, 618658, 618662, 618661, 618660, 
                    618664, 618663, 618666, 618665, 618668, 618667, 618670, 618669, 618671, 618673, 618674, 618675, 608215, 618676, 618679, 618678, 
                    618677, 618681, 618680, 618683, 481990, 618682, 618685, 618684, 618687, 618686, 618689, 618688, 618690, 618694, 618693, 618691, 
                    481234, 481233, 613722, 613721, 613720, 481235, 481989, 613015, 613719, 481510, 618113, 618116, 618115, 618111, 481236, 616773, 
                    616772, 616764, 51858464, 484812, 484811, 468969 */
                    ))) $this->download_options['expire_seconds'] = true;

                if($obj = self::get_WD_obj_using_id($ret['i'], 'all')) {
                    $this->download_options['expire_seconds'] = false;
                    return array($ret['i'], $obj);
                }
                $this->download_options['expire_seconds'] = false;
            }    
        }
    }
    private function fix_further($page_id, $canonical, $new_WD_id)
    {
        $ret = array("i" => $new_WD_id, "c" => $canonical);
        $this->download_options['expire_seconds'] = true; //means cache expires now, same if value is 0 zero.
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
                if($val = @$obj->search[0]->id) {
                    echo "\nentity found for string: [$string]";
                    echo "\nentity ID found is: [".$obj->search[0]->id."]\n";
                    return $obj->search[0]->id;    
                }
                else {
                    print_r($obj);
                    echo("\n[".$string."]\nTitle not found. Investigate\n");
                }
            }
        }
        return false;
    }
    function get_WD_obj_using_id($wikidata_id, $what = 'all')
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
            elseif($what == 'DOI')   return @$obj->entities->$wikidata_id->claims->P356[0]->mainsnak->datavalue->value;
            elseif($what == 'label') return @$obj->entities->$wikidata_id->labels->en->value;
            else exit("\nERROR: Specify return item.\n");
        }
        else exit("\nShould not go here.\n");
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
            
            /* use this block to exclude already run: manually done
            if($i < 8) continue;
            */

            echo "\n".$row;
            fwrite($WRITE, $row."\n");
            if(($i % 3) == 0) { $batch_num++;
                echo "\n-----";
                fclose($WRITE);
                self::run_quickstatements_api($batch_name, $batch_num);
                echo "\nsleep 30 seconds...\n"; sleep(30);
                /* sometimes running it twice is needed to remove the error
                self::run_quickstatements_api($batch_name, $batch_num); 
                */

                // if($batch_num == 1200) exit;
                $WRITE = Functions::file_open($this->tmp_batch_export, "w"); //initialize again

                // break; //just the first 3 rows //debug only
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
        -d "batchname=for DOI 10.1111/j.1365-2311.1965.tb02304.x" \
        --data-raw 'token=$2y$10$hz0sJt78sWQZavuLhlvNBev9ACNiUK3zFaF9Mu.WJFURYPXb6LmNy' \
        --data-urlencode data@citation_export_file.qs
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
    function run_all_resources($spreadsheet, $task)
    {
        /* works ok if you don't need to format/clean the entire row.
        $file = Functions::file_open($this->text_path[$type], "r");
        while(!feof($file)) { $row = fgetcsv($file); }
        fclose($file);
        */
        // /* stop node query
        require_library('connectors/CypherQueryAPI');
        $resource_id = 'eol';
        $func = new CypherQueryAPI($resource_id);
        $input = array();
        $input["params"] = array();
        $input["type"] = "traits_stop_at";
        $input["per_page"] = 500; // 500 worked ok
        $this->stop_node_query = $func->get_traits_stop_at($input);
        echo "\nstop_node_query: ".count($this->stop_node_query)."\n"; //exit;
        unset($func);
        // exit("\n-end-\n");
        // */


        $spreadsheet = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/resources/".$spreadsheet;
        $total = shell_exec("wc -l < ".escapeshellarg($spreadsheet)); $total = trim($total);
        $i = 0;
        foreach(new FileIterator($spreadsheet) as $line_number => $line) { $i++; $this->progress = "\n$i of $total resources\n";
            if(!$line) continue;
            $row = str_getcsv($line);
            if(!$row) continue;
            if($i == 1) { $fields = $row; $count = count($fields); continue;}
            else { //main records
                /* during dev only
                if($i % 2 == 0) {echo "\n[EVEN]\n"; continue;} //even
                else {echo "\n[ODD]\n"; } //odd
                */

                $values = $row; $k = 0; $rec = array();
                foreach($fields as $field) { $rec[$field] = $values[$k]; $k++; }
                $rec = array_map('trim', $rec); //important step

                $this->is_the_title = false;

                // /* takbo
                $real_row = $i - 1;
                $this->real_row = $real_row;
                // if(in_array($real_row, array(1,2,4,5,6,7,8,9,10,11))) continue; //DONE ALREADY | row 5 ignore deltakey | 11 our very first
                //---------------------------------------------------------------
                // if(!in_array($real_row, array(1,2,4,6,7,8,9,10,11,13,14,15,16,17,18,19,20))) continue; //dev only  --- for testing
                // if(!in_array($real_row, array(20))) continue; //dev only  --- for testing

                // if(!in_array($real_row, array(11))) continue; //dev only  --- our very first
                // if(!in_array($real_row, array(3))) continue; //dev only  --- fpnas 198187
                // row 12 -- zero results for query by citation and source
                // if(!in_array($real_row, array(13,14,15,16,17,18,19,20))) continue; //dev only --  QuickStatements Done
                if(!in_array($real_row, array(21,22,23,24,25,26,27,28,29,30))) continue; //dev only -- ready for review, with ancestry
                // if(!in_array($real_row, array(31))) continue; // 7 connectors 403648
                echo "\nrow: $real_row\n";
                // */
                
                /* status
                rows 1,2,4,6,7,8,9,10,11,13,14,15,16,17,18,19,20 - all traits from these are now in WikiData.
                rows 3,21,22,23,24,25,26,27,28,29,30 - taxonomic corrections implemented, to be sent to QuickStatements.
                row 5 - will be ignored for now (delta-key).
                row 12 - was run but no records returned. Will investigate more. Will inform Jen. (1038 https://doi.org/10.2994/1808-9798(2008)3[58:HTBAAD]2.0.CO;2)
                row 31 - for taxonomic review
                */

                $paths = self::run_resource_traits($rec, $task);

                /*############################### START #####################################*/ //to do: can move to its own function
                if($task == 'generate trait reports') { //copy 2 folders to /rowNum_resourceID/
                    /*Array(
                    [0] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/cypher/26781a84311d6d09f25971b21516b796/
                    [1] => /opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/cypher/bf64239ace12e4bd48f16387713bc309/

                    trait path:          [/opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/cypher/95f89fd54344bb1630126a64b9cff1e3/]
                    inferred_trait path: [/opt/homebrew/var/www/eol_php_code/applications/content_server/resources_3/reports/cypher/da23f9319bb205e88bcdeab285f494d7/]
                    )*/
                    if($paths) { print_r($paths);
                        // https://doi.org/10.1007/978-1-4020-6359-6_3929
                        // http://doi.org/10.1098/rspb.2011.0134
                        $source = str_ireplace("https://", "", $rec['trait.source']);
                        $source = str_ireplace("http://", "", $source);
                        $source = str_ireplace("/", "_", $source);
                        
                        $destination = $real_row."_".$rec['r.resource_id']."_".$source;
                        $destination = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$destination; // exit("\ndestination: [$destination\n");
                        if(!is_dir($destination)) mkdir($destination);
                        else { //delete and re-create the destination folder
                            /* stripos search is used just to be sure you can safely remove that folder */
                            if(stripos($destination, "/reports/cypher/") !== false) recursive_rmdir($destination); //string is found
                            mkdir($destination);
                        }
                        foreach($paths as $path) {
                            $path = substr($path, 0, -1);
                            $cmd = "cp -R $path $destination"; //worked OK
                            $cmd .= " 2>&1"; // echo "\n[$cmd]\n";
                            shell_exec($cmd);
                            self::delete_folder_contents($path."/", array("inferred_trait_qry.tsv", "trait_qry.tsv", "export_file.qs"));
                        }    
                    }
                }
                /*############################### END #####################################*/
                // break; //process just first record
                // if($i >= 3) break; //debug only
            }
        }
        print_r($this->debug);
    }
    private function run_resource_traits($rec, $task)
    {   /*Array(
            [r.resource_id] => 1054
            [trait.source] => https://www.wikidata.org/entity/Q116263059
            [trait.citation] => McDermott, F. (1964). The Taxonomy of the Lampyridae (Coleoptera). Transactions of the American Entomological Society (1890-), 90(1), 1-72. Retrieved January 29, 2021, from http://www.jstor.org/stable/25077867
        )*/
        // print_r($rec); exit;
        if($rec['trait.source'] == 'https://www.wikidata.org/entity/Q116180473') return; //row 11 already ran. Our very first. Done QuickStatements OK
        if($rec['trait.source'] == 'https://www.wikidata.org/wiki/Q116180473') return; //row 11 already ran. Our very first. Done QuickStatements OK
        
        // /* good way to run 1 resource for investigation
        // if($rec['trait.source'] != 'https://www.wikidata.org/entity/Q116263059') return; //row 1                     QuickStatements done           
        // if($rec['trait.source'] != 'https://doi.org/10.2307/3503472') return; //row 2                                QuickStatements done
        // if($rec['trait.source'] != 'https://doi.org/10.1073/pnas.1907847116') return; //row 3                        running... fpnas 198187
        // if($rec['trait.source'] != 'https://doi.org/10.1007/978-1-4020-6359-6_1885') return; //row 4                 QuickStatements done
        // if($rec['trait.source'] != 'https://www.delta-intkey.com/britin/lep/www/endromid.htm') return; //row 5       will be ignored...
        // if($rec['trait.source'] != 'https://doi.org/10.1007/978-1-4020-6359-6_3929') return; //row 6 and 7,8,9,10    QuickStatements DONE...
        // if($rec['trait.source'] != 'https://doi.org/10.1111/j.1365-2311.1965.tb02304.x') return; //403648 traits     7 connectors
        // */

        print_r($rec); //exit;
        if($rec['trait.source'] == 'https://www.wikidata.org/entity/Q116180473') $use_citation = TRUE;
        elseif($rec['trait.source'] == 'https://www.wikidata.org/wiki/Q116180473') $use_citation = TRUE;
        else $use_citation = FALSE; //the rest goes here.
        
        if($use_citation) {
            $citation = $rec['trait.citation'];
            $input = array();
            $input["params"] = array("citation" => $citation);
            $input["type"] = "wikidata_base_qry_citation";
            $input["per_page"] = 500; // 500 worked ok    
        }
        else {
            $source = $rec['trait.source'];
            $input = array();
            $input["params"] = array("source" => $source);
            $input["type"] = "wikidata_base_qry_source";
            $input["per_page"] = 500; // 500 finished ok
        }

        $input["trait kind"] = "trait";
        $path1 = self::generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path1]\n";
        $file1 = $path1.$input['trait kind']."_qry.tsv";
        $this->tmp_batch_export = $path1 . "/temp_export.qs";

        $input["trait kind"] = "inferred_trait";
        $path2 = self::generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path2]\n";
        $file2 = $path2.$input['trait kind']."_qry.tsv";
        $this->tmp_batch_export = $path2 . "/temp_export.qs";

        // exit("\n$file1\n$file2\nxxx\n");

        $input["trait kind"] = "trait";
        if(file_exists($file1)) {
            if($task == 'generate trait reports') self::create_WD_traits($input);
            elseif($task == 'create WD traits') self::divide_exportfile_send_2quickstatements($input);
        }
        else echo "\n[$file1]\nNo query results yet: ".$input['trait kind']."\n";

        // /* un-comment in real operation
        $input["trait kind"] = "inferred_trait";
        if(file_exists($file2)) {
            if($task == 'generate trait reports') self::create_WD_traits($input);
            elseif($task == 'create WD traits') self::divide_exportfile_send_2quickstatements($input);
        }
        else echo "\n[$file2]\nNo query results yet: ".$input['trait kind']."\n";
        // */

        // $func->divide_exportfile_send_2quickstatements($input); exit("\n-end divide_exportfile_send_2quickstatements() -\n");
        return array($path1, $path2);
    }
    private function show_totals()
    {
        $WRITE = Functions::file_open($this->report_path."readme.txt", "w");
        $files = $this->report_path."*.*";
        foreach (glob($files) as $file) {
            $total = shell_exec("wc -l < ".escapeshellarg($file)); $total = trim($total);
            $basename = pathinfo($file, PATHINFO_BASENAME);
            if($basename == "readme.txt") continue;

            if($basename == "inferred_trait_qry.tsv") $which_file = $basename;
            if($basename == "trait_qry.tsv")          $which_file = $basename;

            if(in_array($basename, array("unprocessed_taxa.tsv", "taxonomic_mappings_for_review.tsv", "inferred_trait_qry.tsv", "trait_qry.tsv", "discarded_rows.tsv"))) $total--;
            echo "\n$basename: [$total]\n";
            fwrite($WRITE, "$basename: [$total]"."\n");
        }
        fwrite($WRITE, "\nNumber of rows"."\n");
        fwrite($WRITE, "--------------"."\n");
        fwrite($WRITE, "[export_file.qs] == [taxonomic_mappings_for_review.tsv]"."\n");
        fwrite($WRITE, "[export_file.qs] + [unprocessed_taxa.tsv] + [discarded_rows.tsv] = [$which_file]"."\n");
        fclose($WRITE);
    }
    private function get_WD_id_of_citation($rec)
    {   /*Array(
        [p.canonical] => Alecton
        [p.page_id] => 20365964
        [pred.name] => behavioral circadian rhythm
        [stage.name] => adult
        [sex.name] => 
        [stat.name] => 
        [obj.name] => diurnal
        [t.measurement] => 
        [units.name] => 
        [t.source] => https://www.wikidata.org/entity/Q116263059
        [t.citation] => McDermott, F. (1964). The Taxonomy of the Lampyridae (Coleoptera). Transactions of the American Entomological Society (1890-), 90(1), 1-72. Retrieved January 29, 2021, from http://www.jstor.org/stable/25077867
        [ref.literal] => 
        [how] => identifier-map
        )*/
        if(preg_match("/wikidata.org\/entity\/(.*?)elix/ims", $rec['t.source']."elix", $arr)) return $arr[1];                   //is WikiData entity
        elseif(preg_match("/wikidata.org\/wiki\/(.*?)elix/ims", $rec['t.source']."elix", $arr)) return $arr[1];                 //is WikiData entity
        elseif(stripos($rec['t.source'], "/doi.org/") !== false) { //string is found    //https://doi.org/10.1002/ajpa.20957    //is DOI
            if($val = self::get_WD_entityID_for_DOI($rec['t.source'])) return $val;
            else { //has DOI no WikiData yet
                echo "\n---------------------\n"; print_r($rec);
                echo "\nhas DOI but not in WikiData yet\n";
                echo "\n---------------------\n";
                self::create_WD_for_citation($rec['t.citation'], $rec['t.source']);
            }
        }
        elseif($rec['t.source'] && $rec['t.citation']) exit("\nNo case like this yet.\n");
        elseif($rec['t.source'] && !$rec['t.citation']) exit("\n huli ka\n");

        else { //https://www.delta-intkey.com/britin/lep/www/endromid.htm
            return "";
            print_r($rec);
            exit("\nNo design yet.\n");
        }               
    }
    function create_WD_for_citation($citation, $t_source)
    {
        if(stripos($t_source, "/doi.org/") !== false) echo "\n==========\nLet SourceMD generate the export file, since this has a DOI.\n==========\n";

        $citation_obj = self::parse_citation_using_anystyle($citation, 'all');
        if($ret = self::does_title_exist_in_wikidata($citation_obj, $citation)) { //orig un-comment in real operation
            print_r($ret);
            return $ret['wikidata_id'];
        }
        else {
            $title = $citation_obj[0]->title[0];
            $title = self::manual_fix_title($title);
            echo "\nTitle does not exist (B). [$title]\n";
            self::create_WD_reference_item($citation_obj, $citation);
        }
        
        echo ("\n-end muna-\n");
    }
    function run_down_all_citations($spreadsheet)
    {
        $spreadsheet = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/resources/".$spreadsheet;
        $total = shell_exec("wc -l < ".escapeshellarg($spreadsheet)); $total = trim($total);
        $i = 0;
        foreach(new FileIterator($spreadsheet) as $line_number => $line) { $i++; echo "\n$i of $total resources\n";
            if(!$line) continue;
            $row = str_getcsv($line);
            if(!$row) continue;
            if($i == 1) { $fields = $row; $count = count($fields); continue;}
            else { //main records
                /* during dev only
                if($i % 2 == 0) {echo "\n[EVEN]\n"; continue;} //even
                else {echo "\n[ODD]\n"; } //odd
                */
                $values = $row; $k = 0; $rec = array();
                foreach($fields as $field) { $rec[$field] = $values[$k]; $k++; }
                $rec = array_map('trim', $rec); //important step
                print_r($rec); //exit;
                self::check_if_citation_exists_create_export_if_not($rec);
            }
        }
    }
    private function check_if_citation_exists_create_export_if_not($rec)
    {   /*Array(
            [r.resource_id] => 1054
            [trait.source] => https://www.wikidata.org/entity/Q116263059
            [trait.citation] => McDermott, F. (1964). The Taxonomy of the Lampyridae (Coleoptera). Transactions of the American Entomological Society (1890-), 90(1), 1-72. Retrieved January 29, 2021, from http://www.jstor.org/stable/25077867
        )*/
        $t_source = $rec['trait.source'];
        $t_citation = $rec['trait.citation'];

        if(preg_match("/wikidata.org\/entity\/(.*?)elix/ims", $t_source."elix", $arr)) return $arr[1];                   //is WikiData entity
        elseif(preg_match("/wikidata.org\/wiki\/(.*?)elix/ims", $t_source."elix", $arr)) return $arr[1];                 //is WikiData entity
        elseif(stripos($t_source, "/doi.org/") !== false) { //string is found    //https://doi.org/10.1002/ajpa.20957    //is DOI
            if($val = self::get_WD_entityID_for_DOI($t_source)) return $val;
            else { //has DOI no WikiData yet
                echo "\n---------------------\n"; print_r($rec);
                echo "\nhas DOI but not in WikiData yet\n";
                echo "\n---------------------\n";
                self::create_WD_for_citation($t_citation, $t_source);
                exit("\nelix1\n");
            }
        }
    }
    private function delete_folder_contents($path, $exemptions)
    {
        $files = $path."*.*";
        foreach (glob($files) as $file) {
            $basename = pathinfo($file, PATHINFO_BASENAME);
            if(in_array($basename, $exemptions)) continue;
            if(stripos($file, "/reports/cypher/") !== false) unlink($file); //echo "\ndelete $file";
        }
    }
    private function get_pipe_delimited_ancestry($WD_id)
    {
        $str = "";
        if(!$WD_id) return "";
        if($arr = self::get_ancestry_of_taxon($WD_id)) {
            $arr = array_reverse($arr);
            $str = implode("|", $arr);
            // print_r($arr);
            echo "\n$str\n";    
        }
        return $str;
    }
    function get_ancestry_of_taxon($id) //$id is taxon ID e.g. Q3282257
    {
        $qry = 'SELECT ?itemLabel
        WHERE{
            wd:'.$id.' wdt:P171* ?item
            SERVICE wikibase:label { bd:serviceParam wikibase:language "[AUTO_LANGUAGE],en". }
        }';
        $url = "https://query.wikidata.org/sparql?query=";
        $url .= urlencode($qry);
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($xml = Functions::lookup_with_cache($url, $options)) { // print_r($xml);
            //<literal xml:lang='en'>Gadus morhua</literal>
            if(preg_match_all("/<literal xml\:lang=\'en\'>(.*?)<\/literal>/ims", $xml, $arr)) { // print_r($arr[1]);
                return $arr[1];
            }
        }    
    }
    private function rank_is_above_species($rank)
    {
        if(!$rank) return false;
        if(in_array($rank, array("species", "subspecies"))) return false;
        return true; //the rest is true
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