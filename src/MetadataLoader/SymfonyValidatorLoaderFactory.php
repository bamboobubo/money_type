<?php

namespace Re2bit\Types\MetadataLoader;

use Symfony\Component\Validator\Mapping\Loader\XmlFileLoader;

class SymfonyValidatorLoaderFactory
{
    public static function create(): XmlFileLoader
    {
        return (new self())();
    }

    public function __invoke(): XmlFileLoader
    {
        return new XmlFileLoader(__DIR__ . '/../Symfony/Component/Validator/validation.xml');
    }
}
