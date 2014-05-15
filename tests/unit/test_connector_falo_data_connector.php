<?php
namespace php_active_record;
require_library('connectors/FaloDataConnector');
class test_connector_falo_data_connector extends SimpletestUnitBase {

  function testFaloDataConnector() {
    // NOTE: We are testing whether the real FALO file can be downloaded
    //       but we actually use a test file for the other tests. So we could
    //       remove or somehow stub the download test.
    $source_url = 'http://tiny.cc/FALO';
    $connector = new FaloDataConnector('falotest', $source_url);
    $reflector = new \ReflectionClass('\php_active_record\FaloDataConnector');

    $private_properties = array(
      'source_file_path' => null,
      'source_loaded' => null,
      'taxa' => null
    );
    foreach($private_properties as $name => &$property) {
      $property = $reflector->getProperty($name);
      $property->setAccessible(true);
    }

    $private_methods = array(
      'download_source_data_file' => null,
      'load_source_data_from_file' => null,
      'extract_data_from_loaded_source' => null,
      'assign_parent_identifiers' => null
    );
    foreach($private_methods as $name => &$method) {
      $method = $reflector->getMethod($name);
      $method->setAccessible(true);
    }

    // Test download of real source data file
    $private_methods['download_source_data_file']->invoke($connector);
    $downloaded_file = $private_properties['source_file_path']->getValue($connector);
    $this->assertTrue(file_exists($downloaded_file),
      'Source file should be downloaded.');

    // Swap real source file for smaller test file
    $private_properties['source_file_path']->setValue($connector, DOC_ROOT . 'tests/files/FALO.xlsx');
    $private_methods['load_source_data_from_file']->invoke($connector);
    $this->assertEqual(get_class($private_properties['source_loaded']->getValue($connector)),
      'PHPExcel', 'Source data should be loaded.');
    // Set file path back to real source file or test file will be deleted by destruct
    $private_properties['source_file_path']->setValue($connector, $downloaded_file);

    $private_methods['extract_data_from_loaded_source']->invoke($connector);
    $this->assertEqual(count($private_properties['taxa']->getValue($connector)), 65,
      'Taxa should be extracted from source data.');

    $private_methods['assign_parent_identifiers']->invoke($connector);
    $taxa = array();
    foreach($private_properties['taxa']->getValue($connector) as $taxon) {
      $taxa[$taxon['scientificName']] = $taxon;
    }
    $test_taxa = array(
      'Solanaceae' => array(
        'parent' => 'Solanales',
        'rank' => 'family',
        'citation' => 'Stevens, 2013',
        'taxonRemarks' => ''
      ),
      'Bilateria' => array(
        'parent' => 'Animalia',
        'rank' => 'subkingdom',
        'citation' => 'Ruggiero & Gordon (Eds.), 2013',
        'taxonRemarks' => ''
      ),
      'Theria' => array(
        'parent' => 'Mammalia',
        'rank' => 'subclass',
        'citation' => 'Ruggiero & Gordon (Eds.), 2013',
        'taxonRemarks' => ''
      ),
      'Felidae' => array(
        'parent' => 'Carnivora',
        'rank' => 'family',
        'citation' => 'Wilson & Reeder, 2011',
        'taxonRemarks' => ''
      ),
      'Angiospermae' => array(
        'parent' => 'Spermatophytina',
        'rank' => 'superclass',
        'citation' => 'Ruggiero & Gordon (Eds.), 2013',
        'taxonRemarks' => 'Superclass "Angiospermae" uncertain'
      )
    );
    foreach ($test_taxa as $name => $data) {
      $this->assertEqual(
        $taxa[$data['parent']]['taxonID'],
        $taxa[$name]['parentNameUsageID'],
        "{$name} should have parent {$data['parent']}.");
      $this->assertEqual(
        $taxa[$name]['taxonRank'],
        $data['rank'],
        "{$name} should have rank {$data['rank']}.");
      $this->assertEqual(
        $taxa[$name]['bibliographicCitation'],
        $data['citation'],
        "{$name} should have citation {$data['citation']}.");
      if (empty($data['taxonRemarks'])) {
        $this->assertEqual(
          $taxa[$name]['taxonRemarks'],
          $data['taxonRemarks'],
          "{$name} should not have any taxonRemarks.");
      }
      else {
        $this->assertEqual(
          $taxa[$name]['taxonRemarks'],
          $data['taxonRemarks'],
          "{$name} should have taxonRemarks {$data['taxonRemarks']}.");
      }
    }

    unset($connector);
    unset($reflector);
    $this->assertFalse(file_exists($downloaded_file),
      'Downloaded file should be removed on instance destruct.');
  }
}
?>
