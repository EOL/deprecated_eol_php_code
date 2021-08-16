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
    function Transformations_for_species_in_Eukaryota()
    {
        /*3. Transformations for species in Eukaryota
        species: where taxonRank=species
        The following instructions apply only to descendants of Eukaryota. Please ignore taxa that descend from Viruses, Bacteria, and Archaea.
        Remove the parenthetical subgenus from the canonicalName values of species. Leave them alone in scientificName values.
        Example: Tomopteris (Johnstonella) aloysii-sabaudiae Rosa, 1908 -> Tomopteris aloysii-sabaudiae Rosa, 1908 */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_1.tsv";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_2.tsv";

        $parent_ids = array('04150770-bb1b-4b6b-a33a-f92668772064'); //Eukaryota
        $this->parentID_taxonID = self::get_ids($source);
        echo "\nparentID_taxonID: [".count($this->parentID_taxonID)."]\n";
        
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendant_ids = $func->get_all_descendants_of_these_parents($parent_ids, $this->parentID_taxonID);
        echo "\ndescendant_ids: [".count($descendant_ids)."]\n";

        // /* ---------- re-orient $descendant_ids
        foreach($descendant_ids as $id) $Eukaryota_descendants[$id] = '';
        unset($descendant_ids);
        // ---------- */
        echo "\nEukaryota_descendants: [".count($Eukaryota_descendants)."]\n";
        
        /* start */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
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
                if(isset($Eukaryota_descendants[$rek['taxonID']])) {
                    $var = $rek['canonicalName'];
                    $arr = explode(" ", $var);
                    $second = $arr[1];
                    if($second[0] == "(" && substr($second, -1) == ")") {
                        unset($arr[1]); //remove 2nd word
                        $rek['canonicalName'] = implode(" ", $arr);
                        echo "\n[$new]\n";
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

        $parent_ids = array('04150770-bb1b-4b6b-a33a-f92668772064'); //Eukaryota
        $this->parentID_taxonID = self::get_ids($source);
        echo "\nparentID_taxonID: [".count($this->parentID_taxonID)."]\n";
        
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        $descendant_ids = $func->get_all_descendants_of_these_parents($parent_ids, $this->parentID_taxonID);
        echo "\ndescendant_ids: [".count($descendant_ids)."]\n";

        // /* ---------- re-orient $descendant_ids
        foreach($descendant_ids as $id) $Eukaryota_descendants[$id] = '';
        unset($descendant_ids);
        // ---------- */
        echo "\nEukaryota_descendants: [".count($Eukaryota_descendants)."]\n";
        
        /* start */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++;
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
            if($rek['taxonRank'] == 'subgenus') {
                if(isset($Eukaryota_descendants[$rek['taxonID']])) { // descendants of Eukaryota
                    $ret = $func2->parse_sourceinfo($sourceinfo); // print_r($ret); exit;
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
                    //=========================================================
                    //=========================================================
                    //=========================================================
                }
            }
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
}