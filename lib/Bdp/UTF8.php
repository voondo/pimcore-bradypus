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

class Bdp_UTF8 {

  const ENC = 'UTF-8';

  /**
    * Convert a foreign charset encoding to UTF-8
    */
  static function convert($string, $encoding)
  {

    if (function_exists('iconv')) {
            return iconv($encoding, 'UTF-8//IGNORE', $contents);
    }

    if (function_exists('mb_convert_encoding')) {
            mb_substitute_character('none');
            return mb_convert_encoding($contents, self::ENC, $encoding );
    }

    return $string;
  }

  /**
    * Determine the number of characters of a string
    * Compatible with mb_strlen(), an UTF-8 friendly replacement for strlen()
    */
  static function strlen($str)
  {
    return mb_strlen($str, self::ENC);
  }

  /**
    * Count the number of substring occurances
    * Compatible with mb_substr_count(), an UTF-8 friendly replacement for substr_count()
    */
  static function substr_count($haystack, $needle)
  {
    return mb_substr_count($haystack, $needle);
  }

  /**
    * Return part of a string, length and offset in characters
    * Compatible with mb_substr(), an UTF-8 friendly replacement for substr()
    */
  static function substr($str, $start , $length = null)
  {
    if(!isset($length))
            $length = self::strlen($str)-$start;
    return mb_substr($str, $start, $length, self::ENC);
  }

  /**
    * Return part of a string, length and offset in bytes
    * Compatible with mb_strcut()
    */
  static function strcut($str, $start, $length = null)
  {
    return mb_strcut($str, $start, self::ENC);
  }

  /**
    * Determine the width of a string
    * Compatible with mb_strwidth()
    */
  static function strwidth($str)
  {
    return mb_strwidth($str, self::ENC);
  }

  /**
    * Get truncated string with specified width
    * Compatible with mb_strimwidth()
    */
  static function strimwidth($str, $start, $width, $trimmarker = '')
  {
    return mb_strimwidth($str, $start, $width, $trimmarker, self::ENC);
  }

  /**
    * Find position of last occurance of a string in another string
    * Compatible with mb_strrpos(), an UTF-8 friendly replacement for strrpos()
    */
  static function strrpos($haystack, $needle)
  {
    return mb_strrpos($haystack, $needle, 0, self::ENC);
  }

  /**
    * Find position of first occurance of a string in another string
    * Compatible with mb_strpos(), an UTF-8 friendly replacement for strpos()
    */
  static function strpos($haystack, $needle, $offset = 0)
  {
    return mb_strpos($haystack, $needle, $offset, self::ENC);
  }

  /**
    * Convert a string to lower case
    * Compatible with mb_strtolower(), an UTF-8 friendly replacement for strtolower()
    */
  static function strtolower($str)
  {
    return mb_strtolower($str, self::ENC);
  }

  /**
    * Convert a string to upper case
    * Compatible with mb_strtoupper(), an UTF-8 friendly replacement for strtoupper()
    */
  static function strtoupper($str)
  {
    return mb_strtoupper($str, self::ENC);
  }

  static function ucfirst($str)
  {
    return self::strtoupper(self::charAt($str, 0)).self::substr($str,1);
  }

  static function charAt($str, $i)
  {
    return self::substr( $str, $i, 1 );
  }

  /**
    * Encode a string for use in a MIME header
    * Simplied replacement for mb_encode_mimeheader()
    */
  static function encode_mimeheader($str)
  {
    throw new Exception('to do');
  }

  /**
    * Encode an UTF-8 string with numeric entities
    * Simplied replacement for mb_encode_numericentity()
    */
  static function encode_numericentity($string)
  {
    throw new Exception('to do');
  }
}

?>