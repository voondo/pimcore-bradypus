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
 * @subpackage Bdp_Context
 */
class Bdp_Context_Dev
{
  public function __construct()
  {
    $req = Zend_Controller_Front::getInstance()->getRequest();
    if(!isset($req) || $req->getModuleName() == PIMCORE_FRONTEND_MODULE){
      error_reporting(E_ALL);
    } else {
      error_reporting(E_ERROR | E_WARNING);
    }
    assert_options(ASSERT_ACTIVE,	 1);
    assert_options(ASSERT_WARNING,	0);
    assert_options(ASSERT_BAIL,	   0);
    assert_options(ASSERT_QUIET_EVAL, 0);
    assert_options(ASSERT_CALLBACK,   array($this, 'assertCallback'));
    set_error_handler(array($this,'phpErrorCallback'), E_ALL);
    set_exception_handler(array($this,'manageException'));

    $front = Zend_Controller_Front::getInstance();

    if(isset($front)){
      $front->setParam('noErrorHandler',true)
      ->throwExceptions(true);
    }
  }

  public function manageException( $e, $die = true )
  {
//         echo '<pre>'; echo $e;die;
    while (ob_get_level() > 0) {
        ob_end_flush();
    }
    if(Zend_Controller_Front::getInstance()->getRequest()->getModuleName() != PIMCORE_FRONTEND_MODULE || PHP_SAPI == 'cli' || !class_exists('Exception') || !class_exists('Bdp_Exception'))
    {
      echo $e;

      if(!empty($e->errorInfo) && is_array($e->errorInfo))
      {
          foreach($e->errorInfo as $info)
              echo " * $info\n";
      }
      echo "\n";

      if($die){
        die;
      } else {
        return;
      }
    }

    if(!($e instanceOf Exception)){
      $e = new Bdp_Exception($e);
      $e->removeTraceLevels(2);
    }

    session_write_close();

    if(!headers_sent()){
      header('Content-Type: text/html');
    }

    if($e instanceOf Bdp_Exception){
      echo $e->toHtml();
    } else {
      echo Bdp_Exception::toHtmlStatic($e);
    }
    if($die){
      die;
    }
  }

  public function assertCallback( $file, $line, $message )
  {
      throw new Bdp_Exception('Assertion failed : '.$message);
  }

  public function phpErrorCallback($errno, $errstr, $errfile, $errline)
  {
    if(error_reporting() == 0) { return; }
    if(preg_match('/(DOMDocument)/i', $errstr)){
      return;
    }

    if($errno == 8 && strpos($errfile, '/pimcore')!==false){
      return;
    }

    $msg = 'PHP error #'.$errno.' : '.$errstr;

    self::manageException($msg, true);

  }

  public static function backtrace($msg=null)
  {
    self::manageException(new Bdp_Exception_Backtrace($msg), false);
  }
}

class Bdp_Exception_Backtrace extends Bdp_Exception {
  protected $_displayIncludedFiles = false;
}