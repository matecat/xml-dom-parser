<?php
/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 16/02/26
 * Time: 14:15
 *
 */

declare(strict_types=1);

namespace Matecat\XmlParser;

use Exception;

/**
 * @internal
 */
final readonly class ValidationResult {
    public function __construct(
        public bool $isValid,
        public ?Exception $exception
    ) {}
}

