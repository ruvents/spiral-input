<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Tests;

use Ruvents\SpiralInput\Hydrator\ClassMethodsHydrator;
use PHPUnit\Framework\TestCase;

 /**
  * @internal
  */
 final class ClassMethodsHydratorTest extends TestCase
 {
     public function testExtract(): void
     {
         $hydrator = new ClassMethodsHydrator(false);
         $object = new class() {
             private string $id = '123';
             private bool $visible = true;

             public function getId(): string
             {
                 return $this->id;
             }

             public function isVisible(): bool
             {
                 return $this->visible;
             }
         };

         $this->assertSame(['id' => '123', 'visible' => true], $hydrator->extract($object));
     }
 }
