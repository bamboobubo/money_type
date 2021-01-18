<?php

/**
 * This file is part of the re2bit/money_type library
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) René Gerritsen <https://re2bit.de>
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Re2bit\Types\Symfony\Component\Serializer\Annotation;

/**
 * Annotation class for @Money().
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 */
class Money
{
    public ?int $precision = null;

    public ?string $code = null;

    public ?string $locale = null;

    /**
     * @var string
     *
     * @Enum({"ARRAY", "DECIMAL", "STRING", "INTEGER", "FLOAT"})
     */
    public string $type = 'array';

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }
}
