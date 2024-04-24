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
use PHPUnit\Framework\TestCase;

class HtmlParserTest extends TestCase {

    /**
     * @test
     */
    public function can_parse_a_valid_html5_page() {
        $string = file_get_contents( __DIR__ . '/files/page.html' );
        $parsed = HtmlParser::parse( $string );

        $this->assertCount( 1, $parsed );
        $this->assertCount( 2, $parsed[ 0 ]->inner_html );
        $this->assertEquals( 'head', $parsed[ 0 ]->inner_html[ 0 ]->tagName );
        $this->assertEquals( 'body', $parsed[ 0 ]->inner_html[ 1 ]->tagName );
        $this->assertCount( 4, $parsed[ 0 ]->inner_html[ 0 ]->inner_html );
        $this->assertCount( 1, $parsed[ 0 ]->inner_html[ 1 ]->inner_html );
        $this->assertCount( 5, $parsed[ 0 ]->inner_html[ 1 ]->inner_html[ 0 ]->inner_html );
    }

    /**
     * @test
     */
    public function can_parse_html_with_greater_than_symbol() {
        $string = '<div id="1">Ciao > ciao<div id="2"></div></div>';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 1, $parsed );
        $this->assertEquals( 'Ciao > ciao', $parsed[ 0 ]->inner_html[ 0 ]->text );
        $this->assertEquals( '1', $parsed[ 0 ]->attributes[ 'id' ] );
        $this->assertEquals( '2', $parsed[ 0 ]->inner_html[ 1 ]->attributes[ 'id' ] );
    }

    /**
     * @test
     */
    public function can_not_parse_an_invalid_html() {
        $string = '<div id="1">< Ciao <<div id="2"></div></div>';

        $this->expectException( XmlParsingException::class );
        HtmlParser::parse( $string, true );

    }


    /**
     * @test
     */
    public function can_parse_html_with_greater_than_and_less_than_encoded_symbols() {
        $string = '<div id="1">Ciao &lt;> ciao<div id="2"></div></div>';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 1, $parsed );
        $this->assertEquals( 'Ciao <> ciao', $parsed[ 0 ]->inner_html[ 0 ]->text );
        $this->assertEquals( '1', $parsed[ 0 ]->attributes[ 'id' ] );
        $this->assertEquals( '2', $parsed[ 0 ]->inner_html[ 1 ]->attributes[ 'id' ] );
    }

    /**
     * @test
     */
    public function can_parse_html_with_greater_than_and_less_than_symbols_in_inversed_order() {
        $string = '<div id="1">Ciao > &lt; ciao<div id="2"></div></div>';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 1, $parsed );
        $this->assertEquals( 'Ciao > < ciao', $parsed[ 0 ]->inner_html[ 0 ]->text );
        $this->assertEquals( '1', $parsed[ 0 ]->attributes[ 'id' ] );
        $this->assertEquals( '2', $parsed[ 0 ]->inner_html[ 1 ]->attributes[ 'id' ] );
    }

    /**
     * @test
     */
    public function can_extract_inner_text() {
        $string = '<div class=\'text\'>questo è un testo</div>';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 1, $parsed );
        $this->assertEquals( 'text', $parsed[ 0 ]->attributes[ 'class' ] );
        $this->assertEquals( 'questo è un testo', $parsed[ 0 ]->inner_html[ 0 ]->text );

    }

    /**
     * @test
     */
    public function can_extract_inner_text_with_nested_html_content() {
        $string = '<div class=\'text\'><div>ciao questo è un testo</div> con del contenuto html.</div>';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 1, $parsed );
        $this->assertEquals( 'text', $parsed[ 0 ]->attributes[ 'class' ] );
        $this->assertEquals( '<div>ciao questo è un testo</div>', $parsed[ 0 ]->inner_html[ 0 ]->node );
        $this->assertEquals( ' con del contenuto html.', $parsed[ 0 ]->inner_html[ 1 ]->text );
    }

    /**
     * @test
     */
    public function can_parse_a_string_with_escaped_single_quotes() {
        $string = '<div class=\'text\'></div>';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 1, $parsed );
        $this->assertEquals( 'text', $parsed[ 0 ]->attributes[ 'class' ] );
        $this->assertNull( $parsed[ 0 ]->text );
    }

    /**
     * @test
     */
    public function can_parse_a_string_with_escaped_double_quotes() {
        $string = '<div class="text"></div>';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 1, $parsed );
        $this->assertEquals( 'text', $parsed[ 0 ]->attributes[ 'class' ] );
    }

    /**
     * @test
     */
    public function can_parse_a_string_containing_html() {
        $string = 'Testo libero contenente &lt;ph id="mtc_1" equiv-text="base64:Jmx0O3BjIGlkPSIxIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow=="/&gt;corsivo&lt;ph id="mtc_2" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;, &lt;ph id="mtc_3" equiv-text="base64:Jmx0O3BjIGlkPSIyIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDIiJmd0Ow=="/&gt;grassetto&lt;ph id="mtc_4" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;, &lt;ph id="mtc_5" equiv-text="base64:Jmx0O3BjIGlkPSIzIiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDEiIGRhdGFSZWZTdGFydD0iZDEiJmd0Ow=="/&gt;&lt;ph id="mtc_6" equiv-text="base64:Jmx0O3BjIGlkPSI0IiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDIiIGRhdGFSZWZTdGFydD0iZDIiJmd0Ow=="/&gt;grassetto + corsivo&lt;ph id="mtc_7" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;&lt;ph id="mtc_8" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt; e &lt;ph id="mtc_9" equiv-text="base64:Jmx0O3BjIGlkPSI1IiBjYW5Db3B5PSJubyIgY2FuRGVsZXRlPSJubyIgZGF0YVJlZkVuZD0iZDMiIGRhdGFSZWZTdGFydD0iZDMiJmd0Ow=="/&gt;larghezza fissa&lt;ph id="mtc_10" equiv-text="base64:Jmx0Oy9wYyZndDs="/&gt;.';
        $parsed = HtmlParser::parse( $string, true );

        $this->assertCount( 1, $parsed ); // this is a TextNode only
    }

    /**
     * @test
     */
    public function can_parse_html() {
        $html   = '<div class="row col-md-12" id="test">Ciao</div><div><h1 class="text-center">Title</h1><p>First p</p><p>Second p</p><p>Third p <span>with nested span</span></p></div>';
        $parsed = HtmlParser::parse( $html, true );

        $this->assertCount( 2, $parsed );
        $this->assertEquals( 'Ciao', $parsed[ 0 ]->inner_html[ 0 ]->text );
        $this->assertCount( 4, $parsed[ 1 ]->inner_html );

        $html   = '<div>Ciao</div><ph id="id" dataRef="d1" />';
        $parsed = HtmlParser::parse( $html, true );

        $this->assertCount( 2, $parsed );
    }

    /**
     * @test
     */
    public function can_parse_html_with_escaped_html() {
        $html   = '&lt;div&gt;Ciao&lt;div&gt;Ciao&lt;/div&gt;&lt;/div&gt;';
        $parsed = HtmlParser::parse( $html, true );

        $this->assertCount( 1, $parsed );
        $this->assertCount( 0, $parsed[ 0 ]->inner_html );
        $this->assertEquals( '&lt;div&gt;Ciao&lt;div&gt;Ciao&lt;/div&gt;&lt;/div&gt;', $parsed[ 0 ]->node );
    }

}