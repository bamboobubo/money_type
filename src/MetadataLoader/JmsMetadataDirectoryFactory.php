<?php

namespace Re2bit\Types\MetadataLoader;

class JmsMetadataDirectoryFactory
{
    public static function create(): string
    {
        return (new self())();
    }

    public function __invoke(): string
    {
        return (__DIR__ . '/../Jms/Mappings');
    }
}
