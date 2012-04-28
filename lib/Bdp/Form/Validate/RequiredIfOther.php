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
class Bdp_Form_Validate_RequiredIfOther extends Zend_Validate_Abstract {

    const REQUIRED_EQUALS = 'equals';

    protected $_options;
    protected $_me;
    protected $_form;

    protected $_messageTemplates = array(
        self::REQUIRED_EQUALS => 'Value is required and can\'t be empty'
    );

    public function __construct( Zend_Form $form, $me, $options )
    {
      $this->_form = $form;
      $this->_me = $me;
      $this->_options = $options;
    }

    public function isValid( $value )
    {
      $this->_setValue( $value );
      $me = $this->_me();
      $other_val = $this->_other()->getValue();

      $test = $this->_options['test'];
      switch($test){
        case self::REQUIRED_EQUALS:
          if($other_val == $this->_options['value']){
            if($value===''){
              $this->_error( self::REQUIRED_EQUALS );
              return false;
            }
          }
          return true;
        break;
          assert('false; // Unkown test option');
          return false;
      }
    }

    private function _me()
    {
      return $this->_getElement($this->_me);
    }

    private function _other()
    {
      return $this->_getElement($this->_options['other']);
    }

    private function _getElement($name)
    {
      return $this->_form->getElement($name);
    }

}