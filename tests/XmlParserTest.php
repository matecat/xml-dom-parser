<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 23/04/24
 * Time: 18:16
 *
 */

namespace Matecat\XmlParser\Tests;

use Matecat\XmlParser\Exception\XmlParsingException;
use Matecat\XmlParser\HtmlParser;
use Matecat\XmlParser\XmlParser;
use PHPUnit\Framework\TestCase;

class XmlParserTest extends TestCase {

    /**
     * @test
     */
    public function can_parse_a_xml() {
        $xml    = file_get_contents( __DIR__ . '/files/note.xml' );
        $parsed = XmlParser::parse( $xml );

        $this->assertCount( 4, $parsed );
        $this->assertEquals( 'Tove', $parsed[ 0 ]->inner_html[ 0 ]->text );
        $this->assertEquals( 'Jani', $parsed[ 1 ]->inner_html[ 0 ]->text );
        $this->assertEquals( 'Reminder', $parsed[ 2 ]->inner_html[ 0 ]->text );
        $this->assertEquals( 'Don\'t forget me this weekend!', $parsed[ 3 ]->inner_html[ 0 ]->text );
    }

    /**
     * @test
     */
    public function can_parse_a_xliff() {
        $xliff  = file_get_contents( __DIR__ . '/files/no-target.xliff' );
        $parsed = XmlParser::parse( $xliff );

        $note = $parsed[ 0 ]->inner_html[ 0 ]->inner_html[ 0 ]->inner_html[ 0 ];
        $tu   = $parsed[ 0 ]->inner_html[ 0 ]->inner_html[ 0 ]->inner_html[ 1 ];

        $this->assertEquals( 'note', $note->tagName );
        $this->assertEquals( '', $note->text );
        $this->assertEquals( 'trans-unit', $tu->tagName );
        $this->assertEquals( 'pendo-image-e3aaf7b7|alt', $tu->attributes[ 'id' ] );
    }

    /**
     * @test
     */
    public function can_parse_a_string_containing_less_than_sign() {
        $string = 'In questa frase ci sono caratteri \'nie ontsnap\' nie! Per vedere come si comporta {+ o -} il filtro Markdown in presenza di #. Anche se non \u00e8_detto_che 2 * 2 &lt;5 con
         <ph id="1" canCopy="no" canDelete="no" dataRef="d1"/><ph id="2" canCopy="no" canDelete="no" dataRef="d2"/>.';
        $parsed = XmlParser::parse( $string, true );

        $this->assertCount( 4, $parsed );
        $this->assertEquals( 'd1', $parsed[ 1 ]->attributes[ 'dataRef' ] );
        $this->assertEquals( 'd2', $parsed[ 2 ]->attributes[ 'dataRef' ] );
    }

    /**
     * @test
     */
    public function can_parse_html_with_nested_escaped_html() {
        $html   = 'Testo libero contenente <pc id="1" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1">corsivo</pc>, <pc id="2" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2">grassetto</pc>, <pc id="3" canCopy="no" canDelete="no" dataRefEnd="d1" dataRefStart="d1"><pc id="4" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2">grassetto + corsivo</pc></pc> e <pc id="5" canCopy="no" canDelete="no" dataRefEnd="d3" dataRefStart="d3">larghezza fissa</pc>.';
        $parsed = XmlParser::parse( $html, true );

        $this->assertEquals( '<pc id="4" canCopy="no" canDelete="no" dataRefEnd="d2" dataRefStart="d2">grassetto + corsivo</pc>', $parsed[ 5 ]->inner_html[ 0 ]->node );
    }

    /**
     * @test
     */
    public function can_escape_correctly_nodes_containing_special_characters() {
        // this string contains ’
        $string = '<pc id="source4" dataRefStart="source4">The rider can’t tell if the driver matched the profile picture.</pc>';
        $parsed = HtmlParser::parse( $string, true );

        $pc = $parsed[ 0 ];

        $this->assertEquals( '<pc id="source4" dataRefStart="source4">The rider can’t tell if the driver matched the profile picture.</pc>', $pc->node );
        $this->assertEquals( 'The rider can’t tell if the driver matched the profile picture.', $pc->inner_html[ 0 ]->text );

        // this string contains > inside text
        $string = '&lt;pc id="source4" dataRefStart="source4"&gt;Questa stringa contiene un > a stringa.&lt;/pc&gt;';
        $parsed = HtmlParser::parse( $string, true );

        $pc = $parsed[ 0 ];
        $this->assertEquals( '&lt;pc id="source4" dataRefStart="source4"&gt;Questa stringa contiene un &gt; a stringa.&lt;/pc&gt;', $pc->node );
        $this->assertEquals( '<pc id="source4" dataRefStart="source4">Questa stringa contiene un > a stringa.</pc>', $pc->text );
    }

    /**
     * @test
     */
    public function can_parse_escaped_html_with_greater_than_symbol() {
        $string = 'Ödemenizin kapatılması için Ödemenizin kapatılması için &lt;Outage&gt; beklemenizi rica ediyoruz. <ph dataRef="source1" id="source1"/>';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 2, $parsed );
        $this->assertEquals( 'source1', $parsed[ 1 ]->attributes[ 'id' ] );
        $this->assertEquals( 'source1', $parsed[ 1 ]->attributes[ 'dataRef' ] );
    }

}