<?php

declare(strict_types=1);

namespace Ruvents\SpiralInput\Input;

use Ruvents\SpiralInput\Annotation\HydrateFromEntity;
use Ruvents\SpiralInput\Exception\EntityNotFoundException;
use Ruvents\SpiralInput\Hydrator\ClassMethodsHydrator;
use Cycle\ORM\ORMInterface;
use Laminas\Hydrator\HydratorInterface;
use Laminas\Hydrator\ReflectionHydrator;
use Spiral\Attributes\ReaderInterface;
use Spiral\Filters\InputInterface;

final class EntityMapper
{
    private ReaderInterface $reader;

    private ORMInterface $orm;

    private HydratorInterface $extractor;

    private HydratorInterface $hydrator;

    public function __construct(
        ReaderInterface $reader,
        ORMInterface $orm,
        HydratorInterface $extractor = null,
        HydratorInterface $hydrator = null,
    ) {
        $this->reader = $reader;
        $this->orm = $orm;
        $this->extractor = $extractor ?? new ClassMethodsHydrator(false);
        $this->hydrator = $hydrator ?? new ReflectionHydrator();
    }

    /**
     * Возвращает объект с заполненными данными из сущности.
     *
     * @param T              $object
     * @param InputInterface $input
     *
     * @throws EntityNotFoundException
     *
     * @return T
     * @template T
     */
    public function map(object $object, InputInterface $input): object
    {
        if (null === $attribute = $this->getAttribute(\get_class($object))) {
            return $object;
        }

        if (null === $value = $this->getValue($attribute, $input)) {
            return $object;
        }

        if (null === $entity = $this->getEntity($attribute, $value)) {
            throw new EntityNotFoundException($attribute->entity, $attribute->loadBy, (string) $value);
        }

        $data = array_diff_key($this->extractor->extract($entity), array_flip($attribute->exclude));

        return $this->hydrator->hydrate($data, $object);
    }

    /**
     * @param class-string $class
     */
    public function getAttribute(string $class): ?HydrateFromEntity
    {
        return $this->reader->firstClassMetadata(
            new \ReflectionClass($class),
            HydrateFromEntity::class
        );
    }

    public function getValue(HydrateFromEntity $attribute, InputInterface $input): mixed
    {
        return $input->getValue(...explode(':', $attribute->from, 2));
    }

    /**
     * Загружает сущность согласно инструкциям из атрибута, передавая в
     * репозиторий в value.
     */
    public function getEntity(HydrateFromEntity $attribute, mixed $value): ?object
    {
        if (\is_object($value)) {
            return $value;
        }

        return $this->orm->getRepository($attribute->entity)
            ->findOne([$attribute->loadBy => $value])
        ;
    }
}
