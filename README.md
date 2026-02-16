[![Build Status](https://app.travis-ci.com/matecat/xml-dom-parser.svg?token=qBazxkHwP18h3EWnHjjF&branch=master)](https://app.travis-ci.com/matecat/xml-dom-parser)
[![license](https://img.shields.io/github/license/matecat/xml-dom-parser.svg)]()
[![Packagist](https://img.shields.io/packagist/v/matecat/xml-dom-parser.svg)]()
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=matecat_xml-dom-parser&metric=alert_status)](https://sonarcloud.io/summary/new_code?id=matecat_xml-dom-parser)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=matecat_xml-dom-parser&metric=coverage)](https://sonarcloud.io/summary/new_code?id=matecat_xml-dom-parser)
[![Reliability Rating](https://sonarcloud.io/api/project_badges/measure?project=matecat_xml-dom-parser&metric=reliability_rating)](https://sonarcloud.io/summary/new_code?id=matecat_xml-dom-parser)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=matecat_xml-dom-parser&metric=sqale_rating)](https://sonarcloud.io/summary/new_code?id=matecat_xml-dom-parser)

# Matecat XML DOM Parser

A lightweight PHP library for parsing XML and HTML documents into traversable object structures. Built on top of PHP's
DOMDocument with enhanced error handling and validation support.

## Requirements

- PHP 8.3+
- ext-dom
- ext-xml
- ext-libxml

## Installation

```bash
composer require matecat/xml-dom-parser
```

## Usage

### Parsing XML

```php
use Matecat\XmlParser\XmlParser;

$xml = '<?xml version="1.0"?>
<note>
    <to>Tove</to>
    <from>Jani</from>
    <message>Hello!</message>
</note>';

$parsed = XmlParser::parse($xml);

// Access elements
echo $parsed[0]->tagName;              // "to"
echo $parsed[0]->inner_html[0]->text;  // "Tove"
```

### Parsing XML Fragments

```php
use Matecat\XmlParser\XmlParser;

$fragment = '<tag id="1">Content</tag><tag id="2">More content</tag>';

$parsed = XmlParser::parse($fragment, isXmlFragment: true);

echo $parsed[0]->attributes['id'];     // "1"
echo $parsed[1]->inner_html[0]->text;  // "More content"
```

### Parsing HTML

```php
use Matecat\XmlParser\HtmlParser;

$html = '<html><head><title>Test</title></head><body><div>Content</div></body></html>';

$parsed = HtmlParser::parse($html);

echo $parsed[0]->inner_html[0]->tagName;  // "head"
echo $parsed[0]->inner_html[1]->tagName;  // "body"
```

### Using XmlDomLoader Directly

For more control over XML loading and validation:

```php
use Matecat\XmlParser\XmlDomLoader;
use Matecat\XmlParser\Config;

// Basic loading
$dom = XmlDomLoader::load($xmlContent);

// With configuration
$config = new Config(
    setRootElement: 'root',        // Wrap content in a root element
    allowDocumentType: false,      // Reject DOCTYPE declarations
    xmlOptions: LIBXML_NONET,      // libxml options
    schemaOrCallable: '/path/to/schema.xsd'  // XSD validation
);

$dom = XmlDomLoader::load($xmlContent, $config);
```

### Schema Validation

Validate XML against an XSD schema:

```php
use Matecat\XmlParser\XmlDomLoader;
use Matecat\XmlParser\Config;

$config = new Config(schemaOrCallable: '/path/to/schema.xsd');
$dom = XmlDomLoader::load($xml, $config);
```

Or use a custom validation callable:

```php
use Matecat\XmlParser\Config;
use Matecat\XmlParser\XmlDomLoader;
use DOMDocument;

$validator = function (DOMDocument $dom, bool $internalErrors): bool {
    // Custom validation logic
    return $dom->getElementsByTagName('required-element')->length > 0;
};

$config = new Config(schemaOrCallable: $validator);
$dom = XmlDomLoader::load($xml, $config);
```

## Parsed Element Structure

Each parsed element is an object with the following properties:

| Property       | Type           | Description                         |
|----------------|----------------|-------------------------------------|
| `node`         | `string`       | The raw XML/HTML of the element     |
| `tagName`      | `string`       | The element's tag name              |
| `attributes`   | `array`        | Key-value pairs of attributes       |
| `text`         | `string\|null` | Text content (for text nodes)       |
| `self_closed`  | `bool\|null`   | Whether the element is self-closing |
| `has_children` | `bool\|null`   | Whether the element has child nodes |
| `inner_html`   | `ArrayObject`  | Child elements                      |

## Exception Handling

The library throws specific exceptions for different error conditions:

- `XmlParsingException` - XML syntax errors or validation failures
- `InvalidXmlException` - XML is well-formed but invalid against schema
- `DomDependecyMissingException` - Required PHP extensions are missing

```php
use Matecat\XmlParser\XmlParser;
use Matecat\XmlParser\Exception\XmlParsingException;

try {
    $parsed = XmlParser::parse('<invalid><xml>');
} catch (XmlParsingException $e) {
    echo "Parse error: " . $e->getMessage();
}
```

## License

This project is licensed under the MIT License.
