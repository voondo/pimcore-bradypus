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
class Bdp_View_Partial extends Bdp_DOM_Aggregate
{
    /**
      * @var bool
      */
    private $is_rendered = false;

    /**
      * @var string
      */
    private $path;

    /**
      * @var Bdp_View
      */
    protected $view;

    /**
      * @var DOMDocument
      */
    private $doc;

    /**
      * @var array
      */
    private $helpers;

    /**
      * @var array
      */
    private static $registry;

    /**
      */
    public static function factory( $path, Bdp_View $view )
    {
      $id = $path.'_'.$view->getVarsHash();
//       dump($id, true);

      if(!isset(self::$registry[$id])){

        if(!file_exists($path)){
          throw new Bdp_Exception('File not found : '.$path);
        }

        $tag_key = 'bdp_partial__'.hash('sha1', $path).'_'.str_replace(array('.', '-'), '_', basename($path));
        $cache_key = $tag_key.'__'.filemtime($path);
        if (!$data = Pimcore_Model_Cache::load($cache_key)){

          $contents = file_get_contents($path);
          $contents = Bdp_DOM::simplifyXml($contents);
          preg_match_all('/<bdp-([^ ]+)[^>]*\/?>/u', $contents, $matches);

          $helpers = array();
          foreach($matches[1] as $match){
            $helpers[] = $match;
          }

          $helpers = array_unique($helpers);

          $data = array(
            'helpers' => $helpers,
            'xml' => $contents
            );
          Pimcore_Model_Cache::getInstance()->clean(Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array($tag_key));
          Pimcore_Model_Cache::save($data, $cache_key, array($tag_key), null);
        }

        $el = Bdp_DOM::loadString($data['xml']);

        $obj = parent::factory(__CLASS__, $el);
        $obj->path = $path;
        $obj->helpers = $data['helpers'];
        $obj->view = $view;

        self::$registry[$id] = $obj;
      }
      return self::$registry[$id];
    }

    public function __toString()
    {
      return $this->renderAsString();
    }

    /**
      * @return string
      */
    public function render()
    {
      if($this->is_rendered){
        return $this;
      }
      $this->is_rendered = true;

      $view = $this->view;

      $helpers = array();
      foreach($this->helpers as $helper){

        $helper_els = $this->find('bdp-'.$helper);

        foreach($helper_els as $helper_el){
          $tagname = $helper_el->tagName();
          $name = substr($tagname, 4); // 4 == strlen('bdp-')
          $class = Bdp_Tool::nameToClass($name, 'Bdp_View_DomHelper_:name');
          if(!class_exists($class)){
            $class = 'Bdp_View_DomHelper_Default';
          }

          $helper = Bdp_View_DomHelper_Abstract::factory($class, $helper_el, $this);
          $helper->internalRun();
          $helpers[] = $helper;
        }
      }

      foreach($helpers as $helper){
        $helper->externalRun();
        $helper->cleanUp();
      }

      if($this->tagName()=='html'){

        $head_el = $this->children('head:first-child');

        $res = (string) $view->headLink();
        $res = Bdp_DOM_Tidy::blindlyCloseElements($res);
        $head_el->append( $this->fragment($res) );

        $res = (string) $view->headScript();
//         $res = Bdp_DOM_Tidy::blindlyCloseElements($res);
        $head_el->append( $this->fragment($res) );
      }

      $implicit_helper_path = $this->path.'.php';
      if(file_exists($implicit_helper_path)){
        require $implicit_helper_path;
      }

      return $this;
    }

    public function renderAsString()
    {
      $this->render();
      $doc = $this->dom->doc();

      $res = $doc->saveXml($doc->documentElement, LIBXML_NOEMPTYTAG );
//       backtrace($this->path);
      if(strpos($this->path, 'layouts/')!==false){
        $res = $this->view->doctype()."\n".$res;
      }
      $res = str_replace('<br></br>', '<br/>', $res); // too bad...
      return $res;
    }

    public function view()
    {
      return $this->view;
    }
}
