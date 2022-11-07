<?php
namespace php_active_record;
/* connector: [24_new] */
class AntWebAPI
{
    public function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        /* copied template
        $this->taxa_ids             = array();
        $this->taxa_reference_ids   = array(); // $this->taxa_reference_ids[taxon_id] = reference_ids
        $this->object_ids           = array();
        $this->object_reference_ids = array();
        $this->object_agent_ids     = array();
        $this->reference_ids        = array();
        $this->agent_ids            = array();
        */
        $this->download_options = array('resource_id' => 24, 'timeout' => 172800, 'expire_seconds' => 60*60*24*45, 'download_wait_time' => 1000000); // expire_seconds = every 45 days in normal operation
        $this->download_options['expire_seconds'] = false; //doesn't expire
        
        $this->page['all_taxa'] = 'https://www.antweb.org/taxonomicPage.do?rank=species';
        $this->page['specimens'] = 'https://www.antweb.org/browse.do?species=SPECIES_NAME&genus=GENUS_NAME&rank=species';
        $this->page['specimen_info'] = 'https://www.antweb.org/specimen.do?code=';
        $this->page['images'] = 'https://www.antweb.org/images.do?genus=GENUS_NAME&species=SPECIES_NAME';
        $this->page['specimen_images'] = 'https://www.antweb.org/specimenImages.do?name=SPECIMEN_CODE&project=allantwebants';
        $this->page['specimen_image'] = 'https://www.antweb.org/bigPicture.do?name=SPECIMEN_CODE&shot=SHOT_LETTER&number=1';
        $this->debug = array();
        $this->bibliographicCitation = "AntWeb. Version 8.45.1. California Academy of Science, online at https://www.antweb.org. Accessed ".date("d F Y").".";
        //remove across all textmined resources: cloud, cut
        $this->remove_across_all_resources = array('http://purl.obolibrary.org/obo/ENVO_01000760', 'http://purl.obolibrary.org/obo/ENVO_00000474');
        $this->investigate = array("http://purl.obolibrary.org/obo/ENVO_01000680", "http://purl.obolibrary.org/obo/ENVO_01000477");
    }
    function start()
    {
        // /* This is used for accessing Pensoft annotator to get ENVO URI given habitat string.
        $param['resource_id'] = 24; //AntWeb resource ID
        require_library('connectors/Functions_Pensoft');
        require_library('connectors/Pensoft2EOLAPI');
        $this->pensoft = new Pensoft2EOLAPI($param);
        $this->pensoft->initialize_remaps_deletions_adjustments();
        // /* to test if these 4 variables are populated.
        echo "\n From Pensoft Annotator:";
        echo("\n remapped_terms: "              .count($this->pensoft->remapped_terms)."\n");
        echo("\n mRemarks: "                    .count($this->pensoft->mRemarks)."\n");
        echo("\n delete_MoF_with_these_labels: ".count($this->pensoft->delete_MoF_with_these_labels)."\n");
        echo("\n delete_MoF_with_these_uris: "  .count($this->pensoft->delete_MoF_with_these_uris)."\n");
        // exit;
        // */
        $this->descendants_of_aquatic = $this->pensoft->get_descendants_of_habitat_group('aquatic'); //Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65426&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65426
        // print_r($this->descendants_of_aquatic); exit;("\n");
        // */
        
        require_library('connectors/TraitGeneric'); 
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        /* START DATA-1841 terms remapping */
        $this->func->initialize_terms_remapping(60*60*24); //param is $expire_seconds. 0 means expire now.
        /* END DATA-1841 terms remapping */
        
        // /*
        require_library('connectors/AntWebDataAPI');
        $func = new AntWebDataAPI(false, false, false);
        $this->habitat_map = $func->initialize_habitat_mapping();
        $this->uri_values = $func->initialize_mapping();
        if($this->uri_values["United States Virgin Islands"] == "https://www.wikidata.org/entity/Q11703") print("\nEOL Terms file consolidated OK.\n");
        else print("\nERROR: EOL Terms file NOT consolidated!\n");
        // exit;
        // */
        
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->page['all_taxa'], $options)) {
            $html = str_replace("&nbsp;", ' ', $html);
            // echo $html; exit;
            if(preg_match_all("/<div class=\"sd_data\">(.*?)<div class=\"clear\"><\/div>/ims", $html, $arr)) {
                $eli = 0;
                foreach($arr[1] as $str) {
                    if(preg_match_all("/<div (.*?)<\/div>/ims", $str, $arr2)) {
                        $rec = array_map('trim', $arr2[1]);
                        // print_r($rec);
                        /*Array(
                            [0] => class="sd_name pad">
                            <a href='https://www.antweb.org/common/statusDisplayPage.jsp' target="new"> 
                            <img src="https://www.antweb.org/image/valid_name.png" border="0" title="Valid name.  ">
                            </a>
                            <img src="https://www.antweb.org/image/1x1.gif" width="11" height="12" border="0">
                            <img src="https://www.antweb.org/image/1x1.gif" width="11" height="12" border="0">
                            <img src="https://www.antweb.org/image/1x1.gif" width="11" height="12" border="0">
                            <a href="https://www.antweb.org/description.do?genus=xenomyrmex&species=panamanus&rank=species&project=allantwebants">Xenomyrmex panamanus</a>
                            [1] => class="list_extras author_date">(Wheeler, 1922)
                            [2] => class="list_extras specimens"> <a href='https://www.antweb.org/browse.do?genus=xenomyrmex&species=panamanus&rank=species&project=allantwebants'><span class='numbers'>15</span> Specimens</a>
                            [3] => class="list_extras images">No Images
                            [4] => class="list_extras map">
                            <a href="bigMap.do?taxonName=myrmicinaexenomyrmex panamanus">Map</a>
                            [5] => class="list_extras source">
                            <a target='new' href='http://www.antcat.org/catalog/451293'>Antcat</a>
                        )*/
                        if(stripos($rec[0], "Valid name") !== false) { //string is found
                            $rek = array();
                            if(preg_match("/allantwebants\">(.*?)<\/a>/ims", $rec[0], $arr3)) $rek['sciname'] = str_replace(array('&dagger;'), '', $arr3[1]);
                            $rek['rank'] = 'species';
                            if(preg_match("/description\.do\?(.*?)\">/ims", $rec[0], $arr3)) $rek['source_url'] = 'https://www.antweb.org/description.do?'.$arr3[1];

                            /* good debug - during development
                            if($rek['sciname'] == 'Acromyrmex octospinosus') {
                            // if($rek['sciname'] == 'Acanthognathus ocellatus') {
                            // if($rek['sciname'] == 'Acanthoponera minor') {
                            // if($rek['sciname'] == 'Acanthognathus rudis') {
                                $rek = self::parse_summary_page($rek);
                                if($all_images_per_species = self::get_images($rek['sciname'])) $rek['images'] = $all_images_per_species;
                                echo "images: ".count(@$rek['images'])."\n";
                                if($rek['sciname']) self::write_archive($rek);
                                break;
                                // print_r($rek); exit("\naaa\n");
                            }
                            */

                            /* used when caching
                            $letter = substr($rek['sciname'],0,1);
                            // if($letter <= "J") continue;
                            // if($letter > "J") continue;

                            // if($letter >= "P" && $letter <= "R") {}
                            // else continue;

                            // if($letter >= "P" && $letter <= "Q") {
                            //     if($rek['sciname'] <= 'Proceratium mancum') continue;
                            //     else {}
                            // }
                            // else continue;

                            // if($letter == "P") {
                            //     if($rek['sciname'] >= 'Pseudomyrmex reconditus') {}
                            //     else continue;
                            // }
                            // else continue;
                            
                            // if($letter == "T") {
                            //     if($rek['sciname'] >= 'Tetramorium sericeum') {}
                            //     else continue;
                            // }
                            // else continue;

                            // if($letter == "T") {
                            //     if($rek['sciname'] >= 'Tetramorium phasias' && $rek['sciname'] <= 'Tetramorium transversarium') {}
                            //     else continue;
                            // }
                            // else continue;

                            // if($letter == "T") {
                            //     if($rek['sciname'] >= 'Tetramorium transversarium' && $rek['sciname'] <= 'Tetraponera rakotonis') {}
                            //     else continue;
                            // }
                            // else continue;
                            
                            // if($letter == "T") {
                            //     if($rek['sciname'] >= 'Tetramorium legone') {}
                            //     else continue;
                            // }
                            // else continue;

                            if($letter == "T") {
                                if($rek['sciname'] >= 'Tetramorium microgyna') {}
                                else continue;
                            }
                            else continue;

                            // if($letter >= "S" && $letter <= "U") {}
                            // else continue;

                            // if($letter == "R") {}
                            // else continue;

                            // if($letter == "U") {} //just 1 species
                            // else continue;

                            // if($letter == "S") {} //just 1 species
                            // else continue;

                            // if($letter == "T") {} //just 1 species
                            // else continue;
                            */
                            
                            // /* normal operation
                            debug("\n$rek[sciname] - ");
                                $rek = self::parse_summary_page($rek);
                                if($all_images_per_species = self::get_images($rek['sciname'])) $rek['images'] = $all_images_per_species;
                                debug("images: ".count(@$rek['images'])." | ");
                                if($rek['sciname']) self::write_archive($rek);
                            // print_r($rek); exit("\nbbb\n");
                            // if($rek['sciname']) self::write_archive($rek);
                            // break; //debug only
                            // */

                            $eli++;
                            if(($eli % 1000) == 0) echo "\n".number_format($eli)." ";
                            // if($eli > 5) break; //debug only
                        }
                        
                    }
                }
            }
        }
        // exit("\n-stop muna-\n");
        $this->archive_builder->finalize(true);
        print_r($this->debug);
    }
    private function parse_summary_page($rek)
    {
        if($html = Functions::lookup_with_cache($rek['source_url'], $this->download_options)) {
            $html = str_replace("&nbsp;", ' ', $html);
            // phylum:arthropoda class:insecta order:hymenoptera family:formicidae 
            if(preg_match("/phylum\:(.*?) /ims", $html, $arr)) $rek['ancestry']['phylum'] = ucfirst($arr[1]);
            if(preg_match("/class\:(.*?) /ims", $html, $arr)) $rek['ancestry']['class'] = ucfirst($arr[1]);
            if(preg_match("/order\:(.*?) /ims", $html, $arr)) $rek['ancestry']['order'] = ucfirst($arr[1]);
            if(preg_match("/family\:(.*?) /ims", $html, $arr)) $rek['ancestry']['family'] = ucfirst($arr[1]);
            
            $html = str_replace("// Distribution", "<!--", $html);
            
            if(preg_match("/<h3 style=\"float\:left\;\">Distribution Notes\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Distribution_Notes'] = self::format_html_string($arr[1]);
                // print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Identification\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Identification'] = self::format_html_string($arr[1]);;
                // print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Overview\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Overview'] = self::format_html_string($arr[1]);
                // print_r($rek); exit;
            }
            if(preg_match("/<h3 style=\"float\:left\;\">Biology\:<\/h3>(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Biology'] = self::format_html_string($arr[1]);
                // print_r($rek); exit;
            }
            
            $complete = self::complete_header('<h2>Taxonomic History ', '<\/h2>', $html);
            // <h2>Taxonomic History (provided by Barry Bolton, 2020)</h2>
            // exit("\n$complete\n".preg_quote($complete,"/")."\n");
            if(preg_match("/".preg_quote($complete,"/")."(.*?)<\!\-\-/ims", $html, $arr)) {
                $rek['Taxonomic_History'] = self::format_html_string($arr[1]);
                // print_r($rek); exit;
            }
            // print_r($rek); exit;
            
            // /* start with specimens
            $rek = self::parse_specimens($rek, $html);
            // */
            
        }
        return $rek;
    }
    private function parse_specimens($rek, $html)
    {
        if(stripos($html, '">Specimens</a>') !== false) { //string is found
            $name = explode(' ', $rek['sciname']);
            $url = $this->page['specimens'];
            $url = str_replace('GENUS_NAME', $name[0], $url);
            $url = str_replace('SPECIES_NAME', $name[1], $url);
            if($html = Functions::lookup_with_cache($url, $this->download_options)) {
                $html = str_replace("&nbsp;", ' ', $html); // exit("\n$html\n");
                $complete = '<div class="specimen_layout';
                if(preg_match_all("/".preg_quote($complete,"/")."(.*?)<\!\-\-/ims", $html, $arr)) {
                    debug("Total Specimens: ".count($arr[1])." | ");
                    if($country_habitat = self::get_specimens_metadata($arr[1], $url)) $rek['country_habitat'] = $country_habitat;
                }
            }
        }
        else {
            print_r($rek); exit("\nNo specimens\n[$url]\n");
        }
        return $rek;
    }
    private function get_specimens_metadata($specimen_rows, $source_url)
    {
        $final = array();
        foreach($specimen_rows as $row) {
            $rec = array();
            $rec['source_url'] = $source_url;
            /* <a href="https://www.antweb.org/specimen.do?code=awlit-ba00716"> */
            $complete = '/specimen.do?code=';
            if(preg_match("/".preg_quote($complete,"/")."(.*?)\"/ims", $row, $arr)) $rec['specimen_code'] = $arr[1];
            /* Collection: <a href=https://www.antweb.org/collection.do?name=tc1462219020>tc1462219020</a> */
            $complete = '/collection.do?name=';
            if(preg_match("/".preg_quote($complete,"/")."(.*?)>/ims", $row, $arr)) $rec['collection_code'] = $arr[1];
            /* <span class="">Location: Brazil: Amazonas: Itacoatiara:&nbsp;&nbsp; */
            $rec['country'] = '';
            if(preg_match("/Location: (.*?)\:/ims", $row, $arr)) {
                $tmp = trim(Functions::remove_whitespace(strip_tags($arr[1])));
                if(stripos($tmp, '&deg;,&deg;') !== false) {} //string is found
                else $rec['country'] = $tmp;
            }
            /* <span class="">Date Collected: 2011-12-05</span><br /> */
            if(preg_match("/>Date Collected: (.*?)<\/span>/ims", $row, $arr)) $rec['date_collected'] = $arr[1]; //eventDate
            
            /* <span class="">Determined By: Chaul, J.</span><br /> */
            if(preg_match("/>Determined By: (.*?)<\/span>/ims", $row, $arr)) $rec['determined_by'] = $arr[1]; //identifiedBy
            
            /* <span class="">Owned By: <a href='https://www.antweb.org/museum.do?code=UFV-LABECOL'>UFV-LABECOL</a></span><br /> */
            if(preg_match("/>Owned By: (.*?)<\/span>/ims", $row, $arr)) $rec['owned_by'] = strip_tags($arr[1]); //contributor
            
            /* <span class="">Habitat: </span><br /> */
            $rec['habitat'] = '';
            if(preg_match("/>Habitat: (.*?)<\/span>/ims", $row, $arr)) {
                $rec['habitat'] = $arr[1];
                if(substr($rec['habitat'], -3) == '...') {
                    // print_r($rec);
                    $rec = self::parse_specimen_summary($rec); //this will complete the habitat string with "...".
                    // print_r($rec); exit;
                }
            }
            
            // print_r($rec); exit; //good debug
            
            if($rec['country'] || $rec['habitat']) $final[] = $rec;
        }
        // print_r($final); //exit("\nbbb\n");
        /* normalize and deduplicate country */
        $final = self::normalize_deduplicate_country_and_habitat($final);
        // print_r($final); exit("\nccc\n");
        return $final;
    }
    private function normalize_deduplicate_country_and_habitat($raw)
    {
        $final = array();
        foreach($raw as $r) {
            /*Array(
                [specimen_code] => jtl748681
                [collection_code] => Go-E-02-1-04
                [country] => Costa Rica
                [habitat] => tropical rainforest, 2nd growth, some big trees
            )
            Array(
                [specimen_code] => antweb1038249
                [collection_code] => tc368115418
                [country] => Brazil
                [date_collected] => 2011-12-05
                [determined_by] => Chaul, J.
                [owned_by] => UFV-LABECOL
                [habitat] => 
            )
            */
            if($country = @$r['country']) {
                if(!isset($debug[$country])) {
                    $debug[$country] = '';
                    $final[] = array('specimen_code' => $r['specimen_code'], 'collection_code' => @$r['collection_code'], 'country' => $r['country'],
                    'date_collected' => $r['date_collected'], 'determined_by' => $r['determined_by'], 'owned_by' => $r['owned_by'],
                    'source_url' => $r['source_url']);
                }
            }
            if($habitat = @$r['habitat']) {
                if(strlen($habitat) <= 3) continue; //filter out e.g. 'SSO'
                if(!isset($debug[$habitat])) {
                    $final[] = array('specimen_code' => $r['specimen_code'], 'collection_code' => @$r['collection_code'], 'habitat' => $r['habitat'],
                    'date_collected' => $r['date_collected'], 'determined_by' => $r['determined_by'], 'owned_by' => $r['owned_by'],
                    'source_url' => $r['source_url']);
                    $debug[$habitat] = '';
                }
            }
        }
        return $final;
    }
    private function parse_specimen_summary($rec)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false;
        if($html = Functions::lookup_with_cache($this->page['specimen_info'].$rec['specimen_code'], $options)) {
            $html = str_replace("&nbsp;", ' ', $html);
            /*get Habitat
            <ul>
            <li><b>Habitat: </b></li>
            <li>&nbsp;</li>
            </ul>
            */
            $complete = '<b>Habitat: </b>';
            if(preg_match("/".preg_quote($complete,"/")."(.*?)<\/ul>/ims", $html, $arr)) $rec['habitat'] = trim(strip_tags($arr[1]));
        }
        return $rec;
    }
    private function complete_header($start, $end, $html)
    {
        if(preg_match("/".$start."(.*?)".$end."/ims", $html, $arr)) return $start.$arr[1].str_replace('<\/h2>', '</h2>', $end);
    }
    private function format_html_string($str)
    {
        $str = strip_tags($str,'<em><i><span><p><a>');
        // \t --- chr(9) tab key
        // \r --- chr(13) = Carriage Return - (moves cursor to lefttmost side)
        // \n --- chr(10) = New Line (drops cursor down one line) 
        // $str = str_replace(array("\n", chr(10)), "<br>", $str);
        // $str = str_replace(array("\r", chr(13)), "<br>", $str);
        $str = str_replace(array("\n", chr(10)), " ", $str);
        $str = str_replace(array("\r", chr(13)), " ", $str);
        $str = str_replace(array("\t", chr(9)), " ", $str);
        $str = Functions::remove_whitespace(trim($str));
        $str = str_replace(array("<p></p>"), "", $str);
        $str = str_replace(array("<p> </p>"), "", $str);
        return Functions::remove_whitespace($str);
    }
    private function get_images($sciname)
    {
        $final = array();
        $name = explode(' ', $sciname);
        $url = $this->page['images'];
        $url = str_replace('GENUS_NAME', $name[0], $url);
        $url = str_replace('SPECIES_NAME', $name[1], $url);
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_replace("&nbsp;", ' ', $html); // exit("\n$html\n");
            /*
            <div class="name"><a href="https://www.antweb.org/specimenImages.do?name=psw7796-21&project=allantwebants">PSW7796-21</a></div>
            */
            $complete = 'specimenImages.do?name=';
            if(preg_match_all("/".preg_quote($complete,"/")."(.*?)\&project\=/ims", $html, $arr)) {
                $a = $arr[1];
                $a = array_filter($a); //remove null arrays
                $a = array_unique($a); //make unique
                $a = array_values($a); //reindex key
                // print_r($a); exit;
                /*Array(
                    [0] => casent0246632
                    [1] => casent0900490
                    [2] => casent0922028
                    [3] => fmnhins0000046890
                    [4] => psw7796-21
                )*/
                foreach($a as $specimen_code) {
                    if($images = self::get_specimen_images($specimen_code)) $final = array_merge($final, $images);
                }
            }
        }
        // print_r($final); exit("\nccc\n"); //good debug
        return $final;
    }
    private function get_specimen_images($specimen_code)
    {
        $final = array();
        $url = str_replace('SPECIMEN_CODE', $specimen_code, $this->page['specimen_images']);
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            $html = str_replace("&nbsp;", ' ', $html); // exit("\n$html\n");
            /*
            onclick="window.location='bigPicture.do?name=psw7796-21&shot=h&number=1'"
            */
            $complete = 'bigPicture.do?name='.$specimen_code.'&shot=';
            if(preg_match_all("/".preg_quote($complete,"/")."(.*?)\&number\=/ims", $html, $arr)) {
                // print_r($arr[1]); exit;
                $a = $arr[1];
                $a = array_filter($a); //remove null arrays
                $a = array_unique($a); //make unique
                $a = array_values($a); //reindex key
                // print_r($a); exit;
                /*Array(
                    [0] => h
                    [1] => p
                    [2] => d
                    [3] => l
                )*/
                foreach($a as $shot_letter) {
                    if($shot_letter == 'l') continue;
                    if($image_info = self::parse_specimen_image_summary($specimen_code, $shot_letter, $url)) $final[] = $image_info;
                }
            }
        }
        return $final;
    }
    private function parse_specimen_image_summary($specimen_code, $shot_letter, $urlx)
    {
        $url = str_replace('SPECIMEN_CODE', $specimen_code, $this->page['specimen_image']);
        $url = str_replace('SHOT_LETTER', $shot_letter, $url);
        $image['source_url'] = $url;
        if($html = Functions::lookup_with_cache($url, $this->download_options)) {
            /*
            <li><b>Uploaded By:</b> <a href='https://www.antweb.org/group.do?id=1'>California Academy of Sciences</a></li>
            <li><b>Photographer:</b> <a href='https://www.antweb.org/artist.do?id=69'>April Nobile</a></li>
            <li><b>Date Uploaded:</b> 2007-12-18 14:39:32.0</li>
            */
            $complete = '<li><b>Photographer:</b>';
            if(preg_match("/".preg_quote($complete,"/")."(.*?)<\/li>/ims", $html, $arr)) {
                $str = $arr[1];
                /* <a href='https://www.antweb.org/artist.do?id=105'>Andrea Walker</a> */
                if(preg_match("/>(.*?)</ims", $str, $arr)) $image['photographer'] = $arr[1];
                if(preg_match("/<a href=\'(.*?)\'/ims", $str, $arr)) $image['photographer_homepage'] = $arr[1];
            }
            $complete = '<li><b>Date Uploaded:</b>';
            if(preg_match("/".preg_quote($complete,"/")."(.*?)<\/li>/ims", $html, $arr)) $image['date_uploaded'] = trim($arr[1]);
            /*
            <div class="big_picture">
                <img src="https://www.antweb.org/images/psw7796-21/psw7796-21_h_1_high.jpg">
            </div>
            */
            $complete = '<div class="big_picture">';
            if(preg_match("/".preg_quote($complete,"/")."(.*?)<\/div>/ims", $html, $arr)) {
                if(preg_match("/<img src=\"(.*?)\"/ims", $arr[1], $arr)) $image['media_url'] = $arr[1];
            }
            /* content="1 of 4 images of Acromyrmex octospinosus from AntWeb." */
            $complete = ' images of ';
            if(preg_match("/".preg_quote($complete,"/")."(.*?) from AntWeb./ims", $html, $arr)) $image['sciname'] = $arr[1];
            /* <meta name='description' content='Closeup head view of Specimen CASENT0900490 from AntWeb.'/> */
            $complete = "<meta name='description' content='";
            if(preg_match("/".preg_quote($complete,"/")."(.*?)\'/ims", $html, $arr)) $image['title'] = $arr[1]." (".$image['sciname'].")";
            
            // print_r($image); exit;
            return $image;
        }
    }
    private function write_archive($rek)
    {
        $taxonID = self::write_taxon($rek);
        self::write_text_objects($rek, $taxonID);
        self::write_image_objects($rek, $taxonID);
        self::write_traits($rek, $taxonID);
    }
    private function write_taxon($rek)
    {   /*Array(
        [sciname] => Acromyrmex octospinosus
        [rank] => species
        [source_url] => https://www.antweb.org/description.do?genus=acromyrmex&species=octospinosus&rank=species&project=allantwebants
        [ancestry] => Array(
                [phylum] => Arthropoda
                [class] => Insecta
                [order] => Hymenoptera
                [family] => Formicidae
            )
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = strtolower(str_replace(' ','_',$rek['sciname']));
        $taxon->scientificName  = $rek['sciname'];
        $taxon->phylum  = $rek['ancestry']['phylum'];
        $taxon->class   = $rek['ancestry']['class'];
        $taxon->order   = $rek['ancestry']['order'];
        $taxon->family  = $rek['ancestry']['family'];
        $taxon->furtherInformationURL = $rek['source_url'];
        /* copied template
        $taxon->kingdom         = $t['dwc_Kingdom'];
        $taxon->genus           = $t['dwc_Genus'];
        if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        */
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
        return $taxon->taxonID;
    }
    private function write_text_objects($rek, $taxonID)
    {   /*[Distribution_Notes]
          [Identification] 
          [Overview]
          [Biology]
          [Taxonomic_History]
        */
        $o['taxonID'] = $taxonID;
        $o['type'] = 'http://purl.org/dc/dcmitype/Text'; //dataType
        $o['format'] = 'text/html'; //mimetype
        $o['language'] = 'en';
        $o['furtherInformationURL'] = $rek['source_url'];
        $o['UsageTerms'] = 'http://creativecommons.org/licenses/by-nc-sa/4.0/'; //license
        $o['Owner'] = 'California Academy of Sciences';
        $o['bibliographicCitation'] = $this->bibliographicCitation;
        if($text = @$rek['Distribution_Notes']) {
            $o['identifier'] = $taxonID.'_DisNot';
            $o['CVterm'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution'; //subject
            $o['title'] = 'Distribution Notes';
            $o['description'] = $text;
            self::write_data_object($o);
        }
        if($text = @$rek['Identification']) {
            $o['identifier'] = $taxonID.'_Ide';
            $o['CVterm'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#DiagnosticDescription'; //subject
            $o['title'] = 'Identification';
            $o['description'] = $text;
            self::write_data_object($o);
        }
        if($text = @$rek['Overview']) {
            $o['identifier'] = $taxonID.'_Ove';
            $o['CVterm'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#TaxonBiology'; //subject
            $o['title'] = 'Overview';
            $o['description'] = $text;
            self::write_data_object($o);
        }
        if($text = @$rek['Biology']) {
            $o['identifier'] = $taxonID.'_Bio';
            $o['CVterm'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description'; //subject
            $o['title'] = 'Biology';
            $o['description'] = $text;
            self::write_data_object($o);
        }
        if($text = @$rek['Taxonomic_History']) {
            $o['identifier'] = $taxonID.'_TaxHis';
            $o['CVterm'] = 'http://rs.tdwg.org/ontology/voc/SPMInfoItems#Description'; //subject
            $o['title'] = 'Taxonomic History';
            $o['description'] = $text;
            self::write_data_object($o);
        }
    }
    private function write_image_objects($rek, $taxonID)
    {   /*[images] => Array
            (
                [0] => Array
                    (
                        [photographer] => Andrea Walker
                        [photographer_homepage] => https://www.antweb.org/artist.do?id=105
                        [date_uploaded] => 2011-09-14 14:36:07.0
                        [media_url] => https://www.antweb.org/images/casent0246632/casent0246632_h_1_high.jpg
                    )

                [1] => Array
                    (
                        [source_url] => https://www.antweb.org/bigPicture.do?name=psw7796-21&shot=d&number=1
                        [photographer] => April Nobile
                        [photographer_homepage] => https://www.antweb.org/artist.do?id=69
                        [date_uploaded] => 2007-12-18 14:39:32.0
                        [media_url] => https://www.antweb.org/images/psw7796-21/psw7796-21_d_1_high.jpg
                        [sciname] => Acromyrmex octospinosus
                        [title] => Closeup dorsal view of Specimen PSW7796-21 from AntWeb. (Acromyrmex octospinosus)
                    )
        */
        if($loop = @$rek['images']) {
            foreach($loop as $i) {
                if(!@$i['media_url']) continue;
                $o = array();
                $o['identifier'] = pathinfo($i['media_url'], PATHINFO_FILENAME);
                $o['title'] = $i['sciname'];
                $o['description'] = $i['title'];
                $o['taxonID'] = $taxonID;
                $o['type'] = 'http://purl.org/dc/dcmitype/StillImage'; //dataType
                $o['format'] = 'image/jpeg'; //mimetype
                $o['language'] = 'en';
                $o['furtherInformationURL'] = $i['source_url'];
                $o['UsageTerms'] = 'http://creativecommons.org/licenses/by-nc-sa/4.0/'; //license
                $o['Owner'] = 'California Academy of Sciences';
                $o['bibliographicCitation'] = $this->bibliographicCitation;
                $o['accessURI'] = $i['media_url'];
                $o['CreateDate'] = $i['date_uploaded'];
                $i['role'] = 'photographer';
                if(@$i['photographer']) $o['agentID'] = self::add_agent($i);
                self::write_data_object($o);
            }
        }
    }
    private function write_data_object($o)
    {
        $fields = array_keys($o);
        $mr = new \eol_schema\MediaResource();
        foreach($fields as $field) $mr->$field = $o[$field];
        // $mr->accessURI      = self::use_best_fishbase_server($o['mediaURL']);
        // $mr->thumbnailURL   = self::use_best_fishbase_server($o['thumbnailURL']);
        // $mr->rights         = $o['dc_rights'];
        // $mr->audience       = 'Everyone';
        // $mr->LocationCreated = $o['location'];
        // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
        // if($agent_ids     =     @$this->object_agent_ids[$o['int_do_id']])  $mr->agentID = implode("; ", $agent_ids);
        
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function add_agent($a)
    {
        $r = new \eol_schema\Agent();
        $r->term_name       = $a['photographer'];
        $r->agentRole       = $a['role'];
        $r->identifier      = md5("$r->term_name|$r->agentRole");
        $r->term_homepage   = $a['photographer_homepage'];
        if(!isset($this->agent_ids[$r->identifier])) {
           $this->agent_ids[$r->identifier] = '';
           $this->archive_builder->write_object_to_file($r);
        }
        return $r->identifier;
    }
    private function write_traits($rek, $taxonID)
    {
        // print_r($rek); exit("\n123\n");
        // for Mof
        // bibliographicCitation
        /*[1] => Array(
            [specimen_code] => casent0191007
            [collection_code] => ANTC9005
            [country] => Colombia
            [date_collected] => 1992-08-01
            [determined_by] => 
            [owned_by] => CASC, San Francisco, CA, USA
        )
        [12] => Array(
            [specimen_code] => casent0630452
            [collection_code] => RSA2012-148
            [habitat] => deciduous forest
            [date_collected] => 2012-05-26
            [determined_by] => B. Boudinot
            [owned_by] => Rabeling
        )*/
        
        $save = array();
        $save['taxon_id'] = $taxonID;
        $save['source'] = $rek['source_url'];
        $save['bibliographicCitation'] = $this->bibliographicCitation;
        // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; exit("\n111\n"); //just testing

        // /* biology section
        if($biology = @$rek['Biology']) {
            if($biology_uris = self::use_pensoft_annotator_to_get_envo_uri($biology)) { $mType = 'http://purl.obolibrary.org/obo/RO_0002303';
                // print_r($biology_uris); exit;
                // $this->debug['biology recognized by Pensoft'][$biology] = $biology_uris; #good debug
                
                /*Array(
                    [canopy] => http://purl.obolibrary.org/obo/ENVO_01001240
                    [pasture] => http://purl.obolibrary.org/obo/ENVO_00000266
                    [clay] => http://purl.obolibrary.org/obo/ENVO_00002982
                )*/
                foreach($biology_uris as $label => $mValue) {
                    if(!$mValue) continue;
                    $save['measurementRemarks'] = ""; //No need to put measurementRemarks coming from Biology. Per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65452&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65452
                    $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                    
                    if(in_array($mValue, $this->investigate)) exit("\nhuli ka 1\n");
                    $this->func->add_string_types($save, $mValue, $mType, "true");
                }
            }
            // else $this->debug['Biology, Pensoft classified as no URI'][$biology] = ''; //commented so that build text will not be too long.
        }
        // */
        
        if($loop = @$rek['country_habitat']) {
            foreach($loop as $t) {
                if($country = @$t['country']) { $mType = 'http://eol.org/schema/terms/Present';
                    if($mValue = self::get_country_uri($country)) {
                        $save['measurementRemarks'] = $country;
                        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                        if(in_array($mValue, $this->investigate)) exit("\nhuli ka 2\n");
                        $this->func->add_string_types($save, $mValue, $mType, "true");
                    }
                    else $this->debug['undefined country'][$country] = '';
                }
                if($habitat = @$t['habitat']) { $mType = 'http://purl.obolibrary.org/obo/RO_0002303';
                    if($mValue = @$this->uri_values[$habitat]) {
                        $save['measurementRemarks'] = $habitat;
                        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                        if(in_array($mValue, $this->investigate)) exit("\nhuli ka 3\n");
                        $this->func->add_string_types($save, $mValue, $mType, "true");
                    }
                    /* Original. Worked for the longest time. But it doesn't differentiate with e.g. 'port of entry' without URI;
                    and those terms that are not yet seen by Jen. And hasn't been classified yet to have or not to have a URI.
                    
                    elseif($val = @$this->habitat_map[$habitat]) { $mType = 'http://purl.obolibrary.org/obo/RO_0002303';
                        // echo "\nmapping OK [$val][$habitat]\n"; //good debug info
                        $habitat_uris = explode(";", $val);
                        $habitat_uris = array_map('trim', $habitat_uris);
                        foreach($habitat_uris as $mValue) {
                            if(!$mValue) continue;
                            $save['measurementRemarks'] = $habitat;
                            $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                            $this->func->add_string_types($save, $mValue, $mType, "true");
                        }
                    }
                    else $this->debug['undefined habitat'][$habitat] = ''; //commented so that build text will not be too long.
                    */
                    // /* New. This one will now differentiate terms that are classified and are not.
                    else {
                        if(isset($this->habitat_map[$habitat])) {
                            if($val = @$this->habitat_map[$habitat]) { $mType = 'http://purl.obolibrary.org/obo/RO_0002303';
                                // echo "\nmapping OK [$val][$habitat]\n"; //good debug info
                                $habitat_uris = explode(";", $val);
                                $habitat_uris = array_map('trim', $habitat_uris);
                                foreach($habitat_uris as $mValue) {
                                    if(!$mValue) continue;
                                    $save['measurementRemarks'] = $habitat;
                                    $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                                    if(in_array($mValue, $this->investigate)) exit("\nhuli ka 4\n");
                                    $this->func->add_string_types($save, $mValue, $mType, "true");
                                }
                            }
                            else {
                                if($habitat_uris = self::use_pensoft_annotator_to_get_envo_uri($habitat)) {
                                    // $this->debug['habitats recognized by Pensoft'][$habitat] = $habitat_uris; #good debug
                                    foreach($habitat_uris as $mValue) {
                                        if(!$mValue) continue;
                                        $save['measurementRemarks'] = $habitat;
                                        $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                                        if(in_array($mValue, $this->investigate)) exit("\nhuli ka 5\n");
                                        $this->func->add_string_types($save, $mValue, $mType, "true");
                                    }
                                }
                                // else $this->debug['habitat classified as no URI'][$habitat] = ''; //commented so that build text will not be too long.
                            }
                        }
                        else {
                            if($habitat_uris = self::use_pensoft_annotator_to_get_envo_uri($habitat)) {
                                // $this->debug['habitats recognized by Pensoft'][$habitat] = $habitat_uris; #good debug
                                foreach($habitat_uris as $mValue) {
                                    if(!$mValue) continue;
                                    $save['measurementRemarks'] = $habitat;
                                    $save["catnum"] = $taxonID.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
                                    if(in_array($mValue, $this->investigate)) exit("\nhuli ka 6\n");
                                    $this->func->add_string_types($save, $mValue, $mType, "true");
                                }
                            }
                            // else $this->debug['undefined habitat'][$habitat] = ''; //commented so that build text will not be too long.
                        }
                    }
                    // */
                }

            } //end loop
        }
    }
    private function use_pensoft_annotator_to_get_envo_uri($habitat)
    {
        // echo "\ninput: [$habitat]...";
        $final = array();
        $basename = md5($habitat);
        $desc = strip_tags($habitat);
        $desc = trim(Functions::remove_whitespace($desc));
        $this->pensoft->results = array();
        if($arr = $this->pensoft->retrieve_annotation($basename, $desc)) {
            // print_r($arr); //exit("\n-test muna\n");
            /*Array(
                [http://purl.obolibrary.org/obo/ENVO_01000204] => tropical
            )*/
            /* NEW format for $arr is:
            Array(
                [http://purl.obolibrary.org/obo/ENVO_01000204] => array("lbl" => "tropical", "ontology" => "envo");
            )*/
            
            //======================================================================================
            $arr2 = array();
            // /* copied template from Pensoft2EOLAPI.php
            // foreach($arr as $uri => $label) {
            foreach($arr as $uri => $rek) {
                if($ret = $this->pensoft->apply_adjustments($uri, $rek['lbl'])) {
                    $uri = $ret['uri'];
                    $label = $ret['label'];
                    $arr2[$uri] = $label;
                }
                else continue;
            }
            // */
            //======================================================================================
            if($arr2) $arr = array_keys($arr2);
            else return array();
            // /* customize ----------
            //per Jen: https://eol-jira.bibalex.org/browse/DATA-1713?focusedCommentId=65408&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65408
            $arr = array_diff($arr, $this->remove_across_all_resources); // remove 'cloud', 'cut'
            //per Jen: https://eol-jira.bibalex.org/browse/DATA-1870?focusedCommentId=65426&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65426
            if($arr) {
                foreach($arr as $uri) {
                    if(isset($this->descendants_of_aquatic[$uri])) {}
                    else {
                        $label = $arr2[$uri];
                        $final[$label] = $uri;
                    }
                }
            }
            // ---------- */
            //======================================================================================
            return $final;
        }
        // else echo " - nothing from Pensoft";
    }
    /* copied template
    private function write_presence_measurement_for_state($state_id, $rec)
    {   $string_value = $this->area_id_info[$state_id];
        if($string_uri = self::get_string_uri($string_value)) {}
        else {
            $this->debug['no uri mapping yet'][$string_value];
            $string_uri = $string_value;
        }
        $mValue = $string_uri;
        $mType = 'http://eol.org/schema/terms/Present'; //for generic range
        $taxon_id = $rec['Symbol'];
        $save = array();
        $save['taxon_id'] = $taxon_id;
        $save["catnum"] = $taxon_id.'_'.$mType.$mValue; //making it unique. no standard way of doing it.
        $save['source'] = $rec['source_url'];
        $save['measurementRemarks'] = $string_value;
        // $save['measurementID'] = '';
        // echo "\nLocal: ".count($this->func->remapped_terms)."\n"; //just testing
        $this->func->pre_add_string_types($save, $mValue, $mType, "true");
    }
    */
    private function get_country_uri($country)
    {
        // print_r($this->uri_values); exit("\ntotal: ".count($this->uri_values)."\n"); //debug only
        if($country_uri = @$this->uri_values[$country]) return $country_uri;
        else {
            /*
            switch ($country) { //put here customized mapping
                // case "Port of Entry":   return false; //"DO NOT USE";
                // just examples below. Real entries here were already added to /cp_new/GISD/mapped_location_strings.txt
                // case "United States of America":        return "http://www.wikidata.org/entity/Q30";
                // case "Dutch West Indies":               return "http://www.wikidata.org/entity/Q25227";
            }
            */
        }
    }
}
?>