<?php
namespace php_active_record;
/* connector: [dwh_ncbi_TRAM_795.php] - https://eol-jira.bibalex.org/browse/TRAM-795
              [dwh_ncbi_TRAM_796.php] - https://eol-jira.bibalex.org/browse/TRAM-796

NCBI_Taxonomy_Harvest	    Monday 2018-08-06 02:20:41 AM	{"reference.tab":47083,"taxon.tab":1465751,"vernacular_name.tab":42670}
NCBI_Taxonomy_Harvest_DH	Monday 2018-08-06 04:47:09 AM	{"reference.tab":23592,"taxon.tab":178807,"vernacular_name.tab":167}
NCBI_Taxonomy_Harvest_DH	Wednesday 2018-11-07 11:15:14 PM{"reference.tab":23590,"taxon.tab":177813,"vernacular_name.tab":165}
===========================================================
TRAM-981:
NCBI_Taxonomy_Harvest_no_vernaculars    {"reference.tab":50252,"taxon.tab":1035461}
NCBI_Taxonomy_Harvest                   {"reference.tab":50252,"taxon.tab":1035461,"vernacular_name.tab":11382}
NCBI_Taxonomy_Harvest_DH                {"reference.tab":23215,"taxon.tab":72567}

Hi Katja,
I have here a new batch, I guess with better coverage:
NCBI_Taxonomy_Harvest_no_vernaculars    {"reference.tab":50252,"taxon.tab":1477478}
NCBI_Taxonomy_Harvest                   {"reference.tab":50252,"taxon.tab":1477478,"vernacular_name.tab":43741}
NCBI_Taxonomy_Harvest_DH                {"reference.tab":23215,"taxon.tab":109805}
https://editors.eol.org/eol_php_code/applications/content_server/resources/Feb5/NCBI_Taxonomy_Harvest_no_vernaculars.tar.gz
https://editors.eol.org/eol_php_code/applications/content_server/resources/Feb5/NCBI_Taxonomy_Harvest.tar.gz
https://editors.eol.org/eol_php_code/applications/content_server/resources/Feb5/NCBI_Taxonomy_Harvest_DH.tar.gz
Thanks,
Eli

TRAM-989 eol-archive (same result in Mac Mini)
NCBI_Taxonomy_Harvest_no_vernaculars	Mon 2020-09-21 06:44:36 AM	{"reference.tab":50881, "taxon.tab":1635339, "time_elapsed":{"sec":1097.65, "min":18.29, "hr":0.3}}
NCBI_Taxonomy_Harvest	                Mon 2020-09-21 07:03:24 AM	{"reference.tab":50881, "taxon.tab":1635339, "vernacular_name.tab":44046, "time_elapsed":{"sec":1078.24, "min":17.97, "hr":0.3}}
NCBI_Taxonomy_Harvest_DH	            Mon 2020-09-21 10:39:33 AM	{"reference.tab":24828, "taxon.tab":172951, "time_elapsed":{"sec":203.53, "min":3.39, "hr":0.06}}

=========================================================== run one after the other:
php update_resources/connectors/dwh_ncbi_TRAM_795.php _ '{"with_Vernaculars": 1}'
php update_resources/connectors/dwh_ncbi_TRAM_795.php _ '{"with_Vernaculars": 0}'
php update_resources/connectors/dwh_ncbi_TRAM_796.php
===========================================================
History:
https://eol-jira.bibalex.org/browse/TRAM-795 NCBI Taxonomy Harvest
https://eol-jira.bibalex.org/browse/TRAM-796 NCBI Taxonomy Extract for Dynamic Hierarchy
https://eol-jira.bibalex.org/browse/TRAM-981 Revise connector for NCBI Taxonomy Harvest to filter out more surrogates
https://eol-jira.bibalex.org/browse/TRAM-989 NCBI Extract for DH2
===========================================================

Dump comes from this ftp site: based on TRAM-795
ftp://ftp.ncbi.nlm.nih.gov/pub/taxonomy/        (for desktop access)
https://ftp.ncbi.nlm.nih.gov/pub/taxonomy/      (preferably for connector use)
*Right now there is no auto-download of this dump. Both local and eol-archive will manually use 'wget' to download the dump
*For eol-archive, follow /extra/other_files/DWH/TRAM-795/readmeli.txt for instructions...
*For local, download dump here: /Volumes/AKiTiO4/d_w_h/TRAM-795/ ... then rename to desired month.
*/
class DWH_NCBI_API
{
    function __construct($folder, $with_comnames = false)
    {
        $this->resource_id = $folder;
        $this->with_comnames = $with_comnames;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->download_options = array('resource_id' => $folder, 'download_wait_time' => 1000000, 'timeout' => 60*2, 'download_attempts' => 1, 'cache' => 1); // 'expire_seconds' => 0
        $this->debug = array();
        if(Functions::is_production()) $dump_path = '/extra/other_files/DWH/TRAM-795/'; //read readmeli.txt in eol-archive for more info...
        else                           $dump_path = '/Volumes/AKiTiO4/d_w_h/TRAM-795/';
        $date_folder = 'taxdump_2020_02_03';
        $date_folder = 'taxdump_2020_09_20';
        $date_folder = 'taxdump_2022_03_16'; //latest
        $this->file['names.dmp']['path']     = $dump_path.$date_folder."/names.dmp";
        $this->file['nodes.dmp']['path']     = $dump_path.$date_folder."/nodes.dmp";
        $this->file['citations.dmp']['path'] = $dump_path.$date_folder."/citations.dmp";
        $this->file['names.dmp']['fields'] = array("tax_id", "name_txt", "unique_name", "name_class");
        $this->file['nodes.dmp']['fields'] = array("tax_id", "parent_tax_id", "rank", "embl_code", "division_id", "inherited div flag", "genetic code id", "inherited GC flag", "mitochondrial genetic code id", "inherited MGC flag", "GenBank hidden flag", "hidden subtree root flag", "comments");
        $this->file['citations.dmp']['fields'] = array("cit_id", "cit_key", "pubmed_id", "medline_id", "url", "text", "taxid_list");
        $this->alternative_names = array("synonym", "equivalent name", "in-part", "misspelling", "genbank synonym", "misnomer", "anamorph", "genbank anamorph", "teleomorph", "authority");
        //start TRAM-796 -----------------------------------------------------------
        $this->prune_further = array(10239, 12884, 3193, 4751, 33208, 29178);
        /*It looks like they moved viroids (12884) into viruses, so we don't need to remove those separately -> TRAM-989*/
        if($this->with_comnames) $this->extension_path = CONTENT_RESOURCE_LOCAL_PATH . "NCBI_Taxonomy_Harvest/";                //this folder is from TRAM-795
        else                     $this->extension_path = CONTENT_RESOURCE_LOCAL_PATH . "NCBI_Taxonomy_Harvest_no_vernaculars/"; //this folder is from TRAM-795
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
        //start TRAM-981 -----------------------------------------------------------
        $this->Viruses_Taxonomy_ID = 10239;
        $this->local_debug = false; //primarily during development set to true. Otherwise set to false.
    }
    // ----------------------------------------------------------------- start TRAM-796 -----------------------------------------------------------------
    private function get_meta_info($row_type = false)
    {
        require_library('connectors/DHSourceHierarchiesAPI'); $func = new DHSourceHierarchiesAPI();
        $meta = $func->analyze_eol_meta_xml($this->extension_path."meta.xml", $row_type); //2nd param $row_type is rowType in meta.xml
        print_r($meta);
        return $meta;
    }
    function start_tram_796()
    {
        /* test
        $arr = array("endophyte SE2/SE4", "Phytophthora citricola IV", "Hyperolius nasutus A JK-2009",  "Cryptococcus neoformans AD hybrid", "Gadus morhua");
        $arr = array("Vibrio phage 2.096.O._10N.286.48.B5");
        foreach($arr as $sci) {
            if(self::with_consecutive_capital_letters($sci)) echo "\nYES ($sci)\n";
            else echo "\nNO ($sci)\n";
        }
        exit;
        */
        self::main_tram_796(); //exit("\nstop muna\n");
        self::browse_citations($this->reference_ids_2write); //no need for return value here
        /* vernaculars removed due to harvesting issue with weird chars.
        self::write_vernaculars_DH();
        */
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
    }
    private function write_vernaculars_DH()
    {
        $tax_ids = self::get_tax_ids_from_taxon_tab_working();
        //now start looping vernacular_name.tab from TRAMS-795 and write vernacular where taxonID is found in $tax_ids
        $meta = self::get_meta_info("http://rs.gbif.org/terms/1.0/VernacularName");
        $i = 0;
        foreach(new FileIterator($this->extension_path.$meta['file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 100000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            $row = Functions::conv_to_utf8($row);
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            /* $rec e.g. = Array(
                [vernacularName] => eubacteria
                [language] => en
                [taxonID] => 2
            )*/
            if(isset($tax_ids[$rec['taxonID']])) {
                if($common_name = trim(@$rec['vernacularName'])) {
                    $v = new \eol_schema\VernacularName();
                    $v->taxonID = $rec["taxonID"];
                    $v->vernacularName = $common_name;
                    $v->language = $rec['language'];
                    $vernacular_id = md5("$v->taxonID|$v->vernacularName|$v->language");
                    if(!isset($this->vernacular_ids[$vernacular_id])) {
                        $this->vernacular_ids[$vernacular_id] = '';
                        $this->archive_builder->write_object_to_file($v);
                    }
                }
            }
        }
    }
    private function get_tax_ids_from_taxon_tab_working()
    {
        echo "\n get taxonIDs from taxon_working.tab\n";
        require_library('connectors/DWCADiagnoseAPI');
        $func = new DWCADiagnoseAPI();
        $url = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id."_working" . "/taxon_working.tab";
        $suggested_fields = explode("\t", "taxonID	furtherInformationURL	referenceID	acceptedNameUsageID	parentNameUsageID	scientificName	taxonRank	taxonomicStatus"); //taxonID is what is important here.
        $var = $func->get_fields_from_tab_file($this->resource_id, array("taxonID"), $url, $suggested_fields, false); //since there is $url, the last/5th param is no longer needed, set to false.
        return $var['taxonID'];
    }
    private function main_tram_796() //pruning further
    {
        $taxID_info = self::get_taxID_nodes_info();
        $removed_branches = array(); $i = 0;
        foreach($this->prune_further as $id) $removed_branches[$id] = '';
        
        // /* IMPORTANT: for every taxdump refresh, comment this block, then run, then fill-up more_ids_to_remove_TRAM_796() as needed. Then un-comment this block and run again to finalize.
        $add = self::more_ids_to_remove_TRAM_796();
        foreach($add as $id) $removed_branches[$id] = '';
        // */
        
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...tram_796\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 300000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            $row = Functions::conv_to_utf8($row);
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            //start filter
            /*Array(
                [taxonID] => 1_1
                [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                [acceptedNameUsageID] => 1
                [parentNameUsageID] => 
                [scientificName] => all
                [taxonRank] => no rank
                [taxonomicStatus] => synonym
                [referenceID] => 
            )*/
            /* 1. Remove ALL taxa (and their children, grandchildren, etc.) that have one of the following strings in their scientific name: uncultured|unidentified */
            // /*
            if($rec['taxonomicStatus'] == "accepted") {
                if(stripos($rec['scientificName'], "uncultured") !== false)   {$filtered_ids[$rec['taxonID']] = ''; continue;} //string is found
                if(stripos($rec['scientificName'], "unidentified") !== false) {$filtered_ids[$rec['taxonID']] = ''; continue;} //string is found
            }
            // */
            /* 2. ONLY for taxa where division_id in nodes.dmp IS 0 (bacteria & archaea), we want to remove all taxa that have the string “unclassified” in their scientific name. */
            // /*
            if($rec['taxonomicStatus'] == "accepted") {
                if(in_array($taxID_info[$rec['taxonID']]['dID'], array(0))) {
                    if(stripos($rec['scientificName'], "unclassified") !== false) {$filtered_ids[$rec['taxonID']] = ''; 
                        // print_r($rec); print_r($taxID_info[$rec['taxonID']]); exit("\nrule 2\n"); //good debug
                        continue;} //string is found
                }
            }
            // */
            /* 3. ONLY for taxa where division_id in nodes.dmp IS NOT 0 (things that are not bacteria or archaea), we want to remove all taxa of RANK species that have consecutive 
            capital letters not separated by a white space in their scientific name, e.g., things like “endophyte SE2/SE4” or “Phytophthora citricola IV” or “Hyperolius nasutus A JK-2009” or 
            “Cryptococcus neoformans AD hybrid” */
            if($rec['taxonomicStatus'] == "accepted") {
                if($taxID_info[$rec['taxonID']]['dID'] != 0) {
                    $rank = $taxID_info[$rec['taxonID']]['r'];
                    if($rank == "species") {
                        if(self::with_consecutive_capital_letters($rec['scientificName'])) {
                            $filtered_ids[$rec['taxonID']] = '';
                            // print_r($rec); print_r($taxID_info[$rec['taxonID']]); exit("\nrule 3\n"); //good debug
                            continue;
                        }
                    }
                }
            }
            
            if($rec['taxonomicStatus'] == "accepted") {
                /* Remove branches */
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['taxonID']] = '';
                    $filtered_ids[$rec['taxonID']] = '';
                    continue;
                }
            }
            
            /*Array(
                [taxonID] => 1_1
                [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                [acceptedNameUsageID] => 1
                [parentNameUsageID] => 
                [scientificName] => all
                [taxonRank] => no rank
                [taxonomicStatus] => synonym
                [referenceID] => 
            )*/
            
            /* https://eol-jira.bibalex.org/browse/TRAM-989?focusedCommentId=65561&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-65561
            Hi Eli,
            I would like to make a couple of tweaks to this resource:
            1. Please remove all taxa (and their children) that have one of the following rank values:
                biotype
                forma specialis
                isolate
                pathogroup
                serogroup
                serotype
                strain
            2. Please change the following rank values:
                forma -> form
                varietas -> variety
            */
            if(in_array($rec['taxonRank'], array('biotype', 'forma specialis', 'isolate', 'pathogroup', 'serogroup', 'serotype', 'strain'))) $filtered_ids[$rec['taxonID']] = '';
            
        } //end loop

        // /* Added during TRAM-981
        $tmp = $removed_branches + $filtered_ids;
        $removed_branches = $tmp;
        // */
        
        echo "\nStart main process 2...tram_796\n"; $i = 0;
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 300000) == 0) echo "\n count:[$i] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            $row = Functions::conv_to_utf8($row);
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            /*Array(
                [taxonID] => 1_1
                [furtherInformationURL] => https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=1
                [acceptedNameUsageID] => 1
                [parentNameUsageID] => 
                [scientificName] => all
                [taxonRank] => no rank
                [taxonomicStatus] => synonym
                [referenceID] => 
            )*/
            
            /*
            2. Please change the following rank values:
                forma -> form
                varietas -> variety
            */
            if($rec['taxonRank'] == 'forma') $rec['taxonRank'] = 'form';
            if($rec['taxonRank'] == 'varietas') $rec['taxonRank'] = 'variety';
            
            if(isset($filtered_ids[$rec['taxonID']])) continue;
            if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
            if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;
            if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
            
            if($rec['taxonomicStatus'] == "accepted") {
                /* Remove branches */
                $ancestry = self::get_ancestry_of_taxID($rec['taxonID'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['taxonID']] = '';
                    continue;
                }
            }
            self::write_taxon_DH($rec);
        } //end loop
    }
    private function write_taxon_DH($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxonID'];
        $taxon->parentNameUsageID       = $rec['parentNameUsageID'];
        $taxon->taxonRank               = $rec['taxonRank'];
        $taxon->scientificName          = $rec['scientificName'];
        $taxon->taxonomicStatus         = $rec['taxonomicStatus'];
        $taxon->acceptedNameUsageID     = $rec['acceptedNameUsageID'];
        $taxon->furtherInformationURL   = $rec['furtherInformationURL'];
        $taxon->referenceID             = $rec['referenceID'];
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            
            // /* IMPORTANT integrity check: https://eol-jira.bibalex.org/browse/TRAM-795?focusedCommentId=63387&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63387
            if($taxon->taxonID == $taxon->parentNameUsageID) $taxon->parentNameUsageID = '';
            // */
            
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        if($val = $rec['referenceID']) {
            $ids = explode("; ", $val);
            foreach($ids as $id) $this->reference_ids_2write[$id] = '';
        }
    }
    private function with_consecutive_capital_letters($str)
    {
        // echo "\n$str\n";
        $words = explode(" ", $str);
        // print_r($words);
        foreach($words as $word) {
            // echo "\n word -- $word\n";
            $word = preg_replace("/[^a-zA-Z]/", "", $word); //get only a-z A-Z
            // echo "\n new word -- $word\n";
            $with_small = false;
            for ($i = 0; $i <= strlen($word)-1; $i++) {
                if(is_numeric($word[$i])) continue;
                // echo " ".$word[$i];
                if(!ctype_upper($word[$i])) $with_small = true;
            }
            if(!$with_small && strlen($word) > 1) return true;
        }
        return false;
    }
    private function more_ids_to_remove_TRAM_796() //updated for TRAM-981
    {
        //parentNameUsageID -> 1_undefined_parent_ids.txt
        $a = array();
        //acceptedNameUsageID -> 1_undefined_acceptedName_ids.txt (n = 554 as of Feb 4, 2020)
        /*
        $b = array(88, 89, 131, 132, 290, 417, 423, 679, 683, 702, 703, 749, 757, 926, 928, 1003, 1151, 1535, 1549, 1567, 1568, 1627, 2038, 2039, 2306, 28067, 28068, 29343, 29345, 29346, 29349, 29402, 29403, 32012, 32043, 32044, 33055, 33056, 
        33927, 33939, 34029, 34102, 34103, 35781, 36589, 36852, 36862, 36863, 37694, 38282, 38777, 38778, 39491, 39492, 40991, 41203, 41206, 41207, 42686, 42687, 46507, 47246, 47994, 50055, 50057, 54066, 54067, 54758, 54759, 56240, 
        57173, 57174, 58337, 58338, 59507, 59508, 62676, 64559, 65047, 65048, 65501, 66694, 67684, 67688, 70582, 70584, 70586, 71189, 72993, 76731, 77915, 79879, 80877, 83220, 84026, 85683, 86183, 86188, 87645, 89152, 90729, 92793, 
        93681, 100668, 100761, 101530, 101533, 101534, 105560, 107830, 108080, 112008, 112009, 114248, 114249, 118011, 119861, 119862, 123820, 123822, 126824, 130788, 135579, 136611, 137460, 137461, 138072, 138073, 143692, 152573, 
        162154, 166501, 169055, 169056, 174924, 174925, 178338, 186490, 189384, 189385, 190972, 191960, 196614, 196616, 197479, 198346, 203804, 203907, 204619, 206403, 210011, 212743, 212791, 213485, 215802, 215803, 221239, 221240, 
        221279, 221280, 223232, 223385, 223386, 225324, 233181, 237321, 237340, 237342, 237345, 238670, 247482, 251534, 251535, 251536, 251537, 251538, 251539, 251540, 251541, 251542, 251543, 251544, 251545, 257501, 257502, 262406, 
        263643, 264311, 265310, 265570, 265670, 265671, 265980, 265981, 266021, 269252, 269253, 269258, 269259, 269260, 269261, 270368, 279809, 279810, 283918, 283919, 285944, 288769, 293924, 293925, 293926, 295320, 306266, 306267, 
        307486, 310966, 311458, 312168, 314672, 314673, 316612, 316997, 318147, 318476, 318480, 326458, 327159, 327160, 330062, 332095, 332518, 332522, 332652, 332653, 335431, 335967, 335971, 336377, 336378, 336809, 336810, 337328, 
        337329, 338604, 339866, 340221, 347014, 347015, 349221, 349553, 349554, 349742, 359552, 360239, 360241, 362860, 362861, 363843, 364030, 374666, 374667, 376743, 376746, 376748, 381306, 382725, 384638, 386487, 386607, 386608, 
        388394, 388395, 391607, 391608, 391952, 391953, 392593, 392597, 393764, 393765, 395631, 395928, 395929, 398772, 398773, 399579, 400390, 400771, 400946, 404402, 404403, 404998, 412032, 412033, 412034, 413882, 415014, 415015, 
        417293, 417294, 426114, 427706, 431060, 431061, 431677, 445219, 445220, 447792, 448125, 448157, 448158, 450365, 454131, 455252, 455253, 457575, 467084, 467085, 467598, 467747, 470491, 470492, 472582, 472583, 472825, 472834, 
        478105, 482135, 482171, 482172, 483197, 484769, 485180, 486506, 486507, 492233, 495819, 495820, 502742, 508461, 511434, 511435, 513160, 522261, 522889, 536375, 540501, 545537, 549000, 554722, 555313, 559956, 561442, 568068, 
        568400, 568987, 568988, 573657, 573658, 582472, 584787, 585455, 588816, 592379, 616952, 620910, 630403, 633142, 639200, 641636, 644355, 654416, 655184, 665467, 666376, 669502, 670955, 670956, 680363, 680378, 682400, 693227, 
        693993, 693994, 700201, 708634, 714531, 728055, 735331, 741759, 744406, 744995, 745004, 745410, 749710, 749852, 751577, 752657, 754249, 759720, 759929, 766136, 767528, 767891, 767892, 862775, 869714, 869715, 869716, 881286, 882059, 882627, 886464, 888845, 907288, 909656, 913099, 933500, 936052, 938288, 938289, 946333, 947013, 991903, 991904, 994692, 994695, 994696, 1010676, 1035120, 1041766, 1041767, 1041768, 1046938, 1048752, 1054550, 1070130, 1076588, 1076625, 1076626, 1076628, 1076629, 1078904, 1078905, 1082276, 1090944, 1095805, 1101373, 1104448, 1114981, 1122261, 1125007, 1125862, 1130284, 1133106, 1133546, 1134440, 1144618, 1145345, 1146865, 1154686, 1154688, 1156395, 1157986, 1160784, 1167092, 1176416, 1177755, 1177756, 1182408, 1191175, 1207039, 1227554, 1229656, 1230389, 1233411, 1235591, 1246645, 1249552, 1265508, 1265509, 1273155, 1278974, 1278977, 1296669, 1297617, 1301080, 1302410, 1302411, 1323375, 1345115, 1345117, 1347788, 1348092, 1353246, 1368237, 1381133, 1382356, 1384459, 1384589, 1387982, 1390249, 1392389, 1395940, 1397666, 1400827, 1400857, 1400859, 1400860, 1400862, 1407055, 1408812, 1410382, 1410383, 1410606, 1414643, 1414835, 1415657, 1417852, 1427135, 1427364, 1431962, 1433992, 1433993, 1433998, 1434017, 1434023, 1434025, 1439726, 1442619, 1445552, 1448937, 1449912, 1457365, 1464038, 1465824, 1465825, 1472763, 1472764, 1476748, 1482074, 1484898, 1485594, 1495041, 1499975, 1500506, 1501224, 1502180, 1505531, 1519464, 1521554, 1524249, 1524254, 1524264, 1528099, 1536694, 1541670, 1541743, 1543703, 1543721, 1547922, 1549619, 1550730, 1570016, 1572860, 1573172, 1574283, 1574284, 1576550, 1582879, 1593364, 1594731, 1597779, 1597781, 1603555, 1604025, 1607817, 1608298, 1608830, 1608831, 1609595, 1617948, 1629062, 1630136, 1632780, 1634993, 1647181, 1649455, 1649470, 1649508, 1653846, 1654711, 1655433, 1655545, 1655549, 1673721, 1673724, 1678397, 1682492, 1686287, 1686313, 1689270, 1699066, 1699619, 1700835, 1700836, 1702214, 1702237, 1702241, 1702285, 1705394, 1705729, 1705730, 1709001, 1715798, 1723384, 1734920, 1750598, 1751427, 1765682, 1765683, 1765684, 1765737, 1768907, 1769008, 1769250, 1769732, 1774968, 1774969, 1774970, 1774971, 1775716, 1776760, 1778262, 1778263, 1778264, 1779367, 1779368, 1779369, 1779370, 1779371, 1779372, 1781242, 1793872, 1796922, 1798817, 1818881, 1825023, 1838285, 1839936, 1840207, 1844267, 1844514, 1844524, 1844525, 1846278, 1847725, 1852374, 1855371, 1860102, 1861843, 1861917, 1867035, 1867036, 1867040, 1868589, 1870986, 1871025, 1871604, 1883427, 1885577, 1885581, 1885582, 1885583, 1885584, 1885585, 1885586, 1885587, 1902578, 1902579, 1902823, 1902826, 1902827, 1903276, 1903277, 1905838, 1905839, 1906657, 1906659, 1906660, 1906661, 1912923, 1913444, 1918454, 1920251, 1930845, 1930932, 1931219, 1933048, 1938003, 1940138, 1940235, 1940240, 1940610, 1951300, 1957017, 1971484, 1971485, 1972431, 1978122, 1980681, 1981510, 1982602, 1982961, 1982970, 1982971, 1983104, 1983105, 1983114, 1983115, 1993870, 2014174, 2023214, 2023218, 2023233, 2023234, 2023235, 2026734, 2026791, 2027874, 2030919, 2035356, 2035772, 2039302, 2040999, 2052317, 2052484, 2053538, 2053570, 2055893, 2056121, 2056316, 2058498, 2059302, 2066470, 2066471, 2066914, 2070761, 2077315, 2080658, 2080731, 2081523, 2082189, 2093740, 2094028, 2099674, 2108523, 2126330, 2137422, 2137423, 2163009, 2163959, 2170555, 2172004, 2175151, 2182793, 2201891, 2202177, 2202197, 2211178, 2211183, 2219504, 2219505, 2250257, 2267262, 2291597, 2302373, 2302374, 2302375, 2303498, 2315236, 2315841, 2321403, 2489367, 2497653, 2499835, 2499851, 2507573, 2507574, 2509695, 2510792, 2510793, 2517205, 2517210, 2548426, 2565576, 2565582, 2570323, 2588941, 2588942, 2590445, 2594591, 2594592, 2594593, 2607661, 2608261, 2608262, 2608792, 2608793, 2666346, 2666348, 2678343);
        */
        //NCBI_Taxonomy_Harvest_DH_undefined_acceptedName_ids.txt (n = 133 as of Sep 21, 2020)
        $b = array(1151, 33927, 35781, 36589, 37694, 39491, 62676, 66694, 67684, 67688, 83220, 90729, 136611, 143692, 166501, 210011, 238670, 262406, 293924, 293925, 293926, 295320, 349553, 
        349554, 359552, 391607, 391608, 391609, 452532, 452533, 452534, 452535, 452536, 452537, 452538, 452539, 452540, 452541, 452542, 452543, 452544, 452545, 452546, 452547, 
        452548, 452549, 452550, 452551, 452552, 452553, 452554, 452555, 452556, 452557, 452558, 452559, 452560, 452561, 482171, 482172, 484769, 502742, 515619, 522261, 525904, 
        536375, 554722, 588816, 654416, 657317, 657318, 666376, 745004, 759720, 933500, 936052, 1046938, 1130284, 1167092, 1382356, 1427135, 1457365, 1505531, 1519464, 1649508, 
        1655549, 1700835, 1700836, 1779367, 1779368, 1779369, 1779370, 1779371, 1779372, 1781242, 1838285, 1839936, 1883427, 1912923, 1919279, 1919280, 1919281, 1957017, 1978122, 
        1993870, 2026734, 2026791, 2035772, 2040999, 2052317, 2053538, 2053570, 2056121, 2056316, 2081523, 2094028, 2099674, 2163009, 2250257, 2489367, 2497653, 2517205, 2517210, 
        2571051, 2608792, 2608793, 2653714, 2666346, 2666348, 2666349, 2712852, 2766896, 2766897);

        $c = array(686, 33892, 88833, 170609, 272632, 290337, 316385, 316407, 325777, 340185, 391914, 450394, 450520, 451516, 467705, 499848, 511145, 527799, 531853, 536088, 536089, 561307, 574965, 
        585395, 595496, 679895, 687916, 703352, 710127, 710128, 723892, 743710, 743711, 747078, 865867, 887710, 887711, 903520, 931276, 985080, 985081, 1001542, 1051346, 1051347, 1051348, 
        1051349, 1051350, 1051351, 1051352, 1051353, 1055541, 1055543, 1078030, 1081094, 1081095, 1090929, 1100833, 1110693, 1116211, 1117958, 1131757, 1131758, 1147161, 1147787, 1155079, 
        1155083, 1155084, 1165093, 1165941, 1185419, 1185420, 1193576, 1210455, 1212766, 1232554, 1245474, 1333547, 1359150, 1430440, 1431451, 1446503, 1446504, 1446505, 1446606, 1446607, 
        1446608, 1446702, 1446734, 1446735, 1446765, 1446766, 1460373);

        $all = array_merge($a, $b, $c);
        return array_unique($all);
    }
    // ----------------------------------------------------------------- end TRAM-796 -----------------------------------------------------------------
    private function more_ids_to_remove_TRAM_795() //updated for TRAM-981
    {
        //parentNameUsageID -> 1_undefined_parent_ids.txt
        $a = array(9900);
        //acceptedNameUsageID -> 1_undefined_acceptedName_ids.txt
        $b = array();
        $c = array_merge($a, $b);
        return array_unique($c);
    }
    function start_tram_795()
    {
        // 19   https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=19      18  Pelobacter carbinolicus species accepted    2912; 5381
        /* test
        $taxID_info = self::get_taxID_nodes_info();
        // $ancestry = self::get_ancestry_of_taxID(984211, $taxID_info); print_r($ancestry); print_r($taxID_info[984211]);
        // $ancestry = self::get_ancestry_of_taxID(1173450, $taxID_info); print_r($ancestry); print_r($taxID_info[1173450]);
        $ancestry = self::get_ancestry_of_taxID(9900, $taxID_info); print_r($ancestry); print_r($taxID_info[9900]);
        exit("\n-end tests-\n");
        */
        /* test
        $removed_branches = self::get_removed_branches_from_spreadsheet(); print_r($removed_branches);
        exit("\n-end tests-\n");
        */
        /*
        self::browse_citations();
        exit("\n-end tests-\n");
        */
        self::main_tram_795(); //exit("\nstop muna\n");
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
    }
    private function get_ancestry_of_taxID($tax_id, $taxID_info)
    {   /* Array(
            [1] => Array(
                    [pID] => 1
                    [r] => no rank
                    [dID] => 8
                )
        )*/
        $final = array();
        $final[] = $tax_id;
        while($parent_id = @$taxID_info[$tax_id]['pID']) {
            if(!in_array($parent_id, $final)) $final[] = $parent_id;
            else {
                if($parent_id == 1) return $final;
                else {
                    print_r($final);
                    exit("\nInvestigate $parent_id already in array.\n");
                }
            }
            $tax_id = $parent_id;
        }
        return $final;
    }
    private function get_taxID_nodes_info()
    {
        echo "\nGenerating taxID_info...";
        $final = array();
        $fields = $this->file['nodes.dmp']['fields'];
        $file = Functions::file_open($this->file['nodes.dmp']['path'], "r");
        $i = 0;
        if(!$file) exit("\nFile not found!\n");
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = Functions::conv_to_utf8($row);
            $row = explode("\t|", $row);
            array_pop($row);
            $row = array_map('trim', $row);
            if(($i % 300000) == 0) echo "\n count:[$i] ";
            $row = array_map('trim', $row);
            $vals = $row;
            if(count($fields) != count($vals)) {
                print_r($vals); exit("\nNot same count ".count($fields)." != ".count($vals)."\n"); continue;
            }
            if(!$vals[0]) continue;
            $k = -1; $rec = array();
            foreach($fields as $field) {
                $k++;
                $rec[$field] = $vals[$k];
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit;
            /*Array(
                [tax_id] => 1
                [parent_tax_id] => 1
                [rank] => no rank
                [embl_code] => 
                [division_id] => 8
                [inherited div flag] => 0
                [genetic code id] => 1
                [inherited GC flag] => 0
                [mitochondrial genetic code id] => 0
                [inherited MGC flag] => 0
                [GenBank hidden flag] => 0
                [hidden subtree root flag] => 0
                [comments] => 
            )*/
            if(isset($final[$rec['tax_id']])) exit("\nInvestigate not unique tax_id in nodes.dmp\n");
            $final[$rec['tax_id']] = array("pID" => $rec['parent_tax_id'], 'r' => $rec['rank'], 'dID' => $rec['division_id']);
            
            /* debug only
            if($rec['tax_id'] == 2169824) print_r($final[$rec['tax_id']]);
            elseif($rec['tax_id'] == 2574718) print_r($final[$rec['tax_id']]);
            elseif($rec['tax_id'] == 1448987) print_r($final[$rec['tax_id']]);
            */
            
            // print_r($final); exit;
        }
        // exit("\nexit muna 01\n");
        fclose($file);
        return $final;
    }
    private function is_tax_id_a_virus($ancestry, $division_id)
    {
        if(in_array($this->Viruses_Taxonomy_ID, $ancestry)) return true;
        elseif($division_id == 9) return true;
        else return false;
    }
    private function does_sciname_start_with_Candidatus_et_al($sciname, $Candidatus_only_YN = false)
    {
/*
Hi Katja,
The three Candidatus you mentioned above are represented like these in the source:
- 2601574	|	"Candidatus Cytomitobacter" Tashyreva, Prokopchuk, and Lukes 2018	|		|	synonym	|
- 1968910	|	"Candidatus Nitrosocaldaceae" Qin et al. 2016	|		|	authority	|
- 1968909	|	"Candidatus Nitrosocaldales" Qin et al. 2017	|		|	authority	|

They started with double quotes.
So do you want them converted to:
    '"Candidatus Cytomitobacter" Tashyreva, Prokopchuk, and Lukes 2018'
Or
    'Candidatus Cytomitobacter Tashyreva, Prokopchuk, and Lukes 2018'
Thanks.
*/
        
        if($Candidatus_only_YN) $starts_with = array('Candidatus', '"Candidatus'); //from last section of TRAM-981
        else                    $starts_with = array('Candidatus', "'", "(", "["); //orig
        foreach($starts_with as $s) {
            $count = strlen($s);
            if(substr($sciname, 0, $count) == $s) return true;
        }
        return false;
    }
    private function does_sciname_contains_phytoplasma_et_al($sciname)
    {
        $strings = array(" x ", "phytoplasma");
        foreach($strings as $str) {
            if(stripos($sciname, $str) !== false) return true; //string is found
        }
        return false;
    }
    private function is_sciname_a_strict_binomial($sciname)
    {   /*
        1 uppercase letter
        1 or more lowercase letters
        1 whitespace
        1 or more lowercase letters or dashes: -
        */
        $sciname = Functions::remove_whitespace(trim($sciname));
        if(self::get_number_of_UpperLower_case_letter($sciname, 'upper') == 1) {
            if(self::get_number_of_UpperLower_case_letter($sciname, 'lower') >= 1) {
                if(substr_count($sciname, " ") == 1) {
                    if(self::get_number_of_UpperLower_case_letter($sciname, 'lower') >= 1 || substr_count($sciname, "-") >= 1) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    private function get_number_of_UpperLower_case_letter($sciname, $what)
    {   $count = 0;
        for($x = 0; $x <= strlen($sciname)-1; $x++) {
            if($what == 'upper') {
                if(ctype_upper(substr($sciname,$x,1))) $count++;
            }
            elseif($what == 'lower') {
                if(ctype_upper(substr($sciname,$x,1))) $count++;
            }
            
        }
        return $count;
    }
    private function main_tram_795()
    {
        $filtered_ids = array();
        $taxon_refs = self::browse_citations();
        
        $taxID_info['xxx'] = array("pID" => '', 'r' => '', 'dID' => '');
        $taxID_info = self::get_taxID_nodes_info();
        
        /* test only
        $id = 10090; $ancestry = self::get_ancestry_of_taxID($id, $taxID_info); echo "\nancestry of [$id]"; print_r($ancestry); //Mus musculus
        if(self::is_tax_id_a_virus($ancestry)) echo "\n[$id] is a virus\n";
        else                                  echo "\n[$id] is NOT a virus\n";
        exit("\n-end test-\n");
        */
        
        $removed_branches = self::get_removed_branches_from_spreadsheet();
        /* Additional IDs are taken from undefined_parents report after each connector run.
        // IMPORTANT: for every taxdump refresh, comment this block, then run, then fill-up more_ids_to_remove_TRAM_795() as needed. Then un-comment this block and run again to finalize.
        $add = self::more_ids_to_remove_TRAM_795();
        foreach($add as $id) $removed_branches[$id] = '';
        */
        
        echo "\nMain processing...TRAM-795";
        $fields = $this->file['names.dmp']['fields'];
        $file = Functions::file_open($this->file['names.dmp']['path'], "r");
        $i = 0; $processed = 0;
        if(!$file) exit("\nFile not found!\n");
        $this->ctr = 1; $old_id = "elix";
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = Functions::conv_to_utf8($row);
            $row = explode("\t|", $row); array_pop($row); $row = array_map('trim', $row);
            if(($i % 300000) == 0) echo "\n count:[$i] ";
            $row = array_map('trim', $row);
            $vals = $row;
            if(count($fields) != count($vals)) {
                print_r($vals); exit("\nNot same count ".count($fields)." != ".count($vals)."\n"); continue;
            }
            if(!$vals[0]) continue;
            $k = -1; $rec = array();
            foreach($fields as $field) {
                $k++;
                $rec[$field] = $vals[$k];
            }
            $rec = array_map('trim', $rec);
            /* good debug --------------------------------------------------------------------------------------------
            if($rec['tax_id'] == 1391733) { //1844527
                print_r($rec); print_r($taxID_info[$rec['tax_id']]); 
                
                if(isset($filtered_ids[$rec['tax_id']])) exit("\ntax_id is part of filtered\n");
                $parent_id = $taxID_info[$rec['tax_id']]['pID'];
                if(isset($filtered_ids[$parent_id])) exit("\nparent id is part of filtered\n");
                
                $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
                print_r($ancestry);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    echo "\ntaxon where an id in its ancestry is included among removed branches\n";
                }
                else echo "\nNot part of removed branch\n";
                exit("\ncha 01\n");
            }
            -------------------------------------------------------------------------------------------- */
            // print_r($rec); exit;
            /* Array(
                [tax_id] => 1
                [name_txt] => all
                [unique_name] => 
                [name_class] => synonym
            )*/
            
            /* debug only
            // if($rec['tax_id'] == 7 && $rec['name_txt'] == 'Azorhizobium caulinodans' && $rec['name_class'] == 'scientific name') {
            // if($rec['tax_id'] == 984211) {
            // if($rec['tax_id'] == 58712) {
            // if($rec['tax_id'] == 58096) {
            // if($rec['tax_id'] == 9900) { //Bison
            // if($rec['tax_id'] == 27592) { //Bovinae. The parent of Bison
            // if($rec['tax_id'] == 9895) { //Bovidae. The parent of Bovinae
            // if($rec['tax_id'] == 35500) { //Pecora. The parent of Bovidae
            // if($rec['tax_id'] == 9845) { //Ruminantia. The parent of Pecora
            // if($rec['tax_id'] == 91561) { //Artiodactyla. The parent of Ruminantia
            // if($rec['tax_id'] == 314145) { //. The parent of Artiodactyla
            // if(in_array($rec['tax_id'], array(9900, 27592, 9895, 35500, 9845, 91561, 314145, 1437010, 9347, 32525, 40674, 32524, 32523, 1338369, 8287, 117571, 117570, 7776, 7742, 89593, 7711, 33511, 33213, 6072, 33208, 33154, 2759, 131567, 1))) {
            if($rec['tax_id'] == 9900) { //Bison
                $this->local_debug = true;
                print_r($rec);
                print_r($taxID_info[$rec['tax_id']]);
            }
            else {
                $this->local_debug = false;
                // continue;
            }
            */
            
            /* start filtering: 
            1. Filter by division_id: Remove taxa where division_id in nodes.dmp is 7 (environmental samples) or 11 (synthetic and chimeric taxa) */
            if(in_array($taxID_info[$rec['tax_id']]['dID'], array(7,11))) {
                if($rec['name_class'] == "scientific name") $filtered_ids[$rec['tax_id']] = '';
                else                                        $filtered_names[$rec['name_txt']] = '';
                continue;
            }
            if($this->local_debug) echo "\nreached 100\n";
            /* 2. Filter by text string
            a. Remove taxa that have the string “environmental sample” in their scientific name. This will get rid of those environmental samples that don’t have the environmental samples division for some reason. */
            if(stripos($rec['name_txt'], "environmental sample") !== false) {
                if($rec['name_class'] == "scientific name") $filtered_ids[$rec['tax_id']] = '';
                else                                        $filtered_names[$rec['name_txt']] = '';
                continue;
            } //string is found
            if($this->local_debug) echo "\nreached 101\n";
            
            /* b. Remove all taxa of rank species where the scientific name includes one of the following strings: sp.|aff.|cf.|nr.
            This will get rid of a lot of the samples that haven’t been identified to species. */

            /*
            85262	|	African violet	|		|	common name	|
            85262	|	Saintpaulia ionantha	|		|	synonym	|
            85262	|	Saintpaulia ionantha H.Wendl.	|		|	authority	|
            85262	|	Saintpaulia sp. 'Sigi Falls'	|		|	includes	|
            85262	|	Streptocarpus ionanthus	|		|	scientific name	|
            85262	|	Streptocarpus ionanthus (H.Wendl.) Christenh.	|		|	authority	|
            85262	|	Streptocarpus sp. 'Sigi Falls'	|		|	includes	|
            
            Irregardless of the other filter rules. Let us look at this single rule:
            "Remove all taxa of rank species where the scientific name includes one of the following strings: sp.|aff.|cf.|nr."
            
            Assuming 85262 is rank 'species'.
            Is "Streptocarpus ionanthus" with name class = "scientific name" be excluded since the alternative names has ' sp.'.
            Or the rule for removing taxa with ' sp.' only affects where name class is "scientific name".
            So in this case "Streptocarpus ionanthus" will be included since it doesn't have ' sp.'
            And alternatives will only be:
            85262	|	Saintpaulia ionantha	|		|	synonym	|
            85262	|	Saintpaulia ionantha H.Wendl.	|		|	authority	|
            85262	|	Streptocarpus ionanthus (H.Wendl.) Christenh.	|		|	authority	|
            */
            
            // /* TRAM-989 - Please remove all taxa that have the string "endosymbiont of" in their name.
            // filter it for all name types not just "scientific name"
            if(stripos($rec['name_txt'], "endosymbiont of") !== false) {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
            // */
            
            /* TRAM-981
            1. Expanding 2b in TRAM-795:
            Remove all taxa of rank species where the scientific name includes one of the following strings: sp.|aff.|cf.|nr.
            Please also apply the aff.|cf.|nr. filter to taxa of rank subspecies, varietas, or forma.
            */
            if($rec['name_class'] == "scientific name") {
                $rank = $taxID_info[$rec['tax_id']]['r'];
                if(in_array($rank, array('species', 'no rank'))) {
                    if(stripos($rec['name_txt'], " sp.") !== false)      {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " aff.") !== false) {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " cf.") !== false)  {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " nr.") !== false)  {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                }
                elseif(in_array($rank, array('subspecies', 'varietas', 'forma'))) { //TRAM-981 #1
                    if(stripos($rec['name_txt'], " aff.") !== false)     {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " cf.") !== false)  {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " nr.") !== false)  {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                }
            }
            elseif(in_array($rec['name_class'], $this->alternative_names)) {
                if(stripos($rec['name_txt'], " sp.") !== false)      {$filtered_names[$rec['name_txt']] = ''; continue;} //string is found
                elseif(stripos($rec['name_txt'], " aff.") !== false) {$filtered_names[$rec['name_txt']] = ''; continue;} //string is found
                elseif(stripos($rec['name_txt'], " cf.") !== false)  {$filtered_names[$rec['name_txt']] = ''; continue;} //string is found
                elseif(stripos($rec['name_txt'], " nr.") !== false)  {$filtered_names[$rec['name_txt']] = ''; continue;} //string is found
            }
            if($this->local_debug) echo "\nreached 102\n";
            
            // /* TRAM-981 #2. Remove all taxa (regardless of rank) where scientificName includes the string "genomosp"
            if(stripos($rec['name_txt'], "genomosp") !== false) {
                if($rec['name_class'] == "scientific name") $filtered_ids[$rec['tax_id']] = '';
                else                                        $filtered_names[$rec['name_txt']] = '';
                continue;
            } //string is found
            // */
            if($this->local_debug) echo "\nreached 103\n";
            
            /* TRAM-981 #3. Remove species with non-binomial names, i.e., taxa with the following profile:
            taxonRank IS "species"
            AND taxon is NOT a descendant of Viruses (virus species names are not binomials)
            AND scientificName does NOT start with "Candidatus" OR one of the following characters: '([
            AND scientificName does NOT contain any of the following strings: (" x " OR "phytoplasma") [that's an x with whitespace before & after, indicating a hybrid, case insensitive]
            AND scientificName is not a strict binomial, i.e., does not follow the pattern:
            1 uppercase letter
            1 or more lowercase letters
            1 whitespace
            1 or more lowercase letters or dashes: -
            */
            $rank = $taxID_info[$rec['tax_id']]['r'];
            $division_id = $taxID_info[$rec['tax_id']]['dID'];
            if($rec['name_class'] == "scientific name") {
                if(in_array($rank, array('species'))) {
                    $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
                    if(!self::is_tax_id_a_virus($ancestry, $division_id)) { //taxon is NOT a descendant of Viruses
                        $sciname = $rec['name_txt'];
                        if(!self::does_sciname_start_with_Candidatus_et_al($sciname)) {
                            if(!self::does_sciname_contains_phytoplasma_et_al($sciname)) {
                                if(!self::is_sciname_a_strict_binomial($sciname)) {
                                    if($rec['name_class'] == "scientific name") $filtered_ids[$rec['tax_id']] = '';
                                    // else                                        $filtered_names[$rec['name_txt']] = '';
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
            /* end - TRAM-981 #3.*/
            if($this->local_debug) echo "\nreached 104\n";
            
            /* TRAM-981 #4. Remove surrogate rankless surrogates below species level, i.e., taxa with the following profile:
            taxon is NOT a descendant of Viruses
            AND scientificName does NOT contain "phytoplasma"
            AND taxonRank IS "no rank"
            AND taxonRank of PARENT IS ("species" OR "subspecies" OR "varietas" OR "forma")
            */
            if($rec['name_class'] == "scientific name") {
                $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
                if(!self::is_tax_id_a_virus($ancestry, $division_id)) { //taxon is NOT a descendant of Viruses
                    $sciname = $rec['name_txt'];
                    if(substr_count($sciname, "phytoplasma") == 0) { //AND scientificName does NOT contain "phytoplasma"
                        $rank = $taxID_info[$rec['tax_id']]['r'];
                        if($rank == 'no rank') { //AND taxonRank IS "no rank"
                            if($parent_id = @$taxID_info[$rec['tax_id']]['pID']) { //AND taxonRank of PARENT IS ("species" OR "subspecies" OR "varietas" OR "forma")
                                $rank_of_parent = @$taxID_info[$parent_id]['r'];
                                if(in_array($rank_of_parent, array("species","subspecies","varietas","forma"))) {
                                    if($rec['name_class'] == "scientific name") $filtered_ids[$rec['tax_id']] = '';
                                    // else                                        $filtered_names[$rec['name_txt']] = '';
                                    continue;
                                }
                            }
                        }
                    }
                }
            }
/*
Hi Katja,
Upon checking, most of the names in your droppedTaxaToInvestigate.txt have now been accounted for (DwCA still have to be updated).
Except for those Salmonela names.
e.g.
984211	Salmonella enterica subsp. enterica serovar Anatum str. ATCC BAA-1592
These names will be removed because its parent which is tax_id = 58712

[tax_id] => 58712
[name_txt] => Salmonella enterica subsp. enterica serovar Anatum
[unique_name] => 
[name_class] => scientific name
[rank] => no rank
[parentID] => 59201
[parentID_rank] => subspecies
[divisionID] => 0

got removed under TRAM-981 #4. "Remove surrogate rankless surrogates below species level".
- taxon is NOT a descendant of Viruses
- AND scientificName does NOT contain "phytoplasma"
- AND taxonRank IS "no rank"
- AND taxonRank of PARENT IS ("species" OR "subspecies" OR "varietas" OR "forma")

So all descendants of 58712, including 984211 will be removed.
Please tell me if we need to adjust #4 or if I've incorrectly implemented it.
Thanks.
*/
            
            /* end - TRAM-981 #4.*/
            if($this->local_debug) echo "\nreached 105\n";
            
            /* TRAM-981 #5. Remove remaining surrogate taxa below species level, i.e., taxa with the following profile:
            rank IS ("subspecies" OR "varietas" OR "forma")
            AND taxon is NOT a descendant of Viruses
            AND scientificName includes 1 or more numbers
            */
            $rank = $taxID_info[$rec['tax_id']]['r'];
            if($rec['name_class'] == "scientific name") {
                if(in_array($rank, array("subspecies","varietas","forma"))) {
                    $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
                    if(!self::is_tax_id_a_virus($ancestry, $division_id)) { //taxon is NOT a descendant of Viruses
                        $sciname = $rec['name_txt']; //AND scientificName includes 1 or more numbers
                        preg_match_all('!\d+!', $sciname, $matches);
                        // print_r($matches);
                        if($val = @$matches[0]) {
                            if(count($val) >= 1) {
                                if($rec['name_class'] == "scientific name") $filtered_ids[$rec['tax_id']] = '';
                                // else                                        $filtered_names[$rec['name_txt']] = '';
                                continue;
                            }
                        }
                    }
                }
            }
            /* end - TRAM-981 #5.*/
            if($this->local_debug) echo "\nreached 106\n";
            
            if(in_array($rec['name_class'], array("blast name", "type material", "includes", "acronym", "genbank acronym"))) continue; //ignore these names
            
            if($this->local_debug) echo "\nreached 107\n";
            
            $processed++;
        }
        fclose($file);
        
        // /* We will see...
        $tmp = $removed_branches + $filtered_ids;
        $removed_branches = $tmp;
        // */
        
        // =================================================start 2
        echo "\nMain processing 2...TRAM-795";
        $fields = $this->file['names.dmp']['fields'];
        $file = Functions::file_open($this->file['names.dmp']['path'], "r");
        $i = 0; $processed = 0;
        if(!$file) exit("\nFile not found!\n");
        $this->ctr = 1; $this->old_id = "elix";
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = Functions::conv_to_utf8($row);
            $row = explode("\t|", $row); array_pop($row); $row = array_map('trim', $row);
            if(($i % 300000) == 0) echo "\n count:[$i] ";
            $row = array_map('trim', $row);
            $vals = $row;
            if(count($fields) != count($vals)) {
                print_r($vals); exit("\nNot same count ".count($fields)." != ".count($vals)."\n"); continue;
            }
            if(!$vals[0]) continue;
            $k = -1; $rec = array();
            foreach($fields as $field) {
                $k++;
                $rec[$field] = $vals[$k];
            }
            $rec = array_map('trim', $rec);
            /* Array(
                [tax_id] => 1
                [name_txt] => all
                [unique_name] => 
                [name_class] => synonym
            )*/

            /* debug only
            // if($rec['tax_id'] == 7 && $rec['name_txt'] == 'Azorhizobium caulinodans' && $rec['name_class'] == 'scientific name') {
            // if($rec['tax_id'] == 984211) {
            // if($rec['tax_id'] == 58712) {
            // if($rec['tax_id'] == 58096) {
            // if($rec['tax_id'] == 9900) { //Bison
            // if($rec['tax_id'] == 27592) { //Bovinae. The parent of Bison
            // if($rec['tax_id'] == 9895) { //Bovidae. The parent of Bovinae
            // if($rec['tax_id'] == 35500) { //Pecora. The parent of Bovidae
            // if($rec['tax_id'] == 9845) { //Ruminantia. The parent of Pecora
            // if($rec['tax_id'] == 91561) { //Artiodactyla. The parent of Ruminantia
            // if($rec['tax_id'] == 314145) { //. The parent of Artiodactyla
            // if(in_array($rec['tax_id'], array(9900, 27592, 9895, 35500, 9845, 91561, 314145, 1437010, 9347, 32525, 40674, 32524, 32523, 1338369, 8287, 117571, 117570, 7776, 7742, 89593, 7711, 33511, 33213, 6072, 33208, 33154, 2759, 131567, 1))) {
            if($rec['tax_id'] == 9900) { //Bison
                $this->local_debug = true;
                print_r($rec);
                print_r($taxID_info[$rec['tax_id']]);
            }
            else {
                $this->local_debug = false;
                // continue;
            }
            */
            if($this->local_debug) echo "\nreached 200 a\n";
            if(isset($filtered_names[$rec['name_txt']])) {
                if($rec['tax_id'] == 9900) echo "\n -- ".$rec['name_txt']." -- \n";
                continue;
            }
            if($this->local_debug) echo "\nreached 200 b\n";

            if(in_array($rec['name_class'], array("blast name", "type material", "includes", "acronym", "genbank acronym"))) continue; //ignore these names
            if($this->local_debug) echo "\nreached 200\n";
            if(isset($filtered_ids[$rec['tax_id']])) continue;
            if($this->local_debug) echo "\nreached 201\n";
            $parent_id = $taxID_info[$rec['tax_id']]['pID'];
            $parent_id = trim($parent_id);
            if(isset($filtered_ids[$parent_id])) continue;
            if($this->local_debug) echo "\nreached 202\n";
            
            /* 3. Remove branches */
            $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
            if(count($ancestry) == 1 && $ancestry[0] == $rec['tax_id']) $this->debug['no ancestry'][$rec['tax_id']] = ''; //for stats only
            
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['tax_id']] = '';
                continue;
            }
            if($this->local_debug) echo "\nreached 203\n";
            if($val = @$taxon_refs[$rec['tax_id']]) $reference_ids = array_keys($val);
            else                                    $reference_ids = array();

            if($this->old_id != $rec['tax_id']) $this->ctr = 1;
            else {}
            $this->old_id = $rec['tax_id'];
            
            self::write_taxon($rec, $ancestry, $taxID_info[$rec['tax_id']], $reference_ids);
            if($this->local_debug) echo "\nreached 204\n";
            
            if($this->old_id != $rec['tax_id']) {}
            else {
                if(in_array($rec['name_class'], $this->alternative_names)) $this->ctr++;
            }
            
            $processed++;
        }
        fclose($file);
        // =================================================end 2
        // Total rows: 2687427 Processed rows: 1508421 ------ looks OK finally
        echo "\nTotal rows: $i";
        echo "\nProcessed rows: $processed";
    } //end main_tram_795()
    private function write_taxon($rec, $ancestry, $taxid_info, $reference_ids)
    {   /* Array(
            [tax_id] => 1
            [name_txt] => all
            [unique_name] => 
            [name_class] => synonym
        )
        Array(
            [1] => Array(
                    [pID] => 1
                    [r] => no rank
                    [dID] => 8
                )
        )*/
        /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. Sorry, I realize I didn't make this clear in my initial instructions. 
        I have added a note about it now. */
        if(!in_array($rec['name_class'], array("common name", "genbank common name"))) {
            
            $rec = self::TRAM_981_last_section($rec);
            
            $computed_ids = self::format_tax_id($rec);
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID = $computed_ids['tax_id'];
            
            if($rec['name_class'] == "scientific name") $taxon->parentNameUsageID = $taxid_info['pID'];
            else                                        $taxon->parentNameUsageID = "";
            
            $taxon->taxonRank = $taxid_info['r'];
            $taxon->scientificName = $rec['name_txt'];
            $taxon->taxonomicStatus = self::format_status($rec['name_class']);
            $taxon->acceptedNameUsageID = $computed_ids['acceptedNameUsageID'];
            $taxon->furtherInformationURL = "https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=".$rec['tax_id'];
            if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                
                // /* IMPORTANT integrity check: https://eol-jira.bibalex.org/browse/TRAM-795?focusedCommentId=63387&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-63387
                if($taxon->taxonID == $taxon->parentNameUsageID) $taxon->parentNameUsageID = '';
                // */
                
                $this->archive_builder->write_object_to_file($taxon);
                $this->taxon_ids[$taxon->taxonID] = '';
            }
        }

        if($this->with_comnames) {
            // /* temporarily removed comnames
            if(in_array($rec['name_class'], array("common name", "genbank common name"))) {
                if($common_name = trim(@$rec['name_txt'])) {
                    $v = new \eol_schema\VernacularName();
                    $v->taxonID = $rec["tax_id"];
                    $v->vernacularName = $common_name;
                    $v->language = "en";
                    $vernacular_id = md5("$v->taxonID|$v->vernacularName|$v->language");
                    if(!isset($this->vernacular_ids[$vernacular_id])) {
                        $this->vernacular_ids[$vernacular_id] = '';
                        $this->archive_builder->write_object_to_file($v);
                    }
                }
            }
            // */
        }
    }
    private function TRAM_981_last_section($rec)
    {
        /* TRAM-981 - last section:
        Put scientificName in single quotes IF
        1. scientificName STARTS WITH "Candidatus"
        2. OR scientificName contains "complex" and is not a uninomial or binomial, i.e., it contains more than one white space.
        */
        $sciname = trim(Functions::remove_whitespace($rec['name_txt']));
        if(self::does_sciname_start_with_Candidatus_et_al($sciname, true) || 
            (
                (stripos($sciname, "complex") !== false) //string is found
                &&
                substr_count($sciname, " ") > 1
            )
        )
        {
            $sciname = str_replace('"', '', $sciname);
            $rec['name_txt'] = "'".$sciname."'";
            // echo "\nwith single quotes: [".$rec['name_txt']."]\n"; //debug only
        }
        return $rec;
        /* - end TRAM-981 - last section */
    }
    private function format_tax_id($rec)
    {   /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. Sorry, I realize I didn't make this clear in my initial instructions. 
        I have added a note about it now. */
        
        if($rec['name_class'] == "scientific name")                    return array('tax_id' => $rec['tax_id']                 , 'acceptedNameUsageID' => '');
        elseif(in_array($rec['name_class'], $this->alternative_names)) return array('tax_id' => $rec['tax_id']."_".$this->ctr  , 'acceptedNameUsageID' => $rec['tax_id']);
        else {
            print_r($rec);
            exit("\nInvestigate cha001\n");
        }
    }
    private function format_status($name_class)
    {
        $verbatim = array("equivalent name", "in-part", "misspelling", "genbank synonym", "misnomer", "anamorph", "genbank anamorph", "teleomorph", "authority");
        if($name_class == "scientific name") return "accepted";
        elseif($name_class == "synonym") return "synonym";
        elseif(in_array($name_class, $verbatim)) return $name_class;
        exit("\nUndefined name_class [$name_class]\n");
    }
    private function an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)
    {
        foreach($ancestry as $id) {
            /* use isset() instead
            if(in_array($id, $removed_branches)) return true;
            */
            if(isset($removed_branches[$id])) return true;
        }
        return false;
    }
    private function get_removed_branches_from_spreadsheet() //https://docs.google.com/spreadsheets/d/1eWXWK514ivl072FLm7dF2MpL9W29bs6XYDbPjHtWlxE/edit?usp=sharing
    {
        require_library('connectors/GoogleClientAPI');
        $func = new GoogleClientAPI(); //get_declared_classes(); will give you how to access all available classes
        $params['spreadsheetID'] = '1eWXWK514ivl072FLm7dF2MpL9W29bs6XYDbPjHtWlxE';
        $params['range']         = 'Sheet1!A2:A16'; //where "A" is the starting column, "C" is the ending column, and "1" is the starting row.
        $arr = $func->access_google_sheet($params);
        //start massage array
        foreach($arr as $item) $final[$item[0]] = '';
        /* $final = array_keys($final); */ //commented so we can use isset() instead of in_array()
        return $final;
        /* if google spreadsheet suddenly becomes offline, use this:
        Array(
            [0] => 12908
            [1] => 28384
            [2] => 2323
            [3] => 1035835
            [4] => 1145094
            [5] => 119167
            [6] => 590738
            [7] => 1407750
            [8] => 272150
            [9] => 1899546
            [10] => 328343
            [11] => 463707
            [12] => 410859
            [13] => 341675
            [14] => 56059
        )*/
    }
    private function browse_citations($pre_defined_list_of_refIDs_2write = array())
    {
        $this->debug['citation wrong cols'] = 0;
        echo "\nCitations...";
        $final = array();
        $fields = $this->file['citations.dmp']['fields'];
        $file = Functions::file_open($this->file['citations.dmp']['path'], "r");
        $i = 0;
        if(!$file) exit("\nFile not found!\n");
        while (($row = fgets($file)) !== false) {
            $i++;
            $row = Functions::conv_to_utf8($row);
            $row = explode("\t|", $row);
            array_pop($row);
            $row = array_map('trim', $row);
            if(($i % 10000) == 0) echo "\n count:[$i] ";
            $vals = $row;
            if(count($fields) != count($vals)) {
                // print_r($vals); exit("\nNot same count ".count($fields)." != ".count($vals)."\n"); 
                /* there is just 1 or 2 erroneous records, just ignore */
                $this->debug['citation wrong cols']++;
                continue;
            }
            if(!$vals[0]) continue;
            $k = -1; $rec = array();
            foreach($fields as $field) {
                $k++;
                $rec[$field] = $vals[$k];
            }
            /*Array(
                [cit_id] => 24654
                [cit_key] => Catalan P et al. 2009
                [pubmed_id] => 0
                [medline_id] => 0
                [url] => 
                [text] => Catalan P, Soreng RJ, Peterson PM and Peterson P. 2009. Festuca aloha and Festuca molokaiensis (Poaceae: Loliinae), two new species from Hawaii. Journal of the Botanical Research Institute of Texas 3(1): 51-58.
                [taxid_list] => 650148 650149
            )*/
            // print_r($rec); //exit;
            if(!$pre_defined_list_of_refIDs_2write) {
                $taxids = explode(" ", $rec['taxid_list']);
                foreach($taxids as $tax_id) {
                    $final[$tax_id][$rec['cit_id']] = '';
                    self::write_reference($rec);
                }
            }
            else {
                if(isset($pre_defined_list_of_refIDs_2write[$rec['cit_id']])) self::write_reference($rec);
            }
        }
        // print_r($final);
        // print_r(array_keys($final[1352138])); exit;
        // print_r($this->debug); exit;
        fclose($file);
        return $final;
    }
    private function write_reference($ref)
    {
        if(!@$ref['text']) return false;
        $re = new \eol_schema\Reference();
        $re->identifier     = $ref['cit_id'];
        $re->full_reference = $ref['text'];
        $re->uri            =  $ref['url']; 
        if(!isset($this->reference_ids[$re->identifier])) {
            $this->archive_builder->write_object_to_file($re);
            $this->reference_ids[$re->identifier] = '';
        }
    }
    /*
    private function get_mtype_for_range($range)
    {
        switch($range) {
            case "Introduced":                  return "http://eol.org/schema/terms/IntroducedRange";
            case "Invasive":                    return "http://eol.org/schema/terms/InvasiveRange";
            case "Native":                      return "http://eol.org/schema/terms/NativeRange";
        }
        if(in_array($range, $this->considered_as_Present)) return "http://eol.org/schema/terms/Present";
    }
    */
}
?>