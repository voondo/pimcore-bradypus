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

abstract class Bdp_View_DomHelper_Abstract extends Bdp_DOM_Aggregate {

  public static function factory($class, Bdp_DOM $el, Bdp_View_Partial $partial)
  {
    $obj = parent::factory($class, $el);
    $obj->partial($partial);
    return $obj;
  }

  public function partial(Bdp_View_Partial $partial)
  {
    $this->partial = $partial;
    $this->view = $partial->view();
  }

  public function internalRun()
  {
  }

  public function externalRun()
  {
  }

  public function cleanUp()
  {
    $this->dom->remove();
  }

  protected function _pimcoreEditableRun( array $opts = null )
  {
    $class = get_class($this);
    $method_name = isset($opts['method_name'])?$opts['method_name']:strtolower(substr($class, strrpos($class, '_')+1));

    $attrs = $this->attrs();
    !isset($opts['default']) || $attrs = array_merge($opts['default'], $attrs);
    !isset($opts['override']) || $attrs = array_merge($attrs, $opts['override']);

    if(isset($opts['first_arg'])){
      $first_arg = $opts['first_arg'];
    } else {
      $first_arg_attr = (isset($opts['first_arg_attr']))?$opts['first_arg_attr']:'name';
      if(!isset($attrs[$first_arg_attr])){
        throw new Bdp_DOM_Exception('Empty mandatory attribute : '.$first_arg_attr, $this->dom);
      }
      $first_arg = $attrs[$first_arg_attr];
      unset($attrs[$first_arg_attr]);
    }

    if(!empty($this->view->prefix) && $first_arg_attr == 'name'){
      $first_arg = $this->view->prefix.'__'.$first_arg;
    }
//     dump('oooo');dump($first_arg_attr); dump($first_arg); dump(isset($opts['default']['prefix']));

    $res = $this->view->$method_name($first_arg, $attrs);
// dumph($res);
//     if($this instanceOf Bdp_View_Helper_Multihref){dump($first_arg); dumph($res);die;}

    if(isset($res)){
      if($this->view->editmode){
        if(!$res instanceOf Bdp_View_Partial){
          $res = $this->dom->fragment(Bdp_DOM_Tidy::html($res));
        }
//         dumph($res);die;
//         dump(htmlentities($res->xml()));

      } else {
        $as_html = (isset($opts['as_html']))?$opts['as_html']:false;
        if($as_html){
          $res = $this->dom->fragment($res);
        }
      }
      $this->dom->after( $res );
    }

    return $res;
  }
}