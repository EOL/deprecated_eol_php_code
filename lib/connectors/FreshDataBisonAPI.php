<?php
namespace php_active_record;
/* connector: freedata_bison.php */
class FreshDataBisonAPI
{
    function __construct($folder = null)
    {
        $this->folder = $folder;
        $this->destination[$folder] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";

        $this->ctr = 0;
        $this->debug = array();
        $this->print_header = true;
        

        $this->download_options = array('cache_path' => '/Volumes/Thunderbolt4/eol_cache_bison/', 'expire_seconds' => 5184000, 'download_wait_time' => 2000000, 'timeout' => 600, 
        'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->download_options['expire_seconds'] = false; //orig false | true -- expires now | maybe 5184000 2 months to expire

        $this->increment = 2;
        $this->solr_occurrence_api = "https://bison.usgs.gov/solr/occurrences/select/?q=providerID:440&fq=decimalLatitude:[* TO *]&wt=json&rows=$this->increment";
        // &indent=true -> for nice browser display
        
        $this->solr_taxa_api = "http://services.itis.gov/?wt=json&q=tsn:";
        
        /*
        GBIF occurrence extension   : file:///Library/WebServer/Documents/cp/GBIF_dwca/atlantic_cod/meta.xml
        DWC terms                   : http://rs.tdwg.org/dwc/terms/index.htm#Occurrence
        */
    }

    private function initialize()
    {
        require_library('connectors/FreeDataAPI');
        $func = new FreeDataAPI();
        $func->create_folder_if_does_not_exist($this->folder);
        return $func;
    }

    private function do_some_caching()
    {
        $start = 0;
        while(true)
        {
            $url = $this->solr_occurrence_api."&start=$start";
            if($json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $arr = json_decode($json, true);
                $total = count($arr['response']['docs']);
                echo "\ntotal = [$total]\n";
                // /*
                // print_r($arr); 
                foreach($arr['response']['docs'] as $rec) {
                    self::get_itis_taxon($rec);
                }
                // exit;
                // */
            }
            $start += $this->increment; 
            if($total < $this->increment) break;
        }
    }
    private function get_itis_taxon($rec)
    {
        if($ITIStsn = @$rec['ITIStsn']) {
            if($json = Functions::lookup_with_cache($this->solr_taxa_api.$ITIStsn, $this->download_options)) return json_decode($json, true);
        }
        return false;
    }
    private function main_loop($func)
    {
        // if(@$rek['decimalLatitude']) self::process_record($rek, $func);
        $start = 0;
        while(true)
        {
            $url = $this->solr_occurrence_api."&start=$start";
            if($json = Functions::lookup_with_cache($url, $this->download_options))
            {
                $docs = json_decode($json, true);
                $docs = $docs['response']['docs'];
                $total = count($docs);
                echo "\ntotalx = [$total]\n";
                
                // /*
                foreach($docs as $rec) {
                    
                    $taxa = self::get_itis_taxa($rec);
                }
                // exit;
                // */
            }
            $start += $this->increment; 
            if($total < $this->increment) break;
        }

        
    }
    function start()
    {
        // self::do_some_caching(); exit("\nexit muna\n");
        
        $folder = $this->folder;
        $func = self::initialize(); //use some functions from FreeDataAPI
        
        self::main_loop($func);
        
        // remove tmp dir
        if($paths['temp_dir']) shell_exec("rm -fr ".$paths['temp_dir']);
        
        $func->last_part($folder); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
        if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
    }



    private function process_record($rek, $func)
    {
        $rec = array();
        $this->ctr++;
        $rec['id'] = $this->ctr;
        $rec['taxonID'] = $rek['sourceTaxonId'];
        $rec['scientificName'] = $rek['sourceTaxonName'];
        $rec['lifeStage'] = @$rek['sourceLifeStage'];
        $rec['sex'] = @$rek['sourceTaxonSex'];
        $rec['taxonRemarks'] = $rek['interactionTypeName'] . " " . $rek['targetTaxonName'];
        $rec['locality'] = $rek['localityName'];
        $rec['decimalLatitude'] = $rek['decimalLatitude'];
        $rec['decimalLongitude'] = $rek['decimalLongitude'];
        $rec['eventDate'] = $rek['observationDateTime'];
        $rec['bibliographicCitation'] = $rek['referenceCitation'];

        $rec = array_map('trim', $rec);
        $func->print_header($rec, CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt");
        $val = implode("\t", $rec);
        self::save_to_text_file($val);
        
        /*
        $this->ctr;
        sourceTaxonId       http://rs.tdwg.org/dwc/terms/taxonID
        sourceTaxonName     http://rs.tdwg.org/dwc/terms/scientificName
        sourceLifeStage     http://rs.tdwg.org/dwc/terms/lifeStage
        sourceTaxonSex      http://rs.tdwg.org/dwc/terms/sex
                            http://rs.tdwg.org/dwc/terms/taxonRemarks
        localityName        http://rs.tdwg.org/dwc/terms/locality
        decimalLatitude     http://rs.tdwg.org/dwc/terms/decimalLatitude
        decimalLongitude    http://rs.tdwg.org/dwc/terms/decimalLongitude
        observationDateTime http://rs.tdwg.org/dwc/terms/eventDate
        referenceCitation   http://purl.org/dc/terms/bibliographicCitation
        */

        /*
        interactionTypeId       none
        interactionTypeName     none
        */
        
        $rec = array();
        $this->ctr++;
        $rec['id'] = $this->ctr;
        $rec['taxonID'] = $rek['targetTaxonId'];
        $rec['scientificName'] = $rek['targetTaxonName'];
        $rec['lifeStage'] = @$rek['targetLifeStage'];
        $rec['sex'] = "";
        $rec['taxonRemarks'] = $rek['sourceTaxonName'] . " " . $rek['interactionTypeName'];
        $rec['locality'] = $rek['localityName'];
        $rec['decimalLatitude'] = $rek['decimalLatitude'];
        $rec['decimalLongitude'] = $rek['decimalLongitude'];
        $rec['eventDate'] = $rek['observationDateTime'];
        $rec['bibliographicCitation'] = $rek['referenceCitation'];
        $val = implode("\t", $rec);
        self::save_to_text_file($val);

        /*
        targetTaxonId       http://rs.tdwg.org/dwc/terms/taxonID
        targetTaxonName     http://rs.tdwg.org/dwc/terms/scientificName
        targetLifeStage     http://rs.tdwg.org/dwc/terms/lifeStage
        sex                 none
                            http://rs.tdwg.org/dwc/terms/taxonRemarks
        localityName        http://rs.tdwg.org/dwc/terms/locality
        decimalLatitude     http://rs.tdwg.org/dwc/terms/decimalLatitude
        decimalLongitude    http://rs.tdwg.org/dwc/terms/decimalLongitude
        observationDateTime http://rs.tdwg.org/dwc/terms/eventDate
        referenceCitation   http://purl.org/dc/terms/bibliographicCitation
        */
    }

    private function save_to_text_file($row)
    {
        if($row)
        {
            $WRITE = Functions::file_open($this->destination[$this->folder], "a");
            fwrite($WRITE, $row . "\n");
            fclose($WRITE);
        }
    }

    function extract_file($zip_path)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($zip_path, "interactions.tsv", array('timeout' => 172800, 'expire_seconds' => 2592000)); //expires in 1 month
        return $paths;
    }

}
?>