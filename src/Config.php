<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 08/05/24
 * Time: 15:47
 *
 */

declare(strict_types=1);

namespace Matecat\XmlParser;

use Closure;

/**
 *
 */
readonly class Config {

    protected bool $allowDocumentType;
    protected ?string $setRootElement;
    protected Closure|string|null $schemaOrCallable;
    protected int $xmlOptions;

    public function __construct(
        ?string $setRootElement = null,
        bool $allowDocumentType = false,
        int $xmlOptions = 0,
        Closure|string|null $schemaOrCallable = null
    ) {
        $this->xmlOptions       = $xmlOptions | ( defined( 'LIBXML_COMPACT' ) ? LIBXML_COMPACT : 0 );
        $this->setRootElement    = $setRootElement;
        $this->allowDocumentType = $allowDocumentType;
        $this->schemaOrCallable  = $schemaOrCallable;
    }

    public function getAllowDocumentType(): bool {
        return $this->allowDocumentType;
    }

    public function getSetRootElement(): ?string {
        return $this->setRootElement;
    }

    public function getSchemaOrCallable(): Closure|string|null {
        return $this->schemaOrCallable;
    }

    public function getXmlOptions(): int {
        return $this->xmlOptions;
    }

}