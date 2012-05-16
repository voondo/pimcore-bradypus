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
class Bdp_Form extends Zend_Form
{
  protected $name;

  protected $_objectClassesMap = array();

  private static $instances = array();
  private static $last_instance = null;

  public static function factory( $name )
  {
    if(!isset(self::$instances[$name])){
      $class = Bdp_Tool::nameToClass($name, 'Website_Form_:name');

      $form = Bdp_Object::factory($class, array(
        'name' => $name
      ));

      self::$instances[$name] = $form;
      self::$last_instance = $form;
    }

    return self::$instances[$name];
  }

  public static function lastInstance()
  {
    return self::$last_instance;
  }

  public function setName($name)
  {
    $this->name = $name;
  }

  public function getName()
  {
    return $this->name;
  }

  public function init()
  {
    parent::init();

    Zend_Validate::addDefaultNamespaces('Bdp_Validate');

    Zend_Validate_Abstract::setDefaultTranslator(Zend_Registry::get('Zend_Translate'));

    $this->addPrefixPath('Bdp_Form', 'Bdp/Form');
    $this->addPrefixPath('Website_Form', 'Website/Form');
    $config = new Zend_Config_Ini('website/var/config/forms.ini', $this->name);
    $this->setConfig($config);
  }

  public function createElement($type, $name, array $options = null)
  {
    if(!isset($options)){
      $options = array();
    }
    if(strncmp($type, 'class:', 6)===0){ // 6 == strlen('class:')

      $url = parse_url($type);
      $type = null;

      if(!empty($url['query'])){
        $override = array();
        parse_str($url['query'], $override);

        if(isset($override['type'])){
          $type = $override['type'];
        }
      }
      $map_item = array();
      $object_class_name = $url['path'];

      $object_class = Object_Class::getByName($object_class_name);
      assert('isset($object_class); // Object class not found');

      if(isset($override['brick'])){
        assert('strpos($override["brick"], ".")!==false; // it shoulds contain a dot !');
        $tmp = explode('.', $override['brick'], 2);
        $brick_name = array_shift($tmp);
        $brick_type = ucfirst(array_shift($tmp));

        $brick = $object_class->getFieldDefinition($brick_name);

        assert('$brick instanceOf Object_Class_Data_Objectbricks');

        $brick_types = $brick->getAllowedTypes();
        assert('!empty($brick_types); // No types allowed for this brick');
        assert('in_array($brick_type, $brick_types); // Brick type not allowed here');

        $fields_collection = Object_Objectbrick_Definition::getByKey($brick_type);
        $map_item['brick'] = array(
          'name' => $brick_name,
          'type' => $brick_type,
          );
      } else {
        $fields_collection = $object_class;
      }

      $field = $fields_collection->getFieldDefinition($name);
      assert('$field !== false; // Field not found');

      !isset($options['required']) && $options['required'] = $field->getMandatory();

      $adapter = Bdp_Form_Element_ClassDataAdapter_Abstract::factory($this, $type, $name, $options, $field);

      $map_item['adapter'] = $adapter;

      $options = $adapter->options();
      !isset($type) && $type = $adapter->type();

      if(!isset($this->_objectClassesMap[$object_class_name])){
        $this->_objectClassesMap[$object_class_name] = array();
      }
      $this->_objectClassesMap[$object_class_name][$name] = $map_item;
    }

    // the default <br /><br /> as separator is a semantic non-sense...
    if(($type=='radio' || $type=='multiCheckbox') && empty($options['separator'])){
      $options['separator'] = ' ';
    }

    if(isset($options['requiredIfOther'])){
      $options['required'] = true;
      $options['autoInsertNotEmptyValidator'] = false;
      if($type=='checkbox' || $type=="select"){
        $options['registerInArrayValidator'] = false;
      }
      if(!isset($options['validators'])){
        $options['validators'] = array();
      }
      $options['validators'][] = new Bdp_Form_Validate_RequiredIfOther($this, $name, $options['requiredIfOther']);
      $options['bdp-required-if-other'] = Zend_Json::encode($options['requiredIfOther']);
      unset($options['requiredIfOther']);
    }

    $res = parent::createElement($type, $name, $options);

    return $res;
  }

  public function updateObject( Object_Concrete $object )
  {
    $map = $this->_objectToMap( $object );

    $values = $this->getValues();

    foreach($values as $k=>$v){
      if(!isset($map[$k]) || isset($map[$k]['brick'])){
        continue;
      }
      $map[$k]['adapter']->update($object, $k, $v);
    }


    foreach($values as $k=>$v){
      if(!isset($map[$k]) || !isset($map[$k]['brick'])){
        continue;
      }
      $map_item = $map[$k];

      $getter = 'get'.$map_item['brick']['name'];
      $brick = $object->$getter();

      $getter = 'get'.$map_item['brick']['type'];
      $brick_data = $brick->$getter();
      if(!isset($brick_data)){
        $brick_data_class = 'Object_Objectbrick_Data_'.$map_item['brick']['type'];
        $brick_data = new $brick_data_class($object);
      }

      $map_item['adapter']->update($brick_data, $k, $v);
      $setter = 'set'.$map_item['brick']['type'];
      $brick->$setter($brick_data);
    }
  }

  public function setDefaultsFromObject( Object_Concrete $object )
  {
    $map = $this->_objectToMap( $object );
    throw new Exception();
  }

  public function setDefaultsFromObjectBrick( $class, Object_Objectbrick_Data_Abstract $brick_data )
  {
    $map = $this->_classToMap( $class );

    $defaults = array();

    foreach($brick_data as $k=>$v){
      if(isset($map[$k])){
        $defaults[$k] = $v;
      }
    }

    return $this->setDefaults($defaults);
  }

  private function _objectToMap( Object_Concrete $object )
  {
    $class = $object->getClassName();

    return $this->_classToMap($class);
  }

  private function _classToMap( $class )
  {
    assert('isset($this->_objectClassesMap[$class]); // unknown class');
    return $this->_objectClassesMap[$class];
  }

  public function disable( $elements )
  {
    throw new Exception('TODO');
    if(!is_array($elements)){
      $elements = array($elements);
    }

    foreach($elements as $element)
    {
      $element = $this->getElement($element);

    }
  }

  private $_hiddenFormElement = null;
  public function hiddenFormElement()
  {
    if(!isset($this->_hiddenFormElement)){
      $name = $this->_hiddenFormElementName();

      $el = $this->createElement('hidden', $name, array(
        'value' => 1
        ));
      $this->addElement($el);
      $this->_hiddenFormElement = $el;
    }

    return $this->_hiddenFormElement;
  }

  private function _hiddenFormElementName()
  {
    return '__bdp_'.$this->getName();
  }

  public function isValid( $vars )
  {
    if(!isset($vars[$this->_hiddenFormElementName()])){
      return false;
    }

    return parent::isValid($vars);
  }

}