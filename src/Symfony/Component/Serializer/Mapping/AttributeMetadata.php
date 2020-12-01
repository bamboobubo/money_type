<?php

namespace Re2bit\Types\Symfony\Component\Serializer\Mapping;

/**
 * @author René Gerritsen <rene.gerritsen@me.com>
 */
class AttributeMetadata implements AttributeMetadataInterface
{
    private string $type;
    private string $name;
    private ?string $code;
    private ?string $locale;
    private ?int $precision;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): void
    {
        $this->locale = $locale;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getPrecision(): ?int
    {
        return $this->precision;
    }

    public function setPrecision(?int $precision): void
    {
        $this->precision = $precision;
    }

    /**
     * Returns the names of the properties that should be serialized.
     *
     * @return string[]
     */
    public function __sleep()
    {
        return ['name', 'type', 'code', 'precision'];
    }
}
