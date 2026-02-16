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
use Matecat\XmlParser\Exception\DomDependecyMissingException;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;

/**
 * This class is copied from Symfony\Component\Config\Util\XmlUtils:
 *
 * Please see:
 * https://github.com/symfony/config/blob/v4.0.0/Util/XmlUtils.php
 */
final class XmlDomLoader
{
    /**
     * Parses an XML string.
     *
     * @param string $content An XML string
     * @param Config|null $config
     *
     * @return DOMDocument
     *
     * @throws InvalidXmlException When parsing of XML with schema or callable produces any errors unrelated to the XML parsing itself
     * @throws XmlParsingException When parsing of XML file returns error
     */
    public static function load(string $content, ?Config $config = null): DOMDocument
    {
        if (!extension_loaded('dom')) {
            throw new DomDependecyMissingException('Extension DOM is required.'); // @codeCoverageIgnore
        }

        $config = $config ?? new Config();

        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $dom = self::createDomDocument($content, $config);

        libxml_use_internal_errors($internalErrors);

        self::validateDocumentType($dom, $config);
        self::validateWithSchema($dom, $config);

        libxml_clear_errors();

        return $dom;
    }

    /**
     * Creates and loads the DOMDocument from content.
     *
     * @throws XmlParsingException
     */
    private static function createDomDocument(string $content, Config $config): DOMDocument
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->validateOnParse = true;

        if (!empty($config->getSetRootElement())) {
            $content = "<{$config->getSetRootElement()}>$content</{$config->getSetRootElement()}>";
        }

        $internalErrors = libxml_use_internal_errors(true);
        $res = $dom->loadXML($content, $config->getXmlOptions());

        if (!$res) {
            throw new XmlParsingException(implode("\n", self::getXmlErrors($internalErrors)));
        }

        $dom->normalizeDocument();

        return $dom;
    }

    /**
     * Validates that the document doesn't contain a DOCTYPE when not allowed.
     *
     * @throws XmlParsingException
     */
    private static function validateDocumentType(DOMDocument $dom, Config $config): void
    {
        if ($config->getAllowDocumentType()) {
            return;
        }

        foreach ($dom->childNodes as $child) {
            if (XML_DOCUMENT_TYPE_NODE === $child->nodeType) {
                throw new XmlParsingException('Document types are not allowed.');
            }
        }
    }

    /**
     * Validates the document with schema or callable if configured.
     *
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    private static function validateWithSchema(DOMDocument $dom, Config $config): void
    {
        $schemaOrCallable = $config->getSchemaOrCallable();

        if ($schemaOrCallable === null) {
            return;
        }

        $internalErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        $validationResult = self::performValidation($dom, $schemaOrCallable, $internalErrors);

        if (!$validationResult->isValid) {
            self::handleValidationFailure($internalErrors, $validationResult->exception);
        }
    }

    /**
     * Performs the actual validation using a callable or schema file.
     *
     * @throws XmlParsingException
     */
    private static function performValidation(
        DOMDocument $dom,
        callable|string $schemaOrCallable,
        bool $internalErrors
    ): ValidationResult {
        if (is_callable($schemaOrCallable)) {
            return self::validateWithCallable($dom, $schemaOrCallable, $internalErrors);
        }

        if (is_file($schemaOrCallable)) {
            return self::validateWithSchemaFile($dom, $schemaOrCallable, $internalErrors);
        }

        libxml_use_internal_errors($internalErrors);
        throw new XmlParsingException('The schemaOrCallable argument has to be a valid path to XSD file or callable.');
    }

    /**
     * Validates using a callable.
     */
    private static function validateWithCallable(
        DOMDocument $dom,
        callable $callable,
        bool $internalErrors
    ): ValidationResult {
        try {
            $valid = call_user_func($callable, $dom, $internalErrors);
            return new ValidationResult((bool)$valid, null);
        } catch (Exception $e) {
            return new ValidationResult(false, $e);
        }
    }

    /**
     * Validates using an XSD schema file.
     *
     * @throws XmlParsingException
     */
    private static function validateWithSchemaFile(
        DOMDocument $dom,
        string $schemaFile,
        bool $internalErrors
    ): ValidationResult {
        $schemaSource = file_get_contents($schemaFile);

        // @codeCoverageIgnoreStart
        if ($schemaSource === false) {
            libxml_use_internal_errors($internalErrors);
            throw new XmlParsingException('Could not read schema file.');
        }
        // @codeCoverageIgnoreEnd

        $valid = @$dom->schemaValidateSource($schemaSource);
        return new ValidationResult($valid, null);
    }

    /**
     * Handles validation failure by throwing an appropriate exception.
     *
     * @throws InvalidXmlException
     * @throws XmlParsingException
     */
    private static function handleValidationFailure(bool $internalErrors, ?Exception $exception): void
    {
        $messages = self::getXmlErrors($internalErrors);

        if (empty($messages)) {
            throw new InvalidXmlException('The XML is not valid.', 0, $exception);
        }

        throw new XmlParsingException(implode("\n", $messages), 0, $exception);
    }

    /**
     * @return array<int, string>
     */
    private static function getXmlErrors(bool $internalErrors): array
    {
        $errors = [];
        foreach (libxml_get_errors() as $error) {
            $errors[] = sprintf(
                '[%s %s] %s (in %s - line %d, column %d)',
                LIBXML_ERR_WARNING == $error->level ? 'WARNING' : 'ERROR',
                $error->code,
                trim($error->message),
                $error->file ?: 'n/a',
                $error->line,
                $error->column
            );
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $errors;
    }
}
