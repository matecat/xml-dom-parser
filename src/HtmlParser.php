<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 23/04/24
 * Time: 16:45
 *
 */

namespace Matecat\XmlParser;

use ArrayObject;
use DOMException;
use DOMXPath;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;

class HtmlParser extends AbstractParser {

    /**
     * This solution is taken from here and then modified:
     * https://www.php.net/manual/fr/regexp.reference.recursive.php#95568
     *
     * @param string $xml
     * @param bool   $isXmlFragment
     *
     * @return ArrayObject
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    public static function parse( $xml, $isXmlFragment = false ) {
        $parser = new static( $xml, $isXmlFragment, true );

        return $parser->extractNodes();
    }

    /**
     * @return ArrayObject
     */
    protected function getNodeListFromQueryPath() {

        $xpath = new DOMXPath( $this->dom );

        if ( $this->isXmlFragment ) {
            $htmlNodeList = $xpath->query( "/" . self::fragmentDocumentRoot );
        } else {
            $htmlNodeList = $xpath->query( "/html" );
        }

        return $htmlNodeList;

    }

}