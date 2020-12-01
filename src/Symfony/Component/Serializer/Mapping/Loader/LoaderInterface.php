<?php

namespace Re2bit\Types\Symfony\Component\Serializer\Mapping\Loader;

use Re2bit\Types\Symfony\Component\Serializer\Mapping\ClassMetadataInterface;

interface LoaderInterface
{
    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool;
}
