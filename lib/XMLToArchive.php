<?php
namespace php_active_record;

class XMLToArchive
{
    public function __construct($path_to_xml_file, $validate_xml = false)
    {
        $this->path_to_xml_file = $path_to_xml_file;
        if(!$this->path_to_xml_file) $this->valid_xml = false;
        else
        {
            $valid = $validate_xml ? SchemaValidator::validate($this->path_to_xml_file) : true;
            if($valid === true) $this->valid_xml = true;
            else $this->valid_xml = false;
        }
    }
    
    public function start_process()
    {
        if(!$this->path_to_xml_file) return false;
        if(!$this->valid_xml) return false;
        
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => DOC_ROOT . 'temp/xml_to_archive/'));
        $this->taxon_ids = array();
        $this->media_ids = array();
        $this->vernacular_name_ids = array();
        $this->reference_ids = array();
        $this->agent_ids = array();
        $reader = new \XMLReader();
        $file = file_get_contents($this->path_to_xml_file);
        $file = iconv("UTF-8", "UTF-8//IGNORE", $file);
        $reader->XML($file);
        
        $i = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
            {
                $taxon_xml = $reader->readOuterXML();
                $t = simplexml_load_string($taxon_xml, null, LIBXML_NOCDATA);
                if($t) $this->add_taxon_to_archive($t);
                
                $i++;
                if($i%100==0) echo "Parsed taxon $i : ". time_elapsed() ."\n";
                // if($i >= 5000) break;
            }
        }
        
        $this->archive_builder->finalize();
    }
    
    private function add_taxon_to_archive($t)
    {
        $t_dc = $t->children("http://purl.org/dc/elements/1.1/");
        $t_dcterms = $t->children("http://purl.org/dc/terms/");
        $t_dwc = $t->children("http://rs.tdwg.org/dwc/dwcore/");
        
        $taxon = new \eol_schema\Taxon();
        $taxon->taxonID = self::clean_string($t_dc->identifier);
        if(preg_match("/xeno-canto species ID:(.*)$/", $taxon->taxonID, $arr)) $taxon->taxonID = trim($arr[1]);
        $taxon->source = self::clean_string($t_dc->source);
        $taxon->kingdom = self::clean_string($t_dwc->Kingdom);
        $taxon->phylum = self::clean_string($t_dwc->Phylum);
        $taxon->class = self::clean_string($t_dwc->Class);
        $taxon->order = self::clean_string($t_dwc->Order);
        if($taxon->order == 'Some_Order') $taxon->order = NULL;
        $taxon->family = self::clean_string($t_dwc->Family);
        $taxon->genus = self::clean_string($t_dwc->Genus);
        $taxon->scientificName = self::clean_string($t_dwc->ScientificName);
        $taxon->taxonRank = self::clean_string($t->rank);
        
        if(!$taxon->scientificName)
        {
            if($name = $taxon->genus)
            {
                $taxon->scientificName = $name;
                $taxon->genus = null;
            }elseif($name = $taxon->family)
            {
                $taxon->scientificName = $name;
                $taxon->family = null;
            }elseif($name = $taxon->order)
            {
                $taxon->scientificName = $name;
                $taxon->order = null;
            }elseif($name = $taxon->class)
            {
                $taxon->scientificName = $name;
                $taxon->class = null;
            }elseif($name = $taxon->phylum)
            {
                $taxon->scientificName = $name;
                $taxon->phylum = null;
            }elseif($name = $taxon->kingdom)
            {
                $taxon->scientificName = $name;
                $taxon->kingdom = null;
            }
        }else
        {
            if(!$taxon->genus && preg_match("/^([A-Z-][a-z-]+) [a-z]+$/", $taxon->scientificName, $arr))
            {
                $taxon->genus = $arr[1];
                if(!$taxon->taxonRank) $taxon->taxonRank = 'species';
            }
        }
        if(!$taxon->scientificName) return;
        
        if(!$taxon->taxonID)
        {
            $taxon->taxonID = md5("$taxon->kingdom|$taxon->phylum|$taxon->class|$taxon->order|$taxon->family|$taxon->genus|$taxon->scientificName");
        }
        
        foreach($t->commonName as $c)
        {
            $attr = $c->attributes();
            $xml_attr = $c->attributes("http://www.w3.org/XML/1998/namespace");
            $vernacular = new \eol_schema\VernacularName();
            $vernacular->taxonID = $taxon->taxonID;
            $vernacular->vernacularName = self::clean_string((string) $c);
            $vernacular->language = @self::clean_string($xml_attr["lang"]);
            if(@self::clean_string($attr['isformal']) == 1) $vernacular->isPreferredName = true;
            $vernacular_id = md5("$vernacular->taxonID|$vernacular->vernacularName|$vernacular->language");
            
            if(!$vernacular->vernacularName) continue;
            if(!isset($this->vernacular_name_ids[$vernacular_id]))
            {
                $this->archive_builder->write_object_to_file($vernacular);
                $this->vernacular_name_ids[$vernacular_id] = 1;
            }
        }
        
        foreach($t->synonym as $s)
        {
            $attr = $s->attributes();
            if(!@$attr["relationship"]) $attr["relationship"] = 'synonym';
            $synonym = new \eol_schema\Taxon();
            $synonym->scientificName = self::clean_string((string) $s);
            $synonym->acceptedNameUsageID = $taxon->taxonID;
            $synonym->taxonomicStatus = trim($attr["relationship"]);
            $synonym->taxonID = md5("$taxon->taxonID|$synonym->scientificName|$synonym->taxonomicStatus");
            
            if(!$synonym->scientificName) continue;
            if(!isset($this->taxon_ids[$synonym->taxonID]))
            {
                $this->archive_builder->write_object_to_file($synonym);
                $this->taxon_ids[$synonym->taxonID] = 1;
            }
        }
        
        /* NO TAXON AGENTS */
        
        $reference_ids = array();
        foreach($t->reference as $r)
        {
            $attr = $r->attributes();
            $doi = @self::clean_string($attr['doi'], 0, 0);
            $uri = @self::clean_string($attr['url'], 0, 0);
            if(!$uri) $uri = @self::clean_string($attr['urn'], 0, 0);
            $reference = new \eol_schema\Reference();
            $reference->full_reference = self::clean_string((string) $r, 0, 0);
            $reference->uri = $uri;
            $reference->doi = $doi;
            $reference->identifier = md5("$reference->full_reference|$reference->uri|$reference->doi");
            
            if(!$reference->full_reference) continue;
            if(!isset($this->reference_ids[$reference->identifier]))
            {
                $this->archive_builder->write_object_to_file($reference);
                $this->reference_ids[$reference->identifier] = 1;
            }
            $reference_ids[] = $reference->identifier;
        }
        $taxon->referenceID = implode(";", $reference_ids);
        
        if(!isset($this->taxon_ids[$taxon->taxonID]))
        {
            $this->archive_builder->write_object_to_file($taxon);
            $this->taxon_ids[$taxon->taxonID] = 1;
        }
        
        foreach($t->dataObject as $d)
        {
            $d_dc = $d->children("http://purl.org/dc/elements/1.1/");
            $d_dcterms = $d->children("http://purl.org/dc/terms/");
            $d_geo = $d->children("http://www.w3.org/2003/01/geo/wgs84_pos#");
            
            $media = new \eol_schema\MediaResource();
            $media->identifier = self::clean_string($d_dc->identifier);
            if(preg_match("/xeno-canto recording ID:(.*)$/", $media->identifier, $arr)) $media->identifier = trim($arr[1]);
            $media->taxonID = $taxon->taxonID;
            $media->type = self::clean_string($d->dataType);
            $media->format = self::clean_string($d->mimeType);
            $media->title = self::clean_string($d_dc->title, 0, 0);
            $media->description = self::clean_string($d_dc->description, 0, 0);
            $media->accessURI = self::clean_string($d->mediaURL);
            $media->thumbnailURL = self::clean_string($d->thumbnailURL);
            $media->furtherInformationURL = self::clean_string($d_dc->source);
            $media->CreateDate = self::clean_string($d_dcterms->created);
            $media->modified = self::clean_string($d_dcterms->modified);
            $media->language = self::clean_string($d_dc->language);
            $media->UsageTerms = self::clean_string($d->license);
            $media->rights = self::clean_string($d_dc->rights, 0, 0);
            $media->Owner = self::clean_string($d_dcterms->rightsHolder, 0, 0);
            $media->bibliographicCitation = self::clean_string($d_dcterms->bibliographicCitation, 0, 0);
            $media->LocationCreated = self::clean_string($d->location, 0, 0);
            
            if($r = (string) @$d->additionalInformation->rating)
            {
                if((is_numeric($r)) && $r > 0 && $r <= 5) $media->Rating = $r;
            }
            
            if($subtype = @$d->additionalInformation->subtype)
            {
                $media->subtype = self::clean_string($subtype);
            }
            
            if(!$media->language)
            {
                $xml_attr = $d_dc->description->attributes("http://www.w3.org/XML/1998/namespace");
                $media->language = @self::clean_string($xml_attr["lang"]);
            }
            
            $data_object->latitude = 0;
            $data_object->longitude = 0;
            $data_object->altitude = 0;
            foreach($d_geo->Point as $p)
            {
                $p_geo = $p->children("http://www.w3.org/2003/01/geo/wgs84_pos#");
                $media->lat = self::clean_string($p_geo->lat);
                $media->long = self::clean_string($p_geo->long);
                $media->alt = self::clean_string($p_geo->alt);
            }
            
            $agent_ids = array();
            foreach($d->agent as $a)
            {
                $attr = $a->attributes();
                $agent = new \eol_schema\Agent();
                $agent->term_name = self::clean_string((string) $a, 0, 0);
                $agent->term_homepage = @self::clean_string($attr["homepage"]);
                $agent->term_logo = @self::clean_string($attr["logoURL"]);
                $agent->agentRole = @trim($attr["role"]);
                $agent->identifier = md5("$agent->term_name|$agent->term_homepage|$agent->term_logo|$agent->agentRole");
                
                if(!$agent->term_name) continue;
                if(!isset($this->agent_ids[$agent->identifier]))
                {
                    $this->archive_builder->write_object_to_file($agent);
                    $this->agent_ids[$agent->identifier] = 1;
                }
                $agent_ids[] = $agent->identifier;
            }
            $media->agentID = implode(";", $agent_ids);
            
            foreach($d->audience as $a)
            {
                $media->audience = trim((string) $a);
            }
            
            foreach($d->subject as $s)
            {
                $media->CVterm = trim((string) $s);
            }
            
            if($subject = @$d->additionalInformation->subject)
            {
                $media->CVterm = trim((string) $subject);
            }
            
            $reference_ids = array();
            foreach($d->reference as $r)
            {
                $attr = $r->attributes();
                $doi = @self::clean_string($attr['doi'], 0, 0);
                $uri = @self::clean_string($attr['url'], 0, 0);
                if(!$uri) $uri = @self::clean_string($attr['urn'], 0, 0);
                $reference = new \eol_schema\Reference();
                $reference->full_reference = self::clean_string((string) $r, 0, 0);
                $reference->uri = $uri;
                $reference->doi = $doi;
                $reference->identifier = md5("$reference->full_reference|$reference->uri|$reference->doi");
                
                if(!$reference->full_reference) continue;
                if(!isset($this->reference_ids[$reference->identifier]))
                {
                    $this->archive_builder->write_object_to_file($reference);
                    $this->reference_ids[$reference->identifier] = 1;
                }
                $reference_ids[] = $reference->identifier;
            }
            $media->referenceID = implode(";", $reference_ids);
            
            if(!isset($this->media_ids[$media->identifier]))
            {
                $this->archive_builder->write_object_to_file($media);
                $this->media_ids[$media->identifier] = 1;
            }
        }
    }
    
    private static function clean_string($str, $remove_whitespace = false, $decode = true)
    {
        $str = Functions::import_decode(trim($str), $remove_whitespace, $decode);
        $str = str_replace("&nbsp;", " ", $str);
        $str = str_replace("\t", " ", $str);
        return trim($str);
    }
}

?>
