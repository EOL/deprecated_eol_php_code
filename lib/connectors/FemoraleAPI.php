<?php
namespace php_active_record;
/* connector: [793]: at this point it is a one-time export 
Partner gave us spreadsheets (4). There is structured data (body length), and images. We scrape the mediaURLs from the site.
*/
class FemoraleAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->taxon_ids = array();
        $this->occurrence_ids = array();
        $this->measurement_ids = array();
        $this->object_ids = array();
        $this->download_options = array('resource_id' => $this->resource_id, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1);
        $this->download_options['expire_seconds'] = false; //seems one-time harvest but it depends.
        $this->url_path = "http://localhost/~eolit/cp_new/Femorale/";
        // $this->url_path = "https://dl.dropboxusercontent.com/u/7597512/Femorale/"; //obsolete
        $this->url_path = "https://github.com/eliagbayani/EOL-connector-data-files/raw/master/Femorale/";
        $this->images_path = "http://www.femorale.com/shellphotos/detmore.asp?&localidade=&url=";
        $this->spreadsheets = array();
        $this->spreadsheets[] = "Encyclopedia_Of_Life_Other.xls";        // 365 KB
        $this->spreadsheets[] = "Encyclopedia_Of_Life_Bivalvia.xls";     // 1.0 MB
        $this->spreadsheets[] = "Encyclopedia_Of_Life_Land.xls";         // 1.9 MB
        $this->spreadsheets[] = "Encyclopedia_Of_Life_Gastropoda.xls";   // 4.6 MB
        // $this->spreadsheets[] = "Encyclopedia_Of_Life_Bivalvia_small.xls";
    }

    function get_all_taxa()
    {
        require_library('XLSParser');
        $docs = count($this->spreadsheets);
        $doc_count = 0;
        foreach($this->spreadsheets as $doc)
        {
            $doc_count++;
            echo "\n processing [$doc]...\n";
            if($path = Functions::save_remote_file_to_local($this->url_path . $doc, array("cache" => 1, "timeout" => 3600, "file_extension" => "xls", 'download_attempts' => 2, 'delay_in_minutes' => 2)))
            {
                $parser = new XLSParser();
                $arr = $parser->convert_sheet_to_array($path);
                $fields = array_keys($arr);
                $i = -1;
                $rows = count($arr["Species"]);
                echo "\n total $path: $rows \n";
                foreach($arr["Species"] as $Species)
                {
                    $i++;
                    $rec = array();
                    foreach($fields as $field) $rec[$field] = $arr[$field][$i];
                    $rec = array_map('trim', $rec);
                    
                    /* breakdown when caching
                    $cont = false;
                    // if($i >= 1 && $i < 6000)         $cont = true;
                    // if($i >= 3000 && $i < 6000)      $cont = true;
                    // if($i >= 6000 && $i < 9000)      $cont = true;
                    // if($i >= 9000 && $i < 12000)     $cont = true;
                    // if($i >= 11800 && $i < 15000)    $cont = true;
                    if(!$cont) continue;
                    */
                    
                    print "\n [$doc_count of $docs][" . ($i+1) . " of $rows] " . $rec["Species"] . "\n";
                    $rec = self::clean_taxon_name($rec);
                    $taxon_id = trim(preg_replace('/\s*\([^)]*\)/', '', $rec["sciname"])); // remove parenthesis
                    $taxon_id = str_replace(" ", "_", $taxon_id);
                    $rec["taxon_id"] = md5($taxon_id);
                    self::create_instances_from_taxon_object($rec);
                    self::prepare_images($rec);
                    self::prepare_data($rec);
                    
                    // if($i >= 3) break; //debug only
                }
                unlink($path);
            }
            else echo "\n [$doc] unavailable! \n";
        }
        $this->archive_builder->finalize(TRUE);
    }
    
    private function prepare_data($rec)
    {
        $rec["object_id"] = "size";
        $val = trim(str_replace(array("mm", " up"), "", $rec["Size"]));
        if($val) self::add_string_types($rec, "size", $val, "http://purl.obolibrary.org/obo/CMO_0000013", "true");
        // commented for now
        // $rec["object_id"] = "locality";
        // self::add_string_types($rec, "locality", $rec["Locality"], "http://rs.tdwg.org/dwc/terms/locality", "false");
    }

    private function prepare_images($rec)
    {
        if($mediaURLs = self::get_image_urls($rec))
        {
            print "\n images: " . count($mediaURLs) . "\n";
            foreach($mediaURLs as $mediaURL)
            {
                /* not used for now
                $desc = "";
                if($val = $rec["Locality"]) $desc .= "Locality: " . $val . "<br>";
                if($val = $rec["Size"])     $desc .= "Size: " . $val . "<br>";
                if($val = $rec["Book"])     $desc .= "Book: " . $val . "<br>";
                if($val = $rec["Synonym"])  $desc .= "Synonym: " . $val . "<br>";
                */
                $mr = new \eol_schema\MediaResource();
                $mr->taxonID        = $rec["taxon_id"];
                $mr->identifier     = md5($mediaURL);
                $mr->type           = "http://purl.org/dc/dcmitype/StillImage";
                $mr->format         = Functions::get_mimetype($mediaURL);
                $mr->Owner          = "Femorale";
                $mr->UsageTerms     = "http://creativecommons.org/licenses/by-nc/3.0/";
                $mr->accessURI      = $mediaURL;
                $mr->furtherInformationURL = str_replace(" ", "%20", $rec["Expr1"]);
                if(!isset($this->object_ids[$mr->identifier]))
                {
                    $this->archive_builder->write_object_to_file($mr);
                    $this->object_ids[$mr->identifier] = '';
                }
            }
        }
    }
    
    private function get_image_urls($rec)
    {
        $mediaURLs = array();
        $url = $this->images_path . "&species=" . $rec["Species"] . "&navi=";
        if($html = Functions::lookup_with_cache($url . "1", $this->download_options))
        {
            $navi = 1;
            if(preg_match("/>1 of (.*?)<\/font/ims", $html, $arr)) $navi = trim($arr[1]);
            for($i=1; $i<=$navi; $i++)
            {
                if($i == 1)
                {
                    if(preg_match_all("/src=\"(.*?)\"/ims", $html, $arr)) $mediaURLs = array_merge($mediaURLs, $arr[1]);
                }
                else
                {
                    if($html = Functions::lookup_with_cache($url . $i, $this->download_options))
                    {
                        if(preg_match_all("/src=\"(.*?)\"/ims", $html, $arr)) $mediaURLs = array_merge($mediaURLs, $arr[1]);
                    }
                }
            }
        }
        return $mediaURLs;
    }

    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID         = $rec["taxon_id"];
        $taxon->scientificName  = $rec["sciname"];
        $taxon->taxonRank       = $rec["rank"];
        $taxon->family          = ucfirst(strtolower($rec["Family"]));
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = '';
        }
    }

    private function clean_taxon_name($rec)
    {
        $strings = array(" sp ", " sp.");
        $found = false;
        foreach($strings as $string)
        {
            if(is_numeric(stripos($rec["Species"], $string))) $found = true;
        }
        if($found)
        {
            $rec["sciname"] = Functions::canonical_form($rec["Species"]);
            $rec["rank"] = "genus";
        }
        else
        {
            $rec["sciname"] = $rec["Species"];
            $rec["rank"] = "species";
        }
        return $rec;
    }
    
    private function add_string_types($rec, $label, $value, $measurementType, $measurementOfTaxon)
    {
        $taxon_id = $rec["taxon_id"];
        $object_id = $rec["object_id"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $object_id);
        $m->occurrenceID        = $occurrence->occurrenceID;
        $m->measurementOfTaxon  = $measurementOfTaxon;
        if($label == "size")
        {
            $m->source              = str_replace(" ", "%20", $rec["Expr1"]);
            $m->source              = str_replace(",", "%2C", $m->source);
            $m->source              = str_replace("(", "%28", $m->source);
            $m->source              = str_replace(")", "%29", $m->source);
            $m->measurementUnit     = "http://purl.obolibrary.org/obo/UO_0000016"; //mm - millimeter
            $m->measurementRemarks  = "maximum shell dimension";
        }
        $m->measurementType     = $measurementType;
        $m->measurementValue    = $value;
        $m->statisticalMethod   = "http://www.ebi.ac.uk/efo/EFO_0001444";
        if(!isset($this->measurement_ids[$m->occurrenceID]))
        {
            $this->archive_builder->write_object_to_file($m);
            $this->measurement_ids[$m->occurrenceID] = '';
        }
    }

    private function add_occurrence($taxon_id, $object_id)
    {
        $occurrence_id = $taxon_id . '_' . $object_id;
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

}
?>