<?php
namespace php_active_record;
/* connector: [inat_images.php] */
class iNatImagesAPI /* copied template, from: NMNHimagesAPI.php */
{
    function __construct($folder = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        if(Functions::is_production()) {
            $this->path       = '/extra/other_files/iNat_image_DwCA/GBIF_service/';
            $this->cache_path = '/extra/other_files/iNat_image_DwCA/cache/';
        }
        else {
            $this->path       = '/Volumes/AKiTiO4/web/cp/iNat_image_DwCA/GBIF_service/0166633-210914110416597/'; //just subset data - Gadus morhua
            $this->cache_path = '/Volumes/AKiTiO4/web/cp/iNat_image_DwCA/cache/';
        }
        if(!is_dir($this->cache_path)) mkdir($this->cache_path);
        // $this->cache_path .= "/";
        
        $this->occurrence_gbifid_with_images = array();
        $this->initial_image_limit = 100; //FINALLY 100 agreed upon to use: 100-select-20 //20; //40; //75; //100; //150; //no score ranking, just plain get first xx images per taxon in iNat.
        // $this->download_options = array(
        //     'expire_seconds'     => 60*60*24*30, //expires in 1 month
        //     'download_wait_time' => 2000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 1, 'cache' => 1);
    }
    /*================================================================= STARTS HERE ======================================================================*/
    function start()
    {
        // /*
        require_library('connectors/CacheMngtAPI');
        $this->func = new CacheMngtAPI($this->cache_path);
        // */
        self::process_table('occurrence'); //1st step: basically caching taxon reks, to be used on 2nd step
        self::process_table('multimedia'); //2nd step
        unset($this->occurrence_gbifid_with_images); //clear memory
        print_r($this->debug);
        $this->archive_builder->finalize(true);
    }
    private function process_table($what)
    {   /* as of Mar xx, 2022
        image rows from occurrence.txt: [xxx]
        */
        $path = $this->path.$what.'.txt';
        $i = 0;
        foreach(new FileIterator($path) as $line_number => $line) { // 'true' will auto delete temp_filepath
            $i++;
            // if(($i % 500000) == 0) echo "\n".number_format($i) . "[$path]"; //good debug, but transferred below
            if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                continue;
            }
            else {
                /*
                    [0] => 1        [1] => 47416
                    [0] => 47416    [1] => 94831
                */
                /* new ranges ----------------------------------------------------
                if($range_from && $range_to) {
                    $cont = false;
                    if($i >= $range_from && $i < $range_to) $cont = true;
                    if(!$cont) continue;
                    
                    //newly added:
                    if($i >= $range_to) {
                        echo "\nHave now reached upper limit [$range_to]. Will end loop\n";
                        break;
                    }
                }
                ---------------------------------------------------- */
                
                if(!@$row[0]) continue; //$row[0] is gbifID
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = $row[$k];
                    $k++;
                }
            }
            if(($i % 500000) == 0) echo "\n".number_format($i) . "[$what][$path]". " [".count($this->occurrence_gbifid_with_images)."]";
            // /*
            
            $gbifid = $rec['gbifid'];
            if($what == 'occurrence') {
                /* debug
                if($rec['gbifid'] == '1317202490') { print_r($rec); exit("\nstopx [$what]\n"); }
                */
                @$this->debug['type'][$rec['type']]++; //= ''; //stats only
                // $this->debug['mediatype'][$rec['mediatype']] = ''; //stats only
                
                // if($rec['type'] == 'Image') { @$this->occurrence_image_type_rows++;
                if(stripos($rec['mediatype'], "StillImage") !== false ||
                   stripos($rec['mediatype'], "MovingImage") !== false ||
                   stripos($rec['mediatype'], "Sound") !== false) { //string is found
                    @$this->debug['type taken'][$rec['type']]++; //= ''; //stats only
                    // print_r($rec); exit("\nstopx\n");
                    $rek = array();
                    // $rek['gbifid'] = $gbifid; //1456016777
                    $rek['sn'] = $rec['scientificname']; //Hemicaranx amblyrhynchus (Cuvier, 1833)
                    $rek['k'] = $rec['kingdom']; //Animalia
                    $rek['p'] = $rec['phylum']; //Chordata
                    $rek['c'] = $rec['class']; //Actinopterygii
                    $rek['o'] = $rec['order']; //Perciformes
                    $rek['f'] = $rec['family']; //Carangidae
                    $rek['g'] = $rec['genus']; //Hemicaranx
                    $rek['r'] = strtolower($rec['taxonrank']); //SPECIES
                    
                    if($val = @$rec['taxonid']) $rek['s']  = "https://www.inaturalist.org/taxa/".$val;
                    elseif($val = @$rec['references']) $rek['s'] = $val;    //https://www.inaturalist.org/observations/106689439
                    elseif($val = @$rec['occurrenceid']) $rek['s'] = $val;  //https://www.inaturalist.org/observations/106689439
                    
                    /* memory extensive, not good. Used below instead.
                    $this->occurrence_gbifid_with_images[$gbifid] = json_encode($rek);
                    */
                    // /* New solution:
                    $rek_taxonID = self::format_taxonID($rek); //New: Mar 1, 2022
                    $this->occurrence_gbifid_with_images[$gbifid] = $rek_taxonID; //assignment, to be accessed in multimedia.txt
                    $md5_id = md5($rek_taxonID);
                    if($arr_rek = $this->func->retrieve_json_obj($md5_id, false)) {} //2nd param false means returned value is an array()
                    else {
                        $json = json_encode($rek);
                        $this->func->save_json($md5_id, $json);
                        // $arr_rek = json_decode($json, true);                    //just for testing
                        // print_r($rek); print_r($arr_rek); exit("\ntest...\n");  //just for testing
                    }
                    // */
                    /* copied template
                    [acceptedscientificname] => Hemicaranx amblyrhynchus (Cuvier, 1833)
                    [verbatimscientificname] => Hemicaranx amblyrhynchus
                    [license] => CC0_1_0
                    */
                    @$this->debug['license occurrence.txt'][$rec['license']]++; //= '';
                }
            }
            elseif($what == 'multimedia') { // print_r($rec); exit("\nstopx\n");
                // /* New solution:
                if($rek_taxonID = @$this->occurrence_gbifid_with_images[$gbifid]) {
                    $md5_id = md5($rek_taxonID);
                    if($taxon_rek = $this->func->retrieve_json_obj($md5_id, false)) {} //2nd param false means returned value is an array()
                    else exit("\nThere should be cache at this point [$rek_taxonID].\n");
                }
                else exit("\nThere should be cache rek_taxonID at this point [$gbifid].\n");
                // */

                if(@$this->taxon_images_count[$rek_taxonID] > $this->initial_image_limit) continue;
                
                if($taxon_rek) { // print_r($taxon_rek); exit("\nditox na\n");
                    // /* debug only
                    if(!@$taxon_rek['sn']) {
                        print_r($taxon_rek); exit("\n no sn scientificname \n");
                    }
                    // */
                    
                    if(self::write_media($rec, $rek_taxonID, $taxon_rek)) self::write_taxon($taxon_rek, $rek_taxonID);
                }
                else {
                    // /* good debug
                    print_r($rec);
                    exit("\nshould not go here [$rek_taxonID]...\n");
                    // */
                }
                // if($i >= 100) break; //debug only
            }
            
            // */
            // if($rec['type'] == 'Image') {
                // if($rec['gbifid'] == '1456016777') {
                //     print_r($rec); exit("\nstopx [multimedia.txt]\n");
                // }
            // }
            // print_r($rec); exit("\nstopx\n");
            /*
            Array occurrence.txt ( --- see inat_images.php instead
            )
            */
        } //end foreach
    }
    private function write_taxon($rek, $taxonID)
    {   /*Array(
            [scientificname] => Argemone corymbosa Greene
            [kingdom] => Plantae
            [phylum] => Tracheophyta
            [class] => Magnoliopsida
            [order] => Ranunculales
            [family] => Papaveraceae
            [genus] => Argemone
            [taxonrank] => species
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $taxonID;
        $taxon->scientificName  = $rek['sn'];
        $taxon->kingdom         = $rek['k'];
        $taxon->phylum          = $rek['p'];
        $taxon->class           = $rek['c'];
        $taxon->order           = $rek['o'];
        $taxon->family          = $rek['f'];
        $taxon->genus           = $rek['g'];
        $taxon->taxonRank       = $rek['r'];
        $taxon->furtherInformationURL = $rek['s'];
        if(!isset($this->taxa_ids[$taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxa_ids[$taxonID] = '';
        }
        return $taxonID;
    }
    private function write_media($rec, $taxonID, $rek)
    {   /* Array multimedia.txt ( see inat_images.php ) */
        
        if(!self::valid_record($rec['title'], $rec['description'], $rec['source'])) return false;

        /* less: blank StillImage value --- xxx recs below ==========
        [rec_type] => Array(
                    [StillImage] => Array(
                            [image/jpeg] => xxxxxx
                            [] => xxx
                        )
                )
        */
        if($rec['type'] == "StillImage" && $rec['format'] == "image/jpeg") {}
        else return false;
        /* end ========== */

        @$this->debug['rec_type'][$rec['type']][$rec['format']]++; //= ''; //for stats
        @$this->debug['media type'][$rec['type']]++; //= ''; //for stats
        // @$this->debug['references values'][$rec['references']]++; //= ''; //for stats
        
        if(!$rec['type'] || !$rec['format']) return false;
        
        $type_info['StillImage'] = 'http://purl.org/dc/dcmitype/StillImage';
        $type_info['MovingImage'] = 'http://purl.org/dc/dcmitype/MovingImage';
        $type_info['Sound'] = 'http://purl.org/dc/dcmitype/Sound';

        $format_info['StillImage'] = 'image/jpeg';
        if($rec['type'] == 'Sound') {
            $format_info['Sound'] = self::format_Sound($rec['format']);
        }
        if($rec['type'] == 'MovingImage') {
            $format_info['MovingImage'] = self::format_MovingImage($rec['format']);
            // if($format_info['MovingImage'] = self::format_MovingImage($rec['format'])) {}
            // else return false;
        }
        
        $mr = new \eol_schema\MediaResource();
        $mr->taxonID        = $taxonID;
        $mr->type           = $type_info[$rec['type']];
        $mr->language       = 'en';
        $mr->format         = $format_info[$rec['type']];
        $mr->furtherInformationURL = $rec['references'];
        $mr->accessURI      = $rec['identifier'];
        // $mr->CVterm         = '';
        $mr->Owner          = $rec['rightsholder'];
        // $mr->rights         = '';
        $mr->CreateDate     = $rec['created'];
        $mr->title          = $rec['title'];
        // $mr->UsageTerms     = 'http://creativecommons.org/licenses/publicdomain/'; //copied template
        if($mr->UsageTerms = self::format_license($rec)) {}
        else return false;

        // $mr->audience       = 'Everyone';
        $mr->description    = $rec['description'];
        // $mr->LocationCreated = '';
        // $mr->bibliographicCitation = '';
        // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
        
        $agent_ids = self::add_agents($rec);
        $mr->agentID = implode("; ", $agent_ids);

        /* it is in occurrenct.txt not in multimedia.txt
        $mr->lat    = $rec['decimallatitude'];
        $mr->long    = $rec['decimallongitude'];
        */
        
        // /* New: hash it
        $arr = (array) $mr; //convert object to array; Just typecast it
        $mr->identifier = md5(json_encode($arr));
        // */
        
        if(!isset($this->object_ids[$mr->identifier])) {
            // /* New: to limit 100 or 150 images per taxon at this point.
            @$this->taxon_images_count[$taxonID]++;
            if($this->taxon_images_count[$taxonID] > $this->initial_image_limit) return;
            // */
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
        return true;
    }
    private function format_taxonID($rek)
    {
        return md5($rek['sn'].$rek['k'].$rek['p'].$rek['c'].$rek['o'].$rek['f'].$rek['g'].$rek['r']);
    }
    private function format_license($rec)
    {
        $str = $rec['license'];
            if($str == "CC_BY_NC_4_0")  return "http://creativecommons.org/licenses/by-nc/4.0/";
        elseif($str == "CC_BY_4_0")     return "http://creativecommons.org/licenses/by/4.0/";
        elseif($str == "CC0_1_0")       return "http://creativecommons.org/licenses/publicdomain/";
        else {
            if(stripos($str, "licenses/by-nc/") !== false) return $str; //string is found
            if(stripos($str, "licenses/by-nc-sa/") !== false) return $str; //string is found
            if(stripos($str, "licenses/by/") !== false) return $str; //string is found
            if(stripos($str, "licenses/by-sa/") !== false) return $str; //string is found
            if(stripos($str, "licenses/publicdomain/") !== false) return "http://creativecommons.org/licenses/publicdomain/"; //string is found
            if(stripos($str, "publicdomain/zero/") !== false) return "http://creativecommons.org/licenses/publicdomain/"; //string is found
            // print_r($rec);
            $this->debug['invalid license'][$str] = '';
            return false;
        }
    }
    private function format_Sound($format)
    {
        if($format == 'audio/wav') return 'audio/x-wav';
        elseif($format == 'mpeg') return 'audio/mpeg';
        else exit("\nNot initialized Sound format [$format]\n");
    }
    private function format_MovingImage($format)
    {
        if($format == 'mp4') return 'video/mp4';
        elseif($format == 'quicktime') return 'video/quicktime';
        elseif($format == 'avi') return 'video/x-msvideo';
        else {
            exit("\nNot initialized MovingImage format [$format]\n");
            return false;
        }
    }
    private function valid_record($title, $description, $source)
    {
        $terms = array('Ledger', 'card', 'Barcode', 'documentation', 'Book', 'note', 'scanned paper', 'sheet', 'Label');
        // /* per https://eol-jira.bibalex.org/browse/DATA-1871?focusedCommentId=66454&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-66454
        $terms[] = 'TAX CRT';
        $terms[] = 'Taxa CRT';
        // */
        foreach($terms as $term) {
            if(stripos($description, $term) !== false) return false; //string is found
            if(stripos($title, $term) !== false) return false; //string is found
        }
        $terms = array('published', 'footnote');
        foreach($terms as $term) {
            if(stripos($source, $term) !== false) return false; //string is found
        }
        return true;
    }
    private function add_agents($rec)
    {
        // [creator] => Division of Fishes
        // [publisher] => Smithsonian Institution, NMNH, Fishes
        $agent_ids = array();
        $roles = array('publisher', 'creator');
        foreach($roles as $role) {
            if($term_name = @$rec[$role]) {
                $r = new \eol_schema\Agent();
                $r->term_name       = $term_name;
                $r->agentRole       = $role;
                $r->identifier      = md5("$r->term_name|$r->agentRole");
                // $r->term_homepage   = '';
                $agent_ids[] = $r->identifier;
                if(!isset($this->agent_ids[$r->identifier])) {
                   $this->agent_ids[$r->identifier] = $r->term_name;
                   $this->archive_builder->write_object_to_file($r);
                }
            }
        }
        return $agent_ids;
    }
}