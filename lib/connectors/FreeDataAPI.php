<?php
namespace php_active_record;
/* connector: [freedata] */
class FreeDataAPI
{
    /*
    const VARIABLE_NAME = "string value";
    */
    
    function __construct($folder = null)
    {
        $this->download_options = array('cache' => 1, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 2592000); //expires in a month
        $this->destination['reef life survey'] = CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey/observations.txt";
        $this->fields['reef life survey'] = array("id", "occurrenceID", "eventDate", "decimalLatitude", "decimalLongitude", "scientificName", "taxonRank", "kingdom", "phylum", "class", "family");
        $this->ctr = 0;
        $this->debug = array();
    }

    function generate_ReefLifeSurvey_archive($params)
    {
        if(file_exists(CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey"))
        {
            recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey");
            echo "\nDelete old folder reef_life_survey...\n";
        }
        
        echo "\nCreate folder reef_life_survey...\n";
        //make dir "reef_life_survey"
        $command_line = "mkdir " . CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey";
        $output = shell_exec($command_line);
        
        if(!$WRITE = Functions::file_open($this->destination['reef life survey'], "w")) return;
        fwrite($WRITE, implode("\t", $this->fields['reef life survey']) . "\n");
        fclose($WRITE);
        
        $collections = array("Global reef fish dataset", "Invertebrates");
        // $collections = array("Invertebrates");
        foreach($collections as $coll)
        {
            $url = $params[$coll]; //csv url path
            $temp_path = Functions::save_remote_file_to_local($url, $this->download_options);
            self::process_RLS($temp_path, $coll);
            unlink($temp_path);
        }
        self::generate_meta_xml(); //creates a meta.xml file

        //copy 2 files inside /reef_life_survey/
        copy(CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey/observations.txt", CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey/observations.txt");
        copy(CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey/meta.xml"            , CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey/meta.xml");

        //create reef_life_survey.tar.gz
        $command_line = "tar -czvf " . CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey.tar.gz --directory=" . CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey .";
        $output = shell_exec($command_line);

        if($this->debug) print_r($this->debug);
    }
    
    function process_RLS($csv_file, $collection)
    {
        $i = 0;
        if(!$file = Functions::file_open($csv_file, "r")) return;
        if(!$WRITE = Functions::file_open($this->destination['reef life survey'], "a")) return;
        
        while(!feof($file))
        {
            $temp = fgetcsv($file);
            $i++;
            if(($i % 10000) == 0) echo number_format($i) . "\n";
            if($i == 1)
            {
                $fields = $temp;
                // print_r($fields);
                if(count($fields) != 20)
                {
                    $this->debug["not20"][$fields[0]] = 1;
                    continue;
                }
            }
            else
            {
                $this->ctr++;
                $rec = array();
                $k = 0;
                // 2 checks if valid record
                if(!$temp) continue;
                if(count($temp) != 20)
                {
                    $this->debug["not20"][$temp[0]] = 1;
                    continue;
                }
                
                foreach($temp as $t)
                {
                    $rec[$fields[$k]] = $t;
                    $k++;
                }
                
                if($rec)
                {
                    $rec['id'] = $this->ctr;
                    // print_r($rec); exit;
                    $row = self::process_rec_RLS($rec, $collection);
                    fwrite($WRITE, $row . "\n");
                }
                
                // if($i > 5) break;  //debug only
            }
        } // end while{}
        fclose($file);
        fclose($WRITE);
    }
    
    /* sample "Invertebrates" record
    [FID] => M2_INVERT_DATA.1
    [Key] => 1
    [SurveyID] => 62003108
    [Country] => Indonesia
    [Ecoregion] => Western Sumatra
    [Realm] => Western Indo-Pacific
    [SiteCode] => ACEH22
    [Site] => Ujung Tunku Nth
    [SiteLat] => 5.8829
    [SiteLong] => 95.2512
    [SurveyDate] => 2009-03-01T00:00:00
    [Depth] => 5
    [Phylum] => Echinodermata
    [Class] => Echinoidea
    [Family] => Echinometridae
    [Taxon] => Echinostrephus aciculatus
    [Block] => 1
    [Total] => 100
    [Diver] => RSS
    [geom] => POINT (95.25118 5.88289)
    [id] => 1

    sample "Global reef fish dataset" record
    [FID] => M1_DATA.1
    [Key] => 1
    [SurveyID] => 62003097
    [Country] => Indonesia
    [Ecoregion] => Western Sumatra
    [Realm] => Western Indo-Pacific
    [SiteCode] => ACEH11
    [Site] => Bate Bukulah
    [SiteLat] => 5.8672
    [SiteLong] => 95.2696
    [SurveyDate] => 2009-02-25T00:00:00
    [Depth] => 9
    [Phylum] => Chordata
    [Class] => Actinopterygii
    [Family] => Labridae
    [Taxon] => Halichoeres marginatus
    [Block] => 2
    [Total] => 1
    [Diver] => GJE
    [geom] => POINT (95.2696 5.86718)
    */

    function process_rec_RLS($rec, $collection)
    {
        // id   occurrenceID    eventDate   decimalLatitude decimalLongitude    scientificName  taxonRank   kingdom phylum  class   family
        $rek = array();
        /* total of 11 columns
        $rek['id']               = $rec['id'];
        $rek['occurrenceID']     = $rec['SurveyID'];
        $rek['eventDate']        = $rec['SurveyDate'];
        $rek['decimalLatitude']  = $rec['SiteLat'];
        $rek['decimalLongitude'] = $rec['SiteLong'];
        $rek['scientificName']   = $rec['Taxon'];
        $rek['taxonRank']        = 'species';
        $rek['kingdom']          = 'Animalia';
        $rek['phylum']           = $rec['Phylum'];
        $rek['class']            = $rec['Class'];
        $rek['family']           = $rec['Family'];
        */
        
        //total of 11 columns
        $rek[] = $rec['id'];
        if($collection == "Global reef fish dataset") $rek[] = $rec['SurveyID'] . "_" . $rec['id'];
        elseif($collection == "Invertebrates")        $rek[] = $rec['FID'];
        $rek[] = $rec['SurveyDate'];
        $rek[] = $rec['SiteLat'];
        $rek[] = $rec['SiteLong'];
        
        $taxon = $rec['Taxon'];
        if(stripos($taxon, ' spp.') !== false || stripos($taxon, ' sp.') !== false ) //string is found
        {
            $taxon = str_ireplace(" spp.", "", $taxon);
            $taxon = str_ireplace(" sp.", "", $taxon);
            $rek[] = $taxon;
            $rek[] = '';
        }
        else
        {
            $rek[] = $taxon;
            $rek[] = 'species';
        }
        
        $rek[] = 'Animalia';
        $rek[] = $rec['Phylum'];
        $rek[] = $rec['Class'];
        $rek[] = $rec['Family'];
        return implode("\t", $rek);
    }
    
    function generate_meta_xml()
    {
        if(!$WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "reef_life_survey/meta.xml", "w")) return;
        fwrite($WRITE, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($WRITE, '<archive xmlns="http://rs.tdwg.org/dwc/text/">' . "\n");
        fwrite($WRITE, '  <core encoding="UTF-8" linesTerminatedBy="\n" fieldsTerminatedBy="\t" fieldsEnclosedBy="" ignoreHeaderLines="1" rowType="http://rs.tdwg.org/dwc/terms/Occurrence">' . "\n");
        fwrite($WRITE, '    <files>' . "\n");
        fwrite($WRITE, '      <location>observations.txt</location>' . "\n");
        fwrite($WRITE, '    </files>' . "\n");
        fwrite($WRITE, '    <id index="0"/>' . "\n");
        fwrite($WRITE, '    <field index="0" term="http://rs.gbif.org/terms/1.0/RLSID"/>' . "\n");
        fwrite($WRITE, '    <field index="1" term="http://rs.tdwg.org/dwc/terms/occurrenceID"/>' . "\n");
        fwrite($WRITE, '    <field index="2" term="http://rs.tdwg.org/dwc/terms/eventDate"/>' . "\n");
        fwrite($WRITE, '    <field index="3" term="http://rs.tdwg.org/dwc/terms/decimalLatitude"/>' . "\n");
        fwrite($WRITE, '    <field index="4" term="http://rs.tdwg.org/dwc/terms/decimalLongitude"/>' . "\n");
        fwrite($WRITE, '    <field index="5" term="http://rs.tdwg.org/dwc/terms/scientificName"/>' . "\n");
        fwrite($WRITE, '    <field index="6" term="http://rs.tdwg.org/dwc/terms/taxonRank"/>' . "\n");
        fwrite($WRITE, '    <field index="7" term="http://rs.tdwg.org/dwc/terms/kingdom"/>' . "\n");
        fwrite($WRITE, '    <field index="8" term="http://rs.tdwg.org/dwc/terms/phylum"/>' . "\n");
        fwrite($WRITE, '    <field index="9" term="http://rs.tdwg.org/dwc/terms/class"/>' . "\n");
        fwrite($WRITE, '    <field index="10" term="http://rs.tdwg.org/dwc/terms/family"/>' . "\n");
        fwrite($WRITE, '  </core>' . "\n");
        fwrite($WRITE, '</archive>' . "\n");
        fclose($WRITE);
    }
    
}
?>
