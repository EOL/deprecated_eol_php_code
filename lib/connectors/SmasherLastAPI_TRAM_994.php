<?php
namespace php_active_record;
/* */
class SmasherLastAPI_TRAM_994
{
    function __construct($folder)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->debug = array();
    }
    function Transformations_for_all_taxa()
    {
        /* 1. Transformations for all taxa
        ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/DH_2_1_Jul26/taxon.tab";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_1.tsv";
        /* start */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
            if(!$row) continue;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                fwrite($WRITE, implode("\t", $fields) . "\n");
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\n-end-\n");
            /*Array(
                [taxonID] => 4038af35-41da-469e-8806-40e60241bb58
                [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [furtherInformationURL] => 
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => no rank
                [taxonomicStatus] => accepted
                [taxonRemarks] => 
                [datasetID] => trunk
                [canonicalName] => Life
                [EOLid] => 
                [EOLidAnnotations] => 
                [Landmark] => 
            )*/
            /* Change all em-dashes (–) in the scientificName, canonicalName, and scientificNameAuthorship fields to en-dashes .
            Example: Chetryrus l–notatus -> Chetryrus l-notatus */
            $rek['scientificName'] = str_replace("–", "-", $rek['scientificName']);
            $rek['canonicalName'] = str_replace("–", "-", $rek['canonicalName']);
            /* Remove all single quotes (‘) from canonicalName values, but leave them in scientificName and scientificNameAuthorship values.
            Examples:
            'Desertella' yichangensis -> Desertella yichangensis
            Psalidura d'urvillei -> Psalidura durvillei */
            $rek['canonicalName'] = str_replace("'", "", $rek['canonicalName']);
            /* Replace umlauts and other letter-based special characters (ä,á,å,ç,ë,é,è,ï,í,ñ,ö,ô,ü,û) in canonicalName 
            values with plain letters (a,c,e,i,o,u). Leave them alone in scientificName and scientificNameAuthorship values.
            Example: Stromboscerus schüppeli -> Stromboscerus schuppeli */
            $rek['canonicalName'] = str_replace(array("ä","á","å"), "a", $rek['canonicalName']);
            $rek['canonicalName'] = str_replace(array("ë","é","è"), "e", $rek['canonicalName']);
            $rek['canonicalName'] = str_replace(array("ï","í"), "i", $rek['canonicalName']);
            $rek['canonicalName'] = str_replace(array("ö","ô"), "o", $rek['canonicalName']);
            $rek['canonicalName'] = str_replace(array("ü","û"), "u", $rek['canonicalName']);
            $rek['canonicalName'] = str_replace(array("ç"), "c", $rek['canonicalName']);
            $rek['canonicalName'] = str_replace(array("ñ"), "n", $rek['canonicalName']);
            
            /*2. Transformations for all infraspecifics
            infraspecifics: where taxonRank=infraspecies|subspecies|variety|form|subvariety
            Remove qualifiers from the canonicalName values of infraspecific taxa: f., sp., ssp., subsp., var.
            Leave them alone in scientificName values.
            Examples:
            Nitzschia palea var. debilis -> Nitzschia palea debilis
            Tolypothrix tenuis f. terrestris -> Tolypothrix tenuis terrestris*/
            $infra_ranks = array("infraspecies", "subspecies", "variety", "form", "subvariety");
            if(in_array(strtolower($rek['taxonRank']), $infra_ranks)) {
                $rek['canonicalName'] = str_ireplace(array(" f.", " sp.", " ssp.", " subsp.", " var."), "", $rek['canonicalName']);
                $rek['canonicalName'] = Functions::remove_whitespace($rek['canonicalName']);
            }
            
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    function generate_descendants_for_Viruses_Bacteria_Archaea()
    {
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_1.tsv";
        $this->parentID_taxonID = self::get_ids($source);
        echo "\nparentID_taxonID: [".count($this->parentID_taxonID)."]\n";
        
        $groups['Viruses'] = array('taxonID' => "6f8a846c-9528-42dc-85e4-55527bf9b8d5");
        $groups['Bacteria'] = array('taxonID' => "85e36b59-4fb5-4d65-a449-74c06bf75a86");
        $groups['Archaea'] = array('taxonID' => "aa16d8f2-de4a-41bd-b1e2-893b2d2a9013");
        foreach($groups as $group => $g) {
            echo "\n-----\n[$group] [".$g['taxonID']."]\n";
            //#####################################################################################################
            $descendants_file = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/".$group."_descendants.txt";
            if(file_exists($descendants_file)) {
                $descendant_ids = file($descendants_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $descendant_ids = array_map('trim', $descendant_ids);
                echo "\n$group: [".count($descendant_ids)."]\n";
            }
            else {
                $parent_ids = array($g['taxonID']);
                echo("\ndescendants_file does not exist [$descendants_file]\nCreating now...\n");
                require_library('connectors/PaleoDBAPI_v2');
                $func = new PaleoDBAPI_v2("");
                $descendant_ids = $func->get_all_descendants_of_these_parents($parent_ids, $this->parentID_taxonID);
                echo "\ndescendant_ids: [".count($descendant_ids)."]\n";
                $SULAT = Functions::file_open($descendants_file, "w");
                foreach($descendant_ids as $id) fwrite($SULAT, $id . "\n"); //saving
                fclose($SULAT);
                $out = shell_exec("wc -l ".$descendants_file); echo "\ndescendants_file rows: $out\n";
            }
            //#####################################################################################################
        }
    }
    private function get_non_eukaryote_descendants()
    {   
        $final = array();
        $groups = array('Viruses', 'Bacteria', 'Archaea');
        foreach($groups as $group) {
            echo "\n-----\n[$group]\n";
            $descendants_file = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/".$group."_descendants.txt";
            if(file_exists($descendants_file)) {
                $descendant_ids = file($descendants_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $descendant_ids = array_map('trim', $descendant_ids);
                echo "\n$group: [".count($descendant_ids)."]\n";
                $final = array_merge($final, $descendant_ids);
            }
            else exit("\nERROR: descendants file not yet generated!\n");
        }
        // /* ---------- re-orient $descendant_ids
        foreach($final as $id) $descendants[$id] = '';
        // ---------- */
        echo "\nnon_eukaryote_descendants: ".count($descendants)."\n";
        return $descendants;
    }
    function Transformations_for_species_in_Eukaryota()
    {
        /*3. Transformations for species in Eukaryota
        species: where taxonRank=species
        The following instructions apply only to descendants of Eukaryota. Please ignore taxa that descend from Viruses, Bacteria, and Archaea.
        Remove the parenthetical subgenus from the canonicalName values of species. Leave them alone in scientificName values.
        Example: Tomopteris (Johnstonella) aloysii-sabaudiae Rosa, 1908 -> Tomopteris aloysii-sabaudiae Rosa, 1908 */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_1.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_2.tsv";

        //#####################################################################################################
        $non_eukaryote_descendants = self::get_non_eukaryote_descendants();
        echo "\n non_eukaryote_descendants: [".count($non_eukaryote_descendants)."]\n"; //exit("\nelix\n");
        //#####################################################################################################
        
        /* start */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            if(!$row) continue;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                fwrite($WRITE, implode("\t", $fields) . "\n");
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend4\n");
            /*Array(
                [taxonID] => 4038af35-41da-469e-8806-40e60241bb58
                [parentNameUsageID] => 
                [taxonRank] => no rank
                [canonicalName] => Life
                ...
            )*/
            if($rek['taxonRank'] == 'species') {
                if(!isset($non_eukaryote_descendants[$rek['taxonID']])) {
                    $var = $rek['canonicalName'];
                    $arr = explode(" ", $var);
                    $second = $arr[1];
                    if($second[0] == "(" && substr($second, -1) == ")") {
                        unset($arr[1]); //remove 2nd word
                        $rek['canonicalName'] = implode(" ", $arr);
                    }
                }
            }
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    private function get_ids($source)
    {
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            if(!$row) continue;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend5\n");
            /*Array(
                [taxonID] => 4038af35-41da-469e-8806-40e60241bb58
                [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [furtherInformationURL] => 
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 
                ...
            )*/
            $parent_id = @$rek["parentNameUsageID"];
            $taxon_id = @$rek["taxonID"];
            if($parent_id && $taxon_id) $final[$parent_id][] = $taxon_id;
        }
        return $final;
    }

    function Transformations_for_subgenera_in_Eukaryota()
    {
        /*4. Transformations for subgenera in Eukaryota
        genus: where taxonRank=subgenus

        The following instructions apply only to descendants of Eukaryota. Please ignore taxa that descend from Viruses, Bacteria, and Archaea.

        The canonicalName value of all subgenera in Eukaryota should be of the following form:
        Genusname subgen. Subgenusname
        Example: Cytheridea (Leptocytheridea) Stephenson, 1937 -> Cytheridea subgen. Leptocytheridea

        This is currently true for many of our sugenera, but there are a few that have one of two different forms, either:

        Genusname (Subgenusname)
        Example: Acromastigum (Inaequilatera) (Schiffn.) Grolle -> Acromastigum (Inaequilatera)
        (subgenera from NCBI, ITIS & MAM)

        or

        Subgenusname
        Example: Coeloplana (Benthoplana) Fricke & Plante, 1971 -> Benthoplana
        (some subgenera from WOR)

        We want to change all subgenus canonicalName values for Eukaryota to the Genusname subgen. Subgenusname form.

        For canonicals of the form Genusname (Subgenusname), remove the parentheses and insert the subgen. term.
        Example: Acromastigum (Inaequilatera) (Schiffn.) Grolle -> Acromastigum subgen. Inaequilatera

        For canonicals of the form Subgenusname, grab the genus (first word) and subgenus (second word in parentheses) from the scientificName value 
        and insert the subgen. term between them:
        Example: Coeloplana (Benthoplana) Fricke & Plante, 1971 -> Coeloplana subgen. Benthoplana */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_2.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_3.tsv";

        require_library('connectors/SmasherLastAPI');
        $func2 = new SmasherLastAPI(false);

        //#####################################################################################################
        $non_eukaryote_descendants = self::get_non_eukaryote_descendants();
        echo "\n non_eukaryote_descendants: [".count($non_eukaryote_descendants)."]\n";
        //#####################################################################################################
        
        /* start */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            if(!$row) continue;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                fwrite($WRITE, implode("\t", $fields) . "\n");
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend4\n");
            /*Array(
                [taxonID] => 4038af35-41da-469e-8806-40e60241bb58
                [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => no rank
                [canonicalName] => Life
            )*/
            if($rek['taxonRank'] == 'subgenus') {
                if(!isset($non_eukaryote_descendants[$rek['taxonID']])) {
                    $ret = $func2->parse_sourceinfo($rek['source']); //print_r($ret); exit;
                    /*Array(
                        [source_name] => trunk
                        [taxon_id] => 4038af35-41da-469e-8806-40e60241bb58
                    )*/
                    $source_name = $ret['source_name'];
                    //========================================================= (subgenera from NCBI, ITIS & MAM)
                    if(in_array($source_name, array("NCBI", "ITIS", "MAM"))) {
                        $var = $rek['canonicalName']; //Acromastigum (Inaequilatera) (Schiffn.) Grolle -> Acromastigum subgen. Inaequilatera
                        $arr = explode(" ", $var);
                        $second = $arr[1];
                        if($second[0] == "(" && substr($second, -1) == ")") {
                            $second = str_replace("(", "", $second);
                            $second = str_replace(")", "", $second);
                            $rek['canonicalName'] = $arr[0]. " subgen. " . $second;
                        }
                    }
                    elseif(in_array($source_name, array("WOR"))) { //same routine above, except source is scientificName instead of canonicalName
                        $var = $rek['scientificName']; //Coeloplana (Benthoplana) Fricke & Plante, 1971 -> Coeloplana subgen. Benthoplana
                        $arr = explode(" ", $var);
                        $second = $arr[1];
                        if($second[0] == "(" && substr($second, -1) == ")") {
                            $second = str_replace("(", "", $second);
                            $second = str_replace(")", "", $second);
                            $rek['canonicalName'] = $arr[0]. " subgen. " . $second;
                        }
                    }
                    else {
                        /* good debug; but these records now follow the format: "Name subgen. Name"
                        $this->debug['uninitialized source'][$source_name] = '';
                        $this->debug['uninitialized_source'][$source_name][$rek['canonicalName']] = '';
                        */
                    }
                }
            }
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        if($this->debug) print_r($this->debug);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    function Remove_taxa_with_malformed_canonicalName_values()
    {   
        $this->REPORT = Functions::file_open("/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/removed_taxa.tsv", "w");
        $headers = array('taxonID', 'scientificName', 'canonicalName', 'taxonRank', 'reason');
        fwrite($this->REPORT, implode("\t", $headers) . "\n"); //saving
        
        /*B. Remove taxa with malformed canonicalName values
        The following instructions apply only to descendants of Eukaryota. Please ignore taxa that descend from Viruses, Bacteria, and Archaea.
        */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_3.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_4.tsv";

        //#####################################################################################################
        $non_eukaryote_descendants = self::get_non_eukaryote_descendants();
        echo "\n non_eukaryote_descendants: [".count($non_eukaryote_descendants)."]\n";
        //#####################################################################################################

        /* start */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            if(!$row) continue;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                fwrite($WRITE, implode("\t", $fields) . "\n");
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend4\n");
            /*Array(
                [taxonID] => 4038af35-41da-469e-8806-40e60241bb58
                [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                [parentNameUsageID] => 
                [scientificName] => Life
                [taxonRank] => no rank
                [canonicalName] => Life
            )*/

            // /* manual OCR issue
            $rek['canonicalName'] = str_ireplace("Nasoona indianа", "Nasoona indiana", $rek['canonicalName']);
            $rek['canonicalName'] = str_ireplace("Carpelimus rougemoпti", "Carpelimus rougemonti", $rek['canonicalName']);
            // */
            
            $canonical = $rek['canonicalName'];
            $rank = $rek['taxonRank'];
            if(!isset($non_eukaryote_descendants[$rek['taxonID']])) {
                
                /*
                Canonical names for species (taxonRank=species) should generally be of the form:
                Aus bus – a capitalized genus name and a lower case epithet, with only plain letters and a couple of special characters 
                and numbers allowed, see below.

                Canonical names for infraspecifics (taxonRank=infraspecies|subspecies|variety|form|subvariety) should generally be of the form:
                Aus bus cus – a capitalized genus name and two lower case epithets, with only plain letters and a couple of special characters 
                and numbers allowed, see below.

                Remove species and infraspecifics (and their children, if any) with canonicalName values that violate the basic patterns 
                for the indicated rank, with the following qualifications/exceptions:
                
                1. Ignore names of hybrids, which are recognized by the following patterns:

                [ x ] (space followed by the letter x followed by a space) anywhere in the scientificName
                Example: Frustulia crassinervia x Frustulia saxonica

                × special character at the beginning of an epithet:
                Example: Melampsora ×columbiana

                2. Periods and dashes [-.] are allowed in both the genus name and the epithets.
                Examples of permitted canonicals: Frustulia lacus-templi, Pseudo-nitzschia pungiformis, Macromitrium st.-johnii

                3. The genus name can have 2 capitalized parts if separated by a hyphen.
                Example: Milne-Edwardsia carneata

                4. Numbers are allowed in epithets, but only as the first character(s) of the epithet and 
                    only if separated from the rest of the epithet by a dash.
                Examples of permitted canonicals:
                Batozonus 4-punctatus
                Cholus 23-maculatus

                Examples of malformed canonicals that should result in deletion of the taxon:

                ***Question marks, commas, underscores or brackets are not allowed in any part of the canonicalName:

                Cossonus lacupros?                      Catoptes interruptusfabricius,1781
                Daphnia sarsi_2                         [Peronospora] crispula
                [Peronospora] iberidis                  [Peronospora] sisymbrii-officinalis
                [Peronospora] lepidii-sativi            [Peronospora] diplotaxidis
                [Myrmecia] bisecta                      [Kirchneriella] contorta elegans
                [Pediastrum] simplex pseudoglabrum

                ***Numbers are only allowed if they are the first characters of the epithet:

                Chaunacanthid 217               Brachinus marginipennis2
                Harpalus bicolor2               Harpalus acuminatus2
                Agonum ruficorne2               Agonum thoracicum2
                Carabus croesus2                Omophron suturale2
                Halimeda taenicola.2            Polysiphonia sertularioides-1
                Polysiphonia sertularioides-3   Polysiphonia sertularioides-2

                ***Species names must have no more than two words:

                Pseudoceros latissimus type a           Pseudoceros maximus-type a
                Ingolfiella kapuri coineau              Cleonus punctiventris tenebrosus
                Psepholax mac leayi                 Arachnopus gutt lifer
                Hilipus de geeri                    Balanobius crux minor
                Rhynchaenus saltator alni           Rhynchaenus saltator salicis
                Rhynchaenus saltator ulmi           Polydrosus van volxemi
                Prodioctes de haani                 Nassophasis foveata gunturi
                Spenophorus de haani                Anapygus de haani
                Cyrtophora citricola obscura        Stolonodendrum parasiticum parasiticum
                Phyllophorus celer koehler          Anemonia milne edwardsii

                Please report removed taxa, so I can check to make sure we didn’t remove anything important. */
                
                $infraspecific_ranks = array("infraspecies", "subspecies", "variety", "form", "subvariety");
                if($rank == 'species') {
                    if(self::is_name_hybrid($canonical)) {}
                    else {
                        // Species names must have no more than two words
                        $words = explode(" ", $canonical);
                        if(count($words) > 2) {self::save_rec($rek, "species with > 2 words"); continue;} //save rec
                    }
                }
                
                // /* Numbers are only allowed if they are the first characters of the epithet:
                $words = explode(" ", $canonical);
                if(self::get_numbers_from_string($words[0])) {self::save_rec($rek, "genus has a number"); continue;} //save rec --- first word (genus) has a number

                if($second = @$words[1]) {
                    if($numbers = self::get_numbers_from_string($second)) { //2nd word has number(s)
                        if(count($numbers) > 1) {self::save_rec($rek, "many numbers in string"); continue;} //save rec --- more than 1 number in string
                        else {
                            $needle = $numbers[0]."-";
                            if(substr($second,0,strlen($needle)) == $needle) {} //ignore --- e.g. "Cholus 23-maculatus"
                            else {self::save_rec($rek, "with numbers"); continue;} //save rec --- e.g. "Harpalus bicolor2"
                        }
                    }
                }
                // */
                
                // /* ----------
                // Question marks, commas, underscores or brackets are not allowed in any part of the canonicalName:
                $remove = array("?", ",", "_", "[", "]");
                $cont = true;
                foreach($remove as $char) {
                    if(stripos($canonical, $char) !== false) $cont = false; //string found
                }
                if(!$cont) {
                    if($rek['taxonID'] == "329bdd10-4cd9-4deb-9ef4-19f068936f66") {}
                    else {
                        self::save_rec($rek, "? , _ [ ]"); continue;
                    }
                } //save rec
                // ---------- */
                
                //=========================
                $words = explode(" ", $canonical);
                $infraspecific_ranks = array("infraspecies", "subspecies", "variety", "form", "subvariety");
                if($rank == 'species') {
                    /*Canonical names for species (taxonRank=species) should generally be of the form:
                    Aus bus – a capitalized genus name and a lower case epithet, with only plain letters and a couple of special characters 
                    and numbers allowed, see below.*/
                    if(self::is_name_hybrid($canonical)) {}
                    else {
                        if(self::get_numbers_from_string($canonical)) {}
                        else {
                            $words[1] = str_replace(array("-","."), "", $words[1]);
                            if(self::first_char_is_capital($words[0]) && ctype_lower($words[1])) {}
                            else {self::save_rec($rek, "species pattern"); continue;} //save rec
                        }
                    }
                }
                elseif(in_array($rank, $infraspecific_ranks)) {
                    /*Canonical names for infraspecifics (taxonRank=infraspecies|subspecies|variety|form|subvariety) should generally be of the form:
                    Aus bus cus – a capitalized genus name and two lower case epithets, with only plain letters and a couple of special characters 
                    and numbers allowed, see below.*/
                    if(self::is_name_hybrid($canonical)) {}
                    else {
                        if(self::get_numbers_from_string($canonical)) {}
                        else {
                            $words[1] = str_replace(array("-","."), "", $words[1]);
                            $words[2] = str_replace(array("-","."), "", @$words[2]);
                            if(self::first_char_is_capital($words[0]) && ctype_lower($words[1]) && ctype_lower($words[2])) {}
                            else {self::save_rec($rek, "infraspecifics pattern"); continue;} //save rec
                        }
                    }
                }
                //=========================
                
            }
            //=====================================================================================
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        if($this->debug) print_r($this->debug);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
        fclose($this->REPORT);
    }
    
    function Delete_descendants_of_taxa_from_report()
    {
        /* Delete descendants of taxa from removed_taxa.tsv */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_4.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_5.tsv";

        $parent_ids = self::get_taxonIDs_from_report();
        $this->parentID_taxonID = self::get_ids($source);
        
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendant_ids = $func->get_all_descendants_of_these_parents($parent_ids, $this->parentID_taxonID);
        // print_r($descendant_ids);
        $exclude_uids = array_merge($parent_ids, $descendant_ids);
        echo "\nparent_ids: ".count($parent_ids)."\n";
        echo "\ndescendant_ids: ".count($descendant_ids)."\n";
        echo "\nexclude_uids: ".count($exclude_uids)."\n";
        
        /* start final deletion */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
            if(!$row) continue;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                fwrite($WRITE, implode("\t", $fields) . "\n");
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend4\n");
            /*Array(
                [taxonID] => 4038af35-41da-469e-8806-40e60241bb58
                [source] => trunk:4038af35-41da-469e-8806-40e60241bb58,NCBI:1
                ...
            )*/
            if(in_array($rek['taxonID'], $exclude_uids)) continue;
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
    private function get_taxonIDs_from_report()
    {
        $report = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/removed_taxa.tsv";
        $i = 0;
        foreach(new FileIterator($report) as $line => $row) { $i++;
            if(!$row) continue;
            $rec = explode("\t", $row);
            if($i == 1) {
                $fields = $rec;
                continue;
            }
            else {
                $rek = array(); $k = 0;
                foreach($fields as $fld) {
                    if($fld) $rek[$fld] = @$rec[$k];
                    $k++;
                }
                $rek = array_map('trim', $rek);
            }
            // print_r($rek); exit("\nend eli\n");
            /*Array(
                [taxonID] => -239709
                [scientificName] => Polysiphonia sertularioides-1
                [canonicalName] => Polysiphonia sertularioides-1
                [taxonRank] => species
                [reason] => with numbers
            )*/
            $final[$rek['taxonID']] = '';
        }
        return array_keys($final);
    }
    function investigate_descendants_of_removed_taxa() //a utility
    {
        $source = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_4.tsv";
        $parent_ids = self::get_taxonIDs_from_report();
        echo "\nparent_ids: ".count($parent_ids)."\n";
        $this->parentID_taxonID = self::get_ids($source);

        $WRITE = Functions::file_open("/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/investigate_descendants.tsv", "w");
        foreach($parent_ids as $parent_id) {
            require_library('connectors/PaleoDBAPI_v2');
            $func = new PaleoDBAPI_v2("");
            $descendant_ids = $func->get_all_descendants_of_these_parents(array($parent_id), $this->parentID_taxonID);
            fwrite($WRITE, "\n----------\n[$parent_id] descendant_ids: ".count($descendant_ids)."\n"); //saving
            foreach($descendant_ids as $id) fwrite($WRITE, $id . "\n"); //saving
        }
        fclose($WRITE);
    }
    private function is_name_hybrid($name)
    {   /*
        [ x ] (space followed by the letter x followed by a space) anywhere in the scientificName
        Example: Frustulia crassinervia x Frustulia saxonica */
        if(stripos($name, " x ") !== false) return true;

        /* × special character at the beginning of an epithet:
        Example: Melampsora ×columbiana */
        $words = explode(" ", $name);
        if(self::first_char_is_capital($words[0])) {
            if($second = @$words[1]) {
                if(substr($second,0,strlen("×")) == "×") return true;
            }
            if($third = @$words[2]) {
                if(substr($third,0,strlen("×")) == "×") return true;
            }
        }
        return false;
    }
    private function first_char_is_capital($str)
    {
        $str = trim($str);
        if(ctype_upper($str[0])) return true;
    }
    private function get_numbers_from_string($str)
    {
        if(preg_match_all('/\d+/', $str, $a)) return $a[0];
    }
    private function save_rec($rek, $reason)
    {
        $rec['taxonID'] = $rek['taxonID'];
        $rec['scientificName'] = $rek['scientificName'];
        $rec['canonicalName'] = $rek['canonicalName'];
        $rec['taxonRank'] = $rek['taxonRank'];
        $rec['reason'] = $reason;
        fwrite($this->REPORT, implode("\t", $rec) . "\n"); //saving
    }
}