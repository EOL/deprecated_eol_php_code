<?php
namespace php_active_record;
/*  connector: botany_nmnh.php */
class NMNHBotanyAPI
{
    function __construct($folder)
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        // $this->taxon_ids = array();
        // $this->occurrence_ids = array();
        // $this->media_ids = array();
        // $this->agent_ids = array();
        $this->debug = array();
        $this->download_options = array("timeout" => 60*60, "expire_seconds" => 60*60*24*25);
    }
    
    function start($params, $xml_file_YN = false, $expire_seconds = 60*60*24*25) //expires in 25 days
    {
        if(!$params["xmlYN"]) {
            require_library('connectors/INBioAPI');
            $func = new INBioAPI();
            $paths = $func->extract_archive_file($params["eol_xml_file"], $params["filename"], $this->download_options);
            print_r($paths);
            self::convert_xml($paths["temp_dir"].$params["filename"]);
            $this->archive_builder->finalize(TRUE);
            recursive_rmdir($paths["temp_dir"]); // remove temp dir
        }
        else //is XML file
        {
            $params['path'] = DOC_ROOT . "tmp/";
            $local_xml_file = Functions::save_remote_file_to_local($params['eol_xml_file'], array('file_extension' => "xml", "cache" => 1, "expire_seconds" => $expire_seconds, "timeout" => 7200, "download_attempts" => 2, "delay_in_minutes" => 2)); 
            /* expire_seconds is irrelevant if there is no cache => 1 in save_remote_file_to_local() */ 
            $params['filename'] = pathinfo($local_xml_file, PATHINFO_BASENAME);
            self::convert_xml($local_xml_file);
            $this->archive_builder->finalize(TRUE);
            unlink($local_xml_file);
        }
        echo "\ntotal rows: $this->count\n";
    }
    
    function convert_xml($local_path)
    {
        $reader = new \XMLReader();
        $reader->open($local_path);
        $i = 0;
        while(@$reader->read()) {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon") {
                $page_xml = $reader->readOuterXML();
                $t = simplexml_load_string($page_xml, null, LIBXML_NOCDATA);
                
                $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
                $t_dcterms = $t->children("http://purl.org/dc/terms/");
                $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
                if($val = $t_dc) print_r($val);
                if($val = $t_dcterms) print_r($val);
                if($val = $t_dwc) print_r($val);

                // print_r($t);

                foreach($t->commonName as $c) {
                    echo "\n $c - ". $c->attributes('xml', TRUE)->lang; //works OK
                }
                $xml_string = $t->asXML();
                echo "\n[$xml_string]\n";
                exit("\nJust making tests...OK\n");
            }
        }
    }
    
    private function xml_attribute($object, $attribute)
    {
        if(isset($object[$attribute])) return (string) $object[$attribute];
    }
    /*
    private function add_taxon($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec['taxon_id'];
        $taxon->scientificName  = ucfirst($rec['scientific_name']);
        if($family = @$rec['family']) $taxon->family = ucfirst($family);
        if($taxon->family == "Formicidae") {
            $taxon->phylum  = 'Arthropoda';
            $taxon->class   = 'Insecta';
            $taxon->order   = 'Hymenoptera';
        }
        $taxon->furtherInformationURL = self::compute_furtherInformationURL($taxon->scientificName);
        $taxon->kingdom         = '';
        $taxon->genus           = '';
        $taxon->furtherInformationURL = $t['dc_source'];
        if($reference_ids = @$this->taxa_reference_ids[$t['int_id']]) $taxon->referenceID = implode("; ", $reference_ids);
        $this->taxon_ids[$taxon->taxonID] = '';
        $this->archive_builder->write_object_to_file($taxon);
    }
    private function add_string_types($rec, $value, $measurementType, $measurementOfTaxon = "") {}
    private function add_occurrence($taxon_id, $occurrence_id, $rec, $unique_id) {}
    */
}
?>
