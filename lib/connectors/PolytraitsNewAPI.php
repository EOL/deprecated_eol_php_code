<?php
namespace php_active_record;
/* connector: [polytraits_new.php]

*/
class PolytraitsNewAPI extends ContributorsMapAPI
{
    function __construct($folder = false)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->max_images_per_taxon = 10;
        $this->service['taxa list'] = "http://polytraits.lifewatchgreece.eu/traitspublic_taxa.php?pageID=";
        $this->service['name info'] = "http://polytraits.lifewatchgreece.eu/taxon/SCINAME/json/?exact=1&verbose=1&assoc=0";
        
        $this->service['trait info'] = "http://polytraits.lifewatchgreece.eu/traits/TAXON_ID/json/?verbose=1&assoc=1";
        $this->service['terms list'] = "http://polytraits.lifewatchgreece.eu/terms";
        $this->taxon_page = "http://polytraits.lifewatchgreece.eu/taxonpage/";

        $this->download_options = array('cache' => 1, 'resource_id' => 'polytraits', 'expire_seconds' => 60*60*24*30*6, //6 months
        'download_wait_time' => 1000000, 'timeout' => 10800, 'download_attempts' => 1); //6 months to expire
        // $this->download_options['expire_seconds'] = false;
        if(Functions::is_production()) $this->download_options['download_wait_time'] = 4000000; //4 secs.
        $this->debug = array();

        // /* gives curl error when calling the api
        $this->customized_scinames['Capitella sp. Ia'] = 1434;
        $this->customized_scinames['Capitella sp. II'] = 1436;
        $this->customized_scinames['Capitella sp. IIIa'] = 1439;
        $this->customized_scinames['Capitella sp. M'] = 1433;
        $this->customized_scinames['Paraonis sp.'] = 400;
        // */        
        /*
        Curl error (http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+Ia/json/?exact=1&verbose=1&assoc=0): The requested URL returned error: 404 Not Found :: [lib/Functions.php 
        Capitella sp. Ia [id: 1434]
        Curl error (http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+II/json/?exact=1&verbose=1&assoc=0): The requested URL returned error: 404 Not Found :: [lib/Functions.php 
        Capitella sp. II [id: 1436]
        Curl error (http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+IIIa/json/?exact=1&verbose=1&assoc=0): The requested URL returned error: 404 Not Found :: [lib/Functions.php 
        Capitella sp. IIIa [id: 1439]
        Curl error (http://polytraits.lifewatchgreece.eu/taxon/Capitella+sp.+M/json/?exact=1&verbose=1&assoc=0): The requested URL returned error: 404 Not Found :: [lib/Functions.php 
        Capitella sp. M [id: 1433]
        Curl error (http://polytraits.lifewatchgreece.eu/taxon/Paraonis+sp./json/?exact=1&verbose=1&assoc=0): The requested URL returned error: 404 Not Found :: [lib/Functions.php 
        Paraonis sp. [id: 400]
        */
    }
    function initialize()
    {   // 1.
        $this->terms_info = self::get_terms_info();
        // print_r($this->mTypes); print_r($this->terms_info); exit;
        // 2.
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        // 3.
        $options = array('cache' => 1, 'download_wait_time' => 500000, 'timeout' => 10800, 'expire_seconds' => 60*60*1); //60*60*1
        $this->contributor_mappings = $this->get_contributor_mappings('Polytraits', $options); // print_r($this->contributor_mappings); //good debug
        echo "\n contributor_mappings: ".count($this->contributor_mappings)."";
        if($this->contributor_mappings['Katerina Vasileiadou'] == 'https://orcid.org/0000-0002-5057-6417') echo " - Test OK.\n";
        // 4.: get unique values of a column in any table in a DwCA
        require_library('connectors/DwCA_Utility');
        $dwca_file = "https://editors.eol.org/eol_php_code/applications/content_server/resources/Polytraits.tar.gz";
        $resource_id = "nothing here";
        $func = new DwCA_Utility($resource_id, $dwca_file);
        $download_options = array("timeout" => 172800, 'expire_seconds' => 60*60*24*1); //1 day cache
        $params['row_type'] = 'http://rs.tdwg.org/dwc/terms/measurementorfact';
        $params['column'] = 'http://rs.tdwg.org/dwc/terms/measurementType';
        $this->old_unique_mTypes = $func->lookup_values_in_dwca($download_options, $params); //get unique values of a column in any table in a DwCA
        // print_r($this->old_unique_mTypes); exit("\nelix1\n");
        echo "\nNumber of old mTypes from old resource: ".count($this->old_unique_mTypes)."\n";
        unset($func);
    }
    function start()
    {
        self::initialize();
        self::main();
        $this->archive_builder->finalize(true);
        // Functions::start_print_debug();
        print_r($this->debug);
        print_r($this->contributor_mappings);
    }
    private function main()
    {   $pageID = 0;
        while(true) { $pageID++; $contYN = true;
            $url = $this->service['taxa list'].$pageID;
            if($html = Functions::lookup_with_cache($url, $this->download_options)) { // for taxa page lists...
                if(preg_match_all("/<i>(.*?)<\/td>/ims", $html, $arr)) {
                    if(count($arr[1]) == 0) $contYN = false;
                    // print_r($arr[1]); exit;
                    /*Array(
                        [0] =>  Abarenicola pacifica</i> Healy & Wells, 1959
                        [1] =>  Aberrantidae</i> Wolf, 1987
                        [2] =>  Abyssoninoe</i> Orensanz, 1990
                        [9] =>  Aglaophamus agilis</i> (Langerhans, 1880)
                        [10] =>  Aglaophamus circinata</i> (Verrill in Smith & Harger, 1874)
                        [11] =>  Alciopidae</i> Ehlers, 1864<span style='color:grey;'> (subjective synonym of  
                                <i>Alciopidae</i> according to Rouse, G.W., Pleijel, F. (2001) )</span>*/
                    $total_rows = count($arr[1]);
                    $ctr = 0;
                    foreach($arr[1] as $row) { $ctr++; echo "\nPage: [$pageID] $ctr of $total_rows\n";
                        // /* this block excludes synonyms
                        if(stripos($row, "synonym of") !== false) { //string is found
                            continue;
                        }
                        // */
                        $row = "<i>".$row; //echo "\n".$row;
                        $rek = array();
                        if(preg_match("/<i>(.*?)<\/i>/ims", $row, $arr2))         $rek['sciname'] = trim($arr2[1]);
                        if(preg_match("/<\/i>(.*?)elix/ims", $row."elix", $arr2)) $rek['author']  = trim($arr2[1]);
                        if(!$rek['sciname']) exit("\nInvestigate: no sciname\n");
                        // print_r($rek); exit;
                        self::process_taxon($rek);
                    }
                }
                else break;
            }
            else break;
            // break;                   //debug only
            // if($pageID >= 2) break;  //debug only

            if($contYN == false) break; //end of loop
        } //end while()
    }
    private function process_taxon($rek)
    {   
        if(isset($this->customized_scinames[$rek['sciname']])) $obj = self::customized_obj($rek['sciname'], $this->customized_scinames[$rek['sciname']]);
        else { //regular, rest goes here
            $obj = self::get_name_info($rek['sciname']); //print_r($obj); exit;
        }

        if(!$obj->taxonID) return;
        self::write_taxon($obj);
        // return;
        if($traits = self::get_traits_obj($obj->taxonID)) {
            echo "\nTraits: ".count($traits)." "; //return; //debug only
            self::write_traits($obj, $traits);
        }
    }
    private function get_traits_obj($taxonID)
    {
        $url = str_ireplace('TAXON_ID', $taxonID, $this->service['trait info']);
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            $traits = json_decode($json);
            $traits = $traits->$taxonID; //print_r($traits); exit;
            return $traits;
        }
        // else exit("\nInvestigate: cannot lookup this taxonID = $taxonID\n");
        return false;
    }
    private function customized_obj($sciname, $taxonID)
    {
        if($traits = self::get_traits_obj($taxonID)) {
            $obj = new \stdClass();
            $obj->taxonID = $taxonID;
            $obj->taxon = $traits[0]->valid_taxon;
            $obj->author = $traits[0]->valid_author;
            $obj->validID = $taxonID;
            $obj->valid_taxon = $traits[0]->valid_taxon;
            $obj->valid_author = $traits[0]->valid_author;
            $obj->status = 'accepted'; $traits[0]->taxonomic_status; //seems to be 'accepted'
            $obj->source_of_synonymy = $traits[0]->source_of_synonymy;
            $obj->rank = '';
            return $obj;
        }
    }
    private function write_traits($obj, $traits)
    {   //print_r($obj); print_r($traits); exit("\nditox 1\n");
        /*stdClass Object(
            [taxonID] => 1960
            [taxon] => Abarenicola pacifica
            [author] => Healy & Wells, 1959
            [validID] => 1960
            [valid_taxon] => Abarenicola pacifica
            [valid_author] => Healy & Wells, 1959
            [status] => accepted
            [source_of_synonymy] => 
            [rank] => Species
        )
        [0] => stdClass Object(
                [taxon] => Abarenicola pacifica
                [author] => Healy & Wells, 1959
                [valid_taxon] => Abarenicola pacifica
                [valid_author] => Healy & Wells, 1959
                [taxonomic_status] => 
                [source_of_synonymy] => 
                [parent] => Abarenicola
                [trait] => Parental care/ Brood protection
                [modality] => yes
                [modality_abbreviation] => BP_YES
                [traitvalue] => 1
                [reference] => Rouse, G.W., Pleijel, F. (2001) Polychaetes. Oxford University Press,Oxford.354pp.
                [doi] => 
                [value_creator] => Dimitra Mavraki
                [value_creation_date] => 2014-02-18 14:51:07
                [text_excerpt] => p41:"In Abarenicola pacifica(as Abarenicola claparedii), the males discharge simple spermatophores into the water. These may enter the tube of a female and burst when struck by her chaetae. The fertilised eggs form an "egg tube" around the mid -region of the female"
                [text_excerpt_creator] => Dimitra Mavraki
                [text_excerpt_creation_date] => 2014-02-18 14:54:05
            )
        */
        foreach($traits as $t) {

            if($val = @$this->mTypes[$t->trait]) {
                $mType = $val;
                /* block that excludes mTypes not found in the old resource
                if(!isset($this->old_unique_mTypes[$mType])) {
                    $this->debug['excluded mTypes'][$t->trait][$mType] = '';
                    return;
                }
                */
            }
            else {
                print_r($this->mTypes);
                print_r($t);
                exit("\n[$t->trait] - no mType uri yet\n");
            }
            if($val = @$this->terms_info[$t->trait][trim($t->modality)]['identifier']) $mValue = $val;
            else exit("\nInvestigate: no uri for this value: [$t->trait][$t->modality]\n");

            $rec = array();
            $rec["taxon_id"] = $obj->taxonID;
            $json = json_encode($t); // exit("\n$json\n");
            $rec["catnum"] = md5($json);
            $rec['measurementDeterminedDate'] = $t->value_creation_date;
            $rec['measurementRemarks'] = self::clean_text($t->text_excerpt);
            $rec['source'] = $this->taxon_page.$obj->taxonID; //"http://polytraits.lifewatchgreece.eu"; //same value for all
            $rec['bibliographicCitation'] = "Polytraits Team (2023). Polytraits: A database on biological traits of polychaetes.. LifewatchGreece, Hellenic Centre for Marine Research. Accessed on 2023-05-30. Available from http://polytraits.lifewatchgreece.eu";
            $rec['contributor'] = self::get_or_add_contributor(trim($t->value_creator), trim($t->text_excerpt_creator)); //e.g. "https://orcid.org/0000-0001-9613-8300"
            if($reference_ids = self::get_or_add_reference(trim($t->reference))) $rec['referenceID'] = implode("; ", $reference_ids); //e.g. "1406"
            // print_r($rec); exit("\n[$mType] [$mValue]\n");

            // /* New: filters by Jen - email from Aug 1, 2023. Eli moved it to Jira: https://eol-jira.bibalex.org/browse/DATA-1919?focusedCommentId=67704&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-67704
            if($ret = self::format_trait($rec, $mValue, $mType)) {
                $rec    = $ret['rec'];
                $mValue = $ret['mValue'];
                $mType  = $ret['mType'];    
            }
            else continue; //discard record
            // */

            $this->func->add_string_types($rec, $mValue, $mType, "true");
        } //end foreach()
    }
    private function clean_text($str)
    {
        $str = str_replace('\"', '"', $str);
        return $str;
    }
    private function format_trait($rec, $mValue, $mType)
    {   
        /* Array(
            [taxon_id] => 1960
            [catnum] => a75b7e956513d683f8a35f774e8d9d55
            [measurementDeterminedDate] => 2014-02-18 14:51:07
            [measurementRemarks] => p41:"In Abarenicola pacifica(as Abarenicola claparedii), the males discharge simple spermatophores into the water. These may enter the tube of a female and burst when struck by her chaetae. The fertilised eggs form an "egg tube" around the mid -region of the female"
            [source] => http://polytraits.lifewatchgreece.eu/taxonpage/1960
            [bibliographicCitation] => Polytraits Team (2023). Polytraits: A database on biological traits of polychaetes.. LifewatchGreece, Hellenic Centre for Marine Research. Accessed on 2023-05-30. Available from http://polytraits.lifewatchgreece.eu
            [contributor] => https://orcid.org/0000-0001-6775-530X
            [referenceID] => 20d61805546c4690300a8051699ec4d4
        )
        mType  = [http://purl.obolibrary.org/obo/GO_0060746] 
        mValue = [http://polytraits.lifewatchgreece.eu/terms/BP_YES] */

        /* measurementTypes
        http://purl.obolibrary.org/obo/GO_0044402, http://eol.org/schema/terms/preysUpon, http://polytraits.lifewatchgreece.eu/terms/PRED, 
        http://polytraits.lifewatchgreece.eu/terms/EP_PAR,  http://polytraits.lifewatchgreece.eu/terms/SOC:  filter out; we get these through GloBI */
        $excluded_mTypes = array('http://purl.obolibrary.org/obo/GO_0044402', 'http://eol.org/schema/terms/preysUpon', 'http://polytraits.lifewatchgreece.eu/terms/PRED', 'http://polytraits.lifewatchgreece.eu/terms/EP_PAR', 'http://polytraits.lifewatchgreece.eu/terms/SOC');
        if(in_array($mType, $excluded_mTypes)) return false;
        // ---end---
        /* measurementTypes
        http://purl.obolibrary.org/obo/CMO_0000013 -> http://eol.org/schema/terms/SizeClass
        http://polytraits.lifewatchgreece.eu/terms/EGG -> http://eol.org/schema/terms/SizeClass with lifestage=http://eol.org/schema/terms/eggStage
        http://polytraits.lifewatchgreece.eu/terms/JMOB -> http://purl.obolibrary.org/obo/GO_0040011 with livestage=http://purl.obolibrary.org/obo/PATO_0001190
        http://polytraits.lifewatchgreece.eu/terms/FEED -> http://eol.org/schema/terms/TrophicGuild */
        if($mType == 'http://purl.obolibrary.org/obo/CMO_0000013') $mType = 'http://eol.org/schema/terms/SizeClass';
        if($mType == 'http://polytraits.lifewatchgreece.eu/terms/EGG') {
            $mType = 'http://eol.org/schema/terms/SizeClass';
            $rec['occur']['lifeStage'] = 'http://eol.org/schema/terms/eggStage';
        }
        if($mType == 'http://polytraits.lifewatchgreece.eu/terms/JMOB') {
            $mType = 'http://purl.obolibrary.org/obo/GO_0040011';
            $rec['occur']['lifeStage'] = 'http://purl.obolibrary.org/obo/PATO_0001190';
        }
        if($mType == 'http://polytraits.lifewatchgreece.eu/terms/FEED') $mType = 'http://eol.org/schema/terms/TrophicGuild';
        // ---end---
        /* measurementValues
        http://purl.obolibrary.org/obo/ENVO_00000054 -> http://purl.obolibrary.org/obo/ENVO_01000022
        http://polytraits.lifewatchgreece.eu/terms/HAB_ALG -> https://www.wikidata.org/entity/Q37868
        http://purl.obolibrary.org/obo/ENVO_00000045 -> http://purl.obolibrary.org/obo/ENVO_01000020
        http://purl.obolibrary.org/obo/ENVO_01000122 -> http://purl.obolibrary.org/obo/ENVO_01000030
        http://eol.org/schema/terms/predator -> https://www.wikidata.org/entity/Q170430
        http://purl.bioontology.org/ontology/MESH/D060434 -> https://www.wikidata.org/entity/Q59099
        http://polytraits.lifewatchgreece.eu/terms/EP -> http://purl.obolibrary.org/obo/ENVO_03600009
        http://polytraits.lifewatchgreece.eu/terms/SM_YES ->  http://eol.org/schema/terms/yes
        http://polytraits.lifewatchgreece.eu/terms/SM_NO -> http://eol.org/schema/terms/no */
        $mValue = self::format_mValue($mValue);
        // ---end---
        /* http://polytraits.lifewatchgreece.eu/terms/MOB_SESS -> http://www.wikidata.org/entity/Q1759860
        ^ this value term is extra finicky, and I don't think we were doing it quite right before. 
        It will, I think, come with measurementType=  http://purl.obolibrary.org/obo/GO_0040011 (or JMOB, to be mapped to GO_0040011). 
        For this value only, the measurementType should be changed to http://www.wikidata.org/entity/Q33596. 
        (Motility is a yes/no, motile or sessile. If you ARE motile, locomotion is HOW you get around. I know, picky picky...) */
        if($mValue == "http://polytraits.lifewatchgreece.eu/terms/MOB_SESS") {
            if($mType == "http://purl.obolibrary.org/obo/GO_0040011") {
                $mValue = 'http://www.wikidata.org/entity/Q1759860';
                $mType = 'http://www.wikidata.org/entity/Q33596';
            }
        }
        // ---end---
        /* http://polytraits.lifewatchgreece.eu/terms/RW
        ^ this measurementType has records we keep and records we discard. 
        We keep their records with more specific values, eg: http://polytraits.lifewatchgreece.eu/terms/RW_DIFF, 
        but we discard records with these two (less useful?) values:
        http://polytraits.lifewatchgreece.eu/terms/RW_YES and http://polytraits.lifewatchgreece.eu/terms/RW_NO */
        if($mType == 'http://polytraits.lifewatchgreece.eu/terms/RW') {
            if(in_array($mValue, array('http://polytraits.lifewatchgreece.eu/terms/RW_YES', 'http://polytraits.lifewatchgreece.eu/terms/RW_NO'))) return false;
        }
        // ---end---
        return array('rec' => $rec, 'mValue' => $mValue, 'mType' => $mType);
    }
    private function format_mValue($mValue)
    {   switch ($mValue) {
            case "http://purl.obolibrary.org/obo/ENVO_00000054":
                return 'http://purl.obolibrary.org/obo/ENVO_01000022';
            case "http://polytraits.lifewatchgreece.eu/terms/HAB_ALG":
                return 'https://www.wikidata.org/entity/Q37868';
            case "http://purl.obolibrary.org/obo/ENVO_00000045":
                return 'http://purl.obolibrary.org/obo/ENVO_01000020';
            case "http://purl.obolibrary.org/obo/ENVO_01000122":
                return 'http://purl.obolibrary.org/obo/ENVO_01000030';
            case "http://eol.org/schema/terms/predator":
                return 'https://www.wikidata.org/entity/Q170430';
            case "http://purl.bioontology.org/ontology/MESH/D060434":
                return 'https://www.wikidata.org/entity/Q59099';                
            case "http://polytraits.lifewatchgreece.eu/terms/EP":
                return 'http://purl.obolibrary.org/obo/ENVO_03600009';
            case "http://polytraits.lifewatchgreece.eu/terms/SM_YES":
                return 'http://eol.org/schema/terms/yes';
            case "http://polytraits.lifewatchgreece.eu/terms/SM_NO":
                return 'http://eol.org/schema/terms/no';
            default:
                return $mValue;
        }
        return $mValue;
    }
    function get_name_info($sciname)
    {   if(!$sciname) return;

        // /*
        if(isset($this->customized_scinames[$sciname])) {
            $obj = self::customized_obj($sciname, $this->customized_scinames[$sciname]);
            return $obj;
        }
        // */

        // echo "-Searching [$sciname]-";
        $options = $this->download_options;
        $options['expire_seconds'] = false; //doesn't expire
        $url = str_ireplace('SCINAME', urlencode($sciname), $this->service['name info']);
        if($json = Functions::lookup_with_cache($url, $options)) {
            $objs = json_decode($json);
            // print_r($objs); exit;
            /* sample valid taxon Array(
                [0] => stdClass Object(
                        [taxonID] => 1960
                        [taxon] => Abarenicola pacifica
                        [author] => Healy & Wells, 1959
                        [validID] => 1960
                        [valid_taxon] => Abarenicola pacifica
                        [valid_author] => Healy & Wells, 1959
                        [status] => accepted
                        [source_of_synonymy] => 
                        [rank] => Species
                    )
            )*/
            if(count($objs) == 1) return $objs[0];
            else {
                // print_r($objs); echo("\nInvestigate: multiple results\n");
                foreach($objs as $obj) {
                    @$status[$obj->status]++;
                }
                print_r($status); //exit;
                if(@$status['accepted'] == 1) {
                    foreach($objs as $obj) {
                        if($obj->status == 'accepted') return $obj; //return the only accepted taxon
                    }
                }
                elseif(@$status['accepted'] == 0) return false; //exit("\nInvestigate: zero accepted names\n");
                else                              exit("\nInvestigate: multiple accepted names\n");
            }
        }
    }
    private function get_terms_info()
    {   /*<table class='db_docu' id='DZ'>
                <tr>
                    <th colspan=2 class='traitheader'>Depth zonation (benthos)</th>
                </tr>
                <tbody>
                    <tr class='mod_sub_rows'>
                    <td class='traitdefinition' style='width:250px;'>Definition</td>
                    <td class='traitdefinition' >The depth at which an organism occurs in the water column. Commonly defined based on ecological features of the zonation.</td>
                    </tr>
                    <tr class='mod_sub_rows'>
                    <td class='traitdefinition'>Identifier</td>
                    <td class='traitdefinition'><a href='http://polytraits.lifewatchgreece.eu/terms/DZ' target='_blank'>http://polytraits.lifewatchgreece.eu/terms/DZ</a></td>
                    </tr>		    
                    <tr class='mod_sub_rows'>
                    <td class='traitdefinition'>Related terms</td>
                    <td class='traitdefinition'>maximum bottom depth</td>
                    </tr>
                    <tr class='mod_sub_rows'>
                    <td class='traitdefinition'>Additional explanations</td>
                    <td class='traitdefinition'></td>
                    </tr>		    
                    <tr>
                    <td class='trait_docu_subheader' colspan=2>Modalities</td>        
        */
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->service['terms list'], $options)) {
            /* measurementTypes */
            if(preg_match_all("/class=\'db_docu\'(.*?)class=\'trait_docu_subheader\'/ims", $html, $arr)) {
                // print_r($arr[1]); echo "\n".count($arr[1]).'\n'; exit;
                // <th colspan=2 class='traitheader'>Substrate type of settlement</th>
                foreach($arr[1] as $str) {
                    if(preg_match("/class=\'traitheader\'>(.*?)<\/th>/ims", $str, $arr2)) $trait = trim($arr2[1]);
                    else exit("\nNo trait name...\n");
                    if(preg_match("/href=\'(.*?)\'/ims", $str, $arr2)) $trait_uri = trim($arr2[1]);
                    else exit("\nNo trait uri...\n");
                    $final[$trait] = $trait_uri;
                }
            }
            $mTypes = array_keys($final);
            // print_r($mTypes); print_r($final); echo "".count($final)." terms\n"; exit;
            $this->mTypes = $final;

            /* measurementValues */
            if(preg_match_all("/>Modalities<\/td>(.*?)<\/tr><\/tbody><\/table>/ims", $html, $arr)) {
                // print_r($arr[1]); echo "\n".count($arr[1])."\n"; exit;
                $values = array(); $i = -1;
                foreach($arr[1] as $str) { $i++; //echo "\n$str\n";
                    
                    if(preg_match_all("/onclick=\'expand_close(.*?)<\/table>/ims", $str, $arr2)) { //$str is entire block of all values of a mType
                        // print_r($arr2[1]); exit;
                        foreach($arr2[1] as $str2) { //echo "\n$str2\n"; //good debug
                            if(preg_match("/<td colspan=2>(.*?)<\/td>/ims", $str2, $arr3)) { //$str2 is for a single value
                                $string_value = trim($arr3[1]);
                                $left = '<span '; $right = '</span>'; //cannot use strip_tags()
                                $string_value = self::remove_all_in_between_inclusive($left, $right, $string_value);
                                $string_value = trim(str_replace("&nbsp;", "", $string_value));
                            }
                            if(preg_match("/Definition<\/td>(.*?)<\/td>/ims", $str2, $arr3)) { //$str2 is for a single value
                                $definition = trim(strip_tags($arr3[1]));
                                $a['definition'] = $definition;
                            }
                            if(preg_match("/Identifier<\/td>(.*?)<\/td>/ims", $str2, $arr3)) { //$str2 is for a single value
                                $identifier = trim(strip_tags($arr3[1]));
                                $a['identifier'] = $identifier;
                            }
                            $values[$mTypes[$i]][$string_value] = $a;
                            // print_r($values); echo "".count($values)." values\n"; exit("\n000\n"); //good debug per single value        
                        }
                        // print_r($values); echo "".count($values)." values\n"; exit("\n111\n"); //good debug - all values of a single mType
                    }
                } //end foreach()
                // print_r($values); echo "".count($values)." values\n"; //exit("\n222\n"); //good debug - all values of all mTypes
            }
        }
        // print_r($values['Substrate type of settlement']); exit; //debug
        // testing:
        if(count($values) == 48) echo "\nTotal count test OK.";
        if($values['Substrate type of settlement']['boulders']['identifier'] == 'http://purl.obolibrary.org/obo/ENVO_01000114') echo "\nIdentifier test 1 OK.";            
        if($values['Substrate type']['mixed']['identifier'] == 'http://polytraits.lifewatchgreece.eu/terms/SUBST_MIX') echo "\nIdentifier test 2 OK.";            
        if($values['Body size (max)']['<2.5 mm']['identifier'] == 'http://polytraits.lifewatchgreece.eu/terms/BS_1') echo "\nIdentifier test 3 OK."; //exit;
        return $values;
    }
    public function remove_all_in_between_inclusive($left, $right, $html, $includeRight = true)
    {
        if(preg_match_all("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
            foreach($arr[1] as $str) {
                if($includeRight) { //original
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, '', $html);
                }
                else { //meaning exclude right
                    $substr = $left.$str.$right;
                    $html = str_ireplace($substr, $right, $html);
                }
            }
        }
        return $html;
    }

    // [taxon] => Abarenicola pacifica
    // [author] => Healy & Wells, 1959
    // [valid_taxon] => Abarenicola pacifica
    // [valid_author] => Healy & Wells, 1959
    // [taxonomic_status] => 
    // [source_of_synonymy] => 
    // [parent] => Abarenicola

    private function write_taxon($obj)
    {   /*stdClass Object(
            [taxonID] => 1960
            [taxon] => Abarenicola pacifica
            [author] => Healy & Wells, 1959
            [validID] => 1960
            [valid_taxon] => Abarenicola pacifica
            [valid_author] => Healy & Wells, 1959
            [status] => accepted
            [source_of_synonymy] => 
            [rank] => Species
        )
        sample synonym taxon stdClass Object(
            [taxonID] => 1249
            [taxon] => Alciopidae
            [author] => Ehlers, 1864
            [validID] => 1249
            [valid_taxon] => Alciopidae
            [valid_author] => Ehlers, 1864
            [status] => subjective synonym
            [source_of_synonymy] => Rouse, G.W., Pleijel, F. (2001) Polychaetes. Oxford University Press,Oxford.354pp.
            [rank] => Family
        )*/
        @$this->debug['status values'][$obj->status]++;

        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $obj->taxonID;
        $taxon->scientificName              = $obj->taxon;
        $taxon->scientificNameAuthorship    = $obj->author;
        $taxon->taxonRank                   = strtolower($obj->rank);
        $taxon->source                      = $this->taxon_page.$obj->taxonID; //furtherInformationURL
        $taxon->taxonomicStatus             = $obj->status;
        if($obj->status == 'accepted') {
            if(isset($obj->parentNameUsageID)) {
                if($val = @$obj->parentNameUsageID) $taxon->parentNameUsageID = $val;
            }
            else                                    $taxon->parentNameUsageID = self::get_parentID_of_name($obj->taxon);
        }

        if(stripos($obj->status, "synonym") !== false) { //string is found
            // $taxon->acceptedNameUsageID = self::get_taxon_info_of_name($obj->valid_taxon, 'taxonID');
        }
        elseif($obj->status != 'accepted') return;

        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
    }
    private function get_parentID_of_name($sciname)
    {
        $taxon_id = self::get_taxon_info_of_name($sciname, 'taxonID');
        $ancestry = self::get_ancestry($taxon_id); //print_r($ancestry); exit;
        // /* solution for having undefined parents
        self::write_ancestry($ancestry);
        // */
        $ret = end($ancestry); // print_r($ret); exit("\n-end ancestry-\n");
        if($val = @$ret['taxonID']) return $val;
    }
    private function write_ancestry($ancestry)
    {
        $ancestry = array_reverse($ancestry);
        // print_r($ancestry); //exit("\nxxx\n");
        /*Array(
            [0] => Array(
                    [sciname] => Abarenicola
                    [rank] => genus
                    [taxonID] => 1957
                    [profile] => stdClass Object(
                            [taxonID] => 1957
                            [taxon] => Abarenicola
                            [author] => Wells, 1959
                            [validID] => 1957
                            [valid_taxon] => Abarenicola
                            [valid_author] => Wells, 1959
                            [status] => accepted
                            [source_of_synonymy] => 
                            [rank] => Genus
                        )
                )
        */

        if(!$ancestry) return;

        // /* assign parentNameUsageID
        $i = -1;
        foreach($ancestry as $r) { $i++;
            if(isset($ancestry[$i]['profile'])) {
                $ancestry[$i]['profile']->parentNameUsageID = @$ancestry[$i+1]['taxonID'];
            }
        }
        // */
        // print_r($ancestry); exit("\nyyy\n");
        foreach($ancestry as $r) {
            if(@$r['profile']) self::write_taxon($r['profile']);
        }
    }
    private function get_taxon_info_of_name($sciname, $what = 'all')
    {   if(!$sciname) return;
        if($obj = self::get_name_info($sciname)) {
            if($what == 'taxonID') {
                if($val = $obj->taxonID) return $val;
            }
            elseif($what == 'all') return $obj;    
        }
        exit("\nInvestigate: cannot locate sciname: [$sciname]\n");
    }
    function get_ancestry($taxon_id)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->taxon_page.$taxon_id, $options)) {
            $left = "<span class='taxonpath'>";
            $right = "</span>";
            // <span class='taxonpath'>Polychaeta (Class)  > Polychaeta Palpata (Subclass)  > Phyllodocida (Order)  > Nephtyidae (Family)  >  <i> Aglaophamus</i> (Genus) </span>
            if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $html, $arr)) {
                // print_r($arr[1]); exit;
                $str = $arr[1];
                $arr = explode(" > ", $str);
                $arr = array_map('trim', $arr);
                // print_r($arr); //exit;
                /*Array(
                    [0] => Polychaeta (Class)
                    [1] => Polychaeta Palpata (Subclass)
                    [2] => Terebellida (Order)
                    [3] => Acrocirridae (Family)
                    [4] => <i> Acrocirrus</i> (Genus)
                )*/
                $reks = array();
                foreach($arr as $string) {
                    $string = trim(strip_tags($string));
                    $rek = array();
                    $rek['sciname'] = trim(preg_replace('/\s*\([^)]*\)/', '', $string)); //remove parenthesis OK
                    $rek['rank'] = self::get_string_between("(", ")", $string);
                    if($ret = self::get_taxon_info_of_name($rek['sciname'], 'all')) {
                        $rek['taxonID'] = $ret->taxonID;
                        $rek['profile'] = $ret;    
                    }
                    // print_r($ret); exit;
                    $reks[] = $rek;
                }
                // print_r($reks); exit; //good debug
                return $reks;
            }
        }
    }
    private function get_or_add_contributor($value_creator, $text_excerpt_creator)
    {
        if($agent_name = $value_creator) {}
        elseif($agent_name = $text_excerpt_creator) {}
        else exit("\nBlank agent names\n");
        if($agent_uri = @$this->contributor_mappings[$agent_name]) return $agent_uri;
        else $this->debug['creator no uri yet'][$agent_name] = '';
    }
    private function get_or_add_reference($reference)
    {
        $reference_ids = array();
        $r = new \eol_schema\Reference();
        $r->full_reference = self::clean_text($reference);
        $r->identifier = md5($r->full_reference);
        // $r->uri = '';
        $reference_ids[] = $r->identifier;
        if(!isset($this->reference_ids[$r->identifier])) {
            $this->reference_ids[$r->identifier] = '';
            $this->archive_builder->write_object_to_file($r);
        }
        return array_unique($reference_ids);
    }
    private function get_string_between($left, $right, $string)
    {
        if(preg_match("/".preg_quote($left, '/')."(.*?)".preg_quote($right, '/')."/ims", $string, $arr)) return strtolower(trim($arr[1]));
        return;
    }
    // =========================================================================================
    // ========================================================================================= copied template below
    // =========================================================================================
    function lookup_profile($symbol)
    {
        $url = $this->serviceUrls->plantsServicesUrl.'PlantProfile?symbol='.$symbol;
        // https://plantsservices.sc.egov.usda.gov/api/PlantProfile?symbol=ABBA
        if($json = Functions::lookup_with_cache($url, $this->download_options)) return $json;
    }
    private function process_rec($rec)
    {   /*Array(
        [Symbol] => ABAB
        [Synonym Symbol] => 
        [Scientific Name with Author] => Abutilon abutiloides (Jacq.) Garcke ex Hochr.
        [Common Name] => shrubby Indian mallow
        [Family] => Malvaceae
        )*/
        // $rec['Symbol'] = "ACOCC"; //"ABBA"; //force assign | ABES with copyrights | ACOCC subspecies
        if($json = self::lookup_profile($rec['Symbol'])) {
            $profile = json_decode($json); //print_r($profile); exit;
            /*Array( PlantProfile
                [Id] => 65791
                [Symbol] => ABAB
                [ScientificName] => <i>Abutilon abutiloides</i> (Jacq.) Garcke ex Hochr.
                [ScientificNameComponents] => 
                [CommonName] => shrubby Indian mallow
                [Group] => Dicot
                [RankId] => 180
                [Rank] => Species
                ...and many more...even trait data
            */

            // /*
            if(!isset($profile->Id)) {
                self::create_taxon_archive_using_rec($rec);
                return;
            }
            // */

            echo "\nSymbol: ".$rec['Symbol']." | Plant ID: $profile->Id | HasImages: $profile->HasImages"; //exit;
            $this->symbol_plantID_info[$rec['Symbol']] = $profile->Id;
            self::create_taxon_archive($rec, $profile);
            self::create_taxon_ancestry($profile);

            if($profile->HasImages > 0) {
                if($imgs = self::get_images($profile)) {
                    /* moved up, since we are now adding synonyms as well. Some taxa have synonyms but no images.
                    self::create_taxon_archive($rec, $profile);
                    self::create_taxon_ancestry($profile);
                    */
                    self::create_media_archive($profile->Id, $imgs);
                }
                // exit;
            }
            else {
                echo " | no images";
                // print_r($profile); exit("\nhas no images!\n");
            }
        }
        else exit("\ncannot lookup\n");
    }
    
    private function write_synonym($rec)
    {   /*Array( e.g. synonym record
        [Symbol] => ABAB
        [Synonym Symbol] => ABAM5
        [Scientific Name with Author] => Abutilon americanum (L.) Sweet
        [Common Name] => 
        [Family] => 
        )*/
        if($acceptedNameUsageID = @$this->symbol_plantID_info[$rec['Symbol']]) {}
        else {
            return; //no acceptedNameUsageID for this synonym
            print_r($rec);
            exit("\nInvestigate: no acceptedNameUsageID\n");
        }
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec['Synonym Symbol'];
        /* copied template, but does not work here
        $ret = self::parse_sciname($profile->ScientificName);
        $taxon->scientificName              = $ret['sciname'];
        $taxon->scientificNameAuthorship    = $ret['author'];
        */
        $taxon->scientificName = $rec['Scientific Name with Author'];
        $taxon->taxonomicStatus          = 'synonym';
        $taxon->acceptedNameUsageID      = $acceptedNameUsageID;
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
    }
    private function create_vernacular($rec)
    {   //print_r($rec); exit;
        if(!$rec['Common Name']) return;
        $v = new \eol_schema\VernacularName();
        $v->taxonID         = $rec["taxonID"];
        $v->vernacularName  = $rec['Common Name'];
        $v->language        = "en";
        $vernacular_id = md5("$v->taxonID|$v->vernacularName|$v->language");
        if(!isset($this->vernacular_name_ids[$vernacular_id])) {
           $this->vernacular_name_ids[$vernacular_id] = '';
           $this->archive_builder->write_object_to_file($v);
        }
    }
    private function format_description($img)
    {
        $tmp = "";
        if($val = $img->Title) $tmp .= $val.". ";
        if($val = $img->Comment) $tmp .= $val.". ";
        if($val = $img->CommonName) $tmp .= $val.". ";
        if($val = $img->ProvidedBy) $tmp .= "Provided by ".$val.". ";
        if($val = $img->ImageLocation) $tmp .= $val.". ";
        return trim($tmp);
    }
    private function format_agents($img)
    {   /*[0] => stdClass Object(
                    [ImageID] => 157
                    [StandardSizeImageLibraryPath] => /ImageLibrary/standard/abli_001_shp.jpg
                    [ThumbnailSizeImageLibraryPath] => /ImageLibrary/thumbnail/abli_001_thp.jpg
                    [LargeSizeImageLibraryPath] => /ImageLibrary/large/abli_001_lhp.jpg
                    [OriginalSizeImageLibraryPath] => /ImageLibrary/original/abli_001_php.jpg
                    [Copyright] => 
                    [CommonName] => Tracey Slotta
                    [Title] => 
                    [ImageCreationDate] => 
                    [Collection] => 
                    [InstitutionName] => ARS Systematic Botany and Mycology Laboratory
                    [ImageLocation] => United States, Texas
                    [Comment] => 
                    [EmailAddress] => 
                    [LiteratureTitle] => 
                    [LiteratureYear] => 0
                    [LiteraturePlace] => 
                    [ProvidedBy] => ARS Systematic Botany and Mycology Laboratory
                    [ScannedBy] => 
                )
        */
        $agent_ids = array();
        if($agent_name = $img->InstitutionName) {
            $tmp_ids = self::create_agent($agent_name, 'source');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        if($agent_name = $img->ProvidedBy) {
            $tmp_ids = self::create_agent($agent_name, 'source');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        if($agent_name = $img->CommonName) {
            $tmp_ids = self::create_agent($agent_name, 'photographer');
            $agent_ids = array_merge($agent_ids, $tmp_ids);
        }
        $agent_ids = array_filter($agent_ids); //remove null arrays
        $agent_ids = array_unique($agent_ids); //make unique
        $agent_ids = array_values($agent_ids); //reindex key
        return $agent_ids;
    }
    private function create_agent($agent_name, $role)
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = $agent_name;
        $r->agentRole       = $role;
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = '';
        $agent_ids[] = $r->identifier;
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $agent_ids;
    }
    private function get_parent_id($profile)
    {   // print_r($profile->Ancestors); //exit;
        $index = count($profile->Ancestors) - 2; //to get index of immediate parent
        // print_r($profile->Ancestors[$index]); exit("\n".$profile->Ancestors[$index]->Id."\n");
        return $profile->Ancestors[$index]->Id;
    }
    private function create_taxon_ancestry($profile)
    {
        $i = -1;
        foreach($profile->Ancestors as $a) { $i++; //print_r($a);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID                     = $a->Id;
            $taxon->parentNameUsageID   = @$profile->Ancestors[$i-1]->Id;
            // /* old
            if($a->ScientificName) {
                $ret = self::parse_sciname($a->ScientificName);
                $taxon->scientificName              = $ret['sciname'];
                $taxon->scientificNameAuthorship    = $ret['author'];    
            }
            else {
                print_r($profile); print_r($a);
                exit("\ninvestigate no sciname in ancestry list\n");
            }
            // */

            $taxon->taxonRank                   = strtolower($a->Rank);
            $taxon->source = 'https://plants.usda.gov/home/plantProfile?symbol='.$a->Symbol; //furtherInformationURL
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);    
            }
            /* start vernaculars */
            $rec = array();
            $rec['taxonID']     = $a->Id;
            $rec['Common Name'] = $a->CommonName;
            self::create_vernacular($rec);
        }
    }

    private function create_taxon_archive_using_rec($rec)
    {   /*Array(
        [Symbol] => ABAB
        [Synonym Symbol] => 
        [Scientific Name with Author] => Abutilon abutiloides (Jacq.) Garcke ex Hochr.
        [Common Name] => shrubby Indian mallow
        [Family] => Malvaceae
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec['Symbol'];
        $ret = self::parse_sciname($rec['Scientific Name with Author']);
        $taxon->scientificName              = $ret['sciname'];
        $taxon->scientificNameAuthorship    = $ret['author'];    
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);    
        }
    }
    private function get_US_states_list()
    {
        $options = $this->download_options;
        $options['expire_seconds'] = 60*60*24*30*12; //1 year
        $tsv_file = Functions::save_remote_file_to_local($this->github['US State list'], $options);
        $out = shell_exec("wc -l ".$tsv_file); echo "\nUS States/Territories: $out";
        $i = 0;
        foreach(new FileIterator($tsv_file) as $line_number => $line) { $i++;
            $row = explode("\t", $line);
            $abbrev = $row[1];
            $state_name = $row[0];
            $this->US_abbrev_state[$abbrev] = $state_name;
        }
        unlink($tsv_file);
    }
    // =========================================================================================
    // ========================================================================================= copied template below
    // =========================================================================================
    private function download_and_extract_remote_file($file = false, $use_cache = false)
    {
        if(!$file) $file = $this->data_dump_url; // used when this function is called elsewhere
        $download_options = $this->download_options;
        $download_options['timeout'] = 172800;
        $download_options['file_extension'] = 'txt.zip';
        $download_options['expire_seconds'] = 60*60*24*30;
        if($use_cache) $download_options['cache'] = 1;
        // $download_options['cache'] = 0; // 0 only when developing //debug - comment in real operation
        $temp_path = Functions::save_remote_file_to_local($file, $download_options);
        echo "\nunzipping this file [$temp_path]... \n";
        shell_exec("unzip -o " . $temp_path . " -d " . DOC_ROOT."tmp/"); //worked OK
        unlink($temp_path);
        if(is_dir(DOC_ROOT."tmp/"."__MACOSX")) recursive_rmdir(DOC_ROOT."tmp/"."__MACOSX");
    }
    //==================================================================================================================
    // private function process_record($taxid)
    // {
    //     self::create_trait_archive($a);
    // }
    private function create_trait_archive($a)
    {
        /*            */
        if($val = @$a['stats']['publicrecords']) {
            $rec = array();
            $rec["taxon_id"]            = $a['taxid'];
            $rec["catnum"]              = self::generate_id_from_array_record($a);
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/NumberPublicRecordsInBOLD";
            $rec['measurementValue']    = $val;
            $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
            self::add_string_types($rec);
        }
    }
    private function add_string_types($rec, $a = false) //$a is only for debugging
    {
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"], $rec);
        unset($rec['catnum']);
        unset($rec['taxon_id']);
        
        $m = new \eol_schema\MeasurementOrFact();
        $m->occurrenceID = $occurrence_id;
        foreach($rec as $key => $value) $m->$key = $value;
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        if(!isset($this->measurement_ids[$m->measurementID])) {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->measurementID] = '';
        }
    }
    private function add_occurrence($taxon_id, $catnum, $rec)
    {
        $occurrence_id = $catnum;
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        if($val = @$rec['lifestage']) $o->lifeStage = $val;
        $o->taxonID = $taxon_id;

        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');
        
        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;
    }
}
?>