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
    public $allowDocumentType = false;
    /**
     * @var string|null
     */
    public $setRootElement = null;
    /**
     * @var string|callable|null
     */
    public $schemaOrCallable = null;
    /**
     * @var int
     */
    public $XML_OPTIONS = 0;

    public function __construct() {
        $this->XML_OPTIONS = LIBXML_NONET | LIBXML_NOBLANKS | ( defined( 'LIBXML_COMPACT' ) ? LIBXML_COMPACT : 0 );
    }

}