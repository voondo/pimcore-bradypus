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
class Bdp_View_Helper_FormDate extends Zend_View_Helper_FormElement
{
  private static $_formated_parts_map = array(
    'd' => 'day',
    'MMM' => 'month',
    'MMMM' => 'month',
    'y' => 'year'
  );

  public function formDate($name, $value = null, $attribs = null)
  {
    $info = $this->_getInfo($name, $value, $attribs);
    extract($info); // name, value, attribs, options, listsep, disable
    $dateArray = $attribs['dateArray'];
    unset($attribs['dateArray']);

//     // Do we have a value?
//     if (isset($value) && !empty($value)) {
//         $value = ' value="' . $this->view->escape($value) . '"';
//     } else {
//         $value = '';
//     }

    // Disabled?
    $disabled = '';
    if ($disable) {
        $disabled = ' disabled="disabled"';
    }

    // XHTML or HTML end tag?
    $endTag = ' />';
    if (($this->view instanceof Zend_View_Abstract) && !$this->view->doctype()->isXhtml()) {
        $endTag= '>';
    }

    $xhtml = array();

    $locale = Zend_Registry::get('Zend_Locale');
    $formats = $locale->getTranslationList('date');
    $fields = $locale->getTranslationList('field');

    $format = $formats['full'];

    //guess separator
    $sep = null;
    $possible_seps = array('/', '.', ' ');
    foreach($possible_seps as $possible_sep){
      if(strpos($format, $possible_sep) !== false){
        $sep = $possible_sep;
        break;
      }
    }
    assert('isset($sep); // Unable to guess the separator');

    $formatted_parts = explode($sep, $format);
//     dump($formatted_parts);die;
    $now = Bdp_Date::now();

    foreach($formatted_parts as $formated_part){
      if(!isset(self::$_formated_parts_map[$formated_part])){
        continue;
      }
      $part = self::$_formated_parts_map[$formated_part];

      $options = array(
        '' => array(
          'label' => $fields[$part],
          'selected' => !isset($value)
          )
      );
      $tmp = Bdp_Date::zero();
      switch($part){
        case 'day':
          for($i=1; $i<=31; ++$i){
            $options[$i] = array(
              'label' => $tmp->setDay($i)->toString($formated_part),
              'selected' => isset($dateArray['day'])
                && $tmp->compareDay($dateArray['day']) === 0
              );
          }
        break;
        case 'month':
          for($i=1; $i<=12; ++$i){
            $options[$i] = array(
              'label' => $tmp->setMonth($i)->toString($formated_part),
              'selected' => isset($dateArray['month'])
                && $tmp->compareMonth($dateArray['month']) === 0
              );
          }
        break;
        case 'year':
          $now_year = $now->get(Zend_Date::YEAR);
          for($i=1900; $i<=$now_year; ++$i){
            $options[$i] = array(
              'label' => $tmp->setYear($i)->toString($formated_part),
              'selected' => isset($dateArray['year'])
                && $tmp->compareYear($dateArray['year']) === 0
              );
          }
        break;
      }
      $options_flat = array();
      foreach($options as $k=>$v){
        $selected = $v['selected']?' selected="selected"':'';
        $options_flat[] = '<option'.$selected.' value="'.$this->view->escape($k).'">'.$v['label'].'</option>';
      }

      $xhtml[] = '<select'
        . ' name="'.$this->view->escape($name).'['.$part.']"'
        . ' id="' . $this->view->escape($id) . '_'.$part.'"'
        . $disabled
        . $this->_htmlAttribs($attribs).' >'
        . implode(' ', $options_flat)
        . '</select>';
    }
    return implode(' ', $xhtml);
  }
}