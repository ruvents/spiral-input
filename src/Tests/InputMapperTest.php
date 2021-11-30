<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Tests;

use Ruvents\SpiralInput\Annotation\From;
use Ruvents\SpiralInput\Annotation\Loader;
use Spiral\Filters\ArrayInput;

/**
 * @internal
 */
class InputMapperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->initORM();
    }

    public function testInputMapping(): void
    {
        $mapper = $this->getMapper();

        $object = new class() {
            /**
             * @From("array:username")
             */
            private ?string $foo = null;

            /**
             * @From("array:something")
             */
            private bool $bar = false;

            private ?string $notMapped = null;

            public function getFoo(): ?string
            {
                return $this->foo;
            }

            public function getBar(): bool
            {
                return $this->bar;
            }

            public function getNotMapped(): ?string
            {
                return $this->notMapped;
            }
        };

        $input = new ArrayInput([
            'username' => 'John Example',
            'something' => true,
        ]);

        $newObject = $mapper->map($object, $input);

        $this->assertSame('John Example', $newObject->getFoo());
        $this->assertTrue($newObject->getBar());
        $this->assertNull($newObject->getNotMapped());
    }

    public function testInputMappingViaAttributes(): void
    {
        $mapper = $this->getMapper();

        $object = new class() {
            #[From('array:username')]
            private ?string $foo = null;

            public function getFoo(): ?string
            {
                return $this->foo;
            }
        };

        $input = new ArrayInput([
            'username' => 'John Example',
        ]);

        $newObject = $mapper->map($object, $input);

        $this->assertSame('John Example', $newObject->getFoo());
    }

    public function testDefaultValuesNotBeingOverwrittenByNullValue(): void
    {
        $mapper = $this->getMapper();

        $object = new class() {
            /**
             * @From("array:not_existing_key")
             */
            private array $value = [];

            public function getValue(): array
            {
                return $this->value;
            }
        };

        $input = new ArrayInput([]);
        $newObject = $mapper->map($object, $input);

        $this->assertSame([], $newObject->getValue());
    }

    public function testFilterWithLoaders(): void
    {
        $object = new class() {
            /**
             * @From("array:user")
             * @Loader\Entity("User")
             */
            private ?object $user = null;

            /**
             * @From("array:date")
             * @Loader\Constructor(\DateTimeImmutable::class)
             */
            private ?\DateTimeImmutable $date = null;

            public function getUser(): ?object
            {
                return $this->user;
            }

            public function getDate(): ?\DateTimeImmutable
            {
                return $this->date;
            }
        };

        $mapper = $this->getMapper();
        $newObject = $mapper->map($object, new ArrayInput(['user' => 1, 'date' => '1990-05-05']));

        $this->assertInstanceOf(\DateTimeImmutable::class, $newObject->getDate());
        $this->assertSame((new \DateTimeImmutable('1990-05-05'))->getTimestamp(), $newObject->getDate()->getTimestamp());

        $this->assertIsObject($newObject->getUser());
        $this->assertSame(1, $newObject->getUser()->id);
        $this->assertSame('User1', $newObject->getUser()->username);
    }

    public function testFilterWithLoaderAndNullValue(): void
    {
        $object = new class() {
            /**
             * @From("array:date")
             * @Loader\Constructor(\DateTimeImmutable::class)
             */
            private ?\DateTimeImmutable $date = null;

            public function getDate(): ?\DateTimeImmutable
            {
                return $this->date;
            }
        };

        $mapper = $this->getMapper();
        $newObject = $mapper->map($object, new ArrayInput([]));

        $this->assertNull($newObject->getDate());
    }

    public function testInputDataExtractionWithoutLoading(): void
    {
        $object = new class() {
            /**
             * @From("array:foo")
             */
            public string $foo;

            /**
             * @From("array:bar")
             * @Loader\Constructor(\DateTimeImmutable::class)
             */
            public string $bar;
        };

        $mapper = $this->getMapper();

        $this->assertSame(
            ['foo' => 'hello', 'bar' => 'world'],
            $mapper->extract(\get_class($object), new ArrayInput(['foo' => 'hello', 'bar' => 'world']), false)
        );
    }

    public function testInputDataExtractionWithLoading(): void
    {
        $object = new class() {
            /**
             * @From("array:foo")
             * @Loader\Constructor(\DateTimeImmutable::class)
             */
            public \DateTimeImmutable $foo;
        };

        $mapper = $this->getMapper();
        $data = $mapper->extract(\get_class($object), new ArrayInput(['foo' => '1990-05-05']));

        $this->assertInstanceOf(\DateTimeImmutable::class, $data['foo']);
        $this->assertSame(
            (new \DateTimeImmutable('1990-05-05'))->getTimestamp(),
            $data['foo']->getTimestamp()
        );
    }
}
