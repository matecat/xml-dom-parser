<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 23/04/24
 * Time: 16:45
 *
 */

declare(strict_types=1);

namespace Matecat\XmlParser;

use ArrayObject;
use DOMException;
use DOMNameSpaceNode;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use stdClass;

final class XmlParser extends AbstractParser {

    /**
     * This solution is taken from here and then modified:
     * https://www.php.net/manual/fr/regexp.reference.recursive.php#95568
     *
     * @return ArrayObject<int, stdClass>
     * @throws DOMException
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    public static function parse( string $xml, bool $isXmlFragment = false ): ArrayObject {
        $parser = new self( $xml, $isXmlFragment );

        return $parser->extractNodes();
    }

    /**
     * @return DOMNodeList<DOMNode|DOMNameSpaceNode>|false
     */
    protected function getNodeListFromQueryPath(): DOMNodeList|false {

        $xpath = new DOMXPath( $this->dom );

        if ( $this->isXmlFragment ) {
            $xmlNodeList = $xpath->query( "/" . self::FRAGMENT_DOCUMENT_ROOT );
        } else {
            $xmlNodeList = $xpath->query( "*" );
        }

        return $xmlNodeList;
    }

}