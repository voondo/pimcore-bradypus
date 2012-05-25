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
 * @author Romain Lalaut <romain.lalaut@laposte.net>
 * @package Bdp
 */
abstract class Bdp_Object_Concrete extends Object_Concrete
{

  public function save()
  {

    $path = $this->getPath();

    if(empty($path) && !empty(static::$_defaultParentId)){

      $this->setParentId(static::$_defaultParentId);
      $lock_id = $this->getClassName().'KeyGeneration';

      Bdp_Tool::getLock($lock_id);

      $key = $this->_findFreeKey();
      $this->setKey($key);
    }

    $res = parent::save();

    if(isset($lock_id)){
      Bdp_Tool::releaseLock($lock_id);
    }

    return $res;
  }

  public function getModificationDateObject(){
    return Bdp_Date::timestamp( $this->getModificationDate() );
  }

  protected function _keyPath($key)
  {
    return $this->getParent()->getPath().'/'.$key;
  }

  public function getByKey($key)
  {
    return parent::getByPath($this->_keyPath($key));
  }

  protected static $_keyPrefix;
  private function _findFreeKey()
  {
    $i = 0;
    $max_tries = 20;
    do {
      $key = static::$_keyPrefix.'_'.strtoupper(Bdp_Tool::generateRandom(array(
        'length' => 7,
        'alternate' => false
        )));
      $key = ltrim($key, '_');
      $res = $this->getByKey($key);

    } while(isset($res) && ++$i < $max_tries);
    if($i==$max_tries){
      throw new Exception('Unable to find a free key :(((!');
    }
    return $key;
  }

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

  protected static function _serializeKeys()
  {
    throw new Bdp_Exception('Overload me');
  }
  public function securedSerialize()
  {
    return Bdp_Tool::encrypt(serialize($this), static::_serializeKeys());
  }
  public static function securedUnserialize($data)
  {
    return unserialize(Bdp_Tool::decrypt($data, static::_serializeKeys()));
  }

  public function setValue( $k, $v )
  {
    if(strpos($k, '.')===false){
      return parent::setValue($k, $v);
    }

    $k = explode('.', $k);
    assert('count($k) === 3');

    $method = 'get'.ucfirst($k[0]);
    $brick = call_user_func(array($this, $method));

    $method = 'get'.ucfirst($k[1]);
    $brick_data = call_user_func(array($brick, $method));

    if(!isset($brick_data)){
      $class = 'Object_Objectbrick_Data_'.ucfirst($k[1]);
      $brick_data = new $class($this);
    }

    $method = 'set'.ucfirst($k[2]);
    call_user_func(array($brick_data, $method), $v);

    $method = 'set'.ucfirst($k[1]);
    call_user_func(array($brick, $method), $brick_data);
  }
}