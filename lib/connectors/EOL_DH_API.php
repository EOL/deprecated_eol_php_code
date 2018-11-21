<?php
namespace php_active_record;
/* connector: No specific connector. But now used in EOLv2MetadataAPI.php */

class EOL_DH_API
{
    function __construct()
    {
        $this->EOL_DH = "http://localhost/cp/summary%20data%20resources/DH/eoldynamichierarchywithlandmarks.zip";
    }
    private function extract_DH()
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->EOL_DH, "taxa.txt", array('timeout' => 60*10, 'expire_seconds' => 60*60*24*25)); //expires in 25 days
        $tables['taxa'] = 'taxa.txt';
        $paths['tables'] = $tables;
        return $paths;
    }
    private function prep_DH()
    {
        if(Functions::is_production()) {
            if(!($info = self::extract_DH())) return;
            print_r($info);
            // $this->info_path = $info;
        }
        else { //local development only
            /*
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/EOL_dynamic_hierarchy/',   //for eoldynamichierarchyv1.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_52635/',
                          'tables' => Array('taxa' => 'taxa.txt')); */
            $info = Array('archive_path' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',                         //for eoldynamichierarchywithlandmarks.zip
                          'temp_dir' => '/Library/WebServer/Documents/eol_php_code/tmp/dir_77578/',
                          'tables' => Array('taxa' => 'taxa.txt'));
            // $this->info_path = $info;
        }
        return $info;
    }
    public function parse_DH()
    {
        $info = self::prep_DH();
        $i = 0;
        foreach(new FileIterator($info['archive_path'].$info['tables']['taxa']) as $line_number => $line) {
            $line = explode("\t", $line); $i++;
            if($i == 1) $fields = $line;
            else {
                if(!$line[0]) break;
                $rec = array(); $k = 0;
                foreach($fields as $fld) {
                    $rec[$fld] = $line[$k]; $k++;
                }
                // print_r($rec); exit;
                /*Array(
                    [taxonID] => -168611
                    [acceptedNameUsageID] => -168611
                    [parentNameUsageID] => -105852
                    [scientificName] => Torpediniformes
                    [taxonRank] => order
                    [source] => trunk:59edf7f2-b792-4351-9f37-562dd522eeca,WOR:10215,gbif:881
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 8898
                    [EOLidAnnotations] => multiple;
                    [Landmark] => 1
                )
                Array(
                    [taxonID] => 93302
                    [acceptedNameUsageID] => 93302
                    [parentNameUsageID] => -1
                    [scientificName] => Cellular Organisms
                    [taxonRank] => clade
                    [source] => trunk:b72c3e8e-100e-4e47-82f6-76c3fd4d9d5f
                    [taxonomicStatus] => accepted
                    [canonicalName] => 
                    [scientificNameAuthorship] => 
                    [scientificNameID] => 
                    [taxonRemarks] => 
                    [namePublishedIn] => 
                    [furtherInformationURL] => 
                    [datasetID] => trunk
                    [EOLid] => 6061725
                    [EOLidAnnotations] => manual;
                    [Landmark] => 
                )
                */
                /* debugging
                // if($rec['EOLid'] == 3014446) {print_r($rec); exit;}
                // if($rec['taxonID'] == 93302) {print_r($rec); exit;}
                // if($rec['Landmark']) print_r($rec);
                if(in_array($rec['EOLid'], Array(7687,3014522,42399419,32005829,3014446,2908256))) print_r($rec);
                */
                $this->EOL_2_DH[$rec['EOLid']] = $rec['taxonID'];
                $this->DH_2_EOL[$rec['taxonID']] = $rec['EOLid'];
                $this->parent_of_taxonID[$rec['taxonID']] = $rec['parentNameUsageID'];
                $this->landmark_value_of[$rec['EOLid']] = $rec['Landmark'];
                if($rec['taxonRank'] == 'family') $this->is_family[$rec['EOLid']] = '';
            }
        }
        /* may not want to force assign this:
        $this->DH_2_EOL[93302] = 6061725; //Biota - Cellular Organisms
        */
        
        // remove temp dir
        // recursive_rmdir($info['temp_dir']);
        // echo ("\n temporary directory removed: " . $info['temp_dir']);
    }
    private function get_ancestry_via_DH($page_id, $landmark_only = true)
    {
        $final = array(); $final2 = array();
        $taxonID = @$this->EOL_2_DH[$page_id];
        if(!$taxonID) {
            echo "\nThis page_id [$page_id] is not found in DH.\n";
            return array();
        }
        while(true) {
            if($parent = @$this->parent_of_taxonID[$taxonID]) $final[] = $parent;
            else break;
            $taxonID = $parent;
        }
        $i = 0;
        foreach($final as $taxonID) {
            // echo "\n$i. [$taxonID] => ";
            if($EOLid = @$this->DH_2_EOL[$taxonID]) {
                /* new strategy: using Landmark value   ver 1
                if($this->landmark_value_of[$EOLid]) $final2[] = $EOLid; */

                if($landmark_only) { //default; new strategy: using Landmark value   ver 2
                    if($this->landmark_value_of[$EOLid] || isset($this->is_family[$EOLid])) $final2[] = $EOLid;
                }
                else { //orig strategy
                    $final2[] = $EOLid;
                }
            }
            $i++;
        }
        return $final2;
    }
}
?>