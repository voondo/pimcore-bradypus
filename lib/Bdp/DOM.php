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
 * jQuery's style DOM access class
 *
 * Pseudo-types :
 *
 *  content : content to manipulate
 *    text | Bdp_DOM | DOMNode | DOMNodeList
 *
 *  expr : css element(s) selectors (eg. #my_id)
 *    text
 *
 * @author Romain Lalaut <romain.lalaut@laposte.net>
 * @package Bradypus
 * @subpackage Bdp_DOM
 */
class Bdp_DOM extends Bdp_Object implements IteratorAggregate
{
  const XML_VERSION = '1.0';
  const XML_ENCODING = 'UTF-8';
  const XML_NS = 'http://www.w3.org/XML/1998/namespace';


  public static function escapeAmp( $xml )
  {
    return str_replace('&','&#38;', $xml);
  }

  public static function unEscapeAmp( $xml )
  {
    return str_replace('&#38;','&', $xml);
  }

  public static function escape( $xml )
  {
    return str_replace(array('&', '<'),array('&#38;', '&lt;'), $xml);
  }

  public static function entityDecode( $xml ){
    return html_entity_decode($xml, ENT_NOQUOTES, self::XML_ENCODING);
  }

  public static function simplifyXml( $xml ){
    $xml = trim($xml);
    strncmp($xml, '<?', 2)!==0 || $xml = substr($xml, strpos($xml, '>')+1); // removing xml decl
    $xml = ltrim($xml);
    strncmp($xml, '<!', 2)!==0 || $xml = substr($xml, strpos($xml, '>')+1); // removing doctype
    $xml = ltrim($xml);
    if(empty($xml)){
      return '';
    }
    if($xml[0] === '<'){
      $root = substr($xml, 0, strpos($xml, '>')+1);
      $root_len = strlen($root);
      $ns_start = strpos($root, 'xmlns');
      if($ns_start !== false){
//         dumph($xml);dumph($root);
        $ns_end = strpos($root, $root[$ns_start+6], strpos($root, ' ')+8);
//         dump(strpos($root, ' '));dump($root[$ns_start+6]);
        $root = substr($root, 0, $ns_start-1).substr($root, $ns_end+1);
        $xml = $root.substr($xml, $root_len);
//       dumph($root);dump($ns_start);dump($ns_end);
//       dumph($xml);die;
      }
    }

    return $xml;
  }


  /**
   * Build the DOM tree from a file
   *
   * @param string $xml_path
   * @return Bdp_DOM
   */
  public static function loadFile( $xml_path )
  {
    assert('file_exists($xml_path); // File : '.$xml_path);

    $doc = self::_createDoc();

    if(!@$doc->load($xml_path, LIBXML_NSCLEAN | LIBXML_COMPACT | LIBXML_NOXMLDECL))
      self::_parseError($xml_path);

    return new self( $doc->documentElement );
  }


  /**
   * Build the DOM tree from a string
   *
   * @param string $xml_str
   * @return Bdp_DOM
   */
  public static function loadString( $xml_str )
  {
    $doc = self::_createDoc();

    if(!@$doc->loadXML($xml_str, LIBXML_NSCLEAN | LIBXML_COMPACT | LIBXML_NOXMLDECL))
            self::_parseError($xml_str, true);

    return new self( $doc->documentElement );
  }

  public static function toSelf( $obj, $throw_exception_on_fail = true ){
    if($obj instanceOf self){
      return $obj;
    }
    if($obj instanceOf Bdp_DOM_Aggregate){
      return $obj->dom();
    }
    if($obj instanceOf DOMNode || $obj instanceOf DOMNodeList || $obj instanceOf Bdp_DOM_NodeList ){
      return new self($obj);
    }
    if($throw_exception_on_fail){
      throw new Bdp_DOM_Exception('Unable to transform '.$obj.' into Bdp_DOM');
    } else {
      return null;
    }
  }

  private static $disable_parsing_exceptions = false;
  public static function disableParsingExceptions( $disable_parsing_exceptions )
  {
      self::$disable_parsing_exceptions = $disable_parsing_exceptions;
  }

  private static function _parseError($xml_path, $is_raw_xml = false)
  {
      if(self::$disable_parsing_exceptions) {
          trigger_error('Bad formed xml : '.$xml_path, E_USER_WARNING);
      } else {
          if($is_raw_xml) {
              $xml = $xml_path;
              $xml_path = tempnam(null, __CLASS__);
              file_put_contents($xml_path, '<div>'.$xml.'</div>');
              $delete_file = true;
          }
          else {
            $delete_file = false;
          }
          throw new Bdp_DOM_FileParsingException($xml_path, $delete_file);
      }
  }

  /**
    * @return Bdp_DOM
    */
  public static function createDoc($xml)
  {
      $doc = self::_createDoc();
      $doc->loadXml($xml);
      return new self( $doc->documentElement );
  }

  /**
   * @return DOMDocument
   */
  private static function _createDoc()
  {
    $doc = new DOMDocument(self::XML_VERSION, self::XML_ENCODING);
        $doc->formatOutput = true;
        $doc->preserveWhiteSpace = false;
        return $doc;
  }

  private static function _toSelf( $nodes )
  {
    assert('isset($nodes)');

    if( $nodes instanceOf self )
      return $nodes;
    if( $nodes instanceOf DOMNodeList || $nodes instanceOf Bdp_DOM_NodeList || $nodes instanceOf DOMElement )
      return new self( $nodes );
    if( $nodes instanceOf Bdp_DOM_Aggregate )
      return $nodes->dom();

    throw new Bdp_DOM_Exception('Unmanaged type : '.gettype($nodes));
  }

  private static function _empty( DOMNode $node, $only_class = null )
  {
    foreach( $node->childNodes as $snode ){
      if(!isset($only_class) || $snode instanceOf $only_class){
//         dump(get_class($snode));
        $node->removeChild( $snode );
      }
    }
  }

  private static function _setAttribute( DOMElement $node, $name, $value, $ns = null)
  {
    $value = str_replace('"', '&quot;',$value);
    if(isset($ns))
      $node->setAttribute( $ns, $name, $value );
    else
      $node->setAttribute( $name, $value );
  }

  private static function _explodeSelector( $selector )
  {

    /* selector
    *   : simple_selector [ combinator simple_selector ]*
    *   ;
    */

    $selector_len = strlen($selector);
    $tmp = array();
    $last_simple = null;
    $prev_ws = false;
    for($i=0; $i<$selector_len; ++$i)
    {
        $char = $selector[$i];
        $is_ws = ' ' === $char;
        $is_lt = '>' === $char;

        if(($is_ws||$is_lt) && isset($last_simple))
        {
            $tmp[] = $last_simple;
            $last_simple = null;
        }

        if($is_ws)
            $prev_ws = true;
        elseif($is_lt)
        {
            $tmp[] = $char;
            while(($i+1)<$selector_len && ' '===$selector[$i+1])
                ++$i;
            $prev_ws = false;
        }
        else
        {
            if($prev_ws)
            {
                $tmp[] = ' ';
                $prev_ws = false;
            }
            $last_simple .= $char;
        }
    }
    if(isset($last_simple))
        $tmp[] = $last_simple;

    return $tmp;
  }

  private static function _cssSelectorsToXPath( $selectors, $start_axis = null, $global_function = null )
  {
    if(empty($selectors))
      $selectors = array('*');
    else
      $selectors = explode(',', $selectors);

    $res = array();

    foreach( $selectors as $selector )
    {
      $selector = trim($selector);

      $xpath = '';

      if(isset($start_axis))
        $xpath .= $start_axis.'::';

      $tmp = self::_explodeSelector($selector);

      $j = count($tmp);
      $i = 0;

      while( $i<$j )
      {
        /* simple_selector
        *   : element_name [ HASH | class | attrib | pseudo ]*
        *   | [ HASH | class | attrib | pseudo ]+
        *   ;
        */

        $simple_selector = $tmp[$i];

        $attr = null;
        $node_name = null;

        // If we search an id, ok it's easy
        $pos = strpos($simple_selector, '#');
        if($pos !== false)
        {
          $id = substr($simple_selector, $pos+1 );
          assert('!empty($id); // invalid simple_selector : id expected after a #');

          $attr = '[1 and @id=\''.$id.'\']';
        }
        // If not an id : more complicated...
        else
        {
          // is there a dot ?
          $pos = strpos($simple_selector, '.');
          if($pos !== false)
          {
            $class = substr($simple_selector, $pos+1 );

            assert('!empty($class); // invalid simple_selector : class expected after a dot');

            $attr = '[ (contains( normalize-space( @class ), \' '.$class.' \' ) or
starts-with( normalize-space( @class ), \''.$class.' \') or
substring( normalize-space( @class ), string-length( @class ) - string-length( \''.$class.'\' ) ) = \' '.$class.'\' or
@class = \''.$class.'\') ]';

            if($pos>0)
              $node_name = substr($simple_selector, 0, $pos);
          }
          else
          {
            // is there a [ ?
            $pos = strpos($simple_selector, '[');
            if($pos !== false)
            {
              $expr = substr($simple_selector, $pos+1, -1);
              $expr = explode('=', $expr);
              if(!empty($expr[1]))
              {
                switch($expr[1][0]) // first car
                {
                  // Represents an element with the att attribute whose the value begins with the prefix "val".
                  case '^':
                    $attr = '[starts-with(@'.$expr[0].', "'.substr($expr[1],1).'")]';
                  break;

                  // Represents an element with the att attribute whose the value ends with the suffix "val".
                  case '$':
                    $attr = '[ends-with(@'.$expr[0].', "'.substr($expr[1],1).'")]';
                  break;

                  // Represents an element with the att attribute whose the value contains at least one instance of the substring "val".
                  case '*':
                    $attr = '[contains(@'.$expr[0].', "'.substr($expr[1],1).'")]';
                  break;

                  // Represents an element with the att attribute whose the value is exactly "val".
                  default:
                    $attr = '[@'.$expr[0].'='.str_replace('"', '\'', $expr[1]).']';
                }
              }
              else
                $attr = '[@'.$expr[0].']';

              if($pos>0)
                $node_name = substr($simple_selector, 0, $pos);
            }
            else
            {
              $node_name = $simple_selector;
            }

            ///TODO : E[foo~="warning"] etc.
          }
        }

        // is there a pseudo selector ?
        $pos2 = strpos($simple_selector, ':');
        if($pos2 !== false){
          $expr = substr($simple_selector, $pos2+1);
          switch($expr){
            case 'first-child':
              $pseudo_attr = '1';
            break;
            case 'last-child':
              $pseudo_attr = 'position()=last()';
            break;
            default:
              throw new Bdp_Exception('Unknown pseudo-selector : '.$expr);
          }
          // we remove it
          $attr = str_replace(':'.$expr, '', $attr);

          if(!empty($attr)){
            $attr = substr($attr, 0, -1).' and '.$pseudo_attr.']';
          } else {
            $attr = '['.$pseudo_attr.']';
          }
          if($pos<=0){
            $node_name = substr($simple_selector, 0, $pos2);
          }
        }

        if(!isset($node_name))
          $node_name = '*';

        $xpath .= $node_name;

        if(isset($attr))
          $xpath .= $attr;

        if(isset($tmp[$i+1]))
        {
          $combinator = $tmp[$i+1];

          switch($combinator)
          {
            case ' ': // space char
              $xpath .= '//';
            break;

            case '>':
              $xpath .= '/';
            break;

            default:
              throw new Bdp_DOM_Exception('unknown combinator : '.$combinator);
          }
        }

        $i += 2;
      }

      $res[] = $xpath;
    }

    $res = implode('|', $res);

    if(isset($global_function))
      $res = $global_function.'('.$res.')';
//       dump($res);
    return $res;
  }

  /**
   * @var DOMNodeList
   */
  private $nodes;

  /**
   * @param DOMNodeList|Bdp_DOM_NodeList|DOMNode $nodes
   */
  public function __construct( $nodes = null )
  {
    if( $nodes instanceOf DOMNodeList || $nodes instanceOf Bdp_DOM_NodeList)
      $this->nodes = $nodes;
    else
      $this->nodes = new Bdp_DOM_NodeList( $nodes );
  }

  /**
   * Returns the tagname of the first element in the selection
   * @return string
   */
  public function tagname()
  {
    $node = $this->_firstElement();
    if(isset($node))
      return $node->tagName;

    return null;
  }

  /**
   * Returns the document of the first node in the selection
   * @return DOMDocument
   */
  public function doc()
  {
    return $this->get(0)->ownerDocument;
  }

  /**
   * @return Bdp_DOM
   */
  public function create( $tagname )
  {
    return new self( $this->doc()->createElement( $tagname ) );
  }

  /**
   * @return Bdp_DOM
   */
  public function createAppend( $tagname )
  {
    return $this->create($tagname)->appendTo($this);
  }

  /**
   * @return Bdp_DOM
   */
  public function createPrepend( $tagname )
  {
    return $this->create($tagname)->prependTo($this);
  }

  /**
   * @return Bdp_DOM
   */
  public function createBefore( $tagname )
  {
    return $this->create($tagname)->insertBefore($this);
  }

  /**
   * @return Bdp_DOM
   */
  public function createAfter( $tagname )
  {
    return $this->create($tagname)->insertAfter($this);
  }

  /**
   * @return DOMDocumentFragment
   */
  public function fragment( $xml )
  {
    $xml = self::entityDecode($xml);

    if(empty($xml)){
      return $this->createText('');
    }

    $res = $this->doc()->createDocumentFragment();

    if(!$res->appendXML( $xml ))
            self::_parseError($xml, true);

    return $res;
  }

  /**
   * @return DOMDocumentFragment
   */
  public function createText( $text )
  {
    return $this->doc()->createTextNode( $text );
  }

  /**
   * @return Bdp_DOM
   */
  public function import( $content )
  {
    $nodes = $this->_contentToNodeList( $content );

    $res = new Bdp_DOM_NodeList();
    $doc = $this->doc();

    foreach($nodes as $node)
    {
      $res->push( $doc->importNode( $node, true ));
    }

    return new self( $res );
  }

  /**
   * @return bool
   */
  public function hasAttribute( $name )
  {
    $nodes = $this->nodes;

    foreach($nodes as $node)
    {
      if($node->hasAttribute($name))
        return true;
    }

    return false;
  }

  /**
   * @return Bdp_DOM
   */
  public function removeAttr( $name )
  {
    $nodes = $this->nodes;

    foreach($nodes as $node)
    {
      $node->removeAttribute($name);
    }

    return $this;
  }

  /**
    * @return bool
    */
  public function isEmpty()
  {
      return $this->nodes->length === 0;
  }

  /**
   * @return Bdp_DOM
   */
  public function rename( $tagname )
  {
        $nodes = $this->nodes;
        $res = new Bdp_DOM_NodeList();

        foreach($nodes as $old_dom)
        {
            $new_dom = $old_dom->ownerDocument->createElement($tagname);

            if ($old_dom->attributes->length)
            {
                foreach ($old_dom->attributes as $attribute)
                    $new_dom->setAttribute($attribute->nodeName, $attribute->nodeValue);
            }

            foreach($old_dom->childNodes as $child)
                $new_dom->appendChild($child->cloneNode(true));

            $old_dom->parentNode->replaceChild($new_dom, $old_dom);

            $res->push($new_dom);
        }

        return new self( $res );
  }

    /**
     * @param string|Bdp_DOM_Aggregate $aggregate
     *
     * @return Bdp_DOM_Aggregate
     */
    public function toAggregate( $aggregate )
    {
        return self::_aggregateToInstance( $aggregate )->dom( $this );
    }

    /**
     * @param string|Bdp_DOM_Aggregate $aggregate
     *
     * @return SplFixedArray
     */
    public function toAggregates( $aggregate )
    {
        $res = new SplFixedArray( $this->nodes->length );

        $i = 0;
        foreach($this as $dom)
            $res[$i++] = self::_aggregateToInstance( $aggregate )->dom( $dom );

        return $res;
    }

    private static function _aggregateToInstance( $aggregate )
    {
        if(is_string($aggregate))
            $aggregate = new $aggregate();

        assert('$aggregate instanceOf Bdp_DOM_Aggregate');

        return $aggregate;
    }

  /**
   * @return bool
   */
  public function __toString()
  {
    return print_r($this, true);
  }

  //
  // Implementation of IteratorAggregate
  //

  public function getIterator()
  {
    if($this->nodes instanceOf DOMNodeList)
      return new Bdp_DOM_NativeIterator( $this->nodes );
    else
      return new Bdp_DOM_InternalIterator( $this->nodes );
  }

  //
  // Some "magic" methods
  //

  public function __get( $name )
  {
    return $this->attr( $name );
  }

  public function __set( $name, $value )
  {
    return $this->attr( $name, $value );
  }

  public function __isset( $name )
  {
    return $this->hasAttribute( $name );
  }

  public function __unset( $name )
  {
    return $this->removeAttribute( $name );
  }

  //
  // Internal private methods
  //

  /**
   * @return DOMElement
   */
  private function _firstElement()
  {
    $nodes = $this->nodes;

    foreach($nodes as $node)
    {
      if($node instanceOf DOMElement)
        return $node;
    }
  }

  /**
   * @return DOMXPath
   */
  private function _xpath()
  {
    $doc = $this->doc();
    $xpath = new DOMXPath( $doc );

    return $xpath;
  }

  /**
   * @return DOMNodeList
   */
  private function _xpathQuery( $expr )
  {
    $xpath = $this->_xpath();

    $nodes = $this->nodes;
    if($nodes->length === 1)
    {
      return $xpath->query( $expr, $nodes->item(0) );
    }
    else
    {
      $res = new Bdp_DOM_NodeList();

      foreach( $nodes as $node )
      {
        $res->merge( $xpath->query( $expr, $node ) );
      }

      return $res;
    }

  }

  /**
   * @return mixed
   */
  private function _xpathEvaluate( $expr )
  {
    $xpath = $this->_xpath();

    $nodes = $this->nodes;
    $res = array();

    foreach( $nodes as $node )
    {
      $res[] = array(
        'el' => $node,
        'eval' => $xpath->evaluate( $expr, $node )
        );
    }

    return $res;
  }

  /**
   * @return DOMNodeList
   */
  private function _query( $expr = null, $start_location = null )
  {
    $expr = self::_cssSelectorsToXPath( $expr, $start_location, null);

    return $this->_xpathQuery( $expr );
  }

  /**
   * @return DOMNodeList
   */
  private function _evaluate( $expr = null, $start_location = null, $global_function = null )
  {
    $expr = self::_cssSelectorsToXPath( $expr, $start_location, $global_function );

    return $this->_xpathEvaluate( $expr );
  }

  /**
   * @return DOMNodeList
   */
  private function _contentToNodeList( $content )
  {
    if($content instanceOf self)
      return $this->_importNodesIfNeeded($content->nodes);

    if($content instanceOf DOMNodeList || $content instanceOf Bdp_DOM_NodeList)
      return $this->_importNodesIfNeeded($content);

    if($content instanceOf DOMNode)
    {
      $doc = $this->doc();

      if($content->ownerDocument !== $doc)
        $content = $doc->importNode( $content, true );

      $res = new Bdp_DOM_NodeList();
      $res->push( $content );
      return $res;
    }

    if($content instanceOf Bdp_DOM_Aggregate)
      return $this->_importNodesIfNeeded( $content->dom()->nodes );

    $content = (string) $content;
    if($content !== '' && $content[0] === '<'){
      $content = $this->fragment($content);
    } else {
      $content = $this->doc()->createTextNode((string) $content);
    }

    $res = new Bdp_DOM_NodeList();
    $res->push( $content );
    return $res;
  }

  private function _importNodesIfNeeded( $nodes )
  {
    if($this->isEmpty())
      return $nodes;

    $res = new Bdp_DOM_NodeList();

    $doc = $this->doc();

    foreach( $nodes as $node )
    {
      if($node->ownerDocument !== $doc)
        $node = $doc->importNode( $node, true );

      $res->push($node);
    }

    return $res;
  }

  //
  // jQuery's style public API
  //

  /**
   * The number of elements in the jQuery object.
   *
   * @return int
   */
  public function length()
  {
    return $this->nodes->length;
  }

  /**
   * Reduce the set of matched elements to a single element.
   *
   * @param int $position
   * @return Bdp_DOM
   */
  public function eq( $position )
  {
    return new self( $this->get( $position ) );
  }

  /**
   * Access all matched DOM elements or, if index given, a single matched DOM element
   *
   * @param int $index
   * @return DOMNodeList|DOMNode
   */
  public function get( $index = null )
  {
    if(isset($index)){
      if($this->nodes->length <= $index){
        throw new Bdp_DOM_InvalidOffsetException();
      }
      return $this->nodes->item( $index );
    } else {
      return $this->nodes;
    }
  }

  /**
   * Searches every matched element for the object and returns the index of the element, if found, starting with zero.
   *
   * @param DOMElement $subject
   */
  public function index( DOMNode $dom )
  {
    $nodes = $this->nodes;

    $i = 0;
    foreach( $nodes as $node ){
      if( $node === $dom )
        return $i;
      ++$i;
    }

    return null;
  }

  //
  // Attributes :
  //

  /**
   *  - attr( string $name )
   *     Access a property on the first matched element. This method makes it easy to retrieve a property value
   *     from the first matched element. If the element does not have an attribute with such a name, null is
   *     returned.
   *
   *  - attr( array $properties )
   *     Set a key/value object as properties to all matched elements. This serves as the best way to set a large
   *     number of properties on all matched elements.
   *
   *  - attr( string $name, string $value )
   *     Set a single property to a value, on all matched element
   *
   * @param string|array $p1
   * @param string $p2
   */
  public function attr( $p1, $p2 = null )
  {
    $nodes = $this->nodes;

    if(!isset($p2)){
      if(is_string($p1)){
        foreach($nodes as $node){
          if($node instanceOf DOMElement){
            $val = $node->getAttribute( $p1 );

            if($val==='')
                return null;
            return html_entity_decode($val, ENT_NOQUOTES, self::XML_ENCODING);
          }
        }

        return null;
      }
      else { //if is_array
        foreach($nodes as $node)
        {
          foreach($p1 as $k=>$v)
            if($node instanceOf DOMElement)
              self::_setAttribute( $node, $k, $v );
        }

        return $this;
      }
    } else {
      foreach( $nodes as $node ){
        if($node instanceOf DOMElement)
          self::_setAttribute( $node, $p1, $p2 );
        elseif($node instanceOf DOMDocumentFragment)
          self::_setAttribute( $node->childNodes->item(0), $p1, $p2 );
      }

      return $this;
    }
  }

  public function attrs( array $attrs = null )
  {
    $nodes = $this->nodes;

    if(isset($attrs)){
      throw new Exception('Not implemented');
    } else {

      $res = array();
      $node = $this->get(0);
      if(isset($node)){
        foreach($node->attributes as $attr_node) {
          $res[$attr_node->name] = $attr_node->value;
        }
      }

      return $res;
    }
  }

  /**
   * Adds the specified class(es) to each of the set of matched elements.
   *
   * @param string $class
   * @return Bdp_DOM
   */
  public function addClass( $class )
  {
    $tmp = $this->attr('class');

    if(!empty($tmp)){
      $tmp .= ' ';
    }

    $tmp .= $class;

    $this->attr('class', $tmp);

    return $this;
  }

  /**
   * Returns true if the specified class is present on at least one of the set of matched elements.
   *
   * @param string $class
   * @return bool
   */
  public function hasClass( $class )
  {
    $nodes = $this->nodes;

    foreach( $nodes as $node ){
      if($node instanceOf DOMElement){
        $tmp = $node->getAttribute('class');

        if(strpos($tmp, $class)!==false)
          return true;
      }
    }

    return false;
  }

  /**
   * Removes all or the specified class(es) from the set of matched elements.
   *
   * @param string $class
   * @return Bdp_DOM
   */
  public function removeClass( $class )
  {
    $nodes = $this->nodes;

    foreach( $nodes as $node ){
      if($node instanceOf DOMElement){
        $tmp = $node->getAttribute('class');

        if(($res = strpos($tmp, $class))!==false){ // FIXME : removeClass('foo') will break if class="foobar foo"
          $tmp2 = '';
          if($res > 0){
            $tmp2 .= substr( $tmp, 0, $res );
          }

          $tmp3 = $res+strlen($class);
          if($tmp3 < strlen($tmp)){
            $tmp2 .= substr( $tmp, $tmp3+1 );
          }

          $tmp2 = trim($tmp2);

          if(empty($tmp2)){
            $node->removeAttribute('class');
          } else {
            $node->setAttribute('class', $tmp2);
          }
        }
      }
    }

    return $this;
  }

  /**
   * Adds the specified class if it is not present, removes the specified class if it is present.
   *
   * @param string $class
   * @return Bdp_DOM
   */
  public function toggleClass( $class )
  {
    if($this->hasClass($class))
      $this->removeClass($class);
    else
      $this->addClass($class);

    return $this;
  }

  /**
   *  - xml()
   *     Get the xml of the first matched element.
   *
   *  - xml( string $val )
   *     Set the xml contents of every matched element.
   *
   * @param string $val
   * @return string|Bdp_DOM
   */
  public function xml( $val = null )
  {
    if(!isset($val)){
      $dom = $this->get(0);
      $res = $dom->ownerDocument->saveXML( $dom, LIBXML_NOEMPTYTAG );

      return $res;
    }
    elseif($val !== ''){
      $fragment = $this->fragment( $val );

      $nodes = $this->nodes;

      foreach( $nodes as $node ){
        self::_empty( $node );

        $node->appendChild( $fragment );
      }
    }

    return $this;
  }

  /**
   *  - text()
   *     Get the combined text contents of all matched elements.
   *
   *  - text( string $val )
   *     Set the text contents of all matched elements.
   *
   * @param string $val
   * @return string|Bdp_DOM
   */
  public function text( $val = null )
  {
    $nodes = $this->nodes;

    if(!isset($val)){
      $res = '';
      foreach( $nodes as $node ){
        $res .= $node->textContent;
      }

      return html_entity_decode($res, ENT_NOQUOTES, self::XML_ENCODING);
    } else {
      foreach( $nodes as $node ){
        self::_empty( $node, 'DOMText' );

        $tnode = $node->ownerDocument->createTextNode( $val );
        if(isset($tnode)){
          $node->appendChild( $tnode );
        }
      }

      return $this;
    }
  }

  /**
   *  - val()
   *     Get the content of the value attribute of the first matched element.
   *
   *  - val( string $val )
   *     Set the value attribute of every matched element.
   *
   * @param string $val
   * @return string|Bdp_DOM
   */
  public function val( $val = null )
  {
    if(!isset($val)){
      return $this->attr('value');
    } else {
      return $this->attr('value', $val);
    }
  }

  //
  // Traversing
  //

  // filtering :

  /**
   * Removes all elements from the set of matched elements that do not match the specified expression(s).
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function filter( $expr )
  {
    $evals = $this->_evaluate( $expr, 'self', 'boolean' );
    $res = new Bdp_DOM_NodeList();

    foreach($evals as $eval){
      if($eval['eval']){
        $res->push($eval['el']);
      }
    }

    return new self( $res );
  }

  /**
   * Removes all elements from the set of matched elements that does not match the specified function.
   *
   * @param callback $callback
   * @return Bdp_DOM
   */
  public function filterCallback( $callback )
  {
    $res = new Bdp_DOM_NodeList();

    $nodes = $this->nodes;
    foreach($nodes as $node){
      $tmp = call_user_func( $callback, new self($node) );
      if($tmp){
        $res->push($node);
      }
    }

    return new self( $res );
  }

  /**
   * Checks the current selection against an expression and returns true, if at least one element of the selection fits the given expression.
   *
   * @param expr $expr
   * @return boolean
   */
  public function is( $expr )
  {
    $evals = $this->_evaluate( $expr, 'self', 'boolean' );
    $res = new Bdp_DOM_NodeList();

    foreach($evals as $eval){
      if($eval['eval']){
        return true;
      }
    }

    return false;
  }

  /**
   * Translate a set of elements in the jQuery object into another set of values in an array (which may, or may not, be elements).
   *
   * @param callback $callback
   * @return Bdp_DOM
   */
  public function map( $callback )
  {
    throw new Exception('Not implemented'); ///TODO Implement me !
  }

  /**
   * Removes elements matching the specified expression from the set of matched elements.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function not( $expr )
  {
    $evals = $this->_evaluate( $expr, 'self', 'boolean' );
    $res = new Bdp_DOM_NodeList();

    foreach($evals as $eval)
    {
      if(!$eval['eval'])
        $res->push($eval['el']);
    }

    return new self( $res );
  }

  /**
   * Selects a subset of the matched elements.
   *
   * @param int $start
   * @param int $end
   * @return Bdp_DOM
   */
  public function slice( $start, $end )
  {
    $res = new Bdp_DOM_NodeList();

    $nodes = $this->nodes;
    $i = 0;
    foreach($nodes as $node)
    {
      if($i >= $start && $i <= $end)
      {
        $res->push($node);
      }
      ++$i;
    }

    return new self( $res );
  }

  // Finding:

  /**
   * Adds more elements, matched by the given expression, to the set of matched elements.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function add( $expr = NULL )
  {
    if(is_string($expr))
      $list = $this->_query( $expr, 'descendant', NULL );
    else
      $list = self::_contentToNodeList($expr);

    $res = new Bdp_DOM_NodeList();
    $res->merge( $this->nodes );
    $res->merge( $list );

    return new self( $res );
  }

  /**
   * Get a set of elements containing all of the unique immediate children of each of the matched set of elements.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function children( $expr = null )
  {
    if(isset($expr))
    {
      return new self( $this->_query( $expr, 'child') );
    }
    else
    {
      $res = new Bdp_DOM_NodeList();
      $nodes = $this->nodes;
      foreach($nodes as $node)
      {
        if($node instanceOf DOMElement)
        {
          $children = $node->childNodes;
          foreach($children as $child)
            if($child instanceOf DOMElement)
              $res->push( $child );
        }
      }

      $res = new self( $res );

      return $res;
    }
  }

  /**
   * Searches for all elements in descendant that match the specified expression.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function find( $expr = null )
  {
    // detecting trivial expr which allow to use the native DOM method getElementByTagName()
    if(strpos($expr, '.') === false && strpos($expr, ':') === false && strpos($expr, '#') === false && strpos($expr, ' ') === false && strpos($expr, '[') === false && strpos($expr, ',') === false)
    {
      $res = new Bdp_DOM_NodeList();
      $nodes = $this->nodes;
      foreach($nodes as $node)
      {
        if($node instanceOf DOMElement)
        {
          $res->merge( $node->getElementsByTagName( $expr ) );
        }
      }
      return new self( $res );
    }

    // else we must use xpath...
    return new self( $this->_query( $expr, 'descendant' ) );
  }

  /**
   * Get a set of elements containing the unique next siblings of each of the given set of elements.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function next( $expr = null )
  {
    return new self( $this->_query( $expr.':first-child', 'following-sibling' ) );
  }

  /**
   * Find all sibling elements after the current element.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function nextAll( $expr = null )
  {
    return new self( $this->_query( $expr, 'following-sibling' ) );
  }

  /**
   * Get a set of elements containing the unique parents of the matched set of elements.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function parent( $expr = null )
  {
    if(isset($expr))
      return new self( $this->_query( $expr, 'parent' ) );

    $res = new Bdp_DOM_NodeList();
    $nodes = $this->nodes;

    foreach( $nodes as $node )
    {
      $res->push( $node->parentNode );
    }

    return new self( $res );
  }

  /**
   * Get a set of elements containing the unique ancestors of the matched set of elements (except for the root element).
   *
   * The matched elements can be filtered with an optional expression.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function parents( $expr = null )
  {
    return new self( $this->_query( $expr, 'ancestors') );
  }

  /**
   * Get a set of elements containing the unique closest ancestors of the matched set of elements (except for the root element).
   *
   * The matched elements can be filtered with an optional expression.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function closest( $expr = null )
  {
    return new self( $this->_query( $expr, 'ancestor') );
  }

  /**
   * Get a set of elements containing the unique previous siblings of each of the matched set of elements.
   *
   * @param expr $expr
   * @return string|Bdp_DOM
   */
  public function prev( $expr = null )
  {
    return new self( $this->_query( $expr.':first-child', 'preceding-sibling') );
  }

  /**
   * Find all sibling elements in front of the current element.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function prevAll( $expr = null )
  {
    return new self( $this->_query( $expr, 'preceding-sibling') );
  }

  /**
   * Get a set of elements containing all of the unique siblings of each of the matched set of elements.
   *
   * Can be filtered with an optional expressions.
   *
   * @param expr $expr
   * @return Bdp_DOM
   */
  public function siblings( $expr = null )
  {
    $res = new Bdp_DOM_NodeList();
    $this->_mergeSiblings( $res, $this->prevAll($expr)->nodes );
    $this->_mergeSiblings( $res, $this->nextAll($expr)->nodes );

    return new self( $res );
  }

  private function _mergeSiblings( Bdp_DOM_NodeList $res, $nodes ){

    foreach($nodes as $node){

      if(!$this->contains($node)){
        $res->push($node);
      }
    }
  }

  //
  // Manipulating
  //

  // Inserting Inside:

  /**
   * Append content to the inside of every matched element.
   *
   * @param content $content
   * @return Bdp_DOM
   */
  public function append( $content )
  {
    $dest_els = $this->_contentToNodeList( $content );

    return new self( $this->_append( $this->nodes, $dest_els ) );
  }

  /**
   * Append all of the matched elements to another, specified, set of elements.
   *
   * @param Bdp_DOM $dest
   * @return Bdp_DOM
   */
  public function appendTo( self $dest )
  {
    return new self( $this->_append( $dest->nodes, $this->nodes ) );
  }

  private function _append( $nodes, $dest_els )
  {
    $res = new Bdp_DOM_NodeList();

    foreach( $dest_els as $dest_el )
    {
      foreach($nodes as $node)
      {
        $clone = $dest_el->cloneNode(true);
        $clone = $node->appendChild( $clone );
        $res->push( $clone );
      }

      $parent = $dest_el->parentNode;
      if(isset($parent))
        $parent->removeChild( $dest_el );
    }

    return $res;
  }

  /**
   * Prepend content to the inside of every matched element.
   *
   * @param content $content
   * @return Bdp_DOM
   */
  public function prepend( $content )
  {
    $dest_els = $this->_contentToNodeList( $content );

    return new self( $this->_prepend( $this->nodes, $dest_els ) );
  }

  /**
   * Prepend all of the matched elements to another, specified, set of elements.
   *
   * @param Bdp_DOM $dest
   * @return Bdp_DOM
   */
  public function prependTo( self $dest )
  {
    return new self( $this->_prepend( $dest->nodes, $this->nodes ) );
  }

  private function _prepend( $nodes, $dest_els )
  {
    $res = new Bdp_DOM_NodeList();

    foreach( $dest_els as $dest_el )
    {
      foreach($nodes as $node)
      {
        $clone = $dest_el->cloneNode(true);

        $clone = $node->insertBefore( $clone, $node->childNodes->item(0) );

        $res->push( $clone );
      }

      $parent = $dest_el->parentNode;
      if(isset($parent))
        $parent->removeChild( $dest_el );
    }

    return $res;
  }

  // Inserting Outside:

  /**
   * Insert content after each of the matched elements.
   *
   * @param content $content
   * @return Bdp_DOM
   */
  public function after( $content )
  {
    $dest_els = $this->_contentToNodeList( $content );

    return new self( $this->_after( $this->nodes, $dest_els ) );
  }

  /**
   * Insert all of the matched elements after another, specified, set of elements.
   *
   * @param Bdp_DOM $dest
   * @return Bdp_DOM
   */
  public function insertAfter( self $dest )
  {
    return new self( $this->_after( $dest->nodes, $this->nodes ) );
  }

  private function _after( $nodes, $dest_els )
  {
    $res = new Bdp_DOM_NodeList();

    foreach( $dest_els as $dest_el )
    {
      foreach($nodes as $node)
      {
        $clone = $dest_el->cloneNode(true);

        $parent = $node->parentNode;
        if(!isset($parent))
          throw new Bdp_DOM_Exception('The parent node doesn\'t exist');

        $clone = $parent->insertBefore( $clone, $node->nextSibling );

        $res->push( $clone );
      }

      $parent = $dest_el->parentNode;
      if(isset($parent))
        $parent->removeChild( $dest_el );
    }

    return $res;
  }

  /**
   * Insert content before each of the matched elements.
   *
   * @param content $content
   * @return Bdp_DOM
   */
  public function before( $content )
  {
    $dest_els = $this->_contentToNodeList( $content );

    return new self( $this->_before( $this->nodes, $dest_els ) );
  }

  /**
   * Insert all of the matched elements before another, specified, set of elements.
   *
   * @param Bdp_DOM $content
   * @return Bdp_DOM
   */
  public function insertBefore( self $dest )
  {
    return new self( $this->_before( $dest->nodes, $this->nodes ) );
  }

  private function _before( $nodes, $dest_els )
  {
    $res = new Bdp_DOM_NodeList();

    foreach( $dest_els as $dest_el )
    {
      foreach($nodes as $node)
      {
        $clone = $dest_el->cloneNode(true);

        $parent = $node->parentNode;
        if(!isset($parent))
          throw new Bdp_DOM_Exception('The parent node doesn\'t exist');

        $clone = $parent->insertBefore( $clone, $node );

        $res->push( $clone );
      }

      $parent = $dest_el->parentNode;
      if(isset($parent))
        $parent->removeChild( $dest_el );
    }

    return $res;
  }

  // Inserting Around:

  /**
   * Wrap each matched element with the specified element.
   *
   * @param DOMElement $nodeem
   * @return Bdp_DOM
   */
  public function wrap( DOMElement $nodeem )
  {
    return new self( $this->_wrap( $nodeem, $this->nodes ) );
  }

  /**
   * Wrap the inner child contents of each matched element (including text nodes) with a DOM element.
   *
   * @param string $content
   * @return Bdp_DOM
   */
  public function wrapInner( DOMElement $nodeem )
  {
    $res = new Bdp_DOM_NodeList();

    $nodes = $this->nodes;
    foreach($nodes as $node)
    {
      $tmp = $this->_wrap( $nodeem, $node->childNodes );
      $res->merge( $tmp );
    }

    return new self( $res );
  }

  private function _wrap( $nodeem, $nodes )
  {
    $res = new Bdp_DOM_NodeList();

    foreach($nodes as $node)
    {
      $clone = $nodeem->cloneNode(true);
      $node->parentNode->replaceChild( $clone, $node );
      $clone->appendChild( $node );
      $res->push($clone);
    }

    return $res;
  }

  /**
   * Wrap all the elements in the matched set into a single wrapper element.
   *
   * @param string $content
   * @return Bdp_DOM
   */
  public function wrapAll( DOMElement $nodeem )
  {
    $res = new Bdp_DOM_NodeList();

    $nodes = $this->nodes;
    foreach($nodes as $node)
    {
      if(!isset($clone))
      {
        $clone = $nodeem->cloneNode(true);
        $node->parentNode->replaceChild( $clone, $node );
      }

      $clone->appendChild( $node );
      $res->push($clone);
    }

    return new self( $res );
  }

  // Replacing:

  /**
   * Replaces all matched elements with the specified XML or DOM elements.
   * This returns the JQuery element that was just replaced, which has been removed from the DOM.
   *
   * @param string|DOMElement $content
   * @return Bdp_DOM
   */
  public function replaceWith( $content, $ns_fix = false )
  {
    if(is_string($content))
      $content = $this->fragment($content, $ns_fix);

    $content = self::_contentToNodeList($content);


    $nodes = $this->nodes;

    foreach($nodes as $node)
    {
      foreach($content as $node2)
      {
        $node2= clone $node2;
        $node->parentNode->replaceChild( $node2, $node );
      }
    }

    return new self( $nodes );
  }

  /**
   * Replaces the elements matched by the specified selector with the matched elements.
   *
   * @param string $expr
   * @return Bdp_DOM
   */
  public function replaceAll( $expr )
  {
    return $this;
  }

  // Removing:

  /**
   * Remove all child nodes from the set of matched elements.
   *
   * @param string $content
   * @return Bdp_DOM
   */
  public function removeChildren( )
  {
    $nodes = $this->nodes;
    foreach($nodes as $node)
    {
      self::_empty( $node );
    }

    return $this;
  }

  /**
   * Removes all matched elements from the DOM.
   *
   * @param string $expr A jQuery expression to filter the set of elements to be removed.
   * @return Bdp_DOM
   */
  public function remove( $expr = null )
  {
    if(isset($expr))
      $nodes = $this->filter( $expr )->nodes;
    else
      $nodes = $this->nodes;

    foreach($nodes as $node)
    {
      $parent = $node->parentNode;
      if(isset($parent))
        $parent->removeChild($node);
    }

    return new self( $nodes );
  }

  // Copying:

  /**
   * Clone matched DOM Elements and select the clones.
   *
   * @param string $content
   * @return Bdp_DOM
   */
  public function __clone( )
  {
    $res = new Bdp_DOM_NodeList();

    $nodes = $this->nodes;
    foreach($nodes as $node)
    {
      $res->push( $node->cloneNode(true) );
    }

    $this->nodes = $res;
  }

  /**
   * Clone matched DOM Elements, append them to parent and returns the clones.
   *
   * @return Bdp_DOM
   */
  public function copyTpl()
  {
//   dump("cloning");
// dumph($this);
    $clone = clone $this;
// dump($res->nodes);
    $res = new Bdp_DOM_NodeList;
    foreach($this->nodes as $node){

      $newNode = $node->parentNode->appendChild($clone->nodes->current());
      $clone->nodes->next();
      $res->push($newNode);
    }
//     $this->remove();
    $res = new self($res);
    $res->removeClass('bdp-tpl');
    return $res;
  }

  /**
   * @return Bdp_DOM
   */
  public function tpl($query, $child_selector=':first-child')
  {
    $selectors = explode(',', $query);
    foreach($selectors as &$selector){
      $selector = trim($selector);
      $simples = self::_explodeSelector($selector);

      foreach($simples as &$simple){
        switch($simple[0]){
          case '>':
            $simple = ' > ';
          case ' ':
          break;
          default:
            $simple .= $child_selector;
        }
      }

      $selector = implode('', $simples);

//     dump($els->length());die;

      $simple = array_pop($simples);
      $simple = str_replace($child_selector, '', $simple);
      $cur_els = $this->find($selector);
      while(true) {
//         dumph($this);dumph($this->find('dl:first-child'));dumph($cur_els);die;
        $siblings = $cur_els->siblings($simple);
        $siblings->remove();
//         if(strpos($query, 'dt')!==false){dump($query); dump($simple); dump($selector); dumph($this); dumph($cur_els->parent()); dumph($siblings);die;}

        if(empty($simples)){
          break;
        }
        $sep = array_pop($simples);
        assert('!empty($simples); // syntax error in query');
        $simple = array_pop($simples);
  //       dump($simple);die;
        $simple = str_replace($child_selector, '', $simple);

        if($sep == ' '){
          $cur_els = $cur_els->closest($simple);
        } else {
          $cur_els = $cur_els->parent($simple);
        }
      }
    }
    $query = implode(', ', $selectors);
//     dump($query);dump($depth);dump($simples);die;
    $els = $this->find($query);
    $els->addClass('bdp-tpl');
//     if(strpos($query, 'dl')!==false){die;}
//     dump($els);
// die;
    return $els;
  }

  public function contains( DOMNode $node ){
//      dump('contains ?');
//   dump($node->getNodePath());
//     dump($node); dumph($node);
//   dumph($node->parentNode->parentNode);
//   dump('in :');
    $node->normalize();
    foreach($this->nodes as $node2){
//   dump($node2->getNodePath());
//       dump($node2); dumph($node2);
//   dumph($node2->parentNode->parentNode);
      $node2->normalize();
      if($node2->isSameNode($node)){
//         dump('true');
//         dump('--------------------');
        return true;
      }
    }
//     dump('false');
//         dump('--------------------');
    return false;
  }

  public function objectField($object)
  {
    $field_name = array_pop(explode('-', $this->attr('id')));
    $getter = 'get'.ucfirst($field_name).'Label';
    $value = $object->$getter();
    return array(
      'value' => $value,
      'name' => $field_name
      );
  }

  public function time(Zend_Date $date, $constant=Zend_Date::DATES)
  {
    $this->attr('datetime', $date->getIso())
      ->text($date->get($constant));
  }
}

/**
 * Iterator used to serve internal Bdp_DOM_NodeList content
 */
class Bdp_DOM_InternalIterator implements Iterator
{
  private $nodes;

  public function __construct( Bdp_DOM_NodeList $list )
  {
    $this->nodes = $list;
  }

  public function rewind()
  {
    $this->nodes->rewind();
  }

  public function key()
  {
    return null;
  }

  public function next()
  {
    $this->nodes->next();
  }

  public function valid()
  {
    return $this->nodes->valid();
  }

  public final function current()
  {
    $node = $this->nodes->current();

    if($node instanceOf DOMElement)
      return new Bdp_DOM( $node );
    else
      return $node;
  }
}

/**
 * Iterator used to serve native DOMNodeList content
 */
class Bdp_DOM_NativeIterator implements Iterator
{
  private $it_cpt = 0;
  private $nodes;

  public function __construct( DOMNodeList $nodes )
  {
    $this->nodes = $nodes;
  }

  public function rewind()
  {
    $this->it_cpt = 0;
  }

  public function key()
  {
    return null;
  }

  public function next()
  {
    ++$this->it_cpt;
  }

  public function valid()
  {
    return $this->it_cpt < $this->nodes->length;
  }

  public final function current()
  {
    $node = $this->nodes->item( $this->it_cpt );

    if($node instanceOf DOMElement)
      return new Bdp_DOM( $node );
    else
      return $node;
  }
}

/**
 * This class has been required because the native DOMNodeList doesn't provide merging and pushing methods
 * It implements a simple linked list
 */
class Bdp_DOM_NodeList implements Iterator
{
  public $length = 0;
  private $start;
  private $end;
  private $cur;

  public function __construct( DOMNode $node = null )
  {
    if(isset($node)){
      if($node instanceOf DOMDocument){
        $node = $node->documentElement;
      }
      $this->push($node);
    }
  }

  public function item( $index )
  {
    $cur = $this->start;

    $i = 0;
    while( $cur !== null )
    {
      if($i === $index)
        return $cur[0];

      ++$i;
      $cur = $cur[1];
    }

    return null;
  }

  /**
   * @param Bdp_DOM_NodeList|DOMNodeList $list
   */
  public function merge( $other )
  {
    if( $other instanceOf self )
    {
      if(!isset($this->start))
        $this->start = $other->start;

      $prev_end = $this->end;
      $this->end = $other->end;
      $prev_end[1] = $other->start;

      $this->length += $other->length;
    }
    else
    {
      foreach( $other as $node )
      {
        $this->push($node);
      }
    }
  }

  public function push( DOMNode $node = null )
  {
    if(!isset($node))
      return;

    $item = new SplFixedArray(2);
    $item[0] = $node;

    if(!isset($this->start))
      $this->cur = $this->end = $this->start = $item;
    else
      $this->end = $this->end[1] = $item;

    ++$this->length;
  }

  public function current()
  {
    if(!isset($this->cur))
      return null;

    return $this->cur[0];
  }

  public function valid()
  {
    return $this->cur !== null;
  }

  public function rewind()
  {
    $this->cur = $this->start;
  }

  public function key()
  {
    return null;
  }

  public function next()
  {
    $this->cur = $this->cur[1];
  }
}

/*
class SplFixedArray2 implements ArrayAccess
{
    private $container = array();
    public function __construct($size)
    {
        $this->container = array_fill(0, $size, null);
    }
    public function offsetSet($offset, $value) {
        $this->container[$offset] = $value;
    }
    public function offsetExists($offset) {
        return isset($this->container[$offset]);
    }
    public function offsetUnset($offset) {
        unset($this->container[$offset]);
    }
    public function offsetGet($offset) {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }
}*/
/*

  public static function tidyXml( $xml )
  {
      //<?xml version="1.0" encoding="windows-1251"?

      if(empty($xml))
        return '';

      if($xml[0] === '<' && $xml[1] === '?')
      {
        $pos = strpos($xml, 'encoding=');
        if($pos !== false)
        {
          $pos += 9; // strlen('encoding=');
          $encoding = '';
          while($xml[++$pos] !== '"' && $xml[$pos] !== "'")
          {
            $encoding .= $xml[$pos];
          }

          $pos = strpos($xml, '?>');
          if($pos===false)
            throw new Exception('Parse Error');

          $xml = substr($xml, $pos);

          $xml = iconv( $encoding, self::XML_ENCODING, $xml);

        }
      }

    //  throw new Exception($xml);
      $tmp = tempnam('tmp', 'tidy');
      file_put_contents($tmp, $xml);
      passthru('tidy -utf8 -xml -modify -q --output-bom no --markup no --force-output yes --quote-ampersand yes --quote-nbsp no '.$tmp);

      $output = trim(file_get_contents($tmp));
      unlink($tmp);/*
      if(empty($output))
      {

    file_put_contents('/tmp/output2', $xml);
        die;
      }*/
//       $config = array(
//       'xml' => true,
//      'output-xml' => true,/*
//      'drop-font-tags' => true,
//          'quote-nbsp' => false,
//            'quote-ampersand' => true,
//            'numeric-entities' => true,
//            'fix-uri' => true,
//            'escape-cdata' => true,
//            'drop-empty-paras' => true,
//            'doctype' => 'omit',
//            'force-output' => true,
//            'output-encoding' => 'utf8',
//            'output-bom' => false,*/
//             );
// // Tidy
//             return tidy_repair_string($xml, '-xml -uf8');
 //TA::dump($xml);die;
//             return $output;
//     }
//     public static function tidyHtml( $xml, $only_body = true )
//     {
//             $only_body = ($only_body)?' --show-body-only yes':'';
//     //  throw new Exception($xml);
//       $tmp = tempnam('tmp', 'tidy');
//       file_put_contents($tmp, $xml);
//       passthru('tidy -asxhtml -utf8 -modify -q -f /dev/null --add-xml-space yes --output-bom no --force-output yes --quote-ampersand yes --quote-nbsp no --drop-empty-paras yes --clean yes --doctype omit --numeric-entities yes --drop-proprietary-attributes yes --bare yes --drop-font-tags yes --word-2000 yes --enclose-text yes --escape-cdata yes --markup no --fix-bad-comments yes --fix-uri yes '.$only_body.' --logical-emphasis yes --output-xhtml yes '.$tmp);
//
//       $output = trim(file_get_contents($tmp));
//       unlink($tmp);
//       //TA::dump($xml);die;
//       return $output;
//   }*/