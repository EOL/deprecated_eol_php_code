<?php
namespace php_active_record;
/* connector: freedata_globi.php */
class FreshDataGlobiAPI
{
    function __construct($folder = null)
    {
        $this->folder = $folder;
        $this->destination['GloBI-Ecological-DB-of-the-World-s-Insect-Pathogens'] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";
        $this->destination['GloBI-Ant-Plant-Interactions'] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";

        $this->ctr = 0;
        $this->debug = array();
        $this->print_header = true;
        
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

    function start($params)
    {
        $folder = $this->folder;
        $func = self::initialize(); //use some functions from FreeDataAPI
        
        $paths = self::extract_file($params['zip_path']);
        print_r($paths);
        
        // /* this part is needed because master.zip extracts into a different folder and not into /master/
        $tsv_file = $paths['archive_path']."/interactions.tsv";
        if(!file_exists($tsv_file))
        {
            $paths['archive_path'] .= "/".$params['zip_folder'];
            echo "\nnew paths:\n";
            print_r($paths);
        }
        // */
        
        $i = 0;
        foreach(new FileIterator($paths['archive_path']."/interactions.tsv") as $line => $row)
        {
            $i++;
            if($i == 1) $fields = explode("\t", $row);
            else
            {
                $rec = explode("\t", $row);
                $k = -1;
                foreach($fields as $field)
                {
                    $k++;
                    if($val = @$rec[$k]) $rek[$field] = $val;
                }
                // print_r($rek);
                // if(true) //get all rows //debug only
                if(@$rek['decimalLatitude']) //used in normal operation
                {
                    self::process_record($rek, $func);
                }
            }
        }
        
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