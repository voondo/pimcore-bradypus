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
 */
class Bdp_Exception extends Exception
{
  protected $_displayIncludedFiles = true;
  protected $userInfo;
  private $sections = array();
  private $_traceLevelsToRemove = 0;

  public function __toString()
  {
          $res = parent::__toString();

          if(!empty($this->userInfo))
                  $res .= "\n\n".$this->userInfo."\n\n";

          foreach($this->sections as $section)
          {
                  if(is_array($section))
                          $res .= implode("\n", $section);
                  else
                          $res .= $section;
                  $res .= "\n\n";
          }

          return html_entity_decode(strip_tags($res));
  }

  public function toHtml()
  {
          return self::toHtmlStatic( $this, $this->_displayIncludedFiles, $this->_traceLevelsToRemove );
  }

  public static function toHtmlStatic( Exception $e = null, $display_included_files = true, $trace_levels_to_remove = 0 )
  {
    try{
      if(!isset($e)){
        $e = new Bdp_Exception();
      }
      $prev = $e->getPrevious();
      if(isset($prev) && $prev instanceOf Bdp_Exception){
        $parent = $e;
        $e = $prev;

        $e->addParentException($parent);
      }

      $xhtml = "<!DOCTYPE html>
<html>
  <head>
  <meta name='robots' content='noindex, nofollow'>
  <meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
  <link rel='stylesheet' href='/plugins/Bdp/static/css/inuit.css' />
  <link rel='stylesheet' href='/plugins/Bdp/static/css/style.css' />
  </head>
  <body style='padding: 0 1em'>
      <h1 style='margin-top: 1em' class='delta'>".self::_formatTitle($e)."</h1>";

        $sections_html = array();
        if(isset($e->userInfo))
        {
                $sections_html[] = self::_section('Debug info', '<pre>'.$e->userInfo.'</pre>');
        }
        $sections_html[] = self::_section( "Exception trace", self::_trace( $e->getTrace(), $trace_levels_to_remove ) );

        if(isset($e->sections)){
          foreach($e->sections as $title=>$content){
            $sections_html[] = self::_section($title, $content);
          }
        }
        if($display_included_files){
          $sections_html[] = self::_arrayToSection('Included files', get_included_files());
        }

        $xhtml .= implode('<hr/>', $sections_html)."
    <div class='footer'>
      Powered by Bradypus ".Bdp_Plugin::version()."
    </div>
  </body>
</html>";

      return $xhtml;
      } catch( Exception $e ){
        echo 'Failure !!';
        die;
      }
  }

  private static function _formatTitle($e)
  {
    return "<i>".get_class($e)."</i> : <strong>".$e->getMessage()."</strong>";
  }

  public function removeTraceLevels( $nb )
  {
    $this->_traceLevelsToRemove = $nb;
  }

  public function addParentException( Exception $e )
  {
          $parent_title = self::_formatTitle($e);

          $this->addToSection( $parent_title, $this->_trace( $e->getTrace(), $this->_displayIncludedFiles ) );
  }

  protected function addToSection( $title, $content )
  {
          if(!isset($this->sections[$title]))
                  $this->sections[$title] = array();

          $this->sections[$title][] = $content;
  }

  protected function addFilePrint( $title, $filename, $lines_error = NULL )
  {
          if(!isset($lines_error))
            $lines_error = array();

          $xhtml =  '<table class="std" cellpadding="0" cellspacing="0">' . "\n";
          $xhtml .= '<tr><th>#</th>'
          . '<th>Line</th></tr>' . "\n";

          if(is_array($filename))
                  $rows = $filename;
          else
                  $rows = file($filename);
          $i = 1;
          foreach($rows as $row)
          {
                  if(isset($lines_error[$i]))
                  {
                          $class = ' class="error"';
                          $msg = $lines_error[$i];
                  }
                  else
                          $class = '';

                  $xhtml .= "<tr$class><td><code>".($i++)."</code></td><td><code>".htmlentities($row)."</code></td></tr>";

                  if(isset($msg))
                  {
                          $xhtml .= "<tr class='error_msg'><td colspan='2'>$msg</td></tr>";
                          unset($msg);
                  }

          }

          $xhtml .= '</table>';

          $this->addToSection( $title, $xhtml );
  }

  private static function _section( $title, $content )
  {
          if(is_array($content))
          {
                  if(count($content)==1)
                          $content = $content[0];
                  else
                          $content = '<ul><li>'.implode('</li><li>', $content).'</li></ul>';
          }

          return "<h2 class='epsilon'>$title</h2><div class='section'>$content</div>";
  }

  private static function _arrayToSection( $title,  $input )
  {

          $lis = '';
          foreach($input as $item)
          {
                  $lis .= "<li>$item</li>";
          }
          return self::_section($title, "<ul>$lis</ul>");
  }

  /**
    * Build and return a xhtml table displaying given trace
    */
  private static function _trace( $trace, $trace_levels_to_remove )
  {
          $xhtml =  '<table class="std" cellpadding="0" cellspacing="0">' . "\n";
          $xhtml .= '<tr><th>#</th>'
          . '<th>Function</th>'
          . '<th>Location</th></tr>' . "\n";

          $k = $trace_levels_to_remove;
          $tot = count($trace);
          foreach ($trace as $k => $v)
          {
            if($k<$trace_levels_to_remove){
              continue;
            }
                  $xhtml .= '<tr><td>' . ($tot-$k) . '</td>'
                          . '<td>';
                  if (!empty($v['class'])) {
                          $xhtml .= $v['class'] . $v['type'];
                  }
                  $xhtml .= $v['function'];
                  $args = array();
                  if (!empty($v['args']))
                  {
                          foreach ($v['args'] as $arg)
                          {
                                  if (is_NULL($arg)) $args[] = 'NULL';
                                  elseif (is_array($arg)) $args[] = 'Array';
                                  elseif (is_object($arg)) $args[] = 'Object('.get_class($arg).')';
                                  elseif (is_bool($arg)) $args[] = $arg ? 'true' : 'false';
                                  elseif (is_int($arg) || is_double($arg)) $args[] = $arg;
                                  else {
                                  $arg = (string)$arg;
                                  $str = htmlspecialchars($arg);
                                  //if (strlen($arg) > 16) $str .= '&hellip;';
                                  $args[] = "'" . $str . "'";
                                  }
                          }
                  }
                  $xhtml .= '(' . implode(', ',$args) . ')'
                          . '</td>'
                          . '<td>' . (isset($v['file']) ? $v['file'] : 'unknown')
                  . ':' . (isset($v['line']) ? $v['line'] : 'unknown')
                  . '</td></tr>' . "\n";
          }

          $xhtml .= '<tr><td align="center">0</td>'
          . '<td>{main}</td>'
          . '<td>&nbsp;</td></tr>' . "\n"
          . '</table>';

          return $xhtml;
  }
}
