<?php
namespace php_active_record;
/* connector: [freedata_xxx] 
NOTE: usgs-nas and eMammal still uses local files

Jenkins notes:
add Jenkins user read/write access to:
- eol_php_code base /tmp/ folder
- eol_php_code base /resources/ folder

*/
class FreeDataAPI
{
    /* const VARIABLE_NAME = "string value"; */
    function __construct($folder = null)
    {
        $this->folder = $folder; //now used for all
        $this->download_options = array('cache' => 1, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*28); //expires in 28 days

        $this->print_header = true; //for all
        //----------------------------
        $this->destination['reef-life-survey'] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";
        //----------------------------
        $this->destination['eMammal'] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";
        //----------------------------
        // DATA-1691 MarylandBio
        $this->destination['MarylandBio'] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";
        // $this->data_file['MarylandBio] = "http://localhost/cp/FreshData/Maryland Biodiversity invasives/country_lat_lon.csv";
        $this->data_file['MarylandBio'] = "http://editors.eol.org/data_files/country_lat_lon.csv";
        //----------------------------
        //DATA-1683
        $this->destination['usgs-nas'] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt"; //Nonindigenous Aquatic Species
        $this->service['usgs-nas']['occurrences'] = "https://nas.er.usgs.gov/api/v1/occurrence/search"; //https://nas.er.usgs.gov/api/v1/occurrence/search?genus=Zizania&species=palustris&offset=0
        //----------------------------
        $this->destination['MarylandDNR'] = CONTENT_RESOURCE_LOCAL_PATH . "$folder/observations.txt";
        //----------------------------
        $this->destination['GloBI-Ant-Plant-Interactions'] = CONTENT_RESOURCE_LOCAL_PATH . "GloBI-Ant-Plant-Interactions/observations.txt";
        //----------------------------
        
        $this->ctr = 0; //for "reef-life-survey" and "eMammal" and "MarylandBio"
        $this->debug = array();
        
        /*
        GBIF occurrence extension:
        file:///Library/WebServer/Documents/cp/GBIF_dwca/atlantic_cod/meta.xml
        */
    }

    function get_spreadsheet($spreadsheet_local_path)
    {
        require_library('XLSParser');
        $parser = new XLSParser();
        $arr = $parser->convert_sheet_to_array($spreadsheet_local_path);
        return $arr;
    }

    //start for MarylandDNR ==============================================================================================================
    function generate_MarylandDNR_archive($xls_zip_url)
    {
        $folder = $this->folder;
        self::create_folder_if_does_not_exist($folder);

        //---use external func
        require_library('connectors/FreshDataGlobiAPI');
        $func = new FreshDataGlobiAPI($folder);
        $paths = $func->extract_file($xls_zip_url);
        print_r($paths);
        $filename = pathinfo($xls_zip_url, PATHINFO_FILENAME);
        $filename = "ForNISC2017.xls"; //TODO: get this file automatically, not hard-coded
        $xls_path = $paths['temp_dir'].$filename;
        $arr = self::get_spreadsheet($xls_path);
        $indexes = array_keys($arr);
        // foreach($indexes as $i) echo "\n$i: ".count($arr[$i]); //debug only - just for stats

        $WRITE = Functions::file_open($this->destination[$folder], "a");
        $i = -1;
        foreach($arr['ScientificName'] as $sciname)
        {
            $i++;
            $row = self::process_rec_MarylandDNR($arr, $i);
            if($row) fwrite($WRITE, $row . "\n");
        }
        fclose($WRITE);

        // remove tmp dir
        if($paths['temp_dir']) shell_exec("rm -fr ".$paths['temp_dir']);

        // last steps
        self::last_part($folder);
        if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
    }

    private function process_rec_MarylandDNR($arr, $i)
    {
        $rec = array();
        $this->ctr++;
        // echo("\n".$arr['ScientificName'][$i]."\n");
        $rec['id'] = $this->ctr;
        $rec['scientificName'] = $arr['ScientificName'][$i];
        $rec['kingdom'] = 'Animalia';
        $rec['phylum'] = 'Chordata';
        
        // GearText and fkGearDimensions into http://rs.tdwg.org/dwc/terms/samplingProtocol
        $rec['samplingProtocol'] = "";
        if($val = $arr['GearText'][$i])         $rec['samplingProtocol'] .= $val.". ";
        if($val = $arr['fkGearDimensions'][$i]) $rec['samplingProtocol'] .= $val.". ";
        $rec['samplingProtocol'] = trim($rec['samplingProtocol']);
        
        //SiteName and SiteID into http://rs.tdwg.org/dwc/terms/locality
        $rec['locality'] = "";
        if($val = $arr['SiteName'][$i]) $rec['locality'] .= $val.". ";
        if($val = $arr['SiteID'][$i])   $rec['locality'] .= $val.". ";
        $rec['locality'] = trim($rec['locality']);

        //CountEstimationText and PassComments into measurementRemarks
        $rec['measurementRemarks'] = "";
        if($val = $arr['CountEstimationText'][$i]) $rec['measurementRemarks'] .= $val.". ";
        if($val = $arr['PassComments'][$i])        $rec['measurementRemarks'] .= $val.". ";
        $rec['measurementRemarks'] = trim($rec['measurementRemarks']);
        
        //For http://rs.tdwg.org/dwc/terms/samplingEffort: "Duration = xx seconds. " (if Duration column is populated) 
        //followed by "Electrofishing units = yy" (if NumbElectroFishUnits is populated)
        $rec['samplingEffort'] = "";
        if($val = $arr['Duration'][$i])             $rec['samplingEffort'] .= "Duration = $val seconds. ";
        if($val = $arr['NumbElectroFishUnits'][$i]) $rec['samplingEffort'] .= "Electrofishing units = $val. ";
        $rec['samplingEffort'] = trim($rec['samplingEffort']);
        
        $rec['decimalLatitude'] = $arr['StartLatDD'][$i];
        $rec['decimalLongitude'] = $arr['StartLongDD'][$i];
        if(!$rec['decimalLatitude'] || !$rec['decimalLongitude']) return false; //should have lat long

        // for http://rs.tdwg.org/dwc/terms/individualCount, SpeciesCount should do fine
        $rec['individualCount'] = $arr['SpeciesCount'][$i];
        
        $rec['source'] = "http://dnr.maryland.gov/fisheries/Pages/inland.aspx";
        $rec['bibliographicCitation'] = "http://dnr.maryland.gov/fisheries/Pages/inland.aspx, Maryland Department of Natural Resources, Freshwater Fisheries Program, data accessed July 2017";
        
        self::print_header($rec);
        // print_r($rec); exit;
        return implode("\t", $rec);
    }
    //end for MarylandDNR ================================================================================================================

    //start for MarylandBio ==============================================================================================================
    function generate_MarylandBio_archive($csv_url)
    {
        $this->download_options['expire_seconds'] = 60*60*24; //expires in 24 hours - DATA-1712
        $folder = $this->folder;
        self::create_folder_if_does_not_exist($folder);
        $this->country_lat_lon = self::get_country_lat_lon(); // print_r($this->country_lat_lon);
        $filename = Functions::save_remote_file_to_local($csv_url, $this->download_options);
        self::process_csv($filename, $folder, "");
        self::last_part($folder);
        if($this->debug) print_r($this->debug);
        unlink($filename);
        if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
    }
    
    private function get_country_lat_lon()
    {
        $filename = Functions::save_remote_file_to_local($this->data_file[$this->folder], $this->download_options);
        $a = array(); $i = 0;
        if($file = Functions::file_open($filename, "r"))
        {
            while(!feof($file)) {
                $arr = fgetcsv($file);
                $i++;
                if($i != 1)
                {
                    if($country = @$arr[0]) $a[$country] = array("lat" => $arr[1], "lon" => @$arr[2]);
                }
            }
        }
        unlink($filename);
        return $a;
    }

    function process_rec_MarylandBio($rec)
    {   // 26 cols total: Kingdom,Class,OrderName,Family,Genus,Species,Author,Subspecies,Species_ID,Species_URL,Common_Name,RecordID,Month,Day,Year,County,QuadID,QuadName,QuatLat1,QuadLon1,QuadLat2,QuadLat3,Location,LocID,LocLat,LocLon
        $scientificName = trim($rec['Genus'].' '.$rec['Species'].' '.$rec['Subspecies']);
        $date = self::maryland_date($rec);
        
        $rek = array();
        $rek['id']              = $this->ctr;
        $rek['occurrenceID']    = $rec['RecordID'];
        
        if($rec['LocLat'] && $rec['LocLon'])
        {
            $rek['decimalLatitude'] = $rec['LocLat'];
            $rek['decimalLongitude'] = $rec['LocLon'];
        }
        elseif($county = $rec['County'])
        {
            if($this->country_lat_lon[$county]['lat'] && $this->country_lat_lon[$county]['lon'])
            {
                $rek['decimalLatitude'] = $this->country_lat_lon[$county]['lat'];
                $rek['decimalLongitude'] = $this->country_lat_lon[$county]['lon'];
            }
            else return false; //exclude if not both lat long are filled
        }
        else return false; //exclude if not both lat long are filled
        
        $rek['scientificName']  = $scientificName;
        $rek['genus']           = $rec['Genus'];
        $rek['species']         = $rec['Species'];
        $rek['vernacularName']  = $rec['Common_Name'];
        $rek['taxonRank']       = 'species';
        $rek['kingdom']         = $rec['Kingdom'];
        $rek['class']           = $rec['Class'];
        $rek['order']           = $rec['OrderName'];
        $rek['family']          = $rec['Family'];
        $rek['date']            = $date;
        $rek['month']           = $rec['Month'];
        $rek['day']             = $rec['Day'];
        $rek['year']            = $rec['Year'];
        $rek['county']          = $rec['County'];
        $rek['locality']        = $rec['Location'];
        $rek['source']          = $rec['Species_URL'];
        self::print_header($rek);
        return implode("\t", $rek);
    }
    
    private function maryland_date($rec)
    {   //desired format is: 2016-06-18
        if(@$rec['Month'] && @$rec['Day'] && strlen(@$rec['Year']) == 4) {
            $month = Functions::format_number_with_leading_zeros($rec['Month'], 2);
            $day = Functions::format_number_with_leading_zeros($rec['Day'], 2);
            return $rec['Year']."-".$month."-".$day;
        }
        return "";
    }
    //end for MarylandBio ================================================================================================================
    
    //start for usgs-nas ==============================================================================================================
    /* These are the unique list of groups:
                [Fishes] =>                 Animalia    [Plants] =>                 Plantae
                [Amphibians-Frogs] =>       Animalia    [Reptiles-Snakes] =>        Animalia
                [Mollusks-Bivalves] =>      Animalia    [Reptiles-Crocodilians] =>  Animalia
                [Amphibians-Salamanders] => Animalia    [Reptiles-Turtles] =>       Animalia
                [Crustaceans-Amphipods] =>  Animalia    [Crustaceans-Copepods] =>   Animalia
                [Crustaceans-Isopods] =>    Animalia    [Annelids-Hirundinea] =>    Animalia
                [Mollusks-Gastropods] =>    Animalia    [Coelenterates-Hydrozoans] =>   Animalia
                [Crustaceans-Cladocerans] =>    Animalia    [Rotifers] =>                   Animalia
                [Crustaceans-Crayfish] =>       Animalia    [Crustaceans-Shrimp] =>         Animalia
                [Mammals] =>                    Animalia    [Crustaceans-Crabs] =>          Animalia
                [Crustaceans-Mysids] =>     Animalia        [Bryozoans] =>              Animalia
                [Mollusks-Cephalopods] =>   Animalia        [Annelids-Oligochaetes] =>  Animalia
                [Entoprocts] =>             Animalia
    From the API, we can get the FAMILY of the species belonging to each of the groups.
    Do we need to fill-in the other: KINGDOM, PHYLUM, CLASS, ORDER? */
    
    function generate_usgs_archive($csv_url)
    {   /* steps:
        1. get species list here: from a [csv] button here: https://nas.er.usgs.gov/queries/SpeciesList.aspx?group=&genus=&species=&comname=&Sortby=1
        2. get occurrences for each species
        3. create the zip file
        */
        $this->download_options['expire_seconds'] = 60*60*24; //expires in 24 hours - DATA-1712
        $folder = $this->folder;
        self::create_folder_if_does_not_exist($folder);
        
        $options = $this->download_options;
        $options['resource_id'] = "usgs"; //a folder /usgs/ will be created in /eol_cache/
        $options['download_wait_time'] = 1000000; //1 second
        // $options['expire_seconds'] = false; //debug only - used only during development. Designed to expire in 28 days
        $options['download_attempts'] = 3;
        $options['delay_in_minutes'] = 2;
        
        $i = 0;
        $species_list = Functions::save_remote_file_to_local($csv_url, $this->download_options);
        foreach(new FileIterator($species_list) as $line_number => $line)
        {
            $line = str_replace(", ", ";", $line); //needed to do this bec of rows like e.g. "Fishes,Cyprinidae,Labeo chrysophekadion,,black sharkminnow, black labeo,Exotic,Freshwater";
            $arr = explode(",", $line);
            if(count($arr) == 7) 
            {
                $i++;
                // print_r($arr); //exit;
                $this->debug['group'][$arr[0]] = '';
                $group = $arr[0];
                $temp = explode(" ", $arr[2]); //scientificname
                $temp = array_map('trim', $temp);
                $genus = $temp[0];
                array_shift($temp);
                $species = trim(implode(" ", $temp));
                $species = urlencode($species);
                $offset = 0;
                
                /* breakdown when caching: as of Jun 5, 2017 total is 1,270
                $cont = false;
                // if($i >= 0    && $i < 250) $cont = true; 
                // if($i >= 250    && $i < 500) $cont = true; 
                // if($i >= 500    && $i < 750) $cont = true; 
                // if($i >= 750    && $i < 1000) $cont = true; 
                if($i >= 1000    && $i < 1300) $cont = true; 
                if(!$cont) continue;
                */
                
                while(true) {
                    $api = $this->service['usgs-nas']['occurrences'];
                    $api .= "?offset=$offset&genus=$genus&species=$species";
                    echo "\n[$api]";
                    if($json = Functions::lookup_with_cache($api, $options))
                    {
                        $recs = json_decode($json);
                        if(($i % 200) == 0) echo "\n$i. total: ".count($recs->results);
                        if($val = $recs->results) self::process_usgs_occurrence($val, $group);
                        // break; //debug
                        $offset += 100;
                        if($recs->endOfRecords == "true") break;
                        if(count($recs->results) < 100) break;
                    }
                    else break;
                }
            }
            // if($i > 10) break; //debug - to limit recs
        }
        echo "\ntotal: ".($i-1)."\n";
        self::last_part($folder); //this is a folder within CONTENT_RESOURCE_LOCAL_PATH
        // if($this->debug) print_r($this->debug);
        unlink($species_list);
        if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
    }
    
    private function process_usgs_occurrence($recs, $group)
    {
        $i = 0;
        $WRITE = Functions::file_open($this->destination['usgs-nas'], "a");
        foreach($recs as $rec) {
            $i++;
            if(($i % 1000) == 0) echo number_format($i) . "\n";
            if($rec) {
                $row = self::process_rec_USGS($rec, $group);
                if($row) fwrite($WRITE, $row . "\n");
            }
            // if($i > 5) break;  //debug only
        }
        fclose($WRITE);
    }
    
    function process_rec_USGS($rec, $group)
    {
        $rek = array();
        /* total of 11 columns
        $rek['id']               = $rec['key'];
        $rek['occurrenceID']     = $rec['museumCatNumber'];
        $rek['eventDate']        = $rec['date'];
        $rek['decimalLatitude']  = $rec['decimalLatitude'];
        $rek['decimalLongitude'] = $rec['decimalLongitude'];
        $rek['scientificName']   = $rec['scientificName'];
        $rek['taxonRank']        = 'species';
        $rek['kingdom']          = '';
        $rek['phylum']           = '';
        $rek['class']            = '';
        $rek['family']           = $rec['family'];
        $rek['basisOfRecord']    = $rec['recordType'];
        
        sample actual data
        [key] => 276594                                     done
        [speciesID] => 707                                  x
        [group] => Fishes                                   http://rs.tdwg.org/dwc/terms/group
        [family] => Gobiidae                                done
        [genus] => Acanthogobius                            http://rs.tdwg.org/dwc/terms/genus
        [species] => flavimanus                             http://rs.gbif.org/terms/1.0/species
        [scientificName] => Acanthogobius flavimanus        done
        [commonName] => Yellowfin Goby                      http://rs.tdwg.org/dwc/terms/vernacularName
        [state] => California                               http://rs.tdwg.org/dwc/terms/stateProvince
        [county] => San Diego                               http://rs.tdwg.org/dwc/terms/county
        [locality] => Mission Bay, off Fiesta Island        http://rs.tdwg.org/dwc/terms/locality
        [decimalLatitude] => 32.778904
        [decimalLongitude] => -117.224078
        [huc8Name] => San Diego
        [huc8] => 18070304
        [date] => 2003-6-13                                 http://purl.org/dc/terms/date
        [year] => 2003                                      http://rs.tdwg.org/dwc/terms/year
        [month] => 6                                        http://rs.tdwg.org/dwc/terms/month
        [day] => 13                                         http://rs.tdwg.org/dwc/terms/day
        [status] => established                             x
        [comments] =>                                       x
        [recordType] => Literature                          done
        [disposal] => Scripps Institution of Oceanography   x
        [museumCatNumber] => SIO 03-78                      http://rs.tdwg.org/dwc/terms/catalogNumber
        [freshMarineIntro] => Brackish                      x
        */
        
        //total of 22 columns
        if(!isset($this->debug['key'][$rec->key])) $this->debug['key'][$rec->key] = '';
        else return false; //print("\nkey duplicate: $rec->key\n");
        $this->ctr++;
        $rek['id']  = $this->ctr;
        $rek['occurrenceID']  = $rec->key;
        $rek['eventDate']  = @$rec->date;
        $rek['decimalLatitude']  = $rec->decimalLatitude;
        $rek['decimalLongitude']  = $rec->decimalLongitude;
        $rek['scientificName']  = $rec->scientificName;
        $rek['taxonRank']  = 'species';
        $rek['kingdom']  = ($group == "Plants" ? "Plantae" : "Animalia");
        $rek['family']  = $rec->family;
        $rek['basisOfRecord']  = $rec->recordType;
        $rek['group']  = $rec->group;
        $rek['genus']  = $rec->genus;
        $rek['species']  = $rec->species;
        $rek['vernacularName']  = $rec->commonName;
        $rek['stateProvince']  = $rec->state;
        $rek['county']  = $rec->county;
        $rek['locality']  = $rec->locality;
        $rek['date']  = @$rec->date;
        $rek['year']  = $rec->year;
        $rek['month']  = $rec->month;
        $rek['day']  = $rec->day;
        $rek['catalogNumber']  = $rec->museumCatNumber;
        $rek['source']  = "https://nas.er.usgs.gov/queries/SpecimenViewer.aspx?SpecimenID=".$rec->key;
        self::print_header($rek);
        return implode("\t", $rek);
        /*
        [group] => Fishes                                   http://rs.tdwg.org/dwc/terms/group
        [genus] => Acanthogobius                            http://rs.tdwg.org/dwc/terms/genus
        [species] => flavimanus                             http://rs.gbif.org/terms/1.0/species
        [commonName] => Yellowfin Goby                      http://rs.tdwg.org/dwc/terms/vernacularName
        [state] => California                               http://rs.tdwg.org/dwc/terms/stateProvince
        [county] => San Diego                               http://rs.tdwg.org/dwc/terms/county
        [locality] => Mission Bay, off Fiesta Island        http://rs.tdwg.org/dwc/terms/locality
        [date] => 2003-6-13                                 http://purl.org/dc/terms/date
        [year] => 2003                                      http://rs.tdwg.org/dwc/terms/year
        [month] => 6                                        http://rs.tdwg.org/dwc/terms/month
        [day] => 13                                         http://rs.tdwg.org/dwc/terms/day
        [museumCatNumber] => SIO 03-78                      http://rs.tdwg.org/dwc/terms/catalogNumber
        source                                              http://purl.org/dc/terms/source --- per request: https://eol-jira.bibalex.org/browse/DATA-1683?focusedCommentId=61244&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-61244
        */
    }
    //end for usgs-nas ================================================================================================================

    //start for eMammal ==============================================================================================================
    function generate_eMammal_archive($local_path)
    {
        $folder = $this->folder;
        self::create_folder_if_does_not_exist($folder);
        foreach(glob("$local_path/*.csv") as $filename)
        {
            echo "\n$filename";
            self::process_csv($filename, "eMammal");
            // break; //debug - just process 1 csv file
        }
        
        self::last_part($folder);
        if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
        if($this->debug) print_r($this->debug);
    }

    function process_rec_eMammal($rec)
    {   // id   occurrenceID    eventDate   decimalLatitude decimalLongitude    scientificName  taxonRank   kingdom phylum  class   family
        $rek = array();
        /* total of 11 columns
        $rek['id']               = $rec['id'];
        $rek['occurrenceID']     = $rec['Sequence ID'];
        $rek['eventDate']        = $rec['Begin Time'];
        $rek['decimalLatitude']  = $rec['Actual Lat'];
        $rek['decimalLongitude'] = $rec['Actual Lon'];
        $rek['scientificName']   = $rec['Species Name'];
        $rek['taxonRank']        = 'species';
        $rek['kingdom']          = 'Animalia';
        $rek['phylum']           = 'Chordata';
        $rek['class']            = 'Mammalia';
        $rek['family']           = '';
        
        [Subproject] => White Rock
        [Treatment] => 
        [Deployment Name] => WhiteRock02_062016
        [ID Type] => Researcher
        [Deploy ID] => d20200
        [Sequence ID] => d20200s10
        [Begin Time] => 2016-06-16T10:42:28
        [End Time] => 2016-06-16T10:43:24
        [Species Name] => No Animal
        [Common Name] => No Animal
        [Age] => 
        [Sex] => 
        [Individual] => 
        [Count] => 1
        [Actual Lat] => 48.01166
        [Actual Lon] => -108.00895
        [id] => 1
        */
        
        $taxon = $rec['Species Name'];
        if(stripos($taxon, 'Vehicle') !== false || stripos($taxon, 'Human') !== false ) return false; //string is found
        if(stripos($taxon, 'Unknown') !== false || stripos($taxon, 'Animal') !== false ) return false; //string is found
        if(stripos($taxon, 'Camera') !== false || stripos($taxon, 'Calibration') !== false ) return false; //string is found
        if(stripos($taxon, 'sapiens') !== false || stripos($taxon, 'Homo') !== false ) return false; //string is found
        if(stripos($taxon, 'other') !== false || stripos($taxon, 'species') !== false ) return false; //string is found
        
        //total of 11 columns
        $rek['id'] = $rec['id'];
        $rek['occurrenceID'] = $rec['Sequence ID'];
        $rek['eventDate'] = $rec['Begin Time'];
        $rek['decimalLatitude'] = $rec['Actual Lat'];
        $rek['decimalLongitude'] = $rec['Actual Lon'];
        if(stripos($taxon, ' spp.') !== false || stripos($taxon, ' sp.') !== false ) //string is found
        {
            $taxon = str_ireplace(" spp.", "", $taxon);
            $taxon = str_ireplace(" sp.", "", $taxon);
            $rek['scientificName'] = $taxon;
            $rek['taxonRank'] = '';
        }
        else
        {
            $rek['scientificName'] = $taxon;
            $rek['taxonRank'] = 'species';
        }
        $rek['kingdom'] = 'Animalia';
        $rek['phylum'] = 'Chordata';
        $rek['class'] = 'Mammalia';
        $rek['family'] = '';
        
        self::print_header($rek);
        return implode("\t", $rek);
    }
    //end for eMammal ================================================================================================================

    //start for Reeflife ================================================================================================================
    function generate_ReefLifeSurvey_archive($params)
    {
        $this->download_options['expire_seconds'] = 60*60*24; //expires in 24 hours - DATA-1712
        $folder = $this->folder;
        self::create_folder_if_does_not_exist($folder);
        
        $collections = array("Global reef fish dataset", "Invertebrates");
        // $collections = array("Invertebrates"); //debug only
        foreach($collections as $coll)
        {
            $url = $params[$coll]; //csv url path
            $temp_path = Functions::save_remote_file_to_local($url, $this->download_options);
            self::process_csv($temp_path, "reef-life-survey", $coll);
            unlink($temp_path);
        }

        self::last_part($folder);
        if($folder) recursive_rmdir(CONTENT_RESOURCE_LOCAL_PATH . $folder);
        if($this->debug) print_r($this->debug);
    }
    
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
        $rek['id'] = $rec['id'];
        if($collection == "Global reef fish dataset") $rek['occurrenceID'] = $rec['SurveyID'] . "_" . $rec['id'];
        elseif($collection == "Invertebrates")        $rek['occurrenceID'] = $rec['FID'];
        $rek['eventDate'] = $rec['SurveyDate'];
        $rek['decimalLatitude'] = $rec['SiteLat'];
        $rek['decimalLongitude'] = $rec['SiteLong'];
        
        $taxon = $rec['Taxon'];
        if(stripos($taxon, ' spp.') !== false || stripos($taxon, ' sp.') !== false ) //string is found
        {
            $taxon = str_ireplace(" spp.", "", $taxon);
            $taxon = str_ireplace(" sp.", "", $taxon);
            $rek['scientificName'] = $taxon;
            $rek['taxonRank'] = '';
        }
        else
        {
            $rek['scientificName'] = $taxon;
            $rek['taxonRank'] = 'species';
        }
        $rek['kingdom'] = 'Animalia';
        $rek['phylum'] = $rec['Phylum'];
        $rek['class'] = $rec['Class'];
        $rek['family'] = $rec['Family'];
        self::print_header($rek);
        return implode("\t", $rek);
    }
    //end for Reeflife ================================================================================================================
    
    function last_part($folder, $txt_file = "observations.txt")
    {
        self::generate_meta_xml_v2($folder, $txt_file); //creates a meta.xml file

        //copy 2 files inside /reef-life-survey/
        copy(CONTENT_RESOURCE_LOCAL_PATH . "$folder/$txt_file"  , CONTENT_RESOURCE_LOCAL_PATH . "$folder/$txt_file");
        copy(CONTENT_RESOURCE_LOCAL_PATH . "$folder/meta.xml"   , CONTENT_RESOURCE_LOCAL_PATH . "$folder/meta.xml");

        //create reef-life-survey.tar.gz
        $command_line = "zip -rj " . CONTENT_RESOURCE_LOCAL_PATH . str_replace("_","-",$folder) . ".zip " . CONTENT_RESOURCE_LOCAL_PATH . $folder . "/"; //may need 'sudo zip -rj...'
        $output = shell_exec($command_line);
        
        $arr = Functions::count_resource_tab_files($folder, ".txt");
        Functions::finalize_connector_run($folder, json_encode($arr));
    }
    
    function process_csv($csv_file, $dbase, $collection = "")
    {
        $arr = self::get_fields_as_array($csv_file);
        $field_count = count($arr);

        $i = 0;
        if(!$file = Functions::file_open($csv_file, "r")) {
            echo "\nerror 1\n";
            return;
        }
        if(!$WRITE = Functions::file_open($this->destination[$dbase], "a")) {
            echo "\nerror 2\n";
            return;
        }
        
        while(!feof($file)) {
            $temp = fgetcsv($file);
            $i++;
            if(($i % 10000) == 0) echo number_format($i) . "\n";
            if($i == 1)
            {
                $fields = $temp;
                // print_r($fields); //exit;
                if(count($fields) != $field_count)
                {
                    $this->debug["not20"][$fields[0]] = '';
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
                if(count($temp) != $field_count)
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
                    if    ($dbase == "reef-life-survey") $row = self::process_rec_RLS($rec, $collection);
                    elseif($dbase == "eMammal")          $row = self::process_rec_eMammal($rec);
                    elseif($dbase == "MarylandBio")      $row = self::process_rec_MarylandBio($rec);
                    else echo "\n --undefine dbase-- \n";
                    if($row) fwrite($WRITE, $row . "\n");
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

    function create_folder_if_does_not_exist($folder)
    {
        if(!file_exists(CONTENT_RESOURCE_LOCAL_PATH . $folder)) {
            /* orig
            $command_line = "mkdir " . CONTENT_RESOURCE_LOCAL_PATH . "$folder"; //may need 'sudo mkdir'
            $output = shell_exec($command_line);
            */
            mkdir(CONTENT_RESOURCE_LOCAL_PATH . $folder, 0777, true);
        }
        //will delete zip file so Jenkins and cron can both create and delete its version of the zip file
        $zip_file = CONTENT_RESOURCE_LOCAL_PATH . $folder. ".zip";
        if(file_exists($zip_file))
        {
            $s = unlink($zip_file);
            echo "\nunlink [$zip_file: $s]\n";
        }
    }
    
    function generate_meta_xml_v2($folder, $txt_file)
    {
        if(!$WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "$folder/meta.xml", "w")) return;
        fwrite($WRITE, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($WRITE, '<archive xmlns="http://rs.tdwg.org/dwc/text/">' . "\n");
        fwrite($WRITE, '  <core encoding="UTF-8" linesTerminatedBy="\n" fieldsTerminatedBy="\t" fieldsEnclosedBy="" ignoreHeaderLines="1" rowType="http://rs.tdwg.org/dwc/terms/Occurrence">' . "\n");
        fwrite($WRITE, '    <files>' . "\n");
        fwrite($WRITE, '      <location>'.$txt_file.'</location>' . "\n");
        fwrite($WRITE, '    </files>' . "\n");
        fwrite($WRITE, '    <id index="0"/>' . "\n");
        $terms = self::get_terms();
        $i = 0;
        foreach($this->dwca_fields as $term) {
            fwrite($WRITE, '    <field index="'.$i.'" term="'.$terms[$term].'"/>' . "\n");
            $i++;
        }
        fwrite($WRITE, '  </core>' . "\n");
        fwrite($WRITE, '</archive>' . "\n");
        fclose($WRITE);
    }
    
    private function get_fields_as_array($filename)
    {
        if($file = Functions::file_open($filename, "r")) {
            while(!feof($file)) {
                return fgetcsv($file);
                break; //just get one line
            }
        }
        return false;
    }
    
    function print_header($rek, $filename = null) //$filename here comes from FreshDataGlobiAPI.php
    {
        if(!$filename) $filename = $this->destination[$this->folder]; //orig used here FreeDataAPI
        if($this->print_header)
        {
            //first row - headers of text file
            $WRITE = Functions::file_open($filename, "w");
            fwrite($WRITE, implode("\t", array_keys($rek)) . "\n");
            fclose($WRITE);
            $this->print_header = false;
        }
        $this->dwca_fields = array_keys($rek);
    }

    function get_terms()
    {
        //added for smasher dynamic hierarchy
        $terms['acceptedNameUsageID'] = "http://rs.tdwg.org/dwc/terms/acceptedNameUsageID";
        $terms['parentNameUsageID'] = "http://rs.tdwg.org/dwc/terms/parentNameUsageID";
        $terms['taxonomicStatus'] = "http://rs.tdwg.org/dwc/terms/taxonomicStatus";
        $terms['canonicalName'] = "http://rs.gbif.org/terms/1.0/canonicalName";
        $terms['scientificNameAuthorship'] = "http://rs.tdwg.org/dwc/terms/scientificNameAuthorship";
        $terms['scientificNameID'] = "http://rs.tdwg.org/dwc/terms/scientificNameID";
        $terms['taxonRemarks'] = "http://rs.tdwg.org/dwc/terms/taxonRemarks";
        $terms['namePublishedIn'] = "http://rs.tdwg.org/dwc/terms/namePublishedIn";
        $terms['furtherInformationURL'] = "http://rs.tdwg.org/ac/terms/furtherInformationURL";
        $terms['datasetID'] = "http://rs.tdwg.org/dwc/terms/datasetID";
        $terms['EOLid'] = "http://eol.org/schema/EOLid";
        $terms['EOLidAnnotations'] = "http://eol.org/schema/EOLidAnnotations";
        
        //added for BISON
        $terms['countryCode'] = "http://rs.tdwg.org/dwc/terms/countryCode";
        $terms['institutionID'] = "http://rs.tdwg.org/dwc/terms/institutionID";
        $terms['institutionCode'] = "http://rs.tdwg.org/dwc/terms/institutionCode";
        $terms['subgenus'] = "http://rs.tdwg.org/dwc/terms/subgenus";
        $terms['higherClassification'] = "http://rs.tdwg.org/dwc/terms/higherClassification";
        $terms['recordedBy'] = "http://rs.tdwg.org/dwc/terms/recordedBy";
        $terms['ITISscientificName'] = "http://itis.gov/terms/ITISscientificName";
        
        //added iNat supplemental resource
        $terms['modified'] = "http://purl.org/dc/terms/modified";
        $terms['created'] = "http://purl.org/dc/terms/created";
        $terms['uuid'] = "https://en.wikipedia.org/wiki/Universally_unique_identifier";
        
        //for orig FreshData processing
        $terms['id'] = "http://rs.gbif.org/terms/1.0/RLSID";
        $terms['occurrenceID'] = "http://rs.tdwg.org/dwc/terms/occurrenceID";
        $terms['decimalLatitude'] = "http://rs.tdwg.org/dwc/terms/decimalLatitude";
        $terms['decimalLongitude'] = "http://rs.tdwg.org/dwc/terms/decimalLongitude";
        $terms['scientificName'] = "http://rs.tdwg.org/dwc/terms/scientificName";
        $terms['taxonRank'] = "http://rs.tdwg.org/dwc/terms/taxonRank";
        $terms['kingdom'] = "http://rs.tdwg.org/dwc/terms/kingdom";
        $terms['phylum'] = "http://rs.tdwg.org/dwc/terms/phylum";
        $terms['class'] = "http://rs.tdwg.org/dwc/terms/class";
        $terms['order'] = "http://rs.tdwg.org/dwc/terms/order";
        $terms['family'] = "http://rs.tdwg.org/dwc/terms/family";
        $terms['genus'] = "http://rs.tdwg.org/dwc/terms/genus";
        $terms['species'] = "http://rs.gbif.org/terms/1.0/species";
        $terms['vernacularName'] = "http://rs.tdwg.org/dwc/terms/vernacularName";
        $terms['basisOfRecord'] = "http://rs.tdwg.org/dwc/terms/basisOfRecord";
        $terms['group'] = "http://rs.tdwg.org/dwc/terms/group";
        $terms['stateProvince'] = "http://rs.tdwg.org/dwc/terms/stateProvince";
        $terms['county'] = "http://rs.tdwg.org/dwc/terms/county";
        $terms['locality'] = "http://rs.tdwg.org/dwc/terms/locality";
        $terms['eventDate'] = "http://rs.tdwg.org/dwc/terms/eventDate";
        $terms['date'] = "http://purl.org/dc/terms/date";
        $terms['year'] = "http://rs.tdwg.org/dwc/terms/year";
        $terms['month'] = "http://rs.tdwg.org/dwc/terms/month";
        $terms['day'] = "http://rs.tdwg.org/dwc/terms/day";
        $terms['catalogNumber'] = "http://rs.tdwg.org/dwc/terms/catalogNumber";
        $terms['taxonID'] = "http://rs.tdwg.org/dwc/terms/taxonID";
        $terms['lifeStage'] = "http://rs.tdwg.org/dwc/terms/lifeStage";
        $terms['sex'] = "http://rs.tdwg.org/dwc/terms/sex";
        $terms['bibliographicCitation'] = "http://purl.org/dc/terms/bibliographicCitation";
        $terms['source'] = "http://purl.org/dc/terms/source";
        $terms['samplingProtocol'] = "http://rs.tdwg.org/dwc/terms/samplingProtocol";
        $terms['measurementRemarks'] = "http://rs.tdwg.org/dwc/terms/measurementRemarks";
        $terms['samplingEffort'] = "http://rs.tdwg.org/dwc/terms/samplingEffort";
        $terms['individualCount'] = "http://rs.tdwg.org/dwc/terms/individualCount";
        return $terms;
    }
    
    /*
    function generate_meta_xml($folder)
    {
        if(!$WRITE = Functions::file_open(CONTENT_RESOURCE_LOCAL_PATH . "$folder/meta.xml", "w")) return;
        fwrite($WRITE, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($WRITE, '<archive xmlns="http://rs.tdwg.org/dwc/text/">' . "\n");
        fwrite($WRITE, '  <core encoding="UTF-8" linesTerminatedBy="\n" fieldsTerminatedBy="\t" fieldsEnclosedBy="" ignoreHeaderLines="1" rowType="http://rs.tdwg.org/dwc/terms/Occurrence">' . "\n");
        fwrite($WRITE, '    <files>' . "\n");
        fwrite($WRITE, '      <location>observations.txt</location>' . "\n");
        fwrite($WRITE, '    </files>' . "\n");
        fwrite($WRITE, '    <id index="0"/>' . "\n");
        if(in_array($folder, array("reef-life-survey", "eMammal")))
        {
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
        }
        elseif($folder == "usgs_nonindigenous_aquatic_species") {}
        ..and other dbases
        fwrite($WRITE, '  </core>' . "\n");
        fwrite($WRITE, '</archive>' . "\n");
        fclose($WRITE);
    }
    */
    
}
?>
