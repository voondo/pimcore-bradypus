<?php
/*
 * Copyright (c) 2007-2012, Romain Lalaut <romain.lalaut@laposte.net>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 * - Neither the name of Voondo nor the names of its contributors
 *   may be used to endorse or promote products derived from this
 *   software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * java.lang.Object like
 *
 * @author Romain Lalaut <romain.lalaut@laposte.net>
 * @package Bdp
 */
class Bdp_Object
{

  public function getReflection()
  {
    return self::reflection($this);
  }

  public static function reflection($obj)
  {
    return new ReflectionObject($obj);
  }

  public function hashCode()
  {
    return spl_object_hash($this);
  }

  public function getClassName()
  {
    return get_class($this);
  }

  public function __toString()
  {
    return $this->getClassName().'@'.$this->hashCode();
  }

  public function __call($m, $p)
  {
    return self::staticCall($this, $m, $p);
  }

  public static function staticCall($obj, $m, $p)
  {
    assert('is_object($obj); // Call to a member function on a non-object');

    $reflection = self::reflection($obj);
    if($reflection->hasMethod($m)){
      return $reflection->getMethod($m)->invokeArgs($obj, $p);
    } else {
      throw new Bdp_Exception('Unknown method : '.$reflection->getName().'::'.$m);
    }
  }

  public static function factory( $class )
  {
    $args = func_get_args();
    array_shift($args);

    $reflection = new ReflectionClass($class);
    return $reflection->newInstanceArgs($args);
  }

  public static function fieldLabel($object, $field) {

    if($object instanceOf Object_Objectbrick_Data_Abstract){
      $def = $object->getDefinition();
    } else {
      $def = $object->getClass();
    }

    $def = $def->getFieldDefinition($field);

    $getter = 'get'.ucfirst($field);
    $value = $object->$getter();
    if($def instanceOf Object_Class_Data_Select){
      $options = $def->getOptions();

      foreach($options as $option){
        if($value == $option['value']){
          return $option['key'];
        }
      }
    } else {
      return $value;
    }

    return null;
  }

  public static function __objectMagicCall($object, $m, $p){
   // 5 == strlen('Label')
    if(substr($m, -5)=='Label'){

      $getter = substr($m, 0, -5);
      $reflection = new ReflectionObject($object);

      if($reflection->hasMethod($getter)){
        $res = $reflection->getMethod($getter)->invokeArgs($object, $p);

        $res = Bdp_Object::fieldLabel($object, lcfirst(substr($getter, 3)));
        return $res;
      }
    } elseif(substr($m, 0, 3)=='has') {

      $name = substr($m, 3);
      $reflection = new ReflectionObject($object);
      $getter = 'get'.$name;
      if($reflection->hasMethod($getter)){
        $res = $reflection->getMethod($getter)->invokeArgs($object, $p);

        return !empty($res);
      }
    } elseif(substr($m, -8)=='Currency') {

      $getter = substr($m, 0, -8);
      $reflection = new ReflectionObject($object);

      if($reflection->hasMethod($getter)){
        $res = $reflection->getMethod($getter)->invokeArgs($object, $p);

        $currency = new Zend_Currency();
        $res = $currency->toCurrency($res);
        return $res;
      }
    }
  }
}