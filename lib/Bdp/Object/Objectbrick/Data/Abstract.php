<?php

class Bdp_Object_Objectbrick_Data_Abstract extends Object_Objectbrick_Data_Abstract {


  public function __call($m, $p)
  {
    try {
      parent::__call($m, $p);
    } catch( Exception $e ){
      $res = Bdp_Object::__objectMagicCall($this, $m, $p);
      if(!isset($res)){
        throw $e;
      }
      return $res;
    }
  }
}
