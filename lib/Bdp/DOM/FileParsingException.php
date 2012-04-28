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
 * Thanks to http://blog.bitflux.ch
 *
 * @author Romain Lalaut <romain.lalaut@laposte.net>
 * @package Bradypus
 * @subpackage Bdp_DOM
 */
class Bdp_DOM_FileParsingException extends Bdp_DOM_Exception
{
  private $error_lines = array();

  public function __construct($filename, $delete_file = false)
  {
    set_error_handler(array($this,"errorHandler"));
    $dom = new DomDocument();
    $dom->load($filename);
    restore_error_handler();
    parent::__construct("XML Parse Error in $filename");
    $this->addFilePrint('File content', $filename, $this->error_lines);

    if($delete_file)
        unlink($filename);
  }

  public function errorHandler($errno, $errstr, $errfile, $errline)
  {
    $pos = strpos($errstr,"]:") ;
    if ($pos)
    {
            $errstr = substr($errstr,$pos+ 2);
    }

    preg_match('/^(.*) in.*line: ([0-9]*)/', $errstr, $matches);
    if(isset($matches[1]))
    {
            $this->error_lines[$matches[2]] = $matches[1];
    }
  }
}

