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
