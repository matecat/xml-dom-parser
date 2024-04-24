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
use DOMXPath;

class HtmlParser extends AbstractParser {

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