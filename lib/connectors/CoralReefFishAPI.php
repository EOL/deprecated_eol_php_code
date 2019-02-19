<?php
namespace php_active_record;
// connector: [765]
class CoralReefFishAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        $this->SPM = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems';
        $this->EOL = 'http://eol.org/schema/eol_info_items.xml';
        $this->domain = "http://www.coralreeffish.com/";
        $this->family_list_page = $this->domain . "larvae.html";
        $this->gobiidae_page = $this->domain . "gobiidae.html";
        $this->download_options = array("download_wait_time" => 1000000, "timeout" => 1800, "delay_in_minutes" => 1); // "expire_seconds" => 0
        if(Functions::is_production()) $this->download_options['resource_id'] = "765";
        $this->records = array();
        $this->owner = "www.coralreeffish.com by Benjamin Victor";
    }
    function get_all_taxa()
    {
        $families = self::get_family_list_urls();
        // Gobiidae pages:
        $families[] = "http://www.coralreeffish.com/gobiidae1.html";
        $families[] = "http://www.coralreeffish.com/gobiidae2.html";
        $families[] = "http://www.coralreeffish.com/gobiidae3.html";
        $families[] = "http://www.coralreeffish.com/gobiidae4.html";
        $families[] = "http://www.coralreeffish.com/gobiidae5.html";
        foreach($families as $name => $url) {
            $name = self::clean_string($name);
            echo "\n $name $url\n";
            self::process_family($name, $url);
        }
        self::process_records();
        self::process_gobiidae_structured_data();
        $this->create_archive();
    }
    private function process_records()
    {
        foreach($this->records as $record) {
            foreach($record as $taxon => $rec) {
                if($taxon == "Hypleurochilus pseudaequipinnus =Hypleurochilus aequipinnus") $taxon = "Hypleurochilus pseudaequipinnus";
                if(in_array($taxon, array("Starksia sluiteri complex S. sluiteri S. fasciata S. langi S. greenfieldi S. y-lineata S. smithvanizi", "Starksia lepicoelia complex S. lepicoelia S. robertsoni S. weigti S. williamsi", "Starksia ocellata complex S. ocellata S. occidentalis S. culebrae S. guttata S. variabilis &nbsp;", "Starksia atlantica complex S. atlantica S. springeri S. sangreyae &nbsp;"))) $taxon = "Starksia";
                if($taxon == "Ocyurus chrysurus (Lutjanus chrysurus)") $taxon = "Ocyurus chrysurus";
                $taxon = trim(str_ireplace(array("Notes on", "Early", "Identifying transitional", "The 14-rayed", "The 12-rayed", "+", "type 2", "sp. A,", "sp.", "subfamily", "larva,", "?", " species"), "", $taxon));
                if($taxon == "Sicydium gymnogaster/plumieri") $taxon = "Sicydium gymnogaster";
                if(in_array($taxon, array("Chriolepis/Psilotris  23 ()", "Lythrypnus vs. Coryphopterus", "Elacatinus/Gobiosoma vs. the six-spined gobies"))) continue;
                if($taxon == "Lythrypnus  223") $taxon = "Lythrypnus";
                if($taxon == "Tigrigobius multifasciatus, panamensis, rubrigenis") $taxon = "Tigrigobius multifasciatus";
                if($taxon == "the Elacatinus cleaner/sponge gobies (neon gobies)") $taxon = "Elacatinus";
                if(in_array($taxon, array("Gobiidae1", "Gobiidae2", "Gobiidae3", "Gobiidae4", "Gobiidae5"))) $taxon = "Gobiidae";
                // echo "\n taxon:[$taxon]";
                $rec["sciname"] = $taxon;
                $rec["taxon_id"] = str_replace(" ", "_", $taxon);
                self::create_instances_from_taxon_object($rec);
                self::prepare_text_objects($rec);
                self::prepare_image_objects($rec);
            }
        }
    }
    private function prepare_text_objects($rec)
    {
        $articles = array();
        if(@$rec["texts"]) {
            foreach($rec["texts"] as $type => $r) $articles[$type] = implode("<p>", $r);
            foreach($articles as $type => $description) {
                $description = trim($description);
                if(in_array($description, array("Diagnosis: Modal fin-ray counts of", "A", "Analogues: Content goes here", 
                    "Early juvenile markings: New recruits (10-15 mm SL) of <i>S. partitus</i> have<p>Later juveniles (over 20 mm SL) are characterized by", 
                    "Juvenile analogues: New recruits of <i>S. partitus</i> are separated", "Microspathodon chrysurus new recruit, 10.9 mm SL", "10.6 mm SL<p>(San Blas, Panama, SB84-523)", 
                    "11.1 mm SL<p>(San Blas, Panama, SB83-137)", "Diagnosis: Damselfishes (ML)", "Stegastes planifrons new recruit, 10.9 mm SL 10.9 mm SL", 
                    "Early juvenile markings: New recruits (10-15 mm SL) of <i>S. adustus</i> have<p>Later juveniles (over 20 mm SL) are characterized by"
                ))) continue;
                if(!$description) continue;
                $obj = array();
                $obj["description"] = "<p>" . $description;
                $obj["subject"] = self::get_subject($type);
                $obj["identifier"] = md5($rec["sciname"] . $description);
                $obj["type"] = "text";
                $obj["taxon_id"] = $rec["taxon_id"];
                $obj["source"] = $rec["source"];
                self::get_objects($obj);
            }
        }
    }
    private function prepare_image_objects($rec)
    {
        if($imagez = @$rec["images"]) {
            foreach($imagez as $image) {
                if($taxon_from_image = self::get_taxon_from_image($image)) {
                    // echo " - image from scrape: {" . $rec["sciname"] . "}";
                    $r = array();
                    $r["sciname"] = $taxon_from_image;
                    $r["taxon_id"] = str_replace(" ", "_", $taxon_from_image);
                    $r["source"] = $rec["source"];
                    self::create_instances_from_taxon_object($r);
                    self::save_image_object($image, $r);
                }
                else self::save_image_object($image, $rec);
            }
        }
    }
    private function save_image_object($image, $rec)
    {
        $obj = array();
        $obj["description"] = $image["alt"];
        $obj["title"]       = $image["title"];
        $obj["identifier"]  = md5($image["src"]);
        $obj["type"]        = "image";
        $obj["taxon_id"]    = $rec["taxon_id"];
        $obj["source"]      = $rec["source"];
        $obj["accessURI"]   = $this->domain . $image["src"];
        self::get_objects($obj);
    }
    private function get_taxon_from_image($image)
    {
        if($val = @$image["alt"]) {
            $sciname = ucfirst(Functions::canonical_form($val));
            //manual adjustment
            $sciname = trim(str_ireplace(array("larval"), "", $sciname));
            if($sciname == "goby")              $sciname = "goby fish";
            if($sciname == "wrasse")            $sciname = "dwarf wrasse";
            if($sciname == "fishes")            return "Gobiidae";
            if($sciname == "Atlantic")          return "Malacoctenus macropus";
            if($sciname == "Dusky blenny")      return "Malacoctenus gilli";
            if($sciname == "parrotfish")        return "Scarus";
            if($sciname == "rufus")             return "Bodianus rufus";
            if($sciname == "bluehead wrasse")   return "Thalassoma bifasciatum";
            
            if(!$sciname) return false;
            // echo "\n taxon from image: [$sciname]";
            if($name_from_eol = self::name_exists_in_eol($sciname)) return $name_from_eol;
            else return false;
        }
    }
    private function name_exists_in_eol($name)
    {
        $eol_api = "http://eol.org/api/search/1.0.json?exact=true&q=";
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        
        if($json = Functions::lookup_with_cache($eol_api . $name, $options)) {
            $taxon = json_decode($json, true);
            if(intval($taxon["totalResults"]) > 0) return Functions::canonical_form($taxon["results"][0]["title"]);
            else                                   return false;
        }
    }
    private function get_objects($rec)
    {
        $mr = new \eol_schema\MediaResource();
        if($rec["type"] == "text") {
            $mr->type               = 'http://purl.org/dc/dcmitype/Text';
            $mr->format             = 'text/html';
            $mr->CVterm             = $rec["subject"];
        }
        elseif($rec["type"] == "image") {
            $mr->type               = 'http://purl.org/dc/dcmitype/StillImage';
            $mr->format             = 'image/jpeg';
            $mr->accessURI          = $rec["accessURI"];
            $mr->title              = $rec["title"];
        }
        $mr->taxonID                = $rec["taxon_id"];
        $mr->identifier             = $rec["identifier"];
        $mr->language               = 'en';
        $mr->furtherInformationURL  = $rec["source"];
        $mr->description            = $rec["description"];
        $mr->UsageTerms             = 'http://creativecommons.org/licenses/by/3.0/';
        $mr->Owner                  = $this->owner;
        /*
        $mr->title                  = '';
        $mr->creator                = '';
        $mr->CreateDate             = '';
        $mr->modified               = '';
        $mr->publisher              = '';
        $mr->audience               = 'Everyone';
        $mr->bibliographicCitation  = '';
        */
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->object_ids[$mr->identifier] = 1;
            $this->archive_builder->write_object_to_file($mr);
        }
    }
    private function get_subject($type)
    {
        switch($type) {
            case "Diagnosis":   return $this->SPM . "#DiagnosticDescription";
            case "Ecology":     return $this->SPM . "#Ecology";
            case "Analogues":   return $this->SPM . "#LookAlikes";
            case "Description": return $this->SPM . "#GeneralDescription";
            case "Notes":       return $this->EOL . "#Notes";
            default: echo "\n investigate undefined subject for [$type]\n";
        }
    }
    private function process_gobiidae_structured_data()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        
        $records = self::parse_gobiidae_structured_data();
        foreach($records as $sciname => $record) {
            // start taxon entry
            $r = array();
            $r["sciname"] = $sciname;
            $r["taxon_id"] = str_replace(" ", "_", $sciname);
            $r["source"] = $this->gobiidae_page;
            self::create_instances_from_taxon_object($r);
            // start structured data
            $rec = array();
            $rec["taxon_id"] = $r["taxon_id"];
            $rec["source"] = $this->gobiidae_page;
            $rec['contributor'] = $this->owner;
            // - pelvic fins
            if($info = self::format_pelvic_fin_form($record["Pelvic fins"])) {
                $rec["catnum"] = $info["basename"];
                $pelvic_fin_form = $info["uri"];
                /* old, replaced by TraitGeneric
                add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype)
                self::add_string_types("true", $rec, "Pelvic fins", $pelvic_fin_form, "http://eol.org/schema/terms/PelvicFinForm");
                self::add_string_types("false", $rec, "life stage", "http://purl.obolibrary.org/obo/PATO_0001185", "http://rs.tdwg.org/dwc/terms/lifeStage");
                */
                //---------------
                // $rec['occur']['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                $rec['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                $this->func->add_string_types($rec, $pelvic_fin_form, "http://eol.org/schema/terms/PelvicFinForm", "true");
                //---------------
            }
            // - anal-fin elements
            if($val = $record["anal-fin elements"]) {
                $arr = explode("-", $val);
                if(count($arr) == 1) {
                    $rec["catnum"] = "AnalFinElements";
                    /* old, replaced by TraitGeneric
                    self::add_string_types("true", $rec, "anal-fin elements", $val, "http://eol.org/schema/terms/NumberOfAnalFinElements");
                    self::add_string_types("false", $rec, "life stage", "http://purl.obolibrary.org/obo/PATO_0001185", "http://rs.tdwg.org/dwc/terms/lifeStage");
                    */
                    //---------------
                    // $rec['occur']['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                    $rec['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                    $this->func->add_string_types($rec, $val, "http://eol.org/schema/terms/NumberOfAnalFinElements", "true");
                    //---------------
                }
                else {
                    $rec["catnum"] = "AnalFinElements_min";
                    $rec["statistical_method"] = "http://semanticscience.org/resource/SIO_001113";
                    /* old, replaced by TraitGeneric
                    self::add_string_types("true", $rec, "anal-fin elements", $arr[0], "http://eol.org/schema/terms/NumberOfAnalFinElements");
                    self::add_string_types("false", $rec, "life stage", "http://purl.obolibrary.org/obo/PATO_0001185", "http://rs.tdwg.org/dwc/terms/lifeStage");
                    */
                    //---------------
                    // $rec['occur']['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                    $rec['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                    $this->func->add_string_types($rec, $arr[0], "http://eol.org/schema/terms/NumberOfAnalFinElements", "true");
                    //---------------
                    
                    $rec["catnum"] = "AnalFinElements_max";
                    $rec["statistical_method"] = "http://semanticscience.org/resource/SIO_001114";
                    /* old, replaced by TraitGeneric
                    self::add_string_types("true", $rec, "anal-fin elements", $arr[1], "http://eol.org/schema/terms/NumberOfAnalFinElements");
                    self::add_string_types("false", $rec, "life stage", "http://purl.obolibrary.org/obo/PATO_0001185", "http://rs.tdwg.org/dwc/terms/lifeStage");
                    */
                    //---------------
                    // $rec['occur']['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                    $rec['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                    $this->func->add_string_types($rec, $arr[1], "http://eol.org/schema/terms/NumberOfAnalFinElements", "true");
                    //---------------
                }
            }
            // - 1st dorsal spines
            if($val = $record["1st dorsal spines"]) {
                $rec["statistical_method"] = "";
                $rec["catnum"] = "NumberOfSpinesFirstDorsalFin";
                /* old, replaced by TraitGeneric
                self::add_string_types("true", $rec, "1st dorsal spines", $val, "http://eol.org/schema/terms/NumberOfSpinesFirstDorsalFin");
                self::add_string_types("false", $rec, "life stage", "http://purl.obolibrary.org/obo/PATO_0001185", "http://rs.tdwg.org/dwc/terms/lifeStage");
                */
                //---------------
                // $rec['occur']['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                $rec['lifeStage'] = "http://purl.obolibrary.org/obo/PATO_0001185";
                $this->func->add_string_types($rec, $val, "http://eol.org/schema/terms/NumberOfSpinesFirstDorsalFin", "true");
                //---------------
            }
        }
    }
    private function format_pelvic_fin_form($form)
    {
        if(!$form) return false;
        $uri = "";
        if($form == "divided")                              $uri = "http://eol.org/schema/terms/dividedPelvicFin";
        elseif($form == "partial")                          $uri = "http://eol.org/schema/terms/partiallyDivided";
        elseif(in_array($form, array("fused", "fused*")))   $uri = "http://eol.org/schema/terms/fusedPelvicFin";
        else echo "\n investigate pelvic fin form [$form] \n";
        if($uri) {
            $info = pathinfo($uri);
            return array("uri" => $uri, "basename" => $info["filename"]);
        }
        return false;
    }
    private function parse_gobiidae_structured_data()
    {
        $records = array();
        $fields = array("Pelvic fins", "anal-fin elements", "1st dorsal spines", "anal fewer than dorsal", "equal", "anal more than dorsal");
        if($html = Functions::lookup_with_cache($this->gobiidae_page, $this->download_options)) {
            $html = str_ireplace('<TD CLASS="leftbodytext" WIDTH="32%">', '<TD WIDTH="32%">', $html);
            if(preg_match("/NAME=\"gobies\" BORDERCOLOR=\"#999999\">(.*?)<\/table>/ims", $html, $arr)) {
                if(preg_match_all("/<tr>(.*?)<\/tr>/ims", $arr[1], $arr3)) {
                    array_shift($arr3[1]);
                    foreach($arr3[1] as $block) {
                        $sciname = "";
                        if(preg_match("/<i>(.*?)<\/i>/ims", $block, $arr2)) $sciname = trim($arr2[1]);
                        elseif(preg_match("/WIDTH=\"32%\">(.*?)<\/td>/ims", $block, $arr2)) $sciname = trim(strip_tags($arr2[1]));
                        $sciname = self::clean_str($sciname);
                        $sciname = trim(str_ireplace(array("family","1","2"), "", $sciname));
                        if(preg_match_all("/<TD CLASS=\"leftbodytext\"(.*?)<\/td>/ims", $block, $arr2)) {
                            if(count($arr2[1]) != 6) {
                                echo "\n investigate";
                                continue;
                            }
                            $i = 0;
                            $values = array();
                            // echo "\n [$sciname] ";
                            foreach($arr2[1] as $temp) {
                                if(preg_match("/>(.*?)<\/td>/ims", $temp . "</td>", $arr3)) $values[$fields[$i]] = self::clean_str($arr3[1]);
                                $i++;
                            }
                        }
                        if($sciname && $values) $records[$sciname] = $values;
                    }
                }
            }
            else echo "\n not found \n";
        }
        return $records;
    }
    private function clean_str($str)
    {
        $str = trim($str);
        $str = str_ireplace(array(chr(13), chr(10)), "", $str);
        $str = Functions::remove_whitespace($str);
        return $str;
    }
    private function process_family($name, $url)
    {
        /*
        if(stripos($url, "gobiidae") === false)    $options = $this->download_options; // not found, will use cache
        else                                       $options = $this->download_options2; // will not use cache
        */
        $options = $this->download_options;
        if($html = Functions::lookup_with_cache($url, $options)) {
            if($url == "http://www.coralreeffish.com/microdesmidae.html") $html = str_ireplace('<span class="speciesheading">Cerdale floridana</span>', '<td height="1" bgcolor="#373B4A"></td><span class="speciesheading">Cerdale floridana</span>', $html);
            $html = str_ireplace('<td height="1" bgcolor="#373B4A"> </td>', '<td height="1" bgcolor="#373B4A"></td>', $html);
            $html = str_ireplace('<td height="1" bgcolor="#373B4A"></td>', strtolower('<td height="1" bgcolor="#373B4A"></td>'), $html);
            $blocks = explode('<td height="1" bgcolor="#373b4a"></td>', $html); // take note of lower case '#373b4a'
            foreach($blocks as $block) {
                $record = array();
                // echo "\n==============================================\n";
                $block = str_ireplace(array(' height=""', ' height="20"', '  height="30"', ' height="30"', ' height="17"', ' COLSPAN="2"', ' height="85"', ' height="20px"', ' width="67%"', ' align="center"'), '', $block);
                $taxa = array();
                if(preg_match_all("/<TD CLASS\=\"groupheading\">(.*?)<\/td>/ims", $block, $arr)) {
                    foreach($arr[1] as $taxon) {
                        $name = self::clean_string($taxon, "name");
                        // manual adjustments start
                        if(in_array($name, array("Starksia species list"))) continue;
                        if(in_array($name, array("Larval melanophores", "Early-stage larvae", "Malacoctenus vs. Labrisomus", "Labrisomus"))) $name = "Labrisomidae";
                        if(in_array($name, array("Larval Gobies"))) $name = "Gobiidae";
                        // manual adjustments end
                        $taxa["groupheading"][] = $name;
                    }
                }
                
                if(preg_match_all("/<SPAN CLASS\=\"speciesheading\">(.*?)<\/SPAN>/ims", $block, $arr)) {
                    foreach($arr[1] as $taxon) $taxa["speciesheading"][] = self::clean_string($taxon, "name");
                }
                if(preg_match_all("/<td CLASS\=\"speciesheading\">(.*?)<\/td>/ims", $block, $arr)) {
                    foreach($arr[1] as $taxon) $taxa["speciesheading"][] = self::clean_string($taxon, "name");
                }

                if($url == "http://www.coralreeffish.com/misc.html") {
                    if(preg_match_all("/<td class\=\"bodyitalictext\">(.*?)<\/td>/ims", $block, $arr)) {
                        foreach($arr[1] as $taxon) {
                            $name = self::clean_string($taxon, "name");
                            if(in_array($name, array("Scorpaena sp. transitional larva,", "Scorpaena sp. early transitional larva,"))) $name = "Scorpaena sp.";
                            $taxa["speciesheading"][] = $name;
                        }
                    }
                }

                if(isset($taxa["groupheading"]))    $taxa["groupheading"] = array_unique($taxa["groupheading"]);
                if(isset($taxa["speciesheading"]))  $taxa["speciesheading"] = array_unique($taxa["speciesheading"]);
                
                $texts = array();
                if(preg_match_all("/CLASS=\"bodytext\">(.*?)<\/TD>/ims", $block, $arr)) {
                    $arr[1] = array_map('trim', $arr[1]);
                    foreach($arr[1] as $t) {
                        $t = self::clean_string($t);
                        $t = trim(strip_tags($t, "<i>"));

                        if(in_array($t, array("&nbsp;", "<i></i>", "Diagnosis:", "Analogues:", "Description:", "Diagnosis: Text will Go here", "Diagnosis:  Text will Go here"))) continue;
                        if($url == "http://www.coralreeffish.com/gobiidae.html" && substr($t,0,6) == "Group ") continue;
                        if(!$t) continue;
                        // echo "\n-----------------------------------------------------------\n" . $t;
                        if(in_array($t, array("Species of <i>Serranus</i> are listed in order of increasing pectoral-fin ray counts", "Serranus tortugarum early transitional larva", 
                            "Cerdale floridana larvae", "Cerdale floridana larva", "Chriolepis/Psilotris sp. larva", "7.4 mm SL", "San Blas, Panama, SB81-196", "Description: Larvae not identified.", "Photo by JT Williams",
                            "Microgobius carri transitional recruit", "Bollmannia boqueronensis  larvae", "Ginsburgellus novemlineatus? larva", "Nes longus larvae",
                            "Ctenogobius saepepallens  larvae", "Ctenogobius saepepallens  larvae", "Ctenogobius saepepallens  juvenile", "Ctenogobius boleosoma transitional larvae", "Ctenogobius boleosoma  recruit", "Evorthodus lyricus transitional recruit", "Priolepis hipoliti transitional larva", "Priolepis dawsoni recruit", "Tigrigobius pallens  recruit"
                        ))) continue;

                        if(preg_match("/xxxNes longus(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxCtenogobius(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxEvorthodus lyricus(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxEvermannichthys metzelaari(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxGobionellus oceanicus(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxBollmannia boqueronensis(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxMicrogobius(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxMicrodesmus(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxCerdale(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxTigrigobius(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxSicydium(.*?)yyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxBarbulifer(.*?)larvayyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxElacatinus(.*?)yyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxRisor ruber(.*?)yyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxx<i>Tigrigobius gemmatus(.*?)yyy/ims", "xxx".$t."yyy", $arr)) continue;
                        elseif(preg_match("/xxxGobiosoma(.*?)yyy/ims", "xxx".$t."yyy", $arr)) continue;

                        if    (is_numeric(stripos($t, "Diagnosis:")))   $texts["Diagnosis"][] = $t;
                        elseif(is_numeric(stripos($t, "Analogues:")))   $texts["Analogues"][] = $t;
                        elseif(is_numeric(stripos($t, "Description:"))) $texts["Description"][] = $t;
                        elseif(is_numeric(stripos($t, "Ecology:")))     $texts["Ecology"][] = $t;
                        else                                            $texts["Description"][] = $t;
                    }
                }
                
                if($url == "http://www.coralreeffish.com/misc.html") {
                    if(isset($texts["Description"])) $texts = array();
                }
                
                $images = array();
                if(preg_match_all("/<img (.*?)>/ims", $block, $arr)) {
                    foreach($arr[1] as $image) {
                        if(strpos($image, "images/") === false) {
                            $src = false; $alt = ""; $title = "";
                            if(preg_match("/src\=\"(.*?)\"/ims", $image, $arr2)) $src = $arr2[1];
                            if(preg_match("/alt\=\"(.*?)\"/ims", $image, $arr2)) $alt = $arr2[1];
                            if(preg_match("/title\=\"(.*?)\"/ims", $image, $arr2)) $title = $arr2[1];
                            if($src) $images[] = array("src" => $src, "alt" => $alt, "title" => $title);
                        }
                    }
                }
                
                $taxon = false;
                if($taxon = self::get_taxon(@$taxa["speciesheading"], "speciesheading", $url)) {}
                elseif($taxon = self::get_taxon(@$taxa["groupheading"], "groupheading", $url)) {}
                else {
                    // manual adjustments start
                    if(@$taxa["speciesheading"][0] == "Pseudogramma gregoryi" && @$taxa["speciesheading"][1] == "Gramma loreto") {
                        $record[$taxa["speciesheading"][0]]["texts"] = $texts;
                        $record[$taxa["speciesheading"][0]]["source"] = $url;
                        $record[$taxa["speciesheading"][1]]["images"] = $images;
                        $record[$taxa["speciesheading"][1]]["source"] = $url;
                        $this->records[] = $record;
                        continue;
                    }
                    if($url != "http://www.coralreeffish.com/misc.html") {
                        $parts = pathinfo($url);
                        $family_name = ucfirst($parts["filename"]);
                        $record[$family_name]["texts"] = $texts;
                        $record[$family_name]["images"] = $images;
                        $record[$family_name]["source"] = $url;
                        $this->records[] = $record;
                        continue;
                    }
                    // manual adjustments end
                    
                    if($taxa || $texts || $images) {
                        echo "\n\n investigate no taxa: [$url]\n\n";
                        print_r($taxa);
                        print_r($texts);
                        print_r($images);
                        echo "\n++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++\n";
                    }
                    continue;
                }
                
                // manual adjustment start
                if(in_array($taxon, array("GOBIES OF THE CARIBBEAN"))) continue;
                // manual adjustment end
                
                if($taxon) {
                    $record[$taxon]["texts"] = $texts;
                    $record[$taxon]["images"] = $images;
                    $record[$taxon]["source"] = $url;
                    $this->records[] = $record;
                }
            }
        }
    }
    private function get_taxon($arr, $type, $url)
    {
        if(count($arr) > 1) {
            if($arr[0] == "Pseudogramma gregoryi" && $arr[1] == "Gramma loreto") return false;
            if($arr[0] == "Subfamily Pseudogrammatinae" && $arr[1] == "FAMILY GRAMMATIDAE") return false;
            echo "\n\n investigate more than 1 taxon ($type): [$url]\n\n";
            print_r($arr);
            return false;
        }
        else return $arr[0];
    }
    private function clean_string($string, $type = false)
    {
        $string = str_ireplace(array("\r\n", "\n", "\r", "\t", "\0", "\x0B", "\t"), '', $string);
        if($type == "name") $string = strip_tags($string);
        return trim(Functions::remove_whitespace($string));
    }
    private function get_family_list_urls()
    {
        $families = array();
        if($html = Functions::lookup_with_cache($this->family_list_page, $this->download_options)) {
            if(preg_match_all("/<A HREF\=(.*?)<\/A>/ims", $html, $arr)) {
                foreach($arr[1] as $str) {
                    if(stripos($str, 'CLASS="familylink"') === false) {}
                    else {
                        $family_name = false;
                        if(preg_match("/\>(.*?)xxx/ims", $str."xxx", $arr)) $family_name = trim($arr[1]);
                        if(preg_match("/\"(.*?)\"/ims", $str, $arr)) $url = $arr[1];
                        if($family_name) $families[$family_name] = $this->domain . $url;
                    }
                }
            }
        }
        return $families;
    }
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                     = $rec["taxon_id"];
        $taxon->scientificName              = $rec["sciname"];
        $taxon->furtherInformationURL       = $rec["source"];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->taxa[$taxon->taxonID] = $taxon;
            $this->taxon_ids[$taxon->taxonID] = 1;
        }
    }
    private function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }
    /* used TraitGeneric instead, as of Feb 18, 2019.
    private function add_string_types($measurementOfTaxon, $rec, $label, $value, $mtype)
    {
        $taxon_id = $rec["taxon_id"];
        $catnum = $rec["catnum"];
        
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $catnum);
        $m->occurrenceID = $occurrence->occurrenceID;
        $m->measurementOfTaxon = $measurementOfTaxon;
        $m->source = $rec["source"];
        // $m->contributor = 'coralreeffish.com';
        $m->measurementType = $mtype;
        $m->measurementValue = $value;
        if($val = @$rec["statistical_method"]) $m->statisticalMethod = $val;
        // $m->measurementMethod = '';
        $this->archive_builder->write_object_to_file($m);
    }
    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . '_' . $catnum;
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }
    */
}
?>