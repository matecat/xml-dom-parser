<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 08/05/24
 * Time: 15:47
 *
 */

namespace Matecat\XmlParser;

/**
 *
 */
class Config {

    /**
     * @var bool
     */
    protected $allowDocumentType = false;
    /**
     * @var string|null
     */
    protected $setRootElement = null;
    /**
     * @var string|callable|null
     */
    protected $schemaOrCallable = null;
    /**
     * @var int
     */
    protected $XML_OPTIONS = 0;

    public function __construct( $setRootElement = null, $allowDocumentType = false, $XML_OPTIONS = 0, $schemaOrCallable = null ) {
        $this->XML_OPTIONS       = $XML_OPTIONS | ( defined( 'LIBXML_COMPACT' ) ? LIBXML_COMPACT : 0 );
        $this->setRootElement    = $setRootElement;
        $this->allowDocumentType = $allowDocumentType;
        $this->schemaOrCallable  = $schemaOrCallable;
    }

    /**
     * @return bool|mixed
     */
    public function getAllowDocumentType() {
        return $this->allowDocumentType;
    }

    /**
     * @return mixed|string|null
     */
    public function getSetRootElement() {
        return $this->setRootElement;
    }

    /**
     * @return callable|mixed|string|null
     */
    public function getSchemaOrCallable() {
        return $this->schemaOrCallable;
    }

    /**
     * @return int
     */
    public function getXML_OPTIONS() {
        return $this->XML_OPTIONS;
    }

}