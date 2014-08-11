<?php
namespace php_active_record;

class FaloDataConnector {

  private static $ranks = array(
    'superkingdom', 'kingdom', 'subkingdom', 'infrakingdom',
    'superphylum', 'phylum', 'subphylum', 'infraphylum', 'parvphylum',
    'superclass', 'class', 'subclass', 'infraclass',
    'superorder', 'order', 'family');
  private static $columns_to_extract = array(
    'spk', 'k','sbk','ik',
    'spp','p','sbp','ip', 'pvp',
    'spc','c','sbc','ic',
    'spo','o','family',
    'reference', 'uuid');
  private $source_url;
  private $source_file_path;
  private $source_loaded;
  private $taxa;
  private $path_to_archive_directory;
  private $archive_builder;

  public function __construct($resource_id, $source_url) {
    $this->resource_id = $resource_id;
    $this->path_to_archive_directory = CONTENT_RESOURCE_LOCAL_PATH . $this->resource_id . '/';
    $this->source_url = $source_url;
  }

  public function __destruct() {
    try {
      if (! empty($this->source_file_path)) {
        debug("Deleting temporary source file {$this->source_file_path}");
        unlink($this->source_file_path); // Delete temporary file
      }
    }
    catch (\Exception $e) {
      debug("Error deleting temporary file {$this->source_file_path}: {$e->getMessage()}");
    }
  }


  /**
   * Harvest process for this connector.
   * @see update_resources/connectors/falo.php
   */
  public function begin() {
    $this->download_source_data_file();
    $this->load_source_data_from_file();
    $this->extract_data_from_loaded_source();
    unset($this->source_loaded);
    $this->assign_parent_identifiers();
    $this->build_archive();
    debug('Peak memory usage: ' . (memory_get_peak_usage(true) / 1024 / 1024) . 'MB');
  }

  /**
   * Output time and memory profile information.
   * @param started microtime of start of profile period.
   */
  private function profile($started) {
    $end = microtime(true);
    debug('Finished ' .  sprintf('%.4f', $end - $started) . ' seconds');
    debug('Current memory usage: ' . (memory_get_usage(true) / 1024 / 1024) . ' MB');
  }

  /**
   * Download source data from URL to temporary location on local file system.
   */
  private function download_source_data_file() {
    $start = microtime(true);
    debug("Downloading source file.");
    $download_options = array(
      'file_extension' => pathinfo($this->source_url, PATHINFO_EXTENSION),
      'cache'          => true,
      'timeout'        => 172800
    );
    $this->source_file_path = Functions::save_remote_file_to_local($this->source_url, $download_options);
    if (! file_exists($this->source_file_path)) {
      throw new \Exception('Error downloading source file.');
    }
    $this->profile($start);
  }

  /**
   * Loads source data from local file into memory.
   */
  private function load_source_data_from_file() {
    require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';
    // NOTE: In development it takes 24 Seconds to load and uses 318 MB of memory.
    $start = microtime(true);
    debug('Loading source file.');
    try {
      $reader = new \PHPExcel_Reader_Excel2007();
      $reader->setReadDataOnly(true);
      $this->source_loaded = $reader->load($this->source_file_path);
      unset($reader);
    }
    catch (\Exception $e) {
      throw new \Exception('Error loading source data from '
        . "{$this->source_file_path}: {$e->getMessage()}");
    }
    $this->profile($start);
  }

  /**
   * Extract relevant data from loaded source.
   */
  private function extract_data_from_loaded_source() {
    require_once DOC_ROOT . '/vendor/PHPExcel/Classes/PHPExcel.php';
    $start = microtime(true);
    debug('Extracting taxa.');
    $columns_by_letter = array(); // Columns we want to extract
    $last_row = $this->source_loaded->getActiveSheet()->getHighestRow();
    $row_iterator = $this->source_loaded->getActiveSheet()->getRowIterator();
    $this->taxa = array();
    foreach ($row_iterator as $row) {
      $ri = $row->getRowIndex();
      $t = array();
      $t['uncertain'] = array();
      $cell_iterator = $row->getCellIterator();
      foreach ($cell_iterator as $cell) {
        $column_letter = $cell->getColumn();
        if ($ri == 1) {
          // Store letters (A, B, C etc) of columns we want to extract
          if (($column_label = strtolower($cell->getValue())) && in_array($column_label, self::$columns_to_extract)) {
            $columns_by_letter[$column_letter] = $column_label;
          }
        }
        elseif (in_array($column_letter, array_keys($columns_by_letter)) && ($value = trim($cell->getValue()))) {
          try {
            if ($columns_by_letter[$column_letter] == 'uuid') {
              $t['taxonID'] = $value;
            }
            elseif ($columns_by_letter[$column_letter] == 'reference') {
              $t['bibliographicCitation'] = $value;
            }
            else {
              list($rank, $name) = explode(' ', $value);
              $rank = strtolower(trim($rank));
              if (strpos($name, '"') !== FALSE) {
                $name = preg_replace('/[^A-z]/', '', $name); // Remove quotes
                $t['uncertain'][$rank] = $value;
              }
              if (! in_array($rank, self::$ranks)) {
                throw new \Exception("Error unknown rank at row index: $ri");
              }
              $t[$rank] = $name;
              unset($rank);
              unset($name);
            }
          }
          catch (\Exception $e) {
            throw new \Exception('Error parsing spreadsheet at row index: '
              . "{$ri}: {$e->getMessage()}");
          }
        }
        unset($column_letter);
      }

      // Treat header row differently from other rows
      if ($ri == 1) {
        if (!$this->validate_columns($columns_by_letter)) {
          throw new \Exception('Unexpected or missing columns in FALO source file.');
        }
        // Everything is okay with the header row, move on to next row.
        continue;
      }

      // Bail if we've got a problem parsing the spreadsheet.
      if (empty($t) || !isset($t['taxonID'])) {
        if ($ri == $last_row) {
          debug("Ignoring incomplete last row at index: {$ri}");
        }
        else {
          throw new \Exception("Error extracting taxon at row index: {$ri}");
        }
      }

      // Extract the classification and sort by rank
      $classification = array();
      foreach ($t as $k => $v) {
        if (in_array($k, self::$ranks)) {
          $classification[$k] = $v;
        }
      }
      uksort($classification, 'self::sort_by_rank');
      if (empty($classification)) {
        throw new \Exception("Error extracting classificiation at row index: {$ri}");
      }

      // Set classificaiton tree paths and extract last taxon
      $t['classificationHash'] = md5(implode(';', $classification));
      if (count($classification) > 1) {
        // higherClassification is useful for debugging but not required for harvest
        $t['higherClassification'] = implode(';', array_slice($classification, 0, -1));
        $t['higherClassificationHash'] = md5($t['higherClassification']);
      }
      $t['scientificName'] = end($classification);
      $t['taxonRank'] = key($classification);
      unset($classification);

      $t['taxonRemarks'] = '';
      if (isset($t['uncertain'][$t['taxonRank']])) {
        $t['taxonRemarks'] = "{$t['uncertain'][$t['taxonRank']]} uncertain";
      }
      unset($t['uncertain']);

      $this->taxa[] = $t;
    }
    $this->profile($start);
  }

  /**
   * Sort array keys by rank.
   */
  private static function sort_by_rank($a, $b) {
    $x = array_search($a, self::$ranks);
    $y = array_search($b, self::$ranks);
    if ($x === false) $x = -1;
    if ($y === false) $y = -1;
    if ($x == $y) return 0;
    return ($x < $y) ? -1 : 1;
  }


  /**
   * Determine and add parent identifiers to taxa in extracted data.
   */
  private function assign_parent_identifiers() {
    $start = microtime(true);
    debug('Assigning parent identifiers.');
    foreach ($this->taxa as &$taxon) {
      if (isset($taxon['higherClassificationHash'])) {
        foreach ($this->taxa as $parent) {
          if ($taxon['higherClassificationHash'] == $parent['classificationHash']) {
            $taxon['parentNameUsageID'] = $parent['taxonID'];
            break;
          }
        }
      }
    }
    $this->profile($start);
  }

  /**
   * Build darwin core archive for harvest.
   */
  private function build_archive() {
    $start = microtime(true);
    debug("Building archive {$this->path_to_archive_directory}.");
    $this->archive_builder = new \eol_schema\ContentArchiveBuilder(array('directory_path' => $this->path_to_archive_directory));
    foreach ($this->taxa as $taxon) {
      $t = new \eol_schema\Taxon();
      $archive_properties = array(
        'taxonID', 'scientificName', 'taxonRank', 'parentNameUsageID',
        'bibliographicCitation', 'taxonRemarks'
      );
      foreach ($archive_properties as $property) {
        if (isset($taxon[$property])) {
          $t->{$property} = $taxon[$property];
        }
      }
      $this->archive_builder->write_object_to_file($t);
    }
    $this->archive_builder->finalize(true);
    $this->profile($start);
  }

  /**
   * Check column names match expected values.
   */
  private function validate_columns($columns) {
    return ( count($columns) == count(self::$columns_to_extract) &&
      !array_diff($columns, self::$columns_to_extract) );
  }

}
