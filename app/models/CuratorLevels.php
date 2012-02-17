<?php
namespace php_active_record;

class CuratorLevel extends ActiveRecord
{

  public static function master_curator()
  {
      return CuratorLevel::find_or_create_by_label('Master Curator');
  }
  
  public static function assistant_curator()
  {
      return CuratorLevel::find_or_create_by_label('Assistant Curator');
  }
  
  public static function full_curator()
  {
      return CuratorLevel::find_or_create_by_label('Full Curator');
  }

  public static function curator_ids()
  {
      return array(CuratorLevel::master_curator()->id, CuratorLevel::assistant_curator()->id, CuratorLevel::full_curator()->id);
  }

}

?>