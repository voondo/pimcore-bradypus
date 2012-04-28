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
class Bdp_Form_Element_Date extends Zend_Form_Element_Xhtml
{
    public $helper = 'formDate';

    protected $_day;
    protected $_month;
    protected $_year;

    public function setDay($value)
    {
        $this->_day = (int) $value;
        return $this;
    }

    public function getDay()
    {
        return $this->_day;
    }

    public function setMonth($value)
    {
        $this->_month = (int) $value;
        return $this;
    }

    public function getMonth()
    {
        return $this->_month;
    }

    public function setYear($value)
    {
        $this->_year = (int) $value;
        return $this;
    }

    public function getYear()
    {
        return $this->_year;
    }

    public function setDate($date)
    {
      if(!$date instanceOf Bdp_Date){
        $date = new Bdp_Date($date);
      }

      $this->setDay($date->get(Bdp_Date::DAY));
      $this->setMonth($date->get(Bdp_Date::MONTH));
      $this->setYear($date->get(Bdp_Date::YEAR));
    }

    public function setValue($value)
    {
      if(isset($value)){
        if(is_int($value)) {
          $this->setDate(Bdp_Date::timestamp($value));
        } elseif(is_string($value) || $value instanceOf Zend_Date){
          $this->setDate($value);
        } elseif(is_array($value)
                && count($value) === 3
                && isset($value['day'])
                && isset($value['month'])
                && isset($value['year'])) {

          foreach($value as $k=>$v){
            if(!empty($v)){
              $method = 'set'.ucfirst($k);
              $this->$method($v);
            }
          }
        } else {
          assert('false; // Invalid date');
        }
      }

      return $this;
    }

    public function getValue()
    {
      $res = array();

      if(isset($this->_day)){
        $res['day'] = $this->_day;
      }
      if(isset($this->_month)){
        $res['month'] = $this->_month;
      }
      if(isset($this->_year)){
        $res['year'] = $this->_year;
      }
      $this->setAttrib('dateArray', $res);
      if(isset($this->_day) && isset($this->_month) && isset($this->_year)){
        return new Bdp_Date($res);
      } else {
        return null;
      }
    }
}