<?php
namespace php_active_record;
/* connector: [jamstec] https://eol-jira.bibalex.org/browse/DATA-1828
*/
class JamstecAPI
{
    function __construct($folder = null)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->debug = array();
        $this->download_options = array('cache' => 1, 'resource_id' => $this->resource_id, 'timeout' => 3600, 'download_attempts' => 1, 'expire_seconds' => 60*60*24*30*3); //orig expires quarterly
        // $this->download_options['expire_seconds'] = false; //debug only
        
        $this->meta['Species'] = "https://editors.eol.org/other_files/JAMSTEC/Species/EOLmetadata_Species_20190624.xlsx";
        $this->meta['Genus'] = "https://editors.eol.org/other_files/JAMSTEC/Genus/EOLmetadata_Genus_20190624.xlsx";
        $this->meta['Family'] = "https://editors.eol.org/other_files/JAMSTEC/Family/EOLmetadata_Family_20190624.xlsx";
        $this->meta['Subfamily'] = "https://editors.eol.org/other_files/JAMSTEC/Subfamily/EOLmetadata_Subfamily_20190624.xlsx";
        $this->image_path = "https://editors.eol.org/other_files/JAMSTEC/";
    }
    function start()
    {   
        self::main();
        $this->archive_builder->finalize(TRUE);
        print_r($this->debug);
    }
    private function main()
    {
        $groups = array('Species', 'Genus', 'Family', 'Subfamily');
        foreach($groups as $group) {
            $recs = self::convert_sheet2array($group);
            print_r($recs);
            exit("\n");
        }
    }
    private function create_media()
    {   /*JAMSTEC,DwC-A,notes
        BISMaLTaxonID,http://rs.tdwg.org/dwc/terms/taxonID,
        scientificName,http://rs.tdwg.org/dwc/terms/scientificName,
        license,http://ns.adobe.com/xap/1.0/rights/UsageTerms,
        imageID,http://purl.org/dc/terms/identifier,
        url,http://rs.tdwg.org/ac/terms/furtherInformationURL,
        owner,http://ns.adobe.com/xap/1.0/rights/Owner,
        the pilot・operator・image editor,http://purl.org/dc/terms/contributor,
        Taxonomy,,"I think a parent-child hierarchy would be nice, if you can construct one fairly safely. If that's painful, it should suffice to grab Animalia and the one element that follows it and use just Kingdom and Phylum columns"
        a short text title,http://purl.org/dc/terms/title,
        a longer text caption,http://purl.org/dc/terms/description,
         date,http://ns.adobe.com/xap/1.0/CreateDate,
        verbal location name,http://iptc.org/std/Iptc4xmpExt/1.0/xmlns/LocationCreated,
        latitude,http://www.w3.org/2003/01/geo/wgs84_pos#lat,
        longitude,http://www.w3.org/2003/01/geo/wgs84_pos#long,
        water depth,http://www.w3.org/2003/01/geo/wgs84_pos#alt,"please insert a ""-"" in front of the string"
        partner site,http://purl.org/dc/terms/bibliographicCitation,
        ,,
        CC-BY-NC,http://creativecommons.org/licenses/by-nc/4.0/,
        */
        
    }
    private function convert_sheet2array($group)
    {
        $final = array();
        $options = $this->download_options;
        $options['file_extension'] = 'xlsx';
        if($local_xls = Functions::save_remote_file_to_local($this->meta[$group], $options)) {
            require_library('XLSParser');
            $parser = new XLSParser();
            debug("\n reading: " . $local_xls . "\n");
            $temp = $parser->convert_sheet_to_array($local_xls);
            print_r($temp); exit;
            $fields = array_keys($temp);
        }
        unlink($local_xls);
        return $final;
    }
    
    private function create_instances_from_taxon_object($rec)
    {
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                 = $rec->identifier;
        $taxon->scientificName          = $rec->scientificName;
        $taxon->kingdom                 = $rec->kingdom;
        $taxon->phylum                  = $rec->phylum;
        $taxon->class                   = $rec->class;
        $taxon->order                   = $rec->order;
        $taxon->family                  = $rec->family;
        $taxon->furtherInformationURL   = $rec->source;
        debug(" - " . $taxon->scientificName . " [$taxon->taxonID]");
        $this->archive_builder->write_object_to_file($taxon);
    }
    /*
    function load_zip_contents($zip_path, $download_options, $files, $extension)
    {   $text_path = array();
        $temp_path = create_temp_dir();
        if($file_contents = Functions::lookup_with_cache($zip_path, $download_options)) // resource is set to harvest quarterly and the cache expires by default in a month
        {
            $parts = pathinfo($zip_path);
            $temp_file_path = $temp_path . "/" . $parts["basename"];
            if(!($TMP = Functions::file_open($temp_file_path, "w"))) return;
            fwrite($TMP, $file_contents);
            fclose($TMP);
            $output = shell_exec("unzip -o $temp_file_path -d $temp_path");
            if(file_exists($temp_path . "/" . $files[0] . $extension)) {
                foreach($files as $file) {
                    $text_path[$file] = $temp_path . "/" . $file . $extension;
                }
            }
        }
        else echo "\n\n Connector terminated. Remote files are not ready.\n\n";
        return $text_path;
    }
    */
}
?>
