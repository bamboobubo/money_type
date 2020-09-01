<?php
declare(strict_types=1);

namespace Re2bit\Types\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\SchemaTool;
use DomainException;
use JMS\Serializer\ArrayTransformerInterface;
use JMS\Serializer\SerializerBuilder;
use PHPUnit\Framework\TestCase;
use Re2bit\Types\DBAL\Money\AmountType;
use Re2bit\Types\DBAL\Money\CurrencyType;
use Re2bit\Types\DBAL\Money\MoneyEur16Type;
use Re2bit\Types\DBAL\Money\MoneyEur5Type;
use Re2bit\Types\DBAL\Money\MoneyEur8Type;
use Re2bit\Types\DBAL\Money\MoneyEurType;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Validator\Validator\RecursiveValidator;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorBuilder;

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
        return DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
        ]);
    }

    private function createEntityManager(Connection $con, ?Configuration $cfg = null): EntityManagerInterface
    {
        $metaDataFolder = __DIR__ . '/Fixtures/Doctrine/Entity/' . (new ReflectionClass($this))->getShortName();
        if (!file_exists($metaDataFolder)) {
            static::markTestSkipped('Invalid Test Configuration. Doctrine Entities missing');
        }

        if (!$cfg) {
            $cfg = new Configuration();
            $cfg->setMetadataDriverImpl(new AnnotationDriver(new AnnotationReader(), [
                $metaDataFolder,
            ]));
        }

        $cfg->setAutoGenerateProxyClasses(true);
        $cfg->setProxyNamespace('JMS\Serializer\DoctrineProxy');
        $cfg->setProxyDir(sys_get_temp_dir() . '/money-type-proxies');

        return EntityManager::create($con, $cfg);
    }

    /**
     * @return RecursiveValidator|ValidatorInterface
     */
    protected function createValidator()
    {
        $builder = new ValidatorBuilder();
        $builder->addLoader(
            new AnnotationLoader(
                new AnnotationReader()
            )
        );

        return $builder->getValidator();
    }

    protected function createArrayTransformer(): ArrayTransformerInterface
    {
        $serializerBuilder = SerializerBuilder::create();
        $arrayTransformer = $serializerBuilder->build();
        if (!$arrayTransformer instanceof ArrayTransformerInterface) {
            throw new DomainException('No Array Transformer available');
        }
        return $arrayTransformer;
    }
}
