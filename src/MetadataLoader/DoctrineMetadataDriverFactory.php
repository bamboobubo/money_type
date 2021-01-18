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
