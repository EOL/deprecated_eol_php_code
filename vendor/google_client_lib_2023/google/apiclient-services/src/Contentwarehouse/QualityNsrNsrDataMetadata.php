<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\Contentwarehouse;

class QualityNsrNsrDataMetadata extends \Google\Collection
{
  protected $collection_key = 'raffiaLookupKeys';
  /**
   * @var int[]
   */
  public $goldmineLookupKeyPerField;
  /**
   * @var string[]
   */
  public $goldmineLookupKeys;
  /**
   * @var string
   */
  public $raffiaLookupKey;
  /**
   * @var int[]
   */
  public $raffiaLookupKeyPerField;
  /**
   * @var string[]
   */
  public $raffiaLookupKeys;

  /**
   * @param int[]
   */
  public function setGoldmineLookupKeyPerField($goldmineLookupKeyPerField)
  {
    $this->goldmineLookupKeyPerField = $goldmineLookupKeyPerField;
  }
  /**
   * @return int[]
   */
  public function getGoldmineLookupKeyPerField()
  {
    return $this->goldmineLookupKeyPerField;
  }
  /**
   * @param string[]
   */
  public function setGoldmineLookupKeys($goldmineLookupKeys)
  {
    $this->goldmineLookupKeys = $goldmineLookupKeys;
  }
  /**
   * @return string[]
   */
  public function getGoldmineLookupKeys()
  {
    return $this->goldmineLookupKeys;
  }
  /**
   * @param string
   */
  public function setRaffiaLookupKey($raffiaLookupKey)
  {
    $this->raffiaLookupKey = $raffiaLookupKey;
  }
  /**
   * @return string
   */
  public function getRaffiaLookupKey()
  {
    return $this->raffiaLookupKey;
  }
  /**
   * @param int[]
   */
  public function setRaffiaLookupKeyPerField($raffiaLookupKeyPerField)
  {
    $this->raffiaLookupKeyPerField = $raffiaLookupKeyPerField;
  }
  /**
   * @return int[]
   */
  public function getRaffiaLookupKeyPerField()
  {
    return $this->raffiaLookupKeyPerField;
  }
  /**
   * @param string[]
   */
  public function setRaffiaLookupKeys($raffiaLookupKeys)
  {
    $this->raffiaLookupKeys = $raffiaLookupKeys;
  }
  /**
   * @return string[]
   */
  public function getRaffiaLookupKeys()
  {
    return $this->raffiaLookupKeys;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(QualityNsrNsrDataMetadata::class, 'Google_Service_Contentwarehouse_QualityNsrNsrDataMetadata');
