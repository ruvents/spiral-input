<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Tests;

use Ruvents\SpiralInput\Input\InputMapper;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Transaction;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Spiral\Attributes\AnnotationReader;
use Spiral\Attributes\AttributeReader;
use Spiral\Attributes\Composite\SelectiveReader;
use Spiral\Attributes\ReaderInterface;
use Spiral\Core\Container;
use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseManager;
use Spiral\Database\DatabaseProviderInterface;
use Spiral\Database\Driver\SQLite\SQLiteDriver;

/**
 * @internal
 */
class TestCase extends BaseTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();

        $this->container->bindSingleton(ReaderInterface::class, new SelectiveReader([
            new AnnotationReader(), new AttributeReader(),
        ]));
    }

    protected function getMapper(): InputMapper
    {
        return $this->container->get(InputMapper::class);
    }

    protected function initORM(): void
    {
        $dbal = new DatabaseManager(new DatabaseConfig([
            'default' => 'default',
            'databases' => [
                'default' => [
                    'driver' => 'memory',
                ],
            ],
            'drivers' => [
                'memory' => [
                    'driver' => SQLiteDriver::class,
                    'options' => [
                        'connection' => 'sqlite::memory:',
                    ],
                ],
            ],
        ]));
        $this->container->bindSingleton(DatabaseProviderInterface::class, $dbal);

        $orm = new ORM(
            new Factory($dbal),
            new Schema([
                'User' => [
                    Schema::MAPPER => StdMapper::class,
                    Schema::DATABASE => 'default',
                    Schema::TABLE => 'users',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS => [
                        'id' => 'id',
                        'username' => 'username',
                    ],
                    Schema::TYPECAST => [
                        'id' => 'int',
                    ],
                    Schema::RELATIONS => [],
                ],
            ]),
        );
        $this->container->bindSingleton(ORMInterface::class, $orm);

        $users = $dbal->database()->table('users')->getSchema();
        $users->primary('id');
        $users->string('username');
        $users->save();

        $t = $this->container->get(Transaction::class);
        $t->persist($orm->make('User', ['id' => 1, 'username' => 'User1']));
        $t->persist($orm->make('User', ['id' => 123, 'username' => 'User123']));
        $t->run();
    }
}
