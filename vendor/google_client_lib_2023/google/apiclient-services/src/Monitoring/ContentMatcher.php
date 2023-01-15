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

namespace Google\Service\Monitoring;

class ContentMatcher extends \Google\Model
{
  /**
   * @var string
   */
  public $content;
  protected $jsonPathMatcherType = JsonPathMatcher::class;
  protected $jsonPathMatcherDataType = '';
  public $jsonPathMatcher;
  /**
   * @var string
   */
  public $matcher;

  /**
   * @param string
   */
  public function setContent($content)
  {
    $this->content = $content;
  }
  /**
   * @return string
   */
  public function getContent()
  {
    return $this->content;
  }
  /**
   * @param JsonPathMatcher
   */
  public function setJsonPathMatcher(JsonPathMatcher $jsonPathMatcher)
  {
    $this->jsonPathMatcher = $jsonPathMatcher;
  }
  /**
   * @return JsonPathMatcher
   */
  public function getJsonPathMatcher()
  {
    return $this->jsonPathMatcher;
  }
  /**
   * @param string
   */
  public function setMatcher($matcher)
  {
    $this->matcher = $matcher;
  }
  /**
   * @return string
   */
  public function getMatcher()
  {
    return $this->matcher;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ContentMatcher::class, 'Google_Service_Monitoring_ContentMatcher');
