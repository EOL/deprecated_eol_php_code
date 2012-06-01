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
        $reader->open($this->path_to_xml_file);
        
        $i = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "taxon")
            {
                $taxon_xml = $reader->readOuterXML();
                $t = simplexml_load_string($taxon_xml, null, LIBXML_NOCDATA);
                $this->add_taxon_to_archive($t);
                
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
        $taxon->taxonID = Functions::import_decode($t_dc->identifier);
        $taxon->source = Functions::import_decode($t_dc->source);
        $taxon->kingdom = Functions::import_decode($t_dwc->Kingdom);
        $taxon->phylum = Functions::import_decode($t_dwc->Phylum);
        $taxon->class = Functions::import_decode($t_dwc->Class);
        $taxon->order = Functions::import_decode($t_dwc->Order);
        $taxon->family = Functions::import_decode($t_dwc->Family);
        $taxon->genus = Functions::import_decode($t_dwc->Genus);
        $taxon->scientificName = Functions::import_decode($t_dwc->ScientificName);
        $taxon->taxonRank = Functions::import_decode($t->rank);
        
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
        }
        if(!$taxon->scientificName) return;
        
        if(!$taxon->taxonID)
        {
            $taxon->taxonID = md5("$taxon->kingdom|$taxon->phylum|$taxon->class|$taxon->order|$taxon->family|$taxon->genus|$taxon->scientificName");
        }
        
        foreach($t->commonName as $c)
        {
            $xml_attr = $c->attributes("http://www.w3.org/XML/1998/namespace");
            $vernacular = new \eol_schema\VernacularName();
            $vernacular->taxonID = $taxon->taxonID;
            $vernacular->vernacularName = Functions::import_decode((string) $c);
            $vernacular->language = @Functions::import_decode($xml_attr["lang"]);
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
            $synonym->scientificName = Functions::import_decode((string) $s);
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
            $doi = @Functions::import_decode($attr['doi'], 0, 0);
            $uri = @Functions::import_decode($attr['url'], 0, 0);
            if(!$uri) $uri = @Functions::import_decode($attr['urn'], 0, 0);
            $reference = new \eol_schema\Reference();
            $reference->full_reference = Functions::import_decode((string) $r, 0, 0);
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
            $media->identifier = Functions::import_decode($d_dc->identifier);
            $media->taxonID = $taxon->taxonID;
            $media->type = Functions::import_decode($d->dataType);
            $media->format = Functions::import_decode($d->mimeType);
            $media->title = Functions::import_decode($d_dc->title, 0, 0);
            $media->description = Functions::import_decode($d_dc->description, 0, 0);
            $media->accessURI = Functions::import_decode($d->mediaURL);
            $media->thumbnailURL = Functions::import_decode($d->thumbnailURL);
            $media->furtherInformationURL = Functions::import_decode($d_dc->source);
            $media->CreateDate = Functions::import_decode($d_dcterms->created);
            $media->modified = Functions::import_decode($d_dcterms->modified);
            $media->language = Functions::import_decode($d_dc->language);
            $media->UsageTerms = Functions::import_decode($d->license);
            $media->rights = Functions::import_decode($d_dc->rights, 0, 0);
            $media->Owner = Functions::import_decode($d_dcterms->rightsHolder, 0, 0);
            $media->bibliographicCitation = Functions::import_decode($d_dcterms->bibliographicCitation, 0, 0);
            $media->LocationCreated = Functions::import_decode($d->location, 0, 0);
            
            if($r = (string) @$d->additionalInformation->rating)
            {
                if((is_numeric($r)) && $r > 0 && $r <= 5) $media->Rating = $r;
            }
            
            if($subtype = @$d->additionalInformation->subtype)
            {
                $media->subtype = Functions::import_decode($subtype);
            }
            
            if(!$media->language)
            {
                $xml_attr = $d_dc->description->attributes("http://www.w3.org/XML/1998/namespace");
                $media->language = @Functions::import_decode($xml_attr["lang"]);
            }
            
            $data_object->latitude = 0;
            $data_object->longitude = 0;
            $data_object->altitude = 0;
            foreach($d_geo->Point as $p)
            {
                $p_geo = $p->children("http://www.w3.org/2003/01/geo/wgs84_pos#");
                $media->lat = Functions::import_decode($p_geo->lat);
                $media->long = Functions::import_decode($p_geo->long);
                $media->alt = Functions::import_decode($p_geo->alt);
            }
            
            $agent_ids = array();
            foreach($d->agent as $a)
            {
                $attr = $a->attributes();
                $agent = new \eol_schema\Agent();
                $agent->term_name = Functions::import_decode((string) $a, 0, 0);
                $agent->term_homepage = @Functions::import_decode($attr["homepage"]);
                $agent->term_logo = @Functions::import_decode($attr["logoURL"]);
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
                $doi = @Functions::import_decode($attr['doi'], 0, 0);
                $uri = @Functions::import_decode($attr['url'], 0, 0);
                if(!$uri) $uri = @Functions::import_decode($attr['urn'], 0, 0);
                $reference = new \eol_schema\Reference();
                $reference->full_reference = Functions::import_decode((string) $r, 0, 0);
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
}

?>
