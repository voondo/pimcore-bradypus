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
 * @package Bradypus
 * @subpackage Bdp_DOM
 */
class Bdp_DOM_Tidy extends Bdp_Exception
{
  public static function blindlyCloseElements( $xml )
  {
    return str_replace('>', '/>', $xml);
  }

  public static function html( $xml )
  {
    $xml = (string) $xml;
    //<?xml version="1.0" encoding="windows-1251"?
// dumph($xml, true);
    if(empty($xml))
      return '';

    $encoding = 'UTF-8';
    if($xml[0] === '<' && $xml[1] === '?')
    {
      $pos = strpos($xml, 'encoding=');
      if($pos !== false)
      {
        $pos += 9; // strlen('encoding=');
        $encoding = '';
        while(isset($xml[++$pos]) && $xml[$pos] !== '"' && $xml[$pos] !== "'")
        {
          $encoding .= $xml[$pos];
        }

        $pos = strpos($xml, '?>');
        if($pos!==false){
          $xml = substr($xml, $pos);
        }
      }
    }
    if(strtoupper($encoding) !== 'UTF-8'){
      $xml = iconv( $encoding, 'UTF-8//TRANSLIT//IGNORE', $xml);
    }

    $xml = DOMDocument::loadHtml('<?xml encoding="UTF-8">'.$xml);

    foreach ($xml->childNodes as $item){
        if ($item->nodeType == XML_PI_NODE){
            $xml->removeChild($item); // remove hack
        }
    }
    $xml->encoding = 'UTF-8'; // insert proper
    $xml->substituteEntities = true;
    $xml = $xml->saveXml();


    $xml = Bdp_DOM::simplifyXml($xml);

    $dom = Bdp_DOM::loadString($xml);
    $body = $dom->find('body');
    $dom->find('script')->prependTo($body);
//     dumph($dom);
    $nodes = $body->children();

    foreach($nodes as $node){
      $xml = $node->xml();
      $cur = 0;
      while(($pos = strpos($xml, '<![CDATA[', $cur))!==false){
        $pos2 = strpos($xml, ']]>', $cur+1);
        $xml = substr($xml, 0, $pos).'
//'.substr($xml, $pos, $pos2-$pos).'
//]]>'.substr($xml, $pos2+3);
        $cur = $pos2+7;
      }
      $res[] = $xml;
    }
    return implode("\n", $res);
  }
}

