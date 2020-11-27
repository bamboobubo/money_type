<?php

namespace Re2bit\Types\MetadataLoader;

use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;

class DoctrineMetadataDriverFactory
{
    public static function create(): MappingDriver
    {
        return (new self())();
    }

    public function __invoke(): MappingDriver
    {
        $namespaces = [
            realpath(__DIR__ . '/../Doctrine/ORM/Mappings') => 'Re2bit\Types',
        ];
        return new SimplifiedXmlDriver($namespaces);
    }
}
