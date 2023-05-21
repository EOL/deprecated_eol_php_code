<?php
namespace php_active_record;
/* 
called from WikiDataMtceAPI.php
*/
class WikiDataMtce_ResourceAPI
{
    function __construct()
    {
        // $this->download_options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1);

        /*
        name: native range includes     uri: http://eol.org/schema/terms/NativeRange
        name: endemic to                uri: http://eol.org/terms/endemic
        name: geographic distribution   uri: http://eol.org/schema/terms/Present
        Flora do Brasil: 753
            measurementTypes to use:        
            http://eol.org/schema/terms/NativeRange
            http://eol.org/terms/endemic        
        Kubitzki et al: 822
            measurementTypes to use:
            http://eol.org/schema/terms/NativeRange
            http://eol.org/schema/terms/Present
        */
        $this->mType_label_uri['native range includes']   = 'http://eol.org/schema/terms/NativeRange';
        $this->mType_label_uri['native range']            = 'http://eol.org/schema/terms/NativeRange';
        $this->mType_label_uri['endemic to']              = 'http://eol.org/terms/endemic';
        $this->mType_label_uri['geographic distribution'] = 'http://eol.org/schema/terms/Present';
    }

    function run_1_resource_traits($rec, $task)
    {   
        print_r($rec); //exit("\n[$task]\nstop 173\n");
        /* e.g. Array(
            [r.resource_id] => 753
            [trait.source] => 
            [trait.citation] => 
        )
        Array(
            [r.resource_id] => pnas
            [trait.source] => https://doi.org/10.1007/s13127-017-0350-6
            [trait.citation] => 
        )*/
        /* good way to run 1 resource for investigation
        if($rec['r.resource_id'] != 753) return; // Flora do Brasil           
        */
        
        if(ctype_alpha($rec['r.resource_id'])) {}
        if(ctype_digit($rec['r.resource_id'])) $rec['r.resource_id'] = (int) $rec['r.resource_id'];


        $input = array();
        $input["params"] = array("resource_id" => $rec['r.resource_id']);
        $input["type"] = "wikidata_base_qry_resourceID";

        
        // /* new block Apr 28, 2023
        if($rec['r.resource_id'] == "pnas") { // https://doi.org/10.1007/s13127-017-0350-6
            $input["params"] = array("source" => $rec['trait.source']);
            $input["type"] = "wikidata_base_qry_source";
        }
        // */

        $input["per_page"] = $this->per_page_2; //1000
        
        $input["trait kind"] = "trait";
        // $json = json_encode($input);
        print_r($input); //exit("\n[$json]\nstop muna2\n");
        $path1 = $this->generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path1]\n";
        $file1 = $path1.$input['trait kind']."_qry.tsv";
        $tmp_batch_export1 = $path1 . "/temp_export.qs";

        $input["trait kind"] = "inferred_trait";
        $path2 = $this->generate_report_path($input); echo "\n".$input["trait kind"]." path: [$path2]\n";
        $file2 = $path2.$input['trait kind']."_qry.tsv";
        $tmp_batch_export2 = $path2 . "/temp_export.qs";

        print_r($input);
        // exit("\n$file1\n$file2\nxxx\n");

        // /* UN-COMMENT IN REAL OPERATION
        $this->unique_pred_obj = array();
        $input["trait kind"] = "trait";
        $this->tmp_batch_export = $tmp_batch_export1;
        if(file_exists($file1)) {
            if($task == 'generate trait reports') $this->create_WD_traits($input);
            elseif($task == 'create WD traits') $this->divide_exportfile_send_2quickstatements($input);
        }
        else echo "\n[$file1]\nNo query results yet: ".$input['trait kind']."\n";
        
        // with errors
        // to be sent to QS form submit:
        // Q309609
        // Q17282916
        // Q17283119
        // Q15398398
        // Q15497879
        // Q2914497
        // Q161120
        // Q156790
        // Q1766333
        // Q2068761
        // Q42750599
        // Q2063965
        // Q18080970
        // Q2098767
        // Q17295327
        // Q15508795
        // Q15542246
        // Q15543941
        // */

        // /* UN-COMMENT IN REAL OPERATION
        $this->unique_pred_obj = array();
        $input["trait kind"] = "inferred_trait";
        $this->tmp_batch_export = $tmp_batch_export2;
        if(file_exists($file2)) {
            if($task == 'generate trait reports') $this->create_WD_traits($input);
            elseif($task == 'create WD traits') $this->divide_exportfile_send_2quickstatements($input);
            elseif($task == 'remove WD traits') self::divide_exportfile_send_2quickstatements($input, true); //2nd param remove_traits_YN
        }
        else echo "\n[$file2]\nNo query results yet: ".$input['trait kind']."\n";
        // */

        // $func->divide_exportfile_send_2quickstatements($input); exit("\n-end divide_exportfile_send_2quickstatements() -\n");
        return array($path1, $path2);
    }
    function adjust_record($rec)
    {   /*Array(
            [p.canonical] => Amphisolenia schauinslandi
            [p.page_id] => 48894874
            [pred.name] => native range includes
            [stage.name] => 
            [sex.name] => 
            [stat.name] => 
            [obj.name] => Rio Grande Do Sul
            [obj.uri] => https://www.geonames.org/3451133
            [t.measurement] => 
            [units.name] => 
            [t.source] => http://reflora.jbrj.gov.br/reflora/floradobrasil/FB111250
            [t.citation] => Brazil Flora G (2019). Brazilian Flora 2020 project - Projeto Flora do Brasil 2020. Version 393.206. Instituto de Pesquisas Jardim Botanico do Rio de Janeiro. Checklist dataset https://doi.org/10.15468/1mtkaw accessed via GBIF.org on 2023-02-14
            [ref.literal] => Balech, E.. . Los Dinoflagelados del Atlantico Sudoccidental. Publ. Espec. Instituto Español de Oceanografia, Madrid,,.
        )*/
        return $rec;
    }
    function lookup_geonames_4_WD($rec)
    {   /*Array(
            [p.canonical] => Closterium turgidum giganteum
            [p.page_id] => 51840488
            [pred.name] => native range includes
            [stage.name] => 
            [sex.name] => 
            [stat.name] => 
            [obj.name] => Bahia
            [obj.uri] => https://www.geonames.org/3471168
            [t.measurement] => 
            [units.name] => 
            [t.source] => http://reflora.jbrj.gov.br/reflora/floradobrasil/FB107192
            [t.citation] => Brazil Flora G (2019). Brazilian Flora 2020 project - Projeto Flora do Brasil 2020. Version 393.206. Instituto de Pesquisas Jardim Botanico do Rio de Janeiro. Checklist dataset https://doi.org/10.15468/1mtkaw accessed via GBIF.org on 2023-02-14
            [ref.literal] => OLIVEIRA, I.B.; BICUDO, C.E.M. & MOURA, C.W.N.. . Iheringia, Ser. Bot.,(),.
            [how] => identifier-map
        )*/
        if(preg_match("/geonames.org\/(.*?)elix/ims", $rec['obj.uri']."elix", $arr)) {
            $geonames_id = $arr[1];
            if($WD_entity = self::get_WD_id_using_geonames($geonames_id)) return $WD_entity;
            else {
                // print_r($rec); exit;
                return false;
            }
        }
    }
    private function get_WD_id_using_geonames($geonames_id) //$geonames_id e.g. 3471168 for "Bahia"
    {
        // https://query.wikidata.org/sparql?query=SELECT ?s WHERE {VALUES ?id {"3393129"} ?s wdt:P1566 ?id }

        // https://www.geonames.org/3451133
        // https://query.wikidata.org/sparql?query=SELECT ?s WHERE {VALUES ?id {"3451133"} ?s wdt:P1566 ?id }
        // http://www.wikidata.org/entity/Q40030

        $qry = 'SELECT ?s WHERE {VALUES ?id {"'.$geonames_id.'"} ?s wdt:P1566 ?id }';
        $url = "https://query.wikidata.org/sparql?query=";
        $url .= urlencode($qry);
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($xml = Functions::lookup_with_cache($url, $options)) { // print_r($xml);
            // <uri>http://www.wikidata.org/entity/Q43255</uri>
            // exit("\n".$xml."\n");
            if(preg_match("/<uri>(.*?)<\/uri>/ims", $xml, $arr)) { // print_r($arr[1]);
                return $arr[1];
            }
            else {
                echo("\nInvestigate:\n[$geonames_id]\n$url\n$xml\nmay prob1.\n");
                $this->debug['unmapped'][$geonames_id][$url] = '';
                return false;
            }
        }
        echo("\nInvestigate:\n[$geonames_id]\n$url\nmay prob2.\n");
        $this->debug['unmapped'][$geonames_id][$url] = '';
        return false;
    }
    function write_predicate_object_mapping($rec, $final)
    {   // echo "\n-----111-----\n"; print_r($final); print_r($rec); echo "\n-----222-----\n"; //good debug
        /*Array(
            [taxon_entity] => Q15564123
            [predicate_entity] => https://www.wikidata.org/wiki/Property:P9714
            [object_entity] => http://www.wikidata.org/entity/Q175
            [P248] => Q117034902
        )
        Array(
            [p.canonical] => Mikania vitifolia
            [p.page_id] => 6183527
            [pred.name] => native range includes
            [stage.name] => 
            [sex.name] => 
            [stat.name] => 
            [obj.name] => SãO Paulo
            [obj.uri] => https://www.geonames.org/3448433
            [t.measurement] => 
            [units.name] => 
            [t.source] => http://reflora.jbrj.gov.br/reflora/floradobrasil/FB5449
            [t.citation] => Brazil Flora G (2019). Brazilian Flora 2020 project - Projeto Flora do Brasil 2020. Version 393.206. Instituto de Pesquisas Jardim Botanico do Rio de Janeiro. Checklist dataset https://doi.org/10.15468/1mtkaw accessed via GBIF.org on 2023-02-14
            [ref.literal] => Ritter, M.R. & Miotto, S.T.S.. 2005. Hoehnea,32(3):309-359,2005.
            [how] => identifier-map
        )*/
        // print_r($rec);
        $r = array();
        $r[] = $rec['pred.name'];           //=> native range includes
        $r[] = $final['predicate_entity'];  //=> https://www.wikidata.org/wiki/Property:P9714
        $r[] = $rec['obj.name'];            //=> SãO Paulo
        $r[] = $rec['obj.uri'];             //=> https://www.geonames.org/3448433
        $r[] = $final['object_entity'];     //=> http://www.wikidata.org/entity/Q175
        $r = array_map('trim', $r);

        $md5 = md5(json_encode($r));
        if(!isset($this->unique_pred_obj[$md5])) {
            $WRITE = Functions::file_open($this->predicate_object_mapping, "a");
            fwrite($WRITE, implode("\t", $r)."\n"); fclose($WRITE);
            $this->unique_pred_obj[$md5] = '';
        }
    }
    function get_doi_from_tsource($tsource)
    {   // [t.source] => https://doi.org/10.1007/978-3-662-02604-5_58
        if(stripos($tsource, "/doi.org/") !== false) { //string is found
            $final = str_ireplace("https://doi.org/", "", $tsource);
            $final = str_ireplace("http://doi.org/", "", $final);
            if($final) return array($final);
        }
    }
    function log_citations_mapped_2WD_all($rec)
    {
        if(preg_match("/wikidata.org\/entity\/(.*?)elix/ims", $rec['t.source']."elix", $arr)) {                    //is WikiData entity
            $this->debug2['citation mapped to WD: all'][$rec['t.source']][$rec['t.citation']][$arr[1]] = '';
            // return $arr[1];
            return;
        }
        elseif(preg_match("/wikidata.org\/wiki\/(.*?)elix/ims", $rec['t.source']."elix", $arr)) {                  //is WikiData entity
            $this->debug2['citation mapped to WD: all'][$rec['t.source']][$rec['t.citation']][$arr[1]] = '';
            // return $arr[1];
            return;
        }
        elseif(stripos($rec['t.source'], "/doi.org/") !== false) { //string is found    //https://doi.org/10.1002/ajpa.20957    //is DOI
            if($val = $this->get_WD_entityID_for_DOI($rec['t.source'])) {
                $this->debug2['citation mapped to WD: all'][$rec['t.source']][$rec['t.citation']][$val] = '';
                // return $val;
                return;
            }
        }
        // /* ----- new block: also not to keep on calling ruby
        $citation = $rec['t.citation'];
        $t_source = $rec['t.source'];
        if($citation_obj = @$this->cite_obj[$citation]) {}
        else {
            $citation_obj = $this->parse_citation_using_anystyle($citation, 'all', 4);
            $this->cite_obj[$citation] = $citation_obj;
        }
        if($ret = $this->does_title_exist_in_wikidata($citation_obj, $citation, $t_source)) { //orig un-comment in real operation
            // no need to return anything here.
            // print_r($ret); return $ret['wikidata_id'];
        }
        else $this->debug2['citation not mapped to WD: all'][$t_source][$citation] = '';
        // */ -----
    }
    function which_ret_to_use($rets, $canonical, $page_id)
    {   /*
        1st ver:
        Array(
            [i] => Q50864065
            [c] => Posoqueria latifolia
        )
        2nd ver: used now
        Array(
            [0] => Array(
                    [i] => Q10352100
                    [c] => Posoqueria latifolia
                )
            [1] => Array(
                    [i] => Q50864065
                    [c] => Posoqueria latifolia
                )
        )        
        // from: provider_ids.csv
        // 113724451,Q10352100,1072,1095440,Posoqueria latifolia
        // 116498583,Q50864065,1072,1095440,Posoqueria latifolia
        ---------------------------------------------------------------------
        From identifier-map:
        113712096,Q100477061,1072,52520226,Persoonia hirsuta hirsuta
        113712097,Q100477116,1072,52520226,Persoonia hirsuta hirsuta
        114459187,Q110288015,1072,52520226,Persoonia hirsuta hirsuta
        Which Wikidata entity to use?
        Q100477061 : [Persoonia hirsuta subsp. hirsuta]
        Q100477116 : [Persoonia hirsuta var. hirsuta]
        Q110288015 : [Linkia hirsuta]
        */
        if(count($rets) == 1) return $rets[0];
        elseif($rets) { 
            $total = count($rets);
            // /* ----- 1st try: get the idential string canonical name
            $i = 0;
            foreach($rets as $ret) { $i++;
                $label = $this->get_WD_obj_using_id($ret['i'], $what = 'label'); echo "\n$i of $total: label: [$label][$canonical][$page_id]*\n";
                if($canonical == $label) {
                    print_r($ret);
                    return $ret;
                }
            }
            // */ -----
            // /* ----- 2nd try: get what the special dynamic hierarchy file's scientificName; where eolID matches. Per Katja: https://eol-jira.bibalex.org/browse/COLLAB-1006?focusedCommentId=67434&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67434
            $sciname_with_matching_eolID = self::get_sciname_with_this_eolID($page_id);
            $i = 0;
            foreach($rets as $ret) { $i++;
                $label = $this->get_WD_obj_using_id($ret['i'], $what = 'label'); echo "\n$i of $total: label: [$label][$canonical][$page_id]**\n";
                if($sciname_with_matching_eolID == $label) {
                    print_r($ret); //exit("\nstop try 2\n");
                    return $ret;
                }
            }
            // */ -----
            // /* ----- 3rd try: pick between the canonical matches
            // Q100477061 : [Persoonia hirsuta subsp. hirsuta]
            // Q100477116 : [Persoonia hirsuta var. hirsuta]
            // Q110288015 : [Linkia hirsuta]
            $i = 0;
            foreach($rets as $ret) { $i++;
                $label = $this->get_WD_obj_using_id($ret['i'], $what = 'label'); echo "\n$i of $total: label: [$label][$canonical][$page_id]***\n";
                $label = str_replace(array(' subsp. ', ' var. '), " ", $label);
                if($canonical == $label) {
                    print_r($ret); //exit("\nstop try 3\n");
                    return $ret;
                }
            }
            // */ -----
        }
        // print_r($rets);
        // exit("\nDoes not go here.\n[$canonical]\n"); // cases like this exist
    }
    function is_sciname_present_from_source($sciname)
    {   $sciname = str_replace(array("[", "]"), "", $sciname);
        $sciname = $this->gnparser->run_gnparser($sciname, 'simple');

        if(isset($this->origsource_sciname_info)) {
            if(isset($this->origsource_sciname_info[$sciname])) return true;
            else return false; //write report to Katja
        }
        else {
            self::generate_info_list('origsource_sciname_info'); //generates $this-origsource_sciname_info
            self::is_sciname_present_from_source($sciname);
        }
    }
    function get_sciname_with_this_eolID($page_id)
    {
        if(isset($this->dh_eolID_sciname_info)) {
            if($sciname = @$this->dh_eolID_sciname_info[$page_id]) return $sciname;
            else return false;
        }
        else {
            self::generate_info_list('dh_eolID_sciname_info'); //generates $this->dh_eolID_sciname_info
            self::get_sciname_with_this_eolID($page_id);
        }
    }
    function generate_info_list($what)
    {
        if($what == 'dh_eolID_sciname_info')       $file = "/Volumes/Crucial_2TB/other_files2/dh21eolid/DH21taxaWeolIDs.txt";
        elseif($what == 'origsource_sciname_info') $file = "/Volumes/Crucial_2TB/other_files2/Brazilian_Flora_with_canonical/taxon.tab";
        elseif($what == 'kubitzki_pagenos')        $file = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Kubitzki/KubitzkiPages.tsv";
        else exit("\nUndefined file: [$file]\n");

        // /* special step
        if(substr($file,0,4) == "http") { //e.g. 'kubitzki_pagenos'
            $file = Functions::save_remote_file_to_local($file, array('cache' => 1, 'expire_seconds' => 60*60*24*30));
        }
        // */

        echo "\nReading $file...\n";
        $i = 0;
        foreach(new FileIterator($file) as $line => $row) { $i++;
            // $row = Functions::conv_to_utf8($row);
            if($i == 1) $fields = explode("\t", $row);
            else {
                if(!$row) continue;
                $tmp = explode("\t", $row);
                $rec = array(); $k = 0;
                foreach($fields as $field) { $rec[$field] = $tmp[$k]; $k++; }
                $rec = array_map('trim', $rec);
                // print_r($rec); exit("\nstop muna\n");
                /*Array( dh_eolID_sciname_info
                    [taxonID] => EOL-000000000001
                    [source] => trunk:4038af35-41da-469e-8806-40e60241bb58
                    [furtherInformationURL] => 
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => 
                    [scientificName] => Life
                    [taxonRank] => 
                    [taxonomicStatus] => accepted
                    [datasetID] => trunk
                    [canonicalName] => Life
                    [eolID] => 2913056
                    [Landmark] => 3
                    [higherClassification] => 
                )*/
                /*
                Array( origsource_sciname_info
                    [taxonID] => 12
                    [furtherInformationURL] => http://reflora.jbrj.gov.br/reflora/listaBrasil/FichaPublicaTaxonUC/FichaPublicaTaxonUC.do?id=FB12
                    [acceptedNameUsageID] => 
                    [parentNameUsageID] => 120181
                    [scientificName] => Agaricales
                    [namePublishedIn] => 
                    [kingdom] => Fungi
                    [phylum] => Basidiomycota
                    [class] => 
                    [order] => Agaricales
                    [family] => 
                    [genus] => 
                    [taxonRank] => order
                    [scientificNameAuthorship] => 
                    [taxonomicStatus] => accepted
                    [modified] => 2018-08-10 11:58:06.954
                    [canonicalName] => Agaricales
                )
                Array(
                    [ref_entity] => Q116224190
                    [Taxon] => Acrosorus
                    [page] => 156
                )*/
                if($what == 'dh_eolID_sciname_info') {
                    $this->dh_eolID_sciname_info[$rec['eolID']] = $rec['canonicalName'];
                }
                elseif($what == 'origsource_sciname_info') {
                    $this->origsource_sciname_info[$rec['canonicalName']] = '';
                }
                elseif($what == 'kubitzki_pagenos') {
                    $this->kubitzki_pagenos[$rec['ref_entity']][$rec['Taxon']] = $rec['page'];
                }
            }
        } //end foreach()
        if($what == 'kubitzki_pagenos') unlink($file); // print_r($this->kubitzki_pagenos); exit;
    }
    function start_print_debug($debug, $series, $eol_resource_id)
    {
        $file = CONTENT_RESOURCE_LOCAL_PATH . "debug_".$eol_resource_id."_".$series.".txt";
        $str = print_r($debug, true); //true makes print_r() output as string
        file_put_contents($file, $str);
    }
    function adjust_from_P183_to_P9714() // a utility
    {   /* this does a delete+add: in effect it replaces the property P183 to P9714
        Given: 
            Q91249289|P183|Q43233|S248|Q117034902
        Generates:
            -Q91249289|P183|Q43233
            Q91249289|P9714|Q43233|S248|Q117034902  */
        
        $source_qs_file      = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/adjustments/Flora_753/export_file_adjusted.qs"; //any export_file.qs
        $destination_qs_file = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/adjustments/Flora_753/export_file_final.qs";
        $WRITE = Functions::file_open($destination_qs_file, "w");

        $i = 0; $hits = 0;
        foreach(new FileIterator($source_qs_file) as $line => $row) { $i++;
            /*
            9077. Q17043207|P183|Q41587|S248|Q117034902 --- x 2628
            9078. Q91249271|P183|Q41587|S248|Q117034902 --- x 2629
            9080. Q91249289|P183|Q43233|S248|Q117034902 --- x 2630
            */
            if(stripos($row, "|P183|") !== false) { $hits++; //string is found
                echo "\n$i. ".$row; echo " --- x $hits";
                $remove = $this->remove_reference_part($row);
                fwrite($WRITE, $remove."\n");
                $new = str_ireplace("|P183|", "|P9714|", $row);
                fwrite($WRITE, $new."\n");
            }
        }
        fclose($WRITE);
        echo "\nhits: $hits\n";
        $out = shell_exec("wc -l ".$destination_qs_file);
        echo "\n$out\n";
    }
    function adjust_from_S3452_to_S248($folders) //worked OK!
    {   /* Given:   Q10398723|P2974|Q113024778|S3452|Q113217115
        Generate:
                    -Q10398723|P2974|Q113024778
                    Q10398723|P2974|Q113024778|S248|Q113217115
        */
        // $folders = array('b564eab0404081f7381bbf76b759fedb');
        // $folders = array('53264aeaff9f996093b9b555a5308020');
        // $folders = array('bf64239ace12e4bd48f16387713bc309');
        // $folders = array('3f63a25e04c425e41767f3a7192c964d');
        // $folders = array('ec39332ee0b39fc646c95a2b03467d43');
        // $folders = array('64becbfe6064c00e01bde4849ce1a8b3');
        // $folders = array('da23f9319bb205e88bcdeab285f494d7', 'a5af381f83c7c52cde68c2a8c7372d10'); //10 & 11 rows DONE
        // $folders = array('f6e844e9ac7eae380bdaa91b6c0385a1'); //13 DONE
        // $folders = array('27ee919870baffc5e81ba788ed7ae593'); //14 DONE
        // $folders = array('e9065861b9027ed7c16932e14b7fea98'); //15 DONE
        // $folders = array('fcde2bf04c7179129e08be03f013c47e'); //16 DONE

        foreach($folders as $folder) {

            $source_qs_file      = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file.qs"; //any export_file.qs
            $destination_qs_file = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file_S248.qs";
            $WRITE = Functions::file_open($destination_qs_file, "w");
    
            $i = 0; $hits = 0;
            foreach(new FileIterator($source_qs_file) as $line => $row) { $i++;
                if(stripos($row, "|S3452|") !== false) { $hits++; //string is found
                    echo "\n$i. ".$row; echo " --- x $hits";
                    $remove = $this->remove_S_part($row, 3452);
                    fwrite($WRITE, $remove."\n");
                    $new = str_ireplace("|S3452|", "|S248|", $row);
                    fwrite($WRITE, $new."\n");
                }
            }
            fclose($WRITE);
            echo "\nhits: $hits\n";
            $out = shell_exec("wc -l ".$destination_qs_file);
            echo "\n$out\n";

        } //end foreach
    }
    function remove_S_part($row, $str_2remove)
    {
        $pos = strpos($row, "|S".$str_2remove);
        if(!$pos) {
            echo("\nNeed to investigate aaa.\nrow: [$row]\n");
            return "-".$row;
        }
        else return "-".substr($row, 0, $pos);
    }

    function run_any_qs_export_file($input)
    {
        /* 1st client DONE
        $input['report for'] = "Flora_ADJ"; //Flora 753 adjustments --- this does a delete+add: in effect it replaces the property P183 to P9714
        $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/adjustments/Flora_753/export_file_final.qs";
        */

        /* 2nd client DONE
        $input['report for'] = "fpnas_ADJ"; //to delete all with ref Q90856597 (n = 151,862)
        $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/adjustments/fpnas_2delete/export_file_2del_ambrosia.qs";
        $input['what'] = "to delete";
        */

        /* 3rd client RUNNING
        $input['report for'] = "del_Kubitzki_inferred"; //delete all recs (n = 93,080)
        $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/adjustments/Kubitzki_inferred_2delete/export_file.qs";
        $input['what'] = "to delete";
        */

        // /* 4th RUNNING... S248 series
        // $input['report for'] = "S248";
        // $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/b564eab0404081f7381bbf76b759fedb/export_file_S248.qs";
        // $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/64becbfe6064c00e01bde4849ce1a8b3/export_file_S248_trans.qs";
        // $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/a5af381f83c7c52cde68c2a8c7372d10/export_file_S248.qs"; //done
        // $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/da23f9319bb205e88bcdeab285f494d7/export_file_S248.qs"; //done
        // $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/f6e844e9ac7eae380bdaa91b6c0385a1/export_file_S248.qs"; //13 done
        // $input['report for'] = "S248_b";
        // $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/27ee919870baffc5e81ba788ed7ae593/export_file_S248.qs"; //14 done
        // $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/e9065861b9027ed7c16932e14b7fea98/export_file_S248.qs"; //15 done
        // */

        /* 5th
        $input['report for'] = "del_series_31"; //delete all recs (n = 260,744)
        $input['export file'] = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/adjustments/31_2delete/export_file.qs";
        $input['what'] = "to delete";
        */

        $this->eol_resource_id = $input['report for'];

        // /* this block is similar to: function generate_report_path($input)
        $path1 = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/"; //generated from CypherQueryAPI.php
        if(!is_dir($path1)) mkdir($path1);
        $tmp = $input['report for'];
        $path1 .= "$tmp/";
        if(!is_dir($path1)) mkdir($path1);
        // */
        
        $this->tmp_batch_export = $path1 . "/temp_export.qs";

        /* set the paths
        $this->report_path = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/"; //generated from CypherQueryAPI.php
        $tmp = md5(json_encode($input));
        $this->report_path .= "$tmp/";
        */
        $this->report_path = $path1;

        $i = 0;
        $batch_name = date("Y_m_d");
        $batch_num = 0;
        $WRITE = Functions::file_open($this->tmp_batch_export, "w");
        foreach(new FileIterator($input['export file']) as $line => $row) {
            if($row) $i++;
            
            /* use this block to exclude already run: manually done
            if($i <= 3) continue;
            */

            if(@$input['what'] == "to delete") {
                $row = "-".$row;
                $divisor = 50;
            }
            else $divisor = 25;
            $divisor = 30; //5; //50; //overwrite it

            echo "\n".$row;
            fwrite($WRITE, $row."\n");
            if(($i % $divisor) == 0) { $batch_num++; // % 3 25 5 --- when deleting use 50. The rest use 25
                echo "\n-----";
                fclose($WRITE);
                $this->run_quickstatements_api($batch_name, $batch_num);
                $secs = 60*1; echo "\nSleep $secs seconds...v2"; sleep($secs); echo " Continue...\n";
                $WRITE = Functions::file_open($this->tmp_batch_export, "w"); //initialize again
                // break; //just the first 3 rows //debug only
            }
        }
        fclose($WRITE);
        $batch_num++;
        $this->run_quickstatements_api($batch_name, $batch_num);
    }
    function get_genus_part($str)
    {
        $parts = explode(" ", $str);
        return $parts[0];
    }
    function get_ancestry_given_taxon_entity($wikidata_id)
    {   echo "\nGetting ancestry [$wikidata_id]...\n";
        while(true) {
            $parent_id = $this->get_WD_obj_using_id($wikidata_id, 'parent_id');            
            if(!$parent_id) break; 
            $name = $this->get_WD_obj_using_id($parent_id, 'label');            
            // echo "\n[$parent_id]-[$name]\n";
            $wikidata_id = $parent_id;
            $final[$name] = $parent_id;
        }
        // print_r($final);
        // print_r(array_keys($final));
        return $final;

        // print_r($obj); --- below to get parent ID or property P171
        // [P171] => Array(
        //     [0] => stdClass Object(
        //             [mainsnak] => stdClass Object(
        //                     [snaktype] => value
        //                     [property] => P171
        //                     [hash] => 89fde5f5c90993da338ad22fa366982064ac41b0
        //                     [datavalue] => stdClass Object(
        //                             [value] => stdClass Object(
        //                                     [entity-type] => item
        //                                     [numeric-id] => 53476
        //                                     [id] => Q53476
        // echo "\n".$obj->entities->Q2020837->claims->P171[0]->mainsnak->datavalue->value->id;


        exit("\n111\n");
    }
    function process_page_nos_routine($rec, $final, $citation_WD_id)
    {
        if($page_nos = @$this->kubitzki_pagenos[$citation_WD_id][$rec['p.canonical']]) {
            $final['P304'] = $page_nos;
        }
        else {
            $genus_part = $this->get_genus_part($rec['p.canonical']);
            if($page_nos = @$this->kubitzki_pagenos[$citation_WD_id][$genus_part]) {
                $final['P304'] = $page_nos;
            }
            else {
                $sciname = $this->get_WD_obj_using_id($final['taxon_entity'], 'label');
                $genus_part = $this->get_genus_part($sciname);
                if($page_nos = @$this->kubitzki_pagenos[$citation_WD_id][$genus_part]) {
                    $final['P304'] = $page_nos;
                }
                else {

                    $ret = $this->get_ancestry_given_taxon_entity($final['taxon_entity']);
                    $ancestry = array_keys($ret); print_r($ancestry);
                    $found_YN = false;
                    foreach($ancestry as $ancestry_name) {
                        if($page_nos = @$this->kubitzki_pagenos[$citation_WD_id][$ancestry_name]) {
                            $final['P304'] = $page_nos;
                            $found_YN = true;
                            break;
                        }
                    }

                    if(!$found_YN) {
                        echo "\n-------------------------\n"; print_r($rec); print_r($final); // exit("\nPage nos. not found.\n");
                        $tmp = array();
                        $tmp['main'] = $final;
                        $tmp['addtl']['p.canonical'] = $rec['p.canonical'];
                        if(!$rec['p.canonical']) $tmp['addtl']['lookup canonical'] = $this->get_WD_obj_using_id($final['taxon_entity'], 'label');
                        $tmp['addtl']['pred.name'] = $rec['pred.name'];
                        $tmp['addtl']['t.source'] = $rec['t.source'];
                        if(!in_array($tmp, $this->debug['Page nos not found'])) $this->debug['Page nos not found'][] = $tmp;    
                    }

                }    
            }
        }
        return $final;
    }



    function prep_export_file_4deletion($folder) // a utility
    {   /*  Given:      Q14651426|P9566|Q4284186|S3452|Q116482998
            Generates: -Q14651426|P9566|Q4284186 */
        
        $source_qs_file      = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file.qs"; //any export_file.qs
        $destination_qs_file = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file_4del.qs";
        $WRITE = Functions::file_open($destination_qs_file, "w");

        $i = 0; $hits = 0;
        foreach(new FileIterator($source_qs_file) as $line => $row) { $i++;
            if(stripos($row, "|S") !== false) { $hits++; //string is found
                echo "\n$i. ".$row; echo " --- x $hits";
                $remove = $this->remove_reference_part($row);
                fwrite($WRITE, $remove."\n");
            }
        }
        fclose($WRITE);
        echo "\nhits: $hits\n";
        $out = shell_exec("wc -l ".$destination_qs_file);
        echo "\n$out\n";
    }

    function adjust_del_row_then_add($folders) //worked OK!
    {   /* Given:   Q2019188|P9714|Q27381|S248|Q117188019|S304|"368"
        Generate:
                    -Q2019188|P9714|Q27381
                    Q2019188|P9714|Q27381|S248|Q117188019|S304|"368"
        */

        foreach($folders as $folder) {

            $source_qs_file      = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file.qs"; //any export_file.qs
            $destination_qs_file = CONTENT_RESOURCE_LOCAL_PATH."reports/cypher/".$folder."/export_file_DelAdd.qs";
            $WRITE = Functions::file_open($destination_qs_file, "w");
    
            $i = 0; $hits = 0;
            foreach(new FileIterator($source_qs_file) as $line => $row) { $i++;
                if(stripos($row, "|S") !== false) { $hits++; //string is found
                    echo "\n$i. ".$row; echo " --- x $hits";
                    $remove = $this->remove_S_part($row, "");
                    fwrite($WRITE, $remove."\n");
                    $new = $row;
                    fwrite($WRITE, $new."\n");
                }
            }
            fclose($WRITE);
            echo "\nhits: $hits\n";
            $out = shell_exec("wc -l ".$destination_qs_file);
            echo "\n$out\n";

        } //end foreach
    }



    /* copied template
    function xxx()
    {
        $url = "http://www.marinespecies.org/imis.php?module=person&show=search&fulllist=1";
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30; //a month to expire
        if($html = Functions::lookup_with_cache($url, $options)) {}
    }*/
}
?>