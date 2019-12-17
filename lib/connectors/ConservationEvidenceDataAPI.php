<?php
namespace php_active_record;
/* connector: [conservation_evidence.php] */
class ConservationEvidenceDataAPI
{
    function __construct($folder = NULL, $dwca_file = NULL)
    {
        if($folder) {
            $this->resource_id = $folder;
            $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
            $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        }
        $this->dwca_file = $dwca_file;
        $this->debug = array();
        $this->for_mapping = array();
        $this->download_options = array(
            'resource_id'        => 'Conservation_Evidence',
            'expire_seconds'     => 60*60*24*30*3, //expires quarterly
            'download_wait_time' => 3000000, 'timeout' => 60*5, 'download_attempts' => 1, 'delay_in_minutes' => 0.5, 'cache' => 1);
        // $this->download_options['expire_seconds'] = 0; //debug only
        $this->source_csv_species_list = 'https://github.com/eliagbayani/EOL-connector-data-files/raw/master/ConservationEvidence/uniquetaxa_2019_03_06.csv';
        // $this->source_csv_path = DOC_ROOT."../other_files/natdb_harvest/";
        // $this->spreadsheet_for_mapping = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/MAD_tool_NatDB/MADmap.xlsx"; //from Jen (DATA-1754)
            
        $this->api['species'] = 'http://staging.conservationevidence.com/binomial/redlistsearch?name=BINOMIAL&action=1&total=50';
        $this->source_url = 'https://www.conservationevidence.com/data/index?terms=BINOMIAL';
    }
    private function initialize_mapping()
    {
        /* seems not used at all...
        $mappings = Functions::get_eol_defined_uris(false, true);     //1st param: false means will use 1day cache | 2nd param: opposite direction is true
        echo "\n".count($mappings). " - default URIs from EOL registry.";
        $this->uris = Functions::additional_mappings($mappings); //add more mappings used in the past
        // print_r($this->uris);
        */
        // self::initialize_citations_file();
        // self::initialize_spreadsheet_mapping();
    }
    function start()
    {
        require_library('connectors/TraitGeneric');
        $this->func = new TraitGeneric($this->resource_id, $this->archive_builder);
        $this->func->initialize_terms_remapping(); //for DATA-1841 terms remapping

        $tmp_file = Functions::save_remote_file_to_local($this->source_csv_species_list, $this->download_options);
        self::loop_csv_species_list($tmp_file);
        unlink($tmp_file);
        
        $this->archive_builder->finalize(true);
        
        // print_r($this->debug);
        // Functions::start_print_debug($this->debug, $this->resource_id);
    }
    private function loop_csv_species_list($local_csv)
    {
        $i = 0;
        $file = Functions::file_open($local_csv, "r");
        while(!feof($file)) {
            $row = fgetcsv($file);
            if(!$row) break;
            $row = self::clean_html($row); // print_r($row);
            $i++; if(($i % 200) == 0) echo "\n $i ";
            if($i == 1) {
                $fields = $row;
                $fields = self::fill_up_blank_fieldnames($fields);
                $count = count($fields);
                // print_r($fields);
            }
            else { //main records
                $values = $row;
                if($count != count($values)) { //row validation - correct no. of columns
                    // print_r($values); print_r($rec);
                    echo("\nWrong CSV format for this row.\n"); exit;
                    // $this->debug['wrong csv'][$class]['identifier'][$rec['identifier']] = '';
                    continue;
                }
                $k = 0;
                $rec = array();
                foreach($fields as $field) {
                    $rec[$field] = $values[$k];
                    $k++;
                }
                $rec = array_map('trim', $rec); //important step
                // print_r($rec); exit;
                self::process_record($rec);
            } //main records
            // if($i > 2) break; //debug only
        } //main loop
        fclose($file);
    }
    private function process_record($rec)
    {   /*Array(
            [species] => sylvaticum
            [genus] => Geranium
            [binom] => Geranium sylvaticum
            [family] => Geraniaceae
            [order] => Geraniales
            [class] => Magnoliopsida
        )*/
        $taxon_id = self::create_taxon($rec);
        $url = str_replace('BINOMIAL', $rec['binom'], $this->api['species']);
        if($ret = self::access_api($url)) {
            // print_r($ret['results']); exit;
            if($val = @$ret['results']) self::create_measurements($val, $taxon_id);
        }
    }
    private function access_api($url)
    {
        if($json = Functions::lookup_with_cache($url, $this->download_options)) {
            return json_decode($json, true);
        }
    }
    private function create_taxon($rec)
    {
        $taxon_id = str_replace(" ", "_", strtolower($rec['binom']));
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $taxon_id;
        $taxon->scientificName  = $rec['binom'];
        if($rec['binom']) $taxon->genus = $rec['genus'];
        $taxon->class = self::blank_if_NA($rec['class']);
        $taxon->order = self::blank_if_NA($rec['order']);
        $taxon->family = self::blank_if_NA($rec['family']);
        $taxon->furtherInformationURL = str_replace('BINOMIAL', urlencode($rec['binom']), $this->source_url);
        if(!isset($this->taxon_ids[$taxon->taxonID])) {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
        return $taxon_id;
    }
    private function blank_if_NA($str)
    {
        if($str == "NA") return "";
        else return $str;
    }
    private function create_measurements($recs, $taxon_id)
    {   /*Array(
            [0] => Array(
                    [id] => 69
                    [title] => Reduce management intensity on permanent grasslands (several interventions at once)
                    [url] => http://staging.conservationevidence.com/actions/69
                    [type] => Action
                )
            [1] => Array(
                    [id] => 131
                    [title] => Delay mowing or first grazing date on pasture or grassland
                    [url] => http://staging.conservationevidence.com/actions/131
                    [type] => Action
                )
        measurementType=> "conservation_action"
        measurementValue=> url, from the API results, eg: "http://staging.conservationevidence.com/actions/486"
        measurementRemarks=> title, from the API results, eg: "Provide artificial nesting sites for waders"
        */
        foreach($recs as $rec) {
            $mValue = $rec['url'];
            $mType = 'conservation_action';
            $rek = array();
            $rek["taxon_id"] = $taxon_id;
            $rek["catnum"] = $taxon_id."_".$rec['id'];
            $mOfTaxon = "true";
            $rek['measurementRemarks'] = $rec['title'];
            $rek['bibliographicCitation'] = self::get_biblio_from_site($rec['url']);
            // $rek['source'] = $rec['url']; //Eli's own initiative...
            $ret = $this->func->pre_add_string_types($rek, $mValue, $mType, $mOfTaxon);
        }
    }
    private function get_biblio_from_site($url)
    {
        $options = $this->download_options;
        $options['expire_seconds'] = false; //should not expire
        if($html = Functions::lookup_with_cache($url, $options)) {
            if(preg_match("/Please cite as\:(.*?)<\/p>/ims", $html, $arr)) {
                return Functions::remove_whitespace(trim(strip_tags($arr[1])));
            }
        }
    }
    //====================================================================Conservation Evidence ends here. Copied templates below.
    private function clean_html($arr)
    {
        $delimeter = "elicha173";
        $html = implode($delimeter, $arr);
        $html = Functions::conv_to_utf8($html);
        $html = str_ireplace(array("\n", "\r", "\t", "\o", "\xOB", "\11", "\011"), "", trim($html));
        $html = str_ireplace("> |", ">", $html);
        $arr = explode($delimeter, $html);
        return $arr;
    }
    private function initialize_spreadsheet_mapping()
    {
        $final = array();
        $options = $this->download_options;
        $options['file_extension'] = 'xlsx';
        $local_xls = Functions::save_remote_file_to_local($this->spreadsheet_for_mapping, $options);
        require_library('XLSParser');
        $parser = new XLSParser();
        debug("\n reading: " . $local_xls . "\n");
        $map = $parser->convert_sheet_to_array($local_xls);
        $fields = array_keys($map);
        // print_r($map);
        print_r($fields); //exit;
        // foreach($fields as $field) echo "\n$field: ".count($map[$field]); //debug only
        /* get valid_set - the magic 4 fields */
        $i = -1;
        foreach($map['variable'] as $var) {
            $i++;
            if(in_array($var, array("Location.Code"))) continue;
            $tmp = $var."_".$map['value'][$i]."_".$map['dataset'][$i]."_".$map['unit'][$i]."_";
            $tmp = strtolower($tmp);
            $valid_set[$tmp] = self::get_corresponding_rek_from_mapping_spreadsheet($i, $fields, $map);
            //get numeric fields (e.g. Maximum_length). To be used when figuring out which are valid sets, where numeric values should be blank.
            if(!$map['value'][$i]) $this->numeric_fields[$var] = '';
        }
        // print_r($valid_set); exit;
        $this->valid_set = $valid_set;
        unlink($local_xls);
    }
    private function generate_reference($dataset)
    {
        if($ref = @$this->refs[$dataset]) {
            /* [.aubret.2015] => Array(
                    *[URL to paper] => http://www.nature.com/hdy/journal/v115/n4/full/hdy201465a.html
                    *[DOI] => 10.1038/hdy.2014.65
                    [Journal] => Heredity
                    *[Publisher] => Springer Nature
                    *[Title] => Island colonisation and the evolutionary rates of body size in insular neonate snakes
                    *[Author] => Aubret
                    [Year] => 2015
                    *[author_year] => .aubret.2015
                    [BibTeX citation] => @article{aubret2015,title={Island colonisation and the evolutionary rates of body size in insular neonate snakes},author={Aubret, F},journal={Heredity},volume={115},number={4},pages={349--356},year={2015},publisher={Nature Publishing Group}}
                    [Taxonomy ] => Animalia/Serpentes
                    [Person] => Katie
                    [WhoWroteFunction] => 
                    [Everything Completed?] => 
                    [] => 
                    *[full_ref] => Aubret. (2015). Island colonisation and the evolutionary rates of body size in insular neonate snakes. Heredity. Springer Nature.
                )
            */
            if($ref_id = @$ref['author_year']) {
                $r = new \eol_schema\Reference();
                $r->identifier = $ref_id;
                $r->full_reference = $ref['full_ref'];
                $r->uri = $ref['URL.to.paper'];
                $r->doi = $ref['DOI'];
                $r->publisher = $ref['Publisher'];
                $r->title = $ref['Title'];
                $r->authorList = $ref['Author'];
                if(!isset($this->reference_ids[$ref_id])) {
                    $this->reference_ids[$ref_id] = '';
                    $this->archive_builder->write_object_to_file($r);
                }
                return $ref_id;
            }
        }
        else $this->debug['no citations yet'][$dataset] = '';
    }
    private function fill_up_blank_fieldnames($fields)
    {
        $i = 0;
        foreach($fields as $field) {
            if($field) $final[$field] = '';
            else {
                $i++;
                $final['blank_'.$i] = '';
            } 
        }
        return array_keys($final);
    }
}
?>
