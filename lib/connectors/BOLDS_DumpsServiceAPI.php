<?php
namespace php_active_record;
/* connector: [bolds.php]

processid	sampleid	recordID	catalognum	fieldnum	institution_storing	collection_code	bin_uri	
phylum_taxID	phylum_name	class_taxID	class_name	order_taxID	order_name	family_taxID	family_name	subfamily_taxID	subfamily_name	genus_taxID	genus_name	
species_taxID	species_name	subspecies_taxID	subspecies_name	identification_provided_by	identification_method	identification_reference	tax_note	voucher_status	
tissue_type	collection_event_id	collectors	collectiondate_start	collectiondate_end	collectiontime	collection_note	site_code	sampling_protocol	lifestage	sex	reproduction	
habitat	associated_specimens	associated_taxa	extrainfo	notes	lat	lon	coord_source	coord_accuracy	elev	depth	elev_accuracy	depth_accuracy	country	province_state	region	
sector	exactsite	
image_ids	image_urls	media_descriptors	captions	copyright_holders	copyright_years	copyright_licenses	copyright_institutions	photographers	
sequenceID	markercode	genbank_accession	nucleotides	trace_ids	trace_names	trace_links	run_dates	sequencing_centers	directions	seq_primers	marker_codes
*/
class BOLDS_DumpsServiceAPI
{
    function __construct($folder = false)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        
        $this->resource_agent_ids = array();
        $this->max_images_per_taxon = 10;
        
        $this->page['home'] = "http://www.boldsystems.org/index.php/TaxBrowser_Home";
        $this->page['sourceURL'] = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=";
        $this->service['phylum'] = "http://v2.boldsystems.org/connect/REST/getSpeciesBarcodeStatus.php?phylum=";
        $this->service["taxId"] = "http://www.boldsystems.org/index.php/API_Tax/TaxonData?dataTypes=all&includeTree=true&taxId=";
        $this->download_options = array('cache' => 1, 'resource_id' => 'BOLDS', 'expire_seconds' => 60*60*24*30*6, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1); //6 months to expire
        $this->download_options['expire_seconds'] = false;
        //Arthropoda
        $this->kingdom['Animalia'] = array("Acanthocephala", "Annelida", "Arthropoda", "Brachiopoda", "Bryozoa", "Chaetognatha", "Chordata", "Cnidaria", "Cycliophora", "Echinodermata", "Gnathostomulida", "Hemichordata", "Mollusca", "Nematoda", "Nemertea", "Onychophora", "Platyhelminthes", "Porifera", "Priapulida", "Rotifera", "Sipuncula", "Tardigrada", "Xenoturbellida");
        $this->kingdom['Plantae'] = array("Bryophyta", "Chlorophyta", "Lycopodiophyta", "Magnoliophyta", "Pinophyta", "Pteridophyta", "Rhodophyta");
        $this->kingdom['Fungi'] = array("Ascomycota", "Basidiomycota", "Chytridiomycota", "Glomeromycota", "Myxomycota", "Zygomycota");
        $this->kingdom['Protista'] = array("Chlorarachniophyta", "Ciliophora", "Heterokontophyta", "Pyrrophycophyta");
        $this->debug = array();
        $this->temp_path = CONTENT_RESOURCE_LOCAL_PATH . "BOLDS_temp5/";
        $this->cnt = 0;
    }

    function start_using_dump()
    {
        self::create_kingdom_taxa(); //create taxon entry for the 4 kingdoms
        $phylums = array_merge($this->kingdom['Animalia'], $this->kingdom['Plantae'], $this->kingdom['Fungi'], $this->kingdom['Protista']);
        echo("\n Total phylums: ".count($phylums)."\n");

        //------------------------- the 3 big ones:
        // $phylums = array('Arthropoda');
        // $phylums = array('Chordata'); //OK
        // $phylums = array('Magnoliophyta'); //OK

        // for review, first crack:
        // $phylums = array('Annelida'); //Animals
        // $phylums = array('Rhodophyta'); //Plants
        // $phylums = array('Basidiomycota'); //Fungi
        // $phylums = $this->kingdom['Protista'];

        // $phylums = array('Annelida', 'Chordata', 'Rhodophyta', 'Basidiomycota', 'Heterokontophyta');
        
        foreach($phylums as $phylum) $this->dump[$phylum] = "http://localhost/cp/BOLDS_new/bold_".$phylum.".txt.zip"; //assign respective source .txt.zip file

        foreach($phylums as $phylum) {
            if(!$phylum) continue;
            $this->current_kingdom = self::get_kingdom_given_phylum($phylum);
            $this->tax_ids = array(); //initialize parentID
            $this->img_tax_ids = array(); //initialize images per phylum
            self::download_and_extract_remote_file($this->dump[$phylum], true);

            //for images start -------------------------------------------------
            // /*
            if(is_dir($this->temp_path)) recursive_rmdir($this->temp_path);
            mkdir($this->temp_path);
            
            self::process_dump($phylum, "get_images_from_dump_rec");
            self::create_media_archive_from_dump();
            $this->img_tax_ids = array(); //initialize images per phylum

            recursive_rmdir($this->temp_path);
            // */
            //for images end -------------------------------------------------

            //for taxon
            $txt_file = self::process_dump($phylum, "write_taxon_archive");
            unlink($txt_file);
        }
        /* we no longer provide the parentNameUsageID since it is not scalable when doing thousands of API calls. Doable but not scalable
        self::add_needed_parent_entries(1);
        */
        $this->tax_ids = array();       //release memory
        $this->img_tax_ids = array();   //release memory
        
        $this->archive_builder->finalize(true);
        self::start_print_debug();
        echo "\ncnt = $this->cnt \n";
    }
    private function process_dump($phylum, $what)
    {
        $txt_file = DOC_ROOT."tmp/bold_".$phylum.".txt";
        $i = 0; $higher_level_ids = array();
        foreach(new FileIterator($txt_file) as $line_number => $line) {
            $i++;
            $row = explode("\t", $line);
            if($i == 1) $fields = $row;
            else {
                $k = -1;
                $rec = array();
                foreach($fields as $field) {
                    $k++;
                    $rec[$field] = @$row[$k];
                }
                $rec = array_map('trim', $rec);
                // print_r($rec);
                // /* un-comment in normal operation
                if($sci = self::valid_rec($rec)) {
                    // if($rec['species_name'] == "Metaphire magna") print_r($rec); //debug only
                    if    ($what == "get_images_from_dump_rec") self::get_images_from_dump_rec($rec, $sci);
                    elseif($what == "write_taxon_archive")      self::create_taxon_archive_from_dump($sci);
                }
                // if($what == "write_taxon_archive") self::create_taxon_higher_level_archive($rec); //obsolete
                if($what == "write_taxon_archive") $higher_level_ids = self::get_higher_level_ids($rec, $higher_level_ids);
                // */
                
                /* for debug only
                if(@$rec['image_ids']) {
                    print_r($rec); //exit("\nRecord found\n");
                }
                */
                /*
                //for stats only to get unique values of these fields - working OK
                $this->debug[$this->current_kingdom]['lifestage'][$rec['lifestage']]        = '';
                $this->debug[$this->current_kingdom]['sex'][$rec['sex']]                    = '';
                $this->debug[$this->current_kingdom]['reproduction'][$rec['reproduction']]  = '';
                $this->debug[$this->current_kingdom]['habitat'][$rec['habitat']]            = '';
                */
                
                if(($i % 10000) == 0) echo "\n".number_format($i)." $phylum $what";
            }
            // if($i >= 1000) break; //debug only
        }
        /* we no longer provide the parentNameUsageID
        if($what == "write_taxon_archive") {
            foreach(array_keys($higher_level_ids) as $taxid) {
                if(isset($this->taxon_ids[$taxid])) continue; //meaning this taxon has already been added to dwca
                if(self::process_record($taxid)) {}
                else {
                    if($taxon_info = self::get_info_from_page($taxid)) self::create_taxon_archive($taxon_info);
                }
            }
        }
        */
        return $txt_file;
    }
    private function create_taxon_archive_from_dump($a)
    {
        /* Array(
            [taxid] => 256937
            [taxon] => Allolobophora chlorotica L1
            [tax_rank] => species
            [ancestry] => Array(
                    [kingdom] => Animalia
                    [phylum] => Annelida
                    [class] => Clitellata
                    [order] => Haplotaxida
                    [family] => Lumbricidae
                    [genus] => Allolobophora
                )
            [parentid] => 101071
            [tax_division] => Animals
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $a['taxid'];
        $taxon->scientificName      = $a['taxon'];
        $taxon->taxonRank           = $a['tax_rank'];
        
        $ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
        foreach($ranks as $rank) $taxon->$rank = @$a['ancestry'][$rank];
        
        /* no data for:
        $taxon->taxonomicStatus          = '';
        $taxon->acceptedNameUsageID      = '';
        */
        if(isset($this->taxon_ids[$taxon->taxonID])) return;
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
        
        /* commented since it still triggers API calls that are not scalable
        //create trait
        if($json = Functions::lookup_with_cache($this->service['taxId'].$a['taxid'], $this->download_options)) {
            $rec = json_decode($json, true);
            $rec = @$rec[$taxid]; //needed
            if(@$rec['taxon']) {
                self::create_trait_archive($rec);
            }
        }
        */
    }
    private function create_media_archive_from_dump()
    {
        /* from dump:
        Array(
            [377871] => Array(
                    [0] => Array(
                            [processid] => CHONE194-11
                            [image_ids] => 1077290
                            [image_urls] => http://www.boldsystems.org/pics/CHONE/IMG_8623+1301084466.jpg
                            [media_descriptors] => Dorsal
                            [captions] => 
                            [copyright_holders] => CBG Photography Group
                            [copyright_years] => 2011
                            [copyright_licenses] => CreativeCommons - Attribution Non-Commercial Share-Alike
                            [copyright_institutions] => Centre for Biodiversity Genomics
                            [photographers] => Spencer Walker
                        )
                )
        )
        */
        /* ver.1
        echo "\ntotal taxon IDs with img: ".count($this->img_tax_ids)."\n";
        foreach($this->img_tax_ids as $taxonID => $block) {
            if(!@$block['images']) continue;
            foreach($block['images'] as $image) {
                // [image_ids] => 1077290  --- did not use
                // [image_urls] => http://www.boldsystems.org/pics/CHONE/IMG_8623+1301084466.jpg --- did not directly use
                
                //pattern the fields like that of the API results so we can only use one script for creating media archive
                $img = array();
                $img['copyright_institution']   = $image['copyright_institutions'];
                $img['copyright']               = '';
                $img['copyright_license']       = $image['copyright_licenses'];
                $img['copyright_holder']        = $image['copyright_holders'];
                $img['copyright_contact']       = '';
                $img['copyright_year']          = $image['copyright_years'];
                $img['image']                   = str_ireplace("http://www.boldsystems.org/pics/", "", $image['image_urls']);
                if(substr($img['image'],0,4) == "http") {
                    print_r($image);
                    exit("\nInvestigate: image URL\n");
                }
                $img['photographer']            = $image['photographers'];
                $img['meta']                    = $image['media_descriptors'].".";
                if($val = $image['captions']) $img['meta']." Caption: ".$val.".";
                $img['imagequality']            = '';
                self::write_image_record($img, $taxonID);
            }
        }
        */
        // /* ver.2
        $tax_ids = array_keys($this->img_tax_ids);
        echo "\ntotal taxon IDs with img: ".count($tax_ids)."\n";
        foreach($tax_ids as $taxonID) {
            $md5 = md5($taxonID);
            $cache1 = substr($md5, 0, 2);
            $cache2 = substr($md5, 2, 2);
            $file = $this->temp_path . "$cache1/$cache2/".$taxonID.".txt";
            
            foreach(new FileIterator($file) as $line_number => $line) {
                // echo "\n$line";
                $image = json_decode($line, true);
                // print_r($image); echo "\n-=-=-=-=-=-=\n";
                
                //below is exactly same as commented above...
                //pattern the fields like that of the API results so we can only use one script for creating media archive
                $img = array();
                $img['copyright_institution']   = $image['copyright_institutions'];
                $img['copyright']               = '';
                $img['copyright_license']       = $image['copyright_licenses'];
                $img['copyright_holder']        = $image['copyright_holders'];
                $img['copyright_contact']       = '';
                $img['copyright_year']          = $image['copyright_years'];
                $img['image']                   = str_ireplace("http://www.boldsystems.org/pics/", "", $image['image_urls']);
                if(substr($img['image'],0,4) == "http") {
                    print_r($image);
                    exit("\nInvestigate: image URL\n");
                }
                $img['photographer']            = $image['photographers'];
                $img['meta']                    = $image['media_descriptors'].".";
                if($val = $image['captions']) $img['meta']." Caption: ".$val.".";
                $img['imagequality']            = '';
                self::write_image_record($img, $taxonID);
            }
        }
        // */
    }
    private function add_needed_parent_entries($trials)
    {
        echo "\nstart add_needed_parent_entries...\n";
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        $url = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id."_working" . "/taxon_working.tab";
        $suggested_fields = explode("\t", "taxonID	scientificName	taxonRank	parentNameUsageID");
        if($undefined = $func->check_if_all_parents_have_entries($this->resource_id."", true, $url, $suggested_fields)) { //2nd param True means write to text file
            $arr['parents without entries during process'] = $undefined;
            echo "\ntrials:[$trials]"; print_r($arr);
            foreach($arr['parents without entries during process'] as $taxid) {
                if(self::process_record($taxid)) {}
                else {
                    if($taxon_info = self::get_info_from_page($taxid)) {
                        echo "\Scraped taxon info [$taxid] but may not get parentID here.\n";
                        self::create_taxon_archive($taxon_info);
                    }
                }
            }
        }
        else echo "\nAll parents have entries OK - during process\n";
    }
    public function get_info_from_page($taxid)
    {
        if($html = Functions::lookup_with_cache($this->page['sourceURL'].$taxid, $this->download_options)) {
            /*
            <h3>TAXONOMY BROWSER: Bryophyta</h3>
            <p>Phylum : Bryophyta</p>

            <h3>TAXONOMY BROWSER: Triviinae</h3>
            <p>Subfamily : Triviinae</p>
            */
            $info['taxid'] = $taxid;
            $info['tax_division'] = self::get_taxdiv_given_kingdom();
            $info['parentid'] = $this->tax_ids[$taxid]['p'];
            if(preg_match("/<h3>TAXONOMY BROWSER\:(.*?)<\/h3>/ims", $html, $a)) {
                $info['taxon'] = trim($a[1]);
            }
            if(preg_match("/<h3>TAXONOMY BROWSER\:(.*?)<\/p>/ims", $html, $a)) {
                $temp = trim($a[1]);
                if(preg_match("/<p>(.*?)\:/ims", $temp, $a)) {
                    $info['tax_rank'] = trim($a[1]);
                }
            }
            if($info['taxon'] && $info['tax_rank']) {
                echo "\nSalvaged by scraping: [$taxid]";
                return $info;
            }
            elseif(stripos($html, "This taxon cannot be located") !== false) { //string is found
                $this->debug['Page cannot be located'][$taxid] = '';
                echo "\nPage cannot be located: [$taxid]";
                return false;
            }
            else exit("\nInvestigate taxid [$taxid] cannnot scrape properly.\n");
        }
        return false;
    }
    private function get_images_from_dump_rec($rec, $sci)
    {
        // [image_ids] =>          [image_urls] => 
        // [media_descriptors] =>  [captions] => 
        // [copyright_holders] =>  [copyright_years] => 
        // [copyright_licenses] => [copyright_institutions] => 
        // [photographers] => 
        if($val = $rec['image_ids']) {
            $tmp = explode("|", $rec['image_ids']);
            $no_of_images = count($tmp);
            // echo "\n[$no_of_images] [$val]\n";

            $final = array();
            for ($x = 0; $x <= $no_of_images-1; $x++) {
                $fields = array('processid', 'image_ids', 'image_urls', 'media_descriptors', 'captions', 'copyright_holders', 'copyright_years', 'copyright_licenses', 'copyright_institutions', 'photographers');
                foreach($fields as $fld) {
                    $a = explode("|", $rec[$fld]);
                    if($fld == 'processid') $value = $rec['processid'];
                    else                    $value = $a[$x];
                    $final[$x][$fld] = $value;
                }
            }
            // echo "\n".$rec['processid']."\n";
            // print_r($final);

            /* ver.1
            if(!isset($this->img_tax_ids[$sci['taxid']]['images']))             $this->img_tax_ids[$sci['taxid']]['images'] = array();
                      $this->img_tax_ids[$sci['taxid']]['images'] = array_merge($this->img_tax_ids[$sci['taxid']]['images'], $final);
            $this->cnt += count($final);
            */
            // /* ver.2
            if($final) {
                $taxonID = $sci['taxid'];
                $md5 = md5($taxonID);
                $cache1 = substr($md5, 0, 2);
                $cache2 = substr($md5, 2, 2);
                if(!file_exists($this->temp_path . $cache1))           mkdir($this->temp_path . $cache1);
                if(!file_exists($this->temp_path . "$cache1/$cache2")) mkdir($this->temp_path . "$cache1/$cache2");
                $file = $this->temp_path . "$cache1/$cache2/".$taxonID.".txt";

                $WRITE = Functions::file_open($file, "a");
                foreach($final as $f)
                {
                    $json = json_encode($f);
                    /* didn't actually help
                    $json = str_ireplace(array("\n", "\t"), " ", $json);
                    $json = Functions::remove_utf8_bom($json);
                    */
                    $json = trim($json);
                    fwrite($WRITE, $json."\n");
                    $this->cnt++;
                }
                fclose($WRITE);
                $this->img_tax_ids[$taxonID] = '';
            }
            // */

        }
    }
    private function get_higher_level_ids($rec, $higher_level_ids)
    {
        if($taxName = $rec['phylum_name']) {
            $taxID = $rec['phylum_taxID'];
            $taxRank = 'phylum';
            $this->tax_ids[$taxID]['p'] = self::compute_parent_id($rec, $taxRank);
            $higher_level_ids[$taxID] = '';
        }
        if($taxName = $rec['class_name']) {
            $taxID = $rec['class_taxID'];
            $taxRank = 'class';
            $this->tax_ids[$taxID]['p'] = self::compute_parent_id($rec, $taxRank);
            $higher_level_ids[$taxID] = '';
        }
        if($taxName = $rec['order_name']) {
            $taxID = $rec['order_taxID'];
            $taxRank = 'order';
            $this->tax_ids[$taxID]['p'] = self::compute_parent_id($rec, $taxRank);
            $higher_level_ids[$taxID] = '';
        }
        if($taxName = $rec['family_name']) {
            $taxID = $rec['family_taxID'];
            $taxRank = 'family';
            $this->tax_ids[$taxID]['p'] = self::compute_parent_id($rec, $taxRank);
            $higher_level_ids[$taxID] = '';
        }
        if($taxName = $rec['subfamily_name']) {
            $taxID = $rec['subfamily_taxID'];
            $taxRank = 'subfamily';
            $this->tax_ids[$taxID]['p'] = self::compute_parent_id($rec, $taxRank);
            $higher_level_ids[$taxID] = '';
        }
        if($taxName = $rec['genus_name']) {
            $taxID = $rec['genus_taxID'];
            $taxRank = 'genus';
            $this->tax_ids[$taxID]['p'] = self::compute_parent_id($rec, $taxRank);
            $higher_level_ids[$taxID] = '';
        }
        return $higher_level_ids;
    }
    private function create_taxon_higher_level_archive($rec) //create taxon using API
    {}
    private function valid_rec($rec)
    {
        $taxName = false;
        if($taxName = $rec['subspecies_name']) {
            $taxID = $rec['subspecies_taxID'];
            $taxRank = 'subspecies';
            $taxParent = self::compute_parent_id($rec, $taxRank);
            $ancestry = self::compute_ancestry($rec, $taxRank);
        }
        elseif($taxName = $rec['species_name']) {
            $taxID = $rec['species_taxID'];
            $taxRank = 'species';
            $taxParent = self::compute_parent_id($rec, $taxRank);
            $ancestry = self::compute_ancestry($rec, $taxRank);
        }
        // /* uncomment to get more images from dump
        elseif($taxName = $rec['genus_name']) {
            $taxID = $rec['genus_taxID'];
            $taxRank = 'genus';
            $taxParent = self::compute_parent_id($rec, $taxRank);
            $ancestry = self::compute_ancestry($rec, $taxRank);
        }
        elseif($taxName = $rec['subfamily_name']) {
            $taxID = $rec['subfamily_taxID'];
            $taxRank = 'subfamily';
            $taxParent = self::compute_parent_id($rec, $taxRank);
            $ancestry = self::compute_ancestry($rec, $taxRank);
        }
        elseif($taxName = $rec['family_name']) {
            $taxID = $rec['family_taxID'];
            $taxRank = 'family';
            $taxParent = self::compute_parent_id($rec, $taxRank);
            $ancestry = self::compute_ancestry($rec, $taxRank);
        }
        elseif($taxName = $rec['order_name']) {
            $taxID = $rec['order_taxID'];
            $taxRank = 'order';
            $taxParent = self::compute_parent_id($rec, $taxRank);
            $ancestry = self::compute_ancestry($rec, $taxRank);
        }
        elseif($taxName = $rec['class_name']) {
            $taxID = $rec['class_taxID'];
            $taxRank = 'class';
            $taxParent = self::compute_parent_id($rec, $taxRank);
            $ancestry = self::compute_ancestry($rec, $taxRank);
        }
        elseif($taxName = $rec['phylum_name']) {
            $taxID = $rec['phylum_taxID'];
            $taxRank = 'phylum';
            $taxParent = self::compute_parent_id($rec, $taxRank);
            $ancestry = self::compute_ancestry($rec, $taxRank);
        }
        // */
        if($taxName) {
            return array('taxid' => $taxID, 'taxon' => $taxName, 'tax_rank' => $taxRank, 'ancestry' => $ancestry, 'parentid' => $taxParent, 'tax_division' => self::get_taxdiv_given_kingdom());
        }
        return false;
    }
    private function get_taxdiv_given_kingdom()
    {
        if($this->current_kingdom == "Animalia") return "Animals";
        if($this->current_kingdom == "Plantae") return "Plants";
        if($this->current_kingdom == "Fungi") return "Fungi";
        if($this->current_kingdom == "Protista") return "Protists";
    }
    private function compute_ancestry($rec, $taxRank)
    {
        $kingdom = self::get_kingdom_given_phylum($rec['phylum_name']);
        if    ($taxRank == "subspecies") $ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
        elseif($taxRank == "species")    $ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
        elseif($taxRank == "genus")      $ranks = array("kingdom", "phylum", "class", "order", "family");
        elseif($taxRank == "subfamily")  $ranks = array("kingdom", "phylum", "class", "order", "family");
        elseif($taxRank == "family")     $ranks = array("kingdom", "phylum", "class", "order");
        elseif($taxRank == "order")      $ranks = array("kingdom", "phylum", "class");
        elseif($taxRank == "class")      $ranks = array("kingdom", "phylum");
        elseif($taxRank == "phylum")     $ranks = array("kingdom");
        $final = array();
        foreach($ranks as $rank) {
            if($rank == "kingdom") $final[$rank] = $kingdom;
            else                   $final[$rank] = $rec[$rank."_name"];
        }
        return $final;
    }
    private function compute_parent_id($rec, $taxRank)
    {  
        /* 
        [phylum_taxID] => 2         [phylum_name] => Annelida
        [class_taxID] => 95135      [class_name] => Clitellata
        [order_taxID] => 25446      [order_name] => Rhynchobdellida
        [family_taxID] =>           [family_name] => 
        [subfamily_taxID] =>        [subfamily_name] => 
        [genus_taxID] =>            [genus_name] => 
        [species_taxID] =>          [species_name] => 
        [subspecies_taxID] =>       [subspecies_name] => 
        */
        if($taxRank == "phylum") return "1"; //return "1_".self::get_taxdiv_given_kingdom();
        else {
            $index['subspecies'] = 0;
            $index['species'] = 1;
            $index['genus'] = 2;
            $index['subfamily'] = 3;
            $index['family'] = 4;
            $index['order'] = 5;
            $index['class'] = 6;
            $ranks = array("subspecies", "species", "genus", "subfamily", "family", "order", "class", "phylum");
            foreach($ranks as $key => $rank) {
                if($key > $index[$taxRank]) {
                    if($val = $rec[$rank."_taxID"]) return $val;
                }
            }
        }
        //last resort:
        return "1"; //"1_".self::get_taxdiv_given_kingdom();
    }
    private function get_kingdom_given_phylum($phylum)
    {
        if(in_array($phylum, $this->kingdom['Animalia'])) return "Animalia";
        if(in_array($phylum, $this->kingdom['Plantae'])) return "Plantae";
        if(in_array($phylum, $this->kingdom['Fungi'])) return "Fungi";
        if(in_array($phylum, $this->kingdom['Protista'])) return "Protista";
        exit("\nUndefined phylum [$phylum]\n");
    }
    private function download_and_extract_remote_file($file = false, $use_cache = false)
    {
        if(!$file) $file = $this->data_dump_url; // used when this function is called elsewhere
        $download_options = $this->download_options;
        $download_options['timeout'] = 172800;
        $download_options['file_extension'] = 'txt.zip';
        if($use_cache) $download_options['cache'] = 1; // this pertains to the generation of higher-level-taxa list
        // $download_options['cache'] = 0; // 0 only when developing //debug - comment in real operation
        $temp_path = Functions::save_remote_file_to_local($file, $download_options);
        echo "\nunzipping this file [$temp_path]... \n";
        shell_exec("unzip " . $temp_path . " -d " . DOC_ROOT."tmp/"); //worked OK
        unlink($temp_path);
        if(is_dir(DOC_ROOT."tmp/"."__MACOSX")) recursive_rmdir(DOC_ROOT."tmp/"."__MACOSX");
    }
    //==================================================================================================================
    function start_using_api()
    {
        $taxon_ids = self::get_all_taxon_ids();
        // $this->archive_builder->finalize(true);
        print_r($this->debug);
    }
    private function get_all_taxon_ids()
    {
        $phylums = self::get_all_phylums();
        $phylums = array_keys($phylums);

        // $phylums = array('Pyrrophycophyta', 'Heterokontophyta'); done
        // $phylums = array('Onychophora', 'Platyhelminthes', 'Porifera', 'Priapulida', 'Rotifera', 'Sipuncula'); done
        // $phylums = array('Basidiomycota', 'Chytridiomycota', 'Glomeromycota', 'Myxomycota', 'Zygomycota', 'Chlorarachniophyta', 'Ciliophora'); done
        // $phylums = array('Brachiopoda', 'Bryozoa', 'Chaetognatha', 'Cnidaria', 'Cycliophora', '', 'Gnathostomulida', 'Hemichordata', 'Nematoda', 'Nemertea'); done
        // $phylums = array('Annelida', 'Acanthocephala'); //done
        // $phylums = array('Ascomycota'); done
        // $phylums = array('Tardigrada', 'Xenoturbellida', 'Bryophyta', 'Chlorophyta', 'Lycopodiophyta', 'Pinophyta', 'Pteridophyta', 'Rhodophyta'); done
        // $phylums = array('Echinodermata'); done
        // $phylums = array('Mollusca'); done

        $phylums = array('Pyrrophycophyta', 'Heterokontophyta', 'Onychophora', 'Platyhelminthes', 'Porifera', 'Priapulida', 'Rotifera', 'Sipuncula', 'Basidiomycota', 'Chytridiomycota', 
        'Glomeromycota', 'Myxomycota', 'Zygomycota', 'Chlorarachniophyta', 'Ciliophora', 'Brachiopoda', 'Bryozoa', 'Chaetognatha', 'Cnidaria', 'Cycliophora', 'Gnathostomulida', 
        'Hemichordata', 'Nematoda', 'Nemertea', 'Annelida', 'Acanthocephala', 'Ascomycota', 'Tardigrada', 'Xenoturbellida', 'Bryophyta', 'Chlorophyta', 'Lycopodiophyta', 'Pinophyta', 
        'Pteridophyta', 'Rhodophyta', 'Echinodermata', 'Mollusca');
        
        //------------------------- the 3 big ones:
        // $phylums = array('Arthropoda');
        // $phylums = array('Magnoliophyta');
        // $phylums = array('Chordata');
        // $phylums = array('Annelida');

        $download_options = $this->download_options;
        $download_options['expire_seconds'] = false;
        
        foreach($phylums as $phylum) {
            echo "\n$phylum ";
            $final = array();
            $temp_file = Functions::save_remote_file_to_local($this->service['phylum'].$phylum, $download_options);
            $reader = new \XMLReader();
            $reader->open($temp_file);
            while(@$reader->read()) {
                if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "record") {
                    $string = $reader->readOuterXML();
                    if($xml = simplexml_load_string($string)) {
                        // print_r($xml);
                        $ranks = array('phylum', 'class', 'order', 'family', 'genus', 'species');
                        $ranks = array('phylum', 'class', 'order', 'family', 'genus');
                        foreach($ranks as $rank) {
                            // echo "\n - $phylum ".@$xml->taxonomy->$rank->taxon->taxid."\n";
                            if($taxid = (string) @$xml->taxonomy->$rank->taxon->taxid) {
                                $final[$taxid] = '';
                            }
                        }
                    }
                }
            }
            unlink($temp_file);
            self::process_ids_for_this_phylum(array_keys($final), $phylum);
            // break; //debug
        }
    }
    private function process_ids_for_this_phylum($taxids, $phylum)
    {
        // print_r($taxids); exit;
        $total = number_format(count($taxids)); $i = 0;
        foreach($taxids as $taxid) {
            $i++;
            if(($i % 1000) == 0) echo "\n".number_format($i)." of $total - $phylum ";
            self::process_record($taxid);
            // if($i >= 20) break; //debug
        }
    }
    private function process_record($taxid)
    {
        /*Array (   [taxid] => 23
                    [taxon] => Mollusca
                    [tax_rank] => phylum
                    [tax_division] => Animals
                    [parentid] => 1
                    [taxonrep] => Mollusca
        [sitemap] => http://www.boldsystems.org/index.php/TaxBrowser_Maps_CollectionSites?taxid=2
        */
        if($json = Functions::lookup_with_cache($this->service['taxId'].$taxid, $this->download_options)) {
            $a = json_decode($json, true);
            // print_r($a); echo "\n[$taxid]\n"; //exit;
            $a = @$a[$taxid]; //needed
            if(@$a['taxon']) {
                self::create_taxon_archive($a);
                self::create_media_archive($a);
                self::create_trait_archive($a);
            }
            return true;
        }
        else return false;
        // exit("\n");
    }
    private function create_media_archive($a)
    {   /* [images] => Array(
                       [0] => Array(
                               [copyright_institution] => Centre for Biodiversity Genomics
                               [copyright] => 
                               [copyright_license] => CreativeCommons - Attribution Non-Commercial Share-Alike
                               [copyright_holder] => CBG Photography Group
                               [copyright_contact] => ccdbcol@uoguelph.ca
                               [copyright_year] => 2008
                               
                               [specimenid] => 968120
                               [imagequality] => 5
                               [photographer] => Nick Jeffery
                               [image] => ANCN/IMG_6772+1228833566.JPG
                               [fieldnum] => L#08PUK-055
                               [sampleid] => 08BBANN-009
                               [mam_uri] => bold.org/323285
                               [meta] => Lateral
                               [catalognum] => 08BBANN-009
                               [taxonrep] => Clitellata
                               [aspectratio] => 1.499
                               [original] => 1
                               [external] => 
                           )
        */
        
        // /* un-comment in real operation
        if($images = @$a['images']) {
            // print_r($images);
            foreach($images as $img) {
                if($img['image']) {
                    self::write_image_record($img, $a['taxid']);
                }
            }
        }
        // */
        
        /* working OK but maps are excluded now
        //[sitemap] => http://www.boldsystems.org/index.php/TaxBrowser_Maps_CollectionSites?taxid=2
        if($map_url = $a['sitemap']) {
            $mr = new \eol_schema\MediaResource();
            $mr->taxonID                = $a['taxid'];
            $mr->identifier             = "map_".$a['taxid'];
            $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
            $mr->format                 = 'image/png';
            $mr->furtherInformationURL  = $this->page['sourceURL'].$a['taxid'];
            $mr->description            = '';
            $mr->UsageTerms             = 'http://creativecommons.org/licenses/by-nc-sa/3.0/';
            $mr->Owner                  = '';
            $mr->rights                 = '';
            $mr->accessURI              = $map_url;
            $mr->subtype                = 'Map';
            $mr->Rating                 = '';
            if(!isset($this->object_ids[$mr->identifier])) {
                $this->archive_builder->write_object_to_file($mr);
                $this->object_ids[$mr->identifier] = '';
            }
        }
        */
        
    }
    private function write_image_record($img, $taxid)
    {
        $mr = new \eol_schema\MediaResource();
        // if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
        if($agent_ids = self::format_agents($img)) $mr->agentID = implode("; ", $agent_ids);
        $mr->taxonID                = $taxid;
        $mr->identifier             = $img['image'];
        $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
        // $mr->language               = 'en';
        $mr->format                 = Functions::get_mimetype($img['image']);
        $mr->furtherInformationURL  = $this->page['sourceURL'].$taxid;
        $mr->description            = self::format_description($img);
        $mr->UsageTerms             = self::format_license($img['copyright_license']);
        if(!$mr->UsageTerms) return; //invalid license
        $mr->Owner                  = self::format_rightsHolder($img);
        $mr->rights                 = '';
        $mr->accessURI              = "http://www.boldsystems.org/pics/".$img['image'];
        $mr->Rating                 = $img['imagequality']; //will need to check what values they have here...
        if(!isset($this->object_ids[$mr->identifier])) {
            $this->archive_builder->write_object_to_file($mr);
            $this->object_ids[$mr->identifier] = '';
        }
    }
    private function create_taxon_archive($a)
    {   /*                      
        [taxid] => 23
        [taxon] => Mollusca
        [tax_rank] => phylum
        [tax_division] => Animals
        [parentid] => 1
        */
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID             = $a['taxid'];
        $taxon->scientificName      = $a['taxon'];
        $taxon->taxonRank           = $a['tax_rank'];
        $taxon->parentNameUsageID   = $a['parentid'];
        if($taxon->parentNameUsageID == 1) {
            $taxon->parentNameUsageID .= "_".$a['tax_division'];
        }


        /* no data for:
        $taxon->taxonomicStatus          = '';
        $taxon->acceptedNameUsageID      = '';
        */
        if(isset($this->taxon_ids[$taxon->taxonID])) return;
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
        
    }
    private function create_kingdom_taxa()
    {
        /* create these 4 taxon entries
            animals (Animalia),                         1_Animalia
            plants (Plantae),                           1_Plantae
            fungi (Fungi),                              1_Fungi
            protozoa and eucaryotic algae (Protista)    1_Protista
        */
        $add['1_Animals'] = 'Animalia';
        $add['1_Plants'] = 'Plantae';
        $add['1_Fungi'] = 'Fungi';
        $add['1_Protists'] = 'Protista';
        foreach($add as $taxid => $sciname) {
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID             = $taxid;
            $taxon->scientificName      = $sciname;
            $taxon->taxonRank           = 'kingdom';
            if(isset($this->taxon_ids[$taxon->taxonID])) continue;
            $this->taxon_ids[$taxon->taxonID] = '';
            $this->archive_builder->write_object_to_file($taxon);
        }
    }
    private function get_all_phylums()
    {
        if($html = Functions::lookup_with_cache($this->page['home'], $this->download_options)) {
            /* <li><a class="link" href="/index.php/Taxbrowser_Taxonpage?taxid=11">Acanthocephala [747]</a></li> */
            if(preg_match_all("/Taxbrowser_Taxonpage\?taxid\=(.*?) \[/ims", $html, $a)) {
                foreach($a[1] as $tmp) {
                    $tmp = explode('">', $tmp);
                    $final[$tmp[1]] = $tmp[0];
                }
            }
        }
        // print_r(array_keys($final)); exit;
        return $final;
    }
    private function format_description($img)
    {
        /*
        [specimenid] => 968120
        [imagequality] => 5
        [photographer] => Nick Jeffery
        [image] => ANCN/IMG_6772+1228833566.JPG
        [fieldnum] => L#08PUK-055
        [sampleid] => 08BBANN-009
        [mam_uri] => bold.org/323285
        [meta] => Lateral
        [catalognum] => 08BBANN-009
        [taxonrep] => Clitellata
        [aspectratio] => 1.499
        [original] => 1
        [external] => 
        */
        $final = "";
        if($val = @$img['meta'])            $final .= "$val. ";
        if($val = @$img['catalognum'])      $final .= "Catalog no.: $val. ";
        if($val = @$img['specimenid'])      $final .= "Specimen ID: $val. ";
        if($val = @$img['fieldnum'])        $final .= "Field no.: $val. ";
        if($val = @$img['taxonrep'])        $final .= "Taxon rep.: $val. ";
        if($val = @$img['imagequality'])    $final .= "Image quality: $val. ";
        if($val = @$img['aspectratio'])     $final .= "Aspect ratio: $val. ";
        return trim($final);
    }
    private function format_rightsHolder($img)
    {
        /*
        [copyright_institution] => Centre for Biodiversity Genomics
        [copyright] => 
        [copyright_license] => CreativeCommons - Attribution Non-Commercial Share-Alike
        [copyright_holder] => CBG Photography Group
        [copyright_contact] => ccdbcol@uoguelph.ca
        [copyright_year] => 2008
        */
        $final = "";
        if($val = @$img['copyright']) $final .= "$val. ";
        if($val = @$img['copyright_institution']) $final .= "$val. ";
        if($val = @$img['copyright_holder']) $final .= "$val. ";
        if($val = @$img['copyright_year']) $final .= "Year: $val. ";
        if($val = @$img['copyright_contact']) $final .= "Contact: $val. ";
        return trim($final);
    }
    private function format_license($license)
    {
        /*
        [undefined license] => Array
                (
                    [creativecommons â attribution (max weber & ron eytan)] => 
                    [creativecommons â attribution] => 
                    [creativecommons  attribution (bruce a. bennett)] => 
                    [creativecommons  attribution] => 
                    [creativecommons â attribution (bruce a. bennett)] => 
                )
        */
        
        $license = strtolower(trim($license));
        $license = utf8_encode($license);
        $license = str_ireplace(array("â", ""), "", $license);
        $license = Functions::remove_whitespace($license);
        
        if(stripos($license, "no derivatives") !== false)   return false; //string is found
        if(stripos($license, "by-nc-nd") !== false)         return false; //string is found
        
        if(stripos($license, "attribution share-alike") !== false)      return "http://creativecommons.org/licenses/by-sa/3.0/"; //string is found
        if(stripos($license, "(by-nc)") !== false)                      return "http://creativecommons.org/licenses/by-nc/3.0/"; //string is found
        if(stripos($license, "non-commercial share-alike") !== false)   return "http://creativecommons.org/licenses/by-nc-sa/3.0/"; //string is found
        if(stripos($license, "noncommercial sharealike") !== false)     return "http://creativecommons.org/licenses/by-nc-sa/3.0/"; //string is found
        if(stripos($license, "noncommercial share alike") !== false)    return "http://creativecommons.org/licenses/by-nc-sa/3.0/"; //string is found
        if(stripos($license, "attribution (by)") !== false)             return "http://creativecommons.org/licenses/by/3.0/"; //string is found
        if(stripos($license, "commons - attribution by ") !== false)    return "http://creativecommons.org/licenses/by/3.0/"; //string is found
        if(stripos($license, "commons attribution (") !== false)        return "http://creativecommons.org/licenses/by/3.0/"; //string is found
        if(stripos($license, "non-commercial only") !== false)          return "http://creativecommons.org/licenses/by-nc/3.0/"; //string is found
        if(stripos($license, " attribution-noncommercial ") !== false)  return "http://creativecommons.org/licenses/by-nc/3.0/"; //string is found
        
        $arr["creativecommons - attribution non-commercial share-alike"] = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons - attribution"]                            = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creativecommons - attribution non-commercial"]             = "http://creativecommons.org/licenses/by-nc/3.0/";
        $arr["creativecommons - attribution share-alike"]                = "http://creativecommons.org/licenses/by-sa/3.0/";
        $arr["creative commons by nc sa"]                                = "http://creativecommons.org/licenses/by-nc-sa/3.0/";

        $arr["creative commons-by-nc-sa"]                                = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons-attribution non-commervial share-alike"]   = "http://creativecommons.org/licenses/by-nc-sa/3.0/";

        $arr["no rights reserved"]                                       = "No known copyright restrictions";
        $arr["no rights reserved (nrr)"]                                 = "No known copyright restrictions";
        $arr["no right's reserved (nrr)"]                                = "No known copyright restrictions";

        $arr["creativecommons"]                                          = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creative commons"]                                         = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creativecom"]                                              = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creativecommons (by)"]                                     = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creative commons attribution 2.0 generic"]                 = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creativecommons attribution"]                              = "http://creativecommons.org/licenses/by/3.0/";
        $arr["creativecommons  attribution noncommercial share alike"]   = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons attribution non-commercial share-alike"]   = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons (by-nc-sa)"]  = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creativecommons-by-nc-sa"]    = "http://creativecommons.org/licenses/by-nc-sa/3.0/";
        $arr["creative commoms-by-nc-sa"]   = "http://creativecommons.org/licenses/by-nc-sa/3.0/";

        if($val = @$arr[$license]) return $val;
        else {
            // exit("\nInvalid license [$license]\n");
            $this->debug['undefined license'][$license] = '';
        }
    }
    private function format_agents($img)
    {   // [photographer] => Nick Jeffery
        $agent_ids = array();
        if($agent = trim(@$img['photographer'])) {
            $r = new \eol_schema\Agent();
            $r->term_name = $agent;
            $r->identifier = md5("$agent|photographer");
            $r->agentRole = "photographer";
            // $r->term_homepage = "";
            $agent_ids[] = $r->identifier;
            if(!in_array($r->identifier, $this->resource_agent_ids)) {
               $this->resource_agent_ids[] = $r->identifier;
               $this->archive_builder->write_object_to_file($r);
            }
        }
        return $agent_ids;
    }
    private function create_trait_archive($a)
    {
        /*             [stats] => Array(
                            [publicspecies] => 11115
                            [publicbins] => 15448
                            [publicmarkersequences] => Array(
                                    [COI-3P] => 338
                                    [COI-5P] => 113573
                                    [28S-D9-D10] => 2
                                    [CYTB] => 287
                                    [atp6] => 28
                                    [12S] => 534
                                )
                            [publicrecords] => 117712
                            [publicsubspecies] => 320
                            [specimenrecords] => 159082
                            [sequencedspecimens] => 143785
                            [barcodespecimens] => 124800
                            [species] => 14891
                            [barcodespecies] => 13237
                        )
        
        *only non-family ranks will have TraitData:
        publicrecords
            http://eol.org/schema/terms/NumberPublicRecordsInBOLD (numeric)
        specimenrecords:
            http://eol.org/schema/terms/NumberRecordsInBOLD (numeric)
            http://eol.org/schema/terms/RecordInBOLD (Yes/No)
        */
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
        /* working OK but excluded per Jen: https://eol-jira.bibalex.org/browse/TRAM-737?focusedCommentId=62468&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-62468
        if($specimenrecords = @$a['stats']['specimenrecords']) {
            $rec = array();
            $rec["taxon_id"]            = $a['taxid'];
            $rec["catnum"]              = self::generate_id_from_array_record($a);
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/NumberRecordsInBOLD";
            $rec['measurementValue']    = $specimenrecords;
            $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
            self::add_string_types($rec);
            if($specimenrecords > 0) {
                $rec = array();
                $rec["taxon_id"]            = $a['taxid'];
                $rec["catnum"]              = self::generate_id_from_array_record($a);
                $rec['measurementOfTaxon']  = "true";
                $rec['measurementType']     = "http://eol.org/schema/terms/RecordInBOLD";
                $rec['measurementValue']    = 'http://eol.org/schema/terms/yes';
                $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
                self::add_string_types($rec);
            }
        }
        else {
            $rec = array();
            $rec["taxon_id"]            = $a['taxid'];
            $rec["catnum"]              = self::generate_id_from_array_record($a);
            $rec['measurementOfTaxon']  = "true";
            $rec['measurementType']     = "http://eol.org/schema/terms/RecordInBOLD";
            $rec['measurementValue']    = 'http://eol.org/schema/terms/no';
            $rec["source"]              = $this->page['sourceURL'].$a['taxid'];
            self::add_string_types($rec);
        }
        */
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
    private function generate_id_from_array_record($arr)
    {
        $json = json_encode($arr);
        return md5($json);
    }
    private function start_print_debug()
    {
        $defined_uris = Functions::get_eol_defined_uris(false, true);
        $file = CONTENT_RESOURCE_LOCAL_PATH . "bolds_debug.txt";
        $WRITE = Functions::file_open($file, "w");
        foreach($this->debug as $topic => $arr) {
            fwrite($WRITE, "============================================================="."\n");
            fwrite($WRITE, $topic."\n");
            if(is_array($arr)) {
                foreach($arr as $subtopic => $arr2) {
                    fwrite($WRITE, "----- ".$subtopic." ----- \n");
                    if(is_array($arr2)) {
                        $arr2 = array_keys($arr2);
                        asort($arr2);
                        foreach($arr2 as $item) {
                            if($item) {
                                if(!isset($defined_uris[$item])) fwrite($WRITE, $item."\n");
                                // else echo "\ndefined trait already";
                            }
                        }
                    }
                    else fwrite($WRITE, $arr2."\n");
                }
            }
            else fwrite($WRITE, $arr."\n");
        }
        fclose($WRITE);
        print_r($this->debug);
    }
}
?>