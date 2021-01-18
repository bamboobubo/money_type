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
declare(strict_types=1);

namespace Re2bit\Types\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use PHPUnit\Framework\TestCase;
use Re2bit\Types\Doctrine\DBAL\Money\AmountType;
use Re2bit\Types\Doctrine\DBAL\Money\CurrencyType;
use Re2bit\Types\Doctrine\DBAL\Money\MoneyEur16Type;
use Re2bit\Types\Doctrine\DBAL\Money\MoneyEur5Type;
use Re2bit\Types\Doctrine\DBAL\Money\MoneyEur8Type;
use Re2bit\Types\Doctrine\DBAL\Money\MoneyEurType;
use Re2bit\Types\MetadataLoader\DoctrineMetadataDriverFactory;
use ReflectionClass;
use RuntimeException;

abstract class AbstractDoctrineTest extends TestCase
{
    /** @var ClassMetadata[]  */
    protected array $metadata;
    protected ManagerRegistry $registry;
    protected Connection $connection;
    protected EntityManagerInterface $entityManager;
    protected static bool $typeRegistered = false;

    public function registerType(): void
    {
        Type::addType('money_amount', AmountType::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_amount', 'money_amount');

        Type::addType('money_eur', MoneyEurType::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_eur', 'money_eur');

        Type::addType('money_eur5', MoneyEur5Type::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_eur5', 'money_eur5');

        Type::addType('money_eur8', MoneyEur8Type::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_eur8', 'money_eur8');

        Type::addType('money_eur16', MoneyEur16Type::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_eur16', 'money_eur16');

        Type::addType('money_currency', CurrencyType::class);
        $this->connection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('db_money_currency', 'money_currency');
    }

    private function registerTypesOnce(): void
    {
        if (!static::$typeRegistered) {
            $this->registerType();
            static::$typeRegistered = true;
        }
    }

    protected function setUp(): void
    {
        $this->connection = $this->createConnection();
        $this->registerTypesOnce();
        $this->entityManager = $this->createEntityManager($this->connection);
        $this->metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $this->registry = new SimpleBaseManagerRegistry(
            function ($id) {
                switch ($id) {
                    case 'default_connection':
                        return $this->connection;

                    case 'default_manager':
                        return $this->entityManager;

                    default:
                        throw new RuntimeException(sprintf('Unknown service id "%s".', $id));
                }
            }
        );

        $this->prepareDatabase();
    }

    private function prepareDatabase(): void
    {
        /** @var EntityManager $em */
        $em = $this->registry->getManager();

        $tool = new SchemaTool($em);
        $tool->createSchema($em->getMetadataFactory()->getAllMetadata());
    }

    private function createConnection(): Connection
    {
        /** @phpstan-ignore-next-line */
        return DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }

    private function createEntityManager(Connection $con, ?Configuration $cfg = null): EntityManagerInterface
    {
        $fixtureNamespace = (new ReflectionClass($this))->getShortName();
        $metaDataFolder = __DIR__ . '/Fixtures/Doctrine/Entity/' . $fixtureNamespace;
        if (!file_exists($metaDataFolder)) {
            static::markTestSkipped('Invalid Test Configuration. Doctrine Entities missing');
        }

        if (!$cfg) {
            $cfg = new Configuration();

            $annotationDriver = new AnnotationDriver(
                new AnnotationReader(),
                [$metaDataFolder]
            );
            $mappingDriverChain = new MappingDriverChain();
            $mappingDriverChain->setDefaultDriver(DoctrineMetadataDriverFactory::create());
            $mappingDriverChain->addDriver(
                $annotationDriver,
                'Fixtures\Doctrine\Entity\\' . $fixtureNamespace
            );
            $cfg->setMetadataDriverImpl($mappingDriverChain);
        }

        $cfg->setAutoGenerateProxyClasses(true);
        $cfg->setProxyNamespace('JMS\Serializer\DoctrineProxy');
        $cfg->setProxyDir(sys_get_temp_dir() . '/money-type-proxies');

        return EntityManager::create($con, $cfg);
    }
}
