<?php
namespace php_active_record;
/* connector: [dwh_postproc_TRAM_809.php] - TRAM-809 */
class DH_v1_1_taxonomicStatus_synonyms
{
    function __construct($folder) {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        if(Functions::is_production()) { //not used in eol-archive yet, might never be used anymore...
            /*
            $this->download_options = array(
                'cache_path'         => '/extra/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path = "/extra/other_files/DWH/TRAM-807/"; //download_wait_time is 1/4 of a second -> 1000000/4
            */
        }
        else {
            $this->download_options = array(
                'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
                'download_wait_time' => 250000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
            $this->main_path          = "/Volumes/AKiTiO4/d_w_h/TRAM-808/";
            $this->main_path_TRAM_809 = "/Volumes/AKiTiO4/d_w_h/TRAM-809/";
            // $this->file['new DH'] = $this->main_path."DH_v1_1_postproc/taxon.tab";
            // $this->file['old DH'] = $this->main_path."eoldynamichierarchywithlandmarks/taxa.txt";
        }
        $this->mysqli =& $GLOBALS['db_connection'];
    }
    function step_1()
    {
        echo "\nStart step 1...\n";
        $this->debug = array();
        // $this->retired_old_DH_taxonID = array();

        $file = $this->main_path."/new_DH_before_step4.txt"; //last DH output of TRAM-808
        $file = $this->main_path."/with_higherClassification/1558361160.txt"; //last DH output of TRAM-808 --> with higherClassification
        /* initialize info global ------------------------------------------------------------------------------*/
        require_library('connectors/DH_v1_1_postProcessing');
        $func = new DH_v1_1_postProcessing(1);
        /*We want to add taxonomicStatus to DH taxa based on the following rules:

        taxonomicStatus: accepted
        Apply to all descendants of the following taxa:
        Archaeplastida (EOL-000000097815)
        Cyanobacteria (EOL-000000000047)
        Fungi (EOL-000002172573) EXCEPT Microsporidia (EOL-000002172574)
        Gyrista (EOL-000000085512)
        Eumycetozoa (EOL-000000096158)
        Protosteliida (EOL-000000097604)
        Dinoflagellata (EOL-000000025794)

        taxonomicStatus: valid
        Apply to all other taxa including Microsporidia (EOL-000002172574), which is a descendant of Fungi.
        */
        self::get_taxID_nodes_info($file); //for new DH
        $children_of['Microsporidia'] = $func->get_descendants_of_taxID("EOL-000002172574", false, $this->descendants);

        $children_of['Archaeplastida'] = $func->get_descendants_of_taxID("EOL-000000097815", false, $this->descendants);
        $children_of['Cyanobacteria'] = $func->get_descendants_of_taxID("EOL-000000000047", false, $this->descendants);
        $children_of['Fungi'] = $func->get_descendants_of_taxID("EOL-000002172573", false, $this->descendants);
        $children_of['Gyrista'] = $func->get_descendants_of_taxID("EOL-000000096158", false, $this->descendants);
        $children_of['Eumycetozoa'] = $func->get_descendants_of_taxID("EOL-000000096158", false, $this->descendants);
        $children_of['Protosteliida'] = $func->get_descendants_of_taxID("EOL-000000097604", false, $this->descendants);
        $children_of['Dinoflagellata'] = $func->get_descendants_of_taxID("EOL-000000025794", false, $this->descendants);
        // echo "\nMicrosporidia: ".count($children_of['Microsporidia'])."\n";
        // echo "\nArchaeplastida: ".count($children_of['Archaeplastida'])."\n";
        // echo "\nCyanobacteria: ".count($children_of['Cyanobacteria'])."\n";
        // echo "\nFungi: ".count($children_of['Fungi'])."\n";
        // echo "\nGyrista: ".count($children_of['Gyrista'])."\n";
        // echo "\nEumycetozoa: ".count($children_of['Eumycetozoa'])."\n";
        // echo "\nProtosteliida: ".count($children_of['Protosteliida'])."\n";
        // echo "\nDinoflagellata: ".count($children_of['Dinoflagellata'])."\n";
        unset($this->descendants);
        /* loop new DH -----------------------------------------------------------------------------------------*/
        $file_append = $this->main_path_TRAM_809."/new_DH_taxonStatus.txt"; $WRITE = fopen($file_append, "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($file) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            $row = explode("\t", $line);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields);
                $fields[] = 'taxonomicStatus'; //print_r($fields);
                fwrite($WRITE, implode("\t", $fields)."\n");
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            //------------------------------------------------------start taxonomicStatus
            if(in_array($rec['taxonID'], $children_of['Microsporidia'])) $rec['taxonomicStatus'] = 'valid';
            if(!@$rec['taxonomicStatus']) {
                if(in_array($rec['taxonID'], $children_of['Archaeplastida'])) $rec['taxonomicStatus'] = 'accepted';
                if(in_array($rec['taxonID'], $children_of['Cyanobacteria'])) $rec['taxonomicStatus'] = 'accepted';
                if(in_array($rec['taxonID'], $children_of['Fungi'])) $rec['taxonomicStatus'] = 'accepted';
                if(in_array($rec['taxonID'], $children_of['Gyrista'])) $rec['taxonomicStatus'] = 'accepted';
                if(in_array($rec['taxonID'], $children_of['Eumycetozoa'])) $rec['taxonomicStatus'] = 'accepted';
                if(in_array($rec['taxonID'], $children_of['Protosteliida'])) $rec['taxonomicStatus'] = 'accepted';
                if(in_array($rec['taxonID'], $children_of['Dinoflagellata'])) $rec['taxonomicStatus'] = 'accepted';
            }
            if(!@$rec['taxonomicStatus']) $rec['taxonomicStatus'] = 'valid';
            //------------------------------------------------------end taxonomicStatus
            print_r($rec); exit;
            /*
            */
            /* start writing */
            $save = array();
            foreach($fields as $head) $save[] = $rec[$head];
            fwrite($WRITE, implode("\t", $save)."\n");
        }
        fclose($WRITE);
        Functions::start_print_debug($this->debug, $this->resource_id."_step1");
    }
    private function get_taxID_nodes_info($txtfile)
    {
        $this->taxID_info = array(); $this->descendants = array(); //initialize global vars
        $i = 0;
        foreach(new FileIterator($txtfile) as $line_number => $line) {
            $i++; if(($i % 200000) == 0) echo "\n".number_format($i)." ";
            if($i == 1) $line = strtolower($line);
            $row = explode("\t", $line); // print_r($row);
            if($i == 1) {
                $fields = $row;
                $fields = array_filter($fields); //print_r($fields);
                continue;
            }
            else {
                if(!@$row[0]) continue;
                $k = 0; $rec = array();
                foreach($fields as $fld) {
                    $rec[$fld] = @$row[$k];
                    $k++;
                }
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nstopx\n");
            /*Array(
                [taxonid] => EOL-000000000001
                [source] => trunk:1bfce974-c660-4cf1-874a-bdffbf358c19,NCBI:1
                [furtherinformationurl] => 
                [parentnameusageid] => 
                [scientificname] => Life
                [taxonrank] => clade
                [taxonremarks] => 
                [datasetid] => trunk
                [canonicalname] => Life
                [eolid] => 2913056
                [eolidannotations] => 
            )*/
            // $this->taxID_info[$rec['uid']] = array("pID" => $rec['parent_uid'], 'r' => $rec['rank'], 'n' => $rec['name'], 's' => $rec['sourceinfo'], 'f' => $rec['flags']); //used for ancesty and more
            $this->descendants[$rec['parentnameusageid']][$rec['taxonid']] = ''; //used for descendants (children)
        }
    }
    
}
?>
