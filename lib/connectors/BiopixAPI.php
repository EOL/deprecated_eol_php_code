<?php
namespace php_active_record;

class BiopixAPI
{
    function __construct()
    {
        $this->data_dump_url = "http://www.biopix.com/sitemaps/eol.txt";
        $this->taxa = array();
        $this->taxon_ids_written = array();
        $this->media = array();
        $this->categories_with_taxa = array();
        $this->kingdoms = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/31_working/';
    }
    
    function get_all_taxa()
    {
        // download the dump file to temporary file on the server
        $filepath = self::download_resource_data();
        
        echo "\nfilepath: [$filepath]\n";
        // read each line in the file
        $i = 0;
        foreach(new FileIterator($filepath) as $line_number => $line) {
            // $i++; echo "\n[$i] ";
            $this->create_instances_from_row($line);
        }
        
        // finalize the process and create the archive
        $this->create_archive();
        
        unlink($filepath);
    }
    
    function create_instances_from_row($line)
    {
        // split the line into columns on TAB
        $columns = explode("\t", $line);
        $category = @trim($columns[0]);
        $scientific_name = @trim($columns[1]);
        $family = @trim($columns[2]);
        $image_id = @trim($columns[3]);
        $image_url = @trim($columns[4]);
        $source_url = @trim($columns[5]);
        $title = @trim($columns[6]);
        $location = @trim($columns[7]);
        $rating = @trim($columns[8]);
        $owner = @trim($columns[9]);
        
        // we must have a taxon name and an image
        if(!$scientific_name) return;
        if(!$image_id) return;
        if(!$image_url) return;
        
        // add the family the first time we see it
        $family_id = null;
        if($family) {
            $family_id = md5($family);
            if(!isset($this->taxon_ids_written[$family_id])) {
                $t = new \eol_schema\Taxon();
                $t->taxonID = $family_id;
                $t->taxonRank = 'family';
                $t->scientificName = $family;
                $t->parentNameUsageID = $this->get_parent_name_usage_id($category);
                $this->taxa[$family] = $t;
                $this->taxon_ids_written[$family_id] = $family_id;
            }
        }
        
        // add the taxon the first time we see them
        $taxon_id = str_replace(" ", "_", $scientific_name);
        if(!isset($this->taxa[$taxon_id])) {
            $t = new \eol_schema\Taxon();
            $t->taxonID = str_replace(" ", "_", $scientific_name);
            if($family_id) $t->parentNameUsageID = $family_id;
            else $t->parentNameUsageID = $this->get_parent_name_usage_id($category);
            $t->scientificName = $scientific_name;
            $t->source = 'http://www.biopix.com/species.asp?searchtext=' . str_replace(" ", "%20", $scientific_name);
            $this->taxa[$taxon_id] = $t;
        }
        
        // add the image itself
        $m = new \eol_schema\MediaResource();
        $m->identifier = $image_id;
        $m->taxonID = $taxon_id;
        $m->type = 'http://purl.org/dc/dcmitype/StillImage';
        $m->format = 'image/jpeg';
        $m->accessURI = $image_url;
        $m->furtherInformationURL = $source_url;
        $m->LocationCreated = $location;
        $m->Rating = self::convert_biopix_rating($rating);
        $m->UsageTerms = 'http://creativecommons.org/licenses/by-nc/3.0/';
        $m->Owner = $owner;
        $this->media[$image_id] = $m;
    }
    
    function create_archive()
    {
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        foreach($this->taxa as $t) {
            $this->archive_builder->write_object_to_file($t);
        }
        
        foreach($this->media as $m) {
            $this->archive_builder->write_object_to_file($m);
        }
        $this->archive_builder->finalize(true);
    }
    
    static function convert_biopix_rating($rating)
    {
        if($rating == 1) return 4;
        elseif($rating == 2) return 3.5;
        elseif($rating == 3) return 3;
        else return 2.5;
    }
    
    public function get_parent_name_usage_id($category)
    {
        static $kingdoms;
        $category = strtolower($category);
        if(!$kingdoms) $kingdoms = $this->category_kingdoms();
        // we've figured out the parent ID before, so return it
        if(isset($this->parent_taxon_id_from_category[$category])) {
            return $this->parent_taxon_id_from_category[$category];
        }
        
        // otherwise if we recognize the category
        if(isset($kingdoms[$category])) {
            $parent_taxon_id = null;
            $taxon_id = null;
            foreach($kingdoms[$category] as $rank => $taxon_name) {
                $taxon_id = md5($taxon_name);
                
                if(!isset($this->taxon_ids_written[$taxon_id])) {
                    $t = new \eol_schema\Taxon();
                    $t->taxonID = $taxon_id;
                    $t->taxonRank = $rank;
                    $t->scientificName = $taxon_name;
                    if($parent_taxon_id) $t->parentNameUsageID = $parent_taxon_id;
                    $this->taxa[$taxon_id] = $t;
                    $this->taxon_ids_written[$taxon_id] = $taxon_id;
                }
                $parent_taxon_id = $taxon_id;
            }
            $this->parent_taxon_id_from_category[$category] = $parent_taxon_id;
            return $taxon_id;
        }
        
        // default parent ID of 0
        return 0;
    }
    
    public function category_kingdoms()
    {
        if($this->kingdoms) return $this->kingdoms;
        $this->kingdoms["algerlaver"]     = array();
        $this->kingdoms["padderkrybdyr"]  = array("kingdom" => "Animalia", "phylum" => "Chordata");
        $this->kingdoms["arthropoda"]     = array("kingdom" => "Animalia", "phylum" => "Arthropoda");
        $this->kingdoms["fugle"]          = array("kingdom" => "Animalia", "phylum" => "Chordata", "class" => "Aves");
        $this->kingdoms["sommerfugle"]    = array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta", "order" => "Lepidoptera");
        $this->kingdoms["kulturplanter"]  = array("kingdom" => "Plantae");
        $this->kingdoms["husdyr"]         = array("kingdom" => "Animalia");
        $this->kingdoms["fisk"]           = array("kingdom" => "Animalia", "phylum" => "Chordata");
        $this->kingdoms["svampe"]         = array("kingdom" => "Fungi");
        $this->kingdoms["insekter"]       = array("kingdom" => "Animalia", "phylum" => "Arthropoda", "class" => "Insecta");
        $this->kingdoms["lavereDyr"]      = array("kingdom" => "Animalia");
        $this->kingdoms["pattedyr"]       = array("kingdom" => "Animalia", "phylum" => "Chordata", "class" => "Mammalia");
        $this->kingdoms["bloeddyr"]       = array("kingdom" => "Animalia", "phylum" => "Mollusca");
        $this->kingdoms["mosser"]         = array();
        $this->kingdoms["planter"]        = array("kingdom" => "Plantae");
        return $this->kingdoms;
    }
    
    private function download_resource_data()
    {
        $biopix_tab_data = Functions::save_remote_file_to_local($this->data_dump_url, array('cache' => 1, 'expire_seconds' => 60*60*24*25, 'timeout' => 300));
        return $biopix_tab_data;
    }
}
?>
