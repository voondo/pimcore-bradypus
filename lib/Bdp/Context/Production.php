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
class Bdp_Context_Production
{
	protected $is_cli;

	public function __construct()
	{
		$this->is_cli = PHP_SAPI == 'cli';

		if(!$this->is_cli)
            ini_set('display_errors', '0');
		libxml_use_internal_errors(true);
		assert_options(ASSERT_ACTIVE,	 0);
		set_error_handler(array($this,'phpErrorCallback'));
		set_exception_handler(array($this,'manageException'));

	}

	public function __destruct()
	{
    		libxml_clear_errors();
	}

	public function preDispatch( Zend_Application_Bootstrap_BootstrapAbstract $bootstrap )
	{
        Bdp_DOM::disableParsingExceptions(true);
	}

	public function postDispatch( Zend_Application_Bootstrap_BootstrapAbstract $bootstrap )
	{

	}

	public function manageException( Exception $e )
	{
		if($this->is_cli)
		{
			echo "\n";
			echo $e;
			echo "\n";
		}
		else
		{
			error_log($e);
			if(!headers_sent())
				header('HTTP/1.1 500 Internal Server Error');

			die('500 Internal Server Error');
		}
	}

	public function phpErrorCallback($errno, $errstr, $errfile, $errline)
	{
        $msg = 'PHP Error: '.$errstr.' in  '.$errfile.':'.$errline;
        if($this->is_cli)
            echo $msg;

		if($errno!==E_STRICT)
            error_log($msg);
	}
}