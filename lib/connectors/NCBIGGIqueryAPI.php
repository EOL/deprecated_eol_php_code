<?php
namespace php_active_record;
/* connector: [723] NCBI GGI queries (DATA-1369)
              [730] GGBN Queries for GGI  (DATA-1372)
*/
class NCBIGGIqueryAPI
{
    function __construct($folder, $query)
    {
        $this->query = $query;
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->occurrence_ids = array();
        $this->download_options = array('download_wait_time' => 2000000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1);

        // $this->families_list = "http://localhost/~eolit/cp/NCBIGGI/falo2.in";
        $this->families_list = "https://dl.dropboxusercontent.com/u/7597512/NCBI_GGI/falo2.in";

        // $this->family_service_ncbi = "http://www.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        $this->family_service_ncbi = "http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?db=nucleotide&usehistory=y&term=";
        /* to be used if u want to get all Id's, that is u will loop to get all Id's so server won't be overwhelmed: &retmax=10&retstart=0 */
        
        // GGBN data portal:
        $this->family_service_ggbn = "http://www.dnabank-network.org/Query.php?family=";
    }

    function get_all_taxa()
    {
        $families = self::get_families();
        self::create_instances_from_taxon_object($families);
        $this->create_archive();
    }

    private function get_families()
    {
        $families = array();
        if(!$temp_path_filename = Functions::save_remote_file_to_local($this->families_list)) return;
        echo "\n[$temp_path_filename]\n";
        foreach(new FileIterator($temp_path_filename) as $line_number => $line)
        {
            if($line)
            {
                $line = trim($line);
                $temp = explode("[", $line);
                $family = trim($temp[0]);
                $families[$family] = 1;
            }
        }
        unlink($temp_path_filename);
        return array_keys($families);
    }
    
    private function create_instances_from_taxon_object($families)
    {
        $i = 0;
        $total = count($families);
        foreach($families as $family)
        {
            $i++;
            // if($i >= 1) return; //debug
            // $family = "Cystobacteraceae"; //debug 11 pages
            if($family == "Family Unassigned") continue;
            if($this->query == "ncbi_sequence_info")         self::query_family_NCBI_info($family);
            elseif($this->query == "ggbn_dna_specimen_info") self::query_family_GGBN_info($family);
            echo "\n $i of $total - [$family]";
            $taxon = new \eol_schema\Taxon();
            $taxon->taxonID         = $family;
            $taxon->scientificName  = $family;
            $taxon->taxonRank       = "family";
            $taxon->taxonRemarks    = "";
            $taxon->rightsHolder    = "";
            $taxon->furtherInformationURL = "";
            $this->taxa[$taxon->taxonID] = $taxon;
        }
    }

    private function query_family_GGBN_info($family)
    {
        /*<tr class='head'><td colspan='7' align='center'></td></tr>
        <tr><td colspan='7'><hr /></td></tr>
        <table border='0' id='TableWrapper2'>
        <tr>
            <td></td>
            <td class='head2' width='130'><a href='query.php?hitlist=true&sort=SU'><img src='images/upg.jpg' border='0'/></a> Species <a href='query.php?hitlist=true&sort=SD'><img src='images/down.jpg' border='0'/></a></td>
            <td class='head2'><a href='query.php?hitlist=true&sort=CU'><img src='images/up.jpg' border='0'/></a> Country <a href='query.php?hitlist=true&sort=CD'><img src='images/down.jpg' border='0'/></a></td>
            <td class='head2'><a href='query.php?hitlist=true&sort=DU'><img src='images/up.jpg' border='0'/></a> DNA No. <a href='query.php?hitlist=true&sort=DD'><img src='images/down.jpg' border='0'/></a></td>
            <td class='head2'><a href='query.php?hitlist=true&sort=VU'><img src='images/up.jpg' border='0'/></a> Specimen No. <a href='query.php?hitlist=true&sort=VD'><img src='images/down.jpg' border='0'/></a></td>
        </tr>
            <tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'>
                <td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00012&CollCode=DNA bank&InstCode=Ocean 
                Genome Legacy&UnitIDS=S00031&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=121902">
                <img src='images/Zoom.jpg' border='0'> Gadus morhua Linnaeus, 1758</a></td><td valign='top'>Unknown or unspecified country</td>
                <td valign='top'>E00012</b></td><td valign='top'>S00031</td></tr>
            <tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'>
                <td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00006&CollCode=DNA bank&InstCode=Ocean 
                Genome Legacy&UnitIDS=S00031&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=121901">
                <img src='images/Zoom.jpg' border='0'> Gadus morhua Linnaeus, 1758</a></td>
                <td valign='top'>Unknown or unspecified country</td>
                <td valign='top'>E00006</b></td><td valign='top'>S00031</td></tr>
                <tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00004&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00031&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=121900"><img src='images/Zoom.jpg' border='0'> Gadus morhua Linnaeus, 1758</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00004</b></td><td valign='top'>S00031</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00221&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00552&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=122122"><img src='images/Zoom.jpg' border='0'> Gadus morhua Linnaeus, 1758</a></td><td valign='top'>Iceland</td><td valign='top'>E00221</b></td><td valign='top'>S00552</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00066&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00019&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=121945"><img src='images/Zoom.jpg' border='0'> Gadus morhua Linnaeus, 1758</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00066</b></td><td valign='top'>S00019</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00065&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00019&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=121944"><img src='images/Zoom.jpg' border='0'> Gadus morhua Linnaeus, 1758</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00065</b></td><td valign='top'>S00019</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00222&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00552&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=122123"><img src='images/Zoom.jpg' border='0'> Melanogrammus aeglefinus (Linnaeus, 1758)</a></td><td valign='top'>Canada</td><td valign='top'>E00222</b></td><td valign='top'>S00552</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00003&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00031&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=121928"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00003</b></td><td valign='top'>S00031</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00002&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00031&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=121927"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00002</b></td><td valign='top'>S00031</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E02862&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00026&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=123184"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E02862</b></td><td valign='top'>S00026</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00157&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00032&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=122036"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00157</b></td><td valign='top'>S00032</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00156&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00032&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=122035"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00156</b></td><td valign='top'>S00032</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00155&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00032&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=122034"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00155</b></td><td valign='top'>S00032</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00109&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00030&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=122014"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00109</b></td><td valign='top'>S00030</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00108&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00030&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=122013"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00108</b></td><td valign='top'>S00030</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00107&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00030&CollCodeS=OGL&InstCodeS=Bermuda Aquarium, Museum, and Zoo&ID_Cache=122012"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00107</b></td><td valign='top'>S00030</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00085&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00275&CollCodeS=OGL&InstCodeS=Ocean Genome Legacy&ID_Cache=121967"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00085</b></td><td valign='top'>S00275</td></tr><tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'><td width='40%' valign='top' colspan='2'><b><a class='hitlist' href="Query.php?sqlType=Detail&UnitID=E00084&CollCode=DNA bank&InstCode=Ocean Genome Legacy&UnitIDS=S00275&CollCodeS=OGL&InstCodeS=Ocean Genome Legacy&ID_Cache=121966"><img src='images/Zoom.jpg' border='0'> Pollachius virens (Linnaeus, 1758)</a></td><td valign='top'>Unknown or unspecified country</td><td valign='top'>E00084</b></td><td valign='top'>S00275</td></tr><tr><td colspan='7'><hr /></td></tr><tr class='head'><td colspan
                ='7' align='center'></td></tr>
        </table></form>*/
        $records = array();
        $rec["source"] = $this->family_service_ggbn . $family;
        if($html = Functions::lookup_with_cache($rec["source"], $this->download_options))
        {
            $rec["taxon_id"] = $family;
            if(preg_match("/<b>(.*?) entries found/ims", $html, $arr) || preg_match("/<b>(.*?) entry found/ims", $html, $arr))
            {
                $rec["object_id"] = "NumberDNAInGGBN";
                self::add_string_types($rec, "NumberDNAInGGBN", $arr[1], false, $family); //no measurementType yet
            }
            $pages = self::get_number_of_pages($html);
            for ($i = 1; $i <= $pages; $i++)
            {
                if($i > 1) $html = Functions::lookup_with_cache($this->family_service_ggbn . $family . "&page=$i", $this->download_options);
                if($temp = self::process_html($html)) $records = array_merge($records, $temp);
            }
            if($records)
            {
                $rec["object_id"] = "NumberSpecimensInGGBN";
                self::add_string_types($rec, "NumberSpecimensInGGBN", count($records), "http://eol.org/schema/terms/NumberSpecimensInGGBN", $family);
                $rec["object_id"] = "SpecimensInGGBN";
                self::add_string_types($rec, "SpecimensInGGBN", "http://eol.org/schema/terms/yes", "http://eol.org/schema/terms/SpecimensInGGBN", $family);
            }
            else
            {
                $rec["object_id"] = "SpecimensInGGBN";
                self::add_string_types($rec, "SpecimensInGGBN", "http://eol.org/schema/terms/no", "http://eol.org/schema/terms/SpecimensInGGBN", $family);
            }
        }
    }

    private function get_number_of_pages($html)
    {
        if(preg_match_all("/hitlist=true&page=(.*?)\"/ims", $html, $arr)) return array_pop(array_unique($arr[1]));
        return 1;
    }
    
    private function process_html($html)
    {
        $temp = array();
        $html = str_ireplace("<tr style='border-top-width:1px;border-top-style:solid;border-color:#CCCCCC'>", "<tr style='elix'>", $html);
        if(preg_match_all("/<tr style=\'elix\'>(.*?)<\/tr>/ims", $html, $arr))
        {
            foreach($arr[1] as $r)
            {
                $r = strip_tags($r, "<td>");
                if(preg_match_all("/<td valign=\'top\'>(.*?)<\/td>/ims", $r, $arr2)) $temp[] = $arr2[1][2]; //get last coloumn (specimen no.)
            }
        }
        return array_unique($temp);
    }

    private function query_family_NCBI_info($family)
    {
        $rec["source"] = $this->family_service_ncbi . $family;
        $contents = Functions::lookup_with_cache($rec["source"], $this->download_options);
        if($xml = simplexml_load_string($contents))
        {
            $rec["taxon_id"] = $family;
            $rec["object_id"] = "_no_of_seq_in_genbank";
            self::add_string_types($rec, "Number Of Sequences In GenBank", $xml->Count, "http://eol.org/schema/terms/NumberOfSequencesInGenBank", $family);
        }
    }

    private function add_string_types($rec, $label, $value, $measurementType, $family)
    {
        echo "\n [$label]:[$value]\n";
        $taxon_id = (string) $rec["taxon_id"];
        $object_id = (string) $rec["object_id"];
        $m = new \eol_schema\MeasurementOrFact();
        $occurrence = $this->add_occurrence($taxon_id, $object_id);
        $m->occurrenceID        = $occurrence->occurrenceID;
        $m->measurementOfTaxon  = 'true';
        $m->source              = $rec["source"];
        if($val = $measurementType) $m->measurementType = $val;
        else                        $m->measurementType = "http://ggbn.org/". SparqlClient::to_underscore($label);
        $m->measurementValue    = (string) $value;
        // $m->measurementMethod   = '';
        // $m->measurementRemarks  = '';
        // $m->contributor = "";
        // $m->referenceID = "";
        $this->archive_builder->write_object_to_file($m);
    }

    private function add_occurrence($taxon_id, $object_id)
    {
        $occurrence_id = md5($taxon_id . 'o' . $object_id);
        $occurrence_id = $taxon_id . 'O' . $object_id; // suggested by Katja to use -- ['O' . $object_id]
        if(isset($this->occurrence_ids[$occurrence_id])) return $this->occurrence_ids[$occurrence_id];
        $o = new \eol_schema\Occurrence();
        $o->occurrenceID = $occurrence_id;
        $o->taxonID = $taxon_id;
        $this->archive_builder->write_object_to_file($o);
        $this->occurrence_ids[$occurrence_id] = $o;
        return $o;
    }

    private function create_archive()
    {
        foreach($this->taxa as $t) $this->archive_builder->write_object_to_file($t);
        $this->archive_builder->finalize(TRUE);
    }

}
?>