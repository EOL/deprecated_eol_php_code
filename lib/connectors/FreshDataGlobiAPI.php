<?php
namespace php_active_record;
/* connector: globi
*/
class FreshDataGlobiAPI
{
    function __construct($folder = null)
    {
        $this->folder = $folder;
        $this->destination['GloBI_Ecological-DB-of-the-World-s-Insect-Pathogens'] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";
        $this->fields['GloBI_Ecological-DB-of-the-World-s-Insect-Pathogens'] = array("id", "taxonID", "scientificName", "lifeStage", "sex", "taxonRemarks", "locality", "decimalLatitude", "decimalLongitude", "eventDate", "bibliographicCitation");

        $this->ctr = 0;
        $this->debug = array();
        
        /*
        GBIF occurrence extension:
        file:///Library/WebServer/Documents/cp/GBIF_dwca/atlantic_cod/meta.xml
        */
    }

    private function initialize()
    {
        require_library('connectors/FreeDataAPI');
        $func = new FreeDataAPI();
        $func->create_folder_if_does_not_exist($this->folder);
        
        //first row - headers of text file
        $WRITE = Functions::file_open($this->destination[$this->folder], "w");
        fwrite($WRITE, implode("\t", $this->fields[$this->folder]) . "\n");
        fclose($WRITE);
    }

    function start($params)
    {
        self::initialize();
        
        $paths = self::extract_file($params['zip_path']);
        print_r($paths);
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
                    $rek[$field] = $rec[$k];
                }
                
                // if($rek['decimalLatitude']) //used in normal operation
                if(true)
                {
                    self::process_record($rek);
                }
                // else echo " no dec";
            }
        }
        
        
        // remove tmp dir
        if($paths['temp_dir']) shell_exec("rm -fr ".$paths['temp_dir']);
        
        
        require_library('connectors/FreeDataAPI');
        $func = new FreeDataAPI();
        $func->last_part($this->folder); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
    }

    private function process_record($rek)
    {
        $rec = array();
        $this->ctr++;
        $rec[] = $this->ctr;
        $rec[] = $rek['sourceTaxonId'];
        $rec[] = $rek['sourceTaxonName'];
        $rec[] = $rek['sourceLifeStage'];
        $rec[] = $rek['sourceTaxonSex'];
        $rec[] = $rek['interactionTypeName'] . " " . $rek['targetTaxonName'];
        $rec[] = $rek['localityName'];
        $rec[] = $rek['decimalLatitude'];
        $rec[] = $rek['decimalLongitude'];
        $rec[] = $rek['observationDateTime'];
        $rec[] = $rek['referenceCitation'];
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
        $rec[] = $this->ctr;
        $rec[] = $rek['targetTaxonId'];
        $rec[] = $rek['targetTaxonName'];
        $rec[] = $rek['targetLifeStage'];
        $rec[] = "";
        $rec[] = $rek['sourceTaxonName'] . " " . $rek['interactionTypeName'];
        $rec[] = $rek['localityName'];
        $rec[] = $rek['decimalLatitude'];
        $rec[] = $rek['decimalLongitude'];
        $rec[] = $rek['observationDateTime'];
        $rec[] = $rek['referenceCitation'];
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

    private function extract_file($zip_path)
    {
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($zip_path, "interactions.tsv");
        return $paths;
    }

}
?>