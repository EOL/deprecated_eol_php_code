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

        $this->download_options = array('expire_seconds' => 5184000, 'download_wait_time' => 2000000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 1);
        $this->download_options['expire_seconds'] = false; //orig false | true -- expires now | maybe 5184000 2 months to expire

        if(Functions::is_production()) $this->download_options['cache_path'] = '/extra/eol_cache_bison/';
        else                           $this->download_options['cache_path'] = '/Volumes/Thunderbolt4/eol_cache_bison/';

        $this->increment = 10000;
        $this->solr_occurrence_api = "https://bison.usgs.gov/solr/occurrences/select/?q=providerID:440&fq=decimalLatitude:[* TO *]&wt=json&rows=$this->increment";
        // &indent=true -> for nice browser display
        
        $this->solr_taxa_api = "http://services.itis.gov/?wt=json&q=tsn:";
        
        /*
        GBIF occurrence extension   : file:///opt/homebrew/var/www/cp/GBIF_dwca/atlantic_cod/meta.xml
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
    function start()
    {
        // self::do_some_caching(); exit("\nexit muna\n");
        
        $folder = $this->folder;
        $func = self::initialize(); //use some functions from FreeDataAPI
        self::main_loop($func);
        $func->last_part($folder); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
        if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
    }

    private function do_some_caching()
    {
        $start = 0;
        $start = 74330000;
        while(true)
        {
            $url = $this->solr_occurrence_api."&start=$start";
            if($start >= 74330000 && $start <= 76060000) //debug only
            {
                if($json = Functions::lookup_with_cache($url, $this->download_options))
                {
                    $arr = json_decode($json, true);
                    $total = count($arr['response']['docs']);
                    echo "\ntotal = [$total] [$start]\n";
                    // /*
                    foreach($arr['response']['docs'] as $rec) {
                        self::get_itis_taxon($rec);
                    }
                    // */
                    if($total < $this->increment) break;
                }
            }
            $start += $this->increment;
            // break; // debug only - gets the first 10k
            if($start > 76060000) break;   //debug only
        }
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
                echo "\ntotal = [$total]\n";
                
                // /*
                foreach($docs as $rec) {
                    /* Array(
                        [eventDate] => 2005-09-18
                        [providedScientificName] => Abies procera
                        [year] => 2005
                        [countryCode] => US
                        [providedCounty] => Clackamas
                        [ambiguous] => 
                        [generalComments] => Live DBH inches=19.9
                        [verbatimLocality] => plot control number=41120028010497 subplotNbr=3 treeNbr=122
                        [latlon] => -121.8547,45.12979
                        [computedCountyFips] => 41005
                        [] => 1432338409
                        [decimalLongitude] => -121.8547
                        [basisOfRecord] => observation
                        [providedCommonName] => noble fir
                        [collectionID] => https://bison.usgs.gov/ipt/resource?r=usfs-fia-trees-public-lands
                        [] => USFS - Forest Inventory and Analysis - Trees (Public Lands)
                        [scientificName] => Abies procera
                        [institutionID] => https://bison.usgs.gov
                        [computedStateFips] => 41
                        [license] => http://creativecommons.org/publicdomain/zero/1.0/legalcode
                        [TSNs] => Array([0] => 181835)
                        [providerID] => 440
                        [stateProvince] => Oregon
                        [higherGeographyID] => 41005
                        [decimalLatitude] => 45.12979
                        [verbatimElevation] => 3900
                        [geo] => -121.8547 45.12979
                        [provider] => BISON
                        [geodeticDatum] => NAD83
                        [calculatedCounty] => Clackamas County
                        [ITISscientificName] => Abies procera
                        [pointPath] => /-121.8547,45.12979/observation
                        [kingdom] => Plantae
                        [calculatedState] => Oregon
                        [hierarchy_homonym_string] => -202422-954898-846494-954900-846496-846504-500009-954916-500028-18030-18031-181835-
                        [ITIScommonName] => noble fir;red fir;white fir
                        [resourceID] => 440,100028
                        [ITIStsn] => 181835
                        [associatedReferences] => [{"url":"http://apps.fs.fed.us/fiadb-downloads/datamart.html","description":"US Forest Service Datamart"}]
                        [_version_] => 1568324372682244096)
                    */
                    
                    if(($this->ctr % 1000) == 0) echo " ".$this->ctr." ";
                    
                    $rek = array();
                    if(!self::with_lat_long($rec)) continue;
                    
                    $this->ctr++;
                    $rek['id'] = $this->ctr;
                    if($rek['scientificName'] = self::get_sciname($rec)) {}
                    else
                    {
                        $this->ctr--;
                        continue;
                    }
                    $rek['ITISscientificName']  = @$rec['ITISscientificName'];
                    //start get hierarchy from ITIS
                    $ancestry = self::get_itis_ancestry($rec);
                    $rek['taxonRank'] = @$ancestry['taxonRank'];
                    $rek['higherClassification'] = @$ancestry['higherClassification'];
                    $rek['kingdom'] = @$ancestry['kingdom'];
                    $rek['phylum']  = @$ancestry['phylum'];
                    $rek['class']   = @$ancestry['class'];
                    $rek['order']   = @$ancestry['order'];
                    $rek['family']  = @$ancestry['family'];
                    $rek['genus']   = @$ancestry['genus'];

                    $rek['decimalLatitude'] = $rec['decimalLatitude'];
                    $rek['decimalLongitude'] = $rec['decimalLongitude'];
                    $rek['occurrenceID']    = $rec['occurrenceID'];
                    $rek['basisOfRecord']   = $rec['basisOfRecord'];
                    $rek['catalogNumber']   = @$rec['catalogNumber'];
                    $rek['recordedBy']      = @$rec['recordedBy'];
                    $rek['institutionCode'] = $rec['ownerInstitutionCollectionCode'];
                    $rek['eventDate']       = @$rec['eventDate'];
                    $rek['county']          = @$rec['calculatedCounty'];
                    $rek['stateProvince']   = @$rec['calculatedState'];
                    $rek['countryCode']     = @$rec['countryCode'];
                    $rek['institutionID']   = $rec['institutionID'];
                    $rek['source'] = '';
                    
                    /* working but commented per: Jen https://eol-jira.bibalex.org/browse/DATA-1699?focusedCommentId=61781&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61781
                    if($val = @$rec['occurrenceID']) $rek['source'] = "https://bison.usgs.gov/solr/occurrences/select/?q=occurrenceID:".$val;
                    ...replaced by below:
                    */
                    if($val = @$rec['occurrenceID']) $rek['occurrenceID'] = "https://bison.usgs.gov/solr/occurrences/select/?q=occurrenceID:".$val;
                    
                    
                    // print_r($rek);
                    $rek = array_map('trim', $rek);
                    $func->print_header($rek, CONTENT_RESOURCE_LOCAL_PATH . "$this->folder/observations.txt");
                    $val = implode("\t", $rek);
                    self::save_to_text_file($val);
                    
                } //end loop
                // break; //debug -> gets only first 10K records
                // */
            }
            $start += $this->increment; 
            if($total < $this->increment) break;
        }
    }
    private function get_sciname($rec)
    {
        if($val = @$rec['ITISscientificName']) return $val;
        elseif($val = @$rec['scientificName']) return $val;
        elseif($val = @$rec['providedScientificName']) return $val;
        return false;
    }
    private function with_lat_long($rec)
    {
        if(!@$rec['decimalLatitude']) return false;
        if(!@$rec['decimalLongitude']) return false;
        return true;
    }
    private function get_itis_ancestry($rec)
    {
        $ranks = array();
        $taxon = self::get_itis_taxon($rec);
        // print_r($taxon);
        if($str = @$taxon['response']['docs'][0]['hierarchySoFarWRanks'][0])
        {
            $str = str_replace($taxon['response']['docs'][0]['tsn'].":", "", $str);
            // echo "\n[$str]\n";
            $a = explode("$", $str);
            $a = array_map('trim', $a);
            $a = array_filter($a); //remove null arrays
            $a = array_values($a); //reindex key
            // print_r($a); //good debug
            
            $valid_ranks = array('kingdom', 'phylum', 'class', 'order', 'family', 'genus', 'subgenus', 'species');
            $taxonRank = strtolower($taxon['response']['docs'][0]['rank']);
            if(($key = array_search($taxonRank, $valid_ranks)) !== false) {
                unset($valid_ranks[$key]);
            }
            
            foreach($a as $temp)
            {
                $t = explode(":", $temp);
                $rank = strtolower($t[0]);
                if(in_array($rank, $valid_ranks)) $ranks[$rank] = $t[1];
            }
            $ranks['taxonRank'] = $taxonRank;
            
            //for higherClassification
            //"566069:$Plantae$Viridiplantae$Streptophyta$Embryophyta$Tracheophyta$Spermatophytina$Magnoliopsida$Lilianae$Poales$Poaceae$Poa$Poa cusickii$Poa cusickii ssp. cusickii$"
            
            $str = $taxon['response']['docs'][0]['hierarchySoFar'][0];
            $str = str_replace($taxon['response']['docs'][0]['tsn'].":", "", $str);
            $a = explode("$", $str);
            $a = array_map('trim', $a);
            $a = array_filter($a); //remove null arrays
            $a = array_values($a); //reindex key
            array_pop($a);
            // print_r($a); exit;
            $ranks['higherClassification'] = implode("|", $a);
            // print_r($ranks); //good debug
        }
        // 181835:$Kingdom:Plantae$Subkingdom:Viridiplantae$Infrakingdom:Streptophyta$Superdivision:Embryophyta$Division:Tracheophyta$Subdivision:Spermatophytina$Class:Pinopsida$Subclass:Pinidae$Order:Pinales$Family:Pinaceae$Genus:Abies$Species:Abies procera$
        return $ranks;
    }
    
    private function process_record($rek, $func) {}

    private function save_to_text_file($row)
    {
        if($row) {
            $WRITE = Functions::file_open($this->destination[$this->folder], "a");
            fwrite($WRITE, $row . "\n");
            fclose($WRITE);
        }
    }

    private function get_itis_taxon($rec)
    {
        if($ITIStsn = @$rec['ITIStsn']) {
            if($json = Functions::lookup_with_cache($this->solr_taxa_api.$ITIStsn, $this->download_options)) return json_decode($json, true);
        }
        return false;
    }

}
?>