<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 23/04/24
 * Time: 16:45
 *
 */

namespace Matecat\XmlParser;

use DOMNodeList;
use DOMXPath;

class XmlParser extends AbstractParser {

    /**
     * @return DOMNodeList
     */
    protected function getNodeListFromQueryPath(){

        $xpath = new DOMXPath( $this->dom );

        if ( $this->isXmlFragment ) {
            $xmlNodeList = $xpath->query( "/" . self::fragmentDocumentRoot );
        } else {
            $xmlNodeList = $xpath->query( "*" );
        }

        return $xmlNodeList;
    }

}