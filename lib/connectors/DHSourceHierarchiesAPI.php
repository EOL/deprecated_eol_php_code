<?php
namespace php_active_record;
/* connector: [dwh.php] */

class DHSourceHierarchiesAPI
{
    function __construct()
    {
        /*
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        if(Functions::is_production()) {}
        */
        $this->AphiaRecordByAphiaID_download_options = array('download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26, 'expire_seconds' => false);
        $this->webservice['AphiaRecordByAphiaID'] = "http://www.marinespecies.org/rest/AphiaRecordByAphiaID/";

        $this->gnsparser = "http://parser.globalnames.org/api?q=";
        $this->smasher_download_options = array(
            'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
            'download_wait_time' => 1000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
        /* Functions::lookup_with_cache($this->gnsparser.urlencode($rec['scientificName']), $this->smasher_download_options); */
        
        $this->debug = array();
        $this->taxonomy_header = array("uid", "parent_uid", "name", "rank", "sourceinfo"); //('uid	|	parent_uid	|	name	|	rank	|	sourceinfo	|	' + '\n')
        $this->synonym_header = array("uid", "name", "type", "rank");                      //('uid	|	name	|	type	|	rank	|	' + '\n')
        $this->main_path = "/Volumes/AKiTiO4/d_w_h/dynamic_working_hierarchy-master/";
        
        $this->sh['WoRMS']['source']        = $this->main_path."/worms_v5/";
        $this->sh['ioc-birdlist']['source'] = $this->main_path."/ioc-birdlist_v3/";
        $this->sh['trunk']['source']        = $this->main_path."/trunk_20180521/";
        $this->sh['amphibia']['source']     = $this->main_path."/amphibia_v2/";
        $this->sh['spiders']['source']      = $this->main_path."/spiders_v2/";
    }
    
    public function start($what)
    {
        $meta_xml_path = $this->sh[$what]['source']."meta.xml";
        $meta = self::analyze_meta_xml($meta_xml_path);
        if($meta == "No core entry in meta.xml") $meta = self::analyze_eol_meta_xml($meta_xml_path);
        $meta['what'] = $what;
        // print_r($meta); exit;
        self::process_taxon_file($meta);
    }
    private function process_taxon_file($meta)
    {
        $what = $meta['what'];
        $fn_tax = fopen($this->sh[$what]['source']."taxonomy.tsv", "w"); //will overwrite existing
        $fn_syn = fopen($this->sh[$what]['source']."synonym.tsv", "w"); //will overwrite existing
        $i = 0;
        foreach(new FileIterator($this->sh[$what]['source'].$meta['taxon_file']) as $line => $row) {
            $i++;
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            // echo "\n".count($tmp)."\n"; print_r($tmp);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            // print_r($rec); exit; //use to test if field - value is OK
            if(($i % 5000) == 0) echo "\n".number_format($i)."\n";
            //start ----------------------------------
            if(in_array($what, array('trunk'))) {
                /*
                    [index] => 1
                    [taxonomicStatus] => accepted
                    [taxonRank] => superfamily
                    [datasetID] => dd18e3cf-04ba-4b0d-8349-1dd4b7ac5000
                    [parentNameUsageID] => 324b4a02-700b-4ae2-9dbd-65570f42f83c
                    [scientificNameAuthorship] => 
                    [higherClassification] => life,cellular organisms,Eukaryota,Opisthokonta,Metazoa,Bilateria,Protostomia,Ecdysozoa,Panarthropoda,Arthropoda,Chelicerata,Arachnida,Acari,Acariformes,Trombidiformes,Prostigmata,Anystina,Parasitengona
                    [acceptedNameUsageID] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    [scientificName] => Arrenuroidea
                    [taxonID] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    (
                        [0] => 1
                        [1] => accepted
                        [2] => superfamily
                        [3] => dd18e3cf-04ba-4b0d-8349-1dd4b7ac5000
                        [4] => 324b4a02-700b-4ae2-9dbd-65570f42f83c
                        [5] => 
                        [6] => life,cellular organisms,Eukaryota,Opisthokonta,Metazoa,Bilateria,Protostomia,Ecdysozoa,Panarthropoda,Arthropoda,Chelicerata,Arachnida,Acari,Acariformes,Trombidiformes,Prostigmata,Anystina,Parasitengona
                        [7] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                        [8] => Arrenuroidea
                        [9] => 00016d53-eae4-494c-8f79-3e9ddcd5e634
                    )
                    if accepted_id != taxon_id:
                        print('synonym found')
                        out_file_s.write(accepted_id + '\t|\t' + name + '\t|\t' + 'synonym' + '\t|\t' + '\t|\t' + '\n')
                    else:
                        out_file_t.write(taxon_id + '\t|\t' + parent_id + '\t|\t' + name + '\t|\t' + rank + '\t|\t' + source + '\t|\t' + '\n')
                */
                $parent_id = $rec['parentNameUsageID'];     //row[4]
                $name = $rec['scientificName'];             //row[8]
                $taxon_id = $rec['taxonID'];                //row[9]
                $accepted_id = $rec['acceptedNameUsageID']; //row[7]
                $rank = $rec['taxonRank'];                  //row[2]
                $source = '';
                if($accepted_id != $taxon_id) fwrite($fn_syn, $accepted_id . '\t|\t' . $name . '\t|\t' . 'synonym' . '\t|\t' . '\t|\t' . '\n');
                else                          fwrite($fn_tax, $taxon_id . '\t|\t' . $parent_id . '\t|\t' . $name . '\t|\t' . $rank . '\t|\t' . $source . '\t|\t' . '\n');
            }
            //end ------------------------------------
        }
        fclose($fn_tax);
        fclose($fn_syn);
    }
    
    private function analyze_eol_meta_xml($meta_xml_path)
    {
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            foreach($xml->table as $tbl) {
                if($tbl['rowType'] == "http://rs.tdwg.org/dwc/terms/Taxon") {
                    if(in_array($tbl['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
                    else                                                    $ignoreHeaderLines = false;
                    $fields = array();
                    foreach($tbl->field as $f) {
                        $term = (string) $f['term'][0];
                        $uris[] = $term;
                        $fields[] = pathinfo($term, PATHINFO_FILENAME);
                    }
                    $file = (string) $tbl->files->location;
                    return array('fields' => $fields, 'taxon_file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
                }
            }
        }
        else {
            echo "\nNo meta.xml present. Will use first-row header from taxon file\n";
        }
        exit("\nInvestigate 02.\n");
    }
    private function analyze_meta_xml($meta_xml_path)
    {
        if(file_exists($meta_xml_path)) {
            $xml_string = file_get_contents($meta_xml_path);
            $xml = simplexml_load_string($xml_string);
            // print_r($xml->core);
            if(!isset($xml->core)) {
                echo "\nNo core entry in meta.xml\n";
                return "No core entry in meta.xml";
            }
            if(in_array($xml->core['ignoreHeaderLines'], array(1, true))) $ignoreHeaderLines = true;
            else                                                          $ignoreHeaderLines = false;
            $fields = array();
            if($xml->core['index'] == 0) $fields[] = "index";
            if($xml->core->field[0]['index'] == 0) $fields = array(); //this will ignore <id index="0" />
            foreach($xml->core->field as $f) {
                $term = (string) $f['term'][0];
                $uris[] = $term;
                $fields[] = pathinfo($term, PATHINFO_FILENAME);
            }
            $file = (string) $xml->core->files->location;
            return array('fields' => $fields, 'taxon_file' => $file, 'ignoreHeaderLines' => $ignoreHeaderLines);
        }
        else {
            echo "\nNo meta.xml present. Will use first-row header from taxon file\n";
        }
        exit("\nInvestigate 01.\n");
    }
}
?>