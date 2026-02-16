<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 22/04/24
 * Time: 18:55
 *
 */

declare(strict_types=1);

namespace Matecat\Tests\XmlParser;

use DOMAttr;
use DOMDocument;
use DOMElement;
use Exception;
use Matecat\XmlParser\Config;
use Matecat\XmlParser\Exception\InvalidXmlException;
use Matecat\XmlParser\Exception\XmlParsingException;
use Matecat\XmlParser\XmlDomLoader;
use PHPUnit\Framework\Attributes\Test;

class XmlLoaderTest extends Base
{

    /**
     * @throws Exception
     */
    #[Test]
    public function canCallLibxmlAndDom(): void
    {
        $domObject = XmlDomLoader::load($this->getTestFile('xliff12-with-more-emojis.xliff'));
        $tUnitsNodeList = $domObject->getElementsByTagName('trans-unit');

        $this->assertEquals(3, $tUnitsNodeList->length);

        /** @var DOMElement $tUnit */
        foreach ($tUnitsNodeList as $tUnit) {
            /** @var DOMAttr $elem */
            $elem = $tUnit->attributes->getNamedItem('id');
            if ($elem->value == "328_2") {
                $this->assertEquals(
                    'Look at these fantastic emojis: 👨🗔🇺🇸9️⃣👋🏻',
                    $tUnit->getElementsByTagName("source")->item(0)->nodeValue
                );
            }
        }
    }

    /**
     * @throws InvalidXmlException
     */
    #[Test]
    public function throwsExceptionOnInvalidXml(): void
    {
        $this->expectException(XmlParsingException::class);
        XmlDomLoader::load('<invalid><xml>');
    }

    /**
     * @throws InvalidXmlException
     */
    #[Test]
    public function throwsExceptionOnDocumentTypeWhenNotAllowed(): void
    {
        $xmlWithDoctype = '<!DOCTYPE html><html lang="it"><body>Test</body></html>';

        $this->expectException(XmlParsingException::class);
        $this->expectExceptionMessage('Document types are not allowed.');

        XmlDomLoader::load($xmlWithDoctype, new Config(null, false));
    }

    /**
     */
    #[Test]
    public function allowsDocumentTypeWhenConfigured(): void
    {
        $xmlWithDoctype = '<!DOCTYPE html><html lang="it"><body>Test</body></html>';
        try {
            XmlDomLoader::load($xmlWithDoctype, new Config(null, true));
        } catch (Exception) {
            $this->fail('Validation failed');
        }
    }

    /**
     */
    #[Test]
    public function validatesWithCallable(): void
    {
        $xml = '<root><item>Test</item></root>';

        $validator = function (DOMDocument $dom): bool {
            return $dom->getElementsByTagName('item')->length === 1;
        };

        try {
            $config = new Config(null, false, 0, $validator);
            XmlDomLoader::load($xml, $config);
        } catch (Exception) {
            $this->fail('Validation failed');
        }
    }

    /**
     * @throws XmlParsingException
     */
    #[Test]
    public function throwsExceptionWhenCallableReturnsFalse(): void
    {
        $xml = '<root><item>Test</item></root>';

        $validator = function (): bool {
            return false;
        };

        $config = new Config(null, false, 0, $validator);

        $this->expectException(InvalidXmlException::class);
        $this->expectExceptionMessage('The XML is not valid.');

        XmlDomLoader::load($xml, $config);
    }

    /**
     * @throws XmlParsingException
     */
    #[Test]
    public function throwsExceptionWhenCallableThrowsException(): void
    {
        $xml = '<root><item>Test</item></root>';

        $validator = function (): bool {
            throw new Exception('Validation failed');
        };

        $config = new Config(null, false, 0, $validator);

        $this->expectException(InvalidXmlException::class);

        XmlDomLoader::load($xml, $config);
    }

    /**
     * @throws InvalidXmlException
     */
    #[Test]
    public function throwsExceptionForInvalidSchemaPath(): void
    {
        $xml = '<root><item>Test</item></root>';

        $config = new Config(null, false, 0, '/non/existent/schema.xsd');

        $this->expectException(XmlParsingException::class);
        $this->expectExceptionMessage('The schemaOrCallable argument has to be a valid path to XSD file or callable.');

        XmlDomLoader::load($xml, $config);
    }

    /**
     */
    #[Test]
    public function validatesWithSchemaFile(): void
    {
        // Create a temporary XSD schema file
        $xsdContent = '<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
    <xs:element name="root">
        <xs:complexType>
            <xs:sequence>
                <xs:element name="item" type="xs:string"/>
            </xs:sequence>
        </xs:complexType>
    </xs:element>
</xs:schema>';

        $xsdFile = sys_get_temp_dir() . '/test_schema_' . uniqid() . '.xsd';
        file_put_contents($xsdFile, $xsdContent);

        try {
            $xml = '<root><item>Test</item></root>';
            $config = new Config(null, false, 0, $xsdFile);
            XmlDomLoader::load($xml, $config);
        } catch (Exception) {
            $this->fail('Validation failed');
        } finally {
            unlink($xsdFile);
        }
    }

    /**
     * @throws InvalidXmlException
     */
    #[Test]
    public function throwsExceptionWhenSchemaValidationFails(): void
    {
        // Create a temporary XSD schema file that won't match
        $xsdContent = '<?xml version="1.0" encoding="UTF-8"?>
<xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema">
    <xs:element name="different">
        <xs:complexType>
            <xs:sequence>
                <xs:element name="other" type="xs:string"/>
            </xs:sequence>
        </xs:complexType>
    </xs:element>
</xs:schema>';

        $xsdFile = sys_get_temp_dir() . '/test_schema_fail_' . uniqid() . '.xsd';
        file_put_contents($xsdFile, $xsdContent);

        try {
            $xml = '<root><item>Test</item></root>';
            $config = new Config(null, false, 0, $xsdFile);

            $this->expectException(XmlParsingException::class);

            XmlDomLoader::load($xml, $config);
        } finally {
            unlink($xsdFile);
        }
    }

    /**
     */
    #[Test]
    public function loadsWithRootElementConfig(): void
    {
        try {
            $xmlFragment = '<item>Test</item><item>Test2</item>';
            $config = new Config('root', false);
            $dom = XmlDomLoader::load($xmlFragment, $config);

            $this->assertEquals(2, $dom->getElementsByTagName('item')->length);
        } catch (Exception) {
            $this->fail('Validation failed');
        }
    }

}