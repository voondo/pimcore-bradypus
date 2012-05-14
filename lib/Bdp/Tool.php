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
 * @package Bdp_View
 */
abstract class Bdp_Tool
{
  private static $_name_to_class_inflectors = array();
  public static function nameToClass( $name, $target )
  {
    if(!isset(self::$_name_to_class_inflectors[$target])){
      $inflector = new Zend_Filter_Inflector(':name');
      $inflector->setFilterRule('name', array(
        'Word_DashToCamelCase'
        ));
      $inflector->setTarget($target);
      self::$_name_to_class_inflectors[$target] = $inflector;
    }
    return self::$_name_to_class_inflectors[$target]->filter(array(
      'name' => $name
      ));
  }

  public static function updateObjectFromArray( Object_Concrete $object, array $data )
  {
    $reflection = new ReflectionObject($object);
    foreach($data as $k=>$v){
      $method = 'set'.ucfirst($k);
      if($reflection->hasMethod($method)){
        $object->$method($v);
      }
    }
  }

  public static function getLock($id, $timeout=30)
  {
      $res = Pimcore_Resource::get()->fetchOne('SELECT GET_LOCK("'.$id.'",'.$timeout.')');

      if($res != 1){
          throw new Bdp_Exception('Unable to get the lock #'.$id);
      }
  }

  public static function releaseLock($id)
  {
      Pimcore_Resource::get()->fetchOne('SELECT RELEASE_LOCK("'.$id.'")');
  }

  public static function isFreeLock($id)
  {
      return Pimcore_Resource::get()->fetchOne('SELECT IS_FREE_LOCK("'.$id.'")') == 1;
  }

  private static $_generate_password_default_options = array(
    'vowels' => true,
    'consonants' => true,
    'numbers' => true,
    'strength' => 0,
    'alternate' => true,
    'length' => 9
  );
  public static function generateRandom(array $options = null) {

    $options = array_merge(self::$_generate_password_default_options, $options);

    $strength = $options['strength'];
    $length = $options['length'];
    $vowels = $options['vowels']?'aeuy':'';
    $numbers = $options['numbers']?'23456789':'';
    $consonants = $options['consonants']?'bdghjmnpqrstvz':'';
    $alternate_cpt = 1;
    if (!empty($consonants) && ($strength & 1)) {
      $consonants .= 'BDGHJLMNPQRSTVWXZ';
      ++$alternate_cpt;
    }
    if (!empty($vowels) && ($strength & 2)) {
      $vowels .= "AEUY";
      ++$alternate_cpt;
    }
    if (!empty($consonants) && ($strength & 4)) {
      $consonants .= '@#$%';
      ++$alternate_cpt;
    }

    $alternate = $options['alternate'];

    if($alternate_cpt === 1){
      $alternate = false;
    }

    $password = '';

    if($alternate){
      $alt = mt_rand() % 3;
      $cons_len = strlen($consonants);
      $vow_len = strlen($vowels);
      $num_len = strlen($numbers);
    } else {
      $alt = null;
      $dictionary = $consonants.$vowels.$numbers;
      $dict_len = strlen($dictionary);
    }
    for ($i = 0; $i < $length; $i++) {
      if(isset($alt)){
        if ($alt == 1) {
          $password .= $consonants[(mt_rand() % $cons_len)];
          $alt = 0;
        } elseif($alt == 2) {
          $password .= $vowels[(mt_rand() % $vow_len)];
          $alt = 1;
        } else {
          $password .= $numbers[(mt_rand() % $num_len)];
          $alt = 2;
        }
      } else {
        $password .= $dictionary[mt_rand() % $dict_len];
      }
    }
    return $password;
  }

  private static $_cryptConf = array(
    'data' => array(
      'algorithm' => MCRYPT_RIJNDAEL_256,
      'mode' => MCRYPT_MODE_CBC,
      ),
    'hash' => array(
      'algorithm' => 'whirlpool',
      'length' => 64
      )
    );
  private static function _initCrypt(array $keys)
  {
    $conf = self::$_cryptConf;
    $iv_size = mcrypt_get_iv_size($conf['data']['algorithm'], $conf['data']['mode']);
    $key_size = mcrypt_get_key_size($conf['data']['algorithm'], $conf['data']['mode']);
    $keys['data'] = self::_substrOrPad($keys['data'], $key_size);

    return array($keys, $iv_size, $conf);
  }
  private static function _hashCrypt($data, array $conf, array $keys)
  {
    $res = hash_hmac($conf['hash']['algorithm'], $data, $keys['hash'], true);
    $res = self::_substrOrPad($res, $conf['hash']['length']);
    return $res;
  }
  private static function _substrOrPad($str, $length)
  {
    $diff = strlen($str)-$length;
    if($diff > 0){ // too long
      $str = substr($str, 0, $length);
    } elseif($diff < 0) { // too short
      $str = str_pad($str, $length, "#");
    }
    assert('strlen($str) == $length');
    return $str;
  }
  public static function encrypt($data, array $keys)
  {
    list($keys, $iv_size, $conf) = self::_initCrypt($keys);

    $hash = self::_hashCrypt($data, $conf, $keys);
    $iv = mcrypt_create_iv($iv_size, MCRYPT_DEV_URANDOM);

    $filter = new Zend_Filter_Encrypt(array_merge($conf['data'], array(
        'adapter' => 'mcrypt',
        'key'=> $keys['data'],
        'vector'=> $iv
        )));

    $data = $filter->filter($data);
    $data = base64_encode($hash.$iv.$data);
    return $data;
  }
  public static function decrypt($data, array $keys)
  {
    list($keys, $iv_size, $conf) = self::_initCrypt($keys);

    $data = base64_decode($data);

    $hash = substr($data, 0, $conf['hash']['length']);
    $iv = substr($data, $conf['hash']['length'], $iv_size);
    $data = substr($data, $conf['hash']['length']+$iv_size);
    $filter = new Zend_Filter_Decrypt(array_merge($conf['data'], array(
        'adapter' => 'mcrypt',
        'key'=> $keys['data'],
        'vector'=> $iv
        )));

    $data = $filter->filter($data);
    $data = rtrim($data, "\0");

    if($hash !== self::_hashCrypt($data, $conf, $keys)){
      throw new Bdp_Exception('Invalid hash');
    }
    return $data;
  }
}