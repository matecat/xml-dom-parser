<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 24/04/24
 * Time: 18:03
 *
 */

declare(strict_types=1);

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
use stdClass;

abstract class AbstractParser {

    public const string FRAGMENT_DOCUMENT_ROOT = '_____root';
    public const string REGEXP_ENTITIES         = '/&#x([0-1]{0,1}[0-9A-F]{1,2})/u'; //&#x1E;  &#xE;
    public const string REGEXP_ASCII          = '/([\x{00}-\x{1F}\x{7F}])/u';

    /** @var array<string, array{symbol: string, placeHold: string, numeral: int}> */
    protected static array $asciiPlaceHoldMap = [
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
            '7F' => [ 'symbol' => 'DEL', 'placeHold' => '', 'numeral' => 0x7F ]
    ];

    protected bool $isXmlFragment;

    protected DOMDocument $dom;

    /**
     * @var ArrayObject<int, stdClass>
     */
    protected ArrayObject $elements;

    /**
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    protected function __construct( string $xml, bool $isXmlFragment, bool $isHtml = false ) {
        $xml                 = $this->removeNotPrintableChars( $xml );
        $this->isXmlFragment = $isXmlFragment;

        $this->dom = XmlDomLoader::load(
                $xml,
                new Config(
                        ( $isXmlFragment ? self::FRAGMENT_DOCUMENT_ROOT : null ),
                        $isHtml,
                        LIBXML_NONET | LIBXML_NOBLANKS
                )
        );

        $this->elements = new ArrayObject();
    }

    /**
     * We replace not printable chars with a placeholder.
     * This because DomDocument cannot handle not printable chars
     */
    protected function removeNotPrintableChars( string $seg ): string {

        preg_match_all( self::REGEXP_ASCII, $seg, $matches );

        if ( !empty( $matches[ 1 ] ) ) {
            $test_src = $seg;
            foreach ( $matches[ 1 ] as $v ) {
                $key      = sprintf( "%02X", ord( $v ) );
                $hexNum   = sprintf( "/(\\x{%s})/u", $key );
                $test_src = preg_replace( $hexNum, self::$asciiPlaceHoldMap[ $key ][ 'placeHold' ], $test_src, 1 );
            }

            $seg = $test_src;
        }

        preg_match_all( self::REGEXP_ENTITIES, $seg, $matches );

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
     * @param DOMNodeList<DOMNode> $elementList
     * @param ArrayObject<int, stdClass> $elements
     * @return ArrayObject<int, stdClass>
     */
    protected function mapElements( DOMNodeList $elementList, ArrayObject $elements ): ArrayObject {

        for ( $i = 0; $i < $elementList->length; $i++ ) {

            $element = $elementList->item( $i );

            if ( $element === null ) {
                // This is defensive code, DOMNodeList::item() returns null only
                // when the index is out of bounds, which shouldn't happen in a for loop iterating over $elementList->length.
                continue; // @codeCoverageIgnore
            }

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
     * @return array<string, string|null>
     */
    protected function getAttributes( DOMNode $element ): array {

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
     * @return ArrayObject<int, stdClass>
     * @throws DOMException
     */
    protected function extractNodes(): ArrayObject {

        $htmlNodeList = $this->getNodeListFromQueryPath();

        if ( !$htmlNodeList instanceof DOMNodeList ) {
            throw new DOMException( 'Bad DOMNodeList' ); // @codeCoverageIgnore
        }

        $firstNode = $htmlNodeList->item( 0 );
        if ( $this->isXmlFragment && $firstNode !== null && $firstNode->nodeName == self::FRAGMENT_DOCUMENT_ROOT ) {
            // there is a fake root node, skip the first element end start with child nodes
            $this->mapElements( $firstNode->childNodes, $this->elements );
        } else {
            $this->mapElements( $htmlNodeList, $this->elements );
        }

        return $this->elements;

    }

    /**
     * @return DOMNodeList<DOMNode>|false
     */
    abstract protected function getNodeListFromQueryPath(): DOMNodeList|false;

}