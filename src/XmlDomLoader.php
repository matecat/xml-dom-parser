<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 22/04/24
 * Time: 18:48
 *
 */

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
class XmlDomLoader {
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
    public static function load( $content, Config $config = null ) {
        if ( !extension_loaded( 'dom' ) ) {
            throw new RuntimeException( 'Extension DOM is required.' );
        }

        if ( is_null( $config ) ) {
            $config = new Config();
        }

        $internalErrors  = libxml_use_internal_errors( true );
        $disableEntities = libxml_disable_entity_loader();
        libxml_clear_errors();

        $dom                  = new DOMDocument( '1.0', 'UTF-8' );
        $dom->validateOnParse = true;

        if ( is_string( $config->setRootElement ) && !empty( $config->setRootElement ) ) {
            $content = "<$config->setRootElement>$content</$config->setRootElement>";
        }

        $res = $dom->loadXML( $content, $config->XML_OPTIONS );

        if ( !$res ) {
            libxml_disable_entity_loader( $disableEntities );

            throw new XmlParsingException( implode( "\n", static::getXmlErrors( $internalErrors ) ) );
        }

        $dom->normalizeDocument();

        libxml_use_internal_errors( $internalErrors );
        libxml_disable_entity_loader( $disableEntities );

        foreach ( $dom->childNodes as $child ) {
            if ( XML_DOCUMENT_TYPE_NODE === $child->nodeType && !$config->allowDocumentType ) {
                throw new XmlParsingException( 'Document types are not allowed.' );
            }
        }

        if ( null !== $config->schemaOrCallable ) {
            $internalErrors = libxml_use_internal_errors( true );
            libxml_clear_errors();

            $e = null;
            if ( is_callable( $config->schemaOrCallable ) ) {
                try {
                    $valid = call_user_func( $config->schemaOrCallable, $dom, $internalErrors );
                } catch ( Exception $e ) {
                    $valid = false;
                }
            } elseif ( !is_array( $config->schemaOrCallable ) && is_file( (string)$config->schemaOrCallable ) ) {
                $schemaSource = file_get_contents( (string)$config->schemaOrCallable );
                $valid        = @$dom->schemaValidateSource( $schemaSource );
            } else {
                libxml_use_internal_errors( $internalErrors );

                throw new XmlParsingException( 'The schemaOrCallable argument has to be a valid path to XSD file or callable.' );
            }

            if ( !$valid ) {
                $messages = static::getXmlErrors( $internalErrors );
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
     * @param $internalErrors
     *
     * @return array
     */
    private static function getXmlErrors( $internalErrors ) {
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