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

class Bdp_View_DomHelper_Image extends Bdp_View_DomHelper_Abstract {

  public function internalRun()
  {
    $override = array();
    $thumbnail = $this->attr('thumbnail');
    if($thumbnail=='auto'){
      $override['thumbnail'] = array(
        'width' => $this->attr('width'),
        'height' => $this->attr('height'),
        'format' => 'png'
        );
    }

    $path = $this->attr('path');
    if(!empty($path)){
      $res = Asset_Image::getByPath($path);

      if(isset($res)){
        if(isset($override['thumbnail'])){
          $thumb = $res->getThumbnailConfig($override['thumbnail']);
          $res = $res->getThumbnail($thumb);
          unset($override['thumbnail']);
        }
        $el = $this->dom->after('<img />')
          ->attr('src', $res)
          ->attr($override);
      }
    } else {
      $this->_pimcoreEditableRun(array(
        'override' => $override,
        'as_html' => true,
        'default' => array(
          'thumbnail' => 'dummy'
          )
        ));
    }
  }
}