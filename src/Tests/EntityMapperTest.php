<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Tests;

use Ruvents\SpiralInput\Annotation\HydrateFromEntity;
use Ruvents\SpiralInput\Exception\EntityNotFoundException;
use Ruvents\SpiralInput\Input\EntityMapper;
use Laminas\Hydrator\ObjectPropertyHydrator;
use Spiral\Core\FactoryInterface;
use Spiral\Filters\ArrayInput;

 /**
  * @internal
  */
 final class EntityMapperTest extends TestCase
 {
     protected function setUp(): void
     {
         parent::setUp();
         $this->initORM();
     }

     public function testInputMapping(): void
     {
         $entityMapper = $this->getEntityMapper();
         $result = $entityMapper->map(
            $this->getMappingObject(),
            new ArrayInput(['id' => 1])
         );

         $this->assertSame(1, $result->id);
         $this->assertSame('User1', $result->username);
     }

     public function testExceptionOnNonExistingEntity(): void
     {
         $this->expectException(EntityNotFoundException::class);
         $entityMapper = $this->getEntityMapper();
         $result = $entityMapper->map(
            $this->getMappingObject(),
            new ArrayInput(['id' => 999])
         );
     }

     public function testMappingFromLoadedEntity(): void
     {
         $entityMapper = $this->getEntityMapper();
         $result = $entityMapper->map(
            new #[HydrateFromEntity('User', from: 'array:user')] class()
            {
                public ?int $id = null;
                public ?string $username = null;
            },
            new ArrayInput(['user' => (object) ['id' => 1, 'username' => 'User1']])
         );

         $this->assertSame(1, $result->id);
         $this->assertSame('User1', $result->username);
     }

     public function testMappingWithExcludedFields(): void
     {
         $entityMapper = $this->getEntityMapper();
         $result = $entityMapper->map(
            new #[HydrateFromEntity('User', from: 'array:id', exclude: ['username'])] class()
            {
                public ?int $id = null;
                public ?string $username = null;
            },
            new ArrayInput(['id' => 1])
         );

         $this->assertSame(1, $result->id);
         $this->assertNull($result->username);
     }

     private function getMappingObject(): object
     {
         return new #[HydrateFromEntity('User', from: 'array:id')] class()
         {
             public ?int $id = null;
             public ?string $username = null;
         };
     }

     private function getEntityMapper(): EntityMapper
     {
         $factory = $this->container->get(FactoryInterface::class);

         return $factory->make(EntityMapper::class, [
            'extractor' => new ObjectPropertyHydrator(),
            'hydrator' => new ObjectPropertyHydrator(),
         ]);
     }
 }
