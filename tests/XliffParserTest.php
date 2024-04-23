<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 22/04/24
 * Time: 18:55
 *
 */

namespace Matecat\XmlParser\Tests;

use DOMElement;
use Exception;
use Matecat\XmlParser\XmlParser;
use PHPUnit\Framework\TestCase;

class XliffParserTest extends TestCase {

    /**
     * @test
     *
     * @throws Exception
     */
    public function canCallLibxmlAndDom() {
        $domObject      = XmlParser::parse( file_get_contents( __DIR__ . '/files/xliff12-with-more-emojis.xliff' ) );
        $tUnitsNodeList = $domObject->getElementsByTagName( 'trans-unit' );

        $this->assertEquals( 3, $tUnitsNodeList->length );

        /** @var DOMElement $tUnit */
        foreach ( $tUnitsNodeList as $tUnit ) {
            if ( $tUnit->attributes->getNamedItem( 'id' )->value == "328_2" ) {
                $this->assertEquals( 'Look at these fantastic emojis: 👨🗔🇺🇸9️⃣👋🏻', $tUnit->getElementsByTagName( "source" )->item( 0 )->nodeValue );
            }
        }

    }
}