<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Tests;

use Ruvents\SpiralInput\Annotation\Loader\ArrayOf;
use Ruvents\SpiralInput\Annotation\Loader\Constructor;
use Ruvents\SpiralInput\Annotation\Loader\Entity;
use Ruvents\SpiralInput\Annotation\Loader\Service;
use Ruvents\SpiralInput\Loader\Loader;

 /**
  * @internal
  */
 final class LoaderTest extends TestCase
 {
     protected function setUp(): void
     {
         parent::setUp();
         $this->initORM();
     }

     public function testConstructorLoader(): void
     {
         $loader = $this->container->get(Loader::class);
         $loadedValue = $loader->load('1990-05-05', new Constructor(\DateTimeImmutable::class));
         $this->assertInstanceOf(\DateTimeImmutable::class, $loadedValue);
         $this->assertSame((new \DateTimeImmutable('1990-05-05'))->getTimestamp(), $loadedValue->getTimestamp());

         // named
         $loadedValue = $loader->load(
            ['datetime' => '1990-05-05', 'timezone' => new \DateTimeZone('UTC')],
            new Constructor(\DateTimeImmutable::class, true)
         );
         $this->assertInstanceOf(\DateTimeImmutable::class, $loadedValue);
         $this->assertSame(
                (new \DateTimeImmutable('1990-05-05', new \DateTimeZone('UTC')))->getTimestamp(),
                $loadedValue->getTimestamp()
        );
         $this->assertSame('UTC', $loadedValue->getTimezone()->getName());
     }

     public function testEntityLoader(): void
     {
         $loader = $this->container->get(Loader::class);
         $loadedValue = $loader->load(1, new Entity('User'));
         $this->assertIsObject($loadedValue);
         $this->assertSame(1, $loadedValue->id);
         $this->assertSame('User1', $loadedValue->username);

         $loadedValue = $loader->load('User1', new Entity('User', 'username'));
         $this->assertIsObject($loadedValue);
         $this->assertSame(1, $loadedValue->id);
         $this->assertSame('User1', $loadedValue->username);
     }

     public function testServiceLoader(): void
     {
         $loader = $this->container->get(Loader::class);
         $this->container->bindSingleton(
            'SomeService',
            new class() {
                public function multiplyByTwo(int $value): int
                {
                    return $value * 2;
                }

                public function multiply(int $value, int $multiplier): int
                {
                    return $value * $multiplier;
                }
            }
         );
         $loadedValue = $loader->load(5, new Service('SomeService', 'multiplyByTwo'));
         $this->assertIsNumeric($loadedValue);
         $this->assertSame(10, $loadedValue);

         $loadedValue = $loader->load(5, new Service('SomeService', 'multiply', [10]));
         $this->assertIsNumeric($loadedValue);
         $this->assertSame(50, $loadedValue);
     }

     public function testArrayOfLoader(): void
     {
         $loader = $this->container->get(Loader::class);
         $loadedValue = $loader->load(
            [
                '1990-05-05',
                '1995-06-06',
            ],
            new ArrayOf(Constructor::class, [\DateTimeImmutable::class])
        );

         $this->assertIsArray($loadedValue);
         $this->assertCount(2, $loadedValue);
         $this->assertInstanceOf(\DateTimeImmutable::class, $loadedValue[0]);
         $this->assertSame((new \DateTimeImmutable('1990-05-05'))->getTimestamp(), $loadedValue[0]->getTimestamp());
         $this->assertInstanceOf(\DateTimeImmutable::class, $loadedValue[1]);
         $this->assertSame((new \DateTimeImmutable('1995-06-06'))->getTimestamp(), $loadedValue[1]->getTimestamp());
     }
 }
