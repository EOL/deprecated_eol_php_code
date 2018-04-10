<?php
namespace php_active_record;
// connector: [pbdb_fresh_harvest.php]
class PaleoDBAPI_v2
{
    function __construct($folder)
    {
        $this->resource_id = $folder;
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->download_options = array('cache' => 1, 'resource_id' => $folder, 'download_wait_time' => 500000, 'timeout' => 10800, 'download_attempts' => 1, 'delay_in_minutes' => 1, 
        'expire_seconds' => 60*60*24*30*3); //cache expires in 3 months // orig
        $this->download_options['expire_seconds'] = false; //debug

        $this->service["taxon"] = "http://localhost/cp/PaleoDB/TRAM-746/alltaxa.json";

        $this->map['acceptedNameUsageID']       = "acc";
        $this->map['phylum']                    = "phl";
        $this->map['class']                     = "cll";
        $this->map['order']                     = "odl";
        $this->map['family']                    = "fml";
        $this->map['genus']                     = "gnl";

        $this->map['taxonID']                   = "oid";
        // $this->map['taxonID']                   = "vid";
        $this->map['scientificName']            = "nam";
        $this->map['scientificNameAuthorship']  = "att";
        
        // $this->map['furtherInformationURL']     = "oid";
        $this->map['parentNameUsageID']         = "par";
        $this->map['taxonRank']                 = "rnk";
        $this->map['taxonomicStatus']           = "tdf";
        $this->map['nameAccordingTo']           = "ref";


        /* used in PaleoDBAPI.php
        $this->service["collection"] = "http://paleobiodb.org/data1.1/colls/list.csv?vocab=pbdb&limit=10&show=bin,attr,ref,loc,paleoloc,prot,time,strat,stratext,lith,lithext,geo,rem,ent,entname,crmod&taxon_name=";
        $this->service["occurrence"] = "http://paleobiodb.org/data1.1/occs/list.csv?show=loc,time&limit=10&base_name=";
        $this->service["reference"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=displayRefResults&type=view&reference_no=";
        $this->service["source"] = "http://paleobiodb.org/cgi-bin/bridge.pl?a=checkTaxonInfo&is_real_user=1&taxon_no=";
        */
    }

    function get_all_taxa()
    {
        self::parse_big_json_file();
    }
    
    private function parse_big_json_file()
    {
        $jsonfile = Functions::save_remote_file_to_local($this->service["taxon"], $this->download_options);
        $i = 0;
        foreach(new FileIterator($jsonfile) as $line_number => $line) {
            $i++;
            // echo "\n-------------------------\n".$line;
            if(substr($line, 0, strlen('{"oid":')) == '{"oid":') {
                $str = substr($line, 0, -1); //remove last char (",") the comma, very important to convert from json to array.
                $arr = json_decode($str, true);
                self::create_taxon_archive($arr);
            }
            if($i > 30) break; //debug
        }
        unlink($jsonfile);
        $this->archive_builder->finalize(TRUE);
    }
    private function create_taxon_archive($a)
    {
        /*(
            [oid] => txn:10
            [rnk] => 5
            [nam] => Actinomma
            [att] => Haeckel 1862
            [par] => txn:64926
            [rid] => ref:6930
            [ext] => 1
            [noc] => 97
            [fea] => 247.2
            [fla] => 242
            [lea] => 86.3
            [lla] => 70.6
            [tei] => Middle Triassic
            [tli] => Cretaceous
            [siz] => 10
            [exs] => 1
            [cll] => Radiolaria
            [cln] => txn:4
            [odl] => Spumellaria
            [odn] => txn:5
            [fml] => Actinommidae
            [fmn] => txn:64926
            [gnl] => Actinomma
            [gnn] => txn:10
            [jev] => marine
            [jec] => Radiolaria
            [jmo] => passively mobile
            [jmc] => Radiolaria
            [jlh] => planktonic
            [jhc] => Radiolaria
            [jdt] => omnivore
            [jdc] => Radiolaria
            [jco] => silica
            [jtc] => Radiolaria
            [ref] => J. J. Sepkoski, Jr. 2002. A compendium of fossil marine animal genera. Bulletins of American Paleontology 363:1-560
        )*/
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID                  = self::compute_taxonID($a);
        $taxon->scientificName           = $a[$this->map['scientificName']];
        $taxon->scientificNameAuthorship = @$a[$this->map['scientificNameAuthorship']];
        $taxon->taxonRank                = self::compute_taxonRank($a);
        $taxon->taxonomicStatus          = self::compute_taxonomicStatus($a);
        
        $taxon->parentNameUsageID        = $a[$this->map['parentNameUsageID']];
        $taxon->acceptedNameUsageID      = @$a[$this->map['acceptedNameUsageID']];
        
        if($val = @$a[$this->map['taxonID']]) $taxon->furtherInformationURL = "https://paleobiodb.org/classic/checkTaxonInfo?taxon_no=" . self::numerical_part($val);

        if(!@$a[$this->map['acceptedNameUsageID']]) { //acceptedNameUsageID => "acc"
            $taxon->parentNameUsageID = @$a[$this->map['parentNameUsageID']];
            $taxon->phylum  = @$a[$this->map['phylum']];
            $taxon->class   = @$a[$this->map['class']];
            $taxon->order   = @$a[$this->map['order']];
            $taxon->family  = @$a[$this->map['family']];
            $taxon->genus   = @$a[$this->map['genus']];
        }

        $this->archive_builder->write_object_to_file($taxon);
    }
    private function compute_taxonID($a)
    {
        if($vid = @$a['vid']) return self::numerical_part($a['oid'])."-".self::numerical_part($vid);
        else                  return self::numerical_part($a['oid']);
    }
    private function numerical_part($var)
    {
        $temp = explode(":", $var);
        return $temp[1];
    }
    private function compute_taxonRank($a)
    {
        $mappings = self::get_rank_mappings();
        if($num = @$a[$this->map['taxonRank']]) {
            if($val = $mappings[$num]) return $val;
        }
        return "";
    }
    private function compute_taxonomicStatus($a)
    {
        $mappings = self::get_taxon_status_mappings();
        if($str_index = @$a[$this->map['taxonomicStatus']]) {
            if($val = $mappings[$str_index]) return $val;
        }
        return "";
    }
    private function get_taxon_status_mappings()
    {
        $s['invalid subgroup of'] = "invalid subgroup";
        $s['nomen dubium'] = "nomen dubium";
        $s['nomen nudum'] = "nomen nudum";
        $s['nomen oblitum'] = "nomen oblitum";
        $s['nomen vanum'] = "nomen vanum";
        $s['objective synonym of'] = "objective synonym";
        $s['replaced by'] = "replaced";
        $s['subjective synonym of'] = "subjective synonym";
        $s['corrected to'] = "corrected";
        $s['misspelling of'] = "misspelling";
        $s['obsolete variant of'] = "obsolete variant";
        $s['reassigned as'] = "reassigned";
        $s['recombined as'] = "recombined";
        return $s;
    }
    private function get_rank_mappings()
    {
        $r[2] = "subspecies";
        $r[3] = "species";
        $r[4] = "subgenus";
        $r[5] = "genus";
        $r[6] = "subtribe";
        $r[7] = "tribe";
        $r[8] = "subfamily";
        $r[9] = "family";
        $r[10] = "superfamily";
        $r[11] = "infraorder";
        $r[12] = "suborder";
        $r[13] = "order";
        $r[14] = "superorder";
        $r[15] = "infraclass";
        $r[16] = "subclass";
        $r[17] = "class";
        $r[18] = "superclass";
        $r[19] = "subphylum";
        $r[20] = "phylum";
        $r[21] = "superphylum";
        $r[22] = "subkingdom";
        $r[23] = "kingdom";
        $r[25] = "unranked clade";
        $r[26] = "informal";
        return $r;
    }

}
?>