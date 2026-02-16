<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 22/04/24
 * Time: 18:48
 *
 */

declare(strict_types=1);

namespace Matecat\XmlParser;

use DOMDocument;
use Exception;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use RuntimeException;

/**
 * This class is copied from Symfony\Component\Config\Util\XmlUtils:
 *
 * Please see:
 * https://github.com/symfony/config/blob/v4.0.0/Util/XmlUtils.php
 */
final class XmlDomLoader {
    /**
     * Parses an XML string.
     *
     * @param string      $content An XML string
     * @param Config|null $config
     *
     * @return DOMDocument
     *
     * @throws InvalidXmlException When parsing of XML with schema or callable produces any errors unrelated to the XML parsing itself
     * @throws XmlParsingException When parsing of XML file returns error
     */
    public static function load( string $content, ?Config $config = null ): DOMDocument {
        if ( !extension_loaded( 'dom' ) ) {
            throw new RuntimeException( 'Extension DOM is required.' ); // @codeCoverageIgnore
        }

        if ( is_null( $config ) ) {
            $config = new Config();
        }

        $internalErrors  = libxml_use_internal_errors( true );
        libxml_clear_errors();

        $dom                  = new DOMDocument( '1.0', 'UTF-8' );
        $dom->validateOnParse = true;

        if ( is_string( $config->getSetRootElement() ) && !empty( $config->getSetRootElement() ) ) {
            $content = "<{$config->getSetRootElement()}>$content</{$config->getSetRootElement()}>";
        }

        $res = $dom->loadXML( $content, $config->getXML_OPTIONS() );

        if ( !$res ) {
            throw new XmlParsingException( implode( "\n", self::getXmlErrors( $internalErrors ) ) );
        }

        $dom->normalizeDocument();

        libxml_use_internal_errors( $internalErrors );

        foreach ( $dom->childNodes as $child ) {
            if ( XML_DOCUMENT_TYPE_NODE === $child->nodeType && !$config->getAllowDocumentType() ) {
                throw new XmlParsingException( 'Document types are not allowed.' );
            }
        }

        if ( null !== $config->getSchemaOrCallable() ) {
            $internalErrors = libxml_use_internal_errors( true );
            libxml_clear_errors();

            $e = null;
            if ( is_callable( $config->getSchemaOrCallable() ) ) {
                try {
                    $valid = call_user_func( $config->getSchemaOrCallable(), $dom, $internalErrors );
                } catch ( Exception $e ) {
                    $valid = false;
                }
            } elseif ( is_file( $config->getSchemaOrCallable() ) ) {
                $schemaSource = file_get_contents( $config->getSchemaOrCallable() );
                // @codeCoverageIgnoreStart
                if ( $schemaSource === false ) {
                    libxml_use_internal_errors( $internalErrors );
                    throw new XmlParsingException( 'Could not read schema file.' );
                }
                // @codeCoverageIgnoreEnd
                $valid = @$dom->schemaValidateSource( $schemaSource );
            } else {
                libxml_use_internal_errors( $internalErrors );

                throw new XmlParsingException( 'The schemaOrCallable argument has to be a valid path to XSD file or callable.' );
            }

            if ( !$valid ) {
                $messages = self::getXmlErrors( $internalErrors );
                if ( empty( $messages ) ) {
                    throw new InvalidXmlException( 'The XML is not valid.', 0, $e );
                }
                throw new XmlParsingException( implode( "\n", $messages ), 0, $e );
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors( $internalErrors );

        return $dom;
    }

    /**
     * @return array<int, string>
     */
    private static function getXmlErrors( bool $internalErrors ): array {
        $errors = [];
        foreach ( libxml_get_errors() as $error ) {
            $errors[] = sprintf(
                    '[%s %s] %s (in %s - line %d, column %d)',
                    LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                    $error->code,
                    trim( $error->message ),
                    $error->file ?: 'n/a',
                    $error->line,
                    $error->column
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors( $internalErrors );

        return $errors;
    }
}