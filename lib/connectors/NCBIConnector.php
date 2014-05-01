<?php
namespace php_active_record;

class NCBIConnector
{
    const DUMP_URL = "ftp://ftp.ncbi.nih.gov/pub/taxonomy/taxdump.tar.gz";

    public function __construct($resource_id)
    {
        $this->resource_id = $resource_id;
        $this->invalid_synonym_types = array(
            'includes', 'type material', 'in-part', 'misnomer', 'acronym', 'genbank acronym');
    }

    public function build_archive()
    {
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . "/$this->resource_id/";
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));

        if($download_directory = ContentManager::download_temp_file_and_assign_extension(self::DUMP_URL))
        {
            if(is_dir($download_directory) && file_exists($download_directory ."/names.dmp"))
            {
                $this->download_directory = $download_directory;
                $this->get_names();
                $this->get_nodes();
                recursive_rmdir($download_directory);
            }
        }

        $this->archive_builder->finalize(true);
    }

    public function get_names()
    {
        $this->taxon_names = array();
        $this->taxon_synonyms = array();
        $this->taxon_name_classes = array();
        $path = $this->download_directory ."/names.dmp";
        foreach(new FileIterator($path) as $line_number => $line)
        {
            if($line_number % 10000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $line_data  = explode("\t|", $line);
            $tax_id = @trim($line_data[0]);
            $name = @trim($line_data[1]);
            $name_class = @trim($line_data[3]);
            if(!is_numeric($tax_id)) continue;
            if(!$name) continue;
            if(!$name_class) continue;
            if($tax_id == 1) continue;  // tax_id 1 is a sudo-node with the name of 'root'

            // remove single and double quotes from ('")word or phrase('")
            while(preg_match("/(^| )(('|\")(.*?)\\3)( |-|$)/", $name, $arr))
            {
                $name = str_replace($arr[2], $arr[4], $name);
            }
            $name = preg_replace("/^\[([a-z0-9 ]+)\] /i", "\\1 ", $name);
            $name = preg_replace("/ \[([a-z0-9 ]+)\]$/i", " \\1", $name);
            while(preg_match("/  /", $name)) $name = str_replace("  ", " ", $name);

            if($name_class == "scientific name") $this->taxon_names[$tax_id] = $name;
            elseif(!in_array($name_class, $this->invalid_synonym_types)) $this->taxon_synonyms[$tax_id][$name_class][$name] = true;
        }
    }

    public function get_nodes()
    {
        $path = $this->download_directory ."/nodes.dmp";
        foreach(new FileIterator($path) as $line_number => $line)
        {
            if($line_number % 10000 == 0) echo "$line_number :: ". time_elapsed() ." :: ". memory_get_usage() ."\n";
            $line_data  = explode("\t|", $line);
            $tax_id = trim($line_data[0]);
            $parent_tax_id = trim($line_data[1]);
            $rank = trim($line_data[2]);
            $comments = trim($line_data[12]);
            if(!is_numeric($tax_id)) continue;
            if(!is_numeric($parent_tax_id)) continue;

            if($rank == "no rank") $rank = "";
            // tax_id 1 is a sudo-node named 'root'. Things with 1 as a parent are the real roots
            if($parent_tax_id == 1) $parent_tax_id = 0;

            if(isset($this->taxon_names[$tax_id]))
            {
                $t = new \eol_schema\Taxon();
                $t->taxonID = $tax_id;
                $t->scientificName = $this->taxon_names[$tax_id];
                $t->parentNameUsageID = $parent_tax_id;
                $t->taxonRank = $rank;
                $t->taxonomicStatus = "valid";
                $t->taxonRemarks = $comments;
                $this->archive_builder->write_object_to_file($t);

                if(isset($this->taxon_synonyms[$tax_id]))
                {
                    foreach($this->taxon_synonyms[$tax_id] as $name_class => $names)
                    {
                        if(in_array($name_class, array("genbank common name", "common name", "blast name")))
                        {
                            foreach($names as $name => $junk)
                            {
                                $v = new \eol_schema\VernacularName();
                                $v->taxonID = $tax_id;
                                $v->vernacularName = $name;
                                $v->language = "en";
                                $this->archive_builder->write_object_to_file($v);
                            }
                        }else
                        {
                            foreach($names as $name => $junk)
                            {
                                $t = new \eol_schema\Taxon();
                                $t->taxonID = $tax_id ."_syn_". md5($name.$name_class);
                                $t->scientificName = $name;
                                $t->acceptedNameUsageID = $tax_id;
                                $t->taxonomicStatus = $name_class;
                                $this->archive_builder->write_object_to_file($t);
                            }
                        }
                    }
                }
            }
        }
    }
    
}

?>