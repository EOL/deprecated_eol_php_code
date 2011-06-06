<?php
namespace php_active_record;

require_library('connectors/FishWiseAPI');

class test_connector_fishwise_api extends SimpletestUnitBase
{
    function testfishwiseAPI()
    {            
        $taxon=Array
        (
            "SId" => "110",
            "GenusSpecies" => "Enneapterygius abeli",
            "AuthorSpecies" => "Klausewitz",
            "Family" => "Tripterygiidae",
            "DistributionT" => "Red Sea, Western Indian Ocean: East Africa",
            "OrderName" => "Perciformes",
            "Notes" => "MDH 4/95.  SMF visit.",
            "Habitat" => "demersal",
            "HabitatNotes" => "Yellow triplefin. Attains   25 mm.   Body bright yellow;  males with black head and nape.",
            "DepthRange" => "---",
            "DepthRangeShallow" => "0",
            "DepthRangeDeep" => "0",
            "LengthMax" => "25",
            "LengthMaxSuffix" => "mm",
            "LengthMaxType" => "TL",
            "Journal" => "---",
            "Citation" => "---",
            "TextPage" => "---"
        );        
        
        $images=Array
        (
            0 => Array
                (
                    "SId" => "110",
                    "PictureId" => "3",
                    "dbo_Picture_PictureNote" => "portrait",
                    "PictureType" => "tank photo",
                    "IsLegal" => "1",
                    "Location" => "South Africa, KwaZulu-Natal, Sodwana Bay",
                    "PicComments" => "---",
                    "IsAvailable" => "",
                    "LifeStage" => "female",
                    "CollectionName" => "Royal Ontario Museum",
                    "CollectionAcronym" => "ROM",
                    "PictureSource" => "Winterbottom, Rick",
                    "Surname" => "Winterbottom",
                    "Firstname" => "Richard",
                    "DisplayName" => "Rick Winterbottom",
                    "FileName" => "000110F000112W000003.jpg"
                )
        );
        
        $comnames=Array
        (
            0 => Array
                (
                    "CommonName" => "Abel's triplefin",
                    "Language" => "en"
                )
        
        );

        $synonyms=Array
        (
            0 => Array
                (
                    "SynGenusSpecies" => "Enneapterygius abeli",
                    "SynStatus" => "new combination"
                )
        );                          
                        
        $taxa = FishWiseAPI::get_fishwise_taxa($taxon,$images,$comnames,$synonyms);                         
        $this->assertTrue(is_array($taxa), 'Taxa should be an array');        
        $taxon = $taxa[0];
        $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');        
        $dataObject = $taxon->dataObjects[0];
        $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');                            
    }
}
?>