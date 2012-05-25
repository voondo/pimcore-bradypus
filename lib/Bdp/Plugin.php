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

class Bdp_Plugin extends Pimcore_API_Plugin_Abstract implements Pimcore_API_Plugin_Interface {

  public static function version()
  {
    return '1.99.0';
  }

  public function preUpdateObject( $object )
  {
    try {
      $defs = $object->getClass()->getFieldDefinitions();
      if(isset($defs)){
        foreach($defs as $def){
          if($def instanceOf Object_Class_Data_Wysiwyg){

            $name = $def->getName();
            $getter = 'get'.ucfirst($name);
            $setter = 'set'.ucfirst($name);

            $data = $object->$getter();
            $data = Bdp_DOM_Tidy::html($data);
            $object->$setter($data);
          }
        }
      }
    } catch(Exception $e){
      dump((string) $e, true);
      throw $e;
    }
  }

  public function preUpdateDocument( $document )
  {
    try{
      $els = $document->getElements();
//       dump('ooo');
      foreach($els as $el){
        $class = get_class($el);
        $data = $ref = $el->getData();
// dump($class);
        if($class == 'Document_Tag_Wysiwyg'){
          $data = Bdp_DOM_Tidy::html($data);
        }
        if($class == 'Document_Tag_Multihref'){

        }

        if($data != $ref){
          $el->setDataFromEditmode($data);
//           $el->save();
        }
      }
    } catch(Exception $e){
      dump((string) $e, true);
      throw $e;
    }
  }

    public function preDispatch(  )
    {
      $bdpViewHelper = new Bdp_Controller_Action_Helper_ViewRenderer();
      Zend_Controller_Action_HelperBroker::addHelper($bdpViewHelper);
      $front = Zend_Controller_Front::getInstance();
      $loader = Zend_Loader_Autoloader::getInstance();
      $websiteLoader = new Zend_Application_Module_Autoloader(array(
        'basePath'  => 'website',
        'namespace' => 'Website',
      ));

      if(PIMCORE_DEBUG){
        new Bdp_Context_Dev();
      } else {
        new Bdp_Context_Production();
      }
    }

    public static function needsReloadAfterInstall() {
        return false;
    }

    public static function install() {
        // we need a simple way to indicate that the plugin is installed, so we'll create a directory
        $path = self::getInstallPath();

        if(!is_dir($path)) {
            mkdir($path);
        }

        if (self::isInstalled()) {
            return "Bradypus Plugin successfully installed.";
        } else {
            return "Bradypus Plugin could not be installed";
        }
    }

    public static function uninstall() {
        rmdir(self::getInstallPath());

        if (!self::isInstalled()) {
            return "Bradypus Plugin successfully uninstalled.";
        } else {
            return "Bradypus Plugin could not be uninstalled";
        }
    }

    public static function isInstalled() {
        return is_dir(self::getInstallPath());
    }

    public static function getTranslationFile($language) {

    }

    public static function getInstallPath() {
        return PIMCORE_PLUGINS_PATH."/Bdp/install";
    }

}

function dumph($var){
  if($var instanceOf Bdp_DOM){
    echo "DOM length : ".$var->length()."<hr/>";
    foreach($var as $el){
      echo $el.' : '.htmlentities($el->xml())."<hr/>";
    }

  } elseif($var instanceOf DOMNode){
    dump(htmlentities($var->ownerDocument->saveXml($var)));
  } else {
    dump(htmlentities($var));
  }
}
function dump($var, $silent=false){
  ob_start();
  echo '<pre class="debug">';
  var_dump($var);
  echo '</pre>';
  $res = ob_get_clean();

  $req = Zend_Controller_Front::getInstance()->getRequest();
  if(!$silent && (!isset($req) || $req->getModuleName() == PIMCORE_FRONTEND_MODULE)){echo $res;
  @ob_end_flush();} else {
  file_put_contents('plugins/Bdp/install/dump.log', $res, FILE_APPEND);
  }
}
function backtrace($msg=null, $silent=false){
  ob_start();
  echo new Bdp_Exception_Backtrace($msg)."\n\n";
  $res = ob_get_clean();
  file_put_contents('plugins/Bdp/install/dump.log', $res, FILE_APPEND);
  if(!$silent && (!isset($req) || $req->getModuleName() == PIMCORE_FRONTEND_MODULE)){echo $res;
  @ob_end_flush();} else {
  file_put_contents('plugins/Bdp/install/dump.log', $res, FILE_APPEND);
  }
}

if(PHP_SAPI=='cli'){
  Bdp_Plugin::preDispatch();
}