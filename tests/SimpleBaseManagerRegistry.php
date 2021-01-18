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

namespace Re2bit\Types\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\AbstractManagerRegistry;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class SimpleBaseManagerRegistry extends AbstractManagerRegistry
{
    /** @var mixed[] */
    private array $services = [];
    /** @var callable */
    private $serviceCreator;

    /**
     * SimpleBaseManagerRegistry constructor.
     *
     * @param callable       $serviceCreator
     * @param string         $name
     * @param array|string[] $connections
     * @param array|string[] $managers
     * @param string|null    $defaultConnection
     * @param string|null    $defaultManager
     * @param string         $proxyInterface
     */
    public function __construct(
        callable $serviceCreator,
        $name = 'anonymous',
        array $connections = ['default' => 'default_connection'],
        array $managers = ['default' => 'default_manager'],
        $defaultConnection = null,
        $defaultManager = null,
        string $proxyInterface = 'Doctrine\Common\Persistence\Proxy'
    ) {
        if (null === $defaultConnection) {
            $defaultConnection = (string)key($connections);
        }
        if (null === $defaultManager) {
            $defaultManager = (string)key($managers);
        }

        parent::__construct($name, $connections, $managers, $defaultConnection, $defaultManager, $proxyInterface);

        if (!is_callable($serviceCreator)) {
            throw new InvalidArgumentException('$serviceCreator must be a valid callable.');
        }
        $this->serviceCreator = $serviceCreator;
    }

    /**
     * @param string $name
     *
     * @return mixed|object
     */
    public function getService($name)
    {
        if (isset($this->services[$name])) {
            return $this->services[$name];
        }

        return $this->services[$name] = call_user_func($this->serviceCreator, $name);
    }

    /**
     * @param string $name
     */
    public function resetService($name)
    {
        unset($this->services[$name]);
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    public function getAliasNamespace($alias): string
    {
        foreach (array_keys($this->getManagers()) as $name) {
            $manager = $this->getManager($name);

            if ($manager instanceof EntityManager) {
                try {
                    return $manager->getConfiguration()->getEntityNamespace($alias);
                } catch (ORMException $ex) {
                    // Probably mapped by another entity manager, or invalid, just ignore this here.
                }
            } else {
                throw new LogicException(sprintf('Unsupported manager type "%s".', get_class($manager)));
            }
        }

        throw new RuntimeException(sprintf('The namespace alias "%s" is not known to any manager.', $alias));
    }
}
