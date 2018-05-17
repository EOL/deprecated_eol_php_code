<?php
namespace php_active_record;
/* connector: [26] WORMS archive connector
We received a Darwincore archive file from the partner.
Connector downloads the archive file, extracts, reads it, assembles the data and generates the EOL DWC-A resource.

[establishmentMeans] => Array
       (
           [] => 
           [Alien] =>                   used
           [Native - Endemic] =>        used
           [Native] =>                  used
           [Origin uncertain] => 
           [Origin unknown] => 
           [Native - Non-endemic] =>    used
       )
   [occurrenceStatus] => Array
       (
           [present] =>                 used
           [excluded] =>                used
           [doubtful] =>                used
       )

http://www.marinespecies.org/rest/#/
http://www.marinespecies.org/aphia.php?p=taxdetails&id=9
*/

class WormsArchiveAPI
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->object_ids = array();
        // $this->dwca_file = "http://localhost/cp/WORMS/WoRMS2EoL.zip";                            //local - when developing only
        // $this->dwca_file = "http://localhost/cp/WORMS/Archive.zip";                              //local subset copy
        $this->dwca_file = "http://www.marinespecies.org/export/eol/WoRMS2EoL.zip";              //WORMS online copy
        $this->occurrence_ids = array();
        $this->taxon_page = "http://www.marinespecies.org/aphia.php?p=taxdetails&id=";
        
        $this->webservice['AphiaClassificationByAphiaID'] = "http://www.marinespecies.org/rest/AphiaClassificationByAphiaID/";
        $this->webservice['AphiaRecordByAphiaID']         = "http://www.marinespecies.org/rest/AphiaRecordByAphiaID/";
        $this->webservice['AphiaChildrenByAphiaID']       = "http://www.marinespecies.org/rest/AphiaChildrenByAphiaID/";
        
        $this->download_options = array('download_wait_time' => 1000000, 'timeout' => 1200, 'download_attempts' => 2, 'delay_in_minutes' => 1, 'resource_id' => 26);
        $this->download_options["expire_seconds"] = false; //debug - false means it will use cache
        $this->debug = array();
        
        $this->gnsparser = "http://parser.globalnames.org/api?q=";
        $this->smasher_download_options = array(
            'cache_path'         => '/Volumes/AKiTiO4/eol_cache_smasher/',
            'download_wait_time' => 500000, 'timeout' => 600, 'download_attempts' => 1, 'delay_in_minutes' => 0, 'expire_seconds' => false);
    }

    private function get_valid_parent_id($id)
    {
        $taxa = self::AphiaClassificationByAphiaID($id);
        $last_rec = end($taxa);
        return $last_rec['parent_id'];
    }

    function get_all_taxa($what)
    {
        $temp = CONTENT_RESOURCE_LOCAL_PATH . "26_files";
        if(!file_exists($temp)) mkdir($temp);
        $this->what = $what; //either 'taxonomy' or 'media_objects'

        /* last 2 bad parents:
                Cristellaria Lamarck, 1816 (worms#390648)
                Corbiculidae Gray, 1847 (worms#414789)
        And there are six descendants of bad parents respectively:
                *Cristellaria arcuatula Stache, 1864 (worms#895743)
                *Cristellaria foliata Stache, 1864 (worms#903431)
                *Cristellaria vestuta d'Orbigny, 1850 (worms#924530)
                *Cristellaria obtusa (worms#925572)
                *Corbiculina Dall, 1903 (worms#818186)
                *Cyrenobatissa Suzuki & Oyama, 1943 (worms#818201)            
        */

        /* tests
        $this->children_of_synonyms = array(14769, 735405);
        $id = "24"; $id = "142"; $id = "5"; $id = "25"; $id = "890992"; $id = "834546";
        $id = "379702"; 
        $id = "127";
        $id = "14769";
        $id = "930326";

        $x2 = self::get_valid_parent_id($id);
        echo "\n parent_id from api: $x1\n";
        exit("\n valid parent_id: $x2\n");
        exit("\n");
        */
        
        /* tests
        $this->synonyms_without_children = self::get_synonyms_without_children(); //used so script will no longer lookup if this syn is known to have no children.
        // $taxo_tmp = self::get_children_of_taxon("100795");
        // $taxo_tmp = self::get_children_of_taxon(13);
        // $taxo_tmp = self::get_children_of_taxon(510462);
        $taxo_tmp = self::get_children_of_taxon("390648");
        print_r($taxo_tmp); exit("\n[".count($taxo_tmp)."] elix\n");
        */
        
        /* tests
        $this->synonyms_without_children = self::get_synonyms_without_children(); //used so script will no longer lookup if this syn is known to have no children.
        $ids = self::get_all_ids_to_prune();
        print_r($ids); exit("\n[".count($ids)."] total IDs to prune\n");
        */
        
        /*
        $str = "Cyclostomatida  incertae sedis";
        // $str = "Tubuliporoidea Incertae sedis";
        $str = "Lyssacinosida    incertae Sedis Tabachnick, 2002";
        echo "\n[$str]\n";
        $str = self::format_incertae_sedis($str);
        exit("\n[$str]\n");
        */
        
        
        require_library('connectors/INBioAPI');
        $func = new INBioAPI();
        $paths = $func->extract_archive_file($this->dwca_file, "meta.xml", array('timeout' => 172800, 'expire_seconds' => true)); //true means it will re-download, will not use cache. Set TRUE when developing
        $archive_path = $paths['archive_path'];
        $temp_dir = $paths['temp_dir'];

        $harvester = new ContentArchiveReader(NULL, $archive_path);
        $tables = $harvester->tables;
        if(!($this->fields["taxa"] = $tables["http://rs.tdwg.org/dwc/terms/taxon"][0]->fields)) // take note the index key is all lower case
        {
            debug("Invalid archive file. Program will terminate.");
            return false;
        }

        if($this->what == "taxonomy")
        {
            /* First, get all synonyms, then using api, get the list of children, then exclude these children
            Based on latest: https://eol-jira.bibalex.org/browse/TRAM-520?focusedCommentId=60756&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-60756
            */
            $this->children_of_synonyms = self::get_all_children_of_synonyms($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon')); //then we will exclude this in the main operation

            // /* uncomment in real operation
            //add ids to prune for those to be excluded: https://eol-jira.bibalex.org/browse/TRAM-520?focusedCommentId=60923&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-60923
            echo "\nBuilding up IDs to prune...\n"; $ids = self::get_all_ids_to_prune();
            $this->children_of_synonyms = array_merge($this->children_of_synonyms, $ids);
            $this->children_of_synonyms = array_unique($this->children_of_synonyms);
            // */
            
        }
        // exit("\n building up list of children of synonyms \n"); //comment in normal operation

        echo "\n1 of 8\n";  self::build_taxa_rank_array($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon'));
        echo "\n2 of 8\n";  self::create_instances_from_taxon_object($harvester->process_row_type('http://rs.tdwg.org/dwc/terms/Taxon'));
        echo "\n3 of 8\n";  self::add_taxa_from_undeclared_parent_ids();
        if($this->what == "media_objects") {
            echo "\n4 of 8\n";  self::get_objects($harvester->process_row_type('http://eol.org/schema/media/Document'));
            echo "\n5 of 8\n";  self::get_references($harvester->process_row_type('http://rs.gbif.org/terms/1.0/Reference'));
            echo "\n6 of 8\n";  self::get_agents($harvester->process_row_type('http://eol.org/schema/agent/Agent'));
            echo "\n7 of 8\n";  self::get_vernaculars($harvester->process_row_type('http://rs.gbif.org/terms/1.0/VernacularName'));
        }
        unset($harvester);
        echo "\n8 of 8\n";  $this->archive_builder->finalize(TRUE);

        // remove temp dir
        recursive_rmdir($temp_dir);
        echo ("\n temporary directory removed: " . $temp_dir);
        print_r($this->debug);
    }

    private function process_fields($records, $class)
    {
        foreach($records as $rec)
        {
            if    ($class == "vernacular") $c = new \eol_schema\VernacularName();
            elseif($class == "agent")      $c = new \eol_schema\Agent();
            elseif($class == "reference")  $c = new \eol_schema\Reference();
            $keys = array_keys($rec);
            foreach($keys as $key)
            {
                $temp = pathinfo($key);
                $field = $temp["basename"];

                // some fields have '#', e.g. "http://schemas.talis.com/2005/address/schema#localityName"
                $parts = explode("#", $field);
                if($parts[0]) $field = $parts[0];
                if(@$parts[1]) $field = $parts[1];

                $c->$field = $rec[$key];
                if($field == "taxonID") $c->$field = self::get_worms_taxon_id($c->$field);
            }
            $this->archive_builder->write_object_to_file($c);
        }
    }

    /*
    synonym ->  379702	WoRMS:citation:379702	255040	Leptasterias epichlora (Brandt, 1835)
    child ->    934667	WoRMS:citation:934667		Leptasterias epichlora alaskensis Verrill, 1914	Verrill, A.E. (1914).
    child ->    934669	WoRMS:citation:934669		Leptasterias epichlora alaskensis var. siderea Verrill, 1914	Verrill, A.E. (1914). Monograph of the shallow-water 
    */
    private function get_all_children_of_synonyms($records = array())
    {
        $this->synonyms_without_children = self::get_synonyms_without_children(); //used so script will no longer lookup if this syn is known to have no children.
        //=====================================
        // /* commented when building up the file 26_children_of_synonyms.txt. 6 connectors running during build-up ----- COMMENT DURING BUILD-UP WITH 6 CONNECTORS, BUT UN-COMMENT IN REAL OPERATION ----- 
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "26_files/" . $this->resource_id . "_children_of_synonyms.txt";
        if(file_exists($filename))
        {
            $txt = file_get_contents($filename);
            $AphiaIDs = explode("\n", $txt);
            $AphiaIDs = array_filter($AphiaIDs);
            $AphiaIDs = array_unique($AphiaIDs);
            return $AphiaIDs;
        }
        // */
        
        // Continues here if 26_children_of_synonyms.txt hasn't been created yet.
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "26_files/" . $this->resource_id . "_children_of_synonyms.txt";
        $WRITE = fopen($filename, "a");
        
        $AphiaIDs = array();
        $i = 0; //for debug
        $k = 0; $m = count($records)/6; //100000; //only for breakdown when caching
        foreach($records as $rec)
        {
            $k++; echo " ".number_format($k)." ";
            /* breakdown when caching: total ~565,280
            $cont = false;
            // if($k >=  1    && $k < $m) $cont = true;     //1 -   100,000
            // if($k >=  $m   && $k < $m*2) $cont = true;   //100,000 - 200,000
            // if($k >=  $m*2 && $k < $m*3) $cont = true;   //200,000 - 300,000
            // if($k >=  $m*3 && $k < $m*4) $cont = true;   //300,000 - 400,000
            // if($k >=  $m*4 && $k < $m*5) $cont = true;   //400,000 - 500,000
            // if($k >=  $m*5 && $k < $m*6) $cont = true;   //500,000 - 600,000
            if(!$cont) continue;
            */
            
            $status = $rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
            
            //special case where "REMAP_ON_EOL" -> status also becomes 'synonym'
            $taxonRemarks = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRemarks"];
            if(is_numeric(stripos($taxonRemarks, 'REMAP_ON_EOL'))) $status = "synonym";
            
            if($status == "synonym")
            {
                $i++;
                $taxon_id = self::get_worms_taxon_id($rec["http://rs.tdwg.org/dwc/terms/taxonID"]);
                $taxo_tmp = self::get_children_of_taxon($taxon_id);
                if($taxo_tmp) fwrite($WRITE, implode("\n", $taxo_tmp) . "\n");
                // if($i >= 10) break; //debug
            }
        }
        fclose($WRITE);

        // /* //to make unique rows -> call the same function -> uncomment in real operation --- COMMENT DURING BUILD-UP WITH 6 CONNECTORS, BUT UN-COMMENT IN REAL OPERATION
        $AphiaIDs = self::get_all_children_of_synonyms();
        //save to text file
        $WRITE = fopen($filename, "w"); //will overwrite existing
        fwrite($WRITE, implode("\n", $AphiaIDs) . "\n");
        fclose($WRITE);
        // */
        
        return $AphiaIDs;
        /* sample children of a synonym e.g. AphiaID = 13
        [147416] =>
        [24] =>
        [147698] =>
        */
    }
    
    private function get_children_of_taxon($taxon_id)
    {
        $taxo_tmp = array();
        //start ====
        $temp = self::get_children_of_synonym($taxon_id);
        $taxo_tmp = array_merge($taxo_tmp, $temp);

        //start 2nd loop -> process children of children
        foreach($temp as $id)
        {
            $temp2 = self::get_children_of_synonym($id);
            $taxo_tmp = array_merge($taxo_tmp, $temp2);
            //start 3rd loop -> process children of children of children
            foreach($temp2 as $id)
            {
                $temp3 = self::get_children_of_synonym($id);
                $taxo_tmp = array_merge($taxo_tmp, $temp3);
                //start 4th loop -> process children of children of children
                foreach($temp3 as $id)
                {
                    $temp4 = self::get_children_of_synonym($id);
                    $taxo_tmp = array_merge($taxo_tmp, $temp4);
                    //start 5th loop -> process children of children of children
                    foreach($temp4 as $id)
                    {
                        $temp5 = self::get_children_of_synonym($id);
                        $taxo_tmp = array_merge($taxo_tmp, $temp5);
                        //start 6th loop -> process children of children of children
                        foreach($temp5 as $id)
                        {
                            $temp6 = self::get_children_of_synonym($id);
                            $taxo_tmp = array_merge($taxo_tmp, $temp6);
                            //start 7th loop -> process children of children of children
                            foreach($temp6 as $id)
                            {
                                $temp7 = self::get_children_of_synonym($id);
                                $taxo_tmp = array_merge($taxo_tmp, $temp7);
                                //start 8th loop -> process children of children of children
                                foreach($temp7 as $id)
                                {
                                    $temp8 = self::get_children_of_synonym($id);
                                    $taxo_tmp = array_merge($taxo_tmp, $temp8);
                                    //start 9th loop -> process children of children of children
                                    foreach($temp8 as $id)
                                    {
                                        $temp9 = self::get_children_of_synonym($id);
                                        $taxo_tmp = array_merge($taxo_tmp, $temp9);
                                        //start 10th loop -> process children of children of children
                                        foreach($temp9 as $id)
                                        {
                                            $temp10 = self::get_children_of_synonym($id);
                                            $taxo_tmp = array_merge($taxo_tmp, $temp10);
                                            //start 11th loop -> process children of children of children
                                            foreach($temp10 as $id)
                                            {
                                                $temp11 = self::get_children_of_synonym($id);
                                                $taxo_tmp = array_merge($taxo_tmp, $temp11);
                                                //start 12th loop -> process children of children of children
                                                foreach($temp11 as $id)
                                                {
                                                    $temp12 = self::get_children_of_synonym($id);
                                                    $taxo_tmp = array_merge($taxo_tmp, $temp12);
                                                    //start 13th loop -> process children of children of children
                                                    foreach($temp12 as $id)
                                                    {
                                                        print("\nreaches 13th loop\n");
                                                        $temp13 = self::get_children_of_synonym($id);
                                                        $taxo_tmp = array_merge($taxo_tmp, $temp13);
                                                        //start 14th loop -> process children of children of children
                                                        foreach($temp13 as $id)
                                                        {
                                                            print("\nreaches 14th loop\n");
                                                            $temp14 = self::get_children_of_synonym($id);
                                                            $taxo_tmp = array_merge($taxo_tmp, $temp14);
                                                            //start 15th loop -> process children of children of children
                                                            foreach($temp14 as $id)
                                                            {
                                                                print("\nreaches 15th loop\n");
                                                                $temp15 = self::get_children_of_synonym($id);
                                                                $taxo_tmp = array_merge($taxo_tmp, $temp15);
                                                                //start 16th loop -> process children of children of children
                                                                foreach($temp15 as $id)
                                                                {
                                                                    print("\nreaches 16th loop\n");
                                                                    $temp16 = self::get_children_of_synonym($id);
                                                                    $taxo_tmp = array_merge($taxo_tmp, $temp16);
                                                                    //start 17th loop -> process children of children of children
                                                                    foreach($temp16 as $id)
                                                                    {
                                                                        print("\nreaches 17th loop\n");
                                                                        $temp17 = self::get_children_of_synonym($id);
                                                                        $taxo_tmp = array_merge($taxo_tmp, $temp17);
                                                                        //start 18th loop -> process children of children of children
                                                                        foreach($temp17 as $id)
                                                                        {
                                                                            exit("\nreaches 18th loop\n");
                                                                            $temp18 = self::get_children_of_synonym($id);
                                                                            $taxo_tmp = array_merge($taxo_tmp, $temp18);
                                                                        }
                                                                        //end 18th loop
                                                                    }
                                                                    //end 17th loop
                                                                }
                                                                //end 16th loop
                                                            }
                                                            //end 15th loop
                                                        }
                                                        //end 14th loop
                                                    }
                                                    //end 13th loop
                                                }
                                                //end 12th loop
                                            }
                                            //end 11th loop
                                        }
                                        //end 10th loop
                                    }
                                    //end 9th loop
                                }
                                //end 8th loop
                            }
                            //end 7th loop
                        }
                        //end 6th loop
                    }
                    //end 5th loop
                }
                //end 4th loop
            }
            //end 3rd loop
        }
        //end 2nd loop
        $taxo_tmp = array_unique($taxo_tmp);
        $taxo_tmp = array_filter($taxo_tmp);
        //end ====
        return $taxo_tmp;
    }
    
    private function get_children_of_synonym($taxon_id)
    {
        if(in_array($taxon_id, $this->synonyms_without_children)) return array();
        $final = array();
        $options = $this->download_options;
        $options['download_wait_time'] = 500000; //500000 -> half a second; 1 million is 1 second
        $options['delay_in_minutes'] = 0;
        $options['download_attempts'] = 1;

        $offset = 1;
        if($json = Functions::lookup_with_cache($this->webservice['AphiaChildrenByAphiaID'].$taxon_id, $options))
        {
            while(true)
            {
                // echo " $offset";
                if($offset == 1) $url = $this->webservice['AphiaChildrenByAphiaID'].$taxon_id;
                else             $url = $this->webservice['AphiaChildrenByAphiaID'].$taxon_id."?offset=$offset";
                if($json = Functions::lookup_with_cache($url, $options))
                {
                    if($arr = json_decode($json, true))
                    {
                        foreach($arr as $a) $final[] = $a['AphiaID'];
                    }
                    if(count($arr) < 50) break;
                }
                else break;
                $offset = $offset + 50;
            }
        }
        else
        {
            echo "\nsave_2text_synonyms_without_children\n";
            self::save_2text_synonyms_without_children($taxon_id);
        }
        return $final;
    }
    
    private function save_2text_synonyms_without_children($taxon_id)
    {
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "26_files/" . $this->resource_id . "_synonyms_without_children.txt";
        $WRITE = fopen($filename, "a");
        fwrite($WRITE, $taxon_id . "\n");
        fclose($WRITE);
    }
    
    private function get_synonyms_without_children()
    {
        $filename = CONTENT_RESOURCE_LOCAL_PATH . "26_files/" . $this->resource_id . "_synonyms_without_children.txt";
        if(file_exists($filename))
        {
            $txt = file_get_contents($filename);
            $AphiaIDs = explode("\n", $txt);
            $AphiaIDs = array_filter($AphiaIDs);
            $AphiaIDs = array_unique($AphiaIDs);
            return $AphiaIDs;
        }
        return array();
    }
    
    private function get_worms_taxon_id($worms_id)
    {
        return str_ireplace("urn:lsid:marinespecies.org:taxname:", "", (string) $worms_id);
    }
    
    private function build_taxa_rank_array($records)
    {
        foreach($records as $rec)
        {
            $taxon_id = self::get_worms_taxon_id($rec["http://rs.tdwg.org/dwc/terms/taxonID"]);
            $this->taxa_rank[$taxon_id]['r'] = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"];
            $this->taxa_rank[$taxon_id]['s'] = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
        }
    }
    
    private function create_instances_from_taxon_object($records)
    {
        $undeclared_ids = self::get_undeclared_parent_ids(); //uses a historical text file - undeclared parents. If not to use this, then there will be alot of API calls needed.
        $k = 0;
        foreach($records as $rec)
        {
            $rec = array_map('trim', $rec);
            $k++;
            // if(($k % 100) == 0) echo "\n count: $k";
            /* breakdown when caching:
            $cont = false;
            // if($k >=  1   && $k < 200000) $cont = true;
            // if($k >=  200000 && $k < 400000) $cont = true;
            // if($k >=  400000 && $k < 600000) $cont = true;
            if(!$cont) continue;
            */
            
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID = self::get_worms_taxon_id($rec["http://rs.tdwg.org/dwc/terms/taxonID"]);
            
            if($this->what == "taxonomy")
            {
                if(in_array($taxon->taxonID, $this->children_of_synonyms)) continue; //exclude children of synonyms
            }
            
            $taxon->scientificName  = (string) $rec["http://rs.tdwg.org/dwc/terms/scientificName"];
            $taxon->scientificName = self::format_incertae_sedis($taxon->scientificName);
            
            if($taxon->scientificName != "Biota")
            {
                $val = self::get_worms_taxon_id($rec["http://rs.tdwg.org/dwc/terms/parentNameUsageID"]);
                if(in_array($val, $undeclared_ids)) $taxon->parentNameUsageID = self::get_valid_parent_id($taxon->taxonID); //based here: https://eol-jira.bibalex.org/browse/TRAM-520?focusedCommentId=60658&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-60658
                else                                $taxon->parentNameUsageID = $val;
            }
            
            $taxon->taxonRank       = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRank"];
            $this->debug['ranks'][$taxon->taxonRank] = '';
            
            $taxon->taxonomicStatus = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonomicStatus"];
            $taxon->taxonRemarks    = (string) $rec["http://rs.tdwg.org/dwc/terms/taxonRemarks"];
            
            if($this->what == "taxonomy") //based on https://eol-jira.bibalex.org/browse/TRAM-520?focusedCommentId=60923&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-60923
            {
                if($taxon->taxonomicStatus == "") continue; //synonymous to cases where "unassessed" in taxonRemarks
            }
            
            if(is_numeric(stripos($taxon->taxonRemarks, 'REMAP_ON_EOL'))) $taxon->taxonomicStatus = "synonym";

            if($val = (string) $rec["http://rs.tdwg.org/dwc/terms/acceptedNameUsageID"]) $taxon->acceptedNameUsageID  = self::get_worms_taxon_id($val);
            else $taxon->acceptedNameUsageID = '';

            if($taxon->taxonomicStatus == "accepted")
            {
                if((string) $rec["http://rs.tdwg.org/dwc/terms/acceptedNameUsageID"]) $taxon->acceptedNameUsageID = "";
            }
            elseif($taxon->taxonomicStatus == "synonym")
            {
                if(!$taxon->acceptedNameUsageID) continue; //is syn but no acceptedNameUsageID, ignore this taxon
            }
            else //not "synonym" and not "accepted"
            {
                //not syn but has acceptedNameUsageID; seems possible, so just accept it
            }

            if($taxon->taxonID == @$taxon->acceptedNameUsageID) $taxon->acceptedNameUsageID = '';
            if($taxon->taxonID == @$taxon->parentNameUsageID)   $taxon->parentNameUsageID = '';

            if($taxon->taxonomicStatus == "synonym") // this will prevent names to become synonyms of another where the ranks are different
            {
                if($taxon->taxonRank != @$this->taxa_rank[$taxon->acceptedNameUsageID]['r']) continue;
                $taxon->parentNameUsageID = ''; //remove the ParentNameUsageID data from all of the synonym lines
            }
            
            if($this->what == "taxonomy") //based on https://eol-jira.bibalex.org/browse/TRAM-520?focusedCommentId=60923&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-60923
            {
                if(@$taxon->parentNameUsageID)
                {
                    if(!self::if_accepted_taxon($taxon->parentNameUsageID)) continue;
                }
            }
            
            
            // /* stats
            $this->debug["status"][$taxon->taxonomicStatus] = '';
            @$this->debug["count"][$taxon->taxonomicStatus]++;
            @$this->debug["count"]["count"]++;
            // */
            $taxon->namePublishedIn = (string) $rec["http://rs.tdwg.org/dwc/terms/namePublishedIn"];
            $taxon->rightsHolder    = (string) $rec["http://purl.org/dc/terms/rightsHolder"];
            $taxon->source = $this->taxon_page . $taxon->taxonID;
            if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/media/referenceID"])) $taxon->referenceID = $referenceID;

            if(!isset($this->taxon_ids[$taxon->taxonID]))
            {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
                
                Functions::lookup_with_cache($this->gnsparser.self::format_sciname($taxon->scientificName), $this->smasher_download_options);
            }

            /* not used:
            <field index="15" default="http://creativecommons.org/licenses/by/3.0/" term="http://purl.org/dc/terms/accessRights"/>
            <field index="17" default="World Register of Marine Species (WoRMS)" term="http://rs.tdwg.org/dwc/terms/datasetName"/>
            */
        }
    }
    private function format_sciname($str)
    {   //http://parser.globalnames.org/doc/api
        $str = str_replace("&", "%26", $str);
        $str = str_replace(" ", "+", $str);
        return $str;
    }
    private function if_accepted_taxon($taxon_id)
    {
        if($status = @$this->taxa_rank[$taxon_id]['s'])
        {
            if($status == "accepted") return true;
            else return false;
        }
        else //let the API decide
        {
            if($json = Functions::lookup_with_cache($this->webservice['AphiaRecordByAphiaID'].$taxon_id, $this->download_options))
            {
                $arr = json_decode($json, true);
                // print_r($arr);
                if($arr['status'] == "accepted") return true;
            }
            return false;
        }
        return false;
    }
    
    private function get_objects($records)
    {
        foreach($records as $rec)
        {
            $identifier = (string) $rec["http://purl.org/dc/terms/identifier"];
            $type       = (string) $rec["http://purl.org/dc/terms/type"];

            $rec["taxon_id"] = self::get_worms_taxon_id($rec["http://rs.tdwg.org/dwc/terms/taxonID"]);
            $rec["catnum"] = "";
            
            if (strpos($identifier, "WoRMS:distribution:") !== false)
            {
                $rec["catnum"] = (string) $rec["http://purl.org/dc/terms/identifier"];
                /* self::process_distribution($rec); removed as per DATA-1522 */ 
                $rec["catnum"] = str_ireplace("WoRMS:distribution:", "_", $rec["catnum"]);
                self::process_establishmentMeans_occurrenceStatus($rec); //DATA-1522
                continue;
            }
            
            if($type == "http://purl.org/dc/dcmitype/StillImage")
            {
                // WoRMS:image:10299_106331
                $temp = explode("_", $identifier);
                $identifier = $temp[0];
            }

            $mr = new \eol_schema\MediaResource();
            $mr->taxonID        = $rec["taxon_id"];
            $mr->identifier     = $identifier;
            $mr->type           = $type;
            $mr->subtype        = (string) $rec["http://rs.tdwg.org/audubon_core/subtype"];
            $mr->Rating         = (string) $rec["http://ns.adobe.com/xap/1.0/Rating"];
            $mr->audience       = (string) $rec["http://purl.org/dc/terms/audience"];
            
            if($val = trim((string) $rec["http://purl.org/dc/terms/language"])) $mr->language = $val;
            else                                                                $mr->language = "en";
            
            $mr->format         = (string) $rec["http://purl.org/dc/terms/format"];
            $mr->title          = (string) $rec["http://purl.org/dc/terms/title"];
            $mr->CVterm         = (string) $rec["http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/CVterm"];
            
            $mr->creator        = (string) $rec["http://purl.org/dc/terms/creator"];
            $mr->CreateDate     = (string) $rec["http://ns.adobe.com/xap/1.0/CreateDate"];
            $mr->modified       = (string) $rec["http://purl.org/dc/terms/modified"];
            $mr->Owner          = (string) $rec["http://ns.adobe.com/xap/1.0/rights/Owner"];
            $mr->rights         = (string) $rec["http://purl.org/dc/terms/rights"];
            $mr->UsageTerms     = (string) $rec["http://ns.adobe.com/xap/1.0/rights/UsageTerms"];
            $mr->description    = (string) $rec["http://purl.org/dc/terms/description"];
            $mr->bibliographicCitation = (string) $rec["http://purl.org/dc/terms/bibliographicCitation"];

            $mr->derivedFrom     = (string) $rec["http://rs.tdwg.org/ac/terms/derivedFrom"];
            $mr->LocationCreated = (string) $rec["http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated"];
            $mr->spatial         = (string) $rec["http://purl.org/dc/terms/spatial"];
            $mr->lat             = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#lat"];
            $mr->long            = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#long"];
            $mr->alt             = (string) $rec["http://www.w3.org/2003/01/geo/wgs84_pos#alt"];

            $mr->publisher      = (string) $rec["http://purl.org/dc/terms/publisher"];
            $mr->contributor    = (string) $rec["http://purl.org/dc/terms/contributor"];
            $mr->creator        = (string) $rec["http://purl.org/dc/terms/creator"];
            
            if($agentID = (string) $rec["http://eol.org/schema/agent/agentID"])
            {
                $ids = explode(",", $agentID); // not sure yet what separator Worms used, comma or semicolon - or if there are any
                $agent_ids = array();
                foreach($ids as $id) $agent_ids[] = $id;
                $mr->agentID = implode("; ", $agent_ids);
            }

            if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/reference/referenceID"])) $mr->referenceID = $referenceID;
            
            $mr->accessURI      = self::complete_url((string) $rec["http://rs.tdwg.org/ac/terms/accessURI"]);
            $mr->thumbnailURL   = (string) $rec["http://eol.org/schema/media/thumbnailURL"];
            
            if($source = (string) $rec["http://rs.tdwg.org/ac/terms/furtherInformationURL"]) $mr->furtherInformationURL = self::complete_url($source);
            else                                                                             $mr->furtherInformationURL = $this->taxon_page . $mr->taxonID;
            
            if(!isset($this->object_ids[$mr->identifier]))
            {
                $this->object_ids[$mr->identifier] = '';
                $this->archive_builder->write_object_to_file($mr);
            }
        }
    }
    
    private function complete_url($path)
    {
        // http://www.marinespecies.org/aphia.php?p=sourcedetails&id=154106
        $path = trim($path);
        if(substr($path, 0, 10) == "aphia.php?") return "http://www.marinespecies.org/" . $path;
        else return $path;
    }
    
    private function get_branch_ids_to_prune()
    {
        //to do: access google sheets online: https://docs.google.com/spreadsheets/d/11jQ-6CUJIbZiNwZrHqhR_4rqw10mamdA17iaNELWCBQ/edit#gid=0
        return array(12, 598929, 22718, 10, 503066, 234484, 596326, 886300, 147480, 742162, 1836, 178701, 1278, 1300, 719042, 741333, 393257, 598621, 719043, 719950, 164710, 167282, 510103, 719044, 719045, 719046, 397356, 724635, 719047, 719048, 719049, 598607, 719050, 549666, 709139);
    }
    
    private function get_all_ids_to_prune()
    {
        $final = array();
        $ids = self::get_branch_ids_to_prune(); //supposedly comes from a google spreadsheet
        foreach($ids as $id)
        {
            $arr = self::get_children_of_taxon($id);
            if($arr) $final = array_merge($final, $arr);
            $final = array_unique($final);
        }
        $final = array_merge($final, $ids);
        $final = array_unique($final);
        $final = array_filter($final);
        return $final;
    }
    
    private function format_incertae_sedis($str)
    {
        /*
        case 1: [One-word-name] incertae sedis
            Example: Bivalvia incertae sedis
            To: unplaced [One-word-name]
        
        case 2: [One-word-name] incertae sedis [other words]
        Example: Lyssacinosida incertae sedis Tabachnick, 2002
        To: unplaced [One-word-name]

        case 3: [more than 1 word-name] incertae sedis
        :: leave it alone for now
        Examples: Ascorhynchoidea family incertae sedis
        */
        $str = Functions::remove_whitespace($str);
        $str = trim($str);
        if(is_numeric(stripos($str, " incertae sedis")))
        {
            $str = str_ireplace("incertae sedis", "incertae sedis", $str); //this will capture Incertae sedis
            $arr = explode(" incertae sedis", $str);
            if($val = @$arr[0])
            {
                $space_count = substr_count($val, " ");
                if($space_count == 0) return "unplaced " . trim($val);
                else return $str;
            }
        }
        else return $str;
    }

    /*
    private function process_distribution($rec) // structured data
    {
        // not used yet
        // [] => WoRMS:distribution:274241
        // [http://purl.org/dc/terms/type] => http://purl.org/dc/dcmitype/Text
        // [http://rs.tdwg.org/audubon_core/subtype] => 
        // [http://purl.org/dc/terms/format] => text/html
        // [http://purl.org/dc/terms/title] => Distribution
        // [http://eol.org/schema/media/thumbnailURL] => 
        // [http://rs.tdwg.org/ac/terms/furtherInformationURL] => 
        // [http://purl.org/dc/terms/language] => en
        // [http://ns.adobe.com/xap/1.0/Rating] => 
        // [http://purl.org/dc/terms/audience] => 
        // [http://ns.adobe.com/xap/1.0/rights/UsageTerms] => http://creativecommons.org/licenses/by/3.0/
        // [http://purl.org/dc/terms/rights] => This work is licensed under a Creative Commons Attribution-Share Alike 3.0 License
        // [http://eol.org/schema/agent/agentID] => WoRMS:Person:10
        
        // other units:
        $derivedFrom     = "http://rs.tdwg.org/ac/terms/derivedFrom";
        $CreateDate      = "http://ns.adobe.com/xap/1.0/CreateDate"; // 2004-12-21T16:54:05+01:00
        $modified        = "http://purl.org/dc/terms/modified"; // 2004-12-21T16:54:05+01:00
        $LocationCreated = "http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated";
        $spatial         = "http://purl.org/dc/terms/spatial";
        $lat             = "http://www.w3.org/2003/01/geo/wgs84_pos#lat";
        $long            = "http://www.w3.org/2003/01/geo/wgs84_pos#long";
        $alt             = "http://www.w3.org/2003/01/geo/wgs84_pos#alt";
        // for measurementRemarks
        $publisher  = "http://purl.org/dc/terms/publisher";
        $creator    = "http://purl.org/dc/terms/creator"; // db_admin
        $Owner      = "http://ns.adobe.com/xap/1.0/rights/Owner";

        $measurementRemarks = "";
        if($val = $rec["http://purl.org/dc/terms/description"])
        {
                                                        self::add_string_types($rec, "Distribution", $val, "http://rs.tdwg.org/ontology/voc/SPMInfoItems#Distribution");
            if($val = (string) $rec[$derivedFrom])      self::add_string_types($rec, "Derived from", $val, $derivedFrom);
            if($val = (string) $rec[$CreateDate])       self::add_string_types($rec, "Create date", $val, $CreateDate);
            if($val = (string) $rec[$modified])         self::add_string_types($rec, "Modified", $val, $modified);
            if($val = (string) $rec[$LocationCreated])  self::add_string_types($rec, "Location created", $val, $LocationCreated);
            if($val = (string) $rec[$spatial])          self::add_string_types($rec, "Spatial", $val, $spatial);
            if($val = (string) $rec[$lat])              self::add_string_types($rec, "Latitude", $val, $lat);
            if($val = (string) $rec[$long])             self::add_string_types($rec, "Longitude", $val, $long);
            if($val = (string) $rec[$alt])              self::add_string_types($rec, "Altitude", $val, $alt);
            if($val = (string) $rec[$publisher])        self::add_string_types($rec, "Publisher", $val, $publisher);
            if($val = (string) $rec[$creator])          self::add_string_types($rec, "Creator", $val, $creator);
            if($val = (string) $rec[$Owner])            self::add_string_types($rec, "Owner", $val, $Owner);
        }
    }
    */

    private function process_establishmentMeans_occurrenceStatus($rec) // structured data
    {
        $location = $rec["http://purl.org/dc/terms/description"];
        if(!$location) return;
        $establishmentMeans = trim((string) @$rec["http://rs.tdwg.org/dwc/terms/establishmentMeans"]);
        $occurrenceStatus = trim((string) @$rec["http://rs.tdwg.org/dwc/terms/occurrenceStatus"]);

        // /* list down all possible values of the 2 new fields
        $this->debug["establishmentMeans"][$establishmentMeans] = '';
        $this->debug["occurrenceStatus"][$occurrenceStatus] = '';
        // */

        /*
        http://eol.org/schema/terms/Present --- lists locations
        If this condition is met:   occurrenceStatus=present, doubtful, or empty
        If occurrenceStatus=doubtful, add a metadata record in MeasurementOrFact:
        field= http://rs.tdwg.org/dwc/terms/measurementAccuracy, value= http://rs.tdwg.org/ontology/voc/OccurrenceStatusTerm#Questionable
        */
        if(in_array($occurrenceStatus, array("present", "doubtful", "")) || $occurrenceStatus == "")
        {
            $rec["catnum"] .= "_pr";
                                                self::add_string_types($rec, "true", $location, "http://eol.org/schema/terms/Present");
            if($occurrenceStatus == "doubtful") self::add_string_types($rec, "metadata", "http://rs.tdwg.org/ontology/voc/OccurrenceStatusTerm#Questionable", "http://rs.tdwg.org/dwc/terms/measurementAccuracy");
        }
        
        /*
        http://eol.org/schema/terms/Absent --- lists locations
        If this condition is met:   occurrenceStatus=excluded
        */
        if($occurrenceStatus == "excluded")
        {
            $rec["catnum"] .= "_ex";
            self::add_string_types($rec, "true", $location, "http://eol.org/schema/terms/Absent");
        }
        
        /*
        http://eol.org/schema/terms/NativeRange --- lists locations
        If this condition is met:   establishmentMeans=native or native - Endemic
        If establishmentMeans=native - Endemic, add a metadata record in MeasurementOrFact:
        field= http://rs.tdwg.org/dwc/terms/measurementRemarks, value= http://rs.tdwg.org/ontology/voc/OccurrenceStatusTerm#Endemic
        */
        if(in_array($establishmentMeans, array("Native", "Native - Endemic", "Native - Non-endemic")))
        {
            $rec["catnum"] .= "_nr";
            self::add_string_types($rec, "true", $location, "http://eol.org/schema/terms/NativeRange");
            if($establishmentMeans == "Native - Endemic")         self::add_string_types($rec, "metadata", "http://rs.tdwg.org/ontology/voc/OccurrenceStatusTerm#Endemic", "http://rs.tdwg.org/dwc/terms/measurementRemarks");
            // elseif($establishmentMeans == "Native - Non-endemic") //no metadata -> https://jira.eol.org/browse/DATA-1522?focusedCommentId=59715&page=com.atlassian.jira.plugin.system.issuetabpanels:comment-tabpanel#comment-59715
        }
        
        /*
        http://eol.org/schema/terms/IntroducedRange --- lists locations
        If both these conditions are met:
            occurrenceStatus=present, doubtful or empty
            establishmentMeans=Alien
        If occurrenceStatus=doubtful, add a metadata record in MeasurementOrFact:
        field= http://rs.tdwg.org/dwc/terms/measurementAccuracy, value= http://rs.tdwg.org/ontology/voc/OccurrenceStatusTerm#Questionable
        */
        if((in_array($occurrenceStatus, array("present", "doubtful", ""))) && $establishmentMeans == "Alien")
        {
            $rec["catnum"] .= "_ir";
            self::add_string_types($rec, "true", $location, "http://eol.org/schema/terms/IntroducedRange");
            if($occurrenceStatus == "doubtful") self::add_string_types($rec, "metadata", "http://rs.tdwg.org/ontology/voc/OccurrenceStatusTerm#Questionable", "http://rs.tdwg.org/dwc/terms/measurementAccuracy");
        }

    }

    private function add_string_types($rec, $label, $value, $measurementType)
    {
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence_id = $this->add_occurrence($rec["taxon_id"], $rec["catnum"]);
        $m->occurrenceID = $occurrence_id;
        if($label == "Distribution" || $label == "true")
        {   // so that measurementRemarks (and source, contributor, etc.) appears only once in the [measurement_or_fact.tab]
            $m->measurementOfTaxon = 'true';
            $m->measurementRemarks = '';
            $m->source = (string) $rec["http://rs.tdwg.org/ac/terms/accessURI"]; // http://www.marinespecies.org/aphia.php?p=distribution&id=274241
            $m->bibliographicCitation = (string) $rec["http://purl.org/dc/terms/bibliographicCitation"];
            $m->contributor = (string) $rec["http://purl.org/dc/terms/contributor"];
            if($referenceID = self::prepare_reference((string) $rec["http://eol.org/schema/reference/referenceID"])) {
                $m->referenceID = $referenceID;
            }
        }
        $m->measurementType = $measurementType;
        $m->measurementValue = (string) $value;
        $m->measurementMethod = '';
        // $m->measurementID = Functions::generate_measurementID($m, $this->resource_id, 'measurement', array('occurrenceID', 'measurementType', 'measurementValue'));
        $m->measurementID = Functions::generate_measurementID($m, $this->resource_id);
        $this->archive_builder->write_object_to_file($m);
    }

    private function prepare_reference($referenceID)
    {
        if($referenceID)
        {
            $ids = explode(",", $referenceID); // not sure yet what separator Worms used, comma or semicolon - or if there are any
            $reference_ids = array();
            foreach($ids as $id) $reference_ids[] = $id;
            return implode("; ", $reference_ids);
        }
        return false;
    }

    private function add_occurrence($taxon_id, $catnum)
    {
        $occurrence_id = $taxon_id . 'O' . $catnum; // suggested by Katja to use -- ['O' . $catnum]
        // $occurrence_id = md5($taxon_id . 'occurrence'); from environments

        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        
        $o->occurrenceID = Functions::generate_measurementID($o, $this->resource_id, 'occurrence');

        if(isset($this->occurrence_ids[$o->occurrenceID])) return $o->occurrenceID;
        $this->archive_builder->write_object_to_file($o);

        $this->occurrence_ids[$o->occurrenceID] = '';
        return $o->occurrenceID;

        /* old ways
        $this->occurrence_ids[$occurrence_id] = '';
        return $occurrence_id;
        */
    }

    private function get_vernaculars($records)
    {
        self::process_fields($records, "vernacular");
        // foreach($records as $rec)
        // {
        //     $v = new \eol_schema\VernacularName();
        //     $v->taxonID         = $rec["http://rs.tdwg.org/dwc/terms/taxonID"];
        //     $v->taxonID         = str_ireplace("urn:lsid:marinespecies.org:taxname:", "", $v->taxonID);
        //     $v->vernacularName  = $rec["http://rs.tdwg.org/dwc/terms/vernacularName"];
        //     $v->source          = $rec["http://purl.org/dc/terms/source"];
        //     $v->language        = $rec["http://purl.org/dc/terms/language"];
        //     $v->isPreferredName = $rec["http://rs.gbif.org/terms/1.0/isPreferredName"];
        //     $this->archive_builder->write_object_to_file($v);
        // }
    }

    private function get_agents($records)
    {
        self::process_fields($records, "agent");
        // foreach($records as $rec)
        // {
        //     $r = new \eol_schema\Agent();
        //     $r->identifier      = (string) $rec["http://purl.org/dc/terms/identifier"];
        //     $r->term_name       = (string) $rec["http://xmlns.com/foaf/spec/#term_name"];
        //     $r->term_firstName  = (string) $rec["http://xmlns.com/foaf/spec/#term_firstName"];
        //     $r->term_familyName = (string) $rec["http://xmlns.com/foaf/spec/#term_familyName"];
        //     $r->agentRole       = (string) $rec["http://eol.org/schema/agent/agentRole"];
        //     $r->term_mbox       = (string) $rec["http://xmlns.com/foaf/spec/#term_mbox"];
        //     $r->term_homepage   = (string) $rec["http://xmlns.com/foaf/spec/#term_homepage"];
        //     $r->term_logo       = (string) $rec["http://xmlns.com/foaf/spec/#term_logo"];
        //     $r->term_currentProject = (string) $rec["http://xmlns.com/foaf/spec/#term_currentProject"];
        //     $r->organization        = (string) $rec["http://eol.org/schema/agent/organization"];
        //     $r->term_accountName    = (string) $rec["http://xmlns.com/foaf/spec/#term_accountName"];
        //     $r->term_openid         = (string) $rec["http://xmlns.com/foaf/spec/#term_openid"];
        //     $this->archive_builder->write_object_to_file($r);
        // }
    }
    
    private function get_references($records)
    {
        self::process_fields($records, "reference");
        // foreach($records as $rec)
        // {
        //     $r = new \eol_schema\Reference();
        //     $r->identifier      = (string) $rec["http://purl.org/dc/terms/identifier"];
        //     $r->publicationType = (string) $rec["http://eol.org/schema/reference/publicationType"];
        //     $r->full_reference  = (string) $rec["http://eol.org/schema/reference/full_reference"];
        //     $r->primaryTitle    = (string) $rec["http://eol.org/schema/reference/primaryTitle"];
        //     $r->title           = (string) $rec["http://purl.org/dc/terms/title"];
        //     $r->pages           = (string) $rec["http://purl.org/ontology/bibo/pages"];
        //     $r->pageStart       = (string) $rec["http://purl.org/ontology/bibo/pageStart"];
        //     $r->pageEnd         = (string) $rec["http://purl.org/ontology/bibo/pageEnd"];
        //     $r->volume          = (string) $rec["http://purl.org/ontology/bibo/volume"];
        //     $r->edition         = (string) $rec["http://purl.org/ontology/bibo/edition"];
        //     $r->publisher       = (string) $rec["http://purl.org/dc/terms/publisher"];
        //     $r->authorList      = (string) $rec["http://purl.org/ontology/bibo/authorList"];
        //     $r->editorList      = (string) $rec["http://purl.org/ontology/bibo/editorList"];
        //     $r->created         = (string) $rec["http://purl.org/dc/terms/created"];
        //     $r->language        = (string) $rec["http://purl.org/dc/terms/language"];
        //     $r->uri             = (string) $rec["http://purl.org/ontology/bibo/uri"];
        //     $r->doi             = (string) $rec["http://purl.org/ontology/bibo/doi"];
        //     $r->localityName    = (string) $rec["http://schemas.talis.com/2005/address/schema#localityName"];
        //     if(!isset($this->resource_reference_ids[$r->identifier]))
        //     {
        //        $this->resource_reference_ids[$r->identifier] = 1;
        //        $this->archive_builder->write_object_to_file($r);
        //     }
        // }
    }

    // =================================================================================== WORKING OK! BUT MAY HAVE BEEN JUST ONE-TIME IMPORT
    // START dynamic hierarchy ===========================================================
    // ===================================================================================
    // /*
    private function add_taxa_from_undeclared_parent_ids() //text file here is generated by utility check_if_all_parents_have_entries() in 26.php
    {
        $url = CONTENT_RESOURCE_LOCAL_PATH . "26_files/" . $this->resource_id . "_undefined_parent_ids_archive.txt";
        if(file_exists($url)) {
            $i = 0;
            foreach(new FileIterator($url) as $line_number => $id) {
                $i++;
                $taxa = self::AphiaClassificationByAphiaID($id);
                self::create_taxa($taxa);
            }
        }
    }
    private function AphiaClassificationByAphiaID($id)
    {
        $taxa = self::get_ancestry_by_id($id);
        $taxa = self::add_authorship($taxa);
        // $taxa = self::add_parent_id($taxa); //obsolete
        $taxa = self::add_parent_id_v2($taxa);
        return $taxa;
    }
    private function get_ancestry_by_id($id)
    {
        $taxa = array();
        if(!$id) return array();
        if($json = Functions::lookup_with_cache($this->webservice['AphiaClassificationByAphiaID'].$id, $this->download_options)) {
            $arr = json_decode($json, true);
            // print_r($arr);
            if(@$arr['scientificname'] && strlen(@$arr['scientificname']) > 1) $taxa[] = array('AphiaID' => @$arr['AphiaID'], 'rank' => @$arr['rank'], 'scientificname' => @$arr['scientificname']);
            while(true) {
                if(!$arr) break;
                foreach($arr as $i) {
                    if(@$i['scientificname'] && strlen(@$i['scientificname'])>1) {
                        $taxa[] = array('AphiaID' => @$i['AphiaID'], 'rank' => @$i['rank'], 'scientificname' => @$i['scientificname']);
                    }
                    $arr = $i;
                }
            }
        }
        return $taxa;
    }
    private function add_authorship($taxa) //and other metadata
    {
        $i = 0;
        foreach($taxa as $taxon)
        {   
            // [AphiaID] => 7
            // [rank] => Kingdom
            // [scientificname] => Chromista
            // [parent_id] => 1
            if($json = Functions::lookup_with_cache($this->webservice['AphiaRecordByAphiaID'].$taxon['AphiaID'], $this->download_options)) {
                $arr = json_decode($json, true);
                // print_r($arr);
                // [valid_AphiaID] => 1
                // [valid_name] => Biota
                // [valid_authority] => 
                $taxa[$i]['authority'] = $arr['authority'];
                $taxa[$i]['valid_name'] = trim($arr['valid_name'] . " " . $arr['valid_authority']);
                $taxa[$i]['valid_AphiaID'] = $arr['valid_AphiaID'];
                $taxa[$i]['status'] = $arr['status'];
                $taxa[$i]['citation'] = $arr['citation'];
            }
            $i++;
        }
        return $taxa;
    }
    private function create_taxa($taxa) //for dynamic hierarchy only
    {
        foreach($taxa as $t)
        {   // [AphiaID] => 24
            // [rank] => Class
            // [scientificname] => Zoomastigophora
            // [authority] => 
            // [valid_name] => 
            // [valid_AphiaID] => 
            // [status] => unaccepted
            // [parent_id] => 13
            if($t['status'] != "accepted") continue; //only add those that are 'accepted'
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $t['AphiaID'];
            
            if(in_array($taxon->taxonID, $this->children_of_synonyms)) continue; //exclude children of synonyms
            
            $taxon->scientificName  = trim($t['scientificname'] . " " . $t['authority']);
            $taxon->scientificName = self::format_incertae_sedis($taxon->scientificName);
            
            $taxon->taxonRank       = $t['rank'];
            $taxon->taxonomicStatus = $t['status'];
            $taxon->source          = $this->taxon_page . $t['AphiaID'];
            if($t['scientificname'] != "Biota") $taxon->parentNameUsageID = $t['parent_id'];
            $taxon->acceptedNameUsageID     = $t['valid_AphiaID'];
            $taxon->bibliographicCitation   = $t['citation'];
            
            if($taxon->taxonID == @$taxon->acceptedNameUsageID) $taxon->acceptedNameUsageID = '';
            if($taxon->taxonID == @$taxon->parentNameUsageID)   $taxon->parentNameUsageID = '';
            
            if(!isset($this->taxon_ids[$taxon->taxonID])) {
                $this->taxon_ids[$taxon->taxonID] = '';
                $this->archive_builder->write_object_to_file($taxon);
            }
        }
    }
    // private function add_parent_id($taxa) //works OK, but chooses parent whatever is in the line, even if it is 'unaccepted'.
    // {
    //     $i = 0;
    //     foreach($taxa as $taxon) {
    //         if($i != 0) {
    //             for ($x = 1; $x <= count($taxa); $x++) {
    //                 if($val = @$taxa[$i-$x]['AphiaID']) {
    //                     $taxa[$i]['parent_id'] = $val;
    //                     break;
    //                 }
    //             }
    //         }
    //         $i++;
    //     }
    //     return $taxa;
    // }
    private function add_parent_id_v2($taxa)
    {   
        // Array (
        //     [AphiaID] => 25
        //     [rank] => Order
        //     [scientificname] => Choanoflagellida
        //     [authority] => Kent, 1880
        //     [valid_name] => Choanoflagellida Kent, 1880
        //     [valid_AphiaID] => 25
        //     [status] => accepted
        //     [citation] => WoRMS (2013). Choanoflagellida. In: Guiry, M.D. & Guiry, G.M. (2016). AlgaeBase. World-wide electronic publication,...
        // )
        $i = 0;
        foreach($taxa as $taxon) {
            if($taxon['scientificname'] != "Biota") {
                $parent_id = self::get_parent_of_index($i, $taxa);
                $taxa[$i]['parent_id'] = $parent_id;
            }
            $i++;
        }
        return $taxa;
    }
    private function get_parent_of_index($index, $taxa)
    {
        $parent_id = "";
        for($k = 0; $k <= $index-1 ; $k++) {
            if($taxa[$k]['status'] == "accepted") {
                if(!in_array($taxa[$k]['AphiaID'], $this->children_of_synonyms)) $parent_id = $taxa[$k]['AphiaID']; //new
            }
        }
        return $parent_id;
    }
    private function trim_text_files()
    {
        $files = array("_synonyms_without_children.txt", "_children_of_synonyms.txt");
        foreach($files as $file) {
            $filename = CONTENT_RESOURCE_LOCAL_PATH . "26_files/" . $this->resource_id . $file;
            if(file_exists($filename)) {
                $txt = file_get_contents($filename);
                $AphiaIDs = explode("\n", $txt);
                $AphiaIDs = array_filter($AphiaIDs);
                $AphiaIDs = array_unique($AphiaIDs);
                
                
            }
        }
    }
    
    
    // */
    // ===================================================================================
    // END dynamic hierarchy ===========================================================
    // ===================================================================================

    private function get_undeclared_parent_ids()
    {
        $ids = array();
        $url = CONTENT_RESOURCE_LOCAL_PATH . "26_files/" . $this->resource_id . "_undefined_parent_ids_archive.txt";
        if(file_exists($url)) {
            foreach(new FileIterator($url) as $line_number => $id) $ids[$id] = '';
        }
        return array_keys($ids);
    }


}
?>