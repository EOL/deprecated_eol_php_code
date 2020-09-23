<?php
namespace php_active_record;
/* connector: [dwh_Collembola_TRAM_990.php] - TRAM-990
*/
class DWH_Collembola_API
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->debug = array();
        
        /* these paths are manually created, since dumps are using explicit dates */
        if(Functions::is_production()) $this->extension_path = DOC_ROOT."../other_files/DWH/dumps/2020-08-01-archive-complete/";
        else                           $this->extension_path = DOC_ROOT."../cp/COL/2020-08-01-archive-complete/";
        
        $this->dwca['iterator_options'] = array('row_terminator' => "\n");
        $this->run = '';
        /* taxonomicStatus values as of dump:   Array(
            [accepted name] => 
            [synonym] => 
            [ambiguous synonym] => 
            [provisionally accepted name] => 
            [misapplied name] => 
            [] => 
        )*/
        $this->unclassified_id_increments = 0;
    }
    // ----------------------------------------------------------------- start TRAM-803 -----------------------------------------------------------------
    /*Array(
        [taxonID] => 3952391
        [identifier] => 18bd465a6c133112cd80f73f23776dee
        [datasetID] => Species 2000
        [datasetName] => Catalogue of Life in Species 2000 & ITIS Catalogue of Life: 2020-08-01 Beta
        [acceptedNameUsageID] => 
        [parentNameUsageID] => 3946738
        [taxonomicStatus] => 
        [taxonRank] => class
        [verbatimTaxonRank] => 
        [scientificName] => Collembola
        [kingdom] => Animalia
        [phylum] => Arthropoda
        [class] => Collembola
        [order] => 
        [superfamily] => 
        [family] => 
        [genericName] => 
        [genus] => 
        [subgenus] => 
        [specificEpithet] => 
        [infraspecificEpithet] => 
        [scientificNameAuthorship] => 
        [source] => 
        [namePublishedIn] => 
        [nameAccordingTo] => 
        [modified] => 
        [description] => 
        [taxonConceptID] => 
        [scientificNameID] => 
        [references] => 
        [isExtinct] => false
    )*/
    
    private function get_children_of_taxa_group($taxon_ids)
    {
        require_library('connectors/PaleoDBAPI_v2');
        $func = new PaleoDBAPI_v2("");
        if(Functions::is_production()) $dwca_file = DOC_ROOT."../other_files/DWH/dumps/2020-08-01-archive-complete.zip";
        else                           $dwca_file = DOC_ROOT."../cp/COL/2020-08-01-archive-complete.zip";
        $ids = $func->get_descendants_given_parent_ids($dwca_file, $taxon_ids, $this->resource_id);
        unset($func);
        foreach($ids as $id) $final[$id] = '';
        // print_r($final); exit("\nDescendants of Collembola\n");
        return $final;
    }
    function start_tram_990()
    {
        $this->children_of_Collembola = self::get_children_of_taxa_group(array(3952391));
        $this->children_of_Collembola[3952391] = '';
        
        self::main(); //exit("\nstop muna\n");
        $this->archive_builder->finalize(TRUE);
        if($this->debug) {
            Functions::start_print_debug($this->debug, $this->resource_id);
        }
        echo "\n----------\nRaw source checklist: [$this->extension_path]\n----------\n";
    }
    private function main()
    {
        $taxID_info = self::get_taxID_nodes_info(); //un-comment in real operation
        $meta = self::get_meta_info();
        $i = 0; $filtered_ids = array();
        echo "\nStart main process...Collembola DH...\n";
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++;
            if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            if(in_array($rec['taxonomicStatus'], array("synonym", "ambiguous synonym", "misapplied name"))) continue;
            if($rec['taxonID'] == '3952391') echo "\n[111]\n";
            
            //start filter
            if(isset($this->children_of_Collembola[$rec['taxonID']])) {
                if($rec['taxonID'] == '3952391') echo "\n[222]\n";
            }
            else {
                $filtered_ids[$rec['taxonID']] = '';
                $removed_branches[$rec['taxonID']] = '';
                continue;
            }
            //end filter
            
            if($rec['taxonID'] == '3952391') echo "\n[333]\n";
        } //end loop

        echo "\nStart main process 2...Collembola DH...\n"; $i = 0;
        foreach(new FileIterator($this->extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            if(in_array($rec['taxonomicStatus'], array("synonym", "ambiguous synonym", "misapplied name"))) continue;
            if($rec['taxonID'] == '3952391') echo "\n[444]\n";
            /*Array()*/

            if($rec['taxonID'] == '3952391') echo "\n['aaa']\n";
            if(isset($filtered_ids[$rec['taxonID']])) continue;
            if($rec['taxonID'] == '3952391') echo "\n['bbb']\n";
            if(isset($filtered_ids[$rec['acceptedNameUsageID']])) continue;
            if($rec['taxonID'] == '3952391') echo "\n['ccc']\n";
            if($rec['taxonID'] != '3952391') {
                if(isset($filtered_ids[$rec['parentNameUsageID']])) continue;
            }
            if($rec['taxonID'] == '3952391') echo "\n['ddd']\n";

            if(isset($removed_branches[$rec['taxonID']])) continue;
            if($rec['taxonID'] == '3952391') echo "\n['eee']\n";
            if(isset($removed_branches[$rec['acceptedNameUsageID']])) continue;
            if($rec['taxonID'] == '3952391') echo "\n['fff']\n";
            if($rec['taxonID'] != '3952391') {
                if(isset($removed_branches[$rec['parentNameUsageID']])) continue;
            }
            if($rec['taxonID'] == '3952391') echo "\n['ggg']\n";
            
            // print_r($rec); exit("\nexit muna 2\n");
            /**/
            
            if($rec['taxonID'] == '3952391') echo "\n[555]\n";
            /* Remove branches */
            if(isset($this->children_of_Collembola[$rec['taxonID']])) {
                if($rec['taxonID'] == '3952391') echo "\n[777]\n";
            }
            else continue;

            if($rec['taxonID'] == '3952391') $rec['parentNameUsageID'] = '';
            if($rec['taxonID'] == '3952391') echo "\n[888]\n";
            $rec = self::replace_taxonID_with_identifier($rec, $taxID_info); //new - replace [taxonID] with [identifier]
            
            self::write_taxon_DH($rec);
        } //end loop
    }
    private function replace_taxonID_with_identifier($rec, $taxID_info)
    {
        if(in_array($rec['taxonomicStatus'], array("accepted name","provisionally accepted name"))) {
            if($val = $taxID_info[$rec['taxonID']]['i'])            $rec['taxonID'] = $val;
            else {
                print_r($rec); exit("\nInvestigate: no [identifier] for [taxonID]\n");
            }
            if($val = $taxID_info[$rec['parentNameUsageID']]['i'])  $rec['parentNameUsageID'] = $val;
            else {
                // print_r($rec); //exit("\nInvestigate: no [identifier] for [parentNameUsageID]\n");
                $this->debug['no [identifier] for [parentNameUsageID]'][$rec['parentNameUsageID']] = '';
            }
            if($accepted_id = @$rec['acceptedNameUsageID']) {
                if($val = $taxID_info[$accepted_id]['i'])  $rec['acceptedNameUsageID'] = $val;
                else {
                    print_r($rec); exit("\nInvestigate: no [identifier] for [acceptedNameUsageID]\n");
                }
            }
        }
        else {
            if($val = $taxID_info[$rec['taxonID']]['i'])    $rec['taxonID'] = $val;
            if($parent_id = @$rec['parentNameUsageID']) {
                if($val = $taxID_info[$parent_id]['i'])     $rec['parentNameUsageID'] = $val;
            }
            if($accepted_id = @$rec['acceptedNameUsageID']) {
                if($val = $taxID_info[$accepted_id]['i'])   $rec['acceptedNameUsageID'] = $val;
            }
        }
        return $rec;
    }
    private function replace_NotAssigned_name($rec)
    {   /*42981143 -- Not assigned -- order
        We would want to change the scientificName value to “Order not assigned” */
        $sciname = $rec['scientificName'];
        if($rank = $rec['taxonRank']) $sciname = ucfirst(strtolower($rank))." not assigned";
        return $sciname;
    }
    private function get_taxID_nodes_info($meta = false, $extension_path = false)
    {
        if(!$meta) $meta = self::get_meta_info();
        if(!$extension_path) $extension_path = $this->extension_path;
        print_r($meta); //exit;
        echo "\nGenerating taxID_info...";
        $final = array(); $i = 0;
        foreach(new FileIterator($extension_path.$meta['taxon_file'], false, true, @$this->dwc['iterator_options']) as $line => $row) { //2nd and 3rd param; false and true respectively are default values
            $i++; if(($i % 500000) == 0) echo "\n count:[".number_format($i)."] ";
            if($meta['ignoreHeaderLines'] && $i == 1) continue;
            if(!$row) continue;
            $tmp = explode("\t", $row);
            $rec = array(); $k = 0;
            foreach($meta['fields'] as $field) {
                $rec[$field] = $tmp[$k];
                $k++;
            }
            $rec = array_map('trim', $rec);
            // print_r($rec); exit("\nelix\n");
            if($rec['taxonID'] == '3952391') print_r($rec);
            /*Array(
                [taxonID] => 1001
                [identifier] => 40c1c4c8925fb02ce99db87c0221a6f6
                [datasetID] => 18
                [datasetName] => LepIndex in Species 2000 & ITIS Catalogue of Life: 2020-08-01 Beta
                [acceptedNameUsageID] => 
                [parentNameUsageID] => 3997251
                [taxonomicStatus] => accepted name
                [taxonRank] => species
                [verbatimTaxonRank] => 
                [scientificName] => Bucculatrix carolinae Braun, 1963
                [kingdom] => Animalia
                [phylum] => Arthropoda
                [class] => Insecta
                [order] => Lepidoptera
                [superfamily] => Gracillarioidea
                [family] => Bucculatricidae
                [genericName] => Bucculatrix
                [genus] => Bucculatrix
                [subgenus] => 
                [specificEpithet] => carolinae
                [infraspecificEpithet] => 
                [scientificNameAuthorship] => Braun, 1963
                [source] => 
                [namePublishedIn] => 
                [nameAccordingTo] => Wing, P.
                [modified] => 2011-05-06
                [description] => 
                [taxonConceptID] => 
                [scientificNameID] => b3b2ff1c-5a86-4ab0-aada-9f209ed8d075
                [references] => http://www.catalogueoflife.org/col/details/species/id/40c1c4c8925fb02ce99db87c0221a6f6
                [isExtinct] => false
            )*/
            if(isset($rec['identifier'])) $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'r' => $rec['taxonRank'], 'i' => $rec['identifier']);
            else                          $final[$rec['taxonID']] = array("pID" => $rec['parentNameUsageID'], 'n' => $rec['scientificName'], 'r' => $rec['taxonRank'], 's' => $rec['taxonomicStatus']);
            
            $temp[$rec['taxonomicStatus']] = ''; //debug
        }
        print_r($temp); //exit; //debug
        return $final;
    }
    private function get_ancestry_of_taxID($tax_id, $taxID_info)
    {   /* Array(
            [1] => Array(
                    [pID] => 1
                    [r] => no rank
                    [dID] => 8
                )
        )*/
        $final = array();
        $final[] = $tax_id;
        while($parent_id = @$taxID_info[$tax_id]['pID']) {
            if(!in_array($parent_id, $final)) $final[] = $parent_id;
            else {
                if($parent_id == 1) return $final;
                else {
                    print_r($final);
                    exit("\nInvestigate $parent_id already in array.\n");
                }
            }
            $tax_id = $parent_id;
        }
        return $final;
    }
    private function write_taxon_DH($rec)
    {   //from NCBI ticket: a general rule
        /* One more thing: synonyms and other alternative names should not have parentNameUsageIDs. 
        In general, if a taxon has an acceptedNameUsageID it should not also have a parentNameUsageID. 
        So in this specific case, we want acceptedNameUsageID's only if name class IS scientific name. */
        if($rec['acceptedNameUsageID']) $rec['parentNameUsageID'] = '';
        
        if($rec['scientificName'] == "Not assigned") $rec['scientificName'] = self::replace_NotAssigned_name($rec);
        
        /* From Katja: If that's not easy to do, we can also change the resource files to use "unclassified" instead of "unplaced" for container taxa. 
        I can do this for the resources under my control (trunk & ONY). You would have to do it for COL, CLP, ictv & WOR. */
        /* this was later abondoned by one below this:
        $rec['scientificName'] = str_ireplace("Unplaced", "unclassified", $rec['scientificName']);
        */
        
        /* From Katja: The original version of this is:
        13663148 b41f2b15ccd7f64e1f5c329eae60e987 5 CCW in Species 2000 & ITIS Catalogue of Life: 20th February 2019 54217965 accepted name species Erioptera (Unplaced) amamiensis Alexander, 1956
        Instead of changing (Unplaced) to (unclassified) here, we should simply remove the pseudo subgenus string and use the simple binomial, 
        i.e., in this case, the scientificName should be "Erioptera amamiensis Alexander, 1956." 
        To fix this you should be able to do a simple search and replace for (Unplaced) in the scientificName field.
        */
        $rec['scientificName'] = Functions::remove_whitespace(str_ireplace("(Unplaced)", "", $rec['scientificName']));
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec['taxonID'];
        $taxon->parentNameUsageID       = $rec['parentNameUsageID'];
        $taxon->taxonRank               = $rec['taxonRank'];
        $taxon->scientificName          = $rec['scientificName'];
        $taxon->taxonomicStatus         = $rec['taxonomicStatus'];
        $taxon->acceptedNameUsageID     = $rec['acceptedNameUsageID'];
        // $taxon->furtherInformationURL   = $rec['furtherInformationURL']; //removed from 'Feb 20, 2019' dump
        $this->debug['acceptedNameUsageID'][$rec['acceptedNameUsageID']] = '';
        
        /* optional, I guess
        $taxon->scientificNameID    = $rec['scientificNameID'];
        $taxon->nameAccordingTo     = $rec['nameAccordingTo'];
        $taxon->kingdom             = $rec['kingdom'];
        $taxon->phylum              = $rec['phylum'];
        $taxon->class               = $rec['class'];
        $taxon->order               = $rec['order'];
        $taxon->family              = $rec['family'];
        $taxon->genus               = $rec['genus'];
        $taxon->subgenus            = $rec['subgenus'];
        $taxon->specificEpithet     = $rec['specificEpithet'];
        $taxon->infraspecificEpithet        = $rec['infraspecificEpithet'];
        $taxon->scientificNameAuthorship    = $rec['scientificNameAuthorship'];
        $taxon->taxonRemarks        = $rec['taxonRemarks'];
        $taxon->modified            = $rec['modified'];
        $taxon->datasetID           = $rec['datasetID'];
        $taxon->datasetName         = $rec['datasetName'];
        */
        /* for DUPLICATE TAXA process...
        Find duplicate taxa where taxonRank:species      AND the following fields all have the same value: parentNameUsageID, genus, specificEpithet.
        Find duplicate taxa where taxonRank:infraspecies AND the following fields all have the same value: parentNameUsageID, genus, specificEpithet, infraspecificEpithet.
        */
        if($val = @$rec['genus'])                       $taxon->genus = $val;
        if($val = @$rec['specificEpithet'])             $taxon->specificEpithet = $val;
        if($val = @$rec['infraspecificEpithet'])        $taxon->infraspecificEpithet = $val;
        if($val = @$rec['scientificNameAuthorship'])    $taxon->scientificNameAuthorship = $val;
        if($val = @$rec['verbatimTaxonRank'])           $taxon->verbatimTaxonRank = $val;
        if($val = @$rec['subgenus'])                    $taxon->subgenus = $val;
        if($val = @$rec['taxonRemarks'])                $taxon->taxonRemarks = $val;            //for taxonRemarks but for a later stage
        if($val = @$rec['isExtinct'])                   $taxon->taxonRemarks = "isExtinct:$val";//for taxonRemarks but for an earlier stage

        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }
    private function get_meta_info($row_type = false, $extension_path = false)
    {
        if(!$extension_path) $extension_path = $this->extension_path; //default extension_path to use
        require_library('connectors/DHSourceHierarchiesAPI'); $func = new DHSourceHierarchiesAPI();
        $meta = $func->analyze_eol_meta_xml($extension_path."meta.xml", $row_type); //2nd param $row_type is rowType in meta.xml
        // if($GLOBALS['ENV_DEBUG']) print_r($meta); //good debug
        return $meta;
    }
}
?>