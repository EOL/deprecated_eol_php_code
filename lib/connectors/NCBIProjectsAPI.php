<?php
namespace php_active_record;
/* connector: [ncbi]
Partner provides a big XML file. Connector parses it and generates a DWC-A image resource.
*/
class NCBIProjectsAPI
{
    function __construct($folder = false)
    {
        $this->max_images_per_taxon = 10;
        $this->data_dump_url = "ftp://ftp.ncbi.nlm.nih.gov/bioproject/bioproject.xml";
        $this->data_dump_url = "http://localhost/~eolit/xml_parser/bioproject.xml"; // debug

        $this->sourceURL = "http://www.boldsystems.org/index.php/Taxbrowser_Taxonpage?taxid=";
        $this->taxa = array();
        $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . '/' . $folder . '_working/';
        $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
        $this->resource_reference_ids = array();
        $this->resource_agent_ids = array();
        $this->vernacular_name_ids = array();
        $this->taxon_ids = array();
        $this->do_ids = array();
        
        $this->stats = array();
        
    }

    function get_all_taxa($data_dump_url = false)
    {
        /* working but commented during development...
        if(!$data_dump_url) $data_dump_url = $this->data_dump_url;
        $path = self::download_and_extract_remote_file($data_dump_url);
        echo "\n xml file: [$path] \n";
        */
        
        $path = "http://localhost/~eolit/xml_parser/bioproject.xml"; // debug
        
        $reader = new \XMLReader();
        $reader->open($path);
        $i = 0;
        while(@$reader->read())
        {
            if($reader->nodeType == \XMLReader::ELEMENT && $reader->name == "Package")
            {
                $string = $reader->readOuterXML();
                if($xml = simplexml_load_string($string))
                {
                    $i++;
                    self::parse_record_element($xml);
                    // echo("\n $i. ");
                    // print_r($xml);
                    if($i > 20) break;
                }

                // // debug - to process by batch
                // $i++;
                // if($i > 10)
                // {
                //     // self::parse_record_element($xml);
                //     print_r($xml);
                //     if($i > 20) break;
                // }

            }
        }
        
        
        $supergroups = array_keys($this->stats["supergroup"]);
        print "\n";
        print "\n unique taxon_ids: " . count($this->taxon_ids);
        print "\n";
        print_r($supergroups);
        foreach($supergroups as $supergroup)
        {
            print "\n[$supergroup] " . count(array_keys($this->stats["supergroup"][$supergroup]));
            //print_r(array_keys($this->stats["supergroup"][$supergroup]));
        }
        
        print "\n\n";
        $this->create_archive();
        unlink($path);
    }


    private function parse_record_element($xml)
    {
        $reference_ids = array();
        $ref_ids = array();
        $agent_ids = array();
        $rec = $this->create_instances_from_taxon_object($xml, $reference_ids);
        // if($rec) self::get_images($rec, $ref_ids, $agent_ids);
    }

    private function get_object_agents($rec)
    {
        /*
        */
        $agent_ids = array();
        if(@$rec->photographer)
        {
            $agent = (string) trim($rec->photographer);
            if($agent != "")
            {
                $r = new \eol_schema\Agent();
                $r->term_name = $agent;
                $r->identifier = md5("$agent|photographer");
                $r->agentRole = "photographer";
                $r->term_homepage = "";
                $agent_ids[] = $r->identifier;
                if(!in_array($r->identifier, $this->resource_agent_ids))
                {
                   $this->resource_agent_ids[] = $r->identifier;
                   $this->archive_builder->write_object_to_file($r);
                }
            }
        }
        return $agent_ids;
    }

    private function get_images($rec, $reference_ids = null, $agent_ids = null)
    {
        /*
        <specimen_multimedia>
          <media>
            <mediaID>1203947</mediaID>
            <image_link>http://www.boldsystems.org/pics/_w300/BLPDT/10-SRNP-108212_DHJ653087+1309969002.jpg</image_link>
            <photographer>Daniel H. Janzen</photographer>
            <licensing>
              <license>CreativeCommons - Attribution Non-Commercial Share-Alike</license>
              <licenseholder>Daniel H. Janzen</licenseholder>
              <licenseholder_institution>Guanacaste Dry Forest Conservation Fund</licenseholder_institution>
              <year>2010</year>
            </licensing>
          </media>
        </specimen_multimedia>
        */
        if(!@$rec->specimen_multimedia) return;
        $count = 0;
        foreach(@$rec->specimen_multimedia->media as $media)
        {
            if(trim(@$media->image_link) != "" && !is_numeric(stripos($media->licensing->license, "No Derivatives")))
            {
                $SampleID = trim($rec->specimen_identifiers->sampleid);
                $ProcessID = trim($rec->processid);
                $Orientation = trim($media->caption);

                // start checking if image already exists from first/original images resource
                $old_id = $SampleID . "_" . $ProcessID . "_" . $Orientation;
                if(in_array($old_id, $this->old_bolds_image_ids)) 
                {
                    echo "\n [$old_id] Found an old ID, will ignore \n";
                    $this->old_bolds_image_ids_count++;
                    continue;
                }
                // end -

                $taxon_id = trim($rec->taxon_id);
                if(@$this->info[$taxon_id]) 
                {
                    if($this->info[$taxon_id] == $this->max_images_per_taxon)
                    {
                        echo(" --- max $this->max_images_per_taxon images reached for [$taxon_id][$rec->sciname] -- ");
                        break;
                    }
                    $this->info[$taxon_id]++;
                }
                else $this->info[$taxon_id] = 1;

                $description = "";
                if(@$rec->specimen_identifiers->sampleid) $description .= "Sample ID = " . $SampleID . "<br>";
                if(@$rec->processid)                      $description .= "Process ID = " . $ProcessID . "<br>";
                if(@$media->caption)                      $description .= "Caption = " . $Orientation . "<br>";

                $rights = "";
                if(@$media->licensing->year) $rights = "Copyright ". $media->licensing->year;

                $rightsHolder = "";
                if(@$media->licensing->licenseholder) $rightsHolder = $media->licensing->licenseholder;
                if(@$media->licensing->licenseholder_institution) $rightsHolder .= ". " . $media->licensing->licenseholder_institution . ".";

                $agent_ids = self::get_object_agents($media);

                $mediaID = trim($media->mediaID);
                $license = self::get_license($media->licensing->license);
                if(trim($rec->taxon_id) != "" && $mediaID != "" && $license && Functions::get_mimetype($media->image_link) != "")
                {
                    if(in_array($mediaID, $this->do_ids))
                    {
                        echo("\n it should not pass here, just in case... \n");
                        continue;
                    }
                    else $this->do_ids[] = $mediaID;

                    $mr = new \eol_schema\MediaResource();
                    if($reference_ids) $mr->referenceID = implode("; ", $reference_ids);
                    if($agent_ids) $mr->agentID = implode("; ", $agent_ids);
                    $mr->taxonID                = (string) $rec->taxon_id;
                    $mr->identifier             = (string) $mediaID;
                    $mr->type                   = "http://purl.org/dc/dcmitype/StillImage";
                    $mr->language               = 'en';
                    $mr->format                 = (string) Functions::get_mimetype($media->image_link);
                    $mr->furtherInformationURL  = (string) $this->sourceURL . $rec->taxon_id;
                    $mr->description            = (string) $description;
                    $mr->CVterm                 = ""; // subject
                    $mr->title                  = "";
                    $mr->creator                = "";
                    $mr->CreateDate             = "";
                    $mr->modified               = "";
                    $mr->LocationCreated        = "";
                    $mr->UsageTerms             = (string) $license;
                    $mr->Owner                  = (string) $rightsHolder;
                    $mr->publisher              = "";
                    $mr->audience               = "";
                    $mr->bibliographicCitation  = "";
                    $mr->rights                 = (string) $rights;
                    $mr->accessURI              = (string) $media->image_link;
                    $mr->Rating                 = 2;
                    $this->archive_builder->write_object_to_file($mr);
                }
            }
        }
    }

    function create_instances_from_taxon_object($xml, $reference_ids)
    {
        $info = self::get_sciname($xml);
        $sciname  = $info["taxon_name"];
        $taxon_id = $info["taxon_id"];
        $rank     = $info["rank"];
        $ancestry = $info["ancestry"];

        $rec->taxon_id = $taxon_id;
        $rec->sciname = $sciname;

        if(trim($taxon_id) == "" || trim($sciname) == "") return false;
        if(in_array($taxon_id, $this->taxon_ids)) 
        {
            print "\n alert: it has duplicate taxon_ids [$taxon_id]";
            return $rec;
        }
        else $this->taxon_ids[] = $taxon_id;

        $taxon = new \eol_schema\Taxon();
        if($reference_ids) $taxon->referenceID = implode("; ", $reference_ids);

        $taxon->taxonID                     = (string) $taxon_id;
        $taxon->taxonRank                   = (string) $rank;
        $taxon->scientificName              = (string) $sciname;
        $taxon->scientificNameAuthorship    = "";
        $taxon->vernacularName              = "";
        $taxon->kingdom                     = (string) @$ancestry->kingdom->taxon->name;
        $taxon->phylum                      = (string) @$ancestry->phylum->taxon->name;
        $taxon->class                       = (string) @$ancestry->class->taxon->name;
        $taxon->order                       = (string) @$ancestry->order->taxon->name;
        $taxon->family                      = (string) @$ancestry->family->taxon->name;
        $taxon->genus                       = (string) @$ancestry->genus->taxon->name;
        $taxon->furtherInformationURL       = "";
        $taxon->specificEpithet             = "";
        $taxon->taxonomicStatus             = "";
        $taxon->nomenclaturalCode           = "";
        $taxon->nomenclaturalStatus         = "";
        $taxon->acceptedNameUsage           = "";
        $taxon->acceptedNameUsageID         = "";
        $taxon->parentNameUsageID           = "";
        $taxon->namePublishedIn             = "";
        $taxon->taxonRemarks                = (string) @$rec->taxonomy->identification_provided_by ? "Taxonomy identification provided by " . $rec->taxonomy->identification_provided_by : '';
        $taxon->infraspecificEpithet        = "";
        $this->taxa[$taxon_id] = $taxon;
        return $rec;
    }

    private function get_license($license)
    {
        switch($license)
        {
            case "CreativeCommons - Attribution Non-Commercial Share-Alike" : return "http://creativecommons.org/licenses/by-nc-sa/3.0/"; break;
            case "CreativeCommons - Attribution"                            : return "http://creativecommons.org/licenses/by/3.0/"; break;
            case "CreativeCommons - Attribution Non-Commercial"             : return "http://creativecommons.org/licenses/by-nc/3.0/"; break;
            case "CreativeCommons - Attribution Share-Alike"                : return "http://creativecommons.org/licenses/by-sa/3.0/"; break;
            default:
            {
                echo("Invalid license: [$license]");
                return false;
                break;
            }
        }
    }

    private function get_sciname($xml)
    {

        /*
        [Project] => SimpleXMLElement Object
              (
                  [Project] => SimpleXMLElement Object
                      (
                          [ProjectID] => SimpleXMLElement Object
                              (
                                  [ArchiveID] => SimpleXMLElement Object
                                      (
                                          [@attributes] => Array
                                              (
                                                  [accession] => PRJNA9
                                                  [archive] => NCBI
                                                  [id] => 9
                                              )
                                      )
                              )
                          [ProjectDescr] => SimpleXMLElement Object
                              (
                                  [Name] => Francisella tularensis subsp. tularensis SCHU S4
                                  [Title] => Aquatic bacterium that cause tularemia
                                  [Description] => <P>
 
 

 
                         [ProjectType] => SimpleXMLElement Object
                                                 (
                                                     [ProjectTypeSubmission] => SimpleXMLElement Object
                                                         (
                                                             [Target] => SimpleXMLElement Object
                                                                 (
                                                                     [@attributes] => Array
                                                                         (
                                                                             [capture] => eWhole
                                                                             [material] => eGenome
                                                                             [sample_scope] => eMonoisolate
                                                                         )

                                                                     [Organism] => SimpleXMLElement Object
                                                                         (
                                                                             [@attributes] => Array
                                                                                 (
                                                                                     [species] => 263
                                                                                     [taxID] => 177416
                                                                                 )

                                                                             [OrganismName] => Francisella tularensis subsp. tularensis SCHU S4
                                                                             [Strain] => Schu S4
                                                                             [Supergroup] => eBacteria
                                                                             [BiologicalProperties] => SimpleXMLElement Object
                                                                                 (
                                                                                     [Morphology] => SimpleXMLElement Object
                                                                                         (
                                                                                             [Gram] => eNegative
                                                                                             [Shape] => eBacilli
                                                                                             [Motility] => eNo
                                                                                         )

                                                                                     [Environment] => SimpleXMLElement Object
                                                                                         (
                                                                                             [OxygenReq] => eAerobic
                                                                                             [Habitat] => eAquatic
                                                                                         )
        */
        
        foreach($xml->Project as $p)
        {
            foreach($p as $Proj)
            {
                // print "\n" . $Proj->ProjectID->ArchiveID["id"];
                // print " - " . $Proj->ProjectDescr->Name;
                
                
                
                
                if(isset($Proj->ProjectType->ProjectTypeSubmission->Target->Organism->OrganismName))
                {
                    // print "\n target: " . $Proj->ProjectType->ProjectTypeSubmission->Target->Organism->OrganismName;
                    // print "[" . $Proj->ProjectType->ProjectTypeSubmission->Target->Organism["taxID"] . "]";

                    $taxon_name = (string) $Proj->ProjectType->ProjectTypeSubmission->Target->Organism->OrganismName;
                    $supergroup = (string) $Proj->ProjectType->ProjectTypeSubmission->Target->Organism->Supergroup;
                    $taxon_id = (string) $Proj->ProjectType->ProjectTypeSubmission->Target->Organism["taxID"];
                    $rank = "";
                    $name = array();
                }
                elseif(isset($Proj->ProjectType->ProjectTypeTopSingleOrganism->Organism->OrganismName))
                {
                    // print "\n single: " . $Proj->ProjectType->ProjectTypeTopSingleOrganism->Organism->OrganismName;
                    // print "[" . $Proj->ProjectType->ProjectTypeTopSingleOrganism->Organism["taxID"] . "]";

                    $taxon_name = (string) $Proj->ProjectType->ProjectTypeTopSingleOrganism->Organism->OrganismName;
                    $supergroup = (string) $Proj->ProjectType->ProjectTypeTopSingleOrganism->Organism->Supergroup;
                    $taxon_id = (string) $Proj->ProjectType->ProjectTypeTopSingleOrganism->Organism["taxID"];
                    $rank = "";
                    $name = array();
                }
                else
                {
                    $taxon_name = "";
                    $supergroup = "";
                    $taxon_id = "";
                    $rank = "";
                    $name = array();
                }
                
            }
            
        }// loop

        if(@$taxon_name) 
        {
            echo "\n ----------------------------------------------------------------------";
            echo "\n Taxon: $taxon_name";
            echo "\n ID: $taxon_id";
            
            echo "\n Strain: " . $Proj->ProjectType->ProjectTypeSubmission->Target->Organism->Strain;
            echo "\n Supergroup: " . $Proj->ProjectType->ProjectTypeSubmission->Target->Organism->Supergroup;

            echo "\n";
            echo "\n Morphology [Gram]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->BiologicalProperties->Morphology->Gram;
            echo "\n Morphology [Shape]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->BiologicalProperties->Morphology->Shape;
            
          
            echo "\n";
            echo "\n Environment [OxygenReq]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->BiologicalProperties->Environment->OxygenReq;
            echo "\n Environment [OptimumTemperature]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->BiologicalProperties->Environment->OptimumTemperature;
            echo "\n Environment [TemperatureRange]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->BiologicalProperties->Environment->TemperatureRange;
            echo "\n Environment [Habitat]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->BiologicalProperties->Environment->Habitat;

            echo "\n";
            echo "\n Phenotype [Disease]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->BiologicalProperties->Phenotype->Disease;

            echo "\n";
            echo "\n RepliconSet [order]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->RepliconSet->Replicon["order"];
            echo "\n RepliconSet [Type]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->RepliconSet->Replicon->Type;
            echo "\n RepliconSet [Name]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->RepliconSet->Replicon->Name;
            echo "\n RepliconSet [Size]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->RepliconSet->Replicon->Size;
            echo "\n RepliconSet [Count]: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->RepliconSet->Count;

            echo "\n";
            echo "\n GenomeSize: " . @$Proj->ProjectType->ProjectTypeSubmission->Target->Organism->GenomeSize;



            echo "\n";
            echo "\n Project Name: " . $Proj->ProjectDescr->Name;
            echo "\n Project Title: " . $Proj->ProjectDescr->Title;
            echo "\n - accession: " . $Proj->ProjectID->ArchiveID["accession"];
            echo "\n - archive: " . $Proj->ProjectID->ArchiveID["archive"];
            echo "\n - id: " . $Proj->ProjectID->ArchiveID["id"];
            echo "\n ProjectReleaseDate: " . $Proj->ProjectDescr->ProjectReleaseDate;
            

            echo "\n";
            echo "\n Target capture: " . @$Proj->ProjectType->ProjectTypeSubmission->Target["capture"];
            echo "\n Target material: " . @$Proj->ProjectType->ProjectTypeSubmission->Target["material"];
            echo "\n Target sample_scope: " . @$Proj->ProjectType->ProjectTypeSubmission->Target["sample_scope"];
            
            
            // echo "\n Project Desc.: " . str_ireplace(array("\n", "\t"), "", $Proj->ProjectDescr->Description);
            echo "\n";
            echo "\n External Link:";
            foreach($Proj->ProjectDescr->ExternalLink as $link)
            {
                echo "\n - [$link[label]] " . $link->URL;
            }

            echo "\n";
            echo "\n Publication: " . $Proj->ProjectDescr->Publication->Reference;
            echo "\n - ID: " . @$Proj->ProjectDescr->Publication["id"];
            echo "\n - Date: " . @$Proj->ProjectDescr->Publication['date'];
            echo "\n - DbType: " . @$Proj->ProjectDescr->Publication->DbType;
            
            
            echo "\n";
            echo "\n Submission method: " . @$Proj->ProjectType->ProjectTypeSubmission->Method["method_type"];
            echo "\n Submission objectives: " . @$Proj->ProjectType->ProjectTypeSubmission->Objectives->Data["data_type"];
            
            
            
            
            
        }
    

        echo "\n";
        echo "\n Submission submitted: " . @$xml->Submission->Submission["submitted"];
        echo "\n Organization name: " . @$xml->Submission->Submission->Description->Organization->Name;
        echo "\n - role: " . @$xml->Submission->Submission->Description->Organization["role"];
        echo "\n - type: " . @$xml->Submission->Submission->Description->Organization["type"];
        echo "\n - url: " . @$xml->Submission->Submission->Description->Organization["url"];

        echo "\n Access: " . @$xml->Submission->Submission->Description->Access;
        
    
        if(@$taxon_name) 
        {
            $this->stats["supergroup"][$supergroup][$taxon_id] = 1;
            return array("taxon_name" => $taxon_name, "taxon_id" => $taxon_id, "rank" => $rank, "ancestry" => $name);
        }
        else return false;

    
    
    }

    function create_archive()
    {
        foreach($this->taxa as $t)
        {
            $this->archive_builder->write_object_to_file($t);
        }
        $this->archive_builder->finalize(true);
    }

    function download_and_extract_remote_file($file = false)
    {
        if(!$file) $file = $this->data_dump_url; // used when this function is called elsewhere
        $temp_path = Functions::save_remote_file_to_local($file, DOWNLOAD_WAIT_TIME, 999999, 5, "xml");
        echo "\n [$temp_path] \n";
        // shell_exec("gzip -d " . $temp_path);
        // return str_ireplace(".xml.gz", ".xml", $temp_path);
        return $temp_path;
    }

}
?>