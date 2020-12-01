<?php

namespace Re2bit\Types\Symfony\Component\Serializer\Annotation;

/**
 * Annotation class for @Money().
 *
 * @Annotation
 * @Target({"PROPERTY", "METHOD"})
 *
 * @author René Gerritsen <rene.gerritsen@me.com>
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
