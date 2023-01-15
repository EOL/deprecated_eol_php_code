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

class AssistantGroundingRankerMediaGroundingProviderFeatures extends \Google\Model
{
  /**
   * @var bool
   */
  public $isSeedRadio;
  /**
   * @var bool
   */
  public $isSeedRadioRequest;
  /**
   * @var float
   */
  public $mscRate;

  /**
   * @param bool
   */
  public function setIsSeedRadio($isSeedRadio)
  {
    $this->isSeedRadio = $isSeedRadio;
  }
  /**
   * @return bool
   */
  public function getIsSeedRadio()
  {
    return $this->isSeedRadio;
  }
  /**
   * @param bool
   */
  public function setIsSeedRadioRequest($isSeedRadioRequest)
  {
    $this->isSeedRadioRequest = $isSeedRadioRequest;
  }
  /**
   * @return bool
   */
  public function getIsSeedRadioRequest()
  {
    return $this->isSeedRadioRequest;
  }
  /**
   * @param float
   */
  public function setMscRate($mscRate)
  {
    $this->mscRate = $mscRate;
  }
  /**
   * @return float
   */
  public function getMscRate()
  {
    return $this->mscRate;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AssistantGroundingRankerMediaGroundingProviderFeatures::class, 'Google_Service_Contentwarehouse_AssistantGroundingRankerMediaGroundingProviderFeatures');
