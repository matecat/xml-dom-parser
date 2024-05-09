<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 24/04/24
 * Time: 18:03
 *
 */

namespace Matecat\XmlParser;

use ArrayObject;
use DOMAttr;
use DOMDocument;
use DOMException;
use DOMNode;
use DOMNodeList;
use DOMText;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;

abstract class AbstractParser {

    const fragmentDocumentRoot = '_____root';
    const regexpEntity         = '/&#x([0-1]{0,1}[0-9A-F]{1,2})/u'; //&#x1E;  &#xE;
    const regexpAscii          = '/([\x{00}-\x{1F}\x{7F}]{1})/u';
    protected static $asciiPlaceHoldMap = [
            '00' => [ 'symbol' => 'NULL', 'placeHold' => '', 'numeral' => 0x00 ],
            '01' => [ 'symbol' => 'SOH', 'placeHold' => '', 'numeral' => 0x01 ],
            '02' => [ 'symbol' => 'STX', 'placeHold' => '', 'numeral' => 0x02 ],
            '03' => [ 'symbol' => 'ETX', 'placeHold' => '', 'numeral' => 0x03 ],
            '04' => [ 'symbol' => 'EOT', 'placeHold' => '', 'numeral' => 0x04 ],
            '05' => [ 'symbol' => 'ENQ', 'placeHold' => '', 'numeral' => 0x05 ],
            '06' => [ 'symbol' => 'ACK', 'placeHold' => '', 'numeral' => 0x06 ],
            '07' => [ 'symbol' => 'BEL', 'placeHold' => '', 'numeral' => 0x07 ],
            '08' => [ 'symbol' => 'BS', 'placeHold' => '', 'numeral' => 0x08 ],
            '09' => [ 'symbol' => 'HT', 'placeHold' => '', 'numeral' => 0x09 ],
            '0A' => [ 'symbol' => 'LF', 'placeHold' => '', 'numeral' => 0x0A ],
            '0B' => [ 'symbol' => 'VT', 'placeHold' => '', 'numeral' => 0x0B ],
            '0C' => [ 'symbol' => 'FF', 'placeHold' => '', 'numeral' => 0x0C ],
            '0D' => [ 'symbol' => 'CR', 'placeHold' => '', 'numeral' => 0x0D ],
            '0E' => [ 'symbol' => 'SO', 'placeHold' => '', 'numeral' => 0x0E ],
            '0F' => [ 'symbol' => 'SI', 'placeHold' => '', 'numeral' => 0x0F ],
            '10' => [ 'symbol' => 'DLE', 'placeHold' => '', 'numeral' => 0x10 ],
            '11' => [ 'symbol' => 'DC', 'placeHold' => '', 'numeral' => 0x11 ],
            '12' => [ 'symbol' => 'DC', 'placeHold' => '', 'numeral' => 0x12 ],
            '13' => [ 'symbol' => 'DC', 'placeHold' => '', 'numeral' => 0x13 ],
            '14' => [ 'symbol' => 'DC', 'placeHold' => '', 'numeral' => 0x14 ],
            '15' => [ 'symbol' => 'NAK', 'placeHold' => '', 'numeral' => 0x15 ],
            '16' => [ 'symbol' => 'SYN', 'placeHold' => '', 'numeral' => 0x16 ],
            '17' => [ 'symbol' => 'ETB', 'placeHold' => '', 'numeral' => 0x17 ],
            '18' => [ 'symbol' => 'CAN', 'placeHold' => '', 'numeral' => 0x18 ],
            '19' => [ 'symbol' => 'EM', 'placeHold' => '', 'numeral' => 0x19 ],
            '1A' => [ 'symbol' => 'SUB', 'placeHold' => '', 'numeral' => 0x1A ],
            '1B' => [ 'symbol' => 'ESC', 'placeHold' => '', 'numeral' => 0x1B ],
            '1C' => [ 'symbol' => 'FS', 'placeHold' => '', 'numeral' => 0x1C ],
            '1D' => [ 'symbol' => 'GS', 'placeHold' => '', 'numeral' => 0x1D ],
            '1E' => [ 'symbol' => 'RS', 'placeHold' => '', 'numeral' => 0x1E ],
            '1F' => [ 'symbol' => 'US', 'placeHold' => '', 'numeral' => 0x1F ],
            '7F' => [ 'symbol' => 'DEL', 'placeHold' => '', 'numeral' => 0x7F ],
    ];

    /**
     * @var string
     */
    protected $isXmlFragment;

    /**
     * @var DOMDocument
     */
    protected $dom;

    protected $elements;

    /**
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    protected function __construct( $html, $isXmlFragment ) {
        $html                = $this->removeNotPrintableChars( $html );
        $this->isXmlFragment = $isXmlFragment;

        $this->dom = XmlDomLoader::load(
                $html,
                new Config(
                        ( $isXmlFragment ? self::fragmentDocumentRoot : null ),
                        true,
                        LIBXML_NONET | LIBXML_NOBLANKS
                )
        );

        $this->elements = new ArrayObject();
    }

    /**
     * This solution is taken from here and then modified:
     * https://www.php.net/manual/fr/regexp.reference.recursive.php#95568
     *
     * @param string $html
     * @param bool   $isXmlFragment
     *
     * @return ArrayObject
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    public static function parse( $html, $isXmlFragment = false ) {
        $parser = new static( $html, $isXmlFragment );

        return $parser->extractNodes();
    }

    /**
     * We replace not printable chars with a placeholder.
     * This because DomDocument cannot handle not printable chars
     *
     * @param $seg
     *
     * @return string
     */
    protected function removeNotPrintableChars( $seg ) {

        preg_match_all( self::regexpAscii, $seg, $matches );

        if ( !empty( $matches[ 1 ] ) ) {
            $test_src = $seg;
            foreach ( $matches[ 1 ] as $v ) {
                $key      = sprintf( "%02X", ord( $v ) );
                $hexNum   = sprintf( "/(\\x{%s})/u", $key );
                $test_src = preg_replace( $hexNum, self::$asciiPlaceHoldMap[ $key ][ 'placeHold' ], $test_src, 1 );
            }

            $seg = $test_src;
        }

        preg_match_all( self::regexpEntity, $seg, $matches );

        if ( !empty( $matches[ 1 ] ) ) {
            $test_src = $seg;
            foreach ( $matches[ 1 ] as $v ) {
                $byte = sprintf( "%02X", hexdec( $v ) );
                if ( $byte[ 0 ] == '0' ) {
                    $regexp = '/&#x([' . $byte[ 0 ] . ']?' . $byte[ 1 ] . ');/u';
                } else {
                    $regexp = '/&#x(' . $byte . ');/u';
                }

                $key = sprintf( "%02X", hexdec( $v ) );
                if ( array_key_exists( $key, self::$asciiPlaceHoldMap ) ) {
                    $test_src = preg_replace( $regexp, self::$asciiPlaceHoldMap[ $key ][ 'placeHold' ], $test_src );
                }

            }

            $seg = $test_src;
        }

        return $seg;
    }

    /**
     * @param DOMNodeList $elementList
     * @param ArrayObject $elements
     *
     * @return ArrayObject
     */
    protected function mapElements( DOMNodeList $elementList, ArrayObject $elements ) {

        for ( $i = 0; $i < $elementList->length; $i++ ) {

            $element = $elementList->item( $i );

            $elements[] = (object)[
                    'node'         => $this->dom->saveXML( $element ),
                    'tagName'      => $element->nodeName,
                    'attributes'   => $this->getAttributes( $element ),
                    'text'         => ( $element instanceof DOMText ) ? $element->textContent : null,
                    'self_closed'  => ( $element instanceof DOMText ) ? null : !$element->hasChildNodes(),
                    'has_children' => ( $element instanceof DOMText ) ? null : $element->hasChildNodes(),
                    'inner_html'   => $element->hasChildNodes() ? $this->mapElements( $element->childNodes, new ArrayObject() ) : new ArrayObject()
            ];

        }

        return $elements;

    }

    /**
     * @param DOMNode $element
     *
     * @return array
     */
    protected function getAttributes( DOMNode $element ) {

        if ( !$element->hasAttributes() ) {
            return [];
        }

        $attributesMap = [];

        /**
         * @var DOMAttr $attr
         */
        foreach ( $element->attributes as $attr ) {
            $attributesMap[ $attr->nodeName ] = $attr->nodeValue;
        }

        return $attributesMap;

    }

    /**
     * @return ArrayObject
     * @throws DOMException
     */
    protected function extractNodes() {

        $htmlNodeList = $this->getNodeListFromQueryPath();

        if ( !$htmlNodeList instanceof DOMNodeList ) {
            throw new DOMException( 'Bad DOMNodeList' );
        }

        if ( $this->isXmlFragment && $htmlNodeList->item( 0 )->nodeName == self::fragmentDocumentRoot ) {
            // there is a fake root node, skip the first element end start with child nodes
            $this->mapElements( $htmlNodeList->item( 0 )->childNodes, $this->elements );
        } else {
            $this->mapElements( $htmlNodeList, $this->elements );
        }

        return $this->elements;

    }

    /**
     * @return DOMNodeList
     */
    abstract protected function getNodeListFromQueryPath();

}