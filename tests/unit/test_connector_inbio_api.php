<?php
namespace php_active_record;

require_library('connectors/INBioAPI');

class test_connector_inbio_api extends SimpletestUnitBase
{
    function testINBioAPI()
    {
      $GLOBALS['fields'] = Array
      (
          "0" => Array("term" => "http://rs.tdwg.org/dwc/terms/taxonID", "default" => ""),
          "1" => Array("term" => "http://www.pliniancore.org/plic/pcfcore/scientificDescription", "type" => "", "default" => "")
      );
      $taxon = 
      Array
      (
        "http://rs.tdwg.org/dwc/terms/taxonID" => 7240,
        "http://rs.tdwg.org/dwc/terms/family" => "Annonaceae",
        "http://rs.tdwg.org/dwc/terms/genus" => "Annona",
        "http://rs.tdwg.org/dwc/terms/kingdom" => "Plantae",
        "http://rs.tdwg.org/dwc/terms/order" => "Magnoliales",
        "http://rs.tdwg.org/dwc/terms/scientificNameAuthorship" => "L.",
        "http://rs.tdwg.org/dwc/terms/class" => "Magnoliopsida (Dic.)",
        "http://rs.tdwg.org/dwc/terms/scientificName" => "Annona squamosa",
        "http://purl.org/dc/terms/modified" => "2008-08-02 17:09:06.0",
        "http://rs.tdwg.org/dwc/terms/acceptedNameUsage" => "",
        "http://rs.tdwg.org/dwc/terms/phylum" => "Magnoliophyta",
        "http://purl.org/dc/terms/language" => "Español",
        "http://purl.org/dc/terms/rightsHolder" => "INBio, Costa Rica",
        "http://purl.org/dc/terms/license" => "CC-Attribution-NonCommercial-ShareAlike",
        "id" => 7240,
        "image" => Array
            (
                "url" => Array
                    (
                        "0" => "http://multimedia.inbio.ac.cr/m3sINBio/getImage?size=big&id=43681",
                        "1" => "http://multimedia.inbio.ac.cr/m3sINBio/getImage?size=big&id=36150",
                        "2" => "http://multimedia.inbio.ac.cr/m3sINBio/getImage?size=big&id=43682"
                    ),

                "caption" => Array
                    (
                        "0" => "Cuerpos fructíferos de <i>Lactarius costaricensis</i> Singer, donde se nota la secreción de las lamelas .<br>Foto: Ronald Rodríguez.",
                        "1" => "Cuerpo fructífero de <i>Lactarius costaricensis</i> Singer, donde se nota la secreción emanada por las lamelas al cortarlas.<br>Foto: Ronald Rodríguez",
                        "2" => "Esporas de <i>Lactarius costaricensis</i> Singer, vistas al microscopio con objetivo 100X.<br>Foto: Eduardo Alvarado."
                    ),

                "license" => Array
                    (
                        "0" => "CC-Attribution-NonCommercial-ShareAlike",
                        "1" => "CC-Attribution-NonCommercial-ShareAlike",
                        "2" => "CC-Attribution-NonCommercial-ShareAlike"
                    ),

                "publisher" => Array
                    (
                        "0" => "INBio, Costa Rica",
                        "1" => "INBio, Costa Rica",
                        "2" => "INBio, Costa Rica"
                    ),

                "creator" => Array
                    (
                        "0" => "Isaac López",
                        "1" => "Ronald Rodríguez",
                        "2" => "Eduardo Alvarado"
                    ),

                "created" => Array
                    (
                        "0" => "2008-08-02 17:10:05.0",
                        "1" => "2008-08-02 17:10:05.0",
                        "2" => "2008-08-02 17:10:05.0"
                    ),

                "rightsHolder" => Array
                    (
                        "0" => "Instituto Nacional de Biodiversidad - INBio, Costa Rica.", 
                        "1" => "Instituto Nacional de Biodiversidad - INBio, Costa Rica.",
                        "2" => "Instituto Nacional de Biodiversidad - INBio, Costa Rica."
                    ),
            ),

        "reference" => array(),
        "vernacular_name" => "",
        "media" => Array
            (
                "http://rs.tdwg.org/dwc/terms/taxonID" => 7240,
                "http://www.pliniancore.org/plic/pcfcore/scientificDescription" => "El píleo o sombrero es de 3,0 a 5,5 cm de diámetro, de forma depresada hacia el centro y arqueada hacia el margen, la superficie es seca, de textura velutinosa (similar al fieltro) y rugulosa a la vez, el color es beige pardo claro, el contexto o parte interna es de color blanco, de 5 a 8 mm de grosor.<p> El himenio o parte fértil está formado por lamelas próximas entre sí de color crema, éstas se extienden hasta el pie curvándose un poco por lo que se dice que son subdecurrentes y se notan en ellas secreciones lechosas, que se tornan rosadas.<p> El estípite o pie es de 2 a 6 cm de longitud y de 0,5 a 1,2 cm de ancho; la superficie presenta finas fibrillas de color similar a la superficie del píleo, con tonos blancos hacia el himenio y la base, la forma es igual (uniforme), el contexto o relleno es sólido y de color blanco.",
                "http://purl.org/dc/terms/created" => "10/25/2006"
            )
        );

        /*
        "reference" => "Halling, R. & Mueller G. 2005. Common Mushrooms of the Talamanca Mountains. The New York Botanical Garden Press, N.Y. U.S.A., p. 117. </p>CABI Bioscience, Egham, UK. 2002. Disponible en línea en:<br>",
        */
 
        $arr = INBioAPI::get_inbio_taxa($taxon, array());
        $page_taxa = $arr[0];
        $this->assertTrue(is_array($page_taxa), 'Taxa should be an array');
        foreach($page_taxa as $taxon)
        {
          $this->assertIsA($taxon, 'SchemaTaxon', 'Response should be an array of SchemaTaxon');
          $dataObject = $taxon->dataObjects[0];
          $this->assertIsA($dataObject, 'SchemaDataObject', 'Taxon should have a data object');
        }
    }
}
?>