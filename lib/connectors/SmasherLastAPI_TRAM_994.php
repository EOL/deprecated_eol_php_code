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
        ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
        Change all em-dashes (–) in the scientificName, canonicalName, and scientificNameAuthorship fields to en-dashes .
        Example: Chetryrus l–notatus -> Chetryrus l-notatus

        Remove all single quotes (‘) from canonicalName values, but leave them in scientificName and scientificNameAuthorship values.
        Examples:
        'Desertella' yichangensis -> Desertella yichangensis
        Psalidura d'urvillei -> Psalidura durvillei

        Replace umlauts and other letter-based special characters (ä,á,å,ç,ë,é,è,ï,í,ñ,ö,ô,ü,û) in canonicalName values with plain letters (a,c,e,i,o,u). Leave them alone in scientificName and scientificNameAuthorship values.
        Example: Stromboscerus schüppeli -> Stromboscerus schuppeli */
        $source      = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/DH_2_1_Jul26/taxon.tab";
        $destination = "/Volumes/AKiTiO4/d_w_h/last_smasher/TRAM_994/taxonomy_1.tsv";
        /* start */
        $WRITE = Functions::file_open($destination, "w");
        $i = 0;
        foreach(new FileIterator($source) as $line => $row) { $i++; if(($i % 200000) == 0) echo "\n".number_format($i);
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
            
            fwrite($WRITE, implode("\t", $rek) . "\n"); //saving
        }
        fclose($WRITE);
        $out = shell_exec("wc -l ".$source); echo "\nsource: $out\n";
        $out = shell_exec("wc -l ".$destination); echo "\ndestination: $out\n";
    }
}