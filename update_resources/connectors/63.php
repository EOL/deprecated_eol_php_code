#!/usr/local/bin/php
<?php
//#!/usr/local/bin/php
//connector for INOTAXA
//exit;

//define("ENVIRONMENT", "development");
define("MYSQL_DEBUG", true);
define("DEBUG", true);
include_once(dirname(__FILE__) . "/../../config/start.php");

$mysqli =& $GLOBALS['mysqli_connection'];

$resource = new Resource(63);

$subject_arr = array("Associations","Behaviour","Biology","Conservation","ConservationStatus","Cyclicity","Cytology","Description","DiagnosticDescription","Diseases","Dispersal","Distribution","Ecology","Evolution","GeneralDescription","Genetics","Growth","Habitat","Key","Legislation","LifeCycle","LifeExpectancy","Management","Migration","MolecularBiology","Morphology","Physiology","PopulationBiology","Procedures","Reproduction","RiskStatement","Size","TaxonBiology","Threats","Trends","TrophicStrategy","Uses");

$providers = array( 0 => array( "url" => dirname(__FILE__) . "/files/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml"      , "active" => 0),
                    1 => array( "url" => dirname(__FILE__) . "/files/Zootaxa_986_Hamilton_taXMLit_v4-03-UTF8.xml" , "active" => 0),
                    2 => array( "url" => "http://pandanus.eol.org/public/BCA_coleoptv4p3_taXMLit_v4-03-UTF8.xml"    , "active" => 1)                    
                  );

/*
TaxonomicPublication
    IndividualPublication
        Metadata
        PublicationFrontMatter
        PublicationTaxonomicMatter  Heading="none"
            TaxonTreatment
                TreatmentAuthors
                    TreatmentAuthorAtomised
                TreatmentDate
                TaxonHeading
                    RankDesignation
                    TaxonHeadingName
                        AlternateUsedInWork
                            TaxonName
                            GenusName
                TaxonCitationGroup
                Descriptions
                DistributionAndOrSpecimenCitations
                Discussions
        PublicationTaxonomicMatter  Heading="Supplement"
        PublicationBackMatter
        OtherNamesIndex
*/

$schema_taxa = array();

///////////////////////////////
$used_taxa = array();
/*  if this is outside the main loop then, data objects from a single species
    coming from 2 INOTAXA providers will be placed in 1 <taxon> element.

    if this is outside the loop then, there will be 2 <taxon> elements for 2 same species from 2 BCA providers.
*/
///////////////////////////////

foreach($providers as $provider)
{
    // start loop through
    if($provider["active"])
    {
        $url = $provider["url"];
        $xml = @simplexml_load_file($url);        
        if(!($xml = @simplexml_load_file($url)))
        {
            print "<br> <a href='$url'>$url</a> not accessible";
            continue;
        }

        $i=0;
        $sciname = array();//just for debugging

        $hierarchy = array();

        //start get rights holder
        $rightsHolder = trim(strip_tags($xml->IndividualPublication->PublicationFrontMatter->SeriesTitle->Title->asXML()));
        //end get rights holder

        foreach($xml->IndividualPublication as $main)
        {
            foreach($main->PublicationTaxonomicMatter as $ptm)
            {
                foreach($ptm->TaxonTreatment as $tt)
                {
                    $taxon_identifier = @$tt["TaxonID"];
                    $dwc_ScientificName = $tt->TaxonHeading->TaxonHeadingName->AlternateUsedInWork->TaxonName;
                    $rank = $tt->TaxonHeading->RankDesignation;
                    //print $dwc_ScientificName . "($taxon_identifier)($rank) | ";
                    $i++;
                    $sciname["$dwc_ScientificName"] = $dwc_ScientificName;

                    $taxon = str_replace(" ", "_", $dwc_ScientificName);
                    if(@$used_taxa[$taxon])
                    {
                        $taxon_parameters = $used_taxa[$taxon];
                    }
                    else
                    {
                        $taxon_parameters = array();

                        //start get hierarchy
                        foreach($tt->TaxonHierarchyAbove as $tha)
                        {
                            //print " | "; print $tha->RankDesignation . " ";
                            $ranks = array("kingdom", "phylum", "class", "order", "family", "genus");
                            $rank = $tha->RankDesignation;
                            if(in_array($rank, $ranks))
                            {
                                $taxon_parameters["$rank"] = $tha->TaxonHeadingName->AlternateUsedInWork->TaxonName;
                                $hierarchy["$rank"] = $tha->TaxonHeadingName->AlternateUsedInWork->TaxonName;
                            }
                            //print "[" . utf8_decode($tha->TaxonHeadingName->AlternateUsedInWork->TaxonName) . "] ";
                        }
                        if($hierarchy)//if with taxon hierarcy
                        {
                            $arr = array_keys($hierarchy);
                            foreach($arr as $rank)
                            {
                                $taxon_parameters["$rank"] = $hierarchy["$rank"];
                            }
                        }
                        //end get hierarchy

                        $main_citation="";
                        if(isset($xml->IndividualPublication->Metadata->FullBibliographicCitation))$main_citation = $xml->IndividualPublication->Metadata->FullBibliographicCitation;

                        $taxon_parameters["identifier"] = $taxon_identifier;
                        $taxon_parameters["scientificName"]= $dwc_ScientificName;
                        $taxon_parameters["source"] = "http://www.inotaxa.org/jsp/display.jsp?context=TaxonTreatment&taxmlitid=" . $taxon_identifier;

                        if($main_citation)
                        {
                            $taxon_parameters["references"] = array();
                            $referenceParameters = array();
                            $referenceParameters["fullReference"] = trim($main_citation);
                            $references = array();
                            $references[] = new SchemaReference($referenceParameters);
                            $taxon_parameters["references"] = $references;
                        }

                        $taxon_parameters["dataObjects"]= array();

                        $ref="";
                        if(isset($tt->TaxonCitationGroup->AcceptedOrValidTaxonName->AcceptedOrValidTaxonNameParagraph))
                        {
                            foreach($tt->TaxonCitationGroup->AcceptedOrValidTaxonName->AcceptedOrValidTaxonNameParagraph as $aovtp)
                            {
                                $ref = strip_tags($aovtp->asXML());
                            }
                        }

                        $used_taxa[$taxon] = $taxon_parameters;
                    }


                    if(isset($tt->Descriptions->SameLanguageDescription->SameLanguageDescriptionBody->SameLanguageDescriptionParagraph))
                    {
                        $arr = $tt->Descriptions->SameLanguageDescription->SameLanguageDescriptionBody->SameLanguageDescriptionParagraph;
                        $temp = process_dataobjects($arr,1,$ref);
                    }

                    if(isset($tt->Discussions->DiscussionBody->DiscussionParagraph))
                    {
                        $arr = $tt->Discussions->DiscussionBody->DiscussionParagraph;
                        $temp = process_dataobjects($arr,1,$ref);
                    }


                    //start image dataobject
                    if(isset($tt->TaxonHeading->TaxonHeadingParagraph->ref))
                    {
                        $arr = $tt->TaxonHeading->TaxonHeadingParagraph->ref;
                        $temp = process_dataobjects($arr,2,"");
                    }
                    //end image dataobject

                    //print"<br>";
                }
                //print"<hr>";
            }
        }


        /*
        print "<hr>
        i = $i <br>
        sciname = " . count($sciname);
        print "<hr>";
        print count(array_keys($sciname));
        print "<hr>";
        print count(array_keys($used_taxa));
        */

        // end loop through

        print "<hr>" . $provider["url"];

    }//if($provider["active"])

}//end main loop


foreach($used_taxa as $taxon_parameters)
{
    $schema_taxa[] = new SchemaTaxon($taxon_parameters);
}
////////////////////// ---
$new_resource_xml = SchemaDocument::get_taxon_xml($schema_taxa);
$old_resource_path = CONTENT_RESOURCE_LOCAL_PATH . $resource->id .".xml";
$OUT = fopen($old_resource_path, "w+");
fwrite($OUT, $new_resource_xml);
fclose($OUT);
////////////////////// ---

print "<hr> Done processing.";


function get_agents($agents_arr)
{
    $agents=array();
    foreach($agents_arr as $agent)
    {
        $agents[] = $agent->TreatmentAuthorAtomised;
    }
    return $agents;
}

function process_dataobjects($arr,$type,$ref)//$type 1 = text object; 2 = image object
{
    global $taxon_identifier;
    global $tt;
    global $taxon_parameters;
    global $used_taxa;
    global $taxon;
    global $subject_arr;

    foreach($arr as $item)
    {   
        if($type == 1)//text
        {
            $temp = $item->asXML();
            $description = trim(strip_tags($temp));
        }
        else //image
        {
            $image_id = trim($item["target"]);
            $ans = strval(stripos($image_id,"pfig"));            
            $description = $ans;
        }


        if($description != "")
        {
            if($type == 1)
            {
                $dc_identifier = $item["ElementID"];
                if($dc_identifier == "")$dc_identifier = "object_" . $taxon_identifier;
            }
            else $dc_identifier = $image_id;                                
                        

            if(isset($item["KindOfDiscussion"]))  $title = trim($item["KindOfDiscussion"]);
            else                                  $title = "Body Description";
            $title = ucfirst(strtolower($title));            
            if($type == 2)$title = "";

            if(in_array($title, $subject_arr))$subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#" . $title;
            else                              $subject = "http://rs.tdwg.org/ontology/voc/SPMInfoItems#GeneralDescription";
            if($type == 2)$subject = "";
            
            
            

            if($type == 1)
            {
                $dc_source = "http://www.inotaxa.org/jsp/display.jsp?context=TaxonTreatment&taxmlitid=" . $taxon_identifier;
                $mediaURL="";
                $dataType = "http://purl.org/dc/dcmitype/Text";
                $mimeType = "text/html";
                $agents = get_agents($tt->TreatmentAuthors);
            }
            else
            {             
                $dc_source = "http://www.inotaxa.org/jsp/display.jsp?context=Figure&taxmlitid=" . $image_id;
                $mediaURL = "http://www.nhm.ac.uk/hosted-sites/inotaxa/images/img/" . $image_id . ".jpg";
                $dataType = "http://purl.org/dc/dcmitype/StillImage";
                $mimeType = "image/jpeg";
                $agents = array();
                                
                $description = "";
                //start get caption
                if(isset($xml->IndividualPublication->PublicationTaxonomicMatter->PlateOrTable->ImageCaption->SubCaptionComponents))
                {
                    foreach($xml->IndividualPublication->PublicationTaxonomicMatter->PlateOrTable as $pot)
                    {
                        foreach($pot->ImageCaption as $ic)
                        {
                            foreach($ic->SubCaptionComponents as $scc)
                            {
                                if($scc["ImageOrTableID"] == "$image_id") $description = trim(strip_tags($scc->SubCaptionText->asXML())); //print("<hr>11<hr>image id = $image_id<hr>" . $description);
                            }
                        }
                    }
                }
                //end get caption                
            }
                        
            $dcterms_created = "";
            $dcterms_modified = "";            
            $license = "http://creativecommons.org/licenses/by/3.0/";                                           
            
            $data_object_parameters = get_data_object($dc_identifier, $dcterms_created, $dcterms_modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents);
            $taxon_parameters["dataObjects"][] = new SchemaDataObject($data_object_parameters);
            $used_taxa[$taxon] = $taxon_parameters;                    
            
        }
    }    
}//function process_dataobjects($arr)

function get_data_object($id, $created, $modified, $license, $description, $subject, $title, $dc_source, $mediaURL, $dataType, $mimeType, $ref, $agents_arr)
{
    global $rightsHolder;

    $dataObjectParameters = array();
    $dataObjectParameters["title"] = $title;
    $dataObjectParameters["rightsHolder"] = $rightsHolder;
    if($subject)
    {
        $dataObjectParameters["subjects"] = array();
        $subjectParameters = array();
        $subjectParameters["label"] = $subject;
        $dataObjectParameters["subjects"][] = new SchemaSubject($subjectParameters);
    }

    $description = str_replace(array("\n", "\r", "\t", "\o", "\xOB"), '', $description);
    $dataObjectParameters["description"] = trim($description);

    $dataObjectParameters["identifier"] = $id;
    $dataObjectParameters["created"] = $created;
    $dataObjectParameters["modified"] = $modified;
    $dataObjectParameters["rightsHolder"] = $rightsHolder;
    $dataObjectParameters["dataType"] = $dataType;
    $dataObjectParameters["mimeType"] = $mimeType;
    $dataObjectParameters["language"] = "en";
    $dataObjectParameters["license"] = $license;
    $dataObjectParameters["mediaURL"] = $mediaURL;
    $dataObjectParameters["source"] = $dc_source;

    if($agents_arr)
    {
        $agentParameters = array();
        foreach($agents_arr as $agent)
        {
            $agentParameters["role"] = "author";
            $agentParameters["fullName"] = $agent;
            $agents[] = new SchemaAgent($agentParameters);
        }
        $dataObjectParameters["agents"] = $agents;
    }

    ///////////////////////////////////
    $dataObjectParameters["audiences"] = array();
    $audienceParameters = array();

    $audienceParameters["label"] = "Expert users";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);

    $audienceParameters["label"] = "General public";
    $dataObjectParameters["audiences"][] = new SchemaAudience($audienceParameters);
    ///////////////////////////////////

    ///////////////////////////////////
    ///*working
    $dataObjectParameters["references"] = array();

    $referenceParameters = array();
    $referenceParameters["fullReference"] = trim($ref);
    $references[] = new SchemaReference($referenceParameters);

    /*not working...
    $referenceParam["referenceIdentifiers"][] = array("label" => "label" , "value" => "value");
    */

    $dataObjectParameters["references"] = $references;
    ///////////////////////////////////

    return $dataObjectParameters;
}


?>