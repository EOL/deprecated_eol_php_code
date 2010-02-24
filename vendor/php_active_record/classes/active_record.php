<?php
// namespace php_active_record;
// 
// class ActiveRecord
// {
//     static $primary_key = 'id';
//     static $table_name;
//     static $foreign_key;
//     
//     static $belongs_to = array();
//     static $has_many = array();
//     static $has_one = array();
//     static $validates = array();
//     
//     static $before_save = array();
//     static $after_save = array();
//     static $before_create = array();
//     static $after_create = array();
//     static $before_destroy = array();
//     static $after_destroy = array();
//     static $before_validation = array();
//     static $after_validation = array();
//     
//     function __construct($args = false)
//     {
//         if(isset($args) && is_array($args))
//         {
//             $this->initialize_from_row($args);
//         }
//     }
//     
//     /*====================================================================*/
//     /*
//         Static Functions
//     */
//     
//     public static function all()
//     {
//         return self::find(NULL, 'all');
//     }
//     
//     public static function first($args = array())
//     {
//         return self::find(NULL, 'first', $args);
//     }
//     
//     public static function last($args = array())
//     {
//         return self::find(NULL, 'last', $args);
//     }
//     
//     public static function find($value = NULL, $scope = NULL, $args = array())
//     {
//         if(is_null($value) && is_null($scope)) $scope = 'all';
//         if(!in_array($scope, array('all', 'first', 'last'))) $scope = 'first';
//         if(!is_numeric($value) && !is_array($value) && !is_null($value)) return false;
//         
//         if($cache = Cache::get("find_". self::table_name() ."|".$value."|".$scope."|".serialize($args))) return $cache;
//         
//         $return = array();
//         $class = self::called_class();
//         
//         $query = "SELECT * FROM ".self::table_name();
//         if(is_numeric($value)) $query .= " WHERE " . static::$primary_key . "=$value";
//         elseif(is_array($value)) $query .= " WHERE " . static::$primary_key . " IN (".implode(", ", $value).")";
//         
//         if($scope == 'first')
//         {
//             $query .= ' ORDER BY ' . static::$primary_key . ' ASC LIMIT 0,1';
//         }elseif($scope == 'last')
//         {
//             $query .= ' ORDER BY ' . static::$primary_key . ' DESC LIMIT 0,1';
//         }else
//         {
//             if(@$args['conditions']) $query .= ' AND (' . $args['conditions'] . ')';
//             if(@$args['group']) $query .= ' GROUP BY ' .    $GLOBALS['db_connection']->escape($args['group']);
//             if(@$args['having']) $query .= ' HAVING ' .     $GLOBALS['db_connection']->escape($args['having']);
//             if(@$args['order']) $query .= ' ORDER BY ' .    $GLOBALS['db_connection']->escape($args['order']);
//             if(@$args['limit']) $query .= ' LIMIT ' .       $GLOBALS['db_connection']->escape($args['limit']);
//         }
//         
//         
//         $result = $GLOBALS['db_connection']->query($query);
//         while($result && $row = $result->fetch_assoc())
//         {
//             $object = new $class();
//             $object->initialize_from_row($row);
//             if($scope != 'all')
//             {
//                 $return = $object;
//                 break;
//             }
//             
//             $return[] = $object;
//         }
//         if($result && $result->num_rows) $result->free();
//         
//         if(!$return && $scope != 'all') $return = NULL;
//         
//         Cache::set("find_". self::table_name() ."|".$value."|".$scope."|".serialize($args), $return);
//         return $return;
//     }
//     
//     public static function find_by($attribute, $value, $args = array())
//     {
//         if(!isset($attribute)) return NULL;
//         if(!isset($value)) return NULL;
//         if($attribute!=1 && !self::is_field($attribute))
//         {
//             trigger_error('Error Finding: ' . $attribute . ' is undefined in ' . self::called_class() . ' in ' .get_last_function(1), E_USER_ERROR);
//         }
//         
//         if($cache = Cache::get("find_by_". self::table_name() ."|".$attribute."|".$value."|".serialize($args))) return $cache;
//         
//         $value = $GLOBALS['db_connection']->escape($value);
//         $class = self::called_class();
//         $return = array();
//         
//         $query = "SELECT * FROM ".self::table_name()." WHERE $attribute='$value'";
//         
//         if(@$args['conditions']) $query .= ' AND (' . $args['conditions'] . ')';
//         if(@$args['group']) $query .= ' GROUP BY ' .    $GLOBALS['db_connection']->escape($args['group']);
//         if(@$args['having']) $query .= ' HAVING ' .     $GLOBALS['db_connection']->escape($args['having']);
//         if(@$args['order']) $query .= ' ORDER BY ' .    $GLOBALS['db_connection']->escape($args['order']);
//         if(@$args['limit']) $query .= ' LIMIT ' .       $GLOBALS['db_connection']->escape($args['limit']);
//                 
//         $result = $GLOBALS['db_connection']->query($query);
//         while($result && $row = $result->fetch_assoc())
//         {
//             $object = new $class();
//             $object->initialize_from_row($row);
//             if(!@$args['find_all']) return $object;
//             
//             $return[] = $object;
//         }
//         if($result && $result->num_rows) $result->free();
//         
//         if(!$return && !@$args['find_all']) $return = NULL;
//         
//         Cache::set("find_by_". self::table_name() ."|".$attribute."|".$value."|".serialize($args), $return);
//         return $return;
//     }
//     
//     public static function find_last_by($attribute, $value, $order = null)
//     {
//         
//     }
//     
//     public static function find_or_create_by($attribute, $value, $args = array())
//     {
//         if($object = self::find_by($attribute, $value, $args)) return $object;
//         
//         $class = self::called_class();
//         $object = new $class();
//         $object->$attribute = $value;
//         foreach($args as $attr => $val)
//         {
//             if(self::is_field($attr)) $object->$attr = $val;
//         }
//         
//         $object->save();
//         
//         return $object;
//     }
//     
//     public static function create($args = array())
//     {
//         $class = self::called_class();
//         $object = new $class();
//         foreach($args as $attr => $val)
//         {
//             if(self::is_field($attr)) $object->$attr = $val;
//         }
//         
//         $object->save();
//         
//         return $object;
//     }
//     
//     
//     
//     /*====================================================================*/
//     /*
//         Public Instance Functions
//     */
//     
//     public function save()
//     {
//         if(!$this->valid()) return false;
//         
//         if(!$this->validate_callback('before_save')) return false;
//         
//         if($this->exists()) $id = $this->save_update();
//         else $id = $this->create_private();
//         
//         if(!$this->validate_callback('after_save')) return false;
//         
//         return $id;
//     }
//     
//     public function valid()
//     {
//         if(!$this->validate_callback('before_validation')) return false;
//         if(!$this->validate_callback('validates')) return false;
//         if(!$this->validate_callback('after_validation')) return false;
//         return true;
//     }
//     
//     public function _set($attribute, $value)
//     {
//         if(!$this->id) trigger_error('Setting value: Cannot set attribute `' . $attribute . '` of non-existant ' . self::called_class() . ' in '.get_last_function(1), E_USER_ERROR);
//         if(self::is_field($attribute))
//         {
//             $query = "UPDATE `" . self::table_name() . "` SET `$attribute`='" . $GLOBALS['db_connection']->escape($value) . "'  WHERE `" . static::$primary_key . "`=".$this->id;
//             $GLOBALS['db_connection']->update($query);
//             $this->$attribute = $value;
//         }elseif(self::check_relationship('belongs_to', $attribute))
//         {
//             $association_class = __NAMESPACE__ . '\\' . to_camel_case($attribute);
//             $association_primary_key = $association_class::$primary_key;
//             
//             if($fk = @$attributes['foreign_key']) $foreign_key = $fk;
//             else $foreign_key = $attribute . '_id';
//             
//             $query = "UPDATE `" . self::table_name() . "` SET `$foreign_key`=" . $value->$association_primary_key . "  WHERE `" . static::$primary_key . "`=".$this->id;
//             $GLOBALS['db_connection']->update($query);
//             $this->$foreign_key = $value->$association_primary_key;
//         }elseif(self::check_relationship('has_one', $attribute))
//         {
//             $association_class = __NAMESPACE__ . '\\' . to_camel_case($attribute);
//             $primary_key = static::$primary_key;
//             
//             if($fk = @$attributes['foreign_key']) $foreign_key = $fk;
//             else $foreign_key = static::foreign_key();
//             
//             $query = "UPDATE `" . $association_class::table_name() . "` SET `$foreign_key`=" . $this->$primary_key . "  WHERE `" . $association_class::$primary_key . "`=".$value->$primary_key;
//             $GLOBALS['db_connection']->update($query);
//             $value->$foreign_key = $this->$primary_key;
//         }else trigger_error('Object set value: Invalid attribute or association: `' . $attribute . '` in '.get_last_function(1), E_USER_WARNING);
//         
//         return true;
//     }
//     
//     public function _add($association, &$object)
//     {
//         $class_name = __NAMESPACE__ . '\\' . to_camel_case($association);
//         if(!class_exists($class_name)) trigger_error("Association Add: Class `$class_name` doesn't exist in ". get_last_function(1), E_USER_ERROR);
//         
//         $args = self::check_relationship('has_many', $association);
//         if(!$args) trigger_error("Association Add: Class `$association` doesn't exist in ". get_last_function(1), E_USER_ERROR);
//         
//         if($through = @$args['through'])
//         {
//             $through = to_singular($through);
//             $through_class_name = __NAMESPACE__ . '\\' . to_camel_case($through);
//             if(!class_exists($through_class_name)) trigger_error("Association Add: Class `$through_class_name` doesn't exist in ". get_last_function(1), E_USER_ERROR);
//             
//             if($fk = @$args['foreign_key']) $foreign_key = $fk;
//             else $foreign_key = static::foreign_key();
//             
//             $primary_key = static::$primary_key;
//             $association_foreign_key = call_user_func($class_name . "::foreign_key");
//             $association_primary_key = $class_name::$primary_key;
//             
//             $through_object = new $through_class_name();
//             $through_object->$foreign_key = $this->$primary_key;
//             $through_object->$association_foreign_key = $object->$association_primary_key;
//             $through_object->save();
//         }else
//         {
//             $set_method = '_set_' . self::foreign_key();
//             $object->$set_method($this->id);
//         }
//         
//         return true;
//     }
//     
//     
//     
//     
//     
//     public function to_string()
//     {
//         $attributes = $this->attributes_as_array();
//         $string = self::table_name() . ":<br/>\n";
//         foreach($attributes as $attr => $val)
//         {
//             $string .= "  [$attr] $val<br/>\n";
//         }
//         
//         return $string;
//     }
//     
//     public function to_json()
//     {
//         return json_encode($attributes = $this->attributes_as_array());
//     }
//     
//     public function to_xml()
//     {
//         $attributes = $this->attributes_as_array();
//         $xml = '<' . self::table_name() . '>';
//         foreach($attributes as $attr => $val)
//         {
//             $xml .= "<$attr>" . htmlspecialchars($val) . "</$attr>";
//         }
//         $xml .= '</' . self::table_name() . '>';
//         
//         return $xml;
//     }
//     
//     public function destroy()
//     {
//         if(!@$this->id) return false;
//         if(!$this->validate_callback('before_destroy')) return false;
//         
//         $query = "DELETE FROM `" . self::table_name() . "` WHERE `" . static::$primary_key . "`=" . $this->id;
//         $GLOBALS['db_connection']->delete($query);
//         unset($this->id);
//         
//         if(!$this->validate_callback('after_destroy')) return false;
//     }
//     
//     public function refresh()
//     {
//         if(!$this->exists()) return false;
//         $result = $GLOBALS['db_connection']->query("SELECT * FROM ". self::table_name() ." WHERE `". static::$primary_key ."`=". $this->id ." LIMIT 0,1");
//         if($result && $row = $result->fetch_assoc())
//         {
//             foreach($row as $k => $v) $this->$k = $v;
//         }
//         if($result && $result->num_rows) $result->free();
//         
//         return false;
//     }
//     
//     public function equals($object)
//     {
//         if(@get_class($this) != @get_class($object)) return false;
//         if(!$this->exists()) return false;
//         if(!$object->exists()) return false;
//         if($this->id != $object->id) return false;
//         
//         return true;
//     }
//     
//     public function exists()
//     {
//         if(isset($this->id) && $this->id) return true;
//         return false;
//     }
//     
//     
//     /*====================================================================*/
//     /*
//         Private Instance Functions
//     */
//     
//     private function save_update()
//     {
//         if(!$this->exists()) return false;
//         
//         $attributes = $this->attributes_as_array(true);
//         
//         $query = "UPDATE " . self::table_name() . " SET ";
//         foreach($attributes as $attr => $val)
//         {
//             $query .= "`$attr`=$val, ";
//         }
//         $query = substr($query, 0, -2);
//         $query .= " WHERE `" . static::$primary_key . "`=".$this->id;
//         
//         $GLOBALS['db_connection']->update($query);
//         
//         return $this->id;
//     }
//     
//     private function create_private()
//     {
//         if($this->exists()) return false;
//         if(!$this->validate_callback('before_create')) return false;
//         
//         $attributes = $this->attributes_as_array(true);
//         if(!$attributes) $attributes[self::$primary_key] = 'NULL';
//         
//         $query = "INSERT INTO `" . self::table_name() . "` (`";
//         $query .= implode("`, `", array_keys($attributes));
//         $query .= "`) VALUES (";
//         $query .= implode(", ", $attributes);
//         $query .= ")";
//         
//         $id = $GLOBALS['db_connection']->insert($query);
//         $this->id = $id;
//         
//         if(!$this->validate_callback('after_create')) return false;
//                 
//         return $id;
//     }
//     
//     private function validate_callback($callback)
//     {
//         foreach(static::$$callback as $function)
//         {
//             if(!method_exists($this, $function)) trigger_error("$callback: Method `$function` doesn't exist in class `". self::called_class(). "` in ". get_last_function(1), E_USER_ERROR);
//             if($this->$function() === false) throw new ActiveRecordError("Validation `$callback::$function` failed");
//         }
//         
//         return true;
//     }
//     
//     private static function check_relationship($relationship, $class)
//     {
//         // has_many relationships will be plural - have an extra 's' at the end
//         if($relationship == 'has_many') $class = to_plural($class);
//         
//         foreach(static::$$relationship as $args)
//         {
//             $related_class = current($args);
//             if($class == $related_class) return $args;
//         }
//         
//         return false;
//     }
//     
//     private function get_belongs_to($association, $args)
//     {
//         if($association_class = @$args['class_name']) $association = $association_class;
//         $class_name = __NAMESPACE__ . '\\' . to_camel_case($association);
//         if(!class_exists($class_name)) trigger_error("Association: Class `$class_name` doesn't exist in ". get_last_function(1), E_USER_ERROR);
//         
//         if($fk = @$args['foreign_key']) $foreign_key = $fk;
//         else $foreign_key = $association . '_id';
//         
//         $table_name = call_user_func($class_name. "::table_name");
//         $association_primary_key = $class_name::$primary_key;
//         $result = $GLOBALS['db_connection']->query("SELECT * FROM `$table_name` WHERE `$association_primary_key`=" . $this->$foreign_key);
//         if($result && $row=$result->fetch_assoc())
//         {
//             $object = new $class_name();
//             $object->initialize_from_row($row);
//             return $object;
//         }
//         
//         return NULL;
//     }
//     
//     private function get_has_many($association, $args)
//     {
//         if($association_class = @$args['class_name']) $association = $association_class;
//         else $association = to_singular($association);
//         $class_name = __NAMESPACE__ . '\\' . to_camel_case($association);
//         if(!class_exists($class_name)) trigger_error("Association: Class `$class_name` doesn't exist in ". get_last_function(1), E_USER_ERROR);
//         $return = array();
//         
//         if($fk = @$args['foreign_key']) $foreign_key = $fk;
//         else $foreign_key = self::foreign_key();
//         
//         if($through = @$args['through'])
//         {
//             $through = to_singular($through);
//             $through_class_name = __NAMESPACE__ . '\\' . to_camel_case($through);
//             if(!class_exists($through_class_name)) trigger_error("Association: Class `$through_class_name` doesn't exist in ". get_last_function(1), E_USER_ERROR);
//             
//             //$foreign_key = self::foreign_key();
//             $association_foreign_key = call_user_func($class_name . "::foreign_key");
//             $association_primary_key = $class_name::$primary_key;
//             $table_name = call_user_func($class_name . "::table_name");
//             $through_table_name = call_user_func($through_class_name . "::table_name");
//             
//             $result = $GLOBALS['db_connection']->query("SELECT `$table_name`.* FROM `$through_table_name` INNER JOIN `$table_name` ON (`$through_table_name`.`$association_foreign_key` = `$table_name`.`$association_primary_key`) WHERE `$through_table_name`.`$foreign_key`=" . $this->id);
//             while($result && $row=$result->fetch_assoc())
//             {
//                 $object = new $class_name();
//                 $object->initialize_from_row($row);
//                 $return[] = $object;
//             }
//         }else
//         {
//             //$foreign_key = self::foreign_key();
//             $table_name = call_user_func($class_name . "::table_name");
//             $primary_key = static::$primary_key;
//             $result = $GLOBALS['db_connection']->query("SELECT * FROM `$table_name` WHERE `$foreign_key`=" . $this->$primary_key);
//             while($result && $row=$result->fetch_assoc())
//             {
//                 $object = new $class_name();
//                 $object->initialize_from_row($row);
//                 $return[] = $object;
//             }
//         }
//         
//         return $return;
//     }
//     
//     private function get_has_one($association, $args)
//     {
//         $class_name = __NAMESPACE__ . '\\' . to_camel_case($association);
//         if(!class_exists($class_name)) trigger_error("Association: Class `$class_name` doesn't exist in ".get_last_function(1), E_USER_ERROR);
//         
//         $foreign_key = self::foreign_key();
//         $table_name = $association . 's';
//         $primary_key = static::$primary_key;
//         $result = $GLOBALS['db_connection']->query("SELECT * FROM " . $table_name . " WHERE $foreign_key=" . $this->$primary_key);
//         if($result && $row=$result->fetch_assoc())
//         {
//             $object = new $class_name();
//             $object->initialize_from_row($row);
//             return $object;
//         }
//         
//         return NULL;
//     }
//     
//     
//     
//     
//     
//     
//     
//     /*====================================================================*/
//     /*
//         Other Functions
//     */
//     
//     public static function is_field($field)
//     {
//         $fields = self::table_fields();
//         foreach($fields as $attribute)
//         {
//             if($field == $attribute) return true;
//         }
//         return false;
//     }
//     
//     private function attributes_as_array($escape = false)
//     {
//         $attributes = array();
//         
//         $fields = self::table_fields();
//         foreach($fields as $attribute)
//         {
//             if(@isset($this->$attribute))
//             {
//                 if($escape)
//                 {
//                     $attributes[$attribute] = $GLOBALS['db_connection']->escape($this->$attribute);
//                     if(is_null($attributes[$attribute])) $attributes[$attribute] = 'NULL';
//                     else $attributes[$attribute] = "'".$attributes[$attribute]."'";
//                 }else $attributes[$attribute] = $this->$attribute;
//             }
//         }
//         
//         if(!$attributes) return NULL;
//         return $attributes;
//     }
//     
//     protected function initialize_from_row(&$row)
//     {
//         if($row && is_array($row))
//         {
//             foreach($row as $k => $v) $this->$k = $v;
//         }
//     }
//     
//     public static function table_fields()
//     {
//         if($cache = Cache::get('table_fields_' . self::table_name())) return $cache;
//         
//         $fields = array();
//         
//         $result = $GLOBALS['db_connection']->query('SHOW fields FROM `' . self::table_name() . '`');
//         while($result && $row=$result->fetch_assoc())
//         {
//             $fields[] = $row["Field"];
//         }
//         if($result) $result->free();
//         
//         Cache::set('table_fields_' . self::table_name(), $fields, 600);
//         
//         return $fields;
//     }
//     
//     public static function table_name()
//     {
//         if($cache = Cache::get('table_name_' . self::called_class())) return $cache;
//         
//         $called_class = self::called_class();
//         $called_class_name = $called_class;
//         if(preg_match("/\\\([^\\\]*)$/", $called_class, $arr)) $called_class_name = $arr[1];
//         
//         if(property_exists($called_class, 'table_name') && static::$table_name) $table_name = static::$table_name;
//         else $table_name = to_underscore($called_class_name) . 's';
//         
//         Cache::set('table_fields_' . self::called_class(), $table_name, 600);
//         
//         return $table_name;
//     }
//     
//     public static function foreign_key()
//     {
//         $called_class = self::called_class();
//         $called_class_name = $called_class;
//         if(preg_match("/\\\([^\\\]*)$/", $called_class, $arr)) $called_class_name = $arr[1];
//         
//         if(property_exists($called_class, 'foreign_key') && static::$foreign_key) $foreign_key = static::$foreign_key;
//         else $foreign_key = to_underscore($called_class_name) . '_id';
//         
//         return $foreign_key;
//     }
//     
//     public static function called_class()
//     {
//         $called_class = get_called_class();
//         return $called_class;
//     }
//     
//     
//     
//     public function __clone()
//     {
//         $id = static::$primary_key;
//         if(@$this->$id) unset($this->$id);
//     }
//     
//     public function __toString()
//     {
//         // $string = "<pre>";
//         // $string .= print_r($this, 1);
//         // $string .= "</pre>";
//         
//         $string = get_called_class()."<br/>\n";
//         $fields = self::table_fields();
//         foreach($fields as $field)
//         {
//             //if(@$this->$field) 
//             $string .= str_repeat(' -', 3)."$field => ".@$this->$field."<br/>\n";
//         }
//         
//         return $string;
//     }
//     
//     // for intercepting object members
//     public function __get($name)
//     {
//         if($this->is_field($name)) return isset($this->$name) ? $this->$name : NULL;
//         
//         foreach(static::$has_one as $args)
//         {
//             $table = array_shift($args);
//             if($table == $name)
//             {
//                 return self::get_has_one($table, $args);
//             }
//         }
//         
//         foreach(static::$has_many as $args)
//         {
//             $table = array_shift($args);
//             if($table == $name)
//             {
//                 return self::get_has_many($table, $args);
//             }
//         }
//         
//         foreach(static::$belongs_to as $args)
//         {
//             $table = array_shift($args);
//             if($table == $name)
//             {
//                 return self::get_belongs_to($table, $args);
//             }
//         }
//         
//         trigger_error('Call to undefined member `' . self::called_class() . '->' . $name . '` in '.get_last_function(1), E_USER_WARNING);
//     }
//     
//     public function __call($function, $args)
//     {
//         if(preg_match("/^_set_([a-z0-9_]+)$/", $function, $arr))
//         {
//             $attribute = $arr[1];
//             $value = array_shift($args);
//             return $this->_set($attribute, $value);
//         }
//         
//         if(preg_match("/^_add_([a-z0-9_]+)$/", $function, $arr))
//         {
//             $attribute = $arr[1];
//             $value = array_shift($args);
//             return $this->_add($attribute, $value);
//         }
//         
//         //trigger_error('Call to undefined method `' . self::called_class() . '->' . $function . '()` in '.get_last_function(2), E_USER_ERROR);
//         return static::__callStatic($function, $args);
//     }
//     
//     // for intercepting object static functions
//     public static function __callStatic($function, $args)
//     {
//         if(preg_match("/^find_by_([a-z0-9_]+)$/", $function, $arr))
//         {
//             $attribute = $arr[1];
//             $value = array_shift($args);
//             $args = @array_pop($args);
//             $args['find_all'] = false;
//             return self::find_by($attribute, $value, $args);
//         }elseif(preg_match("/^find_all_by_([a-z0-9_]+)$/", $function, $arr))
//         {
//             $attribute = $arr[1];
//             $value = array_shift($args);
//             $args = @array_pop($args);
//             $args['find_all'] = true;
//             return self::find_by($attribute, $value, $args);
//         }elseif($function == "find_all")
//         {
//             $args = @array_pop($args);
//             $args['find_all'] = true;
//             return self::find_by(1, 1, $args);
//         }elseif(preg_match("/^find_or_create_by_([a-z0-9_]+)$/", $function, $arr))
//         {
//             $attribute = $arr[1];
//             $value = array_shift($args);
//             $args = @array_pop($args);
//             $args['find_all'] = false;
//             return self::find_or_create_by($attribute, $value, $args);
//         }
//         
//         trigger_error('Call to undefined method `' . self::called_class() . ' => ' . $function . '()` in '.get_last_function(1), E_USER_ERROR);
//     }
// }

?>