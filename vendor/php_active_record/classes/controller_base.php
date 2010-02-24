<?php
// namespace php_active_record;
// 
// class ControllerBase
// {
//     public static function __callStatic($name, $args)
//     {
//         if(preg_match("/^method_\|(.*)\|(.*)$/", $name, $arr))
//         {
//             $controller = $arr[1];
//             $action = $arr[2];
//             
//             // staticly call the controller action
//             $return = static::$action($args[0]);
//             
//             // if the response is an array show the default view for this action
//             if(is_null($return) || is_array($return))
//             {
//                 if(file_exists(DOC_ROOT . "app/views/$controller/$action.php")) return render_view("$controller/$action", $return);
//             }
//             return true;
//         }
//         
//         trigger_error('Call to undefined member '.$name, E_USER_WARNING);
//     }
//     
//     public static function model_class()
//     {
//         $model = get_called_class();
//         if(preg_match("/^(.*)Controller$/", $model, $arr)) $model = $arr[1];
//         
//         $model = to_singular($model);
//         return $model;
//     }
//     
//     public static function update($args)
//     {
//         if(@!$args["id"]) return false;
//         //if(@$_SERVER['REQUEST_METHOD'] != 'POST') return false;
//         
//         // get the name of the model class
//         $model = self::model_class();
//         
//         // make sure the model class is loaded
//         if(class_exists($model))
//         {
//             // find this instance
//             $object = $model::find($args["id"]);
//             if($object)
//             {
//                 if(isset($args["attribute"]) && isset($args["value"]))
//                 {
//                     $attribute = $args["attribute"];
//                     $value = $args["value"];
//                     
//                     if($model::is_field($attribute))
//                     {
//                         $function = "_set_$attribute";
//                         $object->$function($value);
//                     }elseif(isset($args["association_attribute"]))
//                     {
//                         $associated_class_name = __NAMESPACE__ .'\\'. to_camel_case($attribute);
//                         if(!class_exists($associated_class_name)) trigger_error("Class `$associated_class_name` does not exist" , E_USER_ERROR);
//                         
//                         $association_attribute = $args["association_attribute"];
//                         $method = "find_or_create_by_". $association_attribute;
//                         $associated_object = $associated_class_name::$method($value);
//                         if($associated_object->exists())
//                         {
//                             $method = "_set_". $attribute;
//                             $object->$method($associated_object);
//                         }
//                     }
//                 }else
//                 {
//                     // iterate through the incoming attributes
//                     foreach($args as $attribute => $value)
//                     {
//                         if($model::is_field($attribute))
//                         {
//                             $function = "_set_$attribute";
//                             $object->$function($value);
//                         }
//                     }
//                 }
//             }else trigger_error("Class `$model` has no member: ". $args["id"], E_USER_ERROR);
//         }
//         
//         render_view('json', array('array' => array()));
//     }
// }

?>