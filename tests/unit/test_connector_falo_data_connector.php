<?php
namespace php_active_record;
require_library('connectors/FaloDataConnector');
class test_connector_falo_data_connector extends SimpletestUnitBase {

  function testFaloDataConnector() {
    $connector = new FaloDataConnector('falotest');
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
    $this->assertEqual(count($private_properties['taxa']->getValue($connector)), 42,
      'Taxa should be extracted from source data.');

    $private_methods['assign_parent_identifiers']->invoke($connector);
    $taxa = array();
    foreach($private_properties['taxa']->getValue($connector) as $taxon) {
      $taxa[$taxon['scientificName']] = $taxon;
    }
    $parent_child = array(
      'Solanales' => 'Solanaceae',
      'Animalia' => 'Bilateria',
      'Mammalia' => 'Theria',
      'Carnivora' => 'Felidae'
    );
    foreach ($parent_child as $parent => $child) {
      $this->assertEqual(
        $taxa[$parent]['taxonID'],
        $taxa[$child]['parentNameUsageID'],
        "{$child} should have parent {$parent}.");
    }

    unset($connector);
    unset($reflector);
    $this->assertFalse(file_exists($downloaded_file),
      'Downloaded file should be removed on instance destruct.');
  }
}
?>
