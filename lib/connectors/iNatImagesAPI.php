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
        self::process_table('occurrence');
        self::process_table('multimedia');
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
            if(($i % 500000) == 0) echo "\n".number_format($i) . "[$path]". " [".count($this->occurrence_gbifid_with_images)."]";
            // /*
            
            $gbifid = $rec['gbifid'];
            if($what == 'occurrence') {
                
                /* debug
                if($rec['gbifid'] == '1317202490') {
                    print_r($rec); exit("\nstopx [$what]\n");
                }
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
                    elseif($val = @$rec['identifier']) $rek['s'] = $val;        //http://n2t.net/ark:/65665/325e37c09-cdb2-4ef9-946f-44482687b6e9
                    elseif($val = @$rec['occurrenceid']) $rek['s'] = $val;  //http://n2t.net/ark:/65665/325e37c09-cdb2-4ef9-946f-44482687b6e9
                    
                    /* memory extensive, not good. Used below instead.
                    $this->occurrence_gbifid_with_images[$gbifid] = json_encode($rek);
                    */
                    // /* New solution:
                    $md5_id = md5($gbifid);
                    if($arr_rek = $this->func->retrieve_json_obj($md5_id, false)) {} //2nd param false means returned value is an array()
                    else {
                        $json = json_encode($rek);
                        $this->func->save_json($md5_id, $json);
                        // $arr_rek = json_decode($json, true);                    //just for testing
                        // print_r($rek); print_r($arr_rek); exit("\ntest...\n");  //just for testing
                    }
                    // */
                    
                    
                    // [acceptedscientificname] => Hemicaranx amblyrhynchus (Cuvier, 1833)
                    // [verbatimscientificname] => Hemicaranx amblyrhynchus
                    // [license] => CC0_1_0
                    @$this->debug['license'][$rec['license']]++; //= '';
                }
            }
            elseif($what == 'multimedia') { // print_r($rec); exit("\nstopx\n");
                
                // /* New solution:
                $md5_id = md5($gbifid);
                if($rek = $this->func->retrieve_json_obj($md5_id, false)) {} //2nd param false means returned value is an array()
                else exit("\nThere should be cache at this point.\n");
                // */
                
                // if($json = @$this->occurrence_gbifid_with_images[$gbifid]) { --- old implementation
                if($rek) {
                    // print_r($rek); exit("\nditox na\n");
                    
                    // /* debug only
                    if(!@$rek['sn']) {
                        print_r($rek); exit("\n no sn scientificname \n");
                    }
                    // */
                    
                    $taxonID = md5($rek['sn']); //copied template
                    $taxonID = self::format_taxonID($rek); //New: Mar 1, 2022
                    if(@$this->taxon_images_count[$taxonID] >= 150) continue;
                    if(self::write_media($rec, $taxonID, $rek)) self::write_taxon($rek, $taxonID);
                }
                else {
                    // /* good debug
                    print_r($rec);
                    exit("\nshould not go here...\n");
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
            Array occurrence.txt ( --- from copied template
                [accessrights] => 
                [bibliographiccitation] => 
                [contributor] => 
                [created] => 
                [creator] => 
                [datesubmitted] => 
                [description] => 
                [format] => 
                
                [publisher] => National Museum of Natural History, Smithsonian Institution
                [rights] => 
                [rightsholder] => 
                [source] => 
                [temporal] => 
                [title] => 
                [type] => Image
                [institutionid] => http://biocol.org/urn:lsid:biocol.org:col:34871
                [institutioncode] => USNM
                [collectioncode] => Fishes
                [datasetname] => NMNH Extant Specimen Records
                [ownerinstitutioncode] => 
                [basisofrecord] => MACHINE_OBSERVATION
                [catalognumber] => RAD117265
                [individualcount] => 1
                [organismquantity] => 
                [organismquantitytype] => 
                [occurrencestatus] => PRESENT
                [preparations] => Polyester
                [eventdate] => 1970-09-02T00:00:00
                [startdayofyear] => 245
                [enddayofyear] => 245
                [year] => 1970
                [month] => 9
                [day] => 2
                [verbatimeventdate] => 1970 Sep 02 - 0000 00 00
                [locationid] => 11206
                [highergeography] => Atlantic, Gulf of Mexico, United States, Florida
                [waterbody] => Atlantic, Gulf of Mexico
                [countrycode] => US
                [stateprovince] => Florida
                [decimallatitude] => 29.18
                [decimallongitude] => -87.28
                [typestatus] => 
                [identifiedby] => 
                [dateidentified] => 
                [taxonid] => 
                [scientificnameid] => 
                [acceptednameusageid] => 2391412
                [parentnameusageid] => 

                [acceptednameusage] => 
                [parentnameusage] => 
                [originalnameusage] => 
                [higherclassification] => Animalia, Chordata, Vertebrata, Osteichthyes, Actinopterygii, Neopterygii, Acanthopterygii, Perciformes, Percoidei, Carangidae
                [subgenus] => 
                [specificepithet] => amblyrhynchus
                [infraspecificepithet] => 

                [taxonrank] => SPECIES
                [verbatimtaxonrank] => 
                [nomenclaturalcode] => 
                [taxonomicstatus] => ACCEPTED
                [nomenclaturalstatus] => 
                [taxonremarks] => 
                [datasetkey] => 821cc27a-e3bb-4bc5-ac34-89ada245069d
                [lastinterpreted] => 2020-12-15T21:16:20.936Z
                [depth] => 1463.0
                [issue] => OCCURRENCE_STATUS_INFERRED_FROM_INDIVIDUAL_COUNT;GEODETIC_DATUM_ASSUMED_WGS84
                [mediatype] => StillImage
                [hascoordinate] => true
                [hasgeospatialissues] => false
                [taxonkey] => 2391412
                [acceptedtaxonkey] => 2391412
                [species] => Hemicaranx amblyrhynchus
                [genericname] => Hemicaranx
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
    {   /*Array multimedia.txt (
            [gbifid] => 1456016777
            [type] => StillImage
            [format] => tiff, jpeg, jpeg, jpeg, jpeg
            [identifier] => http://n2t.net/ark:/65665/m3746dde37-ea30-45c0-aa16-31d34de9fa4e
            [references] => 
            [title] => Hemicaranx amblyrhynchus RAD117265-001
            [description] => Envelope Notes Verbatim: Box 1; S-V 435; 1; FL 188; Gulf of Mexico; 2 exposures.
            [source] => Division of Fishes NMNH Smithsonian Institution
            [audience] => 
            [created] => 
            [creator] => Division of Fishes
            [contributor] => 
            [publisher] => Smithsonian Institution, NMNH, Fishes
            [license] => Usage Conditions Apply
            [rightsholder] => 
        )
        Array(
            [gbifid] => 1317613575
            [type] => Sound
            [format] => audio/wav
            [identifier] => http://n2t.net/ark:/65665/m3eca9242b-d95f-46e6-abf0-67e8035712e3
            [references] => 
            [title] => USNM 642718 Achaetops pycnopygius
            [description] => USNM 642718 Achaetops pycnopygius
            [source] => 
            [audience] => 
            [created] => 
            *[creator] => Schmidt, Brian K.
            [contributor] => 
            *[publisher] => Smithsonian Institution, NMNH, Birds
            [license] => Usage Conditions Apply
            [rightsholder] => 
        )
        */
        
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
        @$this->debug['references values'][$rec['references']]++; //= ''; //for stats

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
        $mr->furtherInformationURL = $rek['s'];
        $mr->accessURI      = $rec['identifier'];
        // $mr->CVterm         = '';
        // $mr->Owner          = '';
        // $mr->rights         = '';
        $mr->title          = $rec['title'];
        $mr->UsageTerms     = 'http://creativecommons.org/licenses/publicdomain/'; //copied template
        $mr->UsageTerms     = self::format_license($rec);
        // $mr->audience       = 'Everyone';
        $mr->description    = $rec['description'];
        // $mr->LocationCreated = '';
        // $mr->bibliographicCitation = '';
        // if($reference_ids = @$this->object_reference_ids[$o['int_do_id']])  $mr->referenceID = implode("; ", $reference_ids);
        
        $agent_ids = self::add_agents($rec);
        $mr->agentID = implode("; ", $agent_ids);
        
        // /* New: hash it
        $arr = (array) $mr; //convert object to array; Just typecast it
        $mr->identifier = md5(json_encode($arr));
        // */
        
        if(!isset($this->object_ids[$mr->identifier])) {
            // /* New: to limit 100 or 150 images per taxon at this point.
            @$this->taxon_images_count[$taxonID]++;
            if($this->taxon_images_count[$taxonID] >= 151) return;
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
            print_r($rec);
            exit("\ninvalid license\n");
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