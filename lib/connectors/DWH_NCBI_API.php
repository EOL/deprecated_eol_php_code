<?php
namespace php_active_record;
/* connector: [dwh_ncbi_TRAM_795.php] - https://eol-jira.bibalex.org/browse/TRAM-795
              [dwh_ncbi_TRAM_796.php] - https://eol-jira.bibalex.org/browse/TRAM-796

NCBI_Taxonomy_Harvest	    Monday 2018-08-06 02:20:41 AM	{"reference.tab":47083,"taxon.tab":1465751,"vernacular_name.tab":42670}
NCBI_Taxonomy_Harvest_DH	Monday 2018-08-06 04:47:09 AM	{"reference.tab":23592,"taxon.tab":178807,"vernacular_name.tab":167}
NCBI_Taxonomy_Harvest_DH	Wednesday 2018-11-07 11:15:14 PM{"reference.tab":23590,"taxon.tab":177813,"vernacular_name.tab":165}
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
        $this->file['names.dmp']['path'] = "/Volumes/AKiTiO4/d_w_h/TRAM-795/taxdump/names.dmp";
        $this->file['names.dmp']['fields'] = array("tax_id", "name_txt", "unique_name", "name_class");
        $this->file['nodes.dmp']['path'] = "/Volumes/AKiTiO4/d_w_h/TRAM-795/taxdump/nodes.dmp";
        $this->file['nodes.dmp']['fields'] = array("tax_id", "parent_tax_id", "rank", "embl_code", "division_id", "inherited div flag", "genetic code id", "inherited GC flag", "mitochondrial genetic code id", "inherited MGC flag", "GenBank hidden flag", "hidden subtree root flag", "comments");
        $this->file['citations.dmp']['path'] = "/Volumes/AKiTiO4/d_w_h/TRAM-795/taxdump/citations.dmp";
        $this->file['citations.dmp']['fields'] = array("cit_id", "cit_key", "pubmed_id", "medline_id", "url", "text", "taxid_list");
        $this->alternative_names = array("synonym", "equivalent name", "in-part", "misspelling", "genbank synonym", "misnomer", "anamorph", "genbank anamorph", "teleomorph", "authority");
        //start TRAM-796 -----------------------------------------------------------
        $this->prune_further = array(10239, 12884, 3193, 4751, 33208, 29178);
        $this->extension_path = CONTENT_RESOURCE_LOCAL_PATH . "NCBI_Taxonomy_Harvest/"; //this folder is from TRAM-795
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
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
        self::write_vernaculars_DH(); //exit("\nstopx\n");
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
        $add = self::more_ids_to_remove();
        foreach($add as $id) $removed_branches[$id] = '';
        
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...\n";
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
        } //end loop

        echo "\nStart main process 2...\n"; $i = 0;
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
            if(isset($filtered_ids[$rec['taxonID']])) continue;
            if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
            if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;
            
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
    private function more_ids_to_remove()
    {
        //parentNameUsageID -> 1_undefined_parent_ids.txt
        $a = array(224471, 131, 702, 32032, 310966, 84563, 1938003, 32029, 1567, 2038, 32036, 190972, 29402, 33055, 38777, 41206, 42686, 50055, 54758, 57173, 58337, 59507, 65500, 70302, 70300, 70301, 
        2383, 116105, 100668, 1017280, 108525, 112008, 215777, 118011, 119861, 120947, 120946, 120948, 137460, 265980, 262406, 169055, 32004, 174924, 186475, 198346, 189384, 349742, 196614, 
        1861843, 745004, 265570, 215802, 221239, 1400857, 223385, 2030919, 212791, 237321, 744998, 257502, 265670, 330062, 269252, 269258, 269260, 283918, 285105, 946234, 306266, 1048752, 561442, 
        1233498, 314672, 318476, 327159, 448125, 332518, 332652, 335967, 336377, 336809, 337328, 1502180, 347014, 213485, 362860, 374666, 376743, 1803510, 492233, 386607, 388394, 391607, 447792, 
        395928, 398772, 482135, 582472, 404402, 412032, 415014, 417293, 131190, 85683, 445219, 448157, 455252, 457933, 467084, 1609595, 467747, 470491, 568400, 540501, 384638, 486506, 495819, 
        89152, 1434025, 511434, 39491, 767528, 909656, 573657, 652708, 712991, 745410, 1273155, 1145345, 754249, 741759, 907288, 1381133, 670955, 1551504, 1434023, 693993, 700227, 700205, 
        698791, 700216, 1433992, 744995, 1246645, 1543703, 62654, 712976, 869714, 1191175, 651591, 1649470, 1052821, 648176, 905826, 347538, 1686313, 1715798, 1433993, 991903, 133814, 1125862, 
        1541670, 1068873, 1050716, 1608298, 1076625, 1076628, 1078904, 1501224, 1227554, 29346, 1632780, 62672, 1176416, 1647181, 1154686, 1433998, 1301080, 1649508, 86188, 1562572, 1485594, 
        1434017, 2175150, 718217, 1549619, 1734920, 1524249, 1265508, 1392389, 712934, 1321777, 1321783, 1769008, 1649455, 1825023, 2162630, 1484898, 1593364, 1940138, 1918454, 655184, 1562571, 
        1775716, 1678397, 1783263, 1582879, 1465824, 1472763, 1931219, 1572860, 1654711, 1847725, 1930932, 1574283, 1699066, 1608830, 1920251, 1655545, 1655549, 1660250, 1933048, 749852, 1702237, 
        1705400, 1705729, 2027874, 1868136, 1769732, 1951300, 1765682, 1840207, 1803511, 1844514, 1867035, 1980681, 1930845, 1874526, 1885577, 1885582, 1885584, 1885586, 1902578, 1902823, 1905838, 
        588816, 204619, 1978122, 2039302, 1982970, 1983104, 1983114, 1985430, 1985432, 2023214, 2023233, 2041162, 2047984, 2066470, 2108438, 2172004, 62676, 2170555, 2202177, 1813735, 2219504, 
        2219506);
        
        //acceptedNameUsageID -> 1_undefined_acceptedName_ids.txt
        $b = array(185, 290, 417, 423, 679, 683, 1627, 2341, 2343, 2347, 2366, 2707, 13774, 29295, 29349, 29577, 31975, 31985, 32043, 32044, 33927, 33939, 34025, 35781, 36494, 36589, 36852, 37449, 
        37451, 37628, 37694, 38028, 38282, 38989, 39663, 40801, 40802, 40803, 41203, 41291, 41355, 42865, 43945, 46507, 47161, 47467, 56240, 60545, 61433, 64559, 66694, 67684, 67688, 67833, 67834, 
        67835, 67836, 67837, 72993, 73552, 73553, 75760, 77915, 79879, 80877, 81399, 81579, 83220, 83495, 83500, 83501, 83502, 83503, 83504, 86183, 87343, 87344, 87622, 87624, 87625, 87645, 87736, 
        87738, 87740, 87741, 87742, 87743, 87989, 87996, 87997, 88072, 89186, 89581, 89612, 90729, 94870, 96451, 97134, 97973, 97974, 100120, 100122, 100480, 107695, 107696, 107698, 107699, 107700, 
        107701, 107702, 107703, 107706, 107830, 111849, 115133, 119159, 119160, 119931, 120303, 120996, 120997, 121133, 121421, 121716, 123822, 123828, 126824, 130493, 132093, 132566, 132568, 132569, 
        133192, 135524, 135525, 135526, 135527, 135530, 135531, 135532, 135533, 135534, 135535, 135536, 135537, 135538, 135539, 135540, 135541, 135542, 135543, 135544, 135545, 135546, 135547, 135548, 135549, 135550, 135551, 135552, 135553, 135554, 135555, 135556, 135557, 135558, 135564, 135565, 135579, 136611, 143692, 144790, 144791, 144792, 144793, 145479, 146079, 146080, 146081, 146082, 146083, 146084, 146477, 146478, 146755, 146756, 146757, 146758, 146759, 146897, 146899, 154982, 154983, 154984, 155909, 156578, 156586, 157151, 157672, 163926, 164409, 164410, 167465, 169811, 170628, 170630, 170635, 170638, 174143, 174228, 175619, 179974, 179975, 179976, 181554, 185287, 185288, 185309, 188582, 189965, 189967, 189968, 189970, 191557, 191558, 191559, 191560, 191561, 191562, 191563, 191565, 191566, 192105, 193168, 194664, 194665, 194705, 196828, 196832, 197223, 197224, 197225, 197226, 197462, 198012, 199475, 210011, 214802, 216393, 217241, 218938, 220377, 221030, 221241, 228335, 230497, 230536, 233056, 233057, 233058, 233059, 233060, 233061, 233062, 235637, 236927, 236928, 237671, 238670, 239760, 242601, 243244, 243725, 245012, 245014, 245185, 247476, 247634, 251210, 251211, 253285, 253286, 253287, 260704, 260705, 260706, 260707, 260708, 260709, 261391, 261581, 262074, 262489, 263381, 263382, 263643, 264311, 266760, 266761, 266806, 278963, 285853, 285944, 285968, 286731, 292621, 293024, 293025, 293923, 293924, 293925, 293926, 294693, 295320, 299582, 307568, 312284, 314270, 320150, 322506, 322507, 324074, 326458, 329682, 330275, 330781, 330921, 334548, 334747, 334748, 337464, 337900, 338604, 340948, 344987, 349553, 349554, 356856, 359552, 361212, 366893, 367039, 367336, 368244, 372467, 377314, 382464, 388401, 389068, 389347, 390770, 390771, 394285, 397288, 397328, 397329, 400637, 400638, 400639, 400640, 401048, 402788, 404998, 408672, 413490, 413491, 413492, 413494, 413495, 413882, 414372, 420235, 429624, 433320, 434085, 434087, 441065, 441643, 444220, 444223, 444224, 444225, 444226, 444229, 444231, 444232, 444234, 444235, 444237, 444239, 444241, 444245, 444246, 444248, 444250, 444251, 444253, 444256, 444258, 444261, 444262, 444264, 444266, 444268, 444270, 444271, 444272, 444273, 444274, 444275, 444276, 444277, 444278, 444279, 444281, 444282, 444284, 444285, 444287, 444288, 444514, 450645, 451141, 451142, 451143, 451144, 453320, 454170, 455085, 455086, 457421, 467661, 476205, 476277, 478116, 478741, 479275, 482171, 482172, 484769, 487196, 488173, 488174, 488644, 488645, 490939, 490940, 490942, 490943, 490944, 492525, 492725, 492728, 497806, 502742, 512775, 515323, 519355, 522261, 536375, 537363, 537364, 542258, 542259, 542260, 545538, 551278, 552395, 553506, 554722, 555081, 555572, 555574, 558154, 562980, 563693, 564664, 568068, 573134, 577387, 578917, 578919, 587626, 588111, 590062, 633142, 641417, 646530, 649170, 654416, 658081, 658082, 658083, 658084, 658085, 658086, 658087, 658088, 658089, 658657, 658659, 666376, 670282, 670321, 672789, 679481, 684353, 684354, 688069, 688070, 688071, 688321, 688322, 688323, 688324, 688325, 688326, 688327, 693444, 696757, 700069, 709797, 714955, 715305, 715306, 739134, 742724, 743729, 743731, 745154, 745155, 745291, 749710, 755135, 756045, 756687, 758828, 759720, 760527, 795104, 795105, 796938, 858443, 860576, 876044, 877406, 877414, 877415, 877420, 877421, 877424, 877447, 881271, 881272, 882738, 886778, 888405, 888407, 888408, 888413, 888415, 888417, 888420, 888422, 891144, 891974, 910048, 910049, 910093, 910099, 910114, 910118, 910119, 910120, 910126, 910130, 910143, 910157, 910159, 913318, 913319, 913324, 913327, 913338, 933500, 936052, 936556, 936557, 937450, 939175, 939182, 939192, 939548, 939568, 939611, 939637, 999896, 999898, 1046938, 1046946, 1046952, 1046954, 1046955, 1046956, 1046957, 1046958, 1046959, 1046960, 1046961, 1046962, 1046963, 1046964, 1046965, 1046966, 1046967, 1046970, 1046971, 1046972, 1046973, 1046974, 1046975, 1046978, 1046982, 1046983, 1047006, 1047007, 1047010, 1047012, 1047063, 1047066, 1047067, 1047069, 1047070, 1047071, 1047072, 1047073, 1047075, 1047076, 1047077, 1047078, 1047079, 1047080, 1047081, 1049472, 1050361, 1050369, 1052823, 1052825, 1052826, 1052827, 1052828, 1052829, 1052830, 1052831, 1052832, 1052833, 1052838, 1052839, 1052841, 1054213, 1074288, 1074289, 1081614, 1085028, 1085029, 1090944, 1104448, 1104575, 1104577, 1104578, 1104580, 1104617, 1104619, 1104620, 1107891, 1109226, 1109254, 1109255, 1109256, 1109257, 1109258, 1109259, 1109268, 1109318, 1117107, 1128427, 1130284, 1130285, 1130312, 1130345, 1130346, 1130347, 1130348, 1130374, 1134440, 1134604, 1141885, 1156583, 1158751, 1162179, 1163766, 1165433, 1165998, 1168059, 1173021, 1173100, 1178581, 1179458, 1179459, 1179460, 1179461, 1179462, 1181658, 1184151, 1190231, 1190249, 1198115, 1198116, 1206100, 1210997, 1216208, 1216209, 1216210, 1216211, 1216212, 1216213, 1216214, 1216215, 1216216, 1216217, 1225905, 1225906, 1230730, 1231489, 1232438, 1232439, 1232442, 1232443, 1232444, 1232445, 1232446, 1232447, 1232448, 1232449, 1232452, 1232453, 1232454, 1232457, 1232458, 1233384, 1235799, 1240132, 1242690, 1242735, 1242831, 1242866, 1243985, 1245599, 1247924, 1248762, 1248763, 1248939, 1248940, 1248941, 1250029, 1260189, 1260190, 1260191, 1265478, 1271115, 1274550, 1274551, 1274552, 1274561, 1274565, 1277018, 1277019, 1277020, 1277023, 1277024, 1284708, 1294735, 1298881, 1298894, 1298895, 1298897, 1298899, 1298902, 1298903, 1298910, 1301252, 1302614, 1304894, 1304898, 1307893, 1311317, 1316607, 1316608, 1318599, 1323804, 1325005, 1325472, 1327253, 1327356, 1327423, 1328860, 1330035, 1330036, 1330237, 1336968, 1337832, 1349741, 1378080, 1379386, 1379389, 1379392, 1379393, 1379394, 1379397, 1379398, 1379399, 1380357, 1382716, 1387429, 1387430, 1390409, 1390429, 1390430, 1390431, 1395101, 1400053, 1406378, 1408099, 1411290, 1411306, 1411307, 1414643, 1417303, 1423364, 1426497, 1427135, 1434080, 1434822, 1435893, 1435894, 1444363, 1444364, 1444365, 1449005, 1449124, 1449127, 1449249, 1449250, 1449274, 1449278, 1449317, 1460287, 1471142, 1471143, 1471144, 1476611, 1476722, 1481020, 1481023, 1481024, 1481025, 1481026, 1481027, 1481028, 1481029, 1481030, 1481031, 1481032, 1481033, 1481034, 1481035, 1484108, 1484109, 1484120, 1484121, 1484122, 1484123, 1484433, 1486395, 1486396, 1487582, 1492922, 1495041, 1495144, 1502488, 1504761, 1504776, 1504789, 1505531, 1517682, 1517935, 1519464, 1523418, 1523421, 1523424, 1523428, 1523431, 1523432, 1524976, 1531961, 1531962, 1531963, 1532116, 1535675, 1535676, 1535678, 1535680, 1535962, 1536694, 1537252, 1540278, 1556897, 1556898, 1571821, 1571835, 1571836, 1571837, 1571868, 1571897, 1571898, 1571899, 1571900, 1571901, 1571902, 1572656, 1572657, 1576836, 1576837, 1576838, 1577223, 1577230, 1577241, 1577242, 1577243, 1577244, 1577262, 1577263, 1577264, 1577265, 1577267, 1577268, 1577269, 1577277, 1577279, 1577303, 1577304, 1577306, 1577307, 1577308, 1577312, 1577313, 1577314, 1577323, 1577324, 1577327, 1577328, 1577329, 1577330, 1577333, 1577334, 1577335, 1577336, 1577337, 1577338, 1577341, 1577343, 1577344, 1577345, 1577346, 1577347, 1577348, 1577349, 1577350, 1577351, 1577352, 1577354, 1577356, 1577357, 1577361, 1577365, 1577366, 1577369, 1577370, 1577371, 1577424, 1577427, 1577428, 1579367, 1588751, 1601953, 1601998, 1602014, 1617433, 1617653, 1617948, 1620466, 1629713, 1629714, 1629715, 1629716, 1629717, 1629718, 1629719, 1629720, 1629721, 1629722, 1635171, 1635294, 1639536, 1639588, 1640250, 1640251, 1640252, 1640514, 1644123, 1647716, 1648269, 1653063, 1655558, 1655574, 1655576, 1655578, 1655579, 1655581, 1655582, 1655585, 1655586, 1655595, 1655596, 1655598, 1655602, 1655609, 1655610, 1655618, 1655627, 1655630, 1655631, 1655633, 1657061, 1662476, 1679096, 1690483, 1690484, 1690485, 1700835, 1700836, 1701109, 1703093, 1703776, 1703777, 1719034, 1723640, 1727202, 1729450, 1732568, 1734399, 1734406, 1734407, 1734408, 1737567, 1739552, 1745252, 1766958, 1768196, 1770023, 1775998, 1779367, 1779368, 1779369, 1779370, 1779371, 1779372, 1779809, 1781242, 1783518, 1789227, 1792311, 1798814, 1798817, 1801895, 1801896, 1803813, 1803814, 1803913, 1805167, 1810840, 1811806, 1815591, 1826063, 1826750, 1827381, 1827382, 1827383, 1827384, 1827385, 1827386, 1827387, 1838287, 1844267, 1848566, 1849603, 1852704, 1852898, 1853301, 1855376, 1855399, 1856604, 1856605, 1862379, 1864862, 1871322, 1871323, 1871324, 1873700, 1883427, 1889899, 1892558, 1896227, 1897008, 1904640, 1905359, 1906665, 1913989, 1917428, 1917429, 1917430, 1917431, 1917432, 1917433, 1917434, 1917480, 1917481, 1917482, 1917483, 1918951, 1920109, 1920128, 1922228, 1922229, 1928334, 1931235, 1932700, 1940762, 1940790, 1941348, 1941349, 1953084, 1954174, 1955636, 1955772, 1955776, 1958816, 1958817, 1958821, 1958822, 1958823, 1968565, 1971620, 1982331, 1986601, 1986615, 1986616, 1986618, 1986619, 1986620, 1986621, 1986623, 1986624, 1986627, 1986631, 1986637, 1986643, 1986644, 1986645, 1986662, 1986665, 1986670, 1986671, 1986680, 1986681, 1986720, 1986722, 1986724, 1986728, 1986745, 1986758, 1986760, 1986765, 1986766, 1986768, 1986769, 1986772, 1986773, 1986788, 1986789, 1986790, 1986791, 1986792, 1986793, 1986840, 1986841, 1986842, 1986843, 1986845, 1986876, 1986877, 1987167, 1993870, 2006111, 2014174, 2014804, 2015203, 2015204, 2015205, 2015206, 2015798, 2016198, 2019410, 2021605, 2022662, 2024301, 2026734, 2032575, 2035215, 2039150, 2040999, 2053570, 2056121, 2064846, 2077315, 2079447, 2093740, 2109688, 2126330, 2137776, 2138094, 2138095, 2138096, 2138097, 
        );
        $c = array_merge($a, $b);
        return array_unique($c);
    }
    // ----------------------------------------------------------------- end TRAM-796 -----------------------------------------------------------------
    function start()
    {
        // 19   https://www.ncbi.nlm.nih.gov/Taxonomy/Browser/wwwtax.cgi?id=19      18  Pelobacter carbinolicus species accepted    2912; 5381
        /* test
        $taxID_info = self::get_taxID_nodes_info();
        $ancestry = self::get_ancestry_of_taxID(936556, $taxID_info); print_r($ancestry);
        // $ancestry = self::get_ancestry_of_taxID(503548, $taxID_info); print_r($ancestry);
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
        self::main(); //exit("\nstop muna\n");
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
            if(isset($final[$rec['tax_id']])) exit("\nInvestigate not unique tax_id in nodes.dmp\n");
            $final[$rec['tax_id']] = array("pID" => $rec['parent_tax_id'], 'r' => $rec['rank'], 'dID' => $rec['division_id']);
            // print_r($final); exit;
        }
        fclose($file);
        return $final;
    }
    private function main()
    {
        $filtered_ids = array();
        $taxon_refs = self::browse_citations();
        
        $taxID_info['xxx'] = array("pID" => '', 'r' => '', 'dID' => '');
        $taxID_info = self::get_taxID_nodes_info();
        
        $removed_branches = self::get_removed_branches_from_spreadsheet();
        /* additional IDs are taken from undefined_parents report after each connector run */
        $removed_branches[1296341] = '';
        $removed_branches[993557] = '';
        $removed_branches[1391733] = '';
        /* no need to add this, same result anyway
        $removed_branches[1181] = '';
        $removed_branches[1188] = '';
        $removed_branches[56615] = '';
        $removed_branches[59765] = '';
        $removed_branches[169066] = '';
        $removed_branches[242159] = '';
        $removed_branches[252598] = '';
        $removed_branches[797742] = '';
        $removed_branches[1776082] = '';
        */
        echo "\nMain processing...";
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
            /* start filtering: 
            1. Filter by division_id: Remove taxa where division_id in nodes.dmp is 7 (environmental samples) or 11 (synthetic and chimeric taxa) */
            if(in_array($taxID_info[$rec['tax_id']]['dID'], array(7,11))) {$filtered_ids[$rec['tax_id']] = ''; continue;}
            // Total rows: 2687427      Processed rows: 2609534

            /* 2. Filter by text string
            a. Remove taxa that have the string “environmental sample” in their scientific name. This will get rid of those environmental samples that don’t have the environmental samples division for some reason. */
            if(stripos($rec['name_txt'], "environmental sample") !== false) {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
            // Total rows: 2687427      Processed rows: 2609488
            
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
            
            if($rec['name_class'] == "scientific name") {
                $rank = $taxID_info[$rec['tax_id']]['r'];
                if(in_array($rank, array('species', 'no rank'))) {
                    if(stripos($rec['name_txt'], " sp.") !== false)      {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " aff.") !== false) {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " cf.") !== false)  {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                    elseif(stripos($rec['name_txt'], " nr.") !== false)  {$filtered_ids[$rec['tax_id']] = ''; continue;} //string is found
                }
            }
            elseif(in_array($rec['name_class'], $this->alternative_names)) {
                if(stripos($rec['name_txt'], " sp.") !== false)      {continue;} //string is found
                elseif(stripos($rec['name_txt'], " aff.") !== false) {continue;} //string is found
                elseif(stripos($rec['name_txt'], " cf.") !== false)  {continue;} //string is found
                elseif(stripos($rec['name_txt'], " nr.") !== false)  {continue;} //string is found
            }
            // Total rows: xxx      Processed rows: xxx
            
            if(in_array($rec['name_class'], array("blast name", "type material", "includes", "acronym", "genbank acronym"))) continue; //ignore these names
            
            /* 3. Remove branches 
            // if(in_array($rec['name_class'], array("scientific name", "common name", "genbank common name"))) {
                $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
                if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                    $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['tax_id']] = '';
                    continue;
                }
                if($old_id != $rec['tax_id']) $this->ctr = 1;
                else                          $this->ctr++;
                $old_id = $rec['tax_id'];
                
                if($val = @$taxon_refs[$rec['tax_id']]) $reference_ids = array_keys($val);
                else                                    $reference_ids = array();
                
                self::write_taxon($rec, $ancestry, $taxID_info[$rec['tax_id']], $reference_ids);
            // }
            */
            // Total rows: 2687427      Processed rows: 1648267
            $processed++;
        }
        fclose($file);
        
        // =================================================start 2
        echo "\nMain processing 2...";
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

            if(in_array($rec['name_class'], array("blast name", "type material", "includes", "acronym", "genbank acronym"))) continue; //ignore these names
            if(isset($filtered_ids[$rec['tax_id']])) continue;
            $parent_id = $taxID_info[$rec['tax_id']]['pID'];
            $parent_id = trim($parent_id);
            if(isset($filtered_ids[$parent_id])) continue;
            
            /* 3. Remove branches */
            $ancestry = self::get_ancestry_of_taxID($rec['tax_id'], $taxID_info);
            if(self::an_id_from_ancestry_is_part_of_a_removed_branch($ancestry, $removed_branches)) {
                $this->debug['taxon where an id in its ancestry is included among removed branches'][$rec['tax_id']] = '';
                continue;
            }
            
            if($val = @$taxon_refs[$rec['tax_id']]) $reference_ids = array_keys($val);
            else                                    $reference_ids = array();

            if($this->old_id != $rec['tax_id']) $this->ctr = 1;
            else {}
            $this->old_id = $rec['tax_id'];
            
            self::write_taxon($rec, $ancestry, $taxID_info[$rec['tax_id']], $reference_ids);
            
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
    } //end main()
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