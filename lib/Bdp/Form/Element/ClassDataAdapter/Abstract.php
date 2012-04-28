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
 * @package Bdp_Form
 */
abstract class Bdp_Form_Element_ClassDataAdapter_Abstract extends Bdp_Object
{
  public static function factory(Bdp_Form $form, $type, $name, array $options, Object_Class_Data $field)
  {
    $fieldtype = $field->getFieldtype();

    $class = 'Bdp_Form_Element_ClassDataAdapter_'.ucfirst($fieldtype);

    $options = array_merge(array(
      'label' => $field->getTitle()
      ), $options);


    $adapter = parent::factory($class, $form, $type, $name, $options, $field);

    return $adapter;
  }

  protected $_form;
  protected $_name;
  protected $_options;
  protected $_field;

  public final function __construct(Bdp_Form $form, $type, $name, array $options, Object_Class_Data $field)
  {
    $this->_form = $form;
    $this->_name = $name;
    $this->_options = $options;
    $this->_field = $field;
    isset($type) && $this->_type = $type;
    $this->init();
  }

  public function init()
  {
    // OVERLOAD ME
  }

  public function type()
  {
    assert('!empty($this->_type)');
    return $this->_type;
  }

  public function options()
  {
    return $this->_options;
  }

  protected function _setOption()
  {
    $args = func_get_args();

    $val = array_pop($args);

    $cur =& $this->_options;
    $i = count($args);
    foreach($args as $key){
      if(--$i>0){
        if(!isset($cur[$key])){
          $cur[$key] = array();
        }
      } else {
        $cur[$key] = $val;
      }
      $cur =& $cur[$key];
    }
  }

  public function update( Pimcore_Model_Abstract $object, $name, $value ){
    $method = 'set'.ucfirst($name);
    $object->$method($value);
  }
}