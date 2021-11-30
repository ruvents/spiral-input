<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Input;

use Ruvents\SpiralInput\Annotation\From;
use Ruvents\SpiralInput\Annotation\Loader\LoaderAttributeInterface;
use Ruvents\SpiralInput\Loader\Loader;
use Laminas\Hydrator\ReflectionHydrator;
use Psr\SimpleCache\CacheInterface;
use Spiral\Attributes\ReaderInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Filters\InputInterface;

final class InputMapper implements SingletonInterface
{
    private ReflectionHydrator $hydrator;

    private Loader $loader;

    private ReaderInterface $reader;

    private ?CacheInterface $cache;

    public function __construct(
        ReflectionHydrator $hydrator,
        Loader $loader,
        ReaderInterface $reader,
        ?CacheInterface $cache = null
    ) {
        $this->hydrator = $hydrator;
        $this->loader = $loader;
        $this->reader = $reader;
        $this->cache = $cache;
    }

    /**
     * Извлекает данные из input'а и мапит их на переданный объект согласно
     * схеме, указанной в атрибутах #[From] на свойствах объекта.
     *
     * @param T $object
     *
     * @return T
     * @template T
     */
    public function map(object $object, InputInterface $input, bool $load = true): object
    {
        return $this->hydrator->hydrate(
            $this->extract(\get_class($object), $input, $load),
            clone $object
        );
    }

    /**
     * Извлекает данные из input'а согласно схеме, указанной в атрибутах
     * #[From] на свойствах объекта.
     *
     * @param class-string $class
     */
    public function extract(string $class, InputInterface $input, bool $load = true): array
    {
        $data = [];

        foreach ($this->getMetadata($class) as $field => $metadata) {
            ['from' => $from, 'loader' => $loader] = $metadata;
            $value = $input->getValue(...explode(':', $from->source, 2));

            if (null === $value) {
                continue;
            }

            if ($load && null !== $loader) {
                $value = $this->loader->load($value, $loader);
            }

            $data[$field] = $value;
        }

        return $data;
    }

    /**
     * Создает карту с метаданными полей, включающие атрибуты From и Loader.
     *
     * @param class-string $class
     */
    private function getMetadata(string $class): array
    {
        $cacheKey = sprintf('%s:%s', __CLASS__, $class);

        if ($this->cache && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = array_reduce(
            (new \ReflectionClass($class))->getProperties(),
            function (array $carry, \ReflectionProperty $property): array {
                $from = $this->reader->firstPropertyMetadata($property, From::class);

                if (null === $from) {
                    return $carry;
                }

                $carry[$property->getName()] = [
                    'from' => $from,
                    'loader' => $this->reader
                        ->firstPropertyMetadata($property, LoaderAttributeInterface::class),
                ];

                return $carry;
            },
            []
        );

        if ($this->cache) {
            $this->cache->set($cacheKey, $result);
        }

        return $result;
    }
}
