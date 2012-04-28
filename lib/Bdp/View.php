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
class Bdp_View extends Pimcore_View
{
  public function __construct($config = array())
  {
    parent::__construct($config);

    $this->addHelperPath('Bdp/View/Helper', 'Bdp_View_Helper');
  }

  /**
    * Processes a view script and returns the output.
    *
    * @param string $name The script name to process.
    * @return string The script output.
    */
  public function render($name)
  {
    // find the script file name using the parent private method
    $file = $this->_script($name);

    if(Zend_Controller_Front::getInstance()->getRequest()->getModuleName() != PIMCORE_FRONTEND_MODULE){
      return parent::_run($file);
    }
    if(is_dir($file)){
      throw new Bdp_Exception('Trying to display a directory !');
    }
//     if($name!='front.html'){throw new Exception();}
    return Bdp_View_Partial::factory($file, $this)->render();
  }

  public function getVarsHash()
  {
    $vars = $this->getVars();
    $ctx = hash_init('sha1');

    self::_varHashRecursive($vars, $ctx);

    $hash = hash_final($ctx);
    return $hash;
  }

  private function _varHashRecursive(&$var, &$ctx)
  {
    $str = null;
    if(is_array($var)){
      foreach($var as &$svar){
        self::_varHashRecursive($svar, $ctx);
      }
    } elseif( is_object($var) ) {
      $str = get_class($var).'@'.spl_object_hash($var);
    } else {
      $str = (string) $var;
    }
    if(isset($str)){
      hash_update($ctx, $str);
    }
  }
}