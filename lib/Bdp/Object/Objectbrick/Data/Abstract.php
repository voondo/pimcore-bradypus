<?php

class Bdp_Object_Objectbrick_Data_Abstract extends Object_Objectbrick_Data_Abstract {


  public function __call($m, $p)
  {
    try {
      parent::__call($m, $p);
    } catch( Exception $e ){
      if(strpos($e->getMessage(), 'Call to undefined method')!==false){
        return Bdp_Object::__objectMagicCall($this, $m, $p);
      } else {
        throw $e;
      }
    }
  }
}
